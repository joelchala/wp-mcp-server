<?php

if (!defined('ABSPATH')) {
    exit;
}

class WPMCP_Tools_Content {

    public static function register() {
        WPMCP_Tool_Registry::register('wp_create_post', array(
            'description' => 'Crea un nuevo post, página u otro tipo de contenido.',
            'capability' => 'edit_posts',
            'inputSchema' => array(
                'type' => 'object',
                'properties' => array(
                    'title' => array('type' => 'string', 'description' => 'Título del contenido.'),
                    'content' => array('type' => 'string', 'description' => 'Contenido HTML del post.'),
                    'excerpt' => array('type' => 'string', 'description' => 'Extracto opcional.'),
                    'status' => array('type' => 'string', 'enum' => array('draft', 'publish', 'pending', 'private'), 'description' => 'Estado de publicación. Por defecto draft.'),
                    'post_type' => array('type' => 'string', 'description' => 'Tipo de contenido (post, page, etc). Por defecto post.'),
                    'categories' => array('type' => 'array', 'items' => array('type' => 'string'), 'description' => 'Nombres de categorías.'),
                    'tags' => array('type' => 'array', 'items' => array('type' => 'string'), 'description' => 'Nombres de etiquetas.'),
                ),
                'required' => array('title'),
            ),
            'callback' => array(__CLASS__, 'create_post'),
        ));

        WPMCP_Tool_Registry::register('wp_update_post', array(
            'description' => 'Actualiza un post, página u otro contenido existente por ID.',
            'capability' => 'edit_posts',
            'inputSchema' => array(
                'type' => 'object',
                'properties' => array(
                    'id' => array('type' => 'integer', 'description' => 'ID del post a actualizar.'),
                    'title' => array('type' => 'string'),
                    'content' => array('type' => 'string'),
                    'excerpt' => array('type' => 'string'),
                    'status' => array('type' => 'string', 'enum' => array('draft', 'publish', 'pending', 'private', 'trash')),
                    'categories' => array('type' => 'array', 'items' => array('type' => 'string')),
                    'tags' => array('type' => 'array', 'items' => array('type' => 'string')),
                ),
                'required' => array('id'),
            ),
            'callback' => array(__CLASS__, 'update_post'),
        ));

        WPMCP_Tool_Registry::register('wp_delete_post', array(
            'description' => 'Elimina un post (a la papelera, o permanentemente con force=true).',
            'capability' => 'edit_posts',
            'inputSchema' => array(
                'type' => 'object',
                'properties' => array(
                    'id' => array('type' => 'integer'),
                    'force' => array('type' => 'boolean', 'description' => 'Si es true, borra permanentemente sin pasar por la papelera.'),
                ),
                'required' => array('id'),
            ),
            'callback' => array(__CLASS__, 'delete_post'),
        ));

        WPMCP_Tool_Registry::register('wp_get_posts', array(
            'description' => 'Busca y lista posts/páginas con filtros.',
            'capability' => 'edit_posts',
            'inputSchema' => array(
                'type' => 'object',
                'properties' => array(
                    'search' => array('type' => 'string'),
                    'status' => array('type' => 'string', 'description' => 'Por defecto "any".'),
                    'post_type' => array('type' => 'string', 'description' => 'Por defecto "post".'),
                    'per_page' => array('type' => 'integer', 'description' => 'Máximo 50. Por defecto 10.'),
                    'page' => array('type' => 'integer', 'description' => 'Página de resultados. Por defecto 1.'),
                ),
            ),
            'callback' => array(__CLASS__, 'get_posts'),
        ));

        WPMCP_Tool_Registry::register('wp_get_post', array(
            'description' => 'Obtiene el detalle completo de un post por ID.',
            'capability' => 'edit_posts',
            'inputSchema' => array(
                'type' => 'object',
                'properties' => array('id' => array('type' => 'integer')),
                'required' => array('id'),
            ),
            'callback' => array(__CLASS__, 'get_post'),
        ));

        WPMCP_Tool_Registry::register('wp_upload_media', array(
            'description' => 'Sube un archivo multimedia a la biblioteca desde una URL pública y opcionalmente lo asocia a un post.',
            'capability' => 'upload_files',
            'inputSchema' => array(
                'type' => 'object',
                'properties' => array(
                    'url' => array('type' => 'string', 'description' => 'URL pública del archivo a descargar e importar.'),
                    'title' => array('type' => 'string'),
                    'alt_text' => array('type' => 'string'),
                    'post_id' => array('type' => 'integer', 'description' => 'Post al que asociar el adjunto (opcional).'),
                ),
                'required' => array('url'),
            ),
            'callback' => array(__CLASS__, 'upload_media'),
        ));

        WPMCP_Tool_Registry::register('wp_list_media', array(
            'description' => 'Lista los archivos multimedia de la biblioteca.',
            'capability' => 'upload_files',
            'inputSchema' => array(
                'type' => 'object',
                'properties' => array(
                    'search' => array('type' => 'string'),
                    'per_page' => array('type' => 'integer'),
                    'page' => array('type' => 'integer'),
                ),
            ),
            'callback' => array(__CLASS__, 'list_media'),
        ));

        WPMCP_Tool_Registry::register('wp_delete_media', array(
            'description' => 'Elimina un archivo multimedia de la biblioteca.',
            'capability' => 'upload_files',
            'inputSchema' => array(
                'type' => 'object',
                'properties' => array('id' => array('type' => 'integer')),
                'required' => array('id'),
            ),
            'callback' => array(__CLASS__, 'delete_media'),
        ));

        WPMCP_Tool_Registry::register('wp_list_terms', array(
            'description' => 'Lista los términos de una taxonomía (categorías, etiquetas, etc).',
            'capability' => 'manage_categories',
            'inputSchema' => array(
                'type' => 'object',
                'properties' => array(
                    'taxonomy' => array('type' => 'string', 'description' => 'Por defecto "category".'),
                ),
            ),
            'callback' => array(__CLASS__, 'list_terms'),
        ));

        WPMCP_Tool_Registry::register('wp_create_term', array(
            'description' => 'Crea una categoría, etiqueta u otro término de taxonomía.',
            'capability' => 'manage_categories',
            'inputSchema' => array(
                'type' => 'object',
                'properties' => array(
                    'taxonomy' => array('type' => 'string', 'description' => 'Por defecto "category".'),
                    'name' => array('type' => 'string'),
                    'description' => array('type' => 'string'),
                    'parent' => array('type' => 'integer'),
                ),
                'required' => array('name'),
            ),
            'callback' => array(__CLASS__, 'create_term'),
        ));

        WPMCP_Tool_Registry::register('wp_delete_term', array(
            'description' => 'Elimina un término de taxonomía por ID.',
            'capability' => 'manage_categories',
            'inputSchema' => array(
                'type' => 'object',
                'properties' => array(
                    'taxonomy' => array('type' => 'string', 'description' => 'Por defecto "category".'),
                    'id' => array('type' => 'integer'),
                ),
                'required' => array('id'),
            ),
            'callback' => array(__CLASS__, 'delete_term'),
        ));

        WPMCP_Tool_Registry::register('wp_list_comments', array(
            'description' => 'Lista comentarios con filtros de estado o post.',
            'capability' => 'moderate_comments',
            'inputSchema' => array(
                'type' => 'object',
                'properties' => array(
                    'post_id' => array('type' => 'integer'),
                    'status' => array('type' => 'string', 'enum' => array('all', 'approve', 'hold', 'spam', 'trash')),
                    'per_page' => array('type' => 'integer'),
                ),
            ),
            'callback' => array(__CLASS__, 'list_comments'),
        ));

        WPMCP_Tool_Registry::register('wp_moderate_comment', array(
            'description' => 'Cambia el estado de un comentario (aprobar, marcar como spam, mover a papelera o eliminar).',
            'capability' => 'moderate_comments',
            'inputSchema' => array(
                'type' => 'object',
                'properties' => array(
                    'id' => array('type' => 'integer'),
                    'action' => array('type' => 'string', 'enum' => array('approve', 'hold', 'spam', 'trash', 'delete')),
                ),
                'required' => array('id', 'action'),
            ),
            'callback' => array(__CLASS__, 'moderate_comment'),
        ));
    }

