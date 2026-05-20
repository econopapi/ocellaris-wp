<?php
/**
 * Ocellaris MSI Promotions - Frontend/Checkout logic
 *
 * @package Ocellaris Custom Astra
 * @since 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if a product is eligible for MSI (Meses Sin Intereses)
 *
 * @param int $product_id The product ID to check.
 * @return bool True if the product is in the MSI whitelist and MSI is enabled.
 */
function ocellaris_is_product_msi_eligible( $product_id ) {
	$enabled = get_option( 'ocellaris_msi_mp_enabled', '0' );
	if ( $enabled !== '1' ) {
		return false;
	}

	$msi_products = get_option( 'ocellaris_msi_mp_products', array() );
	if ( ! is_array( $msi_products ) || empty( $msi_products ) ) {
		return false;
	}

	return isset( $msi_products[ $product_id ] );
}

/**
 * Enqueue MSI checkout control scripts and styles.
 */
function ocellaris_msi_enqueue_checkout_assets() {
	if ( ! is_checkout() ) {
		return;
	}

	$enabled = get_option( 'ocellaris_msi_mp_enabled', '0' );
	if ( $enabled !== '1' ) {
		return;
	}

	$msi_products = get_option( 'ocellaris_msi_mp_products', array() );
	if ( ! is_array( $msi_products ) ) {
		$msi_products = array();
	}

	$cart_analysis = ocellaris_msi_analyze_cart( $msi_products );

	wp_enqueue_style(
		'ocellaris-msi-checkout-css',
		get_stylesheet_directory_uri() . '/assets/css/msi-checkout-control.css',
		array(),
		CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION
	);

	wp_enqueue_script(
		'ocellaris-msi-checkout-js',
		get_stylesheet_directory_uri() . '/assets/js/msi-checkout-control.js',
		array( 'jquery' ),
		CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION,
		true
	);

	$mixed_msg = get_option(
		'ocellaris_msi_mp_mixed_cart_message',
		'Algunos productos de tu carrito no son elegibles para Meses Sin Intereses. Para comprar a MSI, retira del carrito los productos que no participan en esta promocion.'
	);

	wp_localize_script(
		'ocellaris-msi-checkout-js',
		'OcellarisMSI',
		array(
			'enabled'          => true,
			'msiStatus'        => $cart_analysis['status'],
			'allowedMonths'    => $cart_analysis['allowed_months'],
			'mixedCartMessage' => $mixed_msg,
		)
	);
}
add_action( 'wp_enqueue_scripts', 'ocellaris_msi_enqueue_checkout_assets' );

/**
 * Analyze cart contents to determine MSI eligibility.
 *
 * @param array $msi_products The MSI product configuration from admin.
 * @return array {
 *     @type string $status         'all_msi' | 'mixed' | 'none_msi'
 *     @type array  $allowed_months Array of month values allowed (always includes 1)
 * }
 */
function ocellaris_msi_analyze_cart( $msi_products ) {
	$result = array(
		'status'         => 'none_msi',
		'allowed_months' => array( 1 ),
	);

	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return $result;
	}

	$cart_items    = WC()->cart->get_cart();
	$has_msi       = false;
	$has_non_msi   = false;
	$common_months = null;

	foreach ( $cart_items as $cart_item ) {
		$product_id   = $cart_item['product_id'];
		$variation_id = isset( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : 0;

		$is_msi_product = false;
		$product_months = array();

		if ( isset( $msi_products[ $product_id ] ) ) {
			$is_msi_product = true;
			$product_months = $msi_products[ $product_id ]['months'];
		} elseif ( $variation_id > 0 && isset( $msi_products[ $variation_id ] ) ) {
			$is_msi_product = true;
			$product_months = $msi_products[ $variation_id ]['months'];
		}

		if ( $is_msi_product ) {
			$has_msi = true;
			if ( $common_months === null ) {
				$common_months = $product_months;
			} else {
				$common_months = array_values( array_intersect( $common_months, $product_months ) );
			}
		} else {
			$has_non_msi = true;
		}
	}

	if ( $has_msi && ! $has_non_msi ) {
		$result['status']         = 'all_msi';
		$result['allowed_months'] = array_merge( array( 1 ), $common_months ?: array() );
		sort( $result['allowed_months'] );
	} elseif ( $has_msi && $has_non_msi ) {
		$result['status']         = 'mixed';
		$result['allowed_months'] = array( 1 );
	} else {
		$result['status']         = 'none_msi';
		$result['allowed_months'] = array( 1 );
	}

	return $result;
}
