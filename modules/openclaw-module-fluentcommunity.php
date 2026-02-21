<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * FluentCommunity module for JRB Remote Site API.
 */
class JRB_Remote_FluentCommunity_Module {

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes() {
		register_rest_route( 'jrb-remote/v1', '/fluentcommunity/members', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'list_members' ),
				'permission_callback' => array( 'JRB_Remote_Module_Loader', 'verify_token' ),
			),
		) );
	}

	public static function list_members( $request ) {
		global $wpdb;

		$per_page = (int) ( $request->get_param( 'per_page' ) ?: 20 );
		$page     = (int) ( $request->get_param( 'page' ) ?: 1 );
		$offset   = ( $page - 1 ) * $per_page;

		$table = $wpdb->prefix . 'fcom_members';

		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) !== $table ) {
			return new WP_REST_Response( array( 'error' => 'FluentCommunity table not found' ), 404 );
		}

		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );

		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT id, user_id, name, created_at FROM {$table} ORDER BY created_at DESC LIMIT %d OFFSET %d",
			$per_page,
			$offset
		) );

		return new WP_REST_Response( array(
			'data' => $results,
			'meta' => array(
				'total'    => $total,
				'page'     => $page,
				'per_page' => $per_page,
			),
		), 200 );
	}
}
