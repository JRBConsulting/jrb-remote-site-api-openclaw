<?php
/**
 * Smoke Test for JRB Remote Site API
 * Verifies that the Core and Auth systems are loading correctly.
 */

require_once 'src/Core/Plugin.php';
require_once 'src/Auth/Guard.php';

use JRB\RemoteApi\Core\Plugin;
use JRB\RemoteApi\Auth\Guard;

echo "--- JRB Remote Site API: Core Smoke Test ---\n";

// Test 1: Constant definition
if (defined('ABSPATH')) {
    echo "FAIL: ABSPATH should not be defined in CLI smoke test without WP mock.\n";
} else {
    define('ABSPATH', '/tmp/');
}

// Test 2: Namespace Visibility
if (class_exists('JRB\RemoteApi\Core\Plugin')) {
    echo "PASS: Plugin class is visible.\n";
} else {
    echo "FAIL: Plugin class not found.\n";
}

// Test 3: Version Check
if (Plugin::VERSION === '6.3.2' || Plugin::VERSION === '6.4.0') {
    echo "PASS: Plugin version identified (" . Plugin::VERSION . ").\n";
} else {
    echo "FAIL: Unexpected version: " . Plugin::VERSION . "\n";
}

echo "--- Smoke Test Complete ---\n";
