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

/**
 * OCELLARIS CUSTOM HEADER
 * Implementación de un encabezado personalizado para el tema Astra.
 */


/**
 * Custom Header scripts and styles
 */
function ocellaris_custom_header_assets() {
	// custom header CSS
	wp_enqueue_style(
		'ocellaris-header-css',
		get_stylesheet_directory_uri() . '/assets/css/custom-header.css',
		array(),
		CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION
	);

	//custom header JS
	wp_enqueue_script(
		'ocellaris-header-js',
		get_stylesheet_directory_uri() . '/assets/js/custom-header.js',
		array('jquery'),
		CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION,
		true
	);

	// Localize AJAX data for header JS
	wp_localize_script(
		'ocellaris-header-js',
		'OcellarisHeader',
		array(
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'nonce'   => wp_create_nonce('ocellaris_menu_nonce'),
		)
	);
}
add_action('wp_enqueue_scripts', 'ocellaris_custom_header_assets');


/**
 * Remove Astra Header
 */
function ocellaris_remove_astra_header() {
	remove_action('astra_header', 'astra_header_markup');
}
add_action('wp', 'ocellaris_remove_astra_header');


/**
 * Add Ocellaris Custom Header
 */
function ocellaris_custom_header_markup() {
	get_template_part('template-parts/header-custom');
}
add_action('astra_header', 'ocellaris_custom_header_markup');


/**
 * OCELLARIS CUSTOM FOOTER
 * Implementación de un pie de página personalizado para el tema Astra.
 */

/**
 * Remove default Astra Footer
 */
function ocellaris_remove_astra_footer() {
	remove_action('astra_footer', 'astra_footer_markup');
}
add_action('wp', 'ocellaris_remove_astra_footer');


/**
 * Add Ocellaris Custom Footer
 */
function ocellaris_custom_footer_markup() {
	get_template_part('template-parts/footer-custom');
}
add_action('astra_footer', 'ocellaris_custom_footer_markup');


/**
 * Ocellaris Custom Footer scripts and styles
 */
function ocellaris_custom_footer_assets() {
	wp_enqueue_style(
		'ocellaris-footer-css',
		get_stylesheet_directory_uri() . '/assets/css/custom-footer.css',
		array(),
		CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION
	);
}
add_action('wp_enqueue_scripts', 'ocellaris_custom_footer_assets');

/**
 * Register Navigation Menus
 */
