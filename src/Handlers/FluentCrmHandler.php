<?php
namespace JRB\RemoteApi\Handlers;

if (!defined('ABSPATH')) exit;

use JRB\RemoteApi\Auth\Guard;

class FluentCrmHandler {
    
    public static function register_routes() {
        $ns = \JRB\RemoteApi\Core\Plugin::API_NAMESPACE;

        register_rest_route($ns, '/crm/subscribers', [
            'methods' => 'GET',
            'callback' => [self::class, 'list_subscribers'],
            'permission_callback' => function() { return Guard::verify_token_and_can('crm_subscribers_read'); }
        ]);

        register_rest_route($ns, '/crm/lists', [
            'methods' => 'GET',
            'callback' => [self::class, 'list_lists'],
            'permission_callback' => function() { return Guard::verify_token_and_can('crm_lists_read'); }
        ]);
    }

    public static function list_subscribers($request) {
        global $wpdb;
        if (!self::is_active()) return new \WP_REST_Response(['error' => 'FluentCRM not active'], 404);

        $page = max(1, (int)$request->get_param('page'));
        $per_page = min(100, max(1, (int)$request->get_param('per_page') ?: 20));
        $offset = ($page - 1) * $per_page;
        
        $table = $wpdb->prefix . 'fc_subscribers';
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT id, email, first_name, last_name, status, created_at FROM %i ORDER BY id DESC LIMIT %d OFFSET %d",
            $table, $per_page, $offset
        ));

        return new \WP_REST_Response($results, 200);
    }

    public static function list_lists() {
        global $wpdb;
        if (!self::is_active()) return new \WP_REST_Response(['error' => 'FluentCRM not active'], 404);

        $table = $wpdb->prefix . 'fc_lists';
        $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM %i", $table));
        return new \WP_REST_Response($results, 200);
    }

    private static function is_active() {
        return defined('FLUENTCRM') || class_exists('\FluentCrm\App\App');
    }
}
