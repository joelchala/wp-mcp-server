<?php

if (!defined('ABSPATH')) {
    exit;
}

class WPMCP_Tools_Users {

    public static function register() {
        WPMCP_Tool_Registry::register('wp_list_users', array(
            'description' => 'Lista los usuarios del sitio.',
            'capability' => 'list_users',
            'inputSchema' => array(
                'type' => 'object',
                'properties' => array(
                    'search' => array('type' => 'string'),
                    'role' => array('type' => 'string'),
                    'per_page' => array('type' => 'integer'),
                ),
            ),
            'callback' => array(__CLASS__, 'list_users'),
        ));

        WPMCP_Tool_Registry::register('wp_get_user', array(
            'description' => 'Obtiene el detalle de un usuario por ID.',
            'capability' => 'list_users',
            'inputSchema' => array(
                'type' => 'object',
                'properties' => array('id' => array('type' => 'integer')),
                'required' => array('id'),
            ),
            'callback' => array(__CLASS__, 'get_user'),
        ));

        WPMCP_Tool_Registry::register('wp_create_user', array(
            'description' => 'Crea un nuevo usuario de WordPress.',
            'capability' => 'create_users',
            'inputSchema' => array(
                'type' => 'object',
                'properties' => array(
                    'username' => array('type' => 'string'),
                    'email' => array('type' => 'string'),
                    'password' => array('type' => 'string', 'description' => 'Opcional. Si se omite, WordPress genera una contraseña aleatoria.'),
                    'role' => array('type' => 'string', 'description' => 'Por defecto el rol predeterminado del sitio.'),
                ),
                'required' => array('username', 'email'),
            ),
            'callback' => array(__CLASS__, 'create_user'),
        ));

        WPMCP_Tool_Registry::register('wp_update_user', array(
            'description' => 'Actualiza datos de un usuario existente, incluido su rol.',
            'capability' => 'edit_users',
            'inputSchema' => array(
                'type' => 'object',
                'properties' => array(
                    'id' => array('type' => 'integer'),
                    'email' => array('type' => 'string'),
                    'display_name' => array('type' => 'string'),
                    'role' => array('type' => 'string'),
                    'password' => array('type' => 'string'),
                ),
                'required' => array('id'),
            ),
            'callback' => array(__CLASS__, 'update_user'),
        ));

        WPMCP_Tool_Registry::register('wp_delete_user', array(
            'description' => 'Elimina un usuario, reasignando opcionalmente su contenido a otro usuario.',
            'capability' => 'delete_users',
            'inputSchema' => array(
                'type' => 'object',
                'properties' => array(
                    'id' => array('type' => 'integer'),
                    'reassign' => array('type' => 'integer', 'description' => 'ID del usuario al que reasignar el contenido (opcional).'),
                ),
                'required' => array('id'),
            ),
            'callback' => array(__CLASS__, 'delete_user'),
        ));
    }

    public static function list_users($args) {
        $query_args = array(
            'number' => isset($args['per_page']) ? min(50, max(1, absint($args['per_page']))) : 20,
        );
        if (!empty($args['search'])) {
            $query_args['search'] = '*' . sanitize_text_field($args['search']) . '*';
        }
        if (!empty($args['role'])) {
            $query_args['role'] = sanitize_key($args['role']);
        }

        $users = get_users($query_args);
        $items = array();
        foreach ($users as $user) {
            $items[] = self::format_user($user);
        }
        return array('items' => $items);
    }

    public static function get_user($args) {
        if (empty($args['id'])) {
            return new WP_Error('wpmcp_missing_arg', 'Falta el ID del usuario.');
        }
        $user = get_user_by('id', absint($args['id']));
        if (!$user) {
            return new WP_Error('wpmcp_not_found', 'No existe un usuario con ID ' . absint($args['id']) . '.');
        }
        return self::format_user($user);
    }

    private static function format_user($user) {
        return array(
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'display_name' => $user->display_name,
            'roles' => $user->roles,
            'registered' => $user->user_registered,
        );
    }

