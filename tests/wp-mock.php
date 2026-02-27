<?php
/**
 * Mock WordPress environment for testing
 */
if (!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/');

function register_rest_route($ns, $route, $args) {}
function get_bloginfo($key) { return 'Mock Blog'; }
function add_action($hook, $callback) {}
function add_filter($hook, $callback) {}
function get_option($key) { return 'mock_token'; }

class WP_REST_Response {
    public $data;
    public $status;
    public function __construct($data, $status) {
        $this->data = $data;
        $this->status = $status;
    }
}

class WP_Error {
    public function __construct($code, $msg) {}
}

class WP_Query {
    public $posts = [];
    public $found_posts = 0;
    public function __construct($args) {}
}

function sanitize_text_field($val) { return $val; }
function wp_get_attachment_url($id) { return "http://example.com/file.jpg"; }
