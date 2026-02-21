# JRB Remote Site API for OpenClaw Skill

Interface with WordPress sites running the `jrb-remote-site-api-openclaw` plugin. This skill enables AI agents to perform administrative tasks, content management, and integration with the Fluent suite (CRM, Forms, Support, etc.) via a secure REST API.

## Configuration

Required environment variables for targeting a site:
- `JRB_API_URL`: The base URL of the site (e.g., `https://jrbconsulting.au`)
- `JRB_API_TOKEN`: The secure API token configured in the plugin settings

## Core Capabilities

### 1. System & Auth
- **Ping**: Verify connection and token validity.
- **Site Info**: Get WordPress version, active theme, plugin version, and capabilities.
- **Self-Update**: Trigger the plugin to update itself from GitHub or a specific ZIP URL.

### 2. Content Management (CRUD)
- **Posts & Pages**: Create, read, update, delete, and list. Supports custom statuses (draft, publish, private) and PublishPress integration.
- **Media**: Upload and manage files in the WordPress Media Library.
- **Categories & Tags**: Management of taxonomies.
- **Menus**: Create and manage WordPress navigation menus and their items.

### 3. Plugin & Theme Management
- **Plugins**: List, install, activate, deactivate, update, and delete. Supports ZIP uploads.
- **Themes**: List active/available themes, switch themes, install from URL, and delete.

### 4. Fluent Suite Integration (Modules)
- **FluentForms**: Programmatic form creation, listing, and management.
- **FluentCRM**: Manage contacts, lists, tags, and campaigns.
- **FluentSupport**: Professional ticket management and customer support.
- **FluentProject**: Task and project management automation.
- **FluentCommunity**: Community interaction and management.

## Usage Patterns

### Verification
```bash
curl -H "X-OpenClaw-Token: $JRB_API_TOKEN" "$JRB_API_URL/wp-json/openclaw/v1/ping"
```

### Create a Page
```bash
curl -X POST -H "X-OpenClaw-Token: $JRB_API_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"title": "New Page", "content": "Hello World", "status": "publish"}' \
     "$JRB_API_URL/wp-json/openclaw/v1/pages"
```

### Self-Update from GitHub
```bash
curl -X POST -H "X-OpenClaw-Token: $JRB_API_TOKEN" \
     "$JRB_API_URL/wp-json/openclaw/v1/self-update"
```

## Security Guidelines
1. **Token Protection**: Never log or expose the `X-OpenClaw-Token`.
2. **HTTPS**: The plugin enforces HTTPS for all operations.
3. **Capability Checks**: Operations are strictly checked against the token's granted capabilities in the plugin UI.