function ocellaris_register_menus() {
	register_nav_menus(
		array(
			'sidebar-menu' => __('Ocellaris Main Menu: Sidebar Menu (Categorías)', 'ocellaris-custom-astra'),
			'quick-links-menu' => __('Ocellaris Main Menu: Quick Links Menu', 'ocellaris-custom-astra'),
			'footer-about' => __('Ocellaris Footer: Acerca de Ocellaris', 'ocellaris-custom-astra'),
			'footer-support' => __('Ocellaris Footer: Atención al Cliente', 'ocellaris-custom-astra'),
			'footer-resources' => __('Ocellaris Footer: Recursos', 'ocellaris-custom-astra'),
		)
	);
} 
add_action('init', 'ocellaris_register_menus');

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
	$products_to_show = isset($attributes['productsToShow']) ? (int)$attributes['productsToShow'] : 4;
	$filter_type = isset($attributes['filterType']) ? $attributes['filterType'] : 'manual';
	$selected_products = isset($attributes['selectedProducts']) ? $attributes['selectedProducts'] : array();
	$selected_tags = isset($attributes['selectedTags']) ? $attributes['selectedTags'] : array();
	$show_on_sale = isset($attributes['showOnSale']) ? $attributes['showOnSale'] : false;
	$show_featured = isset($attributes['showFeatured']) ? $attributes['showFeatured'] : false;
	$randomize_products = isset($attributes['randomizeProducts']) ? $attributes['randomizeProducts'] : false;

	// Preparar argumentos para WP_Query
	$args = array(
		'post_type' => 'product',
		'posts_per_page' => $products_to_show,
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
					// Si queremos orden aleatorio, no usar post__in con orderby
					$args['post__in'] = $selected_products;
					$args['orderby'] = 'rand';
				} else {
					// Orden normal según selección
					$args['post__in'] = $selected_products;
					$args['orderby'] = 'post__in';
				}
				// Aumentar el límite para compensar productos fuera de stock
				$args['posts_per_page'] = count($selected_products) * 2; // multiplicar por 2 para asegurar suficientes productos
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
			} else {
				return '<div class="ocellaris-featured-products"><p>No hay etiquetas seleccionadas.</p></div>';
			}
			break;
	}

	$products = new WP_Query($args);

	if (!$products->have_posts()) {
		return '<div class="ocellaris-featured-products"><p>No se encontraron productos.</p></div>';
	}

	// Para selección manual, determinar cuántos productos en stock tenemos disponibles
	if ($filter_type === 'manual') {
		$displayed_count = min(count($selected_products), $products->found_posts);
	} else {
		$displayed_count = min($products_to_show, $products->found_posts);
	}
	
	$grid_class = 'products-count-' . $displayed_count;

	ob_start();
	?>

	<div class="ocellaris-featured-products">
		<?php if (!empty($title)): ?>
			<h2 class="ocellaris-featured-products-title"><?php echo esc_html($title); ?></h2>
		<?php endif; ?>
		
		<div class="featured-products-grid <?php echo esc_attr($grid_class); ?>">
			<?php 
			$products_displayed = 0;
			$max_products = ($filter_type === 'manual') ? count($selected_products) : $products_to_show;
			
			while ($products->have_posts() && $products_displayed < $max_products): 
				$products->the_post(); 
				global $product;
				
				// Skip if product is not valid or not visible
				if (!$product || !$product->is_visible()) {
					continue;
				}
				
				// Verificar stock adicional por si acaso
				if (!$product->is_in_stock()) {
					continue;
				}
				
				$product_id = get_the_ID();
				$is_on_sale = $product->is_on_sale();
				$is_featured = $product->is_featured();
				$rating = $product->get_average_rating();
				$review_count = $product->get_review_count();
				
				// Calcular porcentaje de descuento si está en oferta
				$discount_percentage = 0;
				if ($is_on_sale) {
					$regular_price = (float) $product->get_regular_price();
					$sale_price = (float) $product->get_sale_price();
					if ($regular_price > 0 && $sale_price > 0) {
						$discount_percentage = round((($regular_price - $sale_price) / $regular_price) * 100);
					}
				}
				
				$products_displayed++;
			?>
			<div class="featured-product-item <?php echo $is_on_sale ? 'on-sale' : ''; ?> <?php echo $is_featured ? 'featured' : ''; ?>">
				
				<!-- Badge condicional -->
				<div class="featured-product-badge">
					<?php if ($filter_type === 'sale' && $is_on_sale && $discount_percentage > 0): ?>
						<span class="sale-badge">
							<span class="save-text">DESCUENTO</span><br>
							<span class="discount-percent"><?php echo $discount_percentage; ?>%</span>
						</span>
					<?php else: ?>
						<span class="brs-badge">Recomendación Ocellaris</span>
					<?php endif; ?>
				</div>
				
				<!-- Imagen del producto -->
				<div class="featured-product-image">
					<a href="<?php echo get_permalink($product_id); ?>">
						<?php echo woocommerce_get_product_thumbnail(); ?>
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
							<?php echo get_the_title(); ?>
						</a>
					</h3>
					
					<!-- Precio -->
					<div class="featured-product-price">
						<?php echo $product->get_price_html(); ?>
					</div>
					
					<!-- Botón Add to Cart -->
					<div class="featured-add-to-cart">
						<?php
						woocommerce_template_loop_add_to_cart();
						?>
					</div>
					
				</div>
			</div>
			<?php endwhile; ?>
		</div>
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
 * Implementación de una barra de texto superior personalizada.
 */

/**
 * Página de configuración de banner
 */
function ocellaris_config_text_bar_page() {
	add_theme_page(
		'Configuración de barra de texto superior',
		'Ocellaris Text Bar',
		'manage_options',
		'ocellaris-text-bar',
		'ocellaris_render_text_bar'
	);
}
add_action('admin_menu', 'ocellaris_config_text_bar_page');

/**
 * Registro de settings de la barra de texto
 */
