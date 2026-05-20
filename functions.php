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
 * OCELLARIS CUSTOM PRODUCT CATEGORY BLOCK
 * Implementación de bloque personalizado para mostrar categorías de productos
 * en en editor de bloques de WordPress.
 */

/**
 * Register Ocellaris Product Category Block
 */
function ocellaris_register_product_categories_block() {
	// register block script
	wp_register_script(
		'ocellaris-product-categories-block',
		get_stylesheet_directory_uri() . '/blocks/product-categories/block.js',
		array('wp-blocks', 'wp-element', 'wp-components', 'wp-data'),
		CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION
	);

	// register block styles
	wp_register_style(
		'ocellaris-product-categories-block-editor',
		get_stylesheet_directory_uri() . '/blocks/product-categories/editor.css',
		array('wp-edit-blocks'),
		CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION
	);

	wp_register_style(
		'ocellaris-product-categories-block',
		get_stylesheet_directory_uri() . '/blocks/product-categories/style.css',
		array(),
		CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION
	);

	// register the block
	register_block_type(
		'ocellaris/product-categories',
		array(
			'editor_script' => 'ocellaris-product-categories-block',
			'editor_style' => 'ocellaris-product-categories-block-editor',
			'style' => 'ocellaris-product-categories-block',
			'render_callback' => 'ocellaris_render_product_categories_block',
			'attributes' => array(
				'selectedCategories' => array(
					'type' => 'array',
					'default' => array(),
				),
				'title' => array(
					'type' => 'string',
					'default' => 'Categorías Top',
				),
				'subtitle' => array(
					'type' => 'string',
					'default' => '',
				),
			),
		)
	);
}
add_action('init', 'ocellaris_register_product_categories_block');


/**
 * Render Ocellaris Product Categories Block
 */
function ocellaris_render_product_categories_block($attributes) {
	$selected_categories = isset($attributes['selectedCategories']) ? $attributes['selectedCategories']:array();
	$title = isset($attributes['title']) ? $attributes['title']: 'Categorías Top';
	$subtitle = isset($attributes['subtitle']) ? $attributes['subtitle']: '';

	if (empty($selected_categories)) {
		return '';
	}

	ob_start();
	?>

	<div class="ocellaris-product-categories">
		<?php if (!empty($title)): ?>
			<h2 class="ocellaris-product-categories-title"><?php echo esc_html($title); ?></h2>
		<?php endif; ?>
		<div class="categories-wrapper">
			<svg class="categories-curve" viewBox="0 0 1400 100" preserveAspectRatio="none">
				<path d="M0,50 Q350,0 700,50 T1400,50" fill="none" stroke="#FF1654" stroke-width="3"/>
			</svg>
			<div class="categories-container">
				<?php foreach ($selected_categories as $cat_id):
					$category = get_term($cat_id, 'product_cat');
					if (!$category || is_wp_error($category)) {
						continue;
					}

					$thumbnail_id = get_term_meta($cat_id, 'thumbnail_id', true);
					$image_url = $thumbnail_id? wp_get_attachment_url($thumbnail_id): wc_placeholder_img_src();
					$category_link = get_term_link($category);
				?>
				<div class="category-item">
					<a href="<?php echo esc_url($category_link); ?>" class="category-link">
						<div class="category-image-wrapper">
							<div class="category-circle"></div>
							<img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($category->name); ?>" class="category-image">
						</div>
						<h3 class="category-name"><?php echo esc_html($category->name); ?></h3>
					</a>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php if(!empty($subtitle)): ?>
			<h4 class="category-subtitle"><?php echo esc_html($subtitle); ?></h4>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}


/**
 * OCELLARIS CUSTOM FEATURED BRANDS BLOCK
 * Implementación de bloque personalizado para mostrar marcas destacadas
 * en el editor de bloques de WordPress.
 */


/**
 * Register Ocellaris Featured Brands Block
 */
function ocellaris_register_featured_brands_block() {
	// register block script
	wp_register_script(
		'ocellaris-featured-brands-block',
		get_stylesheet_directory_uri() . '/blocks/featured-brands/block.js',
		array('wp-blocks', 'wp-element', 'wp-components', 'wp-data'),
		CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION
	);

	// register block styles
	wp_register_style(
		'ocellaris-featured-brands-block-editor',
		get_stylesheet_directory_uri() . '/blocks/featured-brands/editor.css',
		array('wp-edit-blocks'),
		CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION
	);

	wp_register_style(
		'ocellaris-featured-brands-block',
		get_stylesheet_directory_uri() . '/blocks/featured-brands/style.css',
		array(),
		CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION
	);

	// register carousel script
	wp_register_script(
		'ocellaris-brands-carousel',
		get_stylesheet_directory_uri() . '/blocks/featured-brands/carousel.js',
		array('jquery'),
		CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION,
		true
	);

	// register the block
	register_block_type(
		'ocellaris/featured-brands',
		array(
			'editor_script' => 'ocellaris-featured-brands-block',
			'editor_style' => 'ocellaris-featured-brands-block-editor',
			'style' => 'ocellaris-featured-brands-block',
			'render_callback' => 'ocellaris_render_featured_brands_block',
			'attributes' => array(
				'selectedBrands' => array(
					'type' => 'array',
					'default' => array(),
				),
				'title' => array(
					'type' => 'string',
					'default' => 'Marcas Destacadas',
				),
				'autoplaySpeed' => array(
					'type' => 'number',
					'default' => 3000, // en milisegundos
				),
			),
		)
	);
}
add_action('init', 'ocellaris_register_featured_brands_block');


