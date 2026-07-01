# WP MCP Server

> Servidor **MCP (Model Context Protocol)** nativo para WordPress. Conecta Claude u otra IA compatible con MCP directamente a tu sitio para gestionar contenido, medios, plugins, temas, usuarios y base de datos — autenticado con WordPress Application Passwords.

![Versión](https://img.shields.io/badge/versión-1.0.0-blue)
![WordPress](https://img.shields.io/badge/WordPress-5.6%2B-21759b)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4)
![Licencia](https://img.shields.io/badge/licencia-GPLv2-green)

---

## ¿Qué es?

WP MCP Server es un plugin de WordPress que convierte tu instalación en un **servidor MCP** con transporte *Streamable HTTP*. Cualquier cliente compatible con MCP (Claude Desktop, Claude Code, u otros) puede conectarse y usar herramientas para administrar el sitio mediante lenguaje natural.

No requiere dependencias externas, servicios de terceros ni claves de API propias: es PHP plano que corre sobre WordPress y se apoya en su sistema nativo de **Application Passwords** para autenticar y autorizar.

## Características principales

- **31 herramientas** que cubren contenido, medios, taxonomías, comentarios, plugins, temas, usuarios, información del sitio y SQL directo.
- **Autenticación nativa** con WordPress Application Passwords (HTTP Basic). Sin sistema de permisos paralelo.
- **Autorización por capabilities**: cada herramienta exige la capability de WordPress correspondiente; la IA solo puede hacer lo que el usuario dueño de la contraseña puede hacer.
- **Herramientas de base de datos opcionales** (SELECT/INSERT/UPDATE/DELETE directo), **deshabilitadas por defecto** por su alto riesgo.
- **Endpoint JSON-RPC 2.0** único, sin dependencias de librerías MCP externas.
- Página de ajustes en el panel de WordPress con el endpoint listo para copiar y verificación de HTTPS/Application Passwords.

Ver el detalle completo en [Documentacion/CARACTERISTICAS.md](Documentacion/CARACTERISTICAS.md).

## Instalación

1. Descarga `wp-mcp-server.zip` (o comprime la carpeta `wp-mcp-server/`).
2. En WordPress: **Plugins → Añadir nuevo → Subir plugin**, sube el `.zip` y actívalo.
   *Alternativa manual:* copia la carpeta `wp-mcp-server/` a `wp-content/plugins/` y actívalo.
3. Ve a **Ajustes → WP MCP Server** para ver el endpoint y el estado de Application Passwords.

> **Requisito:** Application Passwords requiere HTTPS (o un entorno `local`). Si el sitio no está bajo HTTPS, el plugin lo avisará y la autenticación no funcionará.

## Configuración del cliente MCP

1. En WordPress, ve a **tu perfil de usuario** y genera una nueva **Application Password**.
2. Configura tu cliente MCP apuntando al endpoint como servidor MCP remoto / *Custom Connector* (transporte Streamable HTTP):

   ```
   https://tu-sitio.com/wp-json/wp-mcp/v1/mcp
   ```

3. Autentica con la cabecera HTTP Basic:

   ```
   Authorization: Basic base64(usuario:contraseña_de_aplicación)
   ```

4. Usa un usuario de WordPress con el rol adecuado al alcance que quieras dar a la IA:
   - **Editor** → solo contenido, medios, taxonomías y comentarios.
   - **Administrador** → gestión completa, incluyendo plugins, temas, usuarios y (si se habilitan) base de datos.

### Ejemplo de conexión (cURL)

```bash
curl -X POST https://tu-sitio.com/wp-json/wp-mcp/v1/mcp \
  -u "usuario:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/list","params":{}}'
```

## Endpoint y protocolo

- **Método/ruta:** `POST /wp-json/wp-mcp/v1/mcp`
- **Protocolo:** JSON-RPC 2.0. Métodos soportados: `initialize`, `tools/list`, `tools/call`, `ping`, `resources/list`, `prompts/list` y notifications (`notifications/initialized`, `notifications/cancelled`).
- **Versión de protocolo MCP:** `2025-06-18`.
- **No soporta** batching (lote) ni streaming SSE por GET (un `GET` devuelve `405`). Siempre responde con un único objeto JSON.

## Seguridad

- Toda solicitud debe estar autenticada (`is_user_logged_in()`); de lo contrario devuelve error `-32001` (401).
- Cada herramienta comprueba `current_user_can(<capability>)` antes de ejecutarse. **Los permisos efectivos de la IA son exactamente los del usuario** dueño de la Application Password.
- Las herramientas `wp_db_*` escriben SQL crudo **saltándose los hooks y validaciones de WordPress**. Vienen **deshabilitadas por defecto**, requieren rol Administrador (`manage_options`), bloquean sentencias múltiples y DDL (DROP/ALTER/TRUNCATE/CREATE) y exigen `WHERE` en UPDATE/DELETE salvo confirmación explícita. **No** deben usarse para cambiar contraseñas ni asignar roles.

Ver [Documentacion/COMPATIBILIDAD.md](Documentacion/COMPATIBILIDAD.md) para requisitos de entorno y clientes probados.

## Documentación

- [Documentacion/README.md](Documentacion/README.md) — documentación general del proyecto.
- [Documentacion/CARACTERISTICAS.md](Documentacion/CARACTERISTICAS.md) — referencia completa de las 31 herramientas.
- [Documentacion/COMPATIBILIDAD.md](Documentacion/COMPATIBILIDAD.md) — requisitos de entorno, protocolo y clientes.

## Estructura del repositorio

```
wp-mcp-server/
├── wp-mcp-server.php                 # Plugin principal: hooks, constantes, registro
├── uninstall.php                     # Limpieza de opciones al desinstalar
├── readme.txt                        # Readme estándar de WordPress.org
├── admin/
│   └── class-wpmcp-admin.php         # Página de ajustes (Ajustes → WP MCP Server)
└── includes/
    ├── class-wpmcp-server.php        # Handler REST + JSON-RPC 2.0
    ├── class-wpmcp-tool-registry.php # Registry estático de herramientas
    └── tools/
        ├── class-wpmcp-tools-content.php         # Posts, medios, taxonomías, comentarios
        ├── class-wpmcp-tools-plugins-themes.php  # Plugins y temas
        ├── class-wpmcp-tools-users.php           # Usuarios
        ├── class-wpmcp-tools-site.php            # Info del sitio
        └── class-wpmcp-tools-database.php        # SQL directo (opcional)
```

## Empaquetado

```bash
zip -r wp-mcp-server.zip wp-mcp-server -x '*.DS_Store'
```

## Licencia

GPL v2 o posterior — https://www.gnu.org/licenses/gpl-2.0.html

## Autor

**Joel Chala** — https://coposys.site
