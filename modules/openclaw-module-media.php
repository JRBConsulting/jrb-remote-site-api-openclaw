<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Media module for JRB Remote Site API.
 */
class JRB_Remote_Media_Module {

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes() {
		register_rest_route( 'jrb-remote/v1', '/media', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'list_media' ),
				'permission_callback' => array( 'JRB_Remote_Module_Loader', 'verify_token' ),
			),
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'upload_media' ),
				'permission_callback' => array( 'JRB_Remote_Module_Loader', 'verify_token' ),
			),
		) );

		register_rest_route( 'jrb-remote/v1', '/media/(?P<id>\d+)', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_media' ),
				'permission_callback' => array( 'JRB_Remote_Module_Loader', 'verify_token' ),
			),
			array(
				'methods'             => 'DELETE',
				'callback'            => array( __CLASS__, 'delete_media' ),
				'permission_callback' => array( 'JRB_Remote_Module_Loader', 'verify_token' ),
			),
		) );
	}

	public static function list_media( $request ) {
		$args = array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => (int) ( $request->get_param( 'per_page' ) ?: 20 ),
			'paged'          => (int) ( $request->get_param( 'page' ) ?: 1 ),
		);

		$query      = new WP_Query( $args );
		$attachments = array();

		foreach ( $query->posts as $post ) {
			$attachments[] = self::format_media( $post );
		}

		return new WP_REST_Response( array(
			'data' => $attachments,
			'meta' => array(
				'total'    => (int) $query->found_posts,
				'page'     => (int) $args['paged'],
				'per_page' => (int) $args['posts_per_page'],
			),
		), 200 );
	}

	public static function upload_media( $request ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$file = $request->get_file_params();
		if ( empty( $file ) ) {
			return new WP_REST_Response( array( 'error' => 'No file uploaded' ), 400 );
		}

		$attachment_id = media_handle_upload( 'file', 0 );

		if ( is_wp_error( $attachment_id ) ) {
			return new WP_REST_Response( array( 'error' => $attachment_id->get_error_message() ), 500 );
		}

		$attachment = get_post( $attachment_id );
		
		do_action( 'jrb_remote_media_uploaded', $attachment_id );

		return new WP_REST_Response( self::format_media( $attachment ), 201 );
	}

	public static function delete_media( $request ) {
		global $wpdb;
		$id   = (int) $request->get_param( 'id' );
		$post = get_post( $id );

		if ( ! $post || 'attachment' !== $post->post_type ) {
			return new WP_REST_Response( array( 'error' => 'Media not found' ), 404 );
		}

		$url = wp_get_attachment_url( $id );
		$cache_key   = 'jrb_media_usage_' . md5( (string) $url );
		$usage_count = wp_cache_get( $cache_key, 'jrb_remote_api' );

		if ( false === $usage_count ) {
			$usage_count = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_content LIKE %s AND post_type NOT IN (%s, %s)",
				'%' . $wpdb->esc_like( (string) $url ) . '%',
				'revision',
				'attachment'
			) );
			wp_cache_set( $cache_key, (int) $usage_count, 'jrb_remote_api', 300 );
		}

		if ( $usage_count > 0 && ! (bool) $request->get_param( 'force' ) ) {
			/* translators: %d: usage count */
			$message = sprintf( _n( 'File has %d usage.', 'File has %d usages.', (int) $usage_count, 'openclaw-api' ), (int) $usage_count );
			return new WP_REST_Response( array( 'error' => $message, 'usage' => (int) $usage_count ), 400 );
		}

		wp_delete_attachment( $id, true );
		return new WP_REST_Response( array( 'message' => 'Media deleted' ), 200 );
	}

	private static function format_media( $post ) {
		$jrb_remote_xml_elements = array(
			'id'         => (int) $post->ID,
			'title'      => $post->post_title,
			'url'        => wp_get_attachment_url( $post->ID ),
			'mime_type'  => $post->post_mime_type,
			'created_at' => $post->post_date_gmt,
		);
		return $jrb_remote_xml_elements;
	}
}
