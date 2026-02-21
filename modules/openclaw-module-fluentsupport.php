<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * FluentSupport module for JRB Remote Site API.
 */
class JRB_Remote_FluentSupport_Module {

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes() {
		register_rest_route( 'jrb-remote/v1', '/fluentsupport/tickets', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'list_tickets' ),
				'permission_callback' => array( 'JRB_Remote_Module_Loader', 'verify_token' ),
			),
		) );
	}

	public static function list_tickets( $request ) {
		global $wpdb;

		$per_page = (int) ( $request->get_param( 'per_page' ) ?: 20 );
		$page     = (int) ( $request->get_param( 'page' ) ?: 1 );
		$offset   = ( $page - 1 ) * $per_page;
		$status   = sanitize_text_field( $request->get_param( 'status' ) );

		$table_tickets = $wpdb->prefix . 'fs_tickets';

		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_tickets ) ) !== $table_tickets ) {
			return new WP_REST_Response( array( 'error' => 'FluentSupport table not found' ), 404 );
		}

		$where  = 'WHERE 1=1';
		$params = array();

		if ( ! empty( $status ) ) {
			$where .= ' AND status = %s';
			$params[] = $status;
		}

		$total = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table_tickets} {$where}",
			$params
		) );

		$params[]     = $per_page;
		$params[]     = $offset;
		$results_query = $wpdb->prepare( "SELECT * FROM {$table_tickets} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d", $params );
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
