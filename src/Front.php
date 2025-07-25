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

		// Custom template for 'map' page.
		add_filter( 'template_include', array( $this, 'maybe_load_map_template' ) );
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
}
