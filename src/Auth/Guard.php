<?php
namespace JRB\RemoteApi\Auth;

if (!defined('ABSPATH')) exit;

/**
 * Handles API Authentication and Permissions
 */
class Guard {
    public static function init() {
        // Any early-hook auth logic
    }

    /**
     * Basic check used by Handlers
     */
    public static function check($capability = '') {
        $token = self::get_token_from_request();
        if (empty($token)) {
            return false;
        }

        $valid_token = get_option('jrb_remote_api_token');
        if (empty($valid_token) || $token !== $valid_token) {
            return false;
        }

        // Check for specific capability if provided
        if (!empty($capability)) {
            return self::can($capability);
        }

        return true;
    }

    public static function can($capability) {
        $token_permissions = get_option('jrb_remote_api_permissions', []);
        
        // SECURITY FIX: Default to false if no permissions are defined.
        if (empty($token_permissions)) {
            return false; 
        }

        return in_array($capability, $token_permissions, true);
    }

    private static function get_token_from_request() {
        // SECURITY FIX: Only accept tokens via HTTP Headers to avoid URL logging.
        return $_SERVER['HTTP_X_JRB_TOKEN'] ?? $_SERVER['HTTP_X_jrb_remote_TOKEN'] ?? '';
    }
}