/**
 * OCELLARIS CUSTOM ALL BRANDS BLOCK
 * Implementación de bloque personalizado para mostrar todas las marcas
 * con filtro alfabético y diseño responsive.
 */

/**
 * Register Ocellaris All Brands Block
 */
function ocellaris_register_all_brands_block() {
	// register block script
	wp_register_script(
		'ocellaris-all-brands-block',
		get_stylesheet_directory_uri() . '/blocks/all-brands/block.js',
		array('wp-blocks', 'wp-element', 'wp-components'),
		CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION
	);

	// register block styles
	wp_register_style(
		'ocellaris-all-brands-block-editor',
		get_stylesheet_directory_uri() . '/blocks/all-brands/editor.css',
		array('wp-edit-blocks'),
		CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION
	);

	wp_register_style(
		'ocellaris-all-brands-block',
		get_stylesheet_directory_uri() . '/blocks/all-brands/style.css',
		array(),
		CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION
	);

	// register frontend script for filtering
	wp_register_script(
		'ocellaris-all-brands-frontend',
		get_stylesheet_directory_uri() . '/blocks/all-brands/frontend.js',
		array('jquery'),
		CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION,
		true
	);

	// register the block
	register_block_type(
		'ocellaris/all-brands',
		array(
			'editor_script' => 'ocellaris-all-brands-block',
			'editor_style' => 'ocellaris-all-brands-block-editor',
			'style' => 'ocellaris-all-brands-block',
			'render_callback' => 'ocellaris_render_all_brands_block',
			'attributes' => array(
				'title' => array(
					'type' => 'string',
					'default' => 'TODAS NUESTRAS MARCAS',
				),
				'showAlphabetFilter' => array(
					'type' => 'boolean',
					'default' => true,
				),
				'columns' => array(
					'type' => 'number',
					'default' => 4,
				),
				'displayStyle' => array(
					'type' => 'string',
					'default' => 'grid',
				),
				'showBrandCount' => array(
					'type' => 'boolean',
					'default' => true,
				),
				'brandImageSize' => array(
					'type' => 'string',
					'default' => 'medium',
				),
			),
		)
	);
}
add_action('init', 'ocellaris_register_all_brands_block');

/**
 * OCELLARIS CATALOG FILTERS
 * Implementación modular en includes/woocommerce/catalog-filters.php
 */


/**
 * OCELLARIS CUSTOM FEATURED PRODUCTS BLOCK
 * Implementación de bloque personalizado para mostrar productos destacados
 * con diferentes filtros: manual, por etiquetas, ofertas, etc.
 */

/**
 * Register Ocellaris Featured Products Block
 */