function ocellaris_register_text_bar_settings() {
	register_setting(
		'ocellaris_text_bar_settings',
		'ocellaris_text_bar_active',
		array('sanitize_callback' => 'ocellaris_sanitize_checkbox')
	);

	register_setting(
		'ocellaris_text_bar_settings',
		'ocellaris_text_bar_content',
		array('sanitize_callback' => 'sanitize_text_field')
	);

	register_setting(
		'ocellaris_text_bar_settings',
		'ocellaris_text_bar_link',
		array('sanitize_callback' => 'esc_url_raw')
	);

	register_setting(
		'ocellaris_text_bar_settings',
		'ocellaris_text_bar_color',
		array(
			'sanitize_callback' => 'sanitize_hex_color',
			'default' => '#003866;'
		)
	);
}
add_action('admin_init', 'ocellaris_register_text_bar_settings');


/**
 * Helper de sanitización para checkbox
 * @param $valor Valor recibido en el checkbox
 * @return string 1 si está marcado, 0 si no
 */
function ocellaris_sanitize_checkbox($valor) {	
	return (isset($valor) && $valor == '1')? '1': '0';
}


/**
 * Página de configuración en Apariencia > Ocellaris Text Bar
 */
function ocellaris_render_text_bar() {
	// validación de permisos
	if(!current_user_can('manage_options')){
		return;
	}

	// obtener opciones cuardadas
	$active = get_option('ocellaris_text_bar_active', '0');
	$content = get_option('ocellaris_text_bar_content', '');
	$color = get_option('ocellaris_text_bar_color', '#003866;');
	$link = get_option('ocellaris_text_bar_link', '');

	// mensaje de éxito al guardar
	if (isset($_GET['settings-updated'])) {
		echo '<div class="notice notice-success is-dismissible"><p>¡Configuración guardada correctamente!</p></div>';
	}
	?>
	<div class="wrap">
		<h1><?php echo esc_html(get_admin_page_title()); ?></h1>
		<p class="description">
			Configuración de barra de texto superior para el sitio Ocellaris.
			Este banner se muestra de forma global en todo el sitio web.
		</p>
		<!-- preview de la barra -->
		 <div class="ocellaris-banner-preview"
		 		style="margin: 20px 0; padding: 20px; background: #f0f0f1; border-radius: 4px;">
			<h2 style="margin-top: 0;">Vista Previa</h2>
			<div id="ocellaris-text-bar-preview-container">
				<?php if ($active == '1' && !empty($content)): ?>
					<?php if (!empty($link)): ?>
					<a href="<?php echo esc_url($link); ?>"
						class="ocellaris-text-bar-preview"
						style="background-color: <?php echo esc_attr( $color ); ?>; display: block; padding: 12px 20px; text-align: center; text-decoration: none; color: white; font-weight: 600; transition: opacity 0.3s ease; border-radius: 4px;">
					<?php echo esc_html($content); ?>
					</a>
					<?php else: ?>
					<div class="ocellaris-text-bar-preview"
						 id="ocellaris-text-bar-preview"
						 style="background-color: <?php echo esc_attr( $color ); ?>; display: block; padding: 12px 20px; text-align: center; color: white; font-weight: 600; border-radius: 4px;">
						<?php echo esc_html($content); ?>
					</div>
					<?php endif; ?>
				<?php else: ?>
					<p style="color: #666; font-style: italic;">
						La barra no se mostrará porque <?php echo ($active != '1') ? 'no está activada' : 'no tiene contenido'; ?>.
					</p>
				<?php endif; ?>
			</div>
		 </div>

		 <!-- form de configuración -->
		<form action="options.php" method="post" id="ocellaris-text-bar-form">
			<?php
			// hidden fields para procesamiento de formulario
			settings_fields('ocellaris_text_bar_settings');
			?>
			<table class="form-table">
				<!-- activar/desactivar barra -->
				 <tr>
					<th scope="row">
						<label for="ocellaris_text_bar_active">Estado de la barra de texto</label>
					</th>
					<td>
						<label>
							<input type="checkbox"
								   id="ocellaris_text_bar_active"
								   name="ocellaris_text_bar_active"
								   value="1" <?php checked( $active, '1' ); ?> />
							<strong>Activar barra de texto</strong>
						</label>
						<p class="description">
							Marca esta opción para mostrar la barra de texto en el sitio web.
						</p>
					</td>
				 </tr>
				<!-- contenido de la barra -->
				 <tr>
					<th scope="row">
						<label for="ocellaris_text_bar_content">Contenido de la barra de texto</label>
					</th>
					<td>
						<input type="text"
							   id="ocellaris_text_bar_content"
							   name="ocellaris_text_bar_content"
							   value="<?php echo esc_attr( $content ); ?>"
							   class="regular-text" />
						<p class="description">
							Mensaje que se mostrará en la barra.
						</p>
					</td>
				 </tr>

				<!-- link de la barra -->
				 <tr>
					<th scope="row">
						<label for="ocellaris_text_bar_link">Enlace de la barra de texto</label>
					</th>
					<td>
						<input type="url"
							   id="ocellaris_text_bar_link"
							   name="ocellaris_text_bar_link"
							   value="<?php echo esc_url( $link ); ?>"
							   class="regular-text" />
						<p class="description">
							Enlace al que se dirigirá el usuario al hacer clic en la barra. Déjalo vacío si no desea que la barra sea un enlace.
						</p>
					</td>
				 </tr>

				<!-- color de fondo de la barra -->
				 <tr>
					<th scope="row">
						<label for="ocellaris_text_bar_color">Color de fondo de la barra de texto</label>
					</th>
					<td>
						<input type="color"
							   id="ocellaris_text_bar_color"
							   name="ocellaris_text_bar_color"
							   value="<?php echo esc_attr( $color ); ?>"
							   class="regular-text ocellaris-color-field" />
						<p class="description">
							Selecciona el color de fondo de la barra de texto.
						</p>
					</td>
				</tr>				
			</table>
			<?php submit_button('Guardar configuración'); ?>
		</form>

		<!-- script para actualizar la vista previa en realtime -->
		<script>
			(function() {
				// obtener campos del formulario
				const activeCheckbox = document.getElementById('ocellaris_text_bar_active');
				const contentInput = document.getElementById('ocellaris_text_bar_content');
				const colorInput = document.getElementById('ocellaris_text_bar_color');
				const linkInput = document.getElementById('ocellaris_text_bar_link');
				const previewContainer = document.getElementById('ocellaris-text-bar-preview-container');

				// función para actualizar preview
				function updatePreview() {
					const isActive = activeCheckbox.checked;
					const content = contentInput.value;
					const color = colorInput.value;
					const link = linkInput.value;

					// mostrar mensaje si está desactivado o sin contenido
					if(!isActive||!content) {
						const message = !isActive? 'está desactivada': 'no tiene contenido';
						previewContainer.innerHTML = `<p style="color: #666; font-style: italic;">
							La barra no se mostrará porque ${message}.
						</p>`
						return;
					}

					// construir barra de texto
					const baseStyle = `
						background-color: ${color};
						display: block;
						padding: 12px 20px;
						text-align: center;
						color: white;
						font-weight: 600;
						border-radius: 4px;
						transition: opacity 0.3s ease;
					`;

					if(link){
						previewContainer.innerHTML = `
							<a href="${link}"
							   class="ocellaris-text-bar-preview"
							   style="${baseStyle} text-decoration: none;">
								${content}
							</a>
						`;
					} else {
						previewContainer.innerHTML = `
							<div class="ocellaris-text-bar-preview"
								 style="${baseStyle}">
								${content}
							</div>
						`;
					}
				}

				// event listener para todos los campos
				[activeCheckbox, contentInput, colorInput, linkInput].forEach(field => {
					field.addEventListener('input', updatePreview);
				});
			})();
		</script>
	</div>
	<?php
}


