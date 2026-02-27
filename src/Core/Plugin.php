<?php
namespace JRB\RemoteApi\Core;

if (!defined('ABSPATH')) exit;

class Plugin {
    const VERSION = '6.4.0';
    const API_NAMESPACE = 'openclaw/v1'; // Preserving existing namespace for compatibility
    const TEXT_DOMAIN = 'jrb-remote-site-api-for-openclaw';
    const GITHUB_REPO = 'JRBConsulting/jrb-remote-site-api-openclaw';

    public static function init() {
        self::load_updater();
        
        // Initialize Core Components
        if (class_exists('\JRB\RemoteApi\Auth\Guard')) {
            \JRB\RemoteApi\Auth\Guard::init();
        }

        // Initialize Admin UI
        if (is_admin()) {
            \JRB\RemoteApi\Handlers\AdminHandler::init();
        }
        
        // Initialize REST Routes
        add_action('rest_api_init', [self::class, 'register_routes']);
    }

    private static function load_updater() {
        // Restore the GitHub Updater Logic
        add_filter('update_plugins_github.com', function($update, $plugin_data, $plugin_file) {
            $slug = 'jrb-remote-site-api-for-openclaw/jrb-remote-site-api-for-openclaw.php';
            if ($plugin_file !== $slug) return $update;
            
            $response = wp_remote_get("https://api.github.com/repos/" . self::GITHUB_REPO . "/releases/latest", [
                'headers' => ['User-Agent' => 'WordPress JRB Remote API Plugin'],
                'timeout' => 10,
            ]);
            
            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) return $update;
            
            $release = json_decode(wp_remote_retrieve_body($response), true);
            if (empty($release['tag_name']) || empty($release['assets'])) return $update;
            
            $new_version = ltrim($release['tag_name'], 'v');
            if (version_compare($new_version, self::VERSION, '<=')) return $update;
            
            $download_url = null;
            foreach ($release['assets'] as $asset) {
                if (strpos($asset['name'], '.zip') !== false) {
                    $download_url = $asset['browser_download_url'];
                    break;
                }
            }
            if (!$download_url) return $update;
            
            return [
                'slug' => 'jrb-remote-site-api-for-openclaw',
                'version' => $new_version,
                'url' => $release['html_url'],
                'package' => $download_url,
            ];
        }, 10, 3);
    }

    public static function register_routes() {
        // Register Ping (no auth)
        register_rest_route(self::API_NAMESPACE, '/ping', [
            'methods' => 'GET',
            'callback' => function() { return ['status' => 'ok', 'time' => current_time('mysql')]; },
            'permission_callback' => '__return_true',
        ]);

        // Delegate to Handlers
        \JRB\RemoteApi\Handlers\SystemHandler::register_routes();
        \JRB\RemoteApi\Handlers\MediaHandler::register_routes();
        \JRB\RemoteApi\Handlers\FluentCrmHandler::register_routes();
        \JRB\RemoteApi\Handlers\FluentSupportHandler::register_routes();
        \JRB\RemoteApi\Handlers\FluentProjectHandler::register_routes();
    }
}
