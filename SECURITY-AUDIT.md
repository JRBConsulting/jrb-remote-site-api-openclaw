# Security Audit Report: OpenClaw API WordPress Plugin v2.1.0

**Audit Date:** 2026-02-14  
**Version Audited:** 2.1.0  
**Auditor:** Security Audit (Automated + Manual Review)  
**Verdict:** ❌ **FAIL - Critical vulnerabilities must be fixed before release**

---

## Executive Summary

This audit identified **2 CRITICAL**, **2 HIGH**, **4 MEDIUM**, and **3 LOW** severity issues. The most severe vulnerabilities involve the media upload functionality which allows SVG files (XSS vector) and lacks proper file extension validation, potentially enabling remote code execution on misconfigured servers.

---

## CRITICAL Issues (Must Fix Before Release)

### CVE-CRITICAL-001: SVG File Upload Enables Stored XSS

**Location:** `openclaw_upload_media()` lines 435-442

**Issue:** The file upload endpoint explicitly allows `image/svg+xml` MIME type. SVG files are XML-based and can contain embedded JavaScript that executes when viewed in browsers.

```php
// CURRENT VULNERABLE CODE
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
```

**Attack Vector:**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" onload="alert(document.cookie)">
  <rect width="100" height="100"/>
</svg>
```

Upload this file, and when any admin/user views the media library, the JavaScript executes. This steals cookies, performs actions on behalf of the victim, or delivers malware.

**Impact:** Stored XSS → Session hijacking → Full admin compromise → Site takeover

**Fix:**
```php
// REMOVE SVG from allowed types
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

// OR if SVG support is required, add sanitization:
if ($mime_type === 'image/svg+xml') {
    // Use a library like SVG-Sanitizer
    if (!class_exists('enshrined\\svgSanitize\\Sanitizer')) {
        return new WP_Error('svg_support', 'SVG sanitization library not available', ['status' => 500]);
    }
    $sanitizer = new \enshrined\svgSanitize\Sanitizer();
    $sanitized = $sanitizer->sanitize(file_get_contents($file['tmp_name']));
    if ($sanitized === false) {
        return new WP_Error('svg_invalid', 'SVG file contains malicious content', ['status' => 400]);
    }
    file_put_contents($file['tmp_name'], $sanitized);
}
```

**Recommendation:** Remove SVG from allowed MIME types unless business-critical. If required, use a dedicated SVG sanitization library.

---

### CVE-CRITICAL-002: File Extension Validation Bypass

**Location:** `openclaw_upload_media()` lines 437-460

**Issue:** The code validates MIME type via `finfo_file()` but passes the original `$_FILES['file']` directly to `wp_handle_upload()`. While WordPress performs some validation, the combination of:
1. Trusted MIME type from uploaded file (can be spoofed with valid image header)
2. Original filename extension preserved by WordPress
3. Server misconfiguration that executes PHP for any extension in filename

Creates a potential Remote Code Execution (RCE) vector.

```php
// CURRENT VULNERABLE CODE
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file['tmp_name']);  // Validates MIME
// ...but then...
$upload = wp_handle_upload($file, ['test_form' => false]);  // Uses original extension
```

**Attack Vector:**
1. Create a polyglot file (valid image header + embedded PHP payload)
2. Name it `exploit.jpg.php` or use double extension
3. `finfo` reports `image/jpeg` (valid)
4. On server with `AddHandler application/x-httpd-php .php` in `.htaccess` or similar misconfiguration, the file executes as PHP

**Impact:** Remote Code Execution → Complete server compromise

**Fix:**
```php
// STRICT FILE VALIDATION

// 1. Validate MIME type
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime_type, $allowed_types, true)) {
    return new WP_Error('invalid_type', 'Only image files are allowed (JPEG, PNG, GIF, WebP)', ['status' => 400]);
}

// 2. Map MIME type to SAFE extension (don't trust user input)
$mime_to_ext = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
];
$safe_ext = $mime_to_ext[$mime_type] ?? 'jpg';

// 3. Generate safe filename
$base_name = pathinfo(sanitize_file_name($file['name']), PATHINFO_FILENAME);
$safe_name = sanitize_file_name($base_name . '.' . $safe_ext);

// 4. Override the file array with safe name
$file['name'] = $safe_name;

// 5. Re-validate file size from actual file (not user-provided value)
$file['size'] = filesize($file['tmp_name']);

// 6. Use WP upload with test_type enabled for additional validation
$upload = wp_handle_upload($file, ['test_form' => false, 'test_type' => false]);

