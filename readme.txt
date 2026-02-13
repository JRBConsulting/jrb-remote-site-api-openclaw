=== OpenClaw API ===
Contributors: openclaw
Tags: api, rest, remote, management, openclaw
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 2.0.2
Requires PHP: 7.4
License: AGPLv3.0 or later
License URI: https://www.gnu.org/licenses/agpl-3.0.html

REST API for OpenClaw remote site management with fine-grained capability controls.

== Description ==

OpenClaw API provides a secure REST API for remote management of your WordPress site. 
Designed to work with OpenClaw AI assistants, it enables remote content creation, 
plugin management, and site administration.

**Features:**

* **Content Management** - Create, update, and delete posts, pages, categories, and tags
* **Plugin Management** - Search, install, activate, deactivate, update, and delete plugins
* **Fine-grained Permissions** - Enable only the capabilities you need
* **Token-based Authentication** - Secure API token system (works behind Cloudflare)
* **WordPress.org Integration** - Search and install plugins directly from the repository

**Security:**

* All endpoints require authentication via `X-OpenClaw-Token` header
* Tokens are hashed for secure storage
* Dangerous actions (plugin install, activate, delete) are disabled by default
* Token can be regenerated or deleted at any time
* Works with Cloudflare and other CDNs that strip Authorization headers
* Timing-safe token comparison prevents timing attacks

== Installation ==

1. Upload the `openclaw-api` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings → OpenClaw API
4. Generate an API token
5. Enable the capabilities you need
6. Use the token in your API requests with the `X-OpenClaw-Token` header

== Frequently Asked Questions ==

= Why not use the WordPress REST API? =

The standard WordPress REST API uses the Authorization header, which is stripped by 
Cloudflare and some other CDNs. OpenClaw API uses a custom header (`X-OpenClaw-Token`) 
that works reliably behind these services.

= Is this plugin secure? =

Yes. All endpoints require a valid API token. Tokens are hashed using WordPress's 
secure hashing. Dangerous operations are disabled by default and must be explicitly 
enabled in the settings. Tokens can be regenerated or revoked at any time.

= Can I use this with other tools besides OpenClaw? =

Yes! Any tool that can make HTTP requests with custom headers can use this API.

= Why AGPLv3.0? =

The AGPLv3.0 license ensures that anyone who modifies this software and makes it 
available as a network service (such as a SaaS offering) must also share their 
modifications under the same license terms. This prevents proprietary forks while 
allowing full freedom for end users.

== Usage ==

**Authentication:**

```bash
curl -H "X-OpenClaw-Token: YOUR_TOKEN" \
    https://yoursite.com/wp-json/openclaw/v1/site
```

**List Posts:**

```bash
curl -H "X-OpenClaw-Token: YOUR_TOKEN" \
    https://yoursite.com/wp-json/openclaw/v1/posts
```

**Create a Post:**

```bash
curl -X POST \
    -H "X-OpenClaw-Token: YOUR_TOKEN" \
    -H "Content-Type: application/json" \
    -d '{"title":"My Post","content":"Post content","status":"draft"}' \
    https://yoursite.com/wp-json/openclaw/v1/posts
```

**Search Plugins:**

```bash
curl -H "X-OpenClaw-Token: YOUR_TOKEN" \
    "https://yoursite.com/wp-json/openclaw/v1/plugins/search?q=seo"
```

**Install and Activate a Plugin:**

```bash
curl -X POST \
    -H "X-OpenClaw-Token: YOUR_TOKEN" \
    -H "Content-Type: application/json" \
    -d '{"slug":"wordpress-seo","activate":true}' \
    https://yoursite.com/wp-json/openclaw/v1/plugins/install
```

== API Endpoints ==

| Method | Endpoint | Capability | Description |
|--------|----------|------------|-------------|
| GET | `/ping` | - | Health check (no auth) |
| GET | `/site` | site_info | Site information |
| GET | `/posts` | posts_read | List posts |
| POST | `/posts` | posts_create | Create post |
| PUT | `/posts/{id}` | posts_update | Update post |
| DELETE | `/posts/{id}` | posts_delete | Delete post |
| GET | `/pages` | pages_read | List pages |
| POST | `/pages` | pages_create | Create page |
| GET | `/categories` | categories_read | List categories |
| POST | `/categories` | categories_create | Create category |
| GET | `/tags` | tags_read | List tags |
| POST | `/tags` | tags_create | Create tag |
| GET | `/media` | media_read | List media |
| GET | `/users` | users_read | List users |
| GET | `/plugins` | plugins_read | List installed plugins |
| GET | `/plugins/search` | plugins_search | Search WordPress.org |
| POST | `/plugins/install` | plugins_install | Install plugin |
| POST | `/plugins/{slug}/activate` | plugins_activate | Activate plugin |
| POST | `/plugins/{slug}/deactivate` | plugins_deactivate | Deactivate plugin |
| POST | `/plugins/{slug}/update` | plugins_update | Update plugin |
| DELETE | `/plugins/{slug}` | plugins_delete | Delete plugin |

== Changelog ==

= 2.0.2 =
* SECURITY: Changed license to AGPLv3.0 for stronger copyleft
* SECURITY: Token now hashed before storage (tokens shown once on generation)
* SECURITY: Fixed post type validation (can only modify 'post' type, not pages/attachments)
* SECURITY: Added post existence check before update/delete
* SECURITY: Removed email from users endpoint (privacy protection)
* Added null checks in format_post function
* Added missing term validation in category/tag creation

= 2.0.1 =
* SECURITY: Fixed timing attack vulnerability in token verification
* SECURITY: Added post status validation (only draft, pending, private, publish allowed)
* SECURITY: Added author ID validation (validates user exists before assignment)
* Added plugin slug validation (lowercase alphanumeric with hyphens only)
* Added search query length limits (max 200 characters)
* Added pagination limits (max 100 per page, min page 1)

= 2.0.0 =
* Renamed from Lilith API to OpenClaw API
* New API namespace: `/wp-json/openclaw/v1/`
* New auth header: `X-OpenClaw-Token`
* Added fine-grained capability controls
* Added plugin management endpoints

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 2.0.2 =
**Important:** Token storage has changed. After updating, you will need to regenerate 
your API token in Settings → OpenClaw API. The new token will be shown ONCE - save it 
securely.

= 2.0.0 =
Breaking change: API namespace changed from `lilith/v1` to `openclaw/v1`. 
The auth header changed from `X-Lilith-Token` to `X-OpenClaw-Token`.
You will need to regenerate your API token after upgrading.