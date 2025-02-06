<?php
/**
 * Plugin Name: Tracking Packages
 * Description: Sistema de tracking de paquetes con gestión de manifiestos mediante Custom Post Types y Meta Fields.
 * Version: 1.5
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
        'show_ui' => true,
        'show_in_menu' => true,
        'supports' => array('title'),
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
        'show_ui' => true,
        'show_in_menu' => true,
        'supports' => array('title'),
        'menu_icon' => 'dashicons-archive',
    );
    register_post_type('tp_packages', $args);
}

// Agregar Meta Boxes para Manifiestos
add_action('add_meta_boxes', 'tp_add_manifest_metaboxes');
function tp_add_manifest_metaboxes() {
    add_meta_box('tp_manifest_details', 'Detalles del Manifiesto', 'tp_manifest_metabox_callback', 'tp_manifests', 'normal', 'high');
}

function tp_manifest_metabox_callback($post) {
    wp_nonce_field(basename(__FILE__), 'tp_manifest_nonce');
    $company = get_post_meta($post->ID, 'company', true);
    $country = get_post_meta($post->ID, 'country', true);
    $consignee = get_post_meta($post->ID, 'consignee', true);
    $guide_number = get_post_meta($post->ID, 'guide_number', true);
    $package_count = get_post_meta($post->ID, 'package_count', true);
    $weight = get_post_meta($post->ID, 'weight', true);
    $date = get_post_meta($post->ID, 'date', true);
    echo '<label>Empresa:</label><input type="text" name="company" value="' . esc_attr($company) . '" /><br />';
    echo '<label>País:</label><input type="text" name="country" value="' . esc_attr($country) . '" /><br />';
    echo '<label>Consignatario:</label><input type="text" name="consignee" value="' . esc_attr($consignee) . '" /><br />';
    echo '<label>Número de Guía:</label><input type="text" name="guide_number" value="' . esc_attr($guide_number) . '" /><br />';
    echo '<label>Cantidad de Bultos:</label><input type="number" name="package_count" value="' . esc_attr($package_count) . '" /><br />';
    echo '<label>Peso:</label><input type="text" name="weight" value="' . esc_attr($weight) . '" /><br />';
    echo '<label>Fecha:</label><input type="date" name="date" value="' . esc_attr($date) . '" /><br />';
}

// Guardar Meta Fields
add_action('save_post', 'tp_save_manifest_meta');
function tp_save_manifest_meta($post_id) {
    if (!isset($_POST['tp_manifest_nonce']) || !wp_verify_nonce($_POST['tp_manifest_nonce'], basename(__FILE__))) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    update_post_meta($post_id, 'company', sanitize_text_field($_POST['company']));
    update_post_meta($post_id, 'country', sanitize_text_field($_POST['country']));
    update_post_meta($post_id, 'consignee', sanitize_text_field($_POST['consignee']));
    update_post_meta($post_id, 'guide_number', sanitize_text_field($_POST['guide_number']));
    update_post_meta($post_id, 'package_count', intval($_POST['package_count']));
    update_post_meta($post_id, 'weight', floatval($_POST['weight']));
    update_post_meta($post_id, 'date', sanitize_text_field($_POST['date']));
}

// Agregar Custom Listing para Manifiestos
add_filter('manage_tp_manifests_posts_columns', 'tp_set_custom_columns');
function tp_set_custom_columns($columns) {
    unset($columns['date']);
    $columns['guide_number'] = 'Número de Guía';
    $columns['package_count'] = 'Cantidad de Bultos';
    $columns['weight'] = 'Peso';
    $columns['manifest_date'] = 'Fecha';
    return $columns;
}

add_action('manage_tp_manifests_posts_custom_column', 'tp_custom_column_content', 10, 2);
function tp_custom_column_content($column, $post_id) {
    switch ($column) {
        case 'guide_number':
            echo esc_html(get_post_meta($post_id, 'guide_number', true));
            break;
        case 'package_count':
            echo esc_html(get_post_meta($post_id, 'package_count', true));
            break;
        case 'weight':
            echo esc_html(get_post_meta($post_id, 'weight', true)) . ' kg';
            break;
        case 'manifest_date':
            echo esc_html(get_post_meta($post_id, 'date', true));
            break;
    }
}