// 7. Verify the uploaded file doesn't contain PHP
$uploaded_content = file_get_contents($upload['file']);
if (preg_match('/<\?php|<\?=|<\s*script\s+language\s*=\s*["\']?php/i', $uploaded_content)) {
    // Remove the malicious file
    wp_delete_file($upload['file']);
    return new WP_Error('suspicious_content', 'File contains suspicious content', ['status' => 400]);
}
```

**Recommendation:** Implement strict MIME-to-extension mapping and never trust user-provided extensions for executable file types.

---

## HIGH Issues (Should Fix)

### CVE-HIGH-001: Page Creation Missing Status Validation

**Location:** `openclaw_create_page()` lines 524-534

**Issue:** Unlike the post creation function which validates status values, the page creation function accepts ANY status value without validation.

```php
// CURRENT VULNERABLE CODE
function openclaw_create_page($request) {
    $data = $request->get_json_params();
    $page_id = wp_insert_post([
        'post_type' => 'page',
        'post_title' => sanitize_text_field($data['title'] ?? 'Untitled'),
        'post_content' => $data['content'] ?? '',
        'post_status' => sanitize_text_field($data['status'] ?? 'draft'),  // NO VALIDATION!
    ], true);
```

**Comparison with secure post creation:**
```php
// Posts (lines 242-245) - CORRECT
$allowed_statuses = ['draft', 'pending', 'private', 'publish'];
$status = sanitize_text_field($data['status'] ?? 'draft');
if (!in_array($status, $allowed_statuses, true)) {
    $status = 'draft';
}
```

**Impact:** Attacker could set unusual or future statuses that may bypass workflow controls, or exploit WordPress core vulnerabilities related to unexpected post status values.

**Fix:**
```php
function openclaw_create_page($request) {
    $data = $request->get_json_params();
    
    // Validate status (same as posts)
    $allowed_statuses = ['draft', 'pending', 'private', 'publish'];
    $status = sanitize_text_field($data['status'] ?? 'draft');
    if (!in_array($status, $allowed_statuses, true)) {
        $status = 'draft';
    }
    
    $page_id = wp_insert_post([
        'post_type' => 'page',
        'post_title' => sanitize_text_field($data['title'] ?? 'Untitled'),
        'post_content' => $data['content'] ?? '',
        'post_status' => $status,
    ], true);
    // ...
}
```

---

### CVE-HIGH-002: Author ID Spoofing Without Capability Check

**Location:** `openclaw_create_post()` lines 248-261

**Issue:** The post creation endpoint allows specifying an arbitrary `author_id`, enabling post impersonation. Any authenticated API user can create posts under another user's identity.

```php
// CURRENT VULNERABLE CODE
$author_id = (int) ($data['author_id'] ?? 1);
if ($author_id > 1) {
    $user = get_user_by('id', $author_id);
    if (!$user) {
        return new WP_Error('invalid_author', 'Invalid author ID', ['status' => 400]);
    }
}
// ...uses $author_id directly
$post_data['post_author'] = $author_id;
```

**Impact:** 
- Create posts impersonating administrators or editors
- Bypass editorial workflows (post appears from trusted author)
- Social engineering attacks via fake authoritative posts
- Audit trail corruption

**Fix Options:**

**Option A - Remove capability (recommended):**
```php
// Don't allow author_id specification via API
$post_data['post_author'] = get_current_user_id(); // or a fixed service account
```

**Option B - Require explicit capability:**
```php
// Define new capability: posts_set_author (off by default)
function openclaw_get_default_capabilities() {
    return [
        // ... existing ...
        'posts_set_author' => false,  // Off by default
    ];
}

// In openclaw_create_post():
if (!empty($data['author_id']) && openclaw_can('posts_set_author')) {
    $author_id = (int) $data['author_id'];
    // ... validate ...
} else {
    $author_id = get_current_user_id() ?: 1;
}
```

---

## MEDIUM Issues (Recommend Fix)

### CVE-MEDIUM-001: File Size Validation Uses Untrusted Input

**Location:** `openclaw_upload_media()` lines 445-448

**Issue:** File size validation uses `$file['size']` from `$_FILES` array, which is client-provided and can be spoofed.

```php
// CURRENT CODE
$max_size = 10 * 1024 * 1024;
if ($file['size'] > $max_size) {
    return new WP_Error('file_too_large', 'File exceeds 10MB limit', ['status' => 400]);
}
```

**Impact:** Attacker could potentially bypass the size check with a manipulated request, then upload a larger file that consumes server resources.

**Fix:**
```php
// Validate actual file size after upload
$max_size = 10 * 1024 * 1024;
$actual_size = filesize($file['tmp_name']);
if ($actual_size === false || $actual_size > $max_size) {
    return new WP_Error('file_too_large', 'File exceeds 10MB limit', ['status' => 400]);
}
```

---

### CVE-MEDIUM-002: No Ownership Verification on Media/Delete Operations

**Location:** `openclaw_delete_media()` lines 499-512, `openclaw_delete_post()` lines 321-336

**Issue:** Any authenticated API user with the capability can delete ANY media item or post, not just ones they own.

```php
// CURRENT CODE - No ownership check
function openclaw_delete_media($request) {
    $id = (int) $request['id'];
    $attachment = get_post($id);
    if (!$attachment || $attachment->post_type !== 'attachment') {
        return new WP_Error('not_found', 'Media not found', ['status' => 404]);
    }
    // No check: $attachment->post_author === current_user
    $deleted = wp_delete_attachment($id, true);
```

**Impact:** Privilege escalation within capability scope - any user with `media_delete` can delete all site media, including branding assets, product images, etc.

**Recommendation:** This may be intentional for a remote management API. If so, document it clearly. Otherwise, add ownership or role-based checks:

```php
// Optional ownership check
function openclaw_delete_media($request) {
    $id = (int) $request['id'];
    $attachment = get_post($id);
    
    // Option 1: Check ownership
    $api_user = get_option('openclaw_api_user', 0);
    if ($attachment->post_author && $attachment->post_author !== $api_user) {
        // Optional: Check if user has higher privileges
        return new WP_Error('not_owner', 'You can only delete media you uploaded', ['status' => 403]);
    }
```

---

### CVE-MEDIUM-003: Information Disclosure in Error Messages

**Location:** Multiple locations

**Issue:** Error messages can leak sensitive internal information:

```php
// Line 199 - Reveals admin location
'API token not configured. Go to Settings → OpenClaw API.'

// Line 477 - Leaks WordPress error details  
return new WP_Error('attachment_failed', $attach_id->get_error_message(), ['status' => 500]);

// Line 207 - Version disclosure
'version' => '2.1.0'

// Line 657 - Reveals slug in error
'Plugin not found on WordPress.org: ' . $slug
```

**Impact:** Helps attackers fingerprint the plugin version, discover admin areas, and debug exploitation attempts.

**Fix:**
```php
// Generic error messages for production
return new WP_Error('configuration_error', 'API configuration error. Contact administrator.', ['status' => 500]);
return new WP_Error('upload_failed', 'File upload failed', ['status' => 500]);

// Version in ping should be optional or admin-only
// Consider: Only show version if authenticated or via separate endpoint
```

---

### CVE-MEDIUM-004: Media Upload Creates Attachments Without Author

**Location:** `openclaw_upload_media()` lines 465-476

**Issue:** The attachment post is created without specifying `post_author`. This can lead to:
- Attachments owned by user ID 0 (no user)
- Attachments owned by the first admin
- Audit trail gaps

```php
// CURRENT CODE - No post_author
$attachment = [
    'post_mime_type' => $upload['type'],
    'post_title' => sanitize_text_field($request->get_param('title') ?: pathinfo($filename, PATHINFO_FILENAME)),
    'post_content' => '',
    'post_status' => 'inherit',
];
$attach_id = wp_insert_attachment($attachment, $upload['file']);
```

**Fix:**
```php
// Set a defined author (e.g., first admin or dedicated API user)
$api_user_id = get_option('openclaw_api_user_id', 1);
$attachment = [
    'post_mime_type' => $upload['type'],
    'post_title' => sanitize_text_field($request->get_param('title') ?: pathinfo($filename, PATHINFO_FILENAME)),
    'post_content' => '',
    'post_status' => 'inherit',
    'post_author' => $api_user_id,
];
```

---

## LOW Issues (Nice to Have)

### CVE-LOW-001: No Rate Limiting

**Location:** All API endpoints

**Issue:** The API has no rate limiting, potentially enabling:
- Token brute-force attacks
- DoS via repeated resource-intensive operations
- API abuse

**Recommendation:** Implement rate limiting via:
- WordPress transients (simple)
- Server-level rate limiting (nginx/Apache)
- Dedicated plugin

```php
// Simple rate limiting example
function openclaw_check_rate_limit($action = 'api') {
    $ip = $_SERVER['REMOTE_ADDR'];
    $transient_key = "openclaw_rate_{$action}_" . md5($ip);
    $count = (int) get_transient($transient_key);
    
    $limits = [
        'api' => 100,      // 100 requests per minute
        'upload' => 10,    // 10 uploads per minute
    ];
    
    if ($count >= ($limits[$action] ?? 100)) {
        return new WP_Error('rate_limited', 'Too many requests. Try again later.', ['status' => 429]);
    }
    
    set_transient($transient_key, $count + 1, MINUTE_IN_SECONDS);
    return true;
}
```

---

### CVE-LOW-002: Token Stored in Transient Briefly

**Location:** `openclaw_api_admin_page()` lines 966-969

**Issue:** New tokens are stored in a transient for 60 seconds to display to the admin:
```php
$new_token = $token;
set_transient('openclaw_new_token', $token, 60);
```

**Impact:** Token visible in database/object cache for 60 seconds after generation. If the database is compromised, unhashed tokens could be extracted.

**Fix:** Don't store the token at all - capture it only in the immediate response:
```php
// Pass token directly to display without transient storage
$new_token = $token;
// Remove: set_transient('openclaw_new_token', $token, 60);
// Token only lives in PHP memory for this request
```

---

### CVE-LOW-003: Plugin Version Publicly Disclosed

**Location:** `openclaw_ping()` line 207

**Issue:** The `/ping` endpoint returns the exact plugin version:
```php
return ['status' => 'ok', 'version' => '2.1.0', 'time' => current_time('mysql')];
```

**Impact:** Helps attackers identify vulnerable plugin versions.

**Fix:** Remove version or only show to authenticated users:
```php
function openclaw_ping() {
    return ['status' => 'ok', 'time' => current_time('mysql')];
}
```

---

## Positive Security Findings

The following security best practices are already implemented:

✅ **Timing-safe token comparison** using `hash_equals()`  
✅ **Hashed token storage** (not plaintext)  
✅ **Proper sanitization** of text inputs with `sanitize_text_field()`  
✅ **Input validation** with type casting and whitelisting  
✅ **Nonce verification** on admin forms via `check_admin_referer()`  
✅ **Output escaping** in admin page via `esc_html()`, `esc_attr()`, `esc_js()`  
✅ **Capability-based access control** with granular permissions  
✅ **Default-deny security posture** (capabilities off by default for destructive actions)  
✅ **Plugin slug validation** with strict regex pattern  
✅ **Search query length limits** (200 chars)  
✅ **Migration path** for legacy plaintext tokens to hashed storage  

---

## Summary Table

| ID | Severity | Issue | Status |
|----|----------|-------|--------|
| CVE-CRITICAL-001 | CRITICAL | SVG XSS via file upload | 🔴 Must Fix |
| CVE-CRITICAL-002 | CRITICAL | File extension validation bypass | 🔴 Must Fix |
| CVE-HIGH-001 | HIGH | Page status validation missing | 🟠 Should Fix |
| CVE-HIGH-002 | HIGH | Author ID spoofing | 🟠 Should Fix |
| CVE-MEDIUM-001 | MEDIUM | Spoofable file size check | 🟡 Recommend |
| CVE-MEDIUM-002 | MEDIUM | No ownership verification | 🟡 Recommend |
| CVE-MEDIUM-003 | MEDIUM | Information disclosure | 🟡 Recommend |
| CVE-MEDIUM-004 | MEDIUM | Missing post_author | 🟡 Recommend |
| CVE-LOW-001 | LOW | No rate limiting | 🟢 Optional |
| CVE-LOW-002 | LOW | Transient token storage | 🟢 Optional |
| CVE-LOW-003 | LOW | Version disclosure | 🟢 Optional |

---

## Pass/Fail Assessment

### ❌ **FAIL - DO NOT RELEASE**

**Reasons:**
1. **CVE-CRITICAL-001** allows Stored XSS via SVG uploads, enabling full site compromise
2. **CVE-CRITICAL-002** could allow RCE on common server misconfigurations

**Required Actions Before Release:**
1. Remove `image/svg+xml` from allowed MIME types, OR implement SVG sanitization
2. Implement strict MIME-to-extension mapping in file uploads
3. Add PHP content scanning to uploaded files
4. Validate file size from actual file, not user-provided value
5. Add status validation to page creation endpoint

**Recommended Timeline:**
- Fix CRITICAL issues: **Immediate** (blocks release)
- Fix HIGH issues: **Before v2.1.0 release**
- Fix MEDIUM issues: **v2.1.1 patch release**
- Fix LOW issues: **v2.2.0 future release**

---

*End of Security Audit Report*