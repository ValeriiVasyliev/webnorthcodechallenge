<?php
namespace WebNorthCodeChallenge\Tests;

use WebNorthCodeChallenge\Admin;
use WebNorthCodeChallenge\Plugin;
use Brain\Monkey\Functions;

/**
 * @covers \WebNorthCodeChallenge\Admin
 */
class AdminTest extends TestCase {

	private Admin $admin;
	private Plugin $plugin;

	protected function setUp(): void {
		parent::setUp();

		$this->plugin = $this->createMock( Plugin::class );
		$this->admin  = new Admin( $this->plugin );

		Functions\when( 'esc_attr' )->alias( fn( $val ) => $val );
		Functions\when( 'esc_html__' )->alias( fn( $text, $domain ) => $text );
		Functions\when( '__' )->alias( fn( $text, $domain ) => $text );
		Functions\when( 'get_option' )->justReturn( array( 'api_key' => 'test-key' ) );
		Functions\when( 'get_post_meta' )->alias( fn( $id, $key, $single ) => '' );
		Functions\when( 'wp_json_encode' )->alias( fn( $data, $flags = 0 ) => json_encode( $data, $flags ) );
		Functions\when( 'wp_unslash' )->alias( fn( $val ) => $val );
		Functions\when( 'sanitize_text_field' )->alias( fn( $val ) => $val );
	}

	public function testConstruction(): void {
		$this->assertInstanceOf( Admin::class, $this->admin );
	}

	public function testInitRegistersHooks(): void {
		Functions\expect( 'add_filter' )
			->once()
			->with( 'display_post_states', array( $this->admin, 'add_map_page_state_label' ), 10, 2 );

		Functions\expect( 'add_action' )
			->times( 3 )
			->andReturnUsing(
				function ( $hook ) {
					$this->assertContains( $hook, array( 'admin_init', 'add_meta_boxes', 'save_post' ) );
				}
			);

		$this->admin->init();
		$this->assertTrue( true );
	}

	public function testAddMapPageStateLabelAddsLabelIfMeta(): void {
		$post = new \WP_Post( 11, 'page' );

		Functions\when( 'get_post_meta' )->justReturn( '1' );

		$states = array();
		$result = $this->admin->add_map_page_state_label( $states, $post );

		$this->assertArrayHasKey( 'map_page', $result );
		$this->assertEquals( 'Map Page', $result['map_page'] );
	}

	public function testAddMapPageStateLabelSkipsIfNoMeta(): void {
		$post = new \WP_Post( 12, 'page' );

		Functions\when( 'get_post_meta' )->justReturn( '' );

		$states = array( 'existing' => 'Present' );
		$result = $this->admin->add_map_page_state_label( $states, $post );

		$this->assertArrayNotHasKey( 'map_page', $result );
		$this->assertArrayHasKey( 'existing', $result );
	}

	public function testAddMapPageStateLabelSkipsIfNotPage(): void {
		$post = new \WP_Post( 13, 'post' );

		Functions\when( 'get_post_meta' )->justReturn( '1' );

		$result = $this->admin->add_map_page_state_label( array(), $post );
		$this->assertArrayNotHasKey( 'map_page', $result );
	}

	public function testRegisterWeatherSettings(): void {
		Functions\expect( 'register_setting' )->once()->with( 'general', 'wncc_weather_settings' );
		Functions\expect( 'add_settings_section' )->once();
		Functions\expect( 'add_settings_field' )->once();

		$this->admin->register_weather_settings();
		$this->assertTrue( true );
	}

	public function testRenderWeatherApiKeyFieldOutputsInput(): void {
		Functions\when( 'get_option' )->justReturn( array( 'api_key' => 'the-key' ) );

		ob_start();
		try {
			$this->admin->render_weather_api_key_field();
			$out = ob_get_contents();
		} finally {
			ob_end_clean();
		}

		$this->assertStringContainsString( "name='wncc_weather_settings[api_key]'", $out );
		$this->assertStringContainsString( "value='the-key'", $out );
	}

	public function testRenderWeatherApiKeyFieldWithNoApiKey(): void {
		Functions\when( 'get_option' )->justReturn( array() );

		ob_start();
		try {
			$this->admin->render_weather_api_key_field();
			$out = ob_get_contents();
		} finally {
			ob_end_clean();
		}

		$this->assertStringContainsString( "name='wncc_weather_settings[api_key]'", $out );
		$this->assertStringContainsString( "value=''", $out );
	}

	public function testAddWeatherStationMetaBox(): void {
		Functions\expect( 'add_meta_box' )
			->once()
			->with(
				'weather_station_meta_box',
				'Weather Station Details',
				array( $this->admin, 'render_weather_station_meta_box' ),
				'weather_station',
				'normal',
				'high'
			);

		$this->admin->add_weather_station_meta_box();
		$this->assertTrue( true );
	}

	public function testRenderWeatherStationMetaBoxWithEmptyData(): void {
		$post = new \WP_Post( 99, 'weather_station' );

		Functions\when( 'get_post_meta' )->justReturn( '' );
		Functions\when( 'wp_nonce_field' )->justReturn( '' );

		ob_start();
		try {
			$this->admin->render_weather_station_meta_box( $post );
			$output = ob_get_contents();
		} finally {
			ob_end_clean();
		}

		$this->assertStringContainsString( 'Latitude', $output );
		$this->assertStringContainsString( 'Longitude', $output );
		$this->assertStringContainsString( 'No weather data available.', $output );
	}

	public function testSaveWeatherStationMetaBoxValid(): void {
		$_POST['weather_station_meta_box_nonce'] = 'valid';
		$_POST['lat']                            = '50.123';
		$_POST['lng']                            = '10.456';

		Functions\when( 'wp_verify_nonce' )->justReturn( true );
		Functions\when( 'current_user_can' )->justReturn( true );

		Functions\expect( 'update_post_meta' )->twice();

		$this->admin->save_weather_station_meta_box( 123 );
		$this->assertTrue( true );
	}

	public function testSaveWeatherStationMetaBoxInvalidPermissionSkips(): void {
		$_POST['weather_station_meta_box_nonce'] = 'valid';

		Functions\when( 'wp_verify_nonce' )->justReturn( true );
		Functions\when( 'current_user_can' )->justReturn( false );

		$this->admin->save_weather_station_meta_box( 456 );
		$this->assertTrue( true );
	}

	public function testSaveWeatherStationMetaBoxInvalidNonceSkips(): void {
		unset( $_POST['weather_station_meta_box_nonce'] );

		$this->admin->save_weather_station_meta_box( 789 );
		$this->assertTrue( true );
	}
}
