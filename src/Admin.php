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
			$post_states['map_page'] = __( 'Map Page', 'webnorthcodechallenge' );
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
			__( 'OpenWeatherMap Settings', 'webnorthcodechallenge' ),
			function () {
				echo '<a name="wncc_weather_settings_section"></a>';
			},
			'general'
		);

		add_settings_field(
			'wncc_weather_api_key',
			__( 'API Key', 'webnorthcodechallenge' ),
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
}
