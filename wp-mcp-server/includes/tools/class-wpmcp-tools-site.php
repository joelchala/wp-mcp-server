<?php

if (!defined('ABSPATH')) {
    exit;
}

class WPMCP_Tools_Site {

    public static function register() {
        WPMCP_Tool_Registry::register('wp_get_site_info', array(
            'description' => 'Obtiene información general del sitio: versión de WordPress y PHP, tema activo, número de plugins y usuarios.',
            'capability' => 'manage_options',
            'inputSchema' => array('type' => 'object', 'properties' => new stdClass()),
            'callback' => array(__CLASS__, 'get_site_info'),
        ));
    }

    public static function get_site_info($args) {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        global $wp_version;

        $active_plugins = get_option('active_plugins', array());

        return array(
            'name' => get_bloginfo('name'),
            'description' => get_bloginfo('description'),
            'url' => home_url(),
            'wp_version' => $wp_version,
            'php_version' => PHP_VERSION,
            'active_theme' => wp_get_theme()->get('Name'),
            'plugin_count' => count(get_plugins()),
            'active_plugin_count' => count($active_plugins),
            'user_count' => count_users()['total_users'],
            'multisite' => is_multisite(),
        );
    }
}
