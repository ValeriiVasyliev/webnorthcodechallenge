<?php
namespace WebNorthCodeChallenge\Tests\Unit;

use WebNorthCodeChallenge\Front;
use WebNorthCodeChallenge\Plugin;
use WebNorthCodeChallenge\Tests\TestCase;
use Brain\Monkey\Functions;
use org\bovigo\vfs\vfsStream;
use Patchwork;
use Mockery;

class FrontTest extends TestCase {
	private Front $front;
	private Plugin $plugin;
	private $vfsRoot;
	private string $mapTemplatePath;

	protected function setUp(): void {
		parent::setUp();

		// Setup virtual filesystem for plugin directory with templates
		$this->vfsRoot = vfsStream::setup(
			'plugin-dir',
			null,
			array(
				'templates' => array(
					'map.php' => '<?php // fake map template ?>',
				),
				'styles'    => array(
					'css' => array(
						'style.css' => '/* dummy css */',
					),
				),
				'js'        => array(
					'min' => array(
						'script.js' => 'console.log("dummy");',
					),
				),
				'img'       => array(
					'Logo.svg' => '<svg></svg>',
				),
			)
		);

		$pluginDir = $this->vfsRoot->url();

		// Mock plugin methods for directory and URL
		$this->plugin = $this->createMock( Plugin::class );
		$this->plugin->method( 'plugin_dir' )->willReturn( $pluginDir );
		$this->plugin->method( 'plugin_url' )->willReturn( 'http://example.com/wp-content/plugins/webnorthcodechallenge' );

		$this->front           = new Front( $this->plugin );
		$this->mapTemplatePath = $pluginDir . '/templates/map.php';

		// Patch exit() to throw exception so tests can catch it
		Patchwork\replace(
			'exit',
			function () {
				throw new \RuntimeException( 'Intercepted exit' );
			}
		);

		// Mock plugins_url to avoid "undefined function"
		Functions\when( 'plugins_url' )->alias(
			function ( $path = '', $plugin = '' ) {
				$base = 'http://example.com/wp-content/plugins/webnorthcodechallenge';
				if ( $path ) {
					return rtrim( $base, '/' ) . '/' . ltrim( $path, '/' );
				}
				return $base;
			}
		);
	}

	public function testInitRegistersHooks(): void {
		Functions\expect( 'add_filter' )
			->once()
			->with( 'template_include', array( $this->front, 'maybe_load_map_template' ) );
		Functions\expect( 'add_action' )
			->once()
			->with( 'wp_enqueue_scripts', array( $this->front, 'enqueue_scripts' ) );

		$this->front->init();

		$this->addToAssertionCount( 1 );
	}

	public function testMaybeLoadMapTemplateNotMapPage(): void {
		Functions\when( 'is_page' )->justReturn( false );
		$result = $this->front->maybe_load_map_template( '/some/template.php' );
		$this->assertEquals( '/some/template.php', $result );
	}

	public function testMaybeLoadMapTemplateWithMapPageAndThemeTemplate(): void {
		Functions\when( 'is_page' )->justReturn( true );
		Functions\when( 'locate_template' )->justReturn( $this->mapTemplatePath );

		try {
			$result = $this->front->maybe_load_map_template( '/base/template.php' );
			$this->assertEquals( $this->mapTemplatePath, $result );
		} catch ( \RuntimeException $e ) {
			$this->assertEquals( 'Intercepted exit', $e->getMessage() );
		}
	}

	public function testMaybeLoadMapTemplateWithMapPageAndPluginTemplate(): void {
		Functions\when( 'is_page' )->justReturn( true );
		Functions\when( 'locate_template' )->justReturn( false );

		// Ensure plugin_dir is returned for plugin template fallback
		$this->plugin->method( 'plugin_dir' )->willReturn( $this->vfsRoot->url() );

		try {
			$result = $this->front->maybe_load_map_template( '/base/template.php' );
			$this->assertEquals( $this->mapTemplatePath, $result );
		} catch ( \RuntimeException $e ) {
			$this->assertEquals( 'Intercepted exit', $e->getMessage() );
		}
	}

