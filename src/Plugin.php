<?php
/**
 * Main Plugin class.
 *
 * @package webnorthcodechallenge
 */

namespace WebNorthCodeChallenge;

use WebNorthCodeChallenge\Interfaces\IAPI;
use WP_Error;

/**
 * Class Plugin
 *
 * Responsible for bootstrapping the plugin.
 */
class Plugin {

	/**
	 * Base URL to the plugin directory.
	 *
	 * @var string
	 */
	protected string $plugin_url;

	/**
	 * Absolute path to the plugin directory.
	 *
	 * @var string
	 */
	protected string $plugin_dir;

	/**
	 * API instance implementing IAPI interface.
	 *
	 * @var IAPI
	 */
	private IAPI $api;

	/**
	 * Plugin constructor.
	 *
	 * @param IAPI   $api              Injected API instance.
	 * @param string $plugin_file_path Path to main plugin file (__FILE__).
	 */
	public function __construct( IAPI $api, string $plugin_file_path ) {
		$this->api        = $api;
		$this->plugin_url = untrailingslashit( plugin_dir_url( $plugin_file_path ) );
		$this->plugin_dir = dirname( $plugin_file_path );
	}

	/**
	 * Initialize the plugin.
	 *
	 * Hooks are added and main modules are initialized.
	 */
	public function init(): void {
		$this->register_hooks();

		// Initialize core functionality.
		( new Admin( $this ) )->init();
		( new Front( $this ) )->init();

		// Initialize REST API functionality.
		( new REST( $this ) )->init();
	}

	/**
	 * Register WordPress-specific hooks.
	 */
	protected function register_hooks(): void {
		$this->load_plugin_textdomain();

		// Register custom post type for weather stations.
		add_action( 'init', array( $this, 'register_weather_station_post_type' ) );
	}

	/**
	 * Load plugin text domain for localization.
	 */
	protected function load_plugin_textdomain(): void {
		$domain = 'webnorthcodechallenge';
		if ( isset( $GLOBALS['l10n'][ $domain ] ) ) {
			return;
		}

		load_plugin_textdomain(
			$domain,
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/../languages/'
		);
	}

	/**
	 * Register custom post type for weather stations.
	 */
	public function register_weather_station_post_type(): void {
		$labels = array(
			'name'               => __( 'Weather Stations', 'webnorthcodechallenge' ),
			'singular_name'      => __( 'Weather Station', 'webnorthcodechallenge' ),
			'menu_name'          => __( 'Weather Stations', 'webnorthcodechallenge' ),
			'name_admin_bar'     => __( 'Weather Station', 'webnorthcodechallenge' ),
			'add_new'            => __( 'Add New', 'webnorthcodechallenge' ),
			'add_new_item'       => __( 'Add New Weather Station', 'webnorthcodechallenge' ),
			'new_item'           => __( 'New Weather Station', 'webnorthcodechallenge' ),
			'edit_item'          => __( 'Edit Weather Station', 'webnorthcodechallenge' ),
			'view_item'          => __( 'View Weather Station', 'webnorthcodechallenge' ),
			'all_items'          => __( 'All Weather Stations', 'webnorthcodechallenge' ),
			'search_items'       => __( 'Search Weather Stations', 'webnorthcodechallenge' ),
			'not_found'          => __( 'No weather stations found.', 'webnorthcodechallenge' ),
			'not_found_in_trash' => __( 'No weather stations found in Trash.', 'webnorthcodechallenge' ),
		);

		$args = array(
			'labels'              => $labels,
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'query_var'           => true,
			'rewrite'             => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_in_nav_menus'   => false,
			'capability_type'     => 'post',
			'has_archive'         => false,
			'hierarchical'        => false,
			'menu_position'       => 5,
			'supports'            => array( 'title', 'custom-fields' ),
		);

		register_post_type( 'weather_station', $args );
	}

	/**
	 * Get the plugin's base URL.
	 *
	 * @return string
	 */
	public function plugin_url(): string {
		return $this->plugin_url;
	}

	/**
	 * Get the plugin's base directory path.
	 *
	 * @return string
	 */
	public function plugin_dir(): string {
		return $this->plugin_dir;
	}

	/**
	 * Get the full path to a file inside the plugin directory.
	 *
	 * @param string $path Optional subpath.
	 * @return string
	 */
	public function get_path( string $path = '' ): string {
		return $this->plugin_dir . '/' . ltrim( $path, '/' );
	}

	/**
	 * Get the injected API instance.
	 *
	 * @return IAPI
	 */
	public function get_api(): IAPI {
		return $this->api;
	}
}
