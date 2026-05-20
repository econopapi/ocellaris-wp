<?php
/**
 * Ocellaris Catalog Filters Module
 *
 * @package Ocellaris Custom Astra
 * @since 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * OCELLARIS FILTER BLOCKS (Categories checkbox + Brand dropdown)
 * Registers blocks and implements render callbacks + frontend scripts
 */
function ocellaris_register_filter_blocks() {
	// Categories block (editor + frontend)
	wp_register_script(
		'ocellaris-filter-categories-block',
		get_stylesheet_directory_uri() . '/blocks/filter-categories/block.js',
		array('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components'),
		CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION
	);

	wp_register_script(
		'ocellaris-filter-categories-frontend',
		get_stylesheet_directory_uri() . '/blocks/filter-categories/frontend.js',
		array(),
		CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION,
		true
	);

	wp_register_style(
		'ocellaris-filter-categories-block-editor',
		get_stylesheet_directory_uri() . '/blocks/filter-categories/editor.css',
		array('wp-edit-blocks'),
		CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION
	);

	wp_register_style(
		'ocellaris-filter-categories-block',
		get_stylesheet_directory_uri() . '/blocks/filter-categories/style.css',
		array(),
		CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION
	);

	register_block_type(
		'ocellaris/filter-categories',
		array(
			'editor_script' => 'ocellaris-filter-categories-block',
			'editor_style'  => 'ocellaris-filter-categories-block-editor',
			'style'         => 'ocellaris-filter-categories-block',
			'render_callback' => 'ocellaris_render_filter_categories_block',
			'attributes' => array(
				'title' => array('type' => 'string', 'default' => 'Filtrar Por'),
			),
		)
	);

	// Brand block (editor + frontend)
	wp_register_script(
		'ocellaris-filter-brand-block',
		get_stylesheet_directory_uri() . '/blocks/filter-brand/block.js',
		array('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components'),
		CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION
	);

	wp_register_script(
		'ocellaris-filter-brand-frontend',
		get_stylesheet_directory_uri() . '/blocks/filter-brand/frontend.js',
		array(),
		CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION,
		true
	);

	wp_register_style(
		'ocellaris-filter-brand-block-editor',
		get_stylesheet_directory_uri() . '/blocks/filter-brand/editor.css',
		array('wp-edit-blocks'),
		CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION
	);

	wp_register_style(
		'ocellaris-filter-brand-block',
		get_stylesheet_directory_uri() . '/blocks/filter-brand/style.css',
		array(),
		CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION
	);

	register_block_type(
		'ocellaris/filter-brand',
		array(
			'editor_script' => 'ocellaris-filter-brand-block',
			'editor_style'  => 'ocellaris-filter-brand-block-editor',
			'style'         => 'ocellaris-filter-brand-block',
			'render_callback' => 'ocellaris_render_filter_brand_block',
			'attributes' => array(
				'title' => array('type' => 'string', 'default' => 'Marca'),
			),
		)
	);
}
add_action('init', 'ocellaris_register_filter_blocks');

/**
 * Register and enqueue assets for mobile filters drawer (button + drawer UI)
 */
function ocellaris_register_filters_drawer_assets() {
	// register files
	wp_register_script(
		'ocellaris-filters-drawer',
		get_stylesheet_directory_uri() . '/assets/js/filters-drawer.js',
		array(),
		CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION,
		true
	);

	wp_register_style(
		'ocellaris-filters-drawer',
		get_stylesheet_directory_uri() . '/assets/css/filters-drawer.css',
		array(),
		CHILD_THEME_OCELLARIS_CUSTOM_ASTRA_VERSION
	);

	// Enqueue only on WooCommerce archive/shop/category pages
	if ( function_exists('is_shop') && ( is_shop() || is_post_type_archive('product') || is_tax('product_cat') || is_tax('product_tag') ) ) {
		wp_enqueue_style('ocellaris-filters-drawer');
		wp_enqueue_script('ocellaris-filters-drawer');
	}
}
add_action('wp_enqueue_scripts', 'ocellaris_register_filters_drawer_assets', 20);

/**
 * Render callback: categories filter block (checkboxes). Outputs checkboxes and preserves other query vars.
 */
function ocellaris_render_filter_categories_block($attributes) {
	$title = isset($attributes['title']) ? $attributes['title'] : 'Filtrar Por';

	$terms = get_terms(array(
		'taxonomy' => 'product_cat',
		'hide_empty' => true,
		'orderby' => 'name',
		'order' => 'ASC',
	));

	if (empty($terms) || is_wp_error($terms)) {
		return '';
	}

	$selected = array();
	if (isset($_GET['filter_cat']) && !empty($_GET['filter_cat'])) {
		if (is_array($_GET['filter_cat'])) {
			$selected = array_map('sanitize_text_field', wp_unslash($_GET['filter_cat']));
		} else {
			$selected = array_filter(array_map('sanitize_text_field', explode(',', sanitize_text_field(wp_unslash($_GET['filter_cat'])))));
		}
	}

	ob_start();
	?>
	<div class="ocellaris-filter-categories">
		<?php if (!empty($title)): ?>
			<h3 class="filter-title"><?php echo esc_html($title); ?></h3>
		<?php endif; ?>

		<form class="ocellaris-filter-categories-form" action="" method="get">
			<?php
			// Preserve other query vars (except our filter params and paged)
			foreach ($_GET as $k => $v) {
				if (in_array($k, array('filter_cat', 'filter_brand', 'paged'))) continue;
				if (is_array($v)) {
					foreach ($v as $sub) {
						echo '<input type="hidden" name="' . esc_attr($k) . '[]" value="' . esc_attr($sub) . '">';
					}
				} else {
					echo '<input type="hidden" name="' . esc_attr($k) . '" value="' . esc_attr($v) . '">';
				}
			}
			?>

			<div class="filter-options">
				<?php foreach ($terms as $term):
					$checked = in_array($term->slug, $selected);
				?>
					<label class="filter-option">
						<input type="checkbox" name="filter_cat[]" value="<?php echo esc_attr($term->slug); ?>" class="oc-filter-cat-checkbox" <?php echo $checked ? 'checked' : ''; ?> />
						<?php echo esc_html($term->name); ?>
					</label>
				<?php endforeach; ?>
			</div>

			<noscript><button type="submit" class="button">Aplicar</button></noscript>
		</form>
	</div>
	<?php

	// enqueue frontend behaviour
	wp_enqueue_script('ocellaris-filter-categories-frontend');

	return ob_get_clean();
}

