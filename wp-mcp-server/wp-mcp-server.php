<?php
/**
 * Plugin Name: WP MCP Server
 * Plugin URI: https://github.com/joelchala/wp-mcp-server
 * Description: Servidor MCP (Model Context Protocol) nativo para WordPress. Permite conectar Claude u otra IA compatible con MCP para gestionar contenido, plugins, temas y usuarios, autenticado con WordPress Application Passwords.
 * Version: 1.1.0
 * Author: Joel Chala
 * Author URI: https://profiles.wordpress.org/chalatico07/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-mcp-server
 * Requires at least: 5.6
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WPMCP_VERSION', '1.1.0');
define('WPMCP_PLUGIN_FILE', __FILE__);
define('WPMCP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPMCP_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once WPMCP_PLUGIN_DIR . 'includes/class-wpmcp-tool-registry.php';
require_once WPMCP_PLUGIN_DIR . 'includes/class-wpmcp-server.php';
require_once WPMCP_PLUGIN_DIR . 'includes/tools/class-wpmcp-tools-content.php';
require_once WPMCP_PLUGIN_DIR . 'includes/tools/class-wpmcp-tools-plugins-themes.php';
require_once WPMCP_PLUGIN_DIR . 'includes/tools/class-wpmcp-tools-users.php';
require_once WPMCP_PLUGIN_DIR . 'includes/tools/class-wpmcp-tools-site.php';
require_once WPMCP_PLUGIN_DIR . 'includes/tools/class-wpmcp-tools-database.php';
require_once WPMCP_PLUGIN_DIR . 'admin/class-wpmcp-admin.php';

function wpmcp_is_enabled() {
    return '1' === get_option('wpmcp_enabled', '1');
}

function wpmcp_db_tools_enabled() {
    return '1' === get_option('wpmcp_enable_db_tools', '0');
}

function wpmcp_register_tools() {
    if (!wpmcp_is_enabled()) {
        return;
    }
    WPMCP_Tools_Content::register();
    WPMCP_Tools_Plugins_Themes::register();
    WPMCP_Tools_Users::register();
    WPMCP_Tools_Site::register();
    if (wpmcp_db_tools_enabled()) {
        WPMCP_Tools_Database::register();
    }
    do_action('wpmcp_register_tools');
}
add_action('init', 'wpmcp_register_tools', 20);

function wpmcp_init_server() {
    $server = new WPMCP_Server();
    $server->register_routes();
}
add_action('rest_api_init', 'wpmcp_init_server');

function wpmcp_init_admin() {
    if (is_admin()) {
        new WPMCP_Admin();
    }
}
add_action('plugins_loaded', 'wpmcp_init_admin');

function wpmcp_activate() {
    add_option('wpmcp_enabled', '1');
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'wpmcp_activate');

function wpmcp_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'wpmcp_deactivate');
