<?php
/**
 * Plugin Name: Tracking Packages
 * Description: Sistema de tracking de paquetes con gestión de manifiestos mediante Custom Post Types.
 * Version: 1.4
 * Author: Tu Nombre
 */

// Evitar el acceso directo al plugin
defined('ABSPATH') || exit;

// Registrar Custom Post Type para Manifiestos
add_action('init', 'tp_register_cpt_manifests');
function tp_register_cpt_manifests() {
    $args = array(
        'labels' => array(
            'name' => 'Manifiestos',
            'singular_name' => 'Manifiesto'
        ),
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'custom-fields'),
        'menu_icon' => 'dashicons-portfolio',
    );
    register_post_type('tp_manifests', $args);
}

// Registrar Custom Post Type para Paquetes
add_action('init', 'tp_register_cpt_packages');
function tp_register_cpt_packages() {
    $args = array(
        'labels' => array(
            'name' => 'Paquetes',
            'singular_name' => 'Paquete'
        ),
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'custom-fields'),
        'menu_icon' => 'dashicons-archive',
    );
    register_post_type('tp_packages', $args);
}

// Crear menú en el backend
add_action('admin_menu', 'tp_create_menu');
function tp_create_menu() {
    add_menu_page(
        'Tracking Packages',
        'Tracking Packages',
        'manage_options',
        'tracking-packages',
        'tp_list_manifests',
        'dashicons-visibility',
        20
    );
    add_submenu_page(
        'tracking-packages',
        'Añadir Manifiesto',
        'Añadir Manifiesto',
        'manage_options',
        'add-manifest',
        'tp_add_manifest_page'
    );
    add_submenu_page(
        'tracking-packages',
        'Listado de Manifiestos',
        'Listado de Manifiestos',
        'manage_options',
        'list-manifests',
        'tp_list_manifests'
    );
}

// Página para añadir un manifiesto
function tp_add_manifest_page() {
    if (isset($_POST['tp_save_manifest'])) {
        $post_id = wp_insert_post(array(
            'post_type' => 'tp_manifests',
            'post_title' => sanitize_text_field($_POST['guide_number']),
            'post_status' => 'publish',
            'meta_input' => array(
                'company' => sanitize_text_field($_POST['company']),
                'country' => sanitize_text_field($_POST['country']),
                'consignee' => sanitize_text_field($_POST['consignee']),
                'package_count' => intval($_POST['package_count']),
                'weight' => floatval($_POST['weight']),
                'date' => sanitize_text_field($_POST['date'])
            )
        ));
        echo '<div class="updated"><p>Manifiesto añadido correctamente.</p></div>';
    }

    echo '<div class="wrap">';
    echo '<h1>Añadir Manifiesto</h1>';
    echo '<form method="post">';
    echo '<label>Empresa:</label><input type="text" name="company" required /><br />';
    echo '<label>País:</label><input type="text" name="country" required /><br />';
    echo '<label>Consignatario:</label><input type="text" name="consignee" required /><br />';
    echo '<label>Número de Guía:</label><input type="text" name="guide_number" required /><br />';
    echo '<label>Cantidad de Bultos:</label><input type="number" name="package_count" required /><br />';
    echo '<label>Peso:</label><input type="text" name="weight" required /><br />';
    echo '<label>Fecha:</label><input type="date" name="date" required /><br />';
    echo '<input type="submit" name="tp_save_manifest" class="button button-primary" value="Guardar" />';
    echo '</form>';
    echo '</div>';
}

// Página para listar manifiestos
function tp_list_manifests() {
    $manifests = get_posts(array('post_type' => 'tp_manifests', 'numberposts' => -1));

    echo '<div class="wrap">';
    echo '<h1>Listado de Manifiestos</h1>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Número de Guía</th><th>Cantidad de Bultos</th><th>Peso</th><th>Fecha</th><th>Acción</th></tr></thead>';
    echo '<tbody>';
    foreach ($manifests as $manifest) {
        echo '<tr>';
        echo '<td>' . esc_html(get_post_meta($manifest->ID, 'guide_number', true)) . '</td>';
        echo '<td>' . esc_html(get_post_meta($manifest->ID, 'package_count', true)) . '</td>';
        echo '<td>' . esc_html(get_post_meta($manifest->ID, 'weight', true)) . ' kg</td>';
        echo '<td>' . esc_html(get_post_meta($manifest->ID, 'date', true)) . '</td>';
        echo '<td><a href="post.php?post=' . $manifest->ID . '&action=edit" class="button">Editar</a></td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    echo '</div>';
}