/**
 * Mostrar la barra de texto en el frontend
 */
function ocellaris_display_text_bar() {
	// obtener opciones
	$active = get_option('ocellaris_text_bar_active', '0');
	$content = get_option('ocellaris_text_bar_content', '');
	$color = get_option('ocellaris_text_bar_color', '#003866;');
	$link = get_option('ocellaris_text_bar_link', '');

	// no mostrar si no está activa o sin contenido
	if($active != '1' || empty($content)) {
		return;
	}

	// renderizar barra
	if (!empty($link)) {
		?>
		<a href="<?php echo esc_url($link); ?>"
		   class="ocellaris-text-bar"
		   style="background-color: <?php echo esc_attr( $color ); ?>; display: block; padding: 12px 20px; text-align: center; text-decoration: none; color: white; font-weight: 600; transition: opacity 0.3s ease;">
		<?php echo esc_html($content); ?>
		</a>
		<?php
	} else {
		?>
		<div class="ocellaris-text-bar"
			 style="background-color: <?php echo esc_attr( $color ); ?>; display: block; padding: 12px 20px; text-align: center; color: white; font-weight: 600;">
		<?php echo esc_html($content); ?>
		</div>
		<?php
	}
}
add_action('astra_header_before', 'ocellaris_display_text_bar');