    public static function create_post($args) {
        if (empty($args['title'])) {
            return new WP_Error('wpmcp_missing_arg', 'Falta el título del post.');
        }

        $status = isset($args['status']) && in_array($args['status'], array('draft', 'publish', 'pending', 'private'), true)
            ? $args['status']
            : 'draft';

        if ('publish' === $status && !current_user_can('publish_posts')) {
            return new WP_Error('wpmcp_forbidden', 'No tienes permiso para publicar contenido directamente. Usa status=draft o pending.');
        }

        $post_type = !empty($args['post_type']) ? sanitize_key($args['post_type']) : 'post';
        if (!post_type_exists($post_type)) {
            return new WP_Error('wpmcp_invalid_arg', 'El tipo de contenido "' . $post_type . '" no existe.');
        }

        $postarr = array(
            'post_title' => sanitize_text_field($args['title']),
            'post_content' => isset($args['content']) ? wp_kses_post($args['content']) : '',
            'post_excerpt' => isset($args['excerpt']) ? sanitize_textarea_field($args['excerpt']) : '',
            'post_status' => $status,
            'post_type' => $post_type,
            'post_author' => get_current_user_id(),
        );

        $post_id = wp_insert_post($postarr, true);
        if (is_wp_error($post_id)) {
            return $post_id;
        }

        if (!empty($args['categories']) && is_array($args['categories'])) {
            wp_set_post_terms($post_id, array_map('sanitize_text_field', $args['categories']), 'category');
        }
        if (!empty($args['tags']) && is_array($args['tags'])) {
            wp_set_post_terms($post_id, array_map('sanitize_text_field', $args['tags']), 'post_tag');
        }

        return self::format_post($post_id);
    }

