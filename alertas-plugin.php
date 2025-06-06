<?php
/*
Plugin Name: Sistema de Alertas Frontend
Description: Permite a administradores crear, editar y publicar alertas desde el frontend en WordPress, con tipo (informativa/emergencia), fecha de publicación y expiración automática.
Version: 1.4.2
Author: Oscar Gaztelu
*/

// Evitar acceso directo al archivo
if (!defined('ABSPATH')) {
    exit;
}

// Registrar Custom Post Type para Alertas
function registrar_cpt_alertas() {
    $labels = array(
        'name' => 'Alertas',
        'singular_name' => 'Alerta',
        'menu_name' => 'Alertas',
        'name_admin_bar' => 'Alerta',
        'add_new' => 'Nueva Alerta',
        'add_new_item' => 'Añadir Nueva Alerta',
        'edit_item' => 'Editar Alerta',
        'new_item' => 'Nueva Alerta',
        'view_item' => 'Ver Alerta',
        'all_items' => 'Todas las Alertas',
        'search_items' => 'Buscar Alertas',
        'not_found' => 'No se encontraron alertas',
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'alerta'),
        'capability_type' => 'post',
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => 20,
        'supports' => array('title', 'editor', 'author'),
        'show_in_rest' => true,
    );

    register_post_type('alerta', $args);
}
add_action('init', 'registrar_cpt_alertas');

