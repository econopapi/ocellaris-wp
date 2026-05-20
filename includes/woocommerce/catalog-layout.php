<?php
/**
 * Ocellaris WooCommerce Catalog Layout Module
 *
 * @package Ocellaris Custom Astra
 * @since 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get product brand name from various taxonomies.
 */
function ocellaris_get_product_brand( $product_id = null ) {
	if ( ! $product_id ) {
		global $product;
		if ( ! $product ) {
			return '';
		}
		$product_id = $product->get_id();
	}

	$brand_taxonomies = array( 'pa_brand', 'product_brand', 'brand', 'pa_marca' );

	foreach ( $brand_taxonomies as $taxonomy ) {
		$brand_terms = wp_get_post_terms( $product_id, $taxonomy );
		if ( ! empty( $brand_terms ) && ! is_wp_error( $brand_terms ) ) {
			return $brand_terms[0]->name;
		}
	}

	return '';
}

/**
 * Enqueue catalog styles if we're on a shop page.
 */
function ocellaris_enqueue_catalog_styles() {
	if ( is_shop() || is_product_category() || is_product_tag() || is_product_taxonomy() || is_search() ) {
		wp_enqueue_style( 'ocellaris-catalog-styles', get_stylesheet_uri(), array(), CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION );
	}
}
add_action( 'wp_enqueue_scripts', 'ocellaris_enqueue_catalog_styles', 20 );

/**
 * Force WooCommerce to use our custom product loop structure.
 */
function ocellaris_custom_loop_structure() {
	if ( is_shop() || is_product_category() || is_product_tag() || is_product_taxonomy() || is_search() ) {
		add_action( 'wp_head', 'ocellaris_force_catalog_styles', 999 );
	}
}
add_action( 'wp', 'ocellaris_custom_loop_structure' );

/**
 * Force catalog styles to override any conflicting CSS.
 */
