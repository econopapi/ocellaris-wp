<?php
/**
 * Ocellaris Theme Layout Module
 *
 * Header/footer integration, menus and header AJAX helpers.
 *
 * @package Ocellaris Custom Astra
 * @since 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue custom header scripts and styles.
 */
function ocellaris_custom_header_assets() {
	$header_css_rel = '/assets/css/custom-header.css';
	$header_js_rel  = '/assets/js/custom-header.js';
	$header_css_abs = get_stylesheet_directory() . $header_css_rel;
	$header_js_abs  = get_stylesheet_directory() . $header_js_rel;
	$header_css_ver = file_exists( $header_css_abs ) ? filemtime( $header_css_abs ) : CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION;
	$header_js_ver  = file_exists( $header_js_abs ) ? filemtime( $header_js_abs ) : CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION;

	wp_enqueue_style(
		'ocellaris-header-css',
		get_stylesheet_directory_uri() . $header_css_rel,
		array(),
		$header_css_ver
	);

	wp_enqueue_script(
		'ocellaris-header-js',
		get_stylesheet_directory_uri() . $header_js_rel,
		array( 'jquery' ),
		$header_js_ver,
		true
	);

	wp_localize_script(
		'ocellaris-header-js',
		'OcellarisHeader',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'ocellaris_menu_nonce' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'ocellaris_custom_header_assets' );

/**
 * Update cart count badge dynamically via AJAX fragments.
 */
function ocellaris_cart_count_fragment( $fragments ) {
	$count = WC()->cart->get_cart_contents_count();
	$style = $count === 0 ? ' style="display:none;"' : '';
	$fragments['.ocellaris-cart-count'] = '<span class="ocellaris-cart-count"' . $style . '>' . esc_html( $count ) . '</span>';
	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'ocellaris_cart_count_fragment' );

/**
 * Remove default Astra header.
 */
function ocellaris_remove_astra_header() {
	remove_action( 'astra_header', 'astra_header_markup' );
}
add_action( 'wp', 'ocellaris_remove_astra_header' );

/**
 * Render custom header template.
 */
function ocellaris_custom_header_markup() {
	get_template_part( 'template-parts/header-custom' );
}
add_action( 'astra_header', 'ocellaris_custom_header_markup' );

/**
 * Remove default Astra footer.
 */
function ocellaris_remove_astra_footer() {
	remove_action( 'astra_footer', 'astra_footer_markup' );
}
add_action( 'wp', 'ocellaris_remove_astra_footer' );

/**
 * Render custom footer template.
 */
function ocellaris_custom_footer_markup() {
	get_template_part( 'template-parts/footer-custom' );
}
add_action( 'astra_footer', 'ocellaris_custom_footer_markup' );

/**
 * Enqueue custom footer styles.
 */
function ocellaris_custom_footer_assets() {
	wp_enqueue_style(
		'ocellaris-footer-css',
		get_stylesheet_directory_uri() . '/assets/css/custom-footer.css',
		array(),
		CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'ocellaris_custom_footer_assets' );

/**
 * Register navigation menu locations.
 */
function ocellaris_register_menus() {
	register_nav_menus(
		array(
			'sidebar-menu'    => __( 'Ocellaris Main Menu: Sidebar Menu (Categorías)', 'ocellaris-custom-astra' ),
			'quick-links-menu' => __( 'Ocellaris Main Menu: Quick Links Menu', 'ocellaris-custom-astra' ),
			'footer-about'    => __( 'Ocellaris Footer: Acerca de Ocellaris', 'ocellaris-custom-astra' ),
			'footer-support'  => __( 'Ocellaris Footer: Atención al Cliente', 'ocellaris-custom-astra' ),
			'footer-resources' => __( 'Ocellaris Footer: Recursos', 'ocellaris-custom-astra' ),
		)
	);
}
add_action( 'init', 'ocellaris_register_menus' );

/**
 * Add data-cat-id to product_cat links in sidebar menu.
 */
function ocellaris_product_cat_menu_link_attrs( $atts, $item, $args ) {
	if ( isset( $args->theme_location ) && $args->theme_location === 'sidebar-menu' && isset( $item->object ) && $item->object === 'product_cat' ) {
		$atts['data-cat-id'] = (string) $item->object_id;
	}
	return $atts;
}
add_filter( 'nav_menu_link_attributes', 'ocellaris_product_cat_menu_link_attrs', 10, 3 );

/**
 * AJAX endpoint: get subcategories (children + grandchildren) for a product_cat.
 */
function ocellaris_get_subcategories() {
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'ocellaris_menu_nonce' ) ) {
		wp_send_json_error( array( 'message' => 'Invalid nonce' ), 403 );
	}

	$cat_id = isset( $_POST['catId'] ) ? absint( $_POST['catId'] ) : 0;
	if ( ! $cat_id ) {
		wp_send_json_error( array( 'message' => 'Invalid category ID' ), 400 );
	}

	$parent = get_term( $cat_id, 'product_cat' );
	if ( ! $parent || is_wp_error( $parent ) ) {
		wp_send_json_error( array( 'message' => 'Category not found' ), 404 );
	}

	$children = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'parent'     => $cat_id,
		)
	);

	$groups = array();

	foreach ( $children as $child ) {
		$grandchildren = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
				'parent'     => $child->term_id,
			)
		);

		if ( ! empty( $grandchildren ) ) {
			$items = array();
			foreach ( $grandchildren as $gc ) {
				$items[] = array(
					'title' => $gc->name,
					'link'  => get_term_link( $gc ),
				);
			}
			$groups[] = array(
				'title' => $child->name,
				'items' => $items,
			);
		} else {
			$groups[] = array(
				'title' => '',
				'items' => array(
					array(
						'title' => $child->name,
						'link'  => get_term_link( $child ),
					),
				),
			);
		}
	}

	wp_send_json_success(
		array(
			'title'  => $parent->name,
			'groups' => $groups,
		)
	);
}
add_action( 'wp_ajax_ocellaris_get_subcategories', 'ocellaris_get_subcategories' );
add_action( 'wp_ajax_nopriv_ocellaris_get_subcategories', 'ocellaris_get_subcategories' );

