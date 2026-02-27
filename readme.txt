=== JRB Remote Site API ===
Contributors: jrbconsulting
Tags: api, remote, jrb_remote, automation, fluentcrm, fluentsupport, fluentboards
Requires at least: 5.6
Tested up to: 6.9
Stable tag: 6.4.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Professional WordPress REST API for JRB Consulting remote site management and automation.

== Description ==

JRB Remote Site API provides a secure, high-fidelity bridge between your WordPress environment and external automation tools like jrb_remote. It extends the WordPress REST API to support remote site management, media handling, and deep integration with the Fluent Suite (CRM, Support, Boards).

== Installation ==

1. Upload the `jrb-remote-site-api` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Configure your API token and permissions in the 'JRB Remote API' settings page.
4. **Agent Integration:** To utilize this API with an jrb_remote agent, install the corresponding skill:
   `clawhub install jrb-remote-site-api`

== Changelog ==

= 6.4.0 =
* Major architectural refactor: Transitioned to PSR-4 modular structure.
* Rebrand to JRB Remote Site API.
* Security Hardening: Refactored Authorization Guard with granular permission mapping.
* Optimized Performance: Conditional loading of module handlers (CRM, Support, etc.).
* Automated Quality: Achieved 100/100 Desloppify engineering score.
* Removed legacy GitHub updater in favor of WordPress.org native updates.

= 6.3.2 =
* First official release on the WordPress Plugin Directory.
* Synchronized versioning across GitHub and SVN.
* Enhanced FluentCRM integration and Square POS bridge support.
