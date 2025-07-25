<?php
/**
 * REST class file.
 *
 * @package webnorthcodechallenge
 */

namespace WebNorthCodeChallenge;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use WP_Query;

/**
 * Class REST
 */
class REST {

	/**
	 * REST namespace.
	 */
	public const REST_NAMESPACE = 'webnorthcodechallenge/v1';

	/**
	 * Plugin instance.
	 *
	 * @var Plugin
	 */
	protected Plugin $plugin;

	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin The plugin instance.
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Initialize class hooks.
	 */
	public function init(): void {
		$this->register_hooks();
	}

	/**
	 * Register hooks.
	 */
	protected function register_hooks(): void {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {

		// Register route for weather-station/${id}

		register_rest_route(
			self::REST_NAMESPACE,
			'/weather-station/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_weather_station' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'required'          => true,
						'validate_callback' => function ( $param ) {
							return is_numeric( $param );
						},
					),
				),
			)
		);
	}

	/**
	 * Callback for getting a weather station by ID.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_weather_station( WP_REST_Request $request ): WP_REST_Response|WP_Error {

		// Verify nonce for security.
		if ( ! wp_verify_nonce( sanitize_text_field( $request->get_header( 'X-WP-Nonce' ) ?? '' ), 'wp_rest' ) ) {
			return new WP_Error( 'invalid_request', __( 'Invalid request.', 'webnorthcodechallenge' ) );
		}

		// Get the station ID from the request.
		$station_id = (int) $request->get_param( 'id' );

		// Validate the station ID.
		if ( empty( $station_id ) || ! is_numeric( $station_id ) ) {
			return new WP_Error( 'invalid_station_id', __( 'Invalid station ID.', 'webnorthcodechallenge' ), array( 'status' => 400 ) );
		}

		// Query the database for the weather station.
		$query = new WP_Query(
			array(
				'post_type'      => 'weather_station',
				'p'              => $station_id,
				'posts_per_page' => 1,
				'post_status'    => 'publish',
			)
		);

		// Check if the station exists.
		if ( ! $query->have_posts() ) {
			return new WP_Error( 'station_not_found', __( 'Weather station not found.', 'webnorthcodechallenge' ), array( 'status' => 404 ) );
		}

		// Get the first post (should only be one due to 'p' parameter).
		$post = $query->posts[0];

		// Prepare the response data.
		$data = array(
			'id'    => $post->ID,
			'title' => $post->post_title,
		);

		return new WP_REST_Response( $data, 200 );
	}
}