    public static function update_post($args) {
        if (empty($args['id'])) {
            return new WP_Error('wpmcp_missing_arg', 'Falta el ID del post.');
        }
        $id = absint($args['id']);
        if (!get_post($id)) {
            return new WP_Error('wpmcp_not_found', 'No existe un post con ID ' . $id . '.');
        }
        if (!current_user_can('edit_post', $id)) {
            return new WP_Error('wpmcp_forbidden', 'No tienes permiso para editar este post.');
        }

        $postarr = array('ID' => $id);
        if (isset($args['title'])) {
            $postarr['post_title'] = sanitize_text_field($args['title']);
        }
        if (isset($args['content'])) {
            $postarr['post_content'] = wp_kses_post($args['content']);
        }
        if (isset($args['excerpt'])) {
            $postarr['post_excerpt'] = sanitize_textarea_field($args['excerpt']);
        }
        if (isset($args['status'])) {
            if ('publish' === $args['status'] && !current_user_can('publish_posts')) {
                return new WP_Error('wpmcp_forbidden', 'No tienes permiso para publicar este post.');
            }
            $postarr['post_status'] = sanitize_key($args['status']);
        }

        $result = wp_update_post($postarr, true);
        if (is_wp_error($result)) {
            return $result;
        }

        if (isset($args['categories']) && is_array($args['categories'])) {
            wp_set_post_terms($id, array_map('sanitize_text_field', $args['categories']), 'category');
        }
        if (isset($args['tags']) && is_array($args['tags'])) {
            wp_set_post_terms($id, array_map('sanitize_text_field', $args['tags']), 'post_tag');
        }

        return self::format_post($id);
    }

    public static function delete_post($args) {
        if (empty($args['id'])) {
            return new WP_Error('wpmcp_missing_arg', 'Falta el ID del post.');
        }
        $id = absint($args['id']);
        if (!get_post($id)) {
            return new WP_Error('wpmcp_not_found', 'No existe un post con ID ' . $id . '.');
        }
        if (!current_user_can('delete_post', $id)) {
            return new WP_Error('wpmcp_forbidden', 'No tienes permiso para eliminar este post.');
        }

        $force = !empty($args['force']);
        $result = wp_delete_post($id, $force);
        if (!$result) {
            return new WP_Error('wpmcp_delete_failed', 'No se pudo eliminar el post ' . $id . '.');
        }

        return array('deleted' => true, 'id' => $id, 'force' => $force);
    }