	public function testEnqueueScriptsNotMapPage(): void {
		Functions\when( 'is_page' )->justReturn( false );

		Functions\expect( 'wp_enqueue_style' )->never();
		Functions\expect( 'wp_enqueue_script' )->never();
		Functions\expect( 'wp_register_script' )->never();
		Functions\expect( 'wp_localize_script' )->never();

		$this->front->enqueue_scripts();

		$this->addToAssertionCount( 1 );
	}

	public function testEnqueueScriptsOnMapPage(): void {
		// Mock is_page('map') correctly with parameter match
		Functions\when( 'is_page' )->alias( fn ( $slug ) => $slug === 'map' );

		// Mock filemtime for CSS, JS, and Logo SVG to return a dummy timestamp
		Functions\when( 'filemtime' )->alias( fn ( $path ) => '123456' );

		// Expect Leaflet CSS to be enqueued
		Functions\expect( 'wp_enqueue_style' )
			->with(
				'leaflet',
				'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
				array(),
				'1.9.4'
			)
			->once();

		// Expect plugin CSS style to be enqueued
		Functions\expect( 'wp_enqueue_style' )
			->with(
				'webnorthcodechallenge-style',
				'http://example.com/wp-content/plugins/webnorthcodechallenge/styles/css/style.css',
				array(),
				'123456'
			)
			->once();

		// Expect Leaflet JS to be enqueued
		Functions\expect( 'wp_enqueue_script' )
			->with(
				'leaflet',
				'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
				array(),
				'1.9.4',
				true
			)
			->once();

		// Mock WP_Query to simulate posts for weather stations
		$mock_query        = Mockery::mock( 'WP_Query' );
		$mock_query->posts = array( 123, 456 );
		$mock_query->shouldReceive( 'have_posts' )->andReturn( true );
		$mock_query->shouldReceive( 'the_post' )->atMost()->times( 2 );

		// Replace WP_Query constructor with mock
		Functions\when( 'WP_Query' )->alias( fn () => $mock_query );

		// Mock post data fetching functions
		Functions\when( 'get_the_title' )->alias( fn ( $id ) => "Station $id" );
		Functions\when( 'get_post_meta' )->alias( fn ( $id, $key ) => $key === 'lat' ? 10.0 : ( $key === 'lng' ? 20.0 : null ) );

		// Setup plugin mock methods to return values
		$this->plugin->method( 'plugin_url' )->willReturn( 'http://example.com/wp-content/plugins/webnorthcodechallenge' );
		$this->plugin->method( 'plugin_dir' )->willReturn( $this->vfsRoot->url() );

		// Expect script registration for main front script
		Functions\expect( 'wp_register_script' )
			->with(
				'webnorth_code_challenge_front',
				'http://example.com/wp-content/plugins/webnorthcodechallenge/js/min/script.js',
				array( 'wp-i18n' ),
				'123456',
				true
			)
			->once();

		// Mock nonce and REST URL generation
		Functions\when( 'wp_create_nonce' )->justReturn( 'dummy-nonce' );
		Functions\when( 'rest_url' )->justReturn( 'http://example.com/wp-json/webnorthcodechallenge/v1/' );

		// Expect localized script data
		Functions\expect( 'wp_localize_script' )
			->with(
				'webnorth_code_challenge_front',
				'webnorthCodeChallengeSettings',
				Mockery::on(
					function ( $arg ) {
						return isset( $arg['nonce'], $arg['rest_url'], $arg['weather_stations'], $arg['logo'] )
						&& $arg['nonce'] === 'dummy-nonce'
						&& $arg['rest_url'] === 'http://example.com/wp-json/webnorthcodechallenge/v1/'
						&& is_array( $arg['weather_stations'] )
						&& count( $arg['weather_stations'] ) === 2
						&& $arg['weather_stations'][0]['id'] === 123
						&& $arg['weather_stations'][1]['id'] === 456
						&& str_ends_with( $arg['logo'], 'img/Logo.svg' );
					}
				)
			)
			->once();

		// Expect final script enqueue
		Functions\expect( 'wp_enqueue_script' )
			->with( 'webnorth_code_challenge_front' )
			->once();

		// Run the method under test
		$this->front->enqueue_scripts();

		$this->addToAssertionCount( 1 );
	}
}
