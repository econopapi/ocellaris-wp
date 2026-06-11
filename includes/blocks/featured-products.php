<?php
/**
 * Ocellaris Blocks Module - Featured Products
 *
 * @package Ocellaris Custom Astra
 * @since 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Ocellaris Featured Products Block.
 */
function ocellaris_register_featured_products_block() {
	wp_register_script(
		'ocellaris-featured-products-block',
		get_stylesheet_directory_uri() . '/blocks/featured-products/block.js',
		array('wp-blocks', 'wp-element', 'wp-components', 'wp-data', 'wp-api-fetch', 'wp-url'),
		CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION
	);

	wp_register_style(
		'ocellaris-featured-products-block-editor',
		get_stylesheet_directory_uri() . '/blocks/featured-products/editor.css',
		array('wp-edit-blocks'),
		CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION
	);

	wp_register_style(
		'ocellaris-featured-products-block',
		get_stylesheet_directory_uri() . '/blocks/featured-products/style.css',
		array(),
		CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION
	);

	wp_register_script(
		'ocellaris-featured-products-carousel',
		get_stylesheet_directory_uri() . '/blocks/featured-products/carousel.js',
		array('jquery'),
		CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION,
		true
	);

	register_block_type(
		'ocellaris/featured-products',
		array(
			'editor_script' => 'ocellaris-featured-products-block',
			'editor_style' => 'ocellaris-featured-products-block-editor',
			'style' => 'ocellaris-featured-products-block',
			'render_callback' => 'ocellaris_render_featured_products_block',
			'attributes' => array(
				'title' => array(
					'type' => 'string',
					'default' => 'FEATURED PRODUCTS',
				),
				'productsToShow' => array(
					'type' => 'number',
					'default' => 4,
				),
				'filterType' => array(
					'type' => 'string',
					'default' => 'manual',
				),
				'selectedProducts' => array(
					'type' => 'array',
					'default' => array(),
				),
				'selectedTags' => array(
					'type' => 'array',
					'default' => array(),
				),
				'showOnSale' => array(
					'type' => 'boolean',
					'default' => false,
				),
				'showFeatured' => array(
					'type' => 'boolean',
					'default' => false,
				),
				'randomizeProducts' => array(
					'type' => 'boolean',
					'default' => false,
				),
			),
		)
	);
}
add_action('init', 'ocellaris_register_featured_products_block');

/**
 * Render Ocellaris Featured Products Block.
 */