    public static function get_posts($args) {
        $per_page = isset($args['per_page']) ? min(50, max(1, absint($args['per_page']))) : 10;
        $post_type = !empty($args['post_type']) ? sanitize_key($args['post_type']) : 'post';
        $query_args = array(
            'post_type' => $post_type,
            'post_status' => !empty($args['status']) ? sanitize_key($args['status']) : 'any',
            'posts_per_page' => $per_page,
            'paged' => isset($args['page']) ? max(1, absint($args['page'])) : 1,
        );
        if (!empty($args['search'])) {
            $query_args['s'] = sanitize_text_field($args['search']);
        }

        $post_type_obj = get_post_type_object($post_type);
        if (!$post_type_obj || !current_user_can($post_type_obj->cap->edit_others_posts)) {
            $query_args['author'] = get_current_user_id();
        }

        $query = new WP_Query($query_args);
        $items = array();
        foreach ($query->posts as $post) {
            $items[] = self::format_post($post->ID);
        }

        return array('total' => (int) $query->found_posts, 'items' => $items);
    }

    public static function get_post($args) {
        if (empty($args['id'])) {
            return new WP_Error('wpmcp_missing_arg', 'Falta el ID del post.');
        }
        $id = absint($args['id']);
        if (!get_post($id)) {
            return new WP_Error('wpmcp_not_found', 'No existe un post con ID ' . $id . '.');
        }
        if (!current_user_can('edit_post', $id) && !current_user_can('read_post', $id)) {
            return new WP_Error('wpmcp_forbidden', 'No tienes permiso para ver este post.');
        }
        return self::format_post($id, true);
    }

