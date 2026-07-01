<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Acceso SQL directo a la base de datos de WordPress.
 * Estas herramientas se registran solo si el admin habilita explícitamente
 * "wpmcp_enable_db_tools" desde Ajustes > WP MCP Server, dado su alto riesgo.
 */
class WPMCP_Tools_Database {

    const MAX_ROWS = 200;

    public static function register() {
        WPMCP_Tool_Registry::register('wp_db_list_tables', array(
            'description' => 'Lista todas las tablas de la base de datos de WordPress.',
            'capability' => 'manage_options',
            'inputSchema' => array('type' => 'object', 'properties' => new stdClass()),
            'callback' => array(__CLASS__, 'list_tables'),
        ));

        WPMCP_Tool_Registry::register('wp_db_describe_table', array(
            'description' => 'Muestra la estructura (columnas, tipos, claves) de una tabla de la base de datos.',
            'capability' => 'manage_options',
            'inputSchema' => array(
                'type' => 'object',
                'properties' => array(
                    'table' => array('type' => 'string', 'description' => 'Nombre exacto de la tabla, ej: "wp_posts".'),
                ),
                'required' => array('table'),
            ),
            'callback' => array(__CLASS__, 'describe_table'),
        ));

        WPMCP_Tool_Registry::register('wp_db_query', array(
            'description' => 'Ejecuta una consulta SQL de solo lectura (SELECT) contra la base de datos y devuelve las filas. Se limita automáticamente a ' . self::MAX_ROWS . ' filas si la consulta no trae su propio LIMIT. No se permiten sentencias múltiples ni escrituras a archivos.',
            'capability' => 'manage_options',
            'inputSchema' => array(
                'type' => 'object',
                'properties' => array(
                    'sql' => array('type' => 'string', 'description' => 'Sentencia SQL que debe comenzar con SELECT.'),
                ),
                'required' => array('sql'),
            ),
            'callback' => array(__CLASS__, 'query'),
        ));

        WPMCP_Tool_Registry::register('wp_db_execute', array(
            'description' => 'Ejecuta una sentencia SQL de escritura (INSERT, UPDATE o DELETE) contra la base de datos. UPDATE y DELETE requieren una cláusula WHERE, salvo que se pase confirm_no_where=true de forma explícita. No se permiten sentencias múltiples ni DDL (DROP/ALTER/TRUNCATE/CREATE).',
            'capability' => 'manage_options',
            'inputSchema' => array(
                'type' => 'object',
                'properties' => array(
                    'sql' => array('type' => 'string', 'description' => 'Sentencia SQL que debe comenzar con INSERT, UPDATE o DELETE.'),
                    'confirm_no_where' => array('type' => 'boolean', 'description' => 'Debe ser true para permitir un UPDATE/DELETE sin cláusula WHERE (afecta la tabla completa). Úsalo con extrema precaución.'),
                ),
                'required' => array('sql'),
            ),
            'callback' => array(__CLASS__, 'execute'),
        ));
    }

    private static function existing_tables() {
        global $wpdb;
        return $wpdb->get_col('SHOW TABLES');
    }

    private static function strip_trailing_semicolon($sql) {
        return rtrim(rtrim(trim($sql)), ';');
    }

    private static function has_multiple_statements($sql) {
        return false !== strpos(self::strip_trailing_semicolon($sql), ';');
    }

    public static function list_tables($args) {
        return array('tables' => self::existing_tables());
    }

    public static function describe_table($args) {
        if (empty($args['table'])) {
            return new WP_Error('wpmcp_missing_arg', 'Falta el nombre de la tabla.');
        }
        global $wpdb;
        $table = $args['table'];

        if (!in_array($table, self::existing_tables(), true)) {
            return new WP_Error('wpmcp_not_found', 'La tabla "' . $table . '" no existe en esta base de datos.');
        }

        $columns = $wpdb->get_results('DESCRIBE `' . str_replace('`', '', $table) . '`', ARRAY_A);
        return array('table' => $table, 'columns' => $columns);
    }

    public static function query($args) {
        if (empty($args['sql'])) {
            return new WP_Error('wpmcp_missing_arg', 'Falta la sentencia SQL.');
        }
        global $wpdb;
        $sql = trim($args['sql']);

        if (!preg_match('/^SELECT\s+/i', $sql)) {
            return new WP_Error('wpmcp_invalid_sql', 'Solo se permiten sentencias que comiencen con SELECT en wp_db_query. Usa wp_db_execute para INSERT/UPDATE/DELETE.');
        }
        if (self::has_multiple_statements($sql)) {
            return new WP_Error('wpmcp_invalid_sql', 'No se permiten múltiples sentencias SQL separadas por ";".');
        }
        if (preg_match('/\bINTO\s+(OUTFILE|DUMPFILE)\b/i', $sql)) {
            return new WP_Error('wpmcp_invalid_sql', 'No se permite SELECT ... INTO OUTFILE/DUMPFILE.');
        }

        if (!preg_match('/\bLIMIT\s+\d+/i', $sql)) {
            $sql = self::strip_trailing_semicolon($sql) . ' LIMIT ' . self::MAX_ROWS;
        }

        $rows = $wpdb->get_results($sql, ARRAY_A);
        if (null === $rows && $wpdb->last_error) {
            return new WP_Error('wpmcp_sql_error', $wpdb->last_error);
        }

        return array('row_count' => count($rows), 'rows' => $rows);
    }

    public static function execute($args) {
        if (empty($args['sql'])) {
            return new WP_Error('wpmcp_missing_arg', 'Falta la sentencia SQL.');
        }
        global $wpdb;
        $sql = trim($args['sql']);

        if (!preg_match('/^(INSERT|UPDATE|DELETE)\s+/i', $sql, $matches)) {
            return new WP_Error('wpmcp_invalid_sql', 'Solo se permiten sentencias que comiencen con INSERT, UPDATE o DELETE en wp_db_execute.');
        }
        $verb = strtoupper($matches[1]);

        if (self::has_multiple_statements($sql)) {
            return new WP_Error('wpmcp_invalid_sql', 'No se permiten múltiples sentencias SQL separadas por ";".');
        }

        if (in_array($verb, array('UPDATE', 'DELETE'), true) && !preg_match('/\bWHERE\b/i', $sql) && empty($args['confirm_no_where'])) {
            return new WP_Error(
                'wpmcp_missing_where',
                'La sentencia ' . $verb . ' no tiene cláusula WHERE, lo que afectaría la tabla completa. Añade un WHERE o pasa confirm_no_where=true si realmente lo deseas.'
            );
        }

        $result = $wpdb->query($sql);
        if (false === $result) {
            return new WP_Error('wpmcp_sql_error', $wpdb->last_error ? $wpdb->last_error : 'La consulta falló.');
        }

        $response = array('affected_rows' => (int) $result, 'verb' => $verb);
        if ('INSERT' === $verb) {
            $response['insert_id'] = $wpdb->insert_id;
        }

        return $response;
    }
}
