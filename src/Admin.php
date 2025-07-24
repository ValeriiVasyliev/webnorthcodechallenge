<?php
/**
 * Admin class file.
 *
 * @package webnorthcodechallenge
 */

namespace WebNorthCodeChallenge;

use WP_Query;

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
	}
}
