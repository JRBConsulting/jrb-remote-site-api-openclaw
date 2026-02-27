<?php
/**
 * Plugin Name: JRB Remote Site API
 * Description: WordPress REST API for JRB Consulting remote site management
 * Version: 6.4.0
 * Author: JRB Consulting
 * License: GPLv2 or later
 * Text Domain: jrb-remote-api
 */

if (!defined('ABSPATH')) exit;

/**
 * Modern Plugin Launcher
 * This file replaces the legacy monolithic structure.
 */

// PSR-4 Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'JRB\\RemoteApi\\';
    $base_dir = __DIR__ . '/src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

// Initialize the Plugin
add_action('plugins_loaded', ['JRB\RemoteApi\Core\Plugin', 'init']);

/**
 * CLEANUP NOTICE:
 * All legacy functions are being decommissioned.
 * If external integrations rely on 'jrb_remote_' prefixed functions,
 * please update them to use the REST API endpoints under 'jrb-remote/v1'.
 */
