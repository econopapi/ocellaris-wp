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
 * Render Ocellaris Featured Brands Block
 */
function ocellaris_render_featured_brands_block($attributes) {
	$selected_brands = isset($attributes['selectedBrands']) ? $attributes['selectedBrands']:array();
	$title = isset($attributes['title'])? $attributes['title']: 'Marcas Destacadas';
	$autoplay_speed = isset($attributes['autoplaySpeed'])? $attributes['autoplaySpeed']: 3000;

	if(empty($selected_brands)) {
		return '';
	}

	// enqueue carousel script
	wp_enqueue_script('ocellaris-brands-carousel');

	ob_start();
	?>

	<div class="ocellaris-featured-brands" data-autoplay-speed="<?php echo esc_attr($autoplay_speed); ?>">
		<?php if (!empty($title)): ?>
			<h2 class="brands-title"><?php echo esc_html($title); ?></h2>
		<?php endif; ?>
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
	</div>
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
 * Cambiar "Detalles de facturación" por "Detalles de pedido"
 */
function ocellaris_change_billing_details_title( $translated_text, $text, $domain ) {
	if ( $domain === 'woocommerce' ) {
		switch ( $text ) {
			case 'Billing details':
			case 'Billing &amp; Shipping':
				$translated_text = 'Detalles de pedido';
				break;
			case 'Detalles de facturación':
				$translated_text = 'Detalles de pedido';
				break;
		}
	}
	return $translated_text;
}
add_filter( 'gettext', 'ocellaris_change_billing_details_title', 20, 3 );

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
	if ( is_checkout() ) {
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
