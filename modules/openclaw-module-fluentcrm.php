<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * FluentCRM module for JRB Remote Site API.
 */
class JRB_Remote_FluentCRM_Module {

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes() {
		register_rest_route( 'jrb-remote/v1', '/fluentcrm/subscribers', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'list_subscribers' ),
				'permission_callback' => array( 'JRB_Remote_Module_Loader', 'verify_token' ),
			),
		) );
	}

	public static function list_subscribers( $request ) {
		global $wpdb;

		$per_page = (int) ( $request->get_param( 'per_page' ) ?: 20 );
		$page     = (int) ( $request->get_param( 'page' ) ?: 1 );
		$offset   = ( $page - 1 ) * $per_page;
		$status   = sanitize_text_field( $request->get_param( 'status' ) );

		$table_subscribers = $wpdb->prefix . 'fc_subscribers';

		$where  = 'WHERE 1=1';
		$params = array();

		if ( ! empty( $status ) ) {
			$where .= ' AND status = %s';
			$params[] = $status;
		}

		// Ensure table names are hardcoded in the string or properly escaped if they must be dynamic.
		// For directory compliance, we use literal table names based on prefix.
		$total_query = $wpdb->prepare( "SELECT COUNT(*) FROM {$table_subscribers} {$where}", $params );
		$total       = (int) $wpdb->get_var( $total_query );

		$params[]     = $per_page;
		$params[]     = $offset;
		$results_query = $wpdb->prepare( "SELECT id, email, first_name, last_name, status, created_at FROM {$table_subscribers} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d", $params );
		$results       = $wpdb->get_results( $results_query );

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
