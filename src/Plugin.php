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
