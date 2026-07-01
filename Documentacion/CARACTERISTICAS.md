# Características de WP MCP Server

Documento de referencia de todas las herramientas (tools) MCP expuestas por el plugin, su capability de WordPress requerida y su comportamiento.

## Resumen

| Categoría | Herramientas | Rol mínimo típico |
|---|---|---|
| Contenido, medios, taxonomías, comentarios | 13 | Autor / Editor |
| Plugins y temas | 8 | Administrador |
| Usuarios | 5 | Administrador |
| Información del sitio | 1 | Administrador |
| Base de datos (opcional, desactivado por defecto) | 4 | Administrador |
| **Total** | **31** | — |

La autorización es **por capability de WordPress**, no por rol. Los roles se mencionan solo como referencia orientativa del rol estándar que incluye esa capability.

---

## Contenido y medios (`class-wpmcp-tools-content.php`)

| Herramienta | Capability | Descripción |
|---|---|---|
| `wp_create_post` | `edit_posts` | Crea un post, página u otro tipo de contenido. Publicar (`status=publish`) exige además `publish_posts`; si no, se limita a `draft`/`pending`. El autor se fija al usuario autenticado. Sanitiza título, contenido (`wp_kses_post`) y extracto. |
| `wp_update_post` | `edit_posts` | Actualiza un post existente por ID (título, contenido, extracto, estado, categorías, etiquetas). |
| `wp_delete_post` | `edit_posts` | Envía a la papelera, o borra permanentemente con `force=true`. |
| `wp_get_posts` | `edit_posts` | Busca y lista posts con filtros. **Restringido al autor actual** salvo que el usuario tenga `edit_others_posts`. Máx. 50 por página. |
| `wp_get_post` | `edit_posts` | Detalle completo de un post por ID. **Restringido al autor actual** salvo `edit_others_posts`. |
| `wp_upload_media` | `upload_files` | Descarga un archivo desde una URL pública y lo importa a la biblioteca. Si se pasa `post_id`, valida `edit_post` sobre ese post antes de asociar. |
| `wp_list_media` | `upload_files` | Lista archivos de la biblioteca multimedia. |
| `wp_delete_media` | `upload_files` | Elimina un adjunto por ID. |
| `wp_list_terms` | `manage_categories` | Lista términos de una taxonomía (por defecto `category`). |
| `wp_create_term` | `manage_categories` | Crea una categoría, etiqueta u otro término. |
| `wp_delete_term` | `manage_categories` | Elimina un término por ID. |
| `wp_list_comments` | `moderate_comments` | Lista comentarios con filtros de estado o post. |
| `wp_moderate_comment` | `moderate_comments` | Aprueba, retiene, marca como spam, envía a papelera o elimina un comentario. |

## Plugins y temas (`class-wpmcp-tools-plugins-themes.php`)

| Herramienta | Capability | Descripción |
|---|---|---|
| `wp_list_plugins` | `activate_plugins` | Lista los plugins instalados y su estado. |
| `wp_activate_plugin` | `activate_plugins` | Activa un plugin. |
| `wp_deactivate_plugin` | `activate_plugins` | Desactiva un plugin. |
| `wp_install_plugin` | `install_plugins` | Instala un plugin (desde el repositorio de WordPress.org o similar). |
| `wp_delete_plugin` | `delete_plugins` | Elimina un plugin. |
| `wp_list_themes` | `switch_themes` | Lista los temas instalados. |
| `wp_activate_theme` | `switch_themes` | Activa (cambia a) un tema. |
| `wp_install_theme` | `install_themes` | Instala un tema. |

## Usuarios (`class-wpmcp-tools-users.php`)

| Herramienta | Capability | Descripción |
|---|---|---|
| `wp_list_users` | `list_users` | Lista los usuarios del sitio. |
| `wp_get_user` | `list_users` | Detalle de un usuario. |
| `wp_create_user` | `create_users` | Crea un usuario nuevo. |
| `wp_update_user` | `edit_users` | Actualiza un usuario. Respeta las reglas de promoción de roles/capabilities de WordPress. |
| `wp_delete_user` | `delete_users` | Elimina un usuario. |

## Información del sitio (`class-wpmcp-tools-site.php`)

| Herramienta | Capability | Descripción |
|---|---|---|
| `wp_get_site_info` | `manage_options` | Nombre, descripción, URL, versión de WordPress y PHP, tema activo, número de plugins/usuarios, y si es multisitio. |

## Base de datos — SQL directo (`class-wpmcp-tools-database.php`)

> ⚠️ **Alto riesgo. Deshabilitadas por defecto.** Se registran solo si se activa la opción `wpmcp_enable_db_tools` en **Ajustes → WP MCP Server**. Todas requieren `manage_options` (Administrador) y escriben/leen **saltándose los hooks y validaciones de WordPress**.

| Herramienta | Capability | Descripción y salvaguardas |
|---|---|---|
| `wp_db_list_tables` | `manage_options` | Lista todas las tablas de la base de datos. |
| `wp_db_describe_table` | `manage_options` | Estructura (columnas, tipos, claves) de una tabla existente. |
| `wp_db_query` | `manage_options` | Solo `SELECT`. Bloquea sentencias múltiples y `INTO OUTFILE/DUMPFILE`. Añade `LIMIT 200` automáticamente si la consulta no trae su propio `LIMIT`. |
| `wp_db_execute` | `manage_options` | `INSERT`/`UPDATE`/`DELETE`. Bloquea sentencias múltiples y DDL (`DROP`/`ALTER`/`TRUNCATE`/`CREATE`). `UPDATE`/`DELETE` exigen `WHERE` salvo `confirm_no_where=true`. **No** usar para cambiar contraseñas (rompería el hash y el login) ni para asignar roles/capabilities (usar `wp_update_user`). |

---

## Formato de respuesta

Cada llamada `tools/call` devuelve un objeto MCP estándar:

- **Éxito:** `{ "content": [{ "type": "text", "text": "<JSON o texto>" }], "isError": false }`. Los resultados estructurados se serializan a JSON con formato legible y sin escapar Unicode.
- **Error de negocio** (permiso denegado, argumento inválido, `WP_Error`): `isError: true` con el mensaje descriptivo, manteniendo el estado HTTP 200 a nivel JSON-RPC.
- **Error de protocolo** (JSON inválido, método desconocido, no autenticado): respuesta de error JSON-RPC con su `code` correspondiente (`-32700`, `-32600`, `-32601`, `-32602`, `-32001`, `-32003`).

## Extensibilidad

- Tras registrar las herramientas integradas se dispara la acción `do_action('wpmcp_register_tools')`, punto de enganche para que otros plugins registren herramientas propias con `WPMCP_Tool_Registry::register($name, $args)`.
- El servidor completo puede deshabilitarse con la opción `wpmcp_enabled` (por defecto activado) sin desactivar el plugin.