/**
 * Ensure sidebar categories menu exists and is populated.
 */
function ocellaris_ensure_sidebar_menu() {
	$locations = get_theme_mod( 'nav_menu_locations', array() );
	$menu_id   = isset( $locations['sidebar-menu'] ) ? (int) $locations['sidebar-menu'] : 0;

	if ( ! $menu_id || ! wp_get_nav_menu_object( $menu_id ) ) {
		$menu_obj = wp_get_nav_menu_object( 'Ocellaris Categorías' );
		if ( ! $menu_obj ) {
			$menu_id = wp_create_nav_menu( 'Ocellaris Categorías' );
		} else {
			$menu_id = (int) $menu_obj->term_id;
		}
		$locations['sidebar-menu'] = $menu_id;
		set_theme_mod( 'nav_menu_locations', $locations );
	}

	$items = wp_get_nav_menu_items( $menu_id );
	if ( empty( $items ) ) {
		$top_cats = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
				'parent'     => 0,
			)
		);

		if ( ! is_wp_error( $top_cats ) ) {
			foreach ( $top_cats as $cat ) {
				wp_update_nav_menu_item(
					$menu_id,
					0,
					array(
						'menu-item-object'    => 'product_cat',
						'menu-item-object-id' => $cat->term_id,
						'menu-item-type'      => 'taxonomy',
						'menu-item-title'     => $cat->name,
						'menu-item-url'       => get_term_link( $cat ),
						'menu-item-status'    => 'publish',
					)
				);
			}
		}
	}
}
add_action( 'admin_init', 'ocellaris_ensure_sidebar_menu' );
