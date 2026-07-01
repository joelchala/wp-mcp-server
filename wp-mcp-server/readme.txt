=== WP MCP Server ===
Contributors: joelchala
Tags: mcp, ai, claude, rest-api, automation
Requires at least: 5.6
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Servidor MCP (Model Context Protocol) nativo para WordPress. Permite conectar Claude u otra IA compatible con MCP para gestionar contenido, plugins, temas y usuarios.

== Description ==

Este plugin convierte tu instalación de WordPress en un servidor MCP (transporte Streamable HTTP), autenticado con WordPress Application Passwords. Cualquier cliente compatible con MCP (Claude Desktop, Claude Code, u otros) puede conectarse y usar herramientas para:

* Crear, editar, listar y eliminar posts/páginas
* Subir y gestionar medios
* Gestionar categorías, etiquetas y comentarios
* Instalar, activar y desactivar plugins y temas
* Gestionar usuarios
* (Opcional, deshabilitado por defecto) Ejecutar SQL directo de lectura y escritura sobre la base de datos

= Autenticación =

La autenticación usa el sistema nativo de **WordPress Application Passwords**. Los permisos que tendrá la IA son exactamente los del usuario de WordPress dueño de la contraseña de aplicación usada — no hay un sistema de permisos independiente. Application Passwords requiere HTTPS (o entorno `local`).

Para conectar:

1. Ve a tu perfil de usuario en WordPress y genera una nueva "Application Password".
2. Configura tu cliente MCP para apostar al endpoint `https://tu-sitio.com/wp-json/wp-mcp/v1/mcp` usando el transporte Streamable HTTP, enviando la cabecera `Authorization: Basic base64(usuario:contraseña_de_aplicación)`.
3. Usa un usuario de WordPress con el rol adecuado al alcance que quieras dar a la IA (Editor para solo contenido, Administrador para gestión completa incluyendo plugins/temas/usuarios).

= Herramientas de base de datos =

Las herramientas `wp_db_list_tables`, `wp_db_describe_table`, `wp_db_query` (SELECT) y `wp_db_execute` (INSERT/UPDATE/DELETE) dan acceso SQL directo a la base de datos, sin pasar por los hooks de WordPress. Están deshabilitadas por defecto y deben activarse explícitamente en Ajustes → WP MCP Server. Siempre requieren la capability `manage_options` (Administrador), bloquean sentencias múltiples y DDL (DROP/ALTER/TRUNCATE/CREATE), y exigen cláusula WHERE en UPDATE/DELETE salvo que se confirme explícitamente con `confirm_no_where=true`.

= Endpoint =

`POST /wp-json/wp-mcp/v1/mcp`

Acepta mensajes JSON-RPC 2.0 (`initialize`, `tools/list`, `tools/call`, etc). No soporta batching ni streaming SSE por GET — responde siempre con un único objeto JSON.

== Installation ==

1. Sube la carpeta `wp-mcp-server` a `/wp-content/plugins/`.
2. Activa el plugin desde el panel de administración.
3. Ve a Ajustes → WP MCP Server para ver el endpoint y el estado de Application Passwords.

== Changelog ==

= 1.0.0 =
* Versión inicial.
