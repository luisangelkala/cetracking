<?php
/**
 * Plugin Name: Tracking Packages
 * Description: Sistema de tracking de paquetes mediante la importación de un archivo Excel.
 * Version: 1.0
 * Author: Ing. Luis Angel Kala
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
        manifest_number VARCHAR(50) NOT NULL,
        description VARCHAR(255) NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY manifest_number (manifest_number)
    ) $charset_collate;";

    // Tabla de paquetes (hija)
    $table_packages = $wpdb->prefix . 'tracking_packages';
    $sql2 = "CREATE TABLE $table_packages (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        manifest_id BIGINT(20) UNSIGNED NOT NULL,
        bl_number VARCHAR(50) NOT NULL,
        status VARCHAR(255) NOT NULL,
        PRIMARY KEY (id),
        FOREIGN KEY (manifest_id) REFERENCES $table_manifests(id) ON DELETE CASCADE,
        UNIQUE KEY bl_number (bl_number)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql1);
    dbDelta($sql2);
}

// Crear menú en el backend
add_action('admin_menu', 'tp_create_menu');
function tp_create_menu() {
    add_menu_page(
        'Tracking Packages',
        'Tracking Packages',
        'manage_options',
        'tracking-packages',
        'tp_admin_page',
        'dashicons-visibility',
        20
    );
}

// Página de administración para importar Excel
function tp_admin_page() {
    if (!current_user_can('manage_options')) return;

    global $wpdb;
    $table_manifests = $wpdb->prefix . 'tracking_manifests';
    $table_packages = $wpdb->prefix . 'tracking_packages';

    if (isset($_POST['tp_upload_csv']) && !empty($_FILES['tp_csv_file']['tmp_name'])) {
        $file = $_FILES['tp_csv_file']['tmp_name'];

        if (($handle = fopen($file, 'r')) !== false) {
            // Leer el número de manifiesto desde la primera fila
            $header = fgetcsv($handle, 1000, ',');
            $manifest_number = sanitize_text_field($header[0]);
            $description = 'Importado desde archivo';

            // Insertar manifiesto
            $wpdb->insert($table_manifests, [
                'manifest_number' => $manifest_number,
                'description' => $description
            ]);

            $manifest_id = $wpdb->insert_id;

            // Procesar las filas restantes (BL y estado)
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $bl_number = sanitize_text_field($data[0]);
                $status = sanitize_text_field($data[1]);

                $wpdb->insert($table_packages, [
                    'manifest_id' => $manifest_id,
                    'bl_number' => $bl_number,
                    'status' => $status
                ]);
            }
            fclose($handle);
            echo '<div class="updated"><p>Archivo importado exitosamente.</p></div>';
        } else {
            echo '<div class="error"><p>Error al leer el archivo.</p></div>';
        }
    }

    echo '<div class="wrap">';
    echo '<h1>Importar manifiesto y paquetes</h1>';
    echo '<form method="post" enctype="multipart/form-data">';
    echo '<label for="tp_csv_file">Archivo CSV:</label><br />';
    echo '<input type="file" name="tp_csv_file" accept=".csv" required /><br /><br />';
    echo '<input type="submit" name="tp_upload_csv" class="button button-primary" value="Importar CSV" />';
    echo '</form>';
    echo '</div>';
}

// Registrar y encolar CSS y JS
add_action('wp_enqueue_scripts', 'tp_enqueue_scripts');
function tp_enqueue_scripts() {
    wp_enqueue_style('tp_styles', plugin_dir_url(__FILE__) . 'css/tracking-packages.css');
    wp_enqueue_script('tp_scripts', plugin_dir_url(__FILE__) . 'js/tracking-packages.js', array('jquery'), null, true);
}

// Shortcode para mostrar el formulario de búsqueda
add_shortcode('tracking_form', 'tp_tracking_form');
function tp_tracking_form() {
    global $wpdb;
    $table_manifests = $wpdb->prefix . 'tracking_manifests';
    $table_packages = $wpdb->prefix . 'tracking_packages';

    $output = '<form method="post" class="tp-tracking-form">';
    $output .= '<label for="bl_number">Número de BL:</label> ';
    $output .= '<input type="text" name="bl_number" id="bl_number" class="tp-input" required />';
    $output .= '<input type="submit" value="Buscar" class="tp-button" />';
    $output .= '</form>';

    if (isset($_POST['bl_number'])) {
        $bl_number = sanitize_text_field($_POST['bl_number']);
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT m.manifest_number, m.description, p.status 
            FROM $table_packages p 
            INNER JOIN $table_manifests m ON p.manifest_id = m.id 
            WHERE p.bl_number = %s",
            $bl_number
        ));

        if ($result) {
            $output .= '<div class="tp-result">';
            $output .= '<p><strong>Manifiesto:</strong> ' . esc_html($result->manifest_number) . '</p>';
            $output .= '<p><strong>Descripción:</strong> ' . esc_html($result->description) . '</p>';
            $output .= '<p><strong>Estado:</strong> ' . esc_html($result->status) . '</p>';
            $output .= '</div>';
        } else {
            $output .= '<p class="tp-error">No se encontró el número de BL.</p>';
        }
    }

    return $output;
}
