<?php
namespace JRB\RemoteApi\Handlers;

if (!defined('ABSPATH')) exit;

/**
 * Handles FluentSupport REST Routes
 */
class FluentSupportHandler {
    
    public static function register_routes() {
        $namespace = \JRB\RemoteApi\Core\Plugin::API_NAMESPACE;

        // Tickets
        register_rest_route($namespace, '/support/tickets', [
            'methods' => 'GET',
            'callback' => [self::class, 'list_tickets'],
            'permission_callback' => function() { return \JRB\RemoteApi\Auth\Guard::check('support_tickets_read'); }
        ]);

        register_rest_route($namespace, '/support/tickets/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [self::class, 'get_ticket'],
            'permission_callback' => function() { return \JRB\RemoteApi\Auth\Guard::check('support_tickets_read'); }
        ]);

        // Customers
        register_rest_route($namespace, '/support/customers', [
            'methods' => 'GET',
            'callback' => [self::class, 'list_customers'],
            'permission_callback' => function() { return \JRB\RemoteApi\Auth\Guard::check('support_customers_read'); }
        ]);
        
        // Sync & Stats
        register_rest_route($namespace, '/support/stats', [
            'methods' => 'GET',
            'callback' => [self::class, 'get_stats'],
            'permission_callback' => function() { return \JRB\RemoteApi\Auth\Guard::check('support_tickets_read'); }
        ]);
    }

    public static function list_tickets($request) {
        global $wpdb;
        $page = absint($request->get_param('page')) ?: 1;
        $per_page = min(absint($request->get_param('per_page')) ?: 20, 100);
        
        $table = $wpdb->prefix . 'fs_tickets';
        $total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM %i", $table));
        $offset = ($page - 1) * $per_page;
        
        $tickets = $wpdb->get_results(
            $wpdb->prepare("SELECT id, title, status, priority, customer_id, created_at FROM %i ORDER BY created_at DESC LIMIT %d OFFSET %d", $table, $per_page, $offset)
        );

        return new \WP_REST_Response([
            'tickets' => $tickets,
            'total'   => (int)$total,
            'page'    => $page,
            'per_page'=> $per_page
        ], 200);
    }

    public static function get_ticket($request) {
        global $wpdb;
        $id = absint($request['id']);
        $table = $wpdb->prefix . 'fs_tickets';
        $ticket = $wpdb->get_row($wpdb->prepare("SELECT id, title, content, status, priority, customer_id, created_at FROM %i WHERE id = %d", $table, $id));

        if (!$ticket) {
            return new \WP_Error('support_not_found', 'Ticket not found', ['status' => 404]);
        }

        return new \WP_REST_Response($ticket, 200);
    }

    public static function list_customers() {
        global $wpdb;
        $table = $wpdb->prefix . 'fs_customers';
        $customers = $wpdb->get_results($wpdb->prepare("SELECT id, first_name, last_name, email FROM %i ORDER BY first_name ASC LIMIT 50", $table));
        return new \WP_REST_Response($customers, 200);
    }

    public static function get_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'fs_tickets';
        $stats = $wpdb->get_results($wpdb->prepare("SELECT status, COUNT(*) as count FROM %i GROUP BY status", $table));
        return new \WP_REST_Response($stats, 200);
    }
}