    private static function format_post($id, $full = false) {
        $post = get_post($id);
        $data = array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'status' => $post->post_status,
            'type' => $post->post_type,
            'date' => $post->post_date,
            'author' => (int) $post->post_author,
            'permalink' => get_permalink($post),
            'edit_url' => get_edit_post_link($post->ID, 'raw'),
        );
        if ($full) {
            $data['content'] = $post->post_content;
            $data['excerpt'] = $post->post_excerpt;
            $data['categories'] = wp_list_pluck(get_the_category($post->ID), 'name');
            $data['tags'] = wp_list_pluck(get_the_tags($post->ID) ?: array(), 'name');
        }
        return $data;
    }

    public static function upload_media($args) {
        if (empty($args['url'])) {
            return new WP_Error('wpmcp_missing_arg', 'Falta la URL del archivo.');
        }
        $url = esc_url_raw($args['url']);
        if (!$url) {
            return new WP_Error('wpmcp_invalid_arg', 'La URL proporcionada no es válida.');
        }

        $post_id = !empty($args['post_id']) ? absint($args['post_id']) : 0;
        if ($post_id && !current_user_can('edit_post', $post_id)) {
            return new WP_Error('wpmcp_forbidden', 'No tienes permiso para adjuntar medios a este post.');
        }

        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $attachment_id = media_sideload_image($url, $post_id, isset($args['title']) ? $args['title'] : null, 'id');

        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }

        if (!empty($args['alt_text'])) {
            update_post_meta($attachment_id, '_wp_attachment_image_alt', sanitize_text_field($args['alt_text']));
        }

        return array(
            'id' => $attachment_id,
            'url' => wp_get_attachment_url($attachment_id),
            'edit_url' => get_edit_post_link($attachment_id, 'raw'),
        );
    }

    public static function list_media($args) {
        $per_page = isset($args['per_page']) ? min(50, max(1, absint($args['per_page']))) : 10;
        $query_args = array(
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => $per_page,
            'paged' => isset($args['page']) ? max(1, absint($args['page'])) : 1,
        );
        if (!empty($args['search'])) {
            $query_args['s'] = sanitize_text_field($args['search']);
        }

        $query = new WP_Query($query_args);
        $items = array();
        foreach ($query->posts as $post) {
            $items[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'url' => wp_get_attachment_url($post->ID),
                'mime_type' => $post->post_mime_type,
                'date' => $post->post_date,
            );
        }

        return array('total' => (int) $query->found_posts, 'items' => $items);
    }

    public static function delete_media($args) {
        if (empty($args['id'])) {
            return new WP_Error('wpmcp_missing_arg', 'Falta el ID del adjunto.');
        }
        $id = absint($args['id']);
        if ('attachment' !== get_post_type($id)) {
            return new WP_Error('wpmcp_not_found', 'No existe un archivo multimedia con ID ' . $id . '.');
        }
        if (!current_user_can('delete_post', $id)) {
            return new WP_Error('wpmcp_forbidden', 'No tienes permiso para eliminar este archivo.');
        }

        $result = wp_delete_attachment($id, true);
        if (!$result) {
            return new WP_Error('wpmcp_delete_failed', 'No se pudo eliminar el archivo ' . $id . '.');
        }

        return array('deleted' => true, 'id' => $id);
    }

    public static function list_terms($args) {
        $taxonomy = !empty($args['taxonomy']) ? sanitize_key($args['taxonomy']) : 'category';
        if (!taxonomy_exists($taxonomy)) {
            return new WP_Error('wpmcp_invalid_arg', 'La taxonomía "' . $taxonomy . '" no existe.');
        }

        $terms = get_terms(array('taxonomy' => $taxonomy, 'hide_empty' => false));
        if (is_wp_error($terms)) {
            return $terms;
        }

        $items = array();
        foreach ($terms as $term) {
            $items[] = array(
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'count' => $term->count,
                'parent' => $term->parent,
            );
        }
        return array('taxonomy' => $taxonomy, 'items' => $items);
    }

    public static function create_term($args) {
        if (empty($args['name'])) {
            return new WP_Error('wpmcp_missing_arg', 'Falta el nombre del término.');
        }
        $taxonomy = !empty($args['taxonomy']) ? sanitize_key($args['taxonomy']) : 'category';
        if (!taxonomy_exists($taxonomy)) {
            return new WP_Error('wpmcp_invalid_arg', 'La taxonomía "' . $taxonomy . '" no existe.');
        }

        $term_args = array();
        if (!empty($args['description'])) {
            $term_args['description'] = sanitize_textarea_field($args['description']);
        }
        if (!empty($args['parent'])) {
            $term_args['parent'] = absint($args['parent']);
        }

        $result = wp_insert_term(sanitize_text_field($args['name']), $taxonomy, $term_args);
        if (is_wp_error($result)) {
            return $result;
        }

        return array('id' => $result['term_id'], 'taxonomy' => $taxonomy, 'name' => $args['name']);
    }

    public static function delete_term($args) {
        if (empty($args['id'])) {
            return new WP_Error('wpmcp_missing_arg', 'Falta el ID del término.');
        }
        $taxonomy = !empty($args['taxonomy']) ? sanitize_key($args['taxonomy']) : 'category';
        $result = wp_delete_term(absint($args['id']), $taxonomy);
        if (is_wp_error($result)) {
            return $result;
        }
        if (!$result) {
            return new WP_Error('wpmcp_not_found', 'No existe el término ' . absint($args['id']) . ' en "' . $taxonomy . '".');
        }
        return array('deleted' => true, 'id' => absint($args['id']));
    }

    public static function list_comments($args) {
        $query_args = array(
            'number' => isset($args['per_page']) ? min(50, max(1, absint($args['per_page']))) : 10,
        );
        if (!empty($args['post_id'])) {
            $query_args['post_id'] = absint($args['post_id']);
        }
        if (!empty($args['status']) && 'all' !== $args['status']) {
            $query_args['status'] = sanitize_key($args['status']);
        }

        $comments = get_comments($query_args);
        $items = array();
        foreach ($comments as $comment) {
            $items[] = array(
                'id' => (int) $comment->comment_ID,
                'post_id' => (int) $comment->comment_post_ID,
                'author' => $comment->comment_author,
                'content' => $comment->comment_content,
                'status' => wp_get_comment_status($comment),
                'date' => $comment->comment_date,
            );
        }
        return array('items' => $items);
    }

    public static function moderate_comment($args) {
        if (empty($args['id']) || empty($args['action'])) {
            return new WP_Error('wpmcp_missing_arg', 'Faltan los argumentos "id" y/o "action".');
        }
        $id = absint($args['id']);
        if (!get_comment($id)) {
            return new WP_Error('wpmcp_not_found', 'No existe un comentario con ID ' . $id . '.');
        }

        $action = sanitize_key($args['action']);
        switch ($action) {
            case 'approve':
                wp_set_comment_status($id, 'approve');
                break;
            case 'hold':
                wp_set_comment_status($id, 'hold');
                break;
            case 'spam':
                wp_spam_comment($id);
                break;
            case 'trash':
                wp_trash_comment($id);
                break;
            case 'delete':
                wp_delete_comment($id, true);
                break;
            default:
                return new WP_Error('wpmcp_invalid_arg', 'Acción desconocida: ' . $action);
        }

        return array('id' => $id, 'action' => $action);
    }
}
