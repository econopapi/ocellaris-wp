<?php
/**
 * Ocellaris Custom Astra Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Ocellaris Custom Astra
 * @since 1.0.0
 */

/**
 * Define Constants
 */
define( 'CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION', '1.0.0' );

/**
 * Enqueue styles
 */
function child_enqueue_styles() {

	wp_enqueue_style( 'ocellaris-custom-astra-theme-css', get_stylesheet_directory_uri() . '/style.css', array('astra-theme-css'), CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION, 'all' );
}

add_action( 'wp_enqueue_scripts', 'child_enqueue_styles', 15 );


/**
 * Custom Header scripts and styles
 */
function ocellaris_custom_header_assets() {
	// custom header CSS
	wp_enqueue_style(
		'ocellaris-header-css',
		get_stylesheet_directory_uri() . '/assets/css/custom-header.css',
		array(),
		CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION
	);

	//custom header JS
	wp_enqueue_script(
		'ocellaris-header-js',
		get_stylesheet_directory_uri() . '/assets/js/custom-header.js',
		array('jquery'),
		CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION,
		true
	);
}
add_action('wp_enqueue_scripts', 'ocellaris_custom_header_assets');


/**
 * Remove Astra Header
 */
function ocellaris_remove_astra_header() {
	remove_action('astra_header', 'astra_header_markup');
}
add_action('wp', 'ocellaris_remove_astra_header');


/**
 * Add Ocellaris Custom Header
 */
function ocellaris_custom_header_markup() {
	get_template_part('template-parts/header-custom');
}
add_action('astra_header', 'ocellaris_custom_header_markup');


/**
 * Register Navigation Menus
 */
function ocellaris_register_menus() {
	register_nav_menus(
		array(
			'sidebar-menu' => __('Sidebar Menu', 'ocellaris-custom-astra'),
		));
}
add_action('init', 'ocellaris_register_menus');