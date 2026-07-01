# Compatibilidad

## Requisitos del entorno

| Componente | Requisito | Notas |
|---|---|---|
| WordPress | **5.6 o superior** | Application Passwords se incorporó al núcleo en WP 5.6. |
| PHP | **7.4 o superior** | Sin dependencias de Composer ni extensiones adicionales fuera del stack estándar de WordPress. |
| Probado hasta | WordPress **6.7** | Ver `readme.txt` (`Tested up to`). |
| Servidor web | Cualquiera compatible con la REST API de WordPress | Apache, Nginx, LiteSpeed, etc. |
| Base de datos | MySQL / MariaDB (la del propio WordPress) | Las herramientas `wp_db_*` operan sobre `$wpdb`. |

## HTTPS y Application Passwords

- **La autenticación exige HTTPS.** WordPress solo habilita Application Passwords sobre conexiones seguras, salvo en el entorno de tipo `local`.
- Si el sitio no cumple este requisito, el plugin muestra un aviso en su página de ajustes y el endpoint MCP no podrá autenticar solicitudes.
- El plugin comprueba la disponibilidad con `wp_is_application_passwords_available()`.

## Multisitio

- El plugin no depende de funciones que rompan en multisitio y `wp_get_site_info` reporta `multisite: true/false`.
- No incluye gestión específica de red (network admin); se comporta a nivel de sitio individual según el usuario autenticado.

## Compatibilidad de protocolo MCP

| Aspecto | Soporte |
|---|---|
| Versión de protocolo | `2025-06-18` (negocia la solicitada por el cliente en `initialize`). |
| Transporte | **Streamable HTTP** (un único `POST` con JSON-RPC 2.0). |
| Métodos | `initialize`, `tools/list`, `tools/call`, `ping`, `resources/list`, `prompts/list`, notifications. |
| Capacidades anunciadas | `tools`. No expone `resources` ni `prompts` (devuelven listas vacías). |
| **No** soportado | Solicitudes en lote (batch), streaming SSE por `GET`. |

## Clientes MCP compatibles

Cualquier cliente que soporte servidores MCP remotos con transporte Streamable HTTP y autenticación HTTP Basic, entre ellos:

- **Claude Desktop** (Custom Connector / servidor MCP remoto)
- **Claude Code**
- Otros clientes MCP que permitan configurar cabeceras `Authorization`.

## Consideraciones de despliegue

- **Sin build ni dependencias:** se instala como cualquier plugin de WordPress; no requiere `npm`, `composer` ni procesos de compilación.
- **Reglas de reescritura:** al activar/desactivar se ejecuta `flush_rewrite_rules()` para garantizar que la ruta REST quede registrada.
- **Desinstalación:** `uninstall.php` elimina las opciones (`wpmcp_enabled`, `wpmcp_enable_db_tools`) al borrar el plugin.
- **Despliegue remoto:** puede subirse a un hosting con el flujo estándar de plugins o, en Hostinger, mediante la herramienta `hosting_deployWordpressPlugin` del MCP de Hostinger.