    public static function create_user($args) {
        if (empty($args['username']) || empty($args['email'])) {
            return new WP_Error('wpmcp_missing_arg', 'Faltan "username" y/o "email".');
        }
        if (!is_email($args['email'])) {
            return new WP_Error('wpmcp_invalid_arg', 'El email proporcionado no es válido.');
        }

        $role = !empty($args['role']) ? sanitize_key($args['role']) : get_option('default_role');
        if (!empty($args['role']) && !current_user_can('promote_users')) {
            return new WP_Error('wpmcp_forbidden', 'No tienes permiso para asignar roles a usuarios nuevos.');
        }
        if (!get_role($role)) {
            return new WP_Error('wpmcp_invalid_arg', 'El rol "' . $role . '" no existe.');
        }

        $userdata = array(
            'user_login' => sanitize_user($args['username']),
            'user_email' => sanitize_email($args['email']),
            'user_pass' => !empty($args['password']) ? $args['password'] : wp_generate_password(16),
            'role' => $role,
        );

        $user_id = wp_insert_user($userdata);
        if (is_wp_error($user_id)) {
            return $user_id;
        }

        return self::format_user(get_user_by('id', $user_id));
    }

    public static function update_user($args) {
        if (empty($args['id'])) {
            return new WP_Error('wpmcp_missing_arg', 'Falta el ID del usuario.');
        }
        $id = absint($args['id']);
        $user = get_user_by('id', $id);
        if (!$user) {
            return new WP_Error('wpmcp_not_found', 'No existe un usuario con ID ' . $id . '.');
        }
        if (!current_user_can('edit_user', $id)) {
            return new WP_Error('wpmcp_forbidden', 'No tienes permiso para editar este usuario.');
        }

        $userdata = array('ID' => $id);
        if (isset($args['email'])) {
            if (!is_email($args['email'])) {
                return new WP_Error('wpmcp_invalid_arg', 'El email proporcionado no es válido.');
            }
            $userdata['user_email'] = sanitize_email($args['email']);
        }
        if (isset($args['display_name'])) {
            $userdata['display_name'] = sanitize_text_field($args['display_name']);
        }
        if (isset($args['password'])) {
            $userdata['user_pass'] = $args['password'];
        }
        if (isset($args['role'])) {
            if (!current_user_can('promote_user', $id)) {
                return new WP_Error('wpmcp_forbidden', 'No tienes permiso para cambiar el rol de este usuario.');
            }
            $role = sanitize_key($args['role']);
            if (!get_role($role)) {
                return new WP_Error('wpmcp_invalid_arg', 'El rol "' . $role . '" no existe.');
            }
            $userdata['role'] = $role;
        }

        $result = wp_update_user($userdata);
        if (is_wp_error($result)) {
            return $result;
        }

        return self::format_user(get_user_by('id', $id));
    }

    public static function delete_user($args) {
        if (empty($args['id'])) {
            return new WP_Error('wpmcp_missing_arg', 'Falta el ID del usuario.');
        }
        $id = absint($args['id']);
        if ($id === get_current_user_id()) {
            return new WP_Error('wpmcp_invalid_state', 'No puedes eliminar el usuario con el que estás autenticado.');
        }
        if (!get_user_by('id', $id)) {
            return new WP_Error('wpmcp_not_found', 'No existe un usuario con ID ' . $id . '.');
        }
        if (!current_user_can('delete_user', $id)) {
            return new WP_Error('wpmcp_forbidden', 'No tienes permiso para eliminar este usuario.');
        }

        if (!function_exists('wp_delete_user')) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }

        $reassign = !empty($args['reassign']) ? absint($args['reassign']) : null;
        $result = wp_delete_user($id, $reassign);
        if (!$result) {
            return new WP_Error('wpmcp_delete_failed', 'No se pudo eliminar el usuario ' . $id . '.');
        }

        return array('deleted' => true, 'id' => $id);
    }
}
