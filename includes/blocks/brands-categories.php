<?php
/**
 * Ocellaris Blocks Module - Brands and Categories
 *
 * @package Ocellaris Custom Astra
 * @since 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Ocellaris Product Category Block.
 */
function ocellaris_register_product_categories_block() {
	wp_register_script(
		'ocellaris-product-categories-block',
		get_stylesheet_directory_uri() . '/blocks/product-categories/block.js',
		array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-data' ),
		CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION
	);

	wp_register_style(
		'ocellaris-product-categories-block-editor',
		get_stylesheet_directory_uri() . '/blocks/product-categories/editor.css',
		array( 'wp-edit-blocks' ),
		CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION
	);

	wp_register_style(
		'ocellaris-product-categories-block',
		get_stylesheet_directory_uri() . '/blocks/product-categories/style.css',
		array(),
		CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION
	);

	register_block_type(
		'ocellaris/product-categories',
		array(
			'editor_script'   => 'ocellaris-product-categories-block',
			'editor_style'    => 'ocellaris-product-categories-block-editor',
			'style'           => 'ocellaris-product-categories-block',
			'render_callback' => 'ocellaris_render_product_categories_block',
			'attributes'      => array(
				'selectedCategories' => array(
					'type'    => 'array',
					'default' => array(),
				),
				'title' => array(
					'type'    => 'string',
					'default' => 'Categorías Top',
				),
				'subtitle' => array(
					'type'    => 'string',
					'default' => '',
				),
			),
		)
	);
}
add_action( 'init', 'ocellaris_register_product_categories_block' );

/**
 * Render Ocellaris Product Categories Block.
 */
function ocellaris_render_product_categories_block($attributes) {
	$selected_categories = isset($attributes['selectedCategories']) ? $attributes['selectedCategories'] : array();
	$title = isset($attributes['title']) ? $attributes['title'] : 'Categorías Top';
	$subtitle = isset($attributes['subtitle']) ? $attributes['subtitle'] : '';

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
					$image_url = $thumbnail_id ? wp_get_attachment_url($thumbnail_id) : wc_placeholder_img_src();
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
		<?php if (!empty($subtitle)): ?>
			<h4 class="category-subtitle"><?php echo esc_html($subtitle); ?></h4>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Register Ocellaris Featured Brands Block.
 */
function ocellaris_register_featured_brands_block() {
	wp_register_script(
		'ocellaris-featured-brands-block',
		get_stylesheet_directory_uri() . '/blocks/featured-brands/block.js',
		array('wp-blocks', 'wp-element', 'wp-components', 'wp-data'),
		CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION
	);

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

	wp_register_script(
		'ocellaris-brands-carousel',
		get_stylesheet_directory_uri() . '/blocks/featured-brands/carousel.js',
		array('jquery'),
		CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION,
		true
	);

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
					'default' => 3000,
				),
			),
		)
	);
}
add_action('init', 'ocellaris_register_featured_brands_block');

/**
 * Register Ocellaris All Brands Block.
 */
function ocellaris_register_all_brands_block() {
	wp_register_script(
		'ocellaris-all-brands-block',
		get_stylesheet_directory_uri() . '/blocks/all-brands/block.js',
		array('wp-blocks', 'wp-element', 'wp-components'),
		CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION
	);

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

	wp_register_script(
		'ocellaris-all-brands-frontend',
		get_stylesheet_directory_uri() . '/blocks/all-brands/frontend.js',
		array('jquery'),
		CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION,
		true
	);

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
 * Render Ocellaris Featured Brands Block.
 */
function ocellaris_render_featured_brands_block($attributes) {
	$selected_brands = isset($attributes['selectedBrands']) ? $attributes['selectedBrands'] : array();
	$title = isset($attributes['title']) ? $attributes['title'] : 'Marcas Destacadas';
	$display_mode = isset($attributes['displayMode']) ? $attributes['displayMode'] : 'carousel';
	$autoplay_speed = isset($attributes['autoplaySpeed']) ? $attributes['autoplaySpeed'] : 3000;

	if (empty($selected_brands)) {
		return '';
	}

	if ($display_mode === 'carousel') {
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
							$image_url = $thumbnail_id ? wp_get_attachment_url($thumbnail_id) : '';
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
					$image_url = $thumbnail_id ? wp_get_attachment_url($thumbnail_id) : '';
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
 * Render Ocellaris All Brands Block.
 */
function ocellaris_render_all_brands_block($attributes) {
	$title = isset($attributes['title']) ? $attributes['title'] : 'TODAS NUESTRAS MARCAS';
	$show_alphabet_filter = isset($attributes['showAlphabetFilter']) ? $attributes['showAlphabetFilter'] : true;
	$columns = isset($attributes['columns']) ? $attributes['columns'] : 4;
	$display_style = isset($attributes['displayStyle']) ? $attributes['displayStyle'] : 'grid';
	$show_brand_count = isset($attributes['showBrandCount']) ? $attributes['showBrandCount'] : true;
	$brand_image_size = isset($attributes['brandImageSize']) ? $attributes['brandImageSize'] : 'medium';

	wp_enqueue_script('ocellaris-all-brands-frontend');

	$brands = get_terms(array(
		'taxonomy' => 'product_brand',
		'orderby' => 'name',
		'order' => 'ASC',
		'hide_empty' => true,
	));

	if(empty($brands) || is_wp_error($brands)) {
		return '<div class="ocellaris-all-brands">\n\t\t\t\t\t<div class="brands-empty">\n\t\t\t\t\t\t<div class="brands-empty-icon">🏷️</div>\n\t\t\t\t\t\t<p>No se encontraron marcas disponibles.</p>\n\t\t\t\t\t</div>\n\t\t\t\t</div>';
	}

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
		window.ocellarisAllBrandsData = {
			alphabet: <?php echo json_encode(array_keys($brands_by_letter)); ?>,
			brandCount: <?php echo count($brands); ?>
		};
	</script>
	<?php
	return ob_get_clean();
}
