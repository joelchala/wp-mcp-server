<?php

if (!defined('ABSPATH')) {
    exit;
}

class WPMCP_Tools_Plugins_Themes {

    public static function register() {
        WPMCP_Tool_Registry::register('wp_list_plugins', array(
            'description' => 'Lista los plugins instalados y su estado (activo/inactivo).',
            'capability' => 'activate_plugins',
            'inputSchema' => array('type' => 'object', 'properties' => new stdClass()),
            'callback' => array(__CLASS__, 'list_plugins'),
        ));

        WPMCP_Tool_Registry::register('wp_activate_plugin', array(
            'description' => 'Activa un plugin instalado, identificado por su ruta (carpeta/archivo.php, como aparece en wp_list_plugins).',
            'capability' => 'activate_plugins',
            'inputSchema' => array(
                'type' => 'object',
                'properties' => array('plugin' => array('type' => 'string')),
                'required' => array('plugin'),
            ),
            'callback' => array(__CLASS__, 'activate_plugin'),
        ));

        WPMCP_Tool_Registry::register('wp_deactivate_plugin', array(
            'description' => 'Desactiva un plugin activo, identificado por su ruta.',
            'capability' => 'activate_plugins',
            'inputSchema' => array(
                'type' => 'object',
                'properties' => array('plugin' => array('type' => 'string')),
                'required' => array('plugin'),
            ),
            'callback' => array(__CLASS__, 'deactivate_plugin'),
        ));

        WPMCP_Tool_Registry::register('wp_install_plugin', array(
            'description' => 'Instala (sin activar) un plugin desde el repositorio oficial de WordPress.org por su slug.',
            'capability' => 'install_plugins',
            'inputSchema' => array(
                'type' => 'object',
                'properties' => array(
                    'slug' => array('type' => 'string', 'description' => 'Slug del plugin en WordPress.org, ej: "akismet".'),
                    'activate' => array('type' => 'boolean', 'description' => 'Si es true, activa el plugin después de instalarlo.'),
                ),
                'required' => array('slug'),
            ),
            'callback' => array(__CLASS__, 'install_plugin'),
        ));

        WPMCP_Tool_Registry::register('wp_delete_plugin', array(
            'description' => 'Elimina un plugin instalado (debe estar desactivado primero).',
            'capability' => 'delete_plugins',
            'inputSchema' => array(
                'type' => 'object',
                'properties' => array('plugin' => array('type' => 'string')),
                'required' => array('plugin'),
            ),
            'callback' => array(__CLASS__, 'delete_plugin'),
        ));

        WPMCP_Tool_Registry::register('wp_list_themes', array(
            'description' => 'Lista los temas instalados y cuál está activo.',
            'capability' => 'switch_themes',
            'inputSchema' => array('type' => 'object', 'properties' => new stdClass()),
            'callback' => array(__CLASS__, 'list_themes'),
        ));

        WPMCP_Tool_Registry::register('wp_activate_theme', array(
            'description' => 'Activa un tema instalado por su stylesheet (slug de la carpeta del tema).',
            'capability' => 'switch_themes',
            'inputSchema' => array(
                'type' => 'object',
                'properties' => array('stylesheet' => array('type' => 'string')),
                'required' => array('stylesheet'),
            ),
            'callback' => array(__CLASS__, 'activate_theme'),
        ));

        WPMCP_Tool_Registry::register('wp_install_theme', array(
            'description' => 'Instala (sin activar) un tema desde el repositorio oficial de WordPress.org por su slug.',
            'capability' => 'install_themes',
            'inputSchema' => array(
                'type' => 'object',
                'properties' => array(
                    'slug' => array('type' => 'string'),
                    'activate' => array('type' => 'boolean', 'description' => 'Si es true, activa el tema después de instalarlo.'),
                ),
                'required' => array('slug'),
            ),
            'callback' => array(__CLASS__, 'install_theme'),
        ));
    }

    private static function ensure_upgrader_files() {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/misc.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        require_once ABSPATH . 'wp-admin/includes/theme.php';
    }

    public static function list_plugins($args) {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $all_plugins = get_plugins();
        $active = get_option('active_plugins', array());

        $items = array();
        foreach ($all_plugins as $plugin_file => $data) {
            $items[] = array(
                'plugin' => $plugin_file,
                'name' => $data['Name'],
                'version' => $data['Version'],
                'active' => in_array($plugin_file, $active, true),
            );
        }

        return array('items' => $items);
    }

