<?php
/**
 * Admin class file.
 *
 * @package webnorthcodechallenge
 */

namespace WebNorthCodeChallenge;

use WP_Query;
use WP_Post;

/**
 * Class Admin
 *
 * Handles all admin-related functionality.
 */
class Admin {

	/**
	 * Plugin instance.
	 *
	 * @var Plugin
	 */
	protected Plugin $plugin;

	/**
	 * Admin constructor.
	 *
	 * @param Plugin $plugin The main plugin instance.
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Initialize admin logic.
	 */
	public function init(): void {
		$this->register_hooks();
	}

	/**
	 * Register admin-specific hooks.
	 */
	protected function register_hooks(): void {
		// Custom post state for the map page.
		add_filter( 'display_post_states', array( $this, 'add_map_page_state_label' ), 10, 2 );
		// Register settings for the OpenWeatherMap widget.
		add_action( 'admin_init', array( $this, 'register_weather_settings' ) );
		// Custom meta box for the weather station post type.
		add_action( 'add_meta_boxes', array( $this, 'add_weather_station_meta_box' ) );
		// Save meta box data for the weather station post type.
		add_action( 'save_post', array( $this, 'save_weather_station_meta_box' ) );
	}

	/**
	 * Add a custom label to the post states for the map page.
	 *
	 * @param array   $post_states The existing post states.
	 * @param WP_Post $post The current post object.
	 * @return array Modified post states with the custom label for the map page.
	 */
	public function add_map_page_state_label( $post_states, $post ) {
		if ( 'page' === $post->post_type && '1' === get_post_meta( $post->ID, '_is_map_page', true ) ) {
			$post_states['map_page'] = esc_html__( 'Map Page', 'webnorthcodechallenge' );
		}
		return $post_states;
	}

	/**
	 * Register settings, section, and fields for the OpenWeatherMap widget.
	 *
	 * @return void
	 */
	public function register_weather_settings(): void {
		$option_name = 'wncc_weather_settings';
		$section_id  = 'wncc_weather_settings_section';

		register_setting( 'general', $option_name );

		add_settings_section(
			$section_id,
			esc_html__( 'OpenWeatherMap Settings', 'webnorthcodechallenge' ),
			function () {
				echo '<a name="wncc_weather_settings_section"></a>';
			},
			'general'
		);

		add_settings_field(
			'wncc_weather_api_key',
			esc_html__( 'API Key', 'webnorthcodechallenge' ),
			array( $this, 'render_weather_api_key_field' ),
			'general',
			$section_id
		);
	}

	/**
	 * Render the input field for the OpenWeatherMap API key.
	 *
	 * @return void
	 */
	public function render_weather_api_key_field(): void {
		$options = get_option( 'wncc_weather_settings' );
		$api_key = $options['api_key'] ?? '';

		printf(
			"<input id='%s' name='%s[api_key]' size='110' type='text' value='%s' />",
			esc_attr( 'wncc_weather_api_key' ),
			esc_attr( 'wncc_weather_settings' ),
			esc_attr( $api_key )
		);
	}

	/**
	 * Add a meta box to the 'weather_station' post type.
	 *
	 * @return void
	 */
	public function add_weather_station_meta_box(): void {
		add_meta_box(
			'weather_station_meta_box',
			esc_html__( 'Weather Station Details', 'webnorthcodechallenge' ),
			array( $this, 'render_weather_station_meta_box' ),
			'weather_station',
			'normal',
			'high'
		);
	}

	/**
	 * Render the meta box for the 'weather_station' post type.
	 *
	 * @param WP_Post $post The current post object.
	 * @return void
	 */
	public function render_weather_station_meta_box( WP_Post $post ): void {
		// Nonce field for security.
		wp_nonce_field( 'weather_station_meta_box_nonce', 'weather_station_meta_box_nonce' );

		// Get existing meta values.
		$lat          = get_post_meta( $post->ID, 'lat', true );
		$lng          = get_post_meta( $post->ID, 'lng', true );
		$weather_data = get_post_meta( $post->ID, 'weather_data', true );

		echo '<table class="form-table">';

		// Latitude field.
		echo '<tr>';
		echo '<th><label for="lat">' . esc_html__( 'Latitude', 'webnorthcodechallenge' ) . '</label></th>';
		echo '<td><input type="text" id="lat" name="lat" class="regular-text" value="' . esc_attr( $lat ) . '" />';
		echo '<p class="description">' . esc_html__( 'Enter the latitude of the weather station.', 'webnorthcodechallenge' ) . '</p></td>';
		echo '</tr>';

		// Longitude field.
		echo '<tr>';
		echo '<th><label for="lng">' . esc_html__( 'Longitude', 'webnorthcodechallenge' ) . '</label></th>';
		echo '<td><input type="text" id="lng" name="lng" class="regular-text" value="' . esc_attr( $lng ) . '" />';
		echo '<p class="description">' . esc_html__( 'Enter the longitude of the weather station.', 'webnorthcodechallenge' ) . '</p></td>';
		echo '</tr>';

		// Weather Data (read-only display).
		echo '<tr>';
		echo '<th><label>' . esc_html__( 'Weather Data', 'webnorthcodechallenge' ) . '</label></th>';
		echo '<td>';
		if ( ! empty( $weather_data ) && is_array( $weather_data ) ) {
			$formatted_data = wp_json_encode( $weather_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
			echo '<pre style="background: #f8f8f8; padding: 1em; border: 1px solid #ddd; max-height: 300px; overflow: auto;">' . esc_html( $formatted_data ) . '</pre>';
		} else {
			echo '<p>' . esc_html__( 'No weather data available.', 'webnorthcodechallenge' ) . '</p>';
		}
		echo '<p class="description">' . esc_html__( 'This is the stored weather data in JSON format. It is read-only.', 'webnorthcodechallenge' ) . '</p>';
		echo '</td>';
		echo '</tr>';

		echo '</table>';
	}

	/**
	 * Save the meta box data for the 'weather_station' post type.
	 *
	 * @param int $post_id The ID of the post being saved.
	 *
	 * @return void
	 */
	public function save_weather_station_meta_box( int $post_id ): void {
		// Check nonce for security.
		if (
			! isset( $_POST['weather_station_meta_box_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['weather_station_meta_box_nonce'] ) ), 'weather_station_meta_box_nonce' )
		) {
			return;
		}

		// Check if the user has permission to edit the post.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save latitude and longitude.
		if ( isset( $_POST['lat'] ) ) {
			update_post_meta( $post_id, 'lat', sanitize_text_field( wp_unslash( $_POST['lat'] ) ) );
		}
		if ( isset( $_POST['lng'] ) ) {
			update_post_meta( $post_id, 'lng', sanitize_text_field( wp_unslash( $_POST['lng'] ) ) );
		}
	}
}