function ocellaris_register_featured_products_block() {
	// register block script
	wp_register_script(
		'ocellaris-featured-products-block',
		get_stylesheet_directory_uri() . '/blocks/featured-products/block.js',
		array('wp-blocks', 'wp-element', 'wp-components', 'wp-data', 'wp-api-fetch', 'wp-url'),
		CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION
	);

	// register block styles
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

	// register the block
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
 * Render Ocellaris Featured Products Block
 */
function ocellaris_render_featured_products_block($attributes) {
	$title = isset($attributes['title']) ? $attributes['title'] : 'FEATURED PRODUCTS';
	$products_to_show = isset($attributes['productsToShow']) ? max(1, (int)$attributes['productsToShow']) : 4;
	$filter_type = isset($attributes['filterType']) ? $attributes['filterType'] : 'manual';
	$selected_products = isset($attributes['selectedProducts']) ? $attributes['selectedProducts'] : array();
	$selected_tags = isset($attributes['selectedTags']) ? $attributes['selectedTags'] : array();
	$randomize_products = isset($attributes['randomizeProducts']) ? $attributes['randomizeProducts'] : false;
	$query_limit = $products_to_show;

	// Preparar argumentos para WP_Query
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

	// Aplicar filtros según el tipo seleccionado
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
				// Compensar productos no visibles/sin stock sin limitar la selección manual.
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
					<div class="featured-products-grid featured-products-carousel-track <?php echo esc_attr($grid_class); ?>" style="<?php echo esc_attr($grid_style); ?>">
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
				
				<!-- Badge condicional -->
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
				
				<!-- Imagen del producto -->
				<div class="featured-product-image">
					<a href="<?php echo get_permalink($product_id); ?>">
						<?php echo $product->get_image('woocommerce_thumbnail'); ?>
					</a>
				</div>
				
				<!-- Contenido del producto -->
				<div class="featured-product-content">
					
					<!-- Rating -->
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
					
					<!-- Marca/Brand -->
					<?php 
					$brands = get_the_terms($product_id, 'pa_brand');
					if ($brands && !is_wp_error($brands)): 
						$brand = array_shift($brands);
					?>
					<div class="featured-product-brand">
						<?php echo esc_html($brand->name); ?>
					</div>
					<?php endif; ?>
					
					<!-- Título del producto -->
					<h3 class="featured-product-title">
						<a href="<?php echo get_permalink($product_id); ?>">
							<?php echo get_the_title($product_id); ?>
						</a>
					</h3>
					
					<!-- Precio -->
					<div class="featured-product-price">
						<?php echo $product->get_price_html(); ?>
					</div>
					
					<!-- Botón Add to Cart -->
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


/**
 * Render Ocellaris Featured Brands Block
 */
function ocellaris_render_featured_brands_block($attributes) {
	$selected_brands = isset($attributes['selectedBrands']) ? $attributes['selectedBrands']:array();
	$title = isset($attributes['title'])? $attributes['title']: 'Marcas Destacadas';
	$display_mode = isset($attributes['displayMode'])? $attributes['displayMode']: 'carousel';
	$autoplay_speed = isset($attributes['autoplaySpeed'])? $attributes['autoplaySpeed']: 3000;

	if(empty($selected_brands)) {
		return '';
	}

	// enqueue carousel script only for carousel mode
	if($display_mode === 'carousel') {
		wp_enqueue_script('ocellaris-brands-carousel');
	}

	ob_start();
	?>

	<div class="ocellaris-featured-brands <?php echo esc_attr('mode-' . $display_mode); ?>" <?php if($display_mode === 'carousel'): ?>data-autoplay-speed="<?php echo esc_attr($autoplay_speed); ?>"<?php endif; ?>>
		<?php if (!empty($title)): ?>
			<h2 class="brands-title"><?php echo esc_html($title); ?></h2>
		<?php endif; ?>
		
		<?php if($display_mode === 'carousel'): ?>
			<div class="brands-carousel-wrapper">
				<button class="carousel-nav carousel-prev" aria-label="Anterior">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none">
						<path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>		
				</button>
				<div class="brands-carousel-container">
					<div class="brands-carousel">
						<?php foreach ($selected_brands as $brand_id):
							$brand = get_term($brand_id, 'product_brand');
							if(!$brand || is_wp_error($brand)) {
								continue;
							}
							$thumbnail_id = get_term_meta($brand_id, 'thumbnail_id', true);
							$image_url = $thumbnail_id? wp_get_attachment_url($thumbnail_id) : '';
							$brand_link = get_term_link($brand);
						?>
						<div class="brand-item">
							<a href="<?php echo esc_url($brand_link); ?>" class="brand-link">
								<?php if($image_url): ?>
									<img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($brand->name); ?>" class="brand-logo">
								<?php else: ?>
									<span class="brand-name-text"><?php echo esc_html($brand->name); ?></span>
								<?php endif; ?>
							</a>
						</div>
						<?php endforeach; ?>
					</div>
				</div>
				<button class="carousel-nav carousel-next" aria-label="Siguiente">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none">
						<path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>				
				</button>
			</div>
		<?php else: ?>
			<div class="brands-grid">
				<?php foreach ($selected_brands as $brand_id):
					$brand = get_term($brand_id, 'product_brand');
					if(!$brand || is_wp_error($brand)) {
						continue;
					}
					$thumbnail_id = get_term_meta($brand_id, 'thumbnail_id', true);
					$image_url = $thumbnail_id? wp_get_attachment_url($thumbnail_id) : '';
					$brand_link = get_term_link($brand);
				?>
				<div class="brand-item">
					<a href="<?php echo esc_url($brand_link); ?>" class="brand-link">
						<?php if($image_url): ?>
							<img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($brand->name); ?>" class="brand-logo">
						<?php else: ?>
							<span class="brand-name-text"><?php echo esc_html($brand->name); ?></span>
						<?php endif; ?>
					</a>
				</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}


/**
 * Render Ocellaris All Brands Block
 */
function ocellaris_render_all_brands_block($attributes) {
	$title = isset($attributes['title']) ? $attributes['title'] : 'TODAS NUESTRAS MARCAS';
	$show_alphabet_filter = isset($attributes['showAlphabetFilter']) ? $attributes['showAlphabetFilter'] : true;
	$columns = isset($attributes['columns']) ? $attributes['columns'] : 4;
	$display_style = isset($attributes['displayStyle']) ? $attributes['displayStyle'] : 'grid';
	$show_brand_count = isset($attributes['showBrandCount']) ? $attributes['showBrandCount'] : true;
	$brand_image_size = isset($attributes['brandImageSize']) ? $attributes['brandImageSize'] : 'medium';

	// enqueue frontend script
	wp_enqueue_script('ocellaris-all-brands-frontend');

	// Get all brands from WooCommerce
	$brands = get_terms(array(
		'taxonomy' => 'product_brand',
		'orderby' => 'name',
		'order' => 'ASC',
		'hide_empty' => true,
	));

	if(empty($brands) || is_wp_error($brands)) {
		return '<div class="ocellaris-all-brands">
					<div class="brands-empty">
						<div class="brands-empty-icon">🏷️</div>
						<p>No se encontraron marcas disponibles.</p>
					</div>
				</div>';
	}

	// Organize brands by alphabet
	$brands_by_letter = array();
	$alphabet = range('A', 'Z');
	$other_letters = array();

	foreach($brands as $brand) {
		$first_letter = strtoupper(substr($brand->name, 0, 1));
		if(in_array($first_letter, $alphabet)) {
			$brands_by_letter[$first_letter][] = $brand;
		} else {
			$other_letters[] = $brand;
		}
	}

	// Add numbers/symbols to the end
	if(!empty($other_letters)) {
		$brands_by_letter['#'] = $other_letters;
	}

	ob_start();
	?>
	<div class="ocellaris-all-brands" data-columns="<?php echo esc_attr($columns); ?>" data-style="<?php echo esc_attr($display_style); ?>">
		<?php if (!empty($title)): ?>
			<h2 class="all-brands-title"><?php echo esc_html($title); ?></h2>
		<?php endif; ?>

		<?php if ($show_alphabet_filter): ?>
			<div class="alphabet-filter">
				<button class="filter-button active" data-filter="all">TODAS</button>
				<?php foreach ($alphabet as $letter): ?>
					<?php if (isset($brands_by_letter[$letter])): ?>
						<button class="filter-button" data-filter="<?php echo esc_attr($letter); ?>"><?php echo esc_html($letter); ?></button>
					<?php endif; ?>
				<?php endforeach; ?>
				<?php if (isset($brands_by_letter['#'])): ?>
					<button class="filter-button" data-filter="#">#</button>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ($show_brand_count): ?>
			<div class="brands-count">
				Mostrando <span class="count-number" id="brands-counter"><?php echo count($brands); ?></span> 
				<span id="brands-text">marcas</span>
			</div>
		<?php endif; ?>

		<div class="brands-grid style-<?php echo esc_attr($display_style); ?> columns-<?php echo esc_attr($columns); ?>" id="brands-container">
			<?php foreach($brands as $brand): 
				$first_letter = strtoupper(substr($brand->name, 0, 1));
				$filter_class = in_array($first_letter, $alphabet) ? $first_letter : 'other';
				$thumbnail_id = get_term_meta($brand->term_id, 'thumbnail_id', true);
				$image_url = $thumbnail_id ? wp_get_attachment_url($thumbnail_id) : '';
				$brand_link = get_term_link($brand);
				$product_count = $brand->count;
			?>
				<div class="brand-item" data-letter="<?php echo esc_attr($filter_class); ?>" data-name="<?php echo esc_attr(strtolower($brand->name)); ?>">
					<a href="<?php echo esc_url($brand_link); ?>" class="brand-link">
						<!-- <?php if($image_url): ?>
							<img src="<?php echo esc_url($image_url); ?>" 
								 alt="<?php echo esc_attr($brand->name); ?>" 
								 class="brand-logo size-<?php echo esc_attr($brand_image_size); ?>">
						<?php endif; ?> -->
						<span class="brand-name-text"><?php echo esc_html($brand->name); ?></span>
						<?php if($display_style === 'list'): ?>
							<span class="brand-product-count">(<?php echo esc_html($product_count); ?> productos)</span>
						<?php endif; ?>
					</a>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="brands-loading" id="brands-loading" style="display: none;">
			<div class="loading-spinner"></div>
			<p>Cargando marcas...</p>
		</div>

		<div class="brands-empty" id="brands-empty" style="display: none;">
			<div class="brands-empty-icon">🔍</div>
			<p>No se encontraron marcas con esa letra.</p>
		</div>
	</div>

	<script>
		// Pasar datos al frontend
		window.ocellarisAllBrandsData = {
			alphabet: <?php echo json_encode(array_keys($brands_by_letter)); ?>,
			brandCount: <?php echo count($brands); ?>
		};
	</script>
	<?php
	return ob_get_clean();
}


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
