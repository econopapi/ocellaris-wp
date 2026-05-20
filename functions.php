<?php
/**
 * Ocellaris Custom Astra Theme functions and definitions
 * 
 * Desarrollado por Daniel Limón - <dani@dlimon.net>
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

// /**
//  * TEMPORAL: REDIRECCIÓN DE TODO LO RELACIONADO A WOOCOMMERCE AL ECOMMERCE ANTERIOR
//  * Esto es una solución temporal para poder deplegar este desarrollo en el dominio definitivo,
//  * mientras se resuelve el tema de la pasarela de pago.
//  */
// function ocellaris_redirect_woocommerce_pages() {
// 	$current_store = "https://ocellaris.com.mx/productos";
// 	if(is_shop() || is_product() || is_product_tag() || is_cart() ||
// 	is_checkout() || is_account_page() || is_wc_endpoint_url() || is_woocommerce()){
// 		wp_redirect($current_store, 302); // 302, redirección temporal
// 		exit;
// 	}
// }
// add_action('template_redirect', 'ocellaris_redirect_woocommerce_pages');

/**
 * OCELLARIS CUSTOM HEADER
 * Implementación modular en includes/theme/layout.php
 */

/**
 * OCELLARIS CUSTOM CATEGORY AND BRANDS BLOCKS
 * Implementación modular en includes/blocks/brands-categories.php
 */

/**
 * OCELLARIS CATALOG FILTERS
 * Implementación modular en includes/woocommerce/catalog-filters.php
 */


/**
 * OCELLARIS CUSTOM FEATURED PRODUCTS BLOCK
 * Implementación modular en includes/blocks/featured-products.php
 */

/**
 * OCELLARIS CUSTOM TOP TEXT BAR
 * Implementación modular en includes/admin/text-bar.php
 */


/**
 * Eliminar imágenes asociadas al producto al borrar un producto
 */
add_action('before_delete_post', 'ocellaris_delete_product_images', 10, 1);

function ocellaris_delete_product_images($post_id) {

    // Solo productos
    if (get_post_type($post_id) !== 'product') {
        return;
    }

    // Evitar ejecuciones duplicadas
    if (wp_is_post_revision($post_id)) {
        return;
    }

    // Imagen destacada
    $thumbnail_id = get_post_thumbnail_id($post_id);
    if ($thumbnail_id) {
        wp_delete_attachment($thumbnail_id, true);
    }

    // Galería del producto
    $gallery_ids = get_post_meta($post_id, '_product_image_gallery', true);

    if (!empty($gallery_ids)) {
        $gallery_ids = explode(',', $gallery_ids);

        foreach ($gallery_ids as $image_id) {
            wp_delete_attachment((int) $image_id, true);
        }
    }
}

// Header menu helpers, ajax endpoint and sidebar menu bootstrap moved to includes/theme/layout.php.

/**
 * OCELLARIS CHECKOUT CUSTOMIZATIONS
 * Implementación modular en includes/woocommerce/checkout.php
 */

/**
 * OCELLARIS PRODUCT CATALOG CUSTOMIZATIONS
 * Implementación modular en includes/woocommerce/catalog-layout.php
 */


/**
 * ==========================================================================
 * OCELLARIS MSI PROMOTIONS MODULE
 * Sistema de gestión de Meses Sin Intereses (MSI) por pasarela de pago.
 * Permite configurar qué productos son elegibles para MSI y a cuántos meses.
 * ==========================================================================
 */

// Include admin page
require_once get_stylesheet_directory() . '/includes/theme/layout.php';
require_once get_stylesheet_directory() . '/includes/blocks/brands-categories.php';
require_once get_stylesheet_directory() . '/includes/blocks/featured-products.php';
require_once get_stylesheet_directory() . '/includes/admin/text-bar.php';
require_once get_stylesheet_directory() . '/includes/msi-promotions/admin-page.php';
require_once get_stylesheet_directory() . '/includes/msi-promotions/frontend.php';
require_once get_stylesheet_directory() . '/includes/woocommerce/checkout.php';
require_once get_stylesheet_directory() . '/includes/woocommerce/catalog-layout.php';
require_once get_stylesheet_directory() . '/includes/woocommerce/catalog-filters.php';
require_once get_stylesheet_directory() . '/includes/admin/ocellaris-admin-hub.php';

/**
 * END OF OCELLARIS MSI PROMOTIONS MODULE
 */

/**
 * Checkout field and shipping behavior moved to includes/woocommerce/checkout.php.
 */
