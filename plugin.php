<?php
/**
 *  WebNorth Code Challenge
 *
 * @package           webnorthcodechallenge
 * @author            Valerii Vasyliev
 * @license           GPL-2.0-or-later
 * @wordpress-plugin
 *
 * Plugin Name:       WebNorth Code Challenge
 * Description:       WebNorth Code Challenge
 * Version:           1.0.1
 * Requires at least: 6.0
 * Tested up to:      6.3
 * Requires PHP:      8.0
 * Author:            Valerii Vasyliev
 * Author URI:        https://www.codeable.io/developers/valerii-vasyliev/?ref=OaT0y
 * License:           GPL-2.0-or-later
 * Text Domain:       webnorthcodechallenge
 */

namespace WebNorthCodeChallenge;

// Load the autoloader.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

/**
 * Activation hook.
 *
 * @return void
 */
function web_north_code_challenge_activate() {
}
register_activation_hook( __FILE__, 'WebNorthCodeChallenge\web_north_code_challenge_activate' );

/**
 * Deactivation hook.
 *
 * @return void
 */
function web_north_code_challenge_deactivate() {
	// Flush rewrite rules on deactivation.
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'WebNorthCodeChallenge\web_north_code_challenge_deactivate' );

// Get plugin path.
define( 'WEBNORTH_CODE_CHALLENGE_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

// Plugin file.
define( 'WEBNORTH_CODE_CHALLENGE_PLUGIN_FILE', plugin_basename( __FILE__ ) );

// Initialize the plugin.
add_action( 'plugins_loaded', array( new Plugin( new API\OpenWeatherAPI(), __FILE__ ), 'init' ) );
