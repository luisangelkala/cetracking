<?php
/**
 * Plugin Name: Desactivar Funcionalidades de WordPress
 * Description: Desactiva publicaciones, comentarios, feeds, emojis y otros elementos innecesarios de WordPress.
 * Version: 1.0
 * Author: Tu Nombre
 */

if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente
}

// Desactivar el editor de entradas y páginas
function disable_post_type_supports() {
    remove_post_type_support('post', 'editor');
    remove_post_type_support('page', 'editor');
    remove_post_type_support('post', 'thumbnail');
    remove_post_type_support('page', 'thumbnail');
}
add_action('init', 'disable_post_type_supports');

// Desactivar comentarios
function disable_comments() {
    return false;
}
add_filter('comments_open', 'disable_comments', 20, 2);
add_filter('pings_open', 'disable_comments', 20, 2);

// Eliminar la sección de comentarios del panel de administración
function remove_admin_menu_comments() {
    remove_menu_page('edit-comments.php');
}
add_action('admin_menu', 'remove_admin_menu_comments');

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

// Desactivar barra de administración
add_filter('show_admin_bar', '__return_false');
