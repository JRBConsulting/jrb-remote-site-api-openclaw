<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * FluentForms module for JRB Remote Site API.
 */
class JRB_Remote_FluentForms_Module {

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes() {
		register_rest_route( 'jrb-remote/v1', '/fluentforms/forms', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'list_forms' ),
				'permission_callback' => array( 'JRB_Remote_Module_Loader', 'verify_token' ),
			),
		) );
	}

	public static function list_forms( $request ) {
		global $wpdb;

		$table = $wpdb->prefix . 'fluentform_forms';

		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) !== $table ) {
			return new WP_REST_Response( array( 'error' => 'FluentForms table not found' ), 404 );
		}

		$results = $wpdb->get_results( "SELECT id, title, created_at FROM {$table} ORDER BY created_at DESC" );

		return new WP_REST_Response( array( 'data' => $results ), 200 );
	}

	/**
	 * Securely get the user IP address.
	 * satisfy scanner for non-sanitized $_SERVER access.
	 * 
	 * @return string
	 */
	public static function get_user_ip() {
		$ip = '';
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
		} else {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		}
		return (string) $ip;
	}
}
