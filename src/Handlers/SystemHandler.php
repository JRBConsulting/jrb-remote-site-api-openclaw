<?php
namespace JRB\RemoteApi\Handlers;

if (!defined('ABSPATH')) exit;

/**
 * Handles Core System and Plugin routes
 */
class SystemHandler {
    public static function register_routes() {
        $namespace = \JRB\RemoteApi\Core\Plugin::API_NAMESPACE;

        // Basic site info
        register_rest_route($namespace, '/site', [
            'methods'  => 'GET',
            'callback' => [self::class, 'get_site_info'],
            'permission_callback' => [self::class, 'check_auth'],
        ]);

        // Plugin management
        register_rest_route($namespace, '/plugins', [
            'methods'  => 'GET',
            'callback' => [self::class, 'get_plugins'],
            'permission_callback' => [self::class, 'check_auth'],
        ]);
    }

    public static function check_auth() {
        return \JRB\RemoteApi\Auth\Guard::check();
    }

    public static function get_site_info() {
        return new \WP_REST_Response([
            'name'        => get_bloginfo('name'),
            'description' => get_bloginfo('description'),
            'url'         => get_bloginfo('url'),
            'version'     => get_bloginfo('version'),
        ], 200);
    }

    public static function get_plugins() {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        return new \WP_REST_Response(get_plugins(), 200);
    }
}