    public static function activate_plugin($args) {
        if (empty($args['plugin'])) {
            return new WP_Error('wpmcp_missing_arg', 'Falta la ruta del plugin.');
        }
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $plugin = sanitize_text_field($args['plugin']);
        $installed = get_plugins();
        if (!isset($installed[$plugin])) {
            return new WP_Error('wpmcp_not_found', 'El plugin "' . $plugin . '" no está instalado.');
        }

        $result = activate_plugin($plugin);
        if (is_wp_error($result)) {
            return $result;
        }

        return array('plugin' => $plugin, 'active' => true);
    }

    public static function deactivate_plugin($args) {
        if (empty($args['plugin'])) {
            return new WP_Error('wpmcp_missing_arg', 'Falta la ruta del plugin.');
        }
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $plugin = sanitize_text_field($args['plugin']);
        if (!is_plugin_active($plugin)) {
            return new WP_Error('wpmcp_invalid_state', 'El plugin "' . $plugin . '" ya está inactivo.');
        }

        deactivate_plugins($plugin);
        return array('plugin' => $plugin, 'active' => false);
    }

    public static function install_plugin($args) {
        if (empty($args['slug'])) {
            return new WP_Error('wpmcp_missing_arg', 'Falta el slug del plugin.');
        }
        self::ensure_upgrader_files();

        $slug = sanitize_key($args['slug']);
        $api = plugins_api('plugin_information', array('slug' => $slug, 'fields' => array('sections' => false)));
        if (is_wp_error($api)) {
            return $api;
        }

        $upgrader = new Plugin_Upgrader(new Automatic_Upgrader_Skin());
        $result = $upgrader->install($api->download_link);
        if (is_wp_error($result)) {
            return $result;
        }
        if (!$result) {
            return new WP_Error('wpmcp_install_failed', 'No se pudo instalar el plugin "' . $slug . '". Verifica los permisos de escritura del sistema de archivos.');
        }

        $plugin_file = $upgrader->plugin_info();
        if (!empty($args['activate']) && $plugin_file) {
            $activation = activate_plugin($plugin_file);
            if (is_wp_error($activation)) {
                return $activation;
            }
        }

        return array('slug' => $slug, 'plugin' => $plugin_file, 'activated' => !empty($args['activate']));
    }

    public static function delete_plugin($args) {
        if (empty($args['plugin'])) {
            return new WP_Error('wpmcp_missing_arg', 'Falta la ruta del plugin.');
        }
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        self::ensure_upgrader_files();

        $plugin = sanitize_text_field($args['plugin']);
        if (is_plugin_active($plugin)) {
            return new WP_Error('wpmcp_invalid_state', 'Desactiva el plugin "' . $plugin . '" antes de eliminarlo.');
        }

        $result = delete_plugins(array($plugin));
        if (is_wp_error($result)) {
            return $result;
        }

        return array('plugin' => $plugin, 'deleted' => true);
    }

    public static function list_themes($args) {
        $themes = wp_get_themes();
        $active = get_stylesheet();

        $items = array();
        foreach ($themes as $stylesheet => $theme) {
            $items[] = array(
                'stylesheet' => $stylesheet,
                'name' => $theme->get('Name'),
                'version' => $theme->get('Version'),
                'active' => $stylesheet === $active,
            );
        }

        return array('items' => $items);
    }

    public static function activate_theme($args) {
        if (empty($args['stylesheet'])) {
            return new WP_Error('wpmcp_missing_arg', 'Falta el stylesheet del tema.');
        }
        $stylesheet = sanitize_text_field($args['stylesheet']);
        $theme = wp_get_theme($stylesheet);
        if (!$theme->exists()) {
            return new WP_Error('wpmcp_not_found', 'El tema "' . $stylesheet . '" no está instalado.');
        }

        switch_theme($stylesheet);
        return array('stylesheet' => $stylesheet, 'active' => true);
    }

    public static function install_theme($args) {
        if (empty($args['slug'])) {
            return new WP_Error('wpmcp_missing_arg', 'Falta el slug del tema.');
        }
        self::ensure_upgrader_files();

        $slug = sanitize_key($args['slug']);
        $api = themes_api('theme_information', array('slug' => $slug, 'fields' => array('sections' => false)));
        if (is_wp_error($api)) {
            return $api;
        }

        $upgrader = new Theme_Upgrader(new Automatic_Upgrader_Skin());
        $result = $upgrader->install($api->download_link);
        if (is_wp_error($result)) {
            return $result;
        }
        if (!$result) {
            return new WP_Error('wpmcp_install_failed', 'No se pudo instalar el tema "' . $slug . '". Verifica los permisos de escritura del sistema de archivos.');
        }

        if (!empty($args['activate'])) {
            switch_theme($slug);
        }

        return array('slug' => $slug, 'activated' => !empty($args['activate']));
    }
}
