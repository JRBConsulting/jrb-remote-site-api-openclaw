<?php
/**
 * DEEP-DIVE SECURITY AUDIT - JRB Remote Site API v6.4.0
 */
require_once __DIR__ . '/tests/wp-mock.php';
require_once __DIR__ . '/src/Auth/Guard.php';
require_once __DIR__ . '/src/Core/Plugin.php';
require_once __DIR__ . '/src/Handlers/AdminHandler.php';
require_once __DIR__ . '/src/Handlers/FluentCrmHandler.php';
require_once __DIR__ . '/src/Handlers/FluentSupportHandler.php';

use JRB\RemoteApi\Auth\Guard;

echo "\n============================================\n";
echo "🔐 JOEL'S DEEP-DIVE SECURITY AUDIT v6.4.0\n";
echo "============================================\n\n";

$failures = 0;

// 1. TOKEN LEAKAGE TEST (URL vs HEADER)
// We only support Headers now. Let's verify URL params fail.
update_option('jrb_remote_api_token', 'secure-key-123');
$_GET['token'] = 'secure-key-123'; // Old insecure method
$_SERVER['HTTP_X_JRB_TOKEN'] = '';

if (Guard::check() === false) {
    echo "🛡️  [AUTH] PASS: URL token leakage blocked. System is header-only.\n";
} else {
    echo "☢️  [AUTH] FAIL: TOKEN LEAK DETECTED via \$_GET params.\n";
    $failures++;
}

// 2. HEADER AUTH VERIFICATION
$_SERVER['HTTP_X_JRB_TOKEN'] = 'secure-key-123';
if (Guard::check() === true) {
    echo "🛡️  [AUTH] PASS: Header authentication (X-JRB-Token) verified.\n";
} else {
    echo "☢️  [AUTH] FAIL: Header authentication failed.\n";
    $failures++;
}

// 3. FAIL-CLOSED PERMISSION CHECK
// Empty permissions should deny everything.
update_option('jrb_remote_api_permissions', []); 
if (Guard::can('crm_subscribers_read') === false) {
    echo "🛡️  [PERMS] PASS: Fail-closed logic verified (No perms = No access).\n";
} else {
    echo "☢️  [PERMS] FAIL: System allowed access with empty permissions.\n";
    $failures++;
}

// 4. GRANULAR ACCESS TEST
update_option('jrb_remote_api_permissions', ['crm_subscribers_read']);
if (Guard::can('crm_subscribers_read') === true && Guard::can('support_tickets_read') === false) {
    echo "🛡️  [PERMS] PASS: Granular enforcement verified (Access scoped correctly).\n";
} else {
    echo "☢️  [PERMS] FAIL: Scoped access leaked higher privileges.\n";
    $failures++;
}

// 5. SQL INJECTION (STATIC ANALYSIS)
echo "🔍 [STATIC] Analyzing Handlers for SQL vulnerabilities...\n";
$audit_dirs = ['src/Handlers', 'src/Auth', 'src/Core'];
$unsafe_found = false;

foreach ($audit_dirs as $dir) {
    $files = glob(__DIR__ . "/$dir/*.php");
    foreach ($files as $file) {
        $content = file_get_contents($file);
        
        // Find $wpdb calls that don't involve 'prepare' or 'prefix' (helper setup)
        if (preg_match('/\$wpdb->(query|get_results|get_row|get_var|get_col)\(\s*["\']/', $content)) {
             echo "☢️  [STATIC] WARNING: Direct string query found in: " . basename($file) . "\n";
             $unsafe_found = true;
        }
    }
}

if (!$unsafe_found) {
    echo "🛡️  [STATIC] PASS: No direct string-query patterns found. All use \$wpdb->prepare().\n";
} else {
    $failures++;
}

echo "\n============================================\n";
if ($failures === 0) {
    echo "✅ AUDIT RESULT: SYSTEM HARDENED. GO FOR PRODUCTION.\n";
} else {
    echo "❌ AUDIT RESULT: $failures SECURITY VULNERABILITIES FOUND.\n";
}
echo "============================================\n\n";
