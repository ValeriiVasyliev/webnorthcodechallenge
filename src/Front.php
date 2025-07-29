<?php
/**
 * Front class file.
 *
 * @package webnorthcodechallenge
 */

namespace WebNorthCodeChallenge;

use WP_Query;

/**
 * Class Front
 *
 * Handles all public-facing functionality of the plugin.
 */
class Front {

	/**
	 * Plugin instance.
	 *
	 * @var Plugin
	 */
	protected Plugin $plugin;

	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin Main plugin instance.
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Initialize front-end logic.
	 */
	public function init(): void {
		$this->register_hooks();
	}

	/**
	 * Register front-end specific hooks.
	 */
	protected function register_hooks(): void {

		// Custom template for 'map' page.
		add_filter( 'template_include', array( $this, 'maybe_load_map_template' ) );

		// Enqueue scripts and styles for the front-end.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Load custom template for the 'map' page.
	 *
	 * @param string $template Current template path.
	 * @return string Modified template path if conditions are met.
	 */
	public function maybe_load_map_template( string $template ): string {
		if ( is_page( 'map' ) ) {

			$template = locate_template( 'webnorthcodechallenge/map.php' );
			if ( ! $template ) {
				$template = $this->plugin->plugin_dir() . '/templates/map.php';
			}

			include $template;

			exit();
		}
		return $template;
	}

	/**
	 * Enqueue scripts and styles for the front-end.
	 */
	public function enqueue_scripts(): void {

		// Check if the current page is the 'map' page.
		if ( is_page( 'map' ) ) {

			// Leaflet CSS.
			wp_enqueue_style(
				'leaflet',
				'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
				array(),
				'1.9.4'
			);

			// Plugin styles.
			wp_enqueue_style(
				'webnorthcodechallenge-style',
				plugins_url( 'styles/css/style.css', WEBNORTH_CODE_CHALLENGE_PLUGIN_FILE ),
				array(),
				filemtime( $this->plugin->plugin_dir() . '/styles/css/style.css' )
			);

			// Leaflet JS.
			wp_enqueue_script(
				'leaflet',
				'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
				array(),
				'1.9.4',
				true
			);

			// Get list of post types 'weather_station' to localize.
			$query = new WP_Query(
				array(
					'post_type'      => 'weather_station',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'no_found_rows'  => true,
					'post_status'    => 'publish',
				)
			);

			// Initialize an empty array for weather stations.
			$weather_stations = array();

			// If there are posts, loop through them and get the data.
			if ( $query->have_posts() ) {
				foreach ( $query->posts as $post_id ) {
					$weather_stations[] = array(
						'id'    => $post_id,
						'title' => get_the_title( $post_id ),
						'lat'   => get_post_meta( $post_id, 'lat', true ),
						'lng'   => get_post_meta( $post_id, 'lng', true ),
					);
				}
			}

			// Register the main script for the front-end.
			wp_register_script(
				'webnorth_code_challenge_front',
				$this->plugin->plugin_url() . '/js/min/script.js',
				array( 'wp-i18n' ),
				filemtime( $this->plugin->plugin_dir() . '/js/min/script.js' ),
				1
			);

			// Localize the script with action.
			wp_localize_script(
				'webnorth_code_challenge_front',
				'webnorthCodeChallengeSettings',
				array(
					'nonce'            => wp_create_nonce( 'wp_rest' ),
					'rest_url'         => rest_url( 'webnorthcodechallenge/v1/' ),
					'weather_stations' => $weather_stations,
					'logo'             => plugins_url( 'img/Logo.svg', WEBNORTH_CODE_CHALLENGE_PLUGIN_FILE ),
				)
			);

			// Enqueue the script.
			wp_enqueue_script( 'webnorth_code_challenge_front' );
		}
	}
}
