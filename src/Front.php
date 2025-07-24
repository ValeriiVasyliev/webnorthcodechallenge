<?php
/**
 * Front class file.
 *
 * @package webnorthcodechallenge
 */

namespace WebNorthCodeChallenge;

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
	}

	/**
	 * Enqueue front-end styles and scripts.
	 */
	public function enqueue_assets(): void {
	}
}
