<?php

if (!defined('ABSPATH')) {
    exit;
}

class WPMCP_Server {

    const NAMESPACE_ = 'wp-mcp/v1';
    const ROUTE = '/mcp';
    const PROTOCOL_VERSION = '2025-06-18';

    public function register_routes() {
        register_rest_route(self::NAMESPACE_, self::ROUTE, array(
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'handle_post'),
                'permission_callback' => '__return_true',
            ),
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'handle_get'),
                'permission_callback' => '__return_true',
            ),
        ));
    }

    public function handle_get(WP_REST_Request $request) {
        return new WP_REST_Response(
            array('error' => 'Este servidor MCP no soporta streaming SSE via GET. Usa POST con mensajes JSON-RPC 2.0.'),
            405,
            array('Allow' => 'POST')
        );
    }

    public function handle_post(WP_REST_Request $request) {
        if (!wpmcp_is_enabled()) {
            return $this->error_response(null, -32003, 'El servidor MCP está deshabilitado en este sitio.', 503);
        }

        $body = $request->get_json_params();
        if (null === $body || !is_array($body)) {
            return $this->error_response(null, -32700, 'Parse error: el cuerpo no es JSON válido.');
        }

        if (array_key_exists(0, $body)) {
            return $this->error_response(null, -32600, 'Las solicitudes en lote (batch) no están soportadas.');
        }

        $id = array_key_exists('id', $body) ? $body['id'] : null;
        $method = isset($body['method']) && is_string($body['method']) ? $body['method'] : '';
        $params = isset($body['params']) && is_array($body['params']) ? $body['params'] : array();
        $is_notification = (null === $id) && (0 === strpos($method, 'notifications/'));

        if (!isset($body['jsonrpc']) || '2.0' !== $body['jsonrpc'] || '' === $method) {
            return $this->error_response($id, -32600, 'Invalid Request.');
        }

        if (!is_user_logged_in()) {
            if ($is_notification) {
                return new WP_REST_Response(null, 202);
            }
            return $this->error_response(
                $id,
                -32001,
                'No autenticado. Usa WordPress Application Passwords (cabecera Authorization: Basic usuario:contraseña_de_aplicación).',
                401
            );
        }

        switch ($method) {
            case 'initialize':
                return $this->success_response($id, $this->handle_initialize($params));

            case 'notifications/initialized':
            case 'notifications/cancelled':
                return new WP_REST_Response(null, 202);

            case 'ping':
                return $this->success_response($id, new stdClass());

            case 'tools/list':
                return $this->success_response($id, $this->handle_tools_list());

            case 'tools/call':
                return $this->handle_tools_call($id, $params);

            case 'resources/list':
                return $this->success_response($id, array('resources' => array()));

            case 'prompts/list':
                return $this->success_response($id, array('prompts' => array()));

            default:
                if ($is_notification) {
                    return new WP_REST_Response(null, 202);
                }
                return $this->error_response($id, -32601, 'Método no encontrado: ' . $method);
        }
    }

    private function handle_initialize($params) {
        $requested_version = isset($params['protocolVersion']) ? $params['protocolVersion'] : self::PROTOCOL_VERSION;
        return array(
            'protocolVersion' => $requested_version,
            'capabilities' => array(
                'tools' => new stdClass(),
            ),
            'serverInfo' => array(
                'name' => 'WordPress MCP Server',
                'version' => WPMCP_VERSION,
            ),
            'instructions' => 'Servidor MCP para administrar este sitio WordPress (' . home_url() . '). Los permisos disponibles dependen del usuario de WordPress autenticado mediante Application Passwords: usuario "' . wp_get_current_user()->user_login . '".',
        );
    }

    private function handle_tools_list() {
        $tools = WPMCP_Tool_Registry::get_all();
        $list = array();
        foreach ($tools as $name => $tool) {
            $list[] = array(
                'name' => $name,
                'description' => $tool['description'],
                'inputSchema' => $tool['inputSchema'],
            );
        }
        return array('tools' => $list);
    }

    private function handle_tools_call($id, $params) {
        $name = isset($params['name']) && is_string($params['name']) ? $params['name'] : '';
        $arguments = isset($params['arguments']) && is_array($params['arguments']) ? $params['arguments'] : array();

        $tool = WPMCP_Tool_Registry::get($name);
        if (!$tool || !is_callable($tool['callback'])) {
            return $this->error_response($id, -32602, 'Herramienta desconocida: ' . $name);
        }

        if (!empty($tool['capability']) && !current_user_can($tool['capability'])) {
            return $this->success_response($id, self::tool_error(sprintf(
                'Permiso denegado. El usuario "%s" no tiene la capacidad requerida "%s".',
                wp_get_current_user()->user_login,
                $tool['capability']
            )));
        }

        try {
            $result = call_user_func($tool['callback'], $arguments);
            if (is_wp_error($result)) {
                return $this->success_response($id, self::tool_error($result->get_error_message()));
            }
            return $this->success_response($id, self::tool_success($result));
        } catch (Throwable $e) {
            return $this->success_response($id, self::tool_error('Error interno: ' . $e->getMessage()));
        }
    }

    public static function tool_success($data) {
        $text = is_string($data) ? $data : wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return array(
            'content' => array(array('type' => 'text', 'text' => $text)),
            'isError' => false,
        );
    }

    public static function tool_error($message) {
        return array(
            'content' => array(array('type' => 'text', 'text' => $message)),
            'isError' => true,
        );
    }

    private function success_response($id, $result) {
        return new WP_REST_Response(array('jsonrpc' => '2.0', 'id' => $id, 'result' => $result), 200);
    }

    private function error_response($id, $code, $message, $status = 200) {
        return new WP_REST_Response(array(
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => array('code' => $code, 'message' => $message),
        ), $status);
    }
}
