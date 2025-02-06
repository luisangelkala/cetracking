<?php
/**
 * Plugin Name: Tracking Packages
 * Description: Sistema de tracking de paquetes con gestión de manifiestos.
 * Version: 1.2
 * Author: Tu Nombre
 */

// Evitar el acceso directo al plugin
defined('ABSPATH') || exit;

// Crear tablas al activar el plugin
register_activation_hook(__FILE__, 'tp_create_tables');
function tp_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Tabla de manifiestos
    $table_manifests = $wpdb->prefix . 'tracking_manifests';
    $sql1 = "CREATE TABLE $table_manifests (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        company VARCHAR(100) NOT NULL,
        country VARCHAR(100) NOT NULL,
        consignee VARCHAR(100) NOT NULL,
        guide_number VARCHAR(50) NOT NULL,
        package_count INT NOT NULL,
        weight DECIMAL(10,2) NOT NULL,
        date DATE NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql1);
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

// Página para listar manifiestos
function tp_list_manifests() {
    global $wpdb;
    $table_manifests = $wpdb->prefix . 'tracking_manifests';
    $manifests = $wpdb->get_results("SELECT * FROM $table_manifests");

    echo '<div class="wrap">';
    echo '<h1>Listado de Manifiestos</h1>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Número de Guía</th><th>Cantidad de Bultos</th><th>Peso</th><th>Fecha</th><th>Acción</th></tr></thead>';
    echo '<tbody>';
    foreach ($manifests as $manifest) {
        echo '<tr>';
        echo '<td>' . esc_html($manifest->guide_number) . '</td>';
        echo '<td>' . esc_html($manifest->package_count) . '</td>';
        echo '<td>' . esc_html($manifest->weight) . ' kg</td>';
        echo '<td>' . esc_html($manifest->date) . '</td>';
        echo '<td><a href="admin.php?page=edit-manifest&id=' . $manifest->id . '" class="button">Editar</a></td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    echo '</div>';
}

// Manejar la página de edición de manifiesto dinámicamente
add_action('admin_init', 'tp_check_edit_manifest');
function tp_check_edit_manifest() {
    if (isset($_GET['page']) && $_GET['page'] === 'edit-manifest') {
        add_action('admin_menu', function() {
            add_menu_page('Editar Manifiesto', 'Editar Manifiesto', 'manage_options', 'edit-manifest', 'tp_edit_manifest_page');
        });
    }
}

// Página para editar manifiesto
function tp_edit_manifest_page() {
    global $wpdb;
    $table_manifests = $wpdb->prefix . 'tracking_manifests';
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $manifest = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_manifests WHERE id = %d", $id));

    if (!$manifest) {
        echo '<div class="error"><p>Manifiesto no encontrado.</p></div>';
        return;
    }

    if (isset($_POST['tp_update_manifest'])) {
        $company = sanitize_text_field($_POST['company']);
        $country = sanitize_text_field($_POST['country']);
        $consignee = sanitize_text_field($_POST['consignee']);
        $guide_number = sanitize_text_field($_POST['guide_number']);
        $package_count = intval($_POST['package_count']);
        $weight = floatval($_POST['weight']);
        $date = sanitize_text_field($_POST['date']);

        $wpdb->update($table_manifests, [
            'company' => $company,
            'country' => $country,
            'consignee' => $consignee,
            'guide_number' => $guide_number,
            'package_count' => $package_count,
            'weight' => $weight,
            'date' => $date
        ], ['id' => $id]);

        echo '<div class="updated"><p>Manifiesto actualizado exitosamente.</p></div>';
    }

    echo '<div class="wrap">';
    echo '<h1>Editar Manifiesto</h1>';
    echo '<form method="post">';
    echo '<label>Empresa:</label><input type="text" name="company" value="' . esc_attr($manifest->company) . '" required /><br />';
    echo '<label>País:</label><input type="text" name="country" value="' . esc_attr($manifest->country) . '" required /><br />';
    echo '<label>Consignatario:</label><input type="text" name="consignee" value="' . esc_attr($manifest->consignee) . '" required /><br />';
    echo '<label>Número de Guía:</label><input type="text" name="guide_number" value="' . esc_attr($manifest->guide_number) . '" required /><br />';
    echo '<label>Cantidad de Bultos:</label><input type="number" name="package_count" value="' . esc_attr($manifest->package_count) . '" required /><br />';
    echo '<label>Peso:</label><input type="text" name="weight" value="' . esc_attr($manifest->weight) . '" required /><br />';
    echo '<label>Fecha:</label><input type="date" name="date" value="' . esc_attr($manifest->date) . '" required /><br />';
    echo '<input type="submit" name="tp_update_manifest" class="button button-primary" value="Actualizar" />';
    echo '</form>';
    echo '</div>';
}
