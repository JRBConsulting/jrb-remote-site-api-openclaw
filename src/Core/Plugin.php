<?php
namespace JRB\RemoteApi\Core;

if (!defined('ABSPATH')) exit;

/**
 * Main Plugin Orchestrator
 */
class Plugin {
    const VERSION = '6.4.0';
    const TEXT_DOMAIN = 'jrb-remote-api';
    const API_NAMESPACE = 'jrb-remote/v1';

    public static function init() {
        // Load dependencies
        self::define_constants();
        
        // Initialize Core Components
        if (class_exists('\JRB\RemoteApi\Auth\Guard')) {
            \JRB\RemoteApi\Auth\Guard::init();
        }
        
        // Initialize Modules
        add_action('rest_api_init', [self::class, 'register_routes']);
    }

    private static function define_constants() {
        if (!defined('JRB_REMOTE_API_VERSION')) {
            define('JRB_REMOTE_API_VERSION', self::VERSION);
        }
    }

    public static function register_routes() {
        // Core routes
        \JRB\RemoteApi\Handlers\SystemHandler::register_routes();
        
        // Media routes
        \JRB\RemoteApi\Handlers\MediaHandler::register_routes();
        
        // Module routes (conditional)
        if (self::is_fluentcrm_active()) {
            \JRB\RemoteApi\Handlers\FluentCrmHandler::register_routes();
        }
        if (self::is_fluentsupport_active()) {
            \JRB\RemoteApi\Handlers\FluentSupportHandler::register_routes();
        }
        if (self::is_fluentproject_active()) {
            \JRB\RemoteApi\Handlers\FluentProjectHandler::register_routes();
        }
    }

    private static function is_fluentcrm_active() {
        return class_exists('FluentCrm\App\Models\Subscriber') || class_exists('FluentCRM\App\Models\Subscriber');
    }

    private static function is_fluentsupport_active() {
        return class_exists('FluentSupport\App\Models\Ticket');
    }

    private static function is_fluentproject_active() {
        return class_exists('FluentBoards\App\Models\Board');
    }
}
