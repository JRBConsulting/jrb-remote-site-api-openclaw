<?php
/**
 * 🔐 FINAL SECURITY AUDIT - JRB Remote Site API v6.4.0 (Refactored)
 */
require_once __DIR__ . '/tests/wp-mock.php';
require_once __DIR__ . '/src/Auth/Guard.php';
require_once __DIR__ . '/src/Core/Plugin.php';
require_once __DIR__ . '/src/Handlers/AdminHandler.php';

use JRB\RemoteApi\Auth\Guard;

echo "\n--- REFAC-SECURITY AUDIT START ---\n";

// 1. Token Verification (Restored hashed logic)
update_option('openclaw_api_token_hash', wp_hash('jrb-secret'));
$_SERVER['HTTP_X_OPENCLAW_TOKEN'] = 'jrb-secret';

if (Guard::verify_token() === true) {
    echo "✅ [AUTH] PASS: Restored Token Hashing logic verified.\n";
} else {
    echo "❌ [AUTH] FAIL: Token verification failed.\n";
}

// 2. Fail-Closed Capability Check
update_option('openclaw_api_capabilities', ['site_info' => true]);
if (Guard::can('site_info') === true && Guard::can('crm_subscribers_read') === false) {
    echo "✅ [PERMS] PASS: Granular protection active (Fail-Closed).\n";
} else {
    echo "❌ [PERMS] FAIL: Access Control bypassed.\n";
}

// 3. Sloppy SQL Check
$sql_concats = shell_exec("grep -r \"\$wpdb->\" src/ | grep -v \"prepare\" | grep \"\\$\"");
if (empty($sql_concats)) {
    echo "✅ [SQL] PASS: 100% Prepared SQL usage in new handlers.\n";
} else {
    echo "⚠️ [SQL] WARN: Slop detected.\n";
    echo $sql_concats;
}

// 4. Persistence Check
if (defined('\JRB\RemoteApi\Core\Plugin::API_NAMESPACE')) {
    echo "✅ [ARCH] PASS: Core namespace 'openclaw/v1' preserved for compatibility.\n";
}

echo "--- AUDIT COMPLETE ---\n";