// Shortcode para el formulario de creación de alertas
function formulario_alertas_shortcode() {
    if (!current_user_can('manage_options')) {
        return '<p>Solo los administradores pueden crear alertas.</p>';
    }

    if (isset($_POST['crear_alerta']) && check_admin_referer('crear_alerta_nonce')) {
        $titulo = sanitize_text_field($_POST['alerta_titulo']);
        $contenido = sanitize_textarea_field($_POST['alerta_contenido']);
        $tipo_alerta = sanitize_text_field($_POST['alerta_tipo']);
        $fecha_publicacion = sanitize_text_field($_POST['alerta_fecha']);
        $fecha_expiracion = sanitize_text_field($_POST['alerta_fecha_expiracion']);

        $post_id = wp_insert_post(array(
            'post_title' => $titulo,
            'post_content' => $contenido,
            'post_type' => 'alerta',
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
        ));

        if ($post_id) {
            update_post_meta($post_id, 'tipo_alerta', $tipo_alerta);
            update_post_meta($post_id, 'fecha_publicacion', $fecha_publicacion);
            update_post_meta($post_id, 'fecha_expiracion', $fecha_expiracion);
            echo '<p class="alerta-exito">¡Alerta creada con éxito!</p>';
        } else {
            echo '<p class="alerta-error">Error al crear la alerta.</p>';
        }
    }

    ob_start();
    ?>
    <form method="post" class="formulario-alertas">
        <?php wp_nonce_field('crear_alerta_nonce'); ?>
        <div>
            <label for="alerta_titulo">Título de la Alerta:</label>
            <input type="text" name="alerta_titulo" id="alerta_titulo" required>
        </div>
        <div>
            <label for="alerta_contenido">Contenido de la Alerta:</label>
            <textarea name="alerta_contenido" id="alerta_contenido" rows="5" required></textarea>
        </div>
        <div>
            <label for="alerta_tipo">Tipo de Alerta:</label>
            <select name="alerta_tipo" id="alerta_tipo" required>
                <option value="informativa">Informativa</option>
                <option value="emergencia">Emergencia</option>
            </select>
        </div>
        <div>
            <label for="alerta_fecha">Fecha y Hora de Publicación:</label>
            <input type="datetime-local" name="alerta_fecha" id="alerta_fecha" required>
        </div>
        <div>
            <label for="alerta_fecha_expiracion">Fecha y Hora de Expiración:</label>
            <input type="datetime-local" name="alerta_fecha_expiracion" id="alerta_fecha_expiracion" required>
        </div>
        <button type="submit" name="crear_alerta">Publicar Alerta</button>
    </form>
    <style>
        .formulario-alertas {
            max-width: 600px;
            margin: 20px 0;
            font-family: 'Work Sans', sans-serif;
        }
        .formulario-alertas label {
            display: block;
            margin-bottom: 5px;
        }
        .formulario-alertas input, 
        .formulario-alertas textarea, 
        .formulario-alertas select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        .formulario-alertas button {
            padding: 10px 20px;
            background-color: #5ADBFF;
            color: #000000;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .formulario-alertas button:hover {
            background-color: #FE9000;
            color: #ffffff;
        }
        .alerta-exito {
            color: green;
            font-family: 'Work Sans', sans-serif;
        }
        .alerta-error {
            color: red;
            font-family: 'Work Sans', sans-serif;
        }
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode('formulario_alertas', 'formulario_alertas_shortcode');

// Shortcode para el formulario de edición de alertas
function formulario_editar_alerta_shortcode($atts) {
    if (!current_user_can('manage_options')) {
        return '<p>Solo los administradores pueden editar alertas.</p>';
    }

    // Obtener ID desde URL o atributo del shortcode
    $alerta_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($atts['id']) ? intval($atts['id']) : 0);
    if (!$alerta_id) {
        return '<p>ID de alerta no válido.</p>';
    }

    $alerta = get_post($alerta_id);
    if (!$alerta || $alerta->post_type !== 'alerta') {
        return '<p>Alerta no encontrada.</p>';
    }

    if (isset($_POST['editar_alerta']) && check_admin_referer('editar_alerta_nonce')) {
        $titulo = sanitize_text_field($_POST['alerta_titulo']);
        $contenido = sanitize_textarea_field($_POST['alerta_contenido']);
        $tipo_alerta = sanitize_text_field($_POST['alerta_tipo']);
        $fecha_publicacion = sanitize_text_field($_POST['alerta_fecha']);
        $fecha_expiracion = sanitize_text_field($_POST['alerta_fecha_expiracion']);

        $post_id = wp_update_post(array(
            'ID' => $alerta_id,
            'post_title' => $titulo,
            'post_content' => $contenido,
        ));

        if ($post_id) {
            update_post_meta($alerta_id, 'tipo_alerta', $tipo_alerta);
            update_post_meta($alerta_id, 'fecha_publicacion', $fecha_publicacion);
            update_post_meta($alerta_id, 'fecha_expiracion', $fecha_expiracion);
            echo '<p class="alerta-exito">¡Alerta actualizada con éxito!</p>';
        } else {
            echo '<p class="alerta-error">Error al actualizar la alerta.</p>';
        }
    }

    $tipo_alerta = get_post_meta($alerta_id, 'tipo_alerta', true);
    $fecha_publicacion = get_post_meta($alerta_id, 'fecha_publicacion', true);
    $fecha_expiracion = get_post_meta($alerta_id, 'fecha_expiracion', true);

    ob_start();
    ?>
    <form method="post" class="formulario-alertas">
        <?php wp_nonce_field('editar_alerta_nonce'); ?>
        <div>
            <label for="alerta_titulo">Título de la Alerta:</label>
            <input type="text" name="alerta_titulo" id="alerta_titulo" value="<?php echo esc_attr($alerta->post_title); ?>" required>
        </div>
        <div>
            <label for="alerta_contenido">Contenido de la Alerta:</label>
            <textarea name="alerta_contenido" id="alerta_contenido" rows="5" required><?php echo esc_textarea($alerta->post_content); ?></textarea>
        </div>
        <div>
            <label for="alerta_tipo">Tipo de Alerta:</label>
            <select name="alerta_tipo" id="alerta_tipo" required>
                <option value="informativa" <?php selected($tipo_alerta, 'informativa'); ?>>Informativa</option>
                <option value="emergencia" <?php selected($tipo_alerta, 'emergencia'); ?>>Emergencia</option>
            </select>
        </div>
        <div>
            <label for="alerta_fecha">Fecha y Hora de Publicación:</label>
            <input type="datetime-local" name="alerta_fecha" id="alerta_fecha" value="<?php echo esc_attr($fecha_publicacion); ?>" required>
        </div>
        <div>
            <label for="alerta_fecha_expiracion">Fecha y Hora de Expiración:</label>
            <input type="datetime-local" name="alerta_fecha_expiracion" id="alerta_fecha_expiracion" value="<?php echo esc_attr($fecha_expiracion); ?>" required>
        </div>
        <button type="submit" name="editar_alerta">Actualizar Alerta</button>
    </form>
    <style>
        .formulario-alertas {
            max-width: 600px;
            margin: 20px 0;
            font-family: 'Work Sans', sans-serif;
        }
        .formulario-alertas label {
            display: block;
            margin-bottom: 5px;
        }
        .formulario-alertas input, 
        .formulario-alertas textarea, 
        .formulario-alertas select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        .formulario-alertas button {
            padding: 10px 20px;
            background-color: #5ADBFF;
            color: #000000;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .formulario-alertas button:hover {
            background-color: #FE9000;
            color: #ffffff;
        }
        .alerta-exito {
            color: green;
            font-family: 'Work Sans', sans-serif;
        }
        .alerta-error {
            color: red;
            font-family: 'Work Sans', sans-serif;
        }
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode('formulario_editar_alerta', 'formulario_editar_alerta_shortcode');

// Shortcode para mostrar las alertas
function mostrar_alertas_shortcode() {
    $args = array(
        'post_type' => 'alerta',
        'posts_per_page' => 10,
        'post_status' => 'publish',
    );

    $alertas = new WP_Query($args);
    ob_start();

    if ($alertas->have_posts()) {
        echo '<div class="lista-alertas">';
        while ($alertas->have_posts()) {
            $alertas->the_post();
            $tipo_alerta = get_post_meta(get_the_ID(), 'tipo_alerta', true);
            $clase_alerta = ($tipo_alerta === 'emergencia') ? 'alerta-emergencia' : 'alerta-informativa';
            ?>
            <div class="alerta <?php echo esc_attr($clase_alerta); ?>">
                <h3><?php the_title(); ?></h3>
                <div><?php the_content(); ?></div>
                <p><strong>Tipo:</strong> <?php echo esc_html(ucfirst($tipo_alerta)); ?></p>
                <?php if (current_user_can('manage_options')) : ?>
                    <a href="<?php echo esc_url(add_query_arg('id', get_the_ID(), 'https://osprototype.dignitasmadrid.com/colaboradores/')); ?>" class="editar-alerta">Editar</a>
                <?php endif; ?>
            </div>
            <?php
        }
        echo '</div>';
    } else {
        echo '<p>No hay alertas disponibles.</p>';
    }

    wp_reset_postdata();
    ?>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Work+Sans:wght@400;700&display=swap');
        .lista-alertas {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .lista-alertas .alerta {
            font-family: 'Work Sans', sans-serif;
            padding: 15px;
            flex: 1;
            min-width: 200px;
            max-width: 300px;
            border-radius: 8px;
            border: none;
        }
        .alerta-informativa {
            background-color: #5ADBFF;
            color: #000000;
        }
        .alerta-emergencia {
            background-color: #FE9000;
            color: #ffffff;
        }
        .editar-alerta {
            display: inline-block;
            margin-top: 10px;
            padding: 8px 15px;
            background-color: transparent;
            color: #000000;
            text-decoration: underline;
            border-radius: 4px;
            font-family: 'Work Sans', sans-serif;
        }
        .editar-alerta:hover {
            background-color: transparent;
            color: #FE9000;
            text-decoration: underline;
        }
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode('mostrar_alertas', 'mostrar_alertas_shortcode');

// Programar WP_Cron para eliminar alertas expiradas
function programar_eliminacion_alertas() {
    if (!wp_next_scheduled('eliminar_alertas_expiradas_event')) {
        wp_schedule_event(time(), 'daily', 'eliminar_alertas_expiradas_event');
    }
}
add_action('wp', 'programar_eliminacion_alertas');

// Función para eliminar alertas expiradas
function eliminar_alertas_expiradas() {
    $ahora = current_time('mysql');
    $args = array(
        'post_type' => 'alerta',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => 'fecha_expiracion',
                'value' => $ahora,
                'compare' => '<=',
                'type' => 'DATETIME',
            ),
        ),
    );

    $alertas = new WP_Query($args);
    if ($alertas->have_posts()) {
        while ($alertas->have_posts()) {
            $alertas->the_post();
            wp_trash_post(get_the_ID());
        }
    }
    wp_reset_postdata();
}
add_action('eliminar_alertas_expiradas_event', 'eliminar_alertas_expiradas');
?>