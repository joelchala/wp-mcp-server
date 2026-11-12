=== WP MCP Server ===
Contributors: chalatico07
Tags: mcp, ai, claude, rest-api, automation
Requires at least: 5.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Native MCP (Model Context Protocol) server for WordPress. Let Claude or any MCP client manage your content, plugins, themes and users.

== Description ==

This plugin turns your WordPress site into an MCP server (Streamable HTTP transport) authenticated with WordPress Application Passwords. Any MCP-compatible client (Claude Desktop, Claude Code, and others) can connect and use tools to:

* Create, edit, list and delete posts and pages
* Upload and manage media
* Manage categories, tags and comments
* Install, activate and deactivate plugins and themes (from WordPress.org)
* Manage users
* (Optional, disabled by default) Run direct SQL reads and writes against the database

= Authentication =

Authentication uses the native **WordPress Application Passwords** system. The AI has exactly the permissions of the WordPress user that owns the application password used. There is no separate permission system. Application Passwords require HTTPS (or a `local` environment).

To connect:

1. Go to your WordPress user profile and create a new Application Password.
2. Point your MCP client to the endpoint `https://your-site.com/wp-json/wp-mcp/v1/mcp` using the Streamable HTTP transport, sending the header `Authorization: Basic base64(username:application_password)`.
3. Use a WordPress user whose role matches the scope you want to give the AI (Editor for content only, Administrator for full management including plugins, themes and users).

= Database tools =

The tools `wp_db_list_tables`, `wp_db_describe_table`, `wp_db_query` (SELECT) and `wp_db_execute` (INSERT/UPDATE/DELETE) give direct SQL access to the database, bypassing WordPress hooks. They are disabled by default and must be enabled explicitly under Settings → WP MCP Server. They always require the `manage_options` capability (Administrator), block multiple statements and DDL (DROP/ALTER/TRUNCATE/CREATE), and require a WHERE clause on UPDATE/DELETE unless `confirm_no_where=true` is passed.

= Endpoint =

`POST /wp-json/wp-mcp/v1/mcp`

It accepts JSON-RPC 2.0 messages (`initialize`, `tools/list`, `tools/call`, etc.). Batching and SSE streaming over GET are not supported; it always replies with a single JSON object.

== Installation ==

1. Upload the `wp-mcp-server` folder to `/wp-content/plugins/`.
2. Activate the plugin from the WordPress admin.
3. Go to Settings → WP MCP Server to see the endpoint and the Application Passwords status.

== Changelog ==

= 1.1.0 =
* MCP protocol version negotiation (2025-06-18, 2025-03-26, 2024-11-05) instead of echoing the client's version.

= 1.0.0 =
* Initial release.
