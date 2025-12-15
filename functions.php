<?php
/**
 * Ocellaris Custom Astra Theme functions and definitions
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
 * Register Navigation Menus
 */
function ocellaris_register_menus() {
	register_nav_menus(
		array(
			'sidebar-menu' => __('Sidebar Menu', 'ocellaris-custom-astra'),
		));
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