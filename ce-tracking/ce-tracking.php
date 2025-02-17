<?php
/**
 * Plugin Name: Tracking Packages
 * Description: Sistema de tracking de paquetes con gestión de manifiestos mediante Custom Post Types y Meta Fields.
 * Version: 2.0
 * Author: Luis Angel Calana
 */

// Evitar acceso directo
if (!defined('ABSPATH')) exit;

// Registrar Custom Post Type para Manifiestos
add_action('init', 'tp_register_cpt_manifests');
function tp_register_cpt_manifests() {
    $labels = array(
        'name'               => 'Manifiestos',
        'singular_name'      => 'Manifiesto',
        'menu_name'          => 'Manifiestos',
        'name_admin_bar'     => 'Manifiesto',
        'add_new'            => 'Añadir Nuevo Manifiesto',
        'add_new_item'       => 'Añadir Nuevo Manifiesto',
        'new_item'           => 'Nuevo Manifiesto',
        'edit_item'          => 'Editar Manifiesto',
        'view_item'          => 'Ver Manifiesto',
        'all_items'          => 'Todos los Manifiestos',
        'search_items'       => 'Buscar Manifiestos',
        'not_found'          => 'No se encontraron Manifiestos.',
        'not_found_in_trash' => 'No se encontraron Manifiestos en la papelera.',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'rewrite'            => array('slug' => 'manifiestos'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'menu_position'      => 20,
        'supports'           => array('title'),
        'menu_icon'          => 'dashicons-portfolio',
    );

    register_post_type('tp_manifests', $args);
}

// Agregar columnas personalizadas al listado de Manifiestos en el admin
add_filter('manage_tp_manifests_posts_columns', 'tp_add_manifest_columns');
function tp_add_manifest_columns($columns) {
    $columns['bultos'] = 'Bultos';
    $columns['peso'] = 'Peso';
    $columns['ver_bls'] = 'Ver BLs';
    return $columns;
}

// Mostrar los valores en las columnas personalizadas
add_action('manage_tp_manifests_posts_custom_column', 'tp_show_manifest_columns', 10, 2);
function tp_show_manifest_columns($column, $post_id) {
    if ($column == 'bultos') {
        echo esc_html(get_post_meta($post_id, '_tp_manifest_bultos', true));
    } elseif ($column == 'peso') {
        echo esc_html(get_post_meta($post_id, '_tp_package_peso', true)) . ' kg';
    } elseif ($column == 'ver_bls') {
        $url = admin_url('edit.php?post_type=tp_packages&manifest_id=' . $post_id);
        echo '<a href="' . esc_url($url) . '">Ver BLs</a>';
    }
}


// Registrar Custom Post Type para Paquetes (Sin menú)
add_action('init', 'tp_register_cpt_packages');
function tp_register_cpt_packages() {
    $args = array(
        'labels'      => array(
            'name'          => 'Paquetes',
            'singular_name' => 'Paquete'
        ),
        'public'      => true,
        'show_ui'     => true,
        'show_in_menu'=> false,
        'supports'    => array('title'),
    );
    register_post_type('tp_packages', $args);
}

// Agregar Meta Boxes para Manifiestos
add_action('add_meta_boxes', 'tp_add_manifest_metaboxes');
function tp_add_manifest_metaboxes() {
    $fields = array(
        'tp_manifest_company'   => 'Empresa',
        'tp_manifest_country'   => 'País',
        'tp_manifest_consignee' => 'Consignatario',
        'tp_manifest_guide'     => 'Número de Guía',
        'tp_manifest_bultos'    => 'Cantidad de Bultos',
        'tp_manifest_weight'    => 'Peso',
        'tp_manifest_date'      => 'Fecha'
    );

    foreach ($fields as $id => $label) {
        add_meta_box($id, $label, 'tp_manifest_meta_callback', 'tp_manifests', 'normal', 'high', array('field_id' => $id));
    }

    global $post;
    if ($post && get_post_status($post->ID) !== 'auto-draft') {
        add_meta_box('tp_manifest_excel', 'Importar Paquetes desde Excel', 'tp_manifest_excel_callback', 'tp_manifests', 'normal', 'high');
    }
}

// Callback para todos los Meta Boxes
function tp_manifest_meta_callback($post, $metabox) {
    $field_id = $metabox['args']['field_id'];
    $value = get_post_meta($post->ID, "_$field_id", true);
    $readonly = get_post_status($post->ID) !== 'auto-draft' ? 'readonly' : '';
    echo "<input type='text' name='$field_id' value='" . esc_attr($value) . "' class='widefat' $readonly>";
}

// Callback para el campo de Importar Excel
function tp_manifest_excel_callback() {
    echo '<input type="file" name="tp_manifest_excel_file" accept=".xlsx, .xls, .csv" class="widefat">';
}

// Guardar los metadatos al guardar el manifiesto
add_action('save_post', 'tp_save_manifest_data');
function tp_save_manifest_data($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!isset($_POST['post_type']) || $_POST['post_type'] !== 'tp_manifests') return;
    if (!current_user_can('edit_post', $post_id)) return;
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'update-post_' . $post_id)) return;

    $fields = array(
        'tp_manifest_company',
        'tp_manifest_country',
        'tp_manifest_consignee',
        'tp_manifest_guide',
        'tp_manifest_bultos',
        'tp_manifest_weight',
        'tp_manifest_date'
    );

    foreach ($fields as $field) {
        update_post_meta($post_id, "_$field", sanitize_text_field($_POST[$field] ?? ''));
    }

    // Importación de paquetes desde Excel
    if (get_post_status($post_id) === 'publish' && !empty($_FILES['tp_manifest_excel_file']['tmp_name'])) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();
        global $wp_filesystem;

        $file = $_FILES['tp_manifest_excel_file'];
        $upload = wp_handle_upload($file, ['test_form' => false]);

        if (isset($upload['file'])) {
            require_once plugin_dir_path(__FILE__) . 'lib/PHPExcel.php';
            $excelFile = $upload['file'];
            $objPHPExcel = PHPExcel_IOFactory::load($excelFile);
            $sheet = $objPHPExcel->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

            foreach ($rows as $row) {
                $package_data = array(
                    'post_type'    => 'tp_packages',
                    'post_title'   => sanitize_text_field($row['A']),
                    'post_status'  => 'publish',
                    'meta_input'   => array(
                        '_tp_package_passport'        => sanitize_text_field($row['B']),
                        '_tp_package_remitente'       => sanitize_text_field($row['C']),
                        '_tp_package_remitente_dir'   => sanitize_text_field($row['D']),
                        '_tp_package_destinatario'    => sanitize_text_field($row['E']),
                        '_tp_package_destinatario_ci' => sanitize_text_field($row['F']),
                        '_tp_package_direccion'       => sanitize_text_field($row['G']),
                        '_tp_package_municipio'       => sanitize_text_field($row['H']),
                        '_tp_package_provincia'       => sanitize_text_field($row['I']),
                        '_tp_package_movil'           => sanitize_text_field($row['J']),
                        '_tp_package_telefono'        => sanitize_text_field($row['K']),
                        '_tp_package_bultos'          => sanitize_text_field($row['L']),
                        '_tp_package_peso'            => sanitize_text_field($row['M']),
                        '_tp_package_mercancia'       => sanitize_text_field($row['N']),
                        '_tp_package_observaciones'   => sanitize_text_field($row['O']),
                        '_tp_package_manifest_id'     => $post_id
                    )
                );
                wp_insert_post($package_data);
            }
        }
    }
}