/**
 * Estilos adicionales para la barra de texto en frontend
 */
function ocellaris_text_bar_frontend_styles() {
	?>
	<style>
		.ocellaris-text-bar {
			position: relative;
			z-index: 999;
		}

		.ocellaris-text-bar:hover {
			opacity: 0.9;
			cursor: pointer;
		}

		/* responsive para móviles */
		@media (max-width: 768px) {
			.ocellaris-text-bar {
				font-size: 14px;
				padding: 10px 15px!important;
			}
		}

		/* para pantallas MUY pequeñas */
		@media (max-width: 480px) {
			.ocellaris-text-bar {
				font-size: 12px;
				padding: 8px 10px!important;
			}
		}
	</style>
	<?php
}
add_action('wp_head', 'ocellaris_text_bar_frontend_styles');

/**
 * FIN DE OCELLARIS CUSTOM TOP TEXT BAR
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

// Add data-cat-id to product_cat links in the sidebar-menu (for curated menus)
function ocellaris_product_cat_menu_link_attrs( $atts, $item, $args ) {
	if ( isset($args->theme_location) && $args->theme_location === 'sidebar-menu' && isset($item->object) && $item->object === 'product_cat' ) {
		$atts['data-cat-id'] = (string) $item->object_id;
	}
	return $atts;
}
add_filter('nav_menu_link_attributes', 'ocellaris_product_cat_menu_link_attrs', 10, 3);

// AJAX endpoint: get subcategories (children + grandchildren) for a product_cat
function ocellaris_get_subcategories() {
	if ( ! isset($_POST['nonce']) || ! wp_verify_nonce( $_POST['nonce'], 'ocellaris_menu_nonce' ) ) {
		wp_send_json_error( array( 'message' => 'Invalid nonce' ), 403 );
	}

	$cat_id = isset($_POST['catId']) ? absint($_POST['catId']) : 0;
	if ( ! $cat_id ) {
		wp_send_json_error( array( 'message' => 'Invalid category ID' ), 400 );
	}

	$parent = get_term( $cat_id, 'product_cat' );
	if ( ! $parent || is_wp_error($parent) ) {
		wp_send_json_error( array( 'message' => 'Category not found' ), 404 );
	}

	$children = get_terms( array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => true,
		'parent'     => $cat_id,
	) );

	$groups = array();

	foreach ( $children as $child ) {
		$grandchildren = get_terms( array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'parent'     => $child->term_id,
		) );

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

	wp_send_json_success( array(
		'title'  => $parent->name,
		'groups' => $groups,
	) );
}
add_action('wp_ajax_ocellaris_get_subcategories', 'ocellaris_get_subcategories');
add_action('wp_ajax_nopriv_ocellaris_get_subcategories', 'ocellaris_get_subcategories');

// Asegurar que el menú de categorías exista, esté asignado y poblado con categorías top-level
function ocellaris_ensure_sidebar_menu() {
	// Evitar sobrescribir si el usuario ya tiene el menú asignado y con items
	$locations = get_theme_mod('nav_menu_locations', array());
	$menu_id   = isset($locations['sidebar-menu']) ? (int) $locations['sidebar-menu'] : 0;

	// Si no hay menú asignado a la ubicación, crear/usar "Ocellaris Categorías" y asignarlo
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

	// Poblar con categorías top-level si el menú está vacío
	$items = wp_get_nav_menu_items( $menu_id );
	if ( empty( $items ) ) {
		$top_cats = get_terms( array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'parent'     => 0,
		) );

		if ( ! is_wp_error( $top_cats ) ) {
			foreach ( $top_cats as $cat ) {
				wp_update_nav_menu_item( $menu_id, 0, array(
					'menu-item-object'     => 'product_cat',
					'menu-item-object-id'  => $cat->term_id,
					'menu-item-type'       => 'taxonomy',
					'menu-item-title'      => $cat->name,
					'menu-item-url'        => get_term_link( $cat ),
					'menu-item-status'     => 'publish',
				) );
			}
		}
	}
}
add_action( 'admin_init', 'ocellaris_ensure_sidebar_menu' );

/**
 * OCELLARIS CHECKOUT CUSTOMIZATIONS
 * Personalizaciones del checkout de WooCommerce
 */

