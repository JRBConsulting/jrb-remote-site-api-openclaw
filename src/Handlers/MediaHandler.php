<?php
namespace JRB\RemoteApi\Handlers;

if (!defined('ABSPATH')) exit;

/**
 * Handles WordPress Media Library REST Routes
 */
class MediaHandler {
    
    public static function register_routes() {
        $namespace = \JRB\RemoteApi\Core\Plugin::API_NAMESPACE;

        // List and Upload
        register_rest_route($namespace, '/media', [
            [
                'methods'             => 'GET',
                'callback'            => [self::class, 'list_media'],
                'permission_callback' => function() { return \JRB\RemoteApi\Auth\Guard::check('media_read'); }
            ],
            [
                'methods'             => 'POST',
                'callback'            => [self::class, 'upload_media'],
                'permission_callback' => function() { return \JRB\RemoteApi\Auth\Guard::check('media_upload'); }
            ]
        ]);

        // Single item operations
        register_rest_route($namespace, '/media/(?P<id>\d+)', [
            [
                'methods'             => 'GET',
                'callback'            => [self::class, 'get_media_item'],
                'permission_callback' => function() { return \JRB\RemoteApi\Auth\Guard::check('media_read'); }
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [self::class, 'delete_media_item'],
                'permission_callback' => function() { return \JRB\RemoteApi\Auth\Guard::check('media_delete'); }
            ]
        ]);
    }

    /**
     * List Media Items
     */
    public static function list_media($request) {
        $args = [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => min(absint($request->get_param('per_page')) ?: 20, 100),
            'paged'          => absint($request->get_param('page')) ?: 1,
        ];

        if ($request->get_param('mime_type')) {
            $args['post_mime_type'] = sanitize_text_field($request->get_param('mime_type'));
        }

        $query = new \WP_Query($args);
        $media = [];

        foreach ($query->posts as $post) {
            $media[] = self::prepare_item_for_response($post);
        }

        return new \WP_REST_Response([
            'items' => $media,
            'total' => (int)$query->found_posts,
            'page'  => $args['paged'],
        ], 200);
    }

    /**
     * Upload Media
     */
    public static function upload_media($request) {
        if (!function_exists('wp_handle_upload')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        $file = $_FILES['file'] ?? null;
        if (!$file) {
            return new \WP_Error('no_file', 'No file uploaded', ['status' => 400]);
        }

        $upload = wp_handle_upload($file, ['test_form' => false]);
        if (isset($upload['error'])) {
            return new \WP_Error('upload_error', $upload['error'], ['status' => 500]);
        }

        $attachment = [
            'guid'           => $upload['url'],
            'post_mime_type' => $upload['type'],
            'post_title'     => preg_replace('/\.[^.]+$/', '', basename($upload['file'])),
            'post_content'   => '',
            'post_status'    => 'inherit'
        ];

        $attachment_id = wp_insert_attachment($attachment, $upload['file']);
        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }

        wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, $upload['file']));

        return new \WP_REST_Response(self::prepare_item_for_response(get_post($attachment_id)), 201);
    }

    /**
     * Delete Media
     */
    public static function delete_media_item($request) {
        $id = (int)$request['id'];
        $force = (bool)$request->get_param('force');

        $result = wp_delete_attachment($id, $force);
        if (!$result) {
            return new \WP_Error('delete_failed', 'Failed to delete media item', ['status' => 500]);
        }

        return new \WP_REST_Response(['deleted' => true, 'id' => $id], 200);
    }

    /**
     * Format attachment object for REST response
     */
    private static function prepare_item_for_response($post) {
        return [
            'id'        => $post->ID,
            'title'     => $post->post_title,
            'url'       => wp_get_attachment_url($post->ID),
            'mime_type' => $post->post_mime_type,
            'date'      => $post->post_date,
        ];
    }
}