function ocellaris_force_catalog_styles() {
	?>
	<style id="ocellaris-catalog-override">
	/* Force catalog grid layout with maximum specificity */
	body.woocommerce ul.products,
	body.woocommerce-page ul.products,
	.woocommerce ul.products,
	.woocommerce-page ul.products {
		display: grid !important;
		grid-template-columns: repeat(3, 1fr) !important;
		gap: 30px !important;
		margin: 40px auto !important;
		max-width: 1200px !important;
		padding: 0 20px !important;
		list-style: none !important;
		width: 100% !important;
		clear: both !important;
	}

	/* Force 3 column layout - except for search page which gets 4 */
	body.woocommerce ul.products.columns-4,
	body.woocommerce-page ul.products.columns-4,
	.woocommerce ul.products.columns-4,
	.woocommerce-page ul.products.columns-4 {
		grid-template-columns: repeat(3, 1fr) !important;
	}

	/* Special override for search page - allow 4 columns */
	body.search .woocommerce ul.products.columns-4,
	body.search.woocommerce-page ul.products.columns-4 {
		grid-template-columns: repeat(4, 1fr) !important;
		gap: 20px !important;
		max-width: 100% !important;
	}

	@media (max-width: 767px) {
		body.woocommerce ul.products,
		body.woocommerce-page ul.products,
		.woocommerce ul.products,
		.woocommerce-page ul.products,
		body.search .woocommerce ul.products.columns-4,
		body.search.woocommerce-page ul.products.columns-4 {
			grid-template-columns: repeat(2, 1fr) !important;
			gap: 20px !important;
			padding: 0 15px !important;
		}
	}

	@media (min-width: 768px) and (max-width: 1199px) {
		/* Tablet view - 3 columns for search 4-column layouts */
		body.search .woocommerce ul.products.columns-4,
		body.search.woocommerce-page ul.products.columns-4 {
			grid-template-columns: repeat(3, 1fr) !important;
		}
	}

	@media (max-width: 480px) {
		body.woocommerce ul.products,
		body.woocommerce-page ul.products,
		.woocommerce ul.products,
		.woocommerce-page ul.products {
			grid-template-columns: 1fr !important;
			gap: 25px !important;
			padding: 0 10px !important;
		}
	}

	/* Force product item styling with maximum specificity */
	body.woocommerce ul.products li.product,
	body.woocommerce-page ul.products li.product,
	.woocommerce ul.products li.product,
	.woocommerce-page ul.products li.product {
		background: #fff !important;
		border-radius: 8px !important;
		overflow: hidden !important;
		position: relative !important;
		transition: all 0.3s ease !important;
		box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
		border: 1px solid #e0e0e0 !important;
		margin: 0 !important;
		padding: 0 !important;
		width: auto !important;
		float: none !important;
		display: block !important;
		min-height: 450px !important;
	}

	body.woocommerce ul.products li.product:hover,
	body.woocommerce-page ul.products li.product:hover,
	.woocommerce ul.products li.product:hover,
	.woocommerce-page ul.products li.product:hover {
		transform: translateY(-5px) !important;
		box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
	}

	/* Product image container */
	body.woocommerce ul.products li.product .featured-product-image,
	body.woocommerce-page ul.products li.product .featured-product-image,
	.woocommerce ul.products li.product .featured-product-image,
	.woocommerce-page ul.products li.product .featured-product-image {
		height: 250px !important;
		overflow: hidden !important;
		position: relative !important;
	}

	/* Smaller image height for 4 columns in search */
	body.search .woocommerce ul.products.columns-4 li.product .featured-product-image,
	body.search.woocommerce-page ul.products.columns-4 li.product .featured-product-image {
		height: 250px !important;
	}

	/* Ensure proper image fit for search 4 columns */
	body.search .woocommerce ul.products.columns-4 li.product .featured-product-image img,
	body.search.woocommerce-page ul.products.columns-4 li.product .featured-product-image img {
		width: 100% !important;
		height: 100% !important;
		object-fit: contain !important;
		object-position: center !important;
		background: #f8f9fa !important;
	}

	body.woocommerce ul.products li.product .featured-product-image img,
	body.woocommerce-page ul.products li.product .featured-product-image img,
	.woocommerce ul.products li.product .featured-product-image img,
	.woocommerce-page ul.products li.product .featured-product-image img {
		width: 100% !important;
		height: 100% !important;
		object-fit: cover !important;
	}

	/* Sale badge styling */
	body.woocommerce ul.products li.product .featured-product-badge,
	body.woocommerce-page ul.products li.product .featured-product-badge,
	.woocommerce ul.products li.product .featured-product-badge,
	.woocommerce-page ul.products li.product .featured-product-badge {
		position: absolute !important;
		top: 12px !important;
		right: 12px !important;
		z-index: 10 !important;
	}

	body.woocommerce ul.products li.product .sale-badge,
	body.woocommerce-page ul.products li.product .sale-badge,
	.woocommerce ul.products li.product .sale-badge,
	.woocommerce-page ul.products li.product .sale-badge {
		background: #dc2626 !important;
		color: white !important;
		padding: 6px 12px !important;
		font-size: 11px !important;
		font-weight: bold !important;
		text-transform: uppercase !important;
		display: inline-block !important;
		position: relative !important;
		margin-left: -8px !important;
		margin-top: -8px !important;
		box-shadow: 0 2px 8px rgba(220, 38, 38, 0.3) !important;
		border-radius: 4px 0 4px 0 !important;
	}

	/* Triangulo derecho (punta del liston) */
	body.woocommerce ul.products li.product .sale-badge::after,
	body.woocommerce-page ul.products li.product .sale-badge::after,
	.woocommerce ul.products li.product .sale-badge::after,
	.woocommerce-page ul.products li.product .sale-badge::after {
		content: '' !important;
		position: absolute !important;
		right: -12px !important;
		top: 0 !important;
		width: 0 !important;
		height: 0 !important;
		border-top: 50% solid transparent !important;
		border-bottom: 50% solid transparent !important;
		border-left: 12px solid #dc2626 !important;
	}

	/* Corte en forma de V a la izquierda */
	body.woocommerce ul.products li.product .sale-badge::before,
	body.woocommerce-page ul.products li.product .sale-badge::before,
	.woocommerce ul.products li.product .sale-badge::before,
	.woocommerce-page ul.products li.product .sale-badge::before {
		content: '' !important;
		position: absolute !important;
		left: 0 !important;
		bottom: -6px !important;
		width: 0 !important;
		height: 0 !important;
		border-left: 6px solid transparent !important;
		border-right: 6px solid transparent !important;
		border-top: 6px solid #a61b1b !important;
	}

	body.woocommerce ul.products li.product .save-text,
	body.woocommerce-page ul.products li.product .save-text,
	.woocommerce ul.products li.product .save-text,
	.woocommerce-page ul.products li.product .save-text {
		font-size: 10px !important;
		line-height: 1 !important;
		display: block !important;
	}

	body.woocommerce ul.products li.product .discount-percent,
	body.woocommerce-page ul.products li.product .discount-percent,
	.woocommerce ul.products li.product .discount-percent,
	.woocommerce-page ul.products li.product .discount-percent {
		font-size: 15px !important;
		font-weight: 900 !important;
		line-height: 1 !important;
		display: block !important;
		text-align: center !important;
		width: 100% !important;
	}

	/* MSI badge styling */
	body.woocommerce ul.products li.product .msi-badge-container,
	body.woocommerce-page ul.products li.product .msi-badge-container,
	.woocommerce ul.products li.product .msi-badge-container,
	.woocommerce-page ul.products li.product .msi-badge-container {
		position: absolute !important;
		top: 12px !important;
		left: 12px !important;
		right: auto !important;
		z-index: 10 !important;
	}

	body.woocommerce ul.products li.product .msi-badge-container.has-sale-badge,
	body.woocommerce-page ul.products li.product .msi-badge-container.has-sale-badge,
	.woocommerce ul.products li.product .msi-badge-container.has-sale-badge,
	.woocommerce-page ul.products li.product .msi-badge-container.has-sale-badge {
		top: 12px !important;
		left: 12px !important;
	}

	body.woocommerce ul.products li.product .msi-badge,
	body.woocommerce-page ul.products li.product .msi-badge,
	.woocommerce ul.products li.product .msi-badge,
	.woocommerce-page ul.products li.product .msi-badge {
		background: var(--ocellaris-orange, #f15a22) !important;
		color: white !important;
		padding: 4px 8px !important;
		border-radius: 14px !important;
		font-size: 9px !important;
		font-weight: bold !important;
		text-transform: uppercase !important;
		display: inline-flex !important;
		align-items: center !important;
		gap: 3px !important;
		box-shadow: 0 2px 8px rgba(241, 90, 34, 0.3) !important;
		letter-spacing: 0.3px !important;
		max-width: 100px !important;
		line-height: 1.2 !important;
		text-align: center !important;
	}

	body.woocommerce ul.products li.product .msi-badge::before,
	body.woocommerce-page ul.products li.product .msi-badge::before,
	.woocommerce ul.products li.product .msi-badge::before,
	.woocommerce-page ul.products li.product .msi-badge::before {
		content: '\1F4B3' !important;
		font-size: 11px !important;
		flex-shrink: 0 !important;
	}

	/* Product content container */
	body.woocommerce ul.products li.product .featured-product-content,
	body.woocommerce-page ul.products li.product .featured-product-content,
	.woocommerce ul.products li.product .featured-product-content,
	.woocommerce-page ul.products li.product .featured-product-content {
		padding: 15px !important;
		text-align: center !important;
		min-height: 180px !important;
		display: flex !important;
		flex-direction: column !important;
		justify-content: space-between !important;
	}

	/* Make sure text is visible */
	body.woocommerce ul.products li.product .featured-product-content *,
	body.woocommerce-page ul.products li.product .featured-product-content *,
	.woocommerce ul.products li.product .featured-product-content *,
	.woocommerce-page ul.products li.product .featured-product-content * {
		color: inherit !important;
		display: block !important;
		visibility: visible !important;
		opacity: 1 !important;
	}

	/* Product brand */
	body.woocommerce ul.products li.product .featured-product-brand,
	body.woocommerce-page ul.products li.product .featured-product-brand,
	.woocommerce ul.products li.product .featured-product-brand,
	.woocommerce-page ul.products li.product .featured-product-brand {
		color: #666 !important;
		font-size: 12px !important;
		margin-bottom: 8px !important;
		font-weight: 500 !important;
		display: block !important;
		visibility: visible !important;
	}

	/* Product title */
	body.woocommerce ul.products li.product .featured-product-title,
	body.woocommerce ul.products li.product h2.woocommerce-loop-product__title,
	body.woocommerce-page ul.products li.product .featured-product-title,
	body.woocommerce-page ul.products li.product h2.woocommerce-loop-product__title,
	.woocommerce ul.products li.product .featured-product-title,
	.woocommerce ul.products li.product h2.woocommerce-loop-product__title,
	.woocommerce-page ul.products li.product .featured-product-title,
	.woocommerce-page ul.products li.product h2.woocommerce-loop-product__title {
		color: #333 !important;
		font-size: 14px !important;
		font-weight: 600 !important;
		margin: 0 0 12px 0 !important;
		line-height: 1.3 !important;
		height: auto !important;
		min-height: 36px !important;
		display: block !important;
		visibility: visible !important;
	}

	body.woocommerce ul.products li.product .featured-product-title a,
	body.woocommerce ul.products li.product h2.woocommerce-loop-product__title a,
	body.woocommerce-page ul.products li.product .featured-product-title a,
	body.woocommerce-page ul.products li.product h2.woocommerce-loop-product__title a,
	.woocommerce ul.products li.product .featured-product-title a,
	.woocommerce ul.products li.product h2.woocommerce-loop-product__title a,
	.woocommerce-page ul.products li.product .featured-product-title a,
	.woocommerce-page ul.products li.product h2.woocommerce-loop-product__title a {
		color: #333 !important;
		text-decoration: none !important;
		display: block !important;
	}

	/* Force orange button styling for catalog products */
	body.woocommerce ul.products li.product .button,
	body.woocommerce ul.products li.product .add_to_cart_button,
	body.woocommerce-page ul.products li.product .button,
	body.woocommerce-page ul.products li.product .add_to_cart_button,
	.woocommerce ul.products li.product .button,
	.woocommerce ul.products li.product .add_to_cart_button,
	.woocommerce-page ul.products li.product .button,
	.woocommerce-page ul.products li.product .add_to_cart_button {
		background: #FF6B35 !important;
		color: white !important;
		border: none !important;
		padding: 10px 20px !important;
		border-radius: 25px !important;
		font-weight: bold !important;
		text-transform: uppercase !important;
		font-size: 12px !important;
		letter-spacing: 1px !important;
		width: 100% !important;
		transition: all 0.3s ease !important;
		text-decoration: none !important;
		display: inline-block !important;
		text-align: center !important;
		box-sizing: border-box !important;
		line-height: 1.2 !important;
		min-height: 36px !important;
	}

	body.woocommerce ul.products li.product .button:hover,
	body.woocommerce ul.products li.product .add_to_cart_button:hover,
	body.woocommerce-page ul.products li.product .button:hover,
	body.woocommerce-page ul.products li.product .add_to_cart_button:hover,
	.woocommerce ul.products li.product .button:hover,
	.woocommerce ul.products li.product .add_to_cart_button:hover,
	.woocommerce-page ul.products li.product .button:hover,
	.woocommerce-page ul.products li.product .add_to_cart_button:hover {
		background: #e55a2b !important;
		transform: translateY(-2px) !important;
		box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3) !important;
	}

	/* Force price styling with stronger anti-wrap rules */
	body.woocommerce ul.products li.product .price,
	body.woocommerce-page ul.products li.product .price,
	.woocommerce ul.products li.product .price,
	.woocommerce-page ul.products li.product .price,
	body.woocommerce ul.products li.product .featured-product-price .price,
	body.woocommerce-page ul.products li.product .featured-product-price .price,
	.woocommerce ul.products li.product .featured-product-price .price,
	.woocommerce-page ul.products li.product .featured-product-price .price,
	body.woocommerce ul.products li.product .featured-product-price,
	body.woocommerce-page ul.products li.product .featured-product-price,
	.woocommerce ul.products li.product .featured-product-price,
	.woocommerce-page ul.products li.product .featured-product-price {
		color: #FF6B35 !important;
		font-size: 18px !important;
		font-weight: bold !important;
		text-align: center !important;
		margin-bottom: 12px !important;
		display: flex !important;
		justify-content: center !important;
		align-items: center !important;
		white-space: nowrap !important;
		overflow: visible !important;
		word-break: keep-all !important;
		hyphens: none !important;
		flex-wrap: nowrap !important;
		min-width: 0 !important;
	}

	/* Prevent any wrapping on price components */
	body.woocommerce ul.products li.product .price *,
	body.woocommerce-page ul.products li.product .price *,
	.woocommerce ul.products li.product .price *,
	.woocommerce-page ul.products li.product .price *,
	body.woocommerce ul.products li.product .featured-product-price *,
	body.woocommerce-page ul.products li.product .featured-product-price *,
	.woocommerce ul.products li.product .featured-product-price *,
	.woocommerce-page ul.products li.product .featured-product-price * {
		white-space: nowrap !important;
		display: inline !important;
		color: inherit !important;
		font-size: inherit !important;
		font-weight: inherit !important;
		word-break: keep-all !important;
		hyphens: none !important;
		overflow: visible !important;
		flex-shrink: 0 !important;
	}

	/* Price container */
	body.woocommerce ul.products li.product .featured-product-price,
	body.woocommerce-page ul.products li.product .featured-product-price,
	.woocommerce ul.products li.product .featured-product-price,
	.woocommerce-page ul.products li.product .featured-product-price {
		margin-bottom: 12px !important;
		min-height: 30px !important;
		display: flex !important;
		align-items: center !important;
		justify-content: center !important;
	}

	/* Add to cart container */
	body.woocommerce ul.products li.product .featured-add-to-cart,
	body.woocommerce ul.products li.product .catalog-add-to-cart,
	body.woocommerce-page ul.products li.product .featured-add-to-cart,
	body.woocommerce-page ul.products li.product .catalog-add-to-cart,
	.woocommerce ul.products li.product .featured-add-to-cart,
	.woocommerce ul.products li.product .catalog-add-to-cart,
	.woocommerce-page ul.products li.product .featured-add-to-cart,
	.woocommerce-page ul.products li.product .catalog-add-to-cart {
		width: 100% !important;
		margin-top: auto !important;
	}

	/* Force remove any float or flex that might interfere */
	body.woocommerce ul.products::before,
	body.woocommerce ul.products::after,
	body.woocommerce-page ul.products::before,
	body.woocommerce-page ul.products::after,
	.woocommerce ul.products::before,
	.woocommerce ul.products::after,
	.woocommerce-page ul.products::before,
	.woocommerce-page ul.products::after {
		display: none !important;
	}

	/* Hide default WooCommerce result count and ordering */
	.woocommerce-result-count,
	.woocommerce-ordering {
		margin-bottom: 20px;
	}
	</style>
	<script>
	jQuery(document).ready(function($) {
		// Force grid layout with JavaScript as backup
		setTimeout(function() {
			$('.woocommerce ul.products, .woocommerce-page ul.products').each(function() {
				$(this).css({
					'display': 'grid',
					'grid-template-columns': 'repeat(3, 1fr)',
					'gap': '30px',
					'margin': '40px auto',
					'max-width': '1200px',
					'padding': '0 20px',
					'list-style': 'none'
				});
			});

			// Fix price wrapping specifically
			$('.woocommerce ul.products li.product .price, .woocommerce ul.products li.product .featured-product-price').each(function() {
				$(this).css({
					'white-space': 'nowrap',
					'display': 'flex',
					'justify-content': 'center',
					'align-items': 'center',
					'flex-wrap': 'nowrap'
				});

				// Ensure all child elements stay inline
				$(this).find('*').css({
					'white-space': 'nowrap',
					'display': 'inline',
					'word-break': 'keep-all'
				});
			});
		}, 100);
	});
	</script>
	<?php
}

/**
 * Ensure WooCommerce hooks are properly loaded for catalog display.
 */
function ocellaris_ensure_woocommerce_hooks() {
	if ( is_shop() || is_product_category() || is_product_tag() || is_product_taxonomy() ) {
		if ( ! has_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price' ) ) {
			add_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
		}

		if ( ! has_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart' ) ) {
			add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
		}
	}
}
add_action( 'wp', 'ocellaris_ensure_woocommerce_hooks' );

/**
 * Set default shop columns to 3 for better content display.
 */
function ocellaris_shop_columns() {
	return 3;
}
add_filter( 'loop_shop_columns', 'ocellaris_shop_columns', 999 );

/**
 * Force 3 columns in all WooCommerce product listings.
 */
function ocellaris_force_3_columns() {
	if ( is_shop() || is_product_category() || is_product_tag() || is_product_taxonomy() ) {
		global $woocommerce_loop;
		$woocommerce_loop['columns'] = 3;
	}
}
add_action( 'woocommerce_before_shop_loop', 'ocellaris_force_3_columns', 5 );
