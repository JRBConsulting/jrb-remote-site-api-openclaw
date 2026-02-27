<?php
if (!defined('ABSPATH')) define('ABSPATH', __DIR__);
$GLOBALS['wp_options'] = [];
function get_option($k, $d = false) { return $GLOBALS['wp_options'][$k] ?? $d; }
function update_option($k, $v) { $GLOBALS['wp_options'][$k] = $v; }
function delete_option($k) { unset($GLOBALS['wp_options'][$k]); }
function add_action($h, $c, $p=10, $a=1) {}
function add_filter($h, $c, $p=10, $a=1) {}
function apply_filters($h, $v) { return $v; }
function sanitize_text_field($v) { return $v; }
function wp_unslash($v) { return $v; }
function wp_hash($v) { return md5($v); }
if (!function_exists('hash_equals')) {
    function hash_equals($a, $b) { return $a === $b; }
}
function sanitize_key($k) { return $k; }
function wp_parse_args($a, $b) { return array_merge($b, $a); }
function register_rest_route($n, $r, $a) {}
