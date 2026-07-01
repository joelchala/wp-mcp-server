<?php

if (!defined('ABSPATH')) {
    exit;
}

class WPMCP_Tool_Registry {

    private static $tools = array();

    public static function register($name, $args) {
        $defaults = array(
            'description' => '',
            'inputSchema' => array('type' => 'object', 'properties' => new stdClass()),
            'capability' => '',
            'callback' => null,
        );
        self::$tools[$name] = wp_parse_args($args, $defaults);
    }

    public static function get_all() {
        return self::$tools;
    }

    public static function get($name) {
        return isset(self::$tools[$name]) ? self::$tools[$name] : null;
    }

    public static function reset() {
        self::$tools = array();
    }
}
