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

		add_filter( 'display_post_states', array( $this, 'add_map_page_state_label' ), 10, 2 );
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
}
