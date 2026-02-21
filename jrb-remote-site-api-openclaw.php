<?php
/**
 * Plugin Name:       JRB Remote Site API for OpenClaw
 * Description:       WordPress REST API for OpenClaw remote site management
 * Version:           5.0.0
 * Author:            JRB Consulting
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Tested up to:      6.9
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Text Domain:       openclaw-api
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Global prefix for the plugin version.
if ( ! defined( 'JRB_REMOTE_SITE_API_VERSION' ) ) {
	define( 'JRB_REMOTE_SITE_API_VERSION', '5.0.0' );
}

/**
 * Register REST API routes.
 */
add_action( 'rest_api_init', 'jrb_remote_site_api_init_routes' );

/**
 * Initialize core routes.
 */
function jrb_remote_site_api_init_routes() {
	register_rest_route( 'jrb-remote/v1', '/ping', array(
		'methods'             => 'GET',
		'callback'            => function() {
			return array( 'status' => 'success', 'message' => 'pong' );
		},
		'permission_callback' => '__return_true',
	) );
}

// Load Modules securely.
$jrb_remote_modules_loader_file = plugin_dir_path( __FILE__ ) . 'modules/openclaw-modules.php';
if ( file_exists( $jrb_remote_modules_loader_file ) ) {
	include_once $jrb_remote_modules_loader_file;
}
