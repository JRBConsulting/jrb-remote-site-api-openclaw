<?php
/**
 * Test Suite: JRB Remote Site API Core
 */

require_once __DIR__ . '/wp-mock.php';
require_once __DIR__ . '/../src/Core/Plugin.php';
require_once __DIR__ . '/../src/Auth/Guard.php';
require_once __DIR__ . '/../src/Handlers/SystemHandler.php';
require_once __DIR__ . '/../src/Handlers/MediaHandler.php';

use JRB\RemoteApi\Core\Plugin;
use JRB\RemoteApi\Auth\Guard;
use JRB\RemoteApi\Handlers\SystemHandler;
use JRB\RemoteApi\Handlers\MediaHandler;

echo "🧪 Running JRB Remote Site API Test Suite...\n";

// Test 1: Guard Token Verification
$_SERVER['HTTP_X_JRB_TOKEN'] = 'mock_token';
if (Guard::check() === true) {
    echo "✅ PASS: Guard verified correct token.\n";
} else {
    echo "❌ FAIL: Guard rejected correct token.\n";
}

$_SERVER['HTTP_X_JRB_TOKEN'] = 'wrong_token';
if (Guard::check() === false) {
    echo "✅ PASS: Guard rejected wrong token.\n";
} else {
    echo "❌ FAIL: Guard accepted wrong token.\n";
}

// Test 2: System Handler Output
$response = SystemHandler::get_site_info();
if ($response->status === 200 && $response->data['name'] === 'Mock Blog') {
    echo "✅ PASS: SystemHandler returned correct site info.\n";
} else {
    echo "❌ FAIL: SystemHandler returned unexpected data.\n";
}

// Test 3: Media Handler (Basic Registration)
if (method_exists('JRB\RemoteApi\Handlers\MediaHandler', 'register_routes')) {
    echo "✅ PASS: MediaHandler class is functional.\n";
} else {
    echo "❌ FAIL: MediaHandler class error.\n";
}

echo "🏁 Test Suite Complete.\n";