/**
 * Render callback: brand filter block (dropdown). Detects brand taxonomy (`product_brand` or `pa_brand`).
 */
function ocellaris_render_filter_brand_block($attributes) {
	$title = isset($attributes['title']) ? $attributes['title'] : 'Marca';

	$brand_tax = taxonomy_exists('product_brand') ? 'product_brand' : (taxonomy_exists('pa_brand') ? 'pa_brand' : false);
	if (!$brand_tax) {
		return ''; // no brand taxonomy available
	}

	$terms = get_terms(array(
		'taxonomy' => $brand_tax,
		'hide_empty' => true,
		'orderby' => 'name',
		'order' => 'ASC',
	));

	if (empty($terms) || is_wp_error($terms)) {
		return '';
	}

	$selected = array();
	if (isset($_GET['filter_brand']) && !empty($_GET['filter_brand'])) {
		if (is_array($_GET['filter_brand'])) {
			$selected = array_map('sanitize_text_field', wp_unslash($_GET['filter_brand']));
		} else {
			$selected = array_filter(array_map('sanitize_text_field', explode(',', sanitize_text_field(wp_unslash($_GET['filter_brand'])))));
		}
	}

	ob_start();
	?>
	<div class="ocellaris-filter-brand">
		<?php if (!empty($title)): ?>
			<h3 class="filter-title"><?php echo esc_html($title); ?></h3>
		<?php endif; ?>

		<form class="ocellaris-filter-brand-form" action="" method="get">
			<?php
			// Preserve other query vars
			foreach ($_GET as $k => $v) {
				if (in_array($k, array('filter_cat', 'filter_brand', 'paged'))) continue;
				if (is_array($v)) {
					foreach ($v as $sub) {
						echo '<input type="hidden" name="' . esc_attr($k) . '[]" value="' . esc_attr($sub) . '">';
					}
				} else {
					echo '<input type="hidden" name="' . esc_attr($k) . '" value="' . esc_attr($v) . '">';
				}
			}
			?>

			<select name="filter_brand" class="oc-filter-brand-select brand-select">
				<option value=""><?php echo esc_html__('Todas', 'ocellaris-custom-astra'); ?></option>
				<?php foreach ($terms as $term):
					$sel = in_array($term->slug, $selected);
				?>
					<option value="<?php echo esc_attr($term->slug); ?>" <?php echo $sel ? 'selected' : ''; ?>><?php echo esc_html($term->name); ?></option>
				<?php endforeach; ?>
			</select>

			<noscript><button type="submit" class="button">Aplicar</button></noscript>
		</form>
	</div>
	<?php

	wp_enqueue_script('ocellaris-filter-brand-frontend');

	return ob_get_clean();
}

/**
 * Apply filters from our sidebar blocks to the main products query.
 * Uses `filter_cat` and `filter_brand` GET parameters (comma separated slugs or arrays).
 */
function ocellaris_apply_block_filters($query) {
	if (is_admin() || ! $query->is_main_query()) {
		return;
	}

	// Only affect shop/product archives
	if (! ( function_exists('is_shop') && ( is_shop() || is_post_type_archive('product') || is_tax('product_cat') || is_tax('product_tag') ) ) ) {
		return;
	}

	$tax_queries = $query->get('tax_query') ?: array();

	// Categories filter
	if (isset($_GET['filter_cat']) && !empty($_GET['filter_cat'])) {
		if (is_array($_GET['filter_cat'])) {
			$cats = array_map('sanitize_text_field', wp_unslash($_GET['filter_cat']));
		} else {
			$cats = array_filter(array_map('sanitize_text_field', explode(',', sanitize_text_field(wp_unslash($_GET['filter_cat'])))));
		}

		if (!empty($cats)) {
			$tax_queries[] = array(
				'taxonomy' => 'product_cat',
				'field' => 'slug',
				'terms' => $cats,
				'operator' => 'IN',
			);
		}
	}

	// Brand filter (try product_brand then pa_brand)
	if (isset($_GET['filter_brand']) && !empty($_GET['filter_brand'])) {
		if (is_array($_GET['filter_brand'])) {
			$brands = array_map('sanitize_text_field', wp_unslash($_GET['filter_brand']));
		} else {
			$brands = array_filter(array_map('sanitize_text_field', explode(',', sanitize_text_field(wp_unslash($_GET['filter_brand'])))));
		}

		if (!empty($brands)) {
			$brand_tax = taxonomy_exists('product_brand') ? 'product_brand' : (taxonomy_exists('pa_brand') ? 'pa_brand' : false);
			if ($brand_tax) {
				$tax_queries[] = array(
					'taxonomy' => $brand_tax,
					'field' => 'slug',
					'terms' => $brands,
					'operator' => 'IN',
				);
			}
		}
	}

	if (!empty($tax_queries)) {
		$query->set('tax_query', $tax_queries);
	}
}
add_action('pre_get_posts', 'ocellaris_apply_block_filters', 20);
