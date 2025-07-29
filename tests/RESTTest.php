<?php
namespace WebNorthCodeChallenge\Tests;

use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use WebNorthCodeChallenge\REST;
use WebNorthCodeChallenge\Plugin;
use WebNorthCodeChallenge\Interfaces\IAPI;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_Query;

class RESTTest extends TestCase {
	private REST $rest;
	private Plugin $plugin;

	protected function setUp(): void {
		parent::setUp();
		$this->plugin = $this->createMock( Plugin::class );
		$this->rest   = new REST( $this->plugin );

		Functions\when( '__' )->alias( fn( $text, $domain ) => $text );
		Functions\when( 'sanitize_text_field' )->alias( fn( $val ) => $val );
	}

	public function testConstruction(): void {
		$this->assertInstanceOf( REST::class, $this->rest );
	}

	public function testInitCallsRegisterHooks(): void {
		$rest = $this->getMockBuilder( REST::class )
			->setConstructorArgs( array( $this->plugin ) )
			->onlyMethods( array( 'register_hooks' ) )
			->getMock();
		$rest->expects( $this->once() )->method( 'register_hooks' );
		$rest->init();
	}

	public function testRegisterHooksAddsAction(): void {
		Functions\expect( 'add_action' )
			->once()
			->with( 'rest_api_init', array( $this->rest, 'register_rest_routes' ) );

		$method = ( new \ReflectionClass( $this->rest ) )->getMethod( 'register_hooks' );
		$method->setAccessible( true );
		$method->invoke( $this->rest );

		$this->assertTrue( true );
	}

	public function testRegisterRestRoutesRegistersRoute(): void {
		Functions\expect( 'register_rest_route' )
			->once()
			->with(
				REST::REST_NAMESPACE,
				'/weather-station/(?P<id>\d+)',
				$this->callback(
					function ( $args ) {
						$this->assertArrayHasKey( 'callback', $args );
						$this->assertArrayHasKey( 'args', $args );
						$this->assertArrayHasKey( 'id', $args['args'] );
						return true;
					}
				)
			);

		$this->rest->register_rest_routes();
	}

	public function testGetWeatherStationInvalidNonce(): void {
		$request = new WP_REST_Request();
		$request->set_header( 'X-WP-Nonce', 'bad_nonce' );

		Functions\when( 'wp_verify_nonce' )->justReturn( false );

		$result = $this->rest->get_weather_station( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'invalid_request', $result->get_error_code() );
	}

	public function testGetWeatherStationInvalidId(): void {
		$request = new WP_REST_Request();
		$request->set_header( 'X-WP-Nonce', 'good_nonce' );

		Functions\when( 'wp_verify_nonce' )->justReturn( true );

		$result = $this->rest->get_weather_station( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'invalid_station_id', $result->get_error_code() );
	}

	public function testGetWeatherStationStationNotFound(): void {
		$request = new WP_REST_Request();
		$request->set_param( 'id', 123 );
		$request->set_header( 'X-WP-Nonce', 'good_nonce' );

		Functions\when( 'wp_verify_nonce' )->justReturn( true );

		$query = $this->createMock( \WP_Query::class );
		$query->method( 'have_posts' )->willReturn( false );

		Functions\when( '\WP_Query' )->justReturn( $query );

		$result = $this->rest->get_weather_station( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'station_not_found', $result->get_error_code() );
	}

	public function testGetWeatherStationReturnsCachedWeatherData(): void {
		$request = new WP_REST_Request();
		$request->set_param( 'id', 123 );
		$request->set_header( 'X-WP-Nonce', 'good_nonce' );

		Functions\when( 'wp_verify_nonce' )->justReturn( true );

		$post = (object) array(
			'ID'         => 123,
			'post_title' => 'Test Station',
		);

		$queryMock        = $this->createMock( \WP_Query::class );
		$queryMock->posts = array( $post );
		$queryMock->method( 'have_posts' )->willReturn( true );

		WP_Query::$test_instance = $queryMock;

		$cached_weather = array(
			'main' => array(
				'metric'   => array( 'temp' => 20 ),
				'imperial' => array( 'temp' => 68 ),
			),
		);

		Functions\when( 'get_post_meta' )->alias(
			fn( $id, $key ) => match ( $key ) {
			'lat'                 => '51.5',
			'lng'                 => '-0.1',
			'weather_data'        => $cached_weather,
			'weather_data_updated' => time(),
			default               => '',
			}
		);

		$result = $this->rest->get_weather_station( $request, $queryMock );

		$this->assertInstanceOf( WP_REST_Response::class, $result );

		$data = $result->get_data();

		$this->assertEquals( 123, $data['id'] );
		$this->assertEquals( 'Test Station', $data['title'] );
		$this->assertEquals( $cached_weather, $data['weather'] );
	}

	public function testGetWeatherStationReturnsFreshWeatherData(): void {
		$request = new WP_REST_Request();
		$request->set_param( 'id', 123 );
		$request->set_header( 'X-WP-Nonce', 'good_nonce' );

		Functions\when( 'wp_verify_nonce' )->justReturn( true );

		$post = (object) array(
			'ID'         => 123,
			'post_title' => 'Test Station',
		);

		$queryMock        = new WP_Query();
		$queryMock->posts = array( $post );

		Functions\when( 'get_post_meta' )->alias(
			fn( $id, $key ) => match ( $key ) {
			'lat'                 => '51.5',
			'lng'                 => '-0.1',
			'weather_data_updated' => time() - ( 2 * DAY_IN_SECONDS ),
			default               => '',
			}
		);

		$apiMock = $this->createMock( IAPI::class );
		$apiMock->method( 'get_weather' )->willReturn(
			array(
				'main' => array( 'temp' => 10 ),
			)
		);

		$this->plugin->method( 'get_api' )->willReturn( $apiMock );

		Functions\when( 'update_post_meta' )->justReturn( true );

		$result = $this->rest->get_weather_station( $request, $queryMock );

		$this->assertInstanceOf( WP_REST_Response::class, $result );

		$data = $result->get_data();

		$this->assertArrayHasKey( 'weather', $data );
		$this->assertEquals( 10, $data['weather']['main']['metric']['temp'] );
	}
}
