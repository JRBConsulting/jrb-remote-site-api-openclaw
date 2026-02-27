<?php
namespace JRB\RemoteApi\Auth;

if (!defined('ABSPATH')) exit;

class Guard {
    
    public static function init() {
        // No specific init needed usually, but held for parity
    }

    public static function verify_token() {
        $header = isset($_SERVER['HTTP_X_OPENCLAW_TOKEN']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_X_OPENCLAW_TOKEN'])) : '';
        
        if (empty($header)) {
            return new \WP_Error('missing_header', 'Missing X-OpenClaw-Token header', ['status' => 401]);
        }
        
        // Hashed check (Preferred)
        $token_hash = get_option('openclaw_api_token_hash');
        if (!empty($token_hash)) {
            if (hash_equals($token_hash, wp_hash($header))) return true;
        }
        
        // Legacy fallback
        $legacy_token = get_option('openclaw_api_token');
        if (!empty($legacy_token)) {
            if (hash_equals($legacy_token, $header)) {
                update_option('openclaw_api_token_hash', wp_hash($legacy_token));
                delete_option('openclaw_api_token');
                return true;
            }
        }
        
        return new \WP_Error('invalid_token', 'Invalid API token', ['status' => 401]);
    }

    public static function can($capability) {
        $caps = get_option('openclaw_api_capabilities', []);
        
        // Internal default for core fallback if not set
        $core_defaults = [
            'site_info' => true,
            'posts_read' => true,
            'media_read' => true,
        ];

        if (isset($caps[$capability])) {
            return (bool)$caps[$capability];
        }

        return $core_defaults[$capability] ?? false;
    }

    public static function verify_token_and_can($capability) {
        $token_check = self::verify_token();
        if (is_wp_error($token_check)) return $token_check;
        
        if (!self::can($capability)) {
            return new \WP_Error('capability_denied', "API capability '$capability' is disabled", ['status' => 403]);
        }
        return true;
    }
}
