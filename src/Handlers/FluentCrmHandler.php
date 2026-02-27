<?php
namespace JRB\RemoteApi\Handlers;

if (!defined('ABSPATH')) exit;

/**
 * Handles FluentCRM REST Routes
 */
class FluentCrmHandler {
    
    public static function register_routes() {
        $namespace = \JRB\RemoteApi\Core\Plugin::API_NAMESPACE;

        // Subscribers
        register_rest_route($namespace, '/crm/subscribers', [
            'methods' => 'GET',
            'callback' => [self::class, 'list_subscribers'],
            'permission_callback' => function() { return \JRB\RemoteApi\Auth\Guard::check('crm_subscribers_read'); }
        ]);

        register_rest_route($namespace, '/crm/subscribers/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [self::class, 'get_subscriber'],
            'permission_callback' => function() { return \JRB\RemoteApi\Auth\Guard::check('crm_subscribers_read'); }
        ]);

        // Lists
        register_rest_route($namespace, '/crm/lists', [
            'methods' => 'GET',
            'callback' => [self::class, 'list_lists'],
            'permission_callback' => function() { return \JRB\RemoteApi\Auth\Guard::check('crm_lists_read'); }
        ]);
    }

    public static function list_subscribers($request) {
        global $wpdb;
        $page = absint($request->get_param('page')) ?: 1;
        $per_page = min(absint($request->get_param('per_page')) ?: 20, 100);
        
        $table = $wpdb->prefix . 'fc_subscribers';
        $total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM %i", $table));
        $offset = ($page - 1) * $per_page;
        
        $subscribers = $wpdb->get_results(
            $wpdb->prepare("SELECT id, email, first_name, last_name, status, created_at FROM %i ORDER BY created_at DESC LIMIT %d OFFSET %d", $table, $per_page, $offset)
        );

        return new \WP_REST_Response([
            'subscribers' => $subscribers,
            'total' => (int)$total,
            'page' => $page,
            'per_page' => $per_page
        ], 200);
    }

    public static function get_subscriber($request) {
        global $wpdb;
        $id = absint($request['id']);
        $table = $wpdb->prefix . 'fc_subscribers';
        $subscriber = $wpdb->get_row($wpdb->prepare("SELECT id, email, first_name, last_name, status, created_at FROM %i WHERE id = %d", $table, $id));

        if (!$subscriber) {
            return new \WP_Error('crm_not_found', 'Subscriber not found', ['status' => 404]);
        }

        return new \WP_REST_Response($subscriber, 200);
    }

    public static function list_lists() {
        global $wpdb;
        $table = $wpdb->prefix . 'fc_lists';
        $lists = $wpdb->get_results($wpdb->prepare("SELECT id, title, slug FROM %i ORDER BY title ASC", $table));
        return new \WP_REST_Response($lists, 200);
    }
}
