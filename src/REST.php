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
	}
}
