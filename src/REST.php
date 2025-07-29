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
		// Register route for weather-station/${id}.
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
	 * @param WP_Query|null   $query Optional WP_Query object for custom queries.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_weather_station( WP_REST_Request $request, WP_Query $query = null ): WP_REST_Response|WP_Error {
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
		$query = $query ?? new WP_Query(
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

		// Get lat, lng from post meta.
		$lat = get_post_meta( $post->ID, 'lat', true );
		$lng = get_post_meta( $post->ID, 'lng', true );

		if ( ! empty( $lat ) && ! empty( $lng ) ) {
			$weather_data    = get_post_meta( $post->ID, 'weather_data', true );
			$last_updated_ts = get_post_meta( $post->ID, 'weather_data_updated', true );
			$needs_update    = true;

			// Check if valid weather data and timestamp exist.
			if ( is_array( $weather_data ) && ! empty( $last_updated_ts ) ) {
				$age_seconds = time() - (int) $last_updated_ts;
				if ( $age_seconds < DAY_IN_SECONDS ) {
					$needs_update = false;
				}
			}

			if ( $needs_update ) {
				// Fetch weather data for both units.
				$api_metric   = $this->plugin->get_api()->get_weather( (float) $lat, (float) $lng, 'metric' );
				$api_imperial = $this->plugin->get_api()->get_weather( (float) $lat, (float) $lng, 'imperial' );

				// Extract and fallback to empty arrays if needed.
				$temp_metric   = $api_metric['main'] ?? array();
				$temp_imperial = $api_imperial['main'] ?? array();

				// Remove 'main' from metric data to avoid duplication.
				unset( $api_metric['main'] );

				// Prepare final structured weather data.
				$weather_data         = $api_metric;
				$weather_data['main'] = array(
					'metric'   => $temp_metric,
					'imperial' => $temp_imperial,
				);

				// Save to post meta.
				update_post_meta( $post->ID, 'weather_data', $weather_data );
				update_post_meta( $post->ID, 'weather_data_updated', time() );
			}

			// Final validation: ensure we still have usable data.
			if ( empty( $weather_data ) || ! is_array( $weather_data ) ) {
				return new WP_Error(
					'weather_data_error',
					__( 'Could not retrieve weather data.', 'webnorthcodechallenge' ),
					array( 'status' => 500 )
				);
			}

			// Merge the weather data into the response.
			$data['weather'] = $weather_data;
		}

		return new WP_REST_Response( $data, 200 );
	}
}
