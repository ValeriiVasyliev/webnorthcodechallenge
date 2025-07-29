<?php
namespace WebNorthCodeChallenge\Tests\Unit;

use WebNorthCodeChallenge\Plugin;
use WebNorthCodeChallenge\Interfaces\IAPI;
use WebNorthCodeChallenge\Tests\TestCase;
use Brain\Monkey\Functions;
use Mockery;

class PluginTest extends TestCase {

	private Plugin $plugin;
	private IAPI $api;
	private string $plugin_file_path;
	private string $plugin_url = 'http://example.com/wp-content/plugins/webnorthcodechallenge/';
	private string $plugin_dir = '/var/www/wp-content/plugins/webnorthcodechallenge';

	protected function setUp(): void {
		parent::setUp();

		// Mock API interface
		$this->api              = Mockery::mock( IAPI::class );
		$this->plugin_file_path = $this->plugin_dir . '/plugin.php';

		// Mock WordPress functions
		Functions\when( 'plugin_dir_url' )->justReturn( $this->plugin_url );
		Functions\when( 'plugin_basename' )->justReturn( 'webnorthcodechallenge/plugin.php' );
		Functions\when( 'untrailingslashit' )->alias(
			function ( $input ) {
				return rtrim( $input, '/' );
			}
		);
		Functions\when( '__' )->returnArg();
		Functions\when( 'admin_url' )->justReturn( 'http://example.com/wp-admin/' );

		$this->plugin = new Plugin( $this->api, $this->plugin_file_path );
	}

	protected function tearDown(): void {
		parent::tearDown();
		unset( $GLOBALS['l10n']['webnorthcodechallenge'] );
	}

	public function testConstruction(): void {
		$this->assertInstanceOf( Plugin::class, $this->plugin );
		$this->assertEquals(
			'http://example.com/wp-content/plugins/webnorthcodechallenge',
			$this->plugin->plugin_url()
		);
		$this->assertEquals( $this->plugin_dir, $this->plugin->plugin_dir() );
	}

	public function testInit(): void {
		// Ensure textdomain is loaded
		unset( $GLOBALS['l10n']['webnorthcodechallenge'] );

		Functions\expect( 'add_action' )
			->once()
			->with( 'init', array( $this->plugin, 'register_weather_station_post_type' ) );

		Functions\expect( 'load_plugin_textdomain' )
			->once()
			->with(
				'webnorthcodechallenge',
				false,
				'webnorthcodechallenge/../languages/'
			);

		$this->plugin->init();

		$this->addToAssertionCount( 1 );
	}

	public function testRegisterWeatherStationPostType(): void {
		Functions\expect( 'register_post_type' )
			->once()
			->with(
				'weather_station',
				\Mockery::on(
					function ( $args ) {
						return isset( $args['labels'] ) &&
							isset( $args['public'] ) &&
							isset( $args['publicly_queryable'] ) &&
							$args['publicly_queryable'] === false &&
							isset( $args['rewrite'] ) &&
							$args['rewrite'] === false &&
							isset( $args['supports'] ) &&
							in_array( 'title', $args['supports'], true );
					}
				)
			);

		$this->plugin->register_weather_station_post_type();

		$this->addToAssertionCount( 1 );
	}

	public function testLoadPluginTextdomainWithExistingDomain(): void {
		$GLOBALS['l10n']['webnorthcodechallenge'] = true;

		Functions\expect( 'load_plugin_textdomain' )->never();

		$reflection = new \ReflectionClass( Plugin::class );
		$method     = $reflection->getMethod( 'load_plugin_textdomain' );
		$method->setAccessible( true );
		$method->invoke( $this->plugin );

		unset( $GLOBALS['l10n']['webnorthcodechallenge'] );
		$this->addToAssertionCount( 1 );
	}

	public function testLoadPluginTextdomainWithoutExistingDomain(): void {
		unset( $GLOBALS['l10n']['webnorthcodechallenge'] );

		Functions\expect( 'load_plugin_textdomain' )
			->once()
			->with(
				'webnorthcodechallenge',
				false,
				'webnorthcodechallenge/../languages/'
			);

		$reflection = new \ReflectionClass( Plugin::class );
		$method     = $reflection->getMethod( 'load_plugin_textdomain' );
		$method->setAccessible( true );
		$method->invoke( $this->plugin );

		$this->addToAssertionCount( 1 );
	}

	public function testGetPath(): void {
		$expected = $this->plugin_dir . '/includes/file.php';

		// Test with leading slash
		$this->assertEquals( $expected, $this->plugin->get_path( '/includes/file.php' ) );

		// Test without leading slash
		$this->assertEquals( $expected, $this->plugin->get_path( 'includes/file.php' ) );

		// Test empty path
		$this->assertEquals( $this->plugin_dir . '/', $this->plugin->get_path() );
	}

	public function testGetApi(): void {
		$this->assertSame( $this->api, $this->plugin->get_api() );
	}

	public function testRegisterHooks(): void {
		unset( $GLOBALS['l10n']['webnorthcodechallenge'] );

		Functions\expect( 'load_plugin_textdomain' )
			->once()
			->with(
				'webnorthcodechallenge',
				false,
				'webnorthcodechallenge/../languages/'
			);

		Functions\expect( 'add_action' )
			->once()
			->with( 'init', array( $this->plugin, 'register_weather_station_post_type' ) );

		Functions\expect( 'add_filter' )
			->once()
			->with( 'plugin_action_links_' . WEBNORTH_CODE_CHALLENGE_PLUGIN_FILE, array( $this->plugin, 'add_plugin_action_links' ) );

		$reflection = new \ReflectionClass( Plugin::class );
		$method     = $reflection->getMethod( 'register_hooks' );
		$method->setAccessible( true );
		$method->invoke( $this->plugin );

		$this->addToAssertionCount( 1 );
	}

	public function testAddPluginActionLinksAddsSettingsLink(): void {
		// Ensure admin_url returns expected URL
		Functions\when( 'admin_url' )->alias(
			function ( $path = '' ) {
				return 'http://example.com/wp-admin/' . ltrim( $path, '/' );
			}
		);

		$links    = array( 'Deactivate' );
		$modified = $this->plugin->add_plugin_action_links( $links );

		$this->assertStringContainsString( 'options-general.php#wncc_weather_settings_section', $modified[0] );
		$this->assertEquals( 'Deactivate', $modified[1] );
	}
}
