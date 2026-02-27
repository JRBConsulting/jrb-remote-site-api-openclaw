<?php
/**
 * jrb_remote API Uninstall
 *
 * Clean up plugin options on uninstall.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('jrb_remote_api_token');
delete_option('jrb_remote_api_token_hash');
delete_option('jrb_remote_api_capabilities');
delete_transient('jrb_remote_new_token');

// Also clean up any old options from previous versions
delete_option('lilith_api_token');
delete_option('lilith_api_capabilities');