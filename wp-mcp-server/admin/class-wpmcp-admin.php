<?php

if (!defined('ABSPATH')) {
    exit;
}

class WPMCP_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_notices', array($this, 'maybe_show_https_notice'));
    }

    public function add_menu() {
        add_options_page(
            'WP MCP Server',
            'WP MCP Server',
            'manage_options',
            'wp-mcp-server',
            array($this, 'render_page')
        );
    }

    public function register_settings() {
        register_setting('wpmcp_settings', 'wpmcp_enabled', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_enabled'),
            'default' => '1',
        ));
        register_setting('wpmcp_settings', 'wpmcp_enable_db_tools', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_enabled'),
            'default' => '0',
        ));
    }

    public function sanitize_enabled($value) {
        return ('1' === $value) ? '1' : '0';
    }

    public function maybe_show_https_notice() {
        $screen = get_current_screen();
        if (!$screen || 'settings_page_wp-mcp-server' !== $screen->id) {
            return;
        }
        if (!wp_is_application_passwords_available()) {
            echo '<div class="notice notice-error"><p><strong>WP MCP Server:</strong> Las Application Passwords de WordPress no están disponibles en este sitio (normalmente requieren HTTPS). El servidor MCP no podrá autenticar solicitudes hasta que se habiliten.</p></div>';
        }
    }

    public function render_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $endpoint = rest_url(WPMCP_Server::NAMESPACE_ . WPMCP_Server::ROUTE);
        $app_passwords_available = wp_is_application_passwords_available();
        $profile_url = admin_url('profile.php#application-passwords-section');
        $tools_count = count(WPMCP_Tool_Registry::get_all());
        ?>
        <div class="wrap">
            <h1>WP MCP Server</h1>
            <p>Servidor MCP (Model Context Protocol) que permite conectar Claude u otra IA compatible para administrar este sitio WordPress mediante herramientas autenticadas.</p>

            <form method="post" action="options.php">
                <?php settings_fields('wpmcp_settings'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">Estado</th>
                        <td>
                            <label>
                                <input type="hidden" name="wpmcp_enabled" value="0" />
                                <input type="checkbox" name="wpmcp_enabled" value="1" <?php checked(wpmcp_is_enabled()); ?> />
                                Habilitar el servidor MCP
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Endpoint MCP</th>
                        <td>
                            <input type="text" readonly="readonly" class="regular-text code" value="<?php echo esc_attr($endpoint); ?>" onclick="this.select();" style="width: 100%; max-width: 560px;" />
                            <p class="description">Configura este endpoint como "Custom Connector" / servidor MCP remoto (transporte Streamable HTTP) en Claude Desktop, Claude Code u otro cliente MCP.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Autenticación</th>
                        <td>
                            <?php if ($app_passwords_available) : ?>
                                <p style="color:#1a7f37;">✔ WordPress Application Passwords disponibles en este sitio.</p>
                            <?php else : ?>
                                <p style="color:#b32d2e;">✘ WordPress Application Passwords no disponibles (requiere HTTPS).</p>
                            <?php endif; ?>
                            <p class="description">
                                Genera una contraseña de aplicación desde
                                <a href="<?php echo esc_url($profile_url); ?>">tu perfil de usuario</a>
                                y úsala como <code>Authorization: Basic base64(usuario:contraseña_de_aplicación)</code>.
                                Los permisos que tendrá la IA son exactamente los del usuario de WordPress dueño de esa contraseña —
                                usa un usuario con el rol adecuado (Editor para solo contenido, Administrador para gestionar plugins/temas/usuarios).
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Herramientas registradas</th>
                        <td><?php echo esc_html($tools_count); ?> herramientas disponibles (contenido, medios, taxonomías, comentarios, plugins, temas, usuarios, información del sitio<?php echo wpmcp_db_tools_enabled() ? ', base de datos' : ''; ?>).</td>
                    </tr>
                    <tr>
                        <th scope="row">Acceso SQL directo</th>
                        <td>
                            <label>
                                <input type="hidden" name="wpmcp_enable_db_tools" value="0" />
                                <input type="checkbox" name="wpmcp_enable_db_tools" value="1" <?php checked(wpmcp_db_tools_enabled()); ?> />
                                Habilitar herramientas de base de datos (wp_db_query, wp_db_execute, etc.)
                            </label>
                            <p class="description" style="color:#b32d2e;">
                                ⚠ Riesgo alto: estas herramientas permiten ejecutar SELECT/INSERT/UPDATE/DELETE directos sobre la base de datos,
                                sin pasar por los hooks y validaciones normales de WordPress. Solo un usuario con el rol Administrador
                                (capability <code>manage_options</code>) puede usarlas. Actívalas únicamente si confías en el cliente MCP
                                que se va a conectar. Por seguridad vienen deshabilitadas por defecto.
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Guardar cambios'); ?>
            </form>
        </div>
        <?php
    }
}
