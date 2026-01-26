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

?>
<li <?php wc_product_class( 'featured-product-item ocellaris-catalog-product', $product ); ?>>
	
	<div class="featured-product-image">
		<a href="<?php echo esc_url( get_permalink() ); ?>" class="woocommerce-LoopProduct-link woocommerce-loop-product__link">
			<?php echo woocommerce_get_product_thumbnail(); ?>
		</a>
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