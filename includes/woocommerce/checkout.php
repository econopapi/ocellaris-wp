<?php
/**
 * Ocellaris WooCommerce Checkout Module
 *
 * @package Ocellaris Custom Astra
 * @since 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Translate WooCommerce strings to custom Spanish labels.
 */
function ocellaris_translate_woocommerce_texts( $translated_text, $text, $domain ) {
	if ( $domain === 'woocommerce' ) {
		switch ( $text ) {
			case 'Billing details':
			case 'Billing &amp; Shipping':
				$translated_text = 'Detalles de pedido';
				break;
			case 'Detalles de facturación':
				$translated_text = 'Detalles de pedido';
				break;
			case 'My account':
				$translated_text = 'Mi cuenta';
				break;
		}
	}
	return $translated_text;
}
add_filter( 'gettext', 'ocellaris_translate_woocommerce_texts', 20, 3 );

/**
 * Replace My Account title in loops/page titles.
 */
function ocellaris_change_my_account_title( $title, $post_id = null ) {
	if ( function_exists( 'is_wc_endpoint_url' ) && function_exists( 'is_account_page' ) ) {
		if ( is_account_page() && in_the_loop() && is_main_query() ) {
			$title = str_replace( 'My account', 'Mi cuenta', $title );
		}
	}
	return $title;
}
add_filter( 'the_title', 'ocellaris_change_my_account_title', 10, 2 );
add_filter( 'woocommerce_page_title', 'ocellaris_change_my_account_title', 10, 1 );

/**
 * Replace My Account title in document head titles.
 */
function ocellaris_change_account_page_title( $title ) {
	if ( function_exists( 'is_account_page' ) && is_account_page() ) {
		$title = str_replace( 'My account', 'Mi cuenta', $title );
	}
	return $title;
}
add_filter( 'wp_title', 'ocellaris_change_account_page_title', 10, 1 );
add_filter(
	'document_title_parts',
	function( $title_parts ) {
		if ( function_exists( 'is_account_page' ) && is_account_page() && isset( $title_parts['title'] ) ) {
			$title_parts['title'] = str_replace( 'My account', 'Mi cuenta', $title_parts['title'] );
		}
		return $title_parts;
	},
	10,
	1
);

add_filter( 'astra_the_title_enabled', '__return_true' );
add_filter(
	'astra_page_title',
	function( $title ) {
		if ( function_exists( 'is_account_page' ) && is_account_page() ) {
			$title = str_replace( 'My account', 'Mi cuenta', $title );
		}
		return $title;
	},
	10,
	1
);

/**
 * Remove downloads link in My Account navigation.
 */
function ocellaris_remove_downloads_from_account_menu( $items ) {
	unset( $items['downloads'] );
	return $items;
}
add_filter( 'woocommerce_account_menu_items', 'ocellaris_remove_downloads_from_account_menu' );

/**
 * Disable shipping to a different address.
 */
function ocellaris_disable_ship_to_different_address( $ship_to_different_address ) {
	return false;
}
add_filter( 'woocommerce_ship_to_different_address_checked', 'ocellaris_disable_ship_to_different_address' );

/**
 * Hide shipping address section in checkout.
 */
function ocellaris_hide_shipping_address_section() {
	if ( is_checkout() ) {
		?>
		<style>
			#ship-to-different-address,
			.shipping_address {
				display: none !important;
			}
		</style>
		<?php
	}
}
add_action( 'wp_head', 'ocellaris_hide_shipping_address_section' );

/**
 * Hide shipping choices in cart and show custom message style.
 */
function ocellaris_hide_shipping_in_cart() {
	if ( is_cart() ) {
		?>
		<style>
			.cart_totals .woocommerce-shipping-methods,
			.cart_totals .woocommerce-shipping-calculator,
			.cart_totals .woocommerce-shipping-destination {
				display: none !important;
			}

			.ocellaris-shipping-notice {
				color: #666;
				font-style: italic;
				padding: 5px 0;
			}
		</style>
		<?php
	}
}
add_action( 'wp_head', 'ocellaris_hide_shipping_in_cart' );

/**
 * Replace shipping label text in cart.
 */
function ocellaris_custom_cart_shipping_message( $shipping_label ) {
	if ( is_cart() ) {
		return '<span class="ocellaris-shipping-notice">Los costos de envío se calculan en el Checkout de pago.</span>';
	}
	return $shipping_label;
}
add_filter( 'woocommerce_cart_shipping_method_full_label', 'ocellaris_custom_cart_shipping_message', 10, 1 );

add_filter( 'woocommerce_shipping_show_shipping_calculator', '__return_false' );

/**
 * Replace cart shipping block via JS after cart updates.
 */
function ocellaris_replace_cart_shipping_content() {
	if ( is_cart() ) {
		?>
		<script>
		(function($) {
			function replaceShippingContent() {
				var $shippingTd = $('.cart_totals .woocommerce-shipping-totals td[data-title="Envío"], .cart_totals .woocommerce-shipping-totals td[data-title="Shipping"]');
				if ($shippingTd.length) {
					$shippingTd.html('<span class="ocellaris-shipping-notice">Los costos de envío se calculan en el Checkout de pago.</span>');
				}
			}

			$(document).ready(replaceShippingContent);
			$(document.body).on('updated_cart_totals', replaceShippingContent);
			$(document.body).on('updated_wc_div', replaceShippingContent);
		})(jQuery);
		</script>
		<?php
	}
}
add_action( 'wp_footer', 'ocellaris_replace_cart_shipping_content' );

/**
 * Enqueue checkout shipping filter script.
 */
function ocellaris_checkout_shipping_filter_script() {
	$is_checkout_page = is_checkout()
		|| ( isset( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], '/checkout' ) !== false )
		|| ( function_exists( 'wc_get_page_id' ) && is_page( wc_get_page_id( 'checkout' ) ) );

	if ( $is_checkout_page ) {
		wp_enqueue_script(
			'ocellaris-checkout-shipping-filter',
			get_stylesheet_directory_uri() . '/assets/js/checkout-shipping-filter.js',
			array( 'jquery' ),
			CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION,
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'ocellaris_checkout_shipping_filter_script' );

/**
 * Customize default checkout address field labels.
 */
function ocellaris_custom_checkout_field_labels( $fields ) {
	if ( isset( $fields['city']['label'] ) ) {
		$fields['city']['label'] = 'Alcaldía/Municipio';
	}
	if ( isset( $fields['state']['label'] ) ) {
		$fields['state']['label'] = 'Estado';
	}
	return $fields;
}
add_filter( 'woocommerce_default_address_fields', 'ocellaris_custom_checkout_field_labels' );

/**
 * Persist checkout fields in localStorage.
 */
function ocellaris_checkout_persistence_assets() {
	if ( ! is_checkout() ) {
		return;
	}

	wp_enqueue_script(
		'ocellaris-checkout-persistence',
		get_stylesheet_directory_uri() . '/assets/js/checkout-field-persistence.js',
		array( 'jquery' ),
		CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'ocellaris_checkout_persistence_assets' );
