<?php
/**
 * Product Loop Start
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/loop/loop-start.php.
 *
 * @package Ocellaris Custom Astra
 * @version 3.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get columns from WooCommerce settings or default to 3
$columns = wc_get_loop_prop( 'columns' );
if ( ! $columns ) {
	$columns = apply_filters( 'loop_shop_columns', 3 );
}

?>
<ul class="products columns-<?php echo esc_attr( $columns ); ?> ocellaris-products-catalog featured-products-grid products-count-<?php echo esc_attr( $columns ); ?>">