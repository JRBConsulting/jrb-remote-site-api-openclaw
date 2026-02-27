<?php
/**
 * Plugin Name: JRB Remote Site API for OpenClaw
 * Description: WordPress REST API for OpenClaw remote site management (Refactored v6.4.0)
 * Version: 6.4.0
 * Author: JRB Consulting
 * License: GPLv2 or later
 * Text Domain: jrb-remote-site-api-for-openclaw
 */

if (!defined('ABSPATH')) exit;

/**
 * Autoloader for JRB\RemoteApi namespace
 */
spl_autoload_register(function ($class) {
    if (strpos($class, 'JRB\\RemoteApi\\') !== 0) return;
    
    $relative_class = substr($class, 14);
    $file = __DIR__ . '/src/' . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Initialize plugin
add_action('plugins_loaded', function() {
    \JRB\RemoteApi\Core\Plugin::init();
});
