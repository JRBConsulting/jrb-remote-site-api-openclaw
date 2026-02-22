<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Posts module for JRB Remote Site API (v5.1.0-alpha).
 * Enables draft creation for OpenClaw content.
 */
class JRB_Remote_Posts_Module {

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes() {
		register_rest_route( 'jrb-remote/v1', '/posts', array(
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'create_post' ),
				'permission_callback' => array( 'JRB_Remote_Module_Loader', 'verify_token' ),
			),
		) );
	}

	public static function create_post( $request ) {
		$title   = sanitize_text_field( $request->get_param( 'title' ) );
		$content = $request->get_param( 'content' ); // Handled as Gutenberg-ready HTML
		$excerpt = sanitize_textarea_field( $request->get_param( 'excerpt' ) );
		$status  = sanitize_text_field( $request->get_param( 'status' ) ?: 'draft' );

		// Granular Permission Check: Verify 'publish' permission if trying to go live.
		if ( 'publish' === $status ) {
			$can_publish = get_option( 'jrb_remote_api_allow_publish', false );
			if ( ! $can_publish ) {
				return new WP_REST_Response( array(
					'error'   => 'Live publishing is disabled in plugin settings.',
					'hint'    => 'Update "jrb_remote_api_allow_publish" option to true, or use status="draft".'
				), 403 );
			}
		} else {
			// If not publish, force to draft for safety unless it's a known non-live status.
			if ( ! in_array( $status, array( 'draft', 'pending', 'private' ), true ) ) {
				$status = 'draft';
			}
		}

		if ( empty( $title ) ) {
			return new WP_REST_Response( array( 'error' => 'Title is required' ), 400 );
		}

		$post_id = wp_insert_post( array(
			'post_title'   => $title,
			'post_content' => $content,
			'post_excerpt' => $excerpt,
			'post_status'  => $status,
			'post_type'    => 'post',
		) );

		if ( is_wp_error( $post_id ) ) {
			return new WP_REST_Response( array( 'error' => $post_id->get_error_message() ), 500 );
		}

		return new WP_REST_Response( array(
			'id'      => $post_id,
			'status'  => 'success',
			'link'    => get_edit_post_link( $post_id, '' ),
			'message' => 'Draft created successfully'
		), 201 );
	}
}