function ocellaris_render_featured_products_block($attributes) {
	$title = isset($attributes['title']) ? $attributes['title'] : 'FEATURED PRODUCTS';
	$products_to_show = isset($attributes['productsToShow']) ? max(1, (int)$attributes['productsToShow']) : 4;
	$filter_type = isset($attributes['filterType']) ? $attributes['filterType'] : 'manual';
	$selected_products = isset($attributes['selectedProducts']) ? $attributes['selectedProducts'] : array();
	$selected_tags = isset($attributes['selectedTags']) ? $attributes['selectedTags'] : array();
	$randomize_products = isset($attributes['randomizeProducts']) ? $attributes['randomizeProducts'] : false;
	$query_limit = $products_to_show;

	$args = array(
		'post_type' => 'product',
		'posts_per_page' => $query_limit,
		'post_status' => 'publish',
		'meta_query' => array(
			array(
				'key' => '_stock_status',
				'value' => 'instock',
				'compare' => '='
			)
		),
		'tax_query' => array(),
	);

	switch ($filter_type) {
		case 'manual':
			if (!empty($selected_products)) {
				if ($randomize_products) {
					$args['post__in'] = $selected_products;
					$args['orderby'] = 'rand';
				} else {
					$args['post__in'] = $selected_products;
					$args['orderby'] = 'post__in';
				}
				$query_limit = max(count($selected_products) * 2, $products_to_show);
				$args['posts_per_page'] = $query_limit;
			} else {
				return '<div class="ocellaris-featured-products"><p>No hay productos seleccionados.</p></div>';
			}
			break;

		case 'sale':
			$args['meta_query'][] = array(
				'relation' => 'OR',
				array(
					'key' => '_sale_price',
					'value' => 0,
					'compare' => '>',
					'type' => 'NUMERIC'
				),
				array(
					'key' => '_min_variation_sale_price',
					'value' => 0,
					'compare' => '>',
					'type' => 'NUMERIC'
				)
			);
			if ($randomize_products) {
				$args['orderby'] = 'rand';
			}
			$query_limit = max($products_to_show * 3, 12);
			$args['posts_per_page'] = $query_limit;
			break;

		case 'featured':
			$args['tax_query'][] = array(
				'taxonomy' => 'product_visibility',
				'field' => 'name',
				'terms' => 'featured',
			);
			if ($randomize_products) {
				$args['orderby'] = 'rand';
			}
			$query_limit = max($products_to_show * 3, 12);
			$args['posts_per_page'] = $query_limit;
			break;

		case 'tags':
			if (!empty($selected_tags)) {
				$args['tax_query'][] = array(
					'taxonomy' => 'product_tag',
					'field' => 'term_id',
					'terms' => $selected_tags,
				);
				if ($randomize_products) {
					$args['orderby'] = 'rand';
				}
				$query_limit = max($products_to_show * 3, 12);
				$args['posts_per_page'] = $query_limit;
			} else {
				return '<div class="ocellaris-featured-products"><p>No hay etiquetas seleccionadas.</p></div>';
			}
			break;
	}

	$products = new WP_Query($args);

	if (!$products->have_posts()) {
		return '<div class="ocellaris-featured-products"><p>No se encontraron productos.</p></div>';
	}

	$product_ids = array();
	$max_results = $filter_type === 'manual' ? count($selected_products) : $query_limit;

	while ($products->have_posts() && count($product_ids) < $max_results) {
		$products->the_post();
		global $product;

		if (!$product || !$product->is_visible() || !$product->is_in_stock()) {
			continue;
		}

		$product_ids[] = get_the_ID();
	}

	wp_reset_postdata();

	if (empty($product_ids)) {
		return '<div class="ocellaris-featured-products"><p>No se encontraron productos.</p></div>';
	}

	$total_products = count($product_ids);
	$use_carousel = $total_products > $products_to_show;
	$items_to_render = $use_carousel ? $product_ids : array_slice($product_ids, 0, $products_to_show);
	$grid_class = 'products-count-' . min($products_to_show, 4);
	$grid_style = '--products-per-view:' . $products_to_show . ';';

	if ($use_carousel) {
		wp_enqueue_script('ocellaris-featured-products-carousel');
	}

	ob_start();
	?>

	<div class="ocellaris-featured-products">
		<?php if (!empty($title)): ?>
			<h2 class="ocellaris-featured-products-title"><?php echo esc_html($title); ?></h2>
		<?php endif; ?>

		<?php if ($use_carousel): ?>
		<div class="featured-products-carousel-wrapper" data-visible-items="<?php echo esc_attr($products_to_show); ?>" data-total-items="<?php echo esc_attr($total_products); ?>">
			<div class="featured-products-carousel-header">
				<div class="featured-products-carousel-dots" aria-hidden="true"></div>
			</div>
			<div class="featured-products-carousel-body">
				<button class="featured-products-carousel-nav carousel-prev" aria-label="Productos anteriores" type="button">
					<span class="featured-products-carousel-icon" aria-hidden="true">&lsaquo;</span>
				</button>
				<div class="featured-products-carousel-viewport">
					<div class="featured-products-carousel-track" style="<?php echo esc_attr($grid_style); ?>">
						<?php foreach ($items_to_render as $product_id): ?>
						<?php
						$product = wc_get_product($product_id);
						if (!$product || !$product->is_visible() || !$product->is_in_stock()) {
							continue;
						}
						$is_on_sale = $product->is_on_sale();
						$is_featured = $product->is_featured();
						$rating = $product->get_average_rating();
						$review_count = $product->get_review_count();
						$discount_percentage = 0;
						if ($is_on_sale) {
							$regular_price = (float) $product->get_regular_price();
							$sale_price = (float) $product->get_sale_price();
							if ($regular_price > 0 && $sale_price > 0) {
								$discount_percentage = round((($regular_price - $sale_price) / $regular_price) * 100);
							}
						}
						$is_msi_eligible = ocellaris_is_product_msi_eligible( $product_id );
						?>
			<div class="featured-product-item <?php echo $is_on_sale ? 'on-sale' : ''; ?> <?php echo $is_featured ? 'featured' : ''; ?>">

				<div class="featured-product-badge">
					<?php if ($filter_type === 'sale' && $is_on_sale && $discount_percentage > 0): ?>
						<span class="sale-badge">
							<span class="save-text">DESCUENTO</span><br>
							<span class="discount-percent"><?php echo $discount_percentage; ?>%</span>
						</span>
					<?php elseif (!$is_msi_eligible): ?>
						<span class="brs-badge">Recomendación Ocellaris</span>
					<?php endif; ?>
				</div>

				<?php if ($is_msi_eligible): ?>
				<div class="featured-product-badge msi-badge-container <?php echo ($filter_type === 'sale' && $is_on_sale && $discount_percentage > 0) ? 'has-sale-badge' : (($filter_type !== 'sale') ? 'has-brs-badge' : ''); ?>">
					<span class="msi-badge">Meses sin intereses</span>
				</div>
				<?php endif; ?>

				<div class="featured-product-image">
					<a href="<?php echo get_permalink($product_id); ?>">
						<?php echo $product->get_image('woocommerce_thumbnail'); ?>
					</a>
				</div>

				<div class="featured-product-content">
					<?php if ($rating > 0): ?>
					<div class="featured-product-rating">
						<div class="star-rating">
							<?php
							for ($i = 1; $i <= 5; $i++) {
								if ($i <= $rating) {
									echo '<span class="star filled">★</span>';
								} else {
									echo '<span class="star empty">☆</span>';
								}
							}
							?>
						</div>
					</div>
					<?php endif; ?>

					<?php
					$brands = get_the_terms($product_id, 'pa_brand');
					if ($brands && !is_wp_error($brands)):
						$brand = array_shift($brands);
					?>
					<div class="featured-product-brand">
						<?php echo esc_html($brand->name); ?>
					</div>
					<?php endif; ?>

					<h3 class="featured-product-title">
						<a href="<?php echo get_permalink($product_id); ?>">
							<?php echo get_the_title($product_id); ?>
						</a>
					</h3>

					<div class="featured-product-price">
						<?php echo $product->get_price_html(); ?>
					</div>

					<div class="featured-add-to-cart">
						<?php
						$post_object = get_post($product_id);
						if ($post_object instanceof WP_Post) {
							setup_postdata($post_object);
							$GLOBALS['product'] = $product;
							woocommerce_template_loop_add_to_cart();
						}
						?>
					</div>

				</div>
			</div>
			<?php endforeach; ?>
					</div>
				</div>
				<button class="featured-products-carousel-nav carousel-next" aria-label="Siguientes productos" type="button">
					<span class="featured-products-carousel-icon" aria-hidden="true">&rsaquo;</span>
				</button>
			</div>
		</div>
		<?php else: ?>
		<div class="featured-products-grid <?php echo esc_attr($grid_class); ?>" style="<?php echo esc_attr($grid_style); ?>">
			<?php foreach ($items_to_render as $product_id): ?>
			<?php
			$product = wc_get_product($product_id);
			if (!$product || !$product->is_visible() || !$product->is_in_stock()) {
				continue;
			}
			$is_on_sale = $product->is_on_sale();
			$is_featured = $product->is_featured();
			$rating = $product->get_average_rating();
			$review_count = $product->get_review_count();
			$discount_percentage = 0;
			if ($is_on_sale) {
				$regular_price = (float) $product->get_regular_price();
				$sale_price = (float) $product->get_sale_price();
				if ($regular_price > 0 && $sale_price > 0) {
					$discount_percentage = round((($regular_price - $sale_price) / $regular_price) * 100);
				}
			}
			$is_msi_eligible = ocellaris_is_product_msi_eligible( $product_id );
			?>
			<div class="featured-product-item <?php echo $is_on_sale ? 'on-sale' : ''; ?> <?php echo $is_featured ? 'featured' : ''; ?>">
				<div class="featured-product-badge">
					<?php if ($filter_type === 'sale' && $is_on_sale && $discount_percentage > 0): ?>
						<span class="sale-badge">
							<span class="save-text">DESCUENTO</span><br>
							<span class="discount-percent"><?php echo $discount_percentage; ?>%</span>
						</span>
					<?php elseif (!$is_msi_eligible): ?>
						<span class="brs-badge">Recomendación Ocellaris</span>
					<?php endif; ?>
				</div>

				<?php if ($is_msi_eligible): ?>
				<div class="featured-product-badge msi-badge-container <?php echo ($filter_type === 'sale' && $is_on_sale && $discount_percentage > 0) ? 'has-sale-badge' : (($filter_type !== 'sale') ? 'has-brs-badge' : ''); ?>">
					<span class="msi-badge">Meses sin intereses</span>
				</div>
				<?php endif; ?>

				<div class="featured-product-image">
					<a href="<?php echo get_permalink($product_id); ?>">
						<?php echo $product->get_image('woocommerce_thumbnail'); ?>
					</a>
				</div>

				<div class="featured-product-content">
					<?php if ($rating > 0): ?>
					<div class="featured-product-rating">
						<div class="star-rating">
							<?php
							for ($i = 1; $i <= 5; $i++) {
								if ($i <= $rating) {
									echo '<span class="star filled">★</span>';
								} else {
									echo '<span class="star empty">☆</span>';
								}
							}
							?>
						</div>
					</div>
					<?php endif; ?>

					<?php
					$brands = get_the_terms($product_id, 'pa_brand');
					if ($brands && !is_wp_error($brands)):
						$brand = array_shift($brands);
					?>
					<div class="featured-product-brand">
						<?php echo esc_html($brand->name); ?>
					</div>
					<?php endif; ?>

					<h3 class="featured-product-title">
						<a href="<?php echo get_permalink($product_id); ?>">
							<?php echo get_the_title($product_id); ?>
						</a>
					</h3>

					<div class="featured-product-price">
						<?php echo $product->get_price_html(); ?>
					</div>

					<div class="featured-add-to-cart">
						<?php
						$post_object = get_post($product_id);
						if ($post_object instanceof WP_Post) {
							setup_postdata($post_object);
							$GLOBALS['product'] = $product;
							woocommerce_template_loop_add_to_cart();
						}
						?>
					</div>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>
	</div>

	<?php
	wp_reset_postdata();
	return ob_get_clean();
}
