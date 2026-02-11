<?php
/**
 * The template for displaying product content within loops
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-product.php.
 *
 * @package Ocellaris Custom Astra
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

// Ensure visibility.
if ( empty( $product ) || ! $product->is_visible() ) {
	return;
}

// Get product brand using the helper function
$brand_name = ocellaris_get_product_brand( $product->get_id() );

// Calculate discount percentage if on sale
$discount_percentage = 0;
$is_on_sale = $product->is_on_sale();
if ( $is_on_sale ) {
	$regular_price = (float) $product->get_regular_price();
	$sale_price = (float) $product->get_sale_price();
	if ( $regular_price && $sale_price && $regular_price > $sale_price ) {
		$discount_percentage = round( ( ( $regular_price - $sale_price ) / $regular_price ) * 100 );
	}
}

// Check MSI eligibility
$is_msi_eligible = function_exists( 'ocellaris_is_product_msi_eligible' ) && ocellaris_is_product_msi_eligible( $product->get_id() );

?>
<li <?php wc_product_class( 'featured-product-item ocellaris-catalog-product', $product ); ?>>
	
	<div class="featured-product-image">
		<a href="<?php echo esc_url( get_permalink() ); ?>" class="woocommerce-LoopProduct-link woocommerce-loop-product__link">
			<?php echo woocommerce_get_product_thumbnail(); ?>
		</a>
		
		<?php if ( $is_on_sale && $discount_percentage > 0 ) : ?>
			<div class="featured-product-badge">
				<span class="sale-badge">
					<span class="save-text">DESCUENTO</span>
					<span class="discount-percent"><?php echo $discount_percentage; ?>%</span>
				</span>
			</div>
		<?php endif; ?>

		<?php if ( $is_msi_eligible ) : ?>
			<div class="featured-product-badge msi-badge-container <?php echo ( $is_on_sale && $discount_percentage > 0 ) ? 'has-sale-badge' : ''; ?>">
				<span class="msi-badge">Meses sin intereses</span>
			</div>
		<?php endif; ?>
	</div>

	<div class="featured-product-content">
		
		<?php if ( ! empty( $brand_name ) ) : ?>
			<div class="featured-product-brand"><?php echo esc_html( $brand_name ); ?></div>
		<?php endif; ?>

		<h2 class="woocommerce-loop-product__title featured-product-title">
			<a href="<?php echo esc_url( get_permalink() ); ?>">
				<?php echo get_the_title(); ?>
			</a>
		</h2>

		<div class="featured-product-price">
			<?php echo $product->get_price_html(); ?>
		</div>

		<div class="featured-add-to-cart catalog-add-to-cart">
			<?php
			woocommerce_template_loop_add_to_cart();
			?>
		</div>

	</div>
</li>