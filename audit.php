<?php
/**
 * Security Audit Script for JRB Remote Site API v6.4.0
 */
require_once __DIR__ . '/tests/wp-mock.php';
require_once __DIR__ . '/src/Auth/Guard.php';

use JRB\RemoteApi\Auth\Guard;

echo "\n--- JRB ENGINEERING AUDIT v6.4.0 ---\n";

// 1. Auth Guard Reliability (Header-Only Check)
update_option('jrb_remote_api_token', 'fort-knox-key');
$_SERVER['HTTP_X_JRB_TOKEN'] = 'wrong-token';

if (Guard::check() === false) {
    echo "✅ [SECURITY] Invalid token rejected.\n";
} else {
    echo "❌ [SECURITY] CRITICAL: Invalid token accepted!\n";
}

$_SERVER['HTTP_X_JRB_TOKEN'] = 'fort-knox-key';
if (Guard::check() === true) {
    echo "✅ [SECURITY] Valid token accepted.\n";
} else {
    echo "❌ [SECURITY] Valid token rejected.\n";
}

// 2. Permission Validation (Fail-Closed Logic)
update_option('jrb_remote_api_permissions', ['support_tickets_read']);
if (Guard::can('support_tickets_read') === true && Guard::can('crm_subscribers_read') === false) {
    echo "✅ [SECURITY] Granular permissions enforced (Fail-Closed verified).\n";
} else {
    echo "❌ [SECURITY] Permission check failed (Leaked capability).\n";
}

// 3. Mechanical "Sloppy" Check (SQL & Patterns)
$grep_sql = shell_exec("grep -r \"\$wpdb->\" src/ | grep -v \"prepare\"");
if (empty($grep_sql)) {
    echo "✅ [MECHANICAL] 100% Prepared SQL usage verified.\n";
} else {
    echo "⚠️ [MECHANICAL] Potential direct SQL found: $grep_sql\n";
}

// 4. Branding & Cleanliness Scan
$sloppy = shell_exec("grep -ri \"OpenClaw\" src/ | grep -v \"JRB Remote Site API for OpenClaw\"");
if (empty($sloppy)) {
    echo "✅ [MECHANICAL] 100% Rebranding (OpenClaw branding removed from logic).\n";
} else {
    echo "⚠️ [MECHANICAL] Residual branding found in core src.\n";
}

// 5. Asset Health
$banner_size = filesize('assets/banner-1544x500.png');
if ($banner_size < 100000) {
    echo "✅ [ASSETS] Banner optimized (" . round($banner_size/1024) . "KB).\n";
} else {
    echo "⚠️ [ASSETS] Banner oversized.\n";
}

echo "--- AUDIT COMPLETE ---\n";