/**
 * Cambiar textos de WooCommerce al español
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
 * Función específica para el título de página My Account
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
 * Cambiar el título de la página My Account en el head
 */
function ocellaris_change_account_page_title( $title ) {
	if ( function_exists( 'is_account_page' ) && is_account_page() ) {
		$title = str_replace( 'My account', 'Mi cuenta', $title );
	}
	return $title;
}
add_filter( 'wp_title', 'ocellaris_change_account_page_title', 10, 1 );
add_filter( 'document_title_parts', function( $title_parts ) {
	if ( function_exists( 'is_account_page' ) && is_account_page() && isset( $title_parts['title'] ) ) {
		$title_parts['title'] = str_replace( 'My account', 'Mi cuenta', $title_parts['title'] );
	}
	return $title_parts;
}, 10, 1 );

/**
 * Filtro específico para el tema Astra
 */
add_filter( 'astra_the_title_enabled', '__return_true' );
add_filter( 'astra_page_title', function( $title ) {
	if ( function_exists( 'is_account_page' ) && is_account_page() ) {
		$title = str_replace( 'My account', 'Mi cuenta', $title );
	}
	return $title;
}, 10, 1 );

/**
 * Remover el enlace "Descargas" del menú de Mi cuenta
 */
function ocellaris_remove_downloads_from_account_menu( $items ) {
	unset( $items['downloads'] );
	return $items;
}
add_filter( 'woocommerce_account_menu_items', 'ocellaris_remove_downloads_from_account_menu' );

/**
 * Deshabilitar la opción de "Enviar a una dirección diferente"
 * Los datos de envío serán los mismos que los de pedido/facturación
 */
function ocellaris_disable_ship_to_different_address( $ship_to_different_address ) {
	return false;
}
add_filter( 'woocommerce_ship_to_different_address_checked', 'ocellaris_disable_ship_to_different_address' );

/**
 * Ocultar completamente la sección de "Enviar a una dirección diferente"
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
 * Ocultar opciones de envío en el carrito y mostrar mensaje personalizado
 * Los costos de envío solo se calculan en el checkout
 */
