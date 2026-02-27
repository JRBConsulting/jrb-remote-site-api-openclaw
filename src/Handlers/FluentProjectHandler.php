<?php
namespace JRB\RemoteApi\Handlers;

if (!defined('ABSPATH')) exit;

/**
 * Handles FluentProject REST Routes
 */
class FluentProjectHandler {
    
    public static function register_routes() {
        $namespace = \JRB\RemoteApi\Core\Plugin::API_NAMESPACE;

        // Projects
        register_rest_route($namespace, '/project/projects', [
            [
                'methods'             => 'GET',
                'callback'            => [self::class, 'list_projects'],
                'permission_callback' => function() { return \JRB\RemoteApi\Auth\Guard::check('project_tasks_read'); }
            ],
            [
                'methods'             => 'POST',
                'callback'            => [self::class, 'create_project'],
                'permission_callback' => function() { return \JRB\RemoteApi\Auth\Guard::check('project_tasks_create'); }
            ]
        ]);

        register_rest_route($namespace, '/project/projects/(?P<id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'get_project'],
            'permission_callback' => function() { return \JRB\RemoteApi\Auth\Guard::check('project_tasks_read'); }
        ]);

        // Tasks
        register_rest_route($namespace, '/project/tasks', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'list_tasks'],
            'permission_callback' => function() { return \JRB\RemoteApi\Auth\Guard::check('project_tasks_read'); }
        ]);

        // Stats
        register_rest_route($namespace, '/project/stats', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'get_stats'],
            'permission_callback' => function() { return \JRB\RemoteApi\Auth\Guard::check('project_tasks_read'); }
        ]);
    }

    public static function list_projects($request) {
        $args = [
            'post_type'      => 'fproject',
            'posts_per_page' => 50,
            'post_status'    => 'publish'
        ];

        if ($request->get_param('status')) {
            $args['meta_query'] = [[
                'key'   => '_fproject_status',
                'value' => sanitize_text_field($request->get_param('status'))
            ]];
        }

        $projects = get_posts($args);
        $data = array_map([self::class, 'format_project'], $projects);

        return new \WP_REST_Response($data, 200);
    }

    public static function get_project($request) {
        $id = (int)$request['id'];
        $post = get_post($id);

        if (!$post || $post->post_type !== 'fproject') {
            return new \WP_Error('project_not_found', 'Project not found', ['status' => 404]);
        }

        return new \WP_REST_Response(self::format_project($post), 200);
    }

    public static function list_tasks($request) {
        $args = [
            'post_type'      => 'ftask',
            'posts_per_page' => 50,
            'post_status'    => 'any'
        ];

        $tasks = get_posts($args);
        return new \WP_REST_Response($tasks, 200);
    }

    public static function get_stats() {
        $counts = wp_count_posts('ftask');
        return new \WP_REST_Response($counts, 200);
    }

    private static function format_project($post) {
        return [
            'id'          => $post->ID,
            'title'       => $post->post_title,
            'status'      => get_post_meta($post->ID, '_fproject_status', true) ?: 'active',
            'progress'    => (int)get_post_meta($post->ID, '_fproject_progress', true),
            'due_date'    => get_post_meta($post->ID, '_fproject_due', true),
        ];
    }
}
