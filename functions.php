<?php
/**
 * Butaca — funciones del tema.
 *
 * @package Butaca
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_theme_file_path( 'inc/tmdb.php' );
require_once get_theme_file_path( 'inc/settings.php' );
require_once get_theme_file_path( 'inc/rest.php' );
require_once get_theme_file_path( 'inc/render.php' );

function butaca_setup() {
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_editor_style( array( butaca_fonts_url(), 'assets/css/butaca.css' ) );
}
add_action( 'after_setup_theme', 'butaca_setup' );

function butaca_fonts_url() {
	return 'https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600;700&display=swap';
}

function butaca_enqueue() {
	$ver = wp_get_theme()->get( 'Version' );
	wp_enqueue_style( 'butaca-fonts', butaca_fonts_url(), array(), null );
	wp_enqueue_style( 'butaca', get_theme_file_uri( 'assets/css/butaca.css' ), array(), $ver );
	wp_enqueue_script( 'butaca', get_theme_file_uri( 'assets/js/butaca.js' ), array(), $ver, array( 'strategy' => 'defer', 'in_footer' => true ) );
	wp_add_inline_script(
		'butaca',
		'window.BUTACA = ' . wp_json_encode(
			array(
				'rest'   => esc_url_raw( rest_url( 'butaca/v1/' ) ),
				'locale' => str_replace( '_', '-', get_locale() ),
			)
		) . ';',
		'before'
	);
}
add_action( 'wp_enqueue_scripts', 'butaca_enqueue' );

function butaca_pattern_category() {
	register_block_pattern_category( 'butaca', array( 'label' => __( 'Butaca · Cine', 'butaca' ) ) );
}
add_action( 'init', 'butaca_pattern_category' );

/**
 * Ejecuta shortcodes del bloque Shortcode dentro de plantillas de bloques.
 */
function butaca_render_shortcode_block( $content ) {
	return do_shortcode( shortcode_unautop( $content ) );
}
add_filter( 'render_block_core/shortcode', 'butaca_render_shortcode_block' );

/**
 * Enlaces relativos a la raíz ("/#seccion") → URL real del sitio
 * (compatibilidad con instalaciones en subdirectorio).
 */
function butaca_absolute_links( $html ) {
	if ( false === strpos( $html, '="/' ) ) {
		return $html;
	}
	return preg_replace( '#(href|action)="/(?!/)#', '$1="' . esc_url( untrailingslashit( home_url() ) ) . '/', $html );
}
add_filter( 'render_block', 'butaca_absolute_links' );

/**
 * Enlace a los ajustes desde la pantalla de temas.
 */
function butaca_admin_bar( $bar ) {
	if ( current_user_can( 'manage_options' ) && ! is_admin() ) {
		$bar->add_node(
			array(
				'id'    => 'butaca',
				'title' => butaca_is_demo() ? '🎬 Butaca: modo demo' : '🎬 Butaca: TMDB',
				'href'  => admin_url( 'options-general.php?page=butaca' ),
			)
		);
	}
}
add_action( 'admin_bar_menu', 'butaca_admin_bar', 90 );