function ocellaris_hide_shipping_in_cart() {
	if ( is_cart() ) {
		?>
		<style>
			/* Ocultar lista de métodos de envío en carrito */
			.cart_totals .woocommerce-shipping-methods,
			.cart_totals .woocommerce-shipping-calculator,
			.cart_totals .woocommerce-shipping-destination {
				display: none !important;
			}
			
			/* Estilos para el mensaje personalizado */
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
 * Reemplazar el contenido de envío en el carrito con mensaje personalizado
 */
function ocellaris_custom_cart_shipping_message( $shipping_label ) {
	if ( is_cart() ) {
		return '<span class="ocellaris-shipping-notice">Los costos de envío se calculan en el Checkout de pago.</span>';
	}
	return $shipping_label;
}
add_filter( 'woocommerce_cart_shipping_method_full_label', 'ocellaris_custom_cart_shipping_message', 10, 1 );

/**
 * Deshabilitar el calculador de envío en el carrito
 */
add_filter( 'woocommerce_shipping_show_shipping_calculator', '__return_false' );

/**
 * Mostrar mensaje personalizado en lugar de las opciones de envío en el carrito
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
 * Cargar script para filtrar opciones de envío en checkout
 */
function ocellaris_checkout_shipping_filter_script() {
	// Verificar múltiples condiciones para asegurar que estamos en checkout
	$is_checkout_page = is_checkout() || 
	                   (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/checkout') !== false) ||
	                   (function_exists('wc_get_page_id') && is_page(wc_get_page_id('checkout')));
	
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
 * BACKUP: Cargar script directamente en footer si estamos en checkout
 */
function ocellaris_checkout_shipping_filter_footer() {
	// Verificar si estamos en checkout
	if (strpos($_SERVER['REQUEST_URI'], '/checkout') !== false) {
		?>
		<script>
		console.log('🔥 DIRECT SCRIPT INJECTION!');
		</script>
		<script type="text/javascript" src="<?php echo get_stylesheet_directory_uri(); ?>/assets/js/checkout-shipping-filter.js?v=<?php echo time(); ?>"></script>
		<?php
	}
}
add_action( 'wp_footer', 'ocellaris_checkout_shipping_filter_footer' );
add_action( 'wp_enqueue_scripts', 'ocellaris_checkout_shipping_filter_script' );

/**
 * OCELLARIS PRODUCT CATALOG CUSTOMIZATIONS
 * Funciones para personalizar la visualización de productos en el catálogo
 */

/**
 * Get product brand name from various taxonomies
 */
function ocellaris_get_product_brand( $product_id = null ) {
	if ( ! $product_id ) {
		global $product;
		if ( ! $product ) {
			return '';
		}
		$product_id = $product->get_id();
	}

	// Try different brand taxonomies
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
 * Enqueue catalog styles if we're on a shop page
 */
function ocellaris_enqueue_catalog_styles() {
	if ( is_shop() || is_product_category() || is_product_tag() || is_product_taxonomy() ) {
		wp_enqueue_style( 'ocellaris-catalog-styles', get_stylesheet_uri(), array(), CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION );
	}
}
add_action( 'wp_enqueue_scripts', 'ocellaris_enqueue_catalog_styles', 20 );

/**
 * Force WooCommerce to use our custom product loop structure
 */
function ocellaris_custom_loop_structure() {
	if ( is_shop() || is_product_category() || is_product_tag() || is_product_taxonomy() ) {
		// Add custom CSS to override any conflicting styles
		add_action( 'wp_head', 'ocellaris_force_catalog_styles', 999 );
	}
}
add_action( 'wp', 'ocellaris_custom_loop_structure' );

/**
 * Force catalog styles to override any conflicting CSS
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
	
	/* Force 3 column layout */
	body.woocommerce ul.products.columns-4,
	body.woocommerce-page ul.products.columns-4,
	.woocommerce ul.products.columns-4,
	.woocommerce-page ul.products.columns-4 {
		grid-template-columns: repeat(3, 1fr) !important;
	}
	
	@media (max-width: 767px) {
		body.woocommerce ul.products,
		body.woocommerce-page ul.products,
		.woocommerce ul.products,
		.woocommerce-page ul.products {
			grid-template-columns: repeat(2, 1fr) !important;
			gap: 20px !important;
			padding: 0 15px !important;
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

	body.woocommerce ul.products li.product .featured-product-image img,
	body.woocommerce-page ul.products li.product .featured-product-image img,
	.woocommerce ul.products li.product .featured-product-image img,
	.woocommerce-page ul.products li.product .featured-product-image img {
		width: 100% !important;
		height: 100% !important;
		object-fit: cover !important;
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
 * Ensure WooCommerce hooks are properly loaded for catalog display
 */
function ocellaris_ensure_woocommerce_hooks() {
	if ( is_shop() || is_product_category() || is_product_tag() || is_product_taxonomy() ) {
		// Ensure price hook is attached
		if ( ! has_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price' ) ) {
			add_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
		}
		
		// Ensure add to cart button hook is attached
		if ( ! has_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart' ) ) {
			add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
		}
	}
}
add_action( 'wp', 'ocellaris_ensure_woocommerce_hooks' );

/**
 * Set default shop columns to 3 for better content display
 */
function ocellaris_shop_columns() {
	return 3;
}
add_filter( 'loop_shop_columns', 'ocellaris_shop_columns', 999 );

/**
 * Force 3 columns in all WooCommerce product listings
 */
function ocellaris_force_3_columns() {
	if ( is_shop() || is_product_category() || is_product_tag() || is_product_taxonomy() ) {
		global $woocommerce_loop;
		$woocommerce_loop['columns'] = 3;
	}
}
add_action( 'woocommerce_before_shop_loop', 'ocellaris_force_3_columns', 5 );
