<?php
/*
Plugin Name: Sistema de Alertas Frontend
Description: Permite a administradores crear y publicar alertas desde el frontend en WordPress, con tipo (informativa/emergencia), fecha de publicación y expiración automática.
Version: 1.2
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
    // Verificar si el usuario es administrador
    if (!current_user_can('manage_options')) {
        return '<p>Solo los administradores pueden crear alertas.</p>';
    }

    // Procesar el formulario
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
            // Guardar metadatos
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
            <label for="alerta_fecha">Fecha de Publicación:</label>
            <input type="date" name="alerta_fecha" id="alerta_fecha" required>
        </div>
        <div>
            <label for="alerta_fecha_expiracion">Fecha de Expiración:</label>
            <input type="date" name="alerta_fecha_expiracion" id="alerta_fecha_expiracion" required>
        </div>
        <button type="submit" name="crear_alerta">Publicar Alerta</button>
    </form>
    <style>
        .formulario-alertas {
            max-width: 600px;
            margin: 20px 0;
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
        }
        .alerta-exito {
            color: green;
        }
        .alerta-error {
            color: red;
        }
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode('formulario_alertas', 'formulario_alertas_shortcode');

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
            $fecha_publicacion = get_post_meta(get_the_ID(), 'fecha_publicacion', true);
            $fecha_expiracion = get_post_meta(get_the_ID(), 'fecha_expiracion', true);
            $clase_alerta = ($tipo_alerta === 'emergencia') ? 'alerta-emergencia' : 'alerta-informativa';
            ?>
            <div class="alerta <?php echo esc_attr($clase_alerta); ?>">
                <h3><?php the_title(); ?></h3>
                <div><?php the_content(); ?></div>
                <p><strong>Tipo:</strong> <?php echo esc_html(ucfirst($tipo_alerta)); ?></p>
                <p><strong>Fecha de Publicación:</strong> <?php echo esc_html($fecha_publicacion); ?></p>
                <p><strong>Fecha de Expiración:</strong> <?php echo esc_html($fecha_expiracion); ?></p>
                <p><small>Publicado por <?php the_author(); ?> el <?php the_date(); ?></small></p>
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
        .lista-alertas .alerta {
            border: 1px solid #ccc;
            padding: 15px;
            margin-bottom: 10px;
        }
        .alerta-informativa {
            background-color: #e6f3ff;
        }
        .alerta-emergencia {
            background-color: #ffe6e6;
            border-color: #ff0000;
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
    $hoy = date('Y-m-d');
    $args = array(
        'post_type' => 'alerta',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => 'fecha_expiracion',
                'value' => $hoy,
                'compare' => '<=',
                'type' => 'DATE',
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