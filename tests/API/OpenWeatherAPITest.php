<?php

namespace WebNorthCodeChallenge\Tests\API;

use Brain\Monkey;
use PHPUnit\Framework\TestCase;
use WebNorthCodeChallenge\API\OpenWeatherAPI;

class OpenWeatherAPITest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Mock get_option to return a fake API key.
		Monkey\Functions\when( 'get_option' )->justReturn(
			array(
				'api_key' => 'fake-api-key',
			)
		);

		// Mock add_query_arg to simulate URL building.
		Monkey\Functions\when( 'add_query_arg' )->alias(
			function ( $args, $url ) {
				return $url . '?' . http_build_query( $args );
			}
		);
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_get_weather_successful_response() {
		$fake_response_data = array(
			'weather' => array( array( 'description' => 'clear sky' ) ),
			'main'    => array( 'temp' => 25.0 ),
		);
		$json_body          = json_encode( $fake_response_data );

		// Mock WordPress HTTP functions
		Monkey\Functions\when( 'wp_remote_get' )->justReturn(
			array(
				'body'     => $json_body,
				'headers'  => array(),
				'response' => array( 'code' => 200 ),
			)
		);
		Monkey\Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( 200 );
		Monkey\Functions\when( 'wp_remote_retrieve_body' )->justReturn( $json_body );
		Monkey\Functions\when( 'is_wp_error' )->justReturn( false );

		$api    = new OpenWeatherAPI();
		$result = $api->get_weather( 40.0, -3.0, 'metric' );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'weather', $result );
		$this->assertEquals( 'clear sky', $result['weather'][0]['description'] );
	}

	public function test_get_weather_api_returns_error_code() {
		Monkey\Functions\when( 'wp_remote_get' )->justReturn( array() );
		Monkey\Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( 404 );
		Monkey\Functions\when( 'is_wp_error' )->justReturn( false );

		$api    = new OpenWeatherAPI();
		$result = $api->get_weather( 40.0, -3.0, 'metric' );

		$this->assertFalse( $result );
	}

	public function test_get_weather_with_wp_error() {
		Monkey\Functions\when( 'wp_remote_get' )->justReturn( new \WP_Error( 'error', 'Something went wrong' ) );
		Monkey\Functions\when( 'is_wp_error' )->justReturn( true );

		$api    = new OpenWeatherAPI();
		$result = $api->get_weather( 40.0, -3.0 );

		$this->assertFalse( $result );
	}

	public function test_get_weather_with_invalid_json() {
		$invalid_json = '{invalid json}';

		Monkey\Functions\when( 'wp_remote_get' )->justReturn(
			array(
				'body'     => $invalid_json,
				'response' => array( 'code' => 200 ),
			)
		);
		Monkey\Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( 200 );
		Monkey\Functions\when( 'wp_remote_retrieve_body' )->justReturn( $invalid_json );
		Monkey\Functions\when( 'is_wp_error' )->justReturn( false );

		$api    = new OpenWeatherAPI();
		$result = $api->get_weather( 40.0, -3.0 );

		$this->assertFalse( $result );
	}
}
