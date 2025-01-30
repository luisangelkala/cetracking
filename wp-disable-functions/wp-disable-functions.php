<?php
/**
 * Plugin Name: Deshabilitar Funcionalidades de WordPress
 * Description: Desactiva publicaciones, comentarios, feeds, emojis, scripts innecesarios y todo lo relacionado con la apariencia de WordPress.
 * Version: 1.0
 * Author: Tu Nombre
 */

if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente
}

// Desactivar tipos de contenido predeterminados
function disable_post_types() {
    remove_menu_page('edit.php'); // Entradas
    remove_menu_page('edit.php?post_type=page'); // Páginas
}
add_action('admin_menu', 'disable_post_types');

// Desactivar comentarios
function disable_comments() {
    remove_menu_page('edit-comments.php');
    return false;
}
add_filter('comments_open', 'disable_comments', 20, 2);
add_filter('pings_open', 'disable_comments', 20, 2);
add_action('admin_menu', 'disable_comments');

// Desactivar feeds RSS
function disable_feeds() {
    wp_die(__('Los feeds están deshabilitados.'));
}
add_action('do_feed', 'disable_feeds', 1);
add_action('do_feed_rdf', 'disable_feeds', 1);
add_action('do_feed_rss', 'disable_feeds', 1);
add_action('do_feed_rss2', 'disable_feeds', 1);
add_action('do_feed_atom', 'disable_feeds', 1);

// Desactivar emojis
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('admin_print_scripts', 'print_emoji_detection_script');
remove_action('admin_print_styles', 'print_emoji_styles');

// Eliminar scripts y estilos innecesarios
function remove_unnecessary_assets() {
    wp_dequeue_script('wp-embed');
    wp_dequeue_style('wp-block-library');
}
add_action('wp_enqueue_scripts', 'remove_unnecessary_assets', 100);

// Deshabilitar barra de administración
add_filter('show_admin_bar', '__return_false');

// Deshabilitar actualizaciones de WordPress y plugins
function disable_wp_updates() {
    add_filter('pre_site_transient_update_core', '__return_null');
    add_filter('pre_site_transient_update_plugins', '__return_null');
    add_filter('pre_site_transient_update_themes', '__return_null');
}
add_action('init', 'disable_wp_updates');

// Desactivar el Editor de Apariencia y Personalización
function disable_theme_customization() {
    remove_menu_page('themes.php'); // Eliminar menú Apariencia
    define('DISALLOW_FILE_EDIT', true);
    define('DISALLOW_FILE_MODS', true);
}
add_action('admin_menu', 'disable_theme_customization', 999);

// Desactivar el Site Editor (Editor de Bloques Completo)
function disable_site_editor() {
    remove_theme_support('block-templates');
}
add_action('after_setup_theme', 'disable_site_editor');
