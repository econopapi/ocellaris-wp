<?php
/**
 * Ocellaris MSI Promotions - Admin Page
 * 
 * Panel de administración para gestionar las promociones de
 * Meses Sin Intereses (MSI) por pasarela de pago.
 * 
 * @package Ocellaris Custom Astra
 * @since 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the top-level admin menu "Ocellaris Promociones"
 */
function ocellaris_msi_register_admin_menu() {
	// Top-level menu
	add_menu_page(
		'Ocellaris Promociones',           // Page title
		'Ocellaris Promociones',           // Menu title
		'manage_woocommerce',              // Capability
		'ocellaris-promociones',           // Menu slug
		'ocellaris_msi_render_mercadopago_page', // Callback (default submenu)
		'dashicons-tickets-alt',           // Icon
		56                                 // Position (after WooCommerce)
	);

	// Submenu: MSI MercadoPago
	add_submenu_page(
		'ocellaris-promociones',
		'MSI MercadoPago',
		'MSI MercadoPago',
		'manage_woocommerce',
		'ocellaris-promociones',  // Same slug as parent = first submenu
		'ocellaris_msi_render_mercadopago_page'
	);

	// Future: Submenu for OpenPay
	// add_submenu_page(
	// 	'ocellaris-promociones',
	// 	'MSI OpenPay',
	// 	'MSI OpenPay',
	// 	'manage_woocommerce',
	// 	'ocellaris-msi-openpay',
	// 	'ocellaris_msi_render_openpay_page'
	// );
}
add_action( 'admin_menu', 'ocellaris_msi_register_admin_menu' );

/**
 * Register settings for MSI MercadoPago
 */
function ocellaris_msi_register_settings() {
	register_setting(
		'ocellaris_msi_mercadopago',
		'ocellaris_msi_mp_enabled',
		array( 'sanitize_callback' => 'ocellaris_sanitize_checkbox', 'default' => '0' )
	);

	register_setting(
		'ocellaris_msi_mercadopago',
		'ocellaris_msi_mp_products',
		array( 'sanitize_callback' => 'ocellaris_msi_sanitize_products', 'default' => array() )
	);

	register_setting(
		'ocellaris_msi_mercadopago',
		'ocellaris_msi_mp_mixed_cart_message',
		array(
			'sanitize_callback' => 'wp_kses_post',
			'default' => 'Algunos productos de tu carrito no son elegibles para Meses Sin Intereses. Para comprar a MSI, retira del carrito los productos que no participan en esta promoción.',
		)
	);
}
add_action( 'admin_init', 'ocellaris_msi_register_settings' );

/**
 * Sanitize MSI products data
 */
function ocellaris_msi_sanitize_products( $input ) {
	if ( ! is_array( $input ) ) {
		return array();
	}

	$sanitized = array();
	foreach ( $input as $product_id => $data ) {
		$pid = absint( $product_id );
		if ( $pid <= 0 ) continue;

		$months = array();
		if ( isset( $data['months'] ) && is_array( $data['months'] ) ) {
			$allowed_months = array( 3, 6, 9, 12 );
			foreach ( $data['months'] as $m ) {
				$m = absint( $m );
				if ( in_array( $m, $allowed_months, true ) ) {
					$months[] = $m;
				}
			}
		}

		if ( ! empty( $months ) ) {
			sort( $months );
			$sanitized[ $pid ] = array( 'months' => $months );
		}
	}

	return $sanitized;
}

/**
 * Enqueue admin scripts and styles for the MSI page
 */
function ocellaris_msi_admin_scripts( $hook ) {
	if ( $hook !== 'toplevel_page_ocellaris-promociones' ) {
		return;
	}

	// WooCommerce product search (Select2/selectWoo)
	wp_enqueue_script( 'wc-enhanced-select' );
	wp_enqueue_style( 'woocommerce_admin_styles' );

	// Inline admin styles
	wp_add_inline_style( 'woocommerce_admin_styles', ocellaris_msi_admin_inline_styles() );
}
add_action( 'admin_enqueue_scripts', 'ocellaris_msi_admin_scripts' );

/**
 * Get inline admin styles
 */
function ocellaris_msi_admin_inline_styles() {
	return '
		.ocellaris-msi-wrap {
			max-width: 900px;
		}
		.ocellaris-msi-wrap .ocellaris-msi-header {
			display: flex;
			align-items: center;
			gap: 10px;
			margin-bottom: 5px;
		}
		.ocellaris-msi-wrap .ocellaris-msi-header h1 {
			margin: 0;
		}
		.ocellaris-msi-wrap .ocellaris-msi-header .dashicons {
			font-size: 30px;
			width: 30px;
			height: 30px;
			color: #009EE3;
		}
		.ocellaris-msi-product-table {
			width: 100%;
			border-collapse: collapse;
			margin: 15px 0;
			background: #fff;
			border: 1px solid #c3c4c7;
			border-radius: 4px;
		}
		.ocellaris-msi-product-table th,
		.ocellaris-msi-product-table td {
			padding: 12px 15px;
			text-align: left;
			border-bottom: 1px solid #e0e0e0;
			vertical-align: middle;
		}
		.ocellaris-msi-product-table th {
			background: #f0f0f1;
			font-weight: 600;
			font-size: 13px;
		}
		.ocellaris-msi-product-table tr:last-child td {
			border-bottom: none;
		}
		.ocellaris-msi-product-table .product-thumb {
			width: 40px;
			height: 40px;
			object-fit: cover;
			border-radius: 4px;
			border: 1px solid #ddd;
		}
		.ocellaris-msi-product-table .product-info {
			display: flex;
			align-items: center;
			gap: 10px;
		}
		.ocellaris-msi-product-table .product-name {
			font-weight: 500;
		}
		.ocellaris-msi-product-table .product-sku {
			color: #888;
			font-size: 12px;
		}
		.ocellaris-msi-product-table .month-checks {
			display: flex;
			gap: 15px;
		}
		.ocellaris-msi-product-table .month-checks label {
			display: flex;
			align-items: center;
			gap: 4px;
			cursor: pointer;
			font-size: 13px;
			padding: 4px 8px;
			border-radius: 4px;
			border: 1px solid #ddd;
			background: #f9f9f9;
			transition: all 0.2s;
		}
		.ocellaris-msi-product-table .month-checks label:hover {
			border-color: #009EE3;
			background: #f0f8ff;
		}
		.ocellaris-msi-product-table .month-checks label.checked {
			border-color: #009EE3;
			background: #e6f4fd;
			font-weight: 600;
		}
		.ocellaris-msi-product-table .remove-product {
			color: #b32d2e;
			cursor: pointer;
			font-size: 16px;
			border: none;
			background: none;
			padding: 5px;
		}
		.ocellaris-msi-product-table .remove-product:hover {
			color: #dc3232;
		}
		.ocellaris-msi-add-product-row {
			margin: 15px 0;
			display: flex;
			gap: 10px;
			align-items: center;
		}
		.ocellaris-msi-add-product-row .wc-product-search {
			min-width: 350px;
		}
		.ocellaris-msi-empty-state {
			text-align: center;
			padding: 40px 20px;
			color: #888;
		}
		.ocellaris-msi-empty-state .dashicons {
			font-size: 48px;
			width: 48px;
			height: 48px;
			color: #ccc;
			margin-bottom: 10px;
		}
		.ocellaris-msi-info-box {
			background: #f0f8ff;
			border-left: 4px solid #009EE3;
			padding: 12px 16px;
			margin: 15px 0;
			font-size: 13px;
			line-height: 1.5;
		}
		.ocellaris-msi-status-badge {
			display: inline-block;
			padding: 3px 10px;
			border-radius: 12px;
			font-size: 12px;
			font-weight: 600;
		}
		.ocellaris-msi-status-badge.active {
			background: #d4edda;
			color: #155724;
		}
		.ocellaris-msi-status-badge.inactive {
			background: #f8d7da;
			color: #721c24;
		}
		#ocellaris-msi-select-all-months {
			margin: 0 0 10px 0;
		}
		#ocellaris-msi-select-all-months label {
			margin-right: 12px;
			cursor: pointer;
		}
	';
}

/**
 * Render the MercadoPago MSI admin page
 */
function ocellaris_msi_render_mercadopago_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	$enabled     = get_option( 'ocellaris_msi_mp_enabled', '0' );
	$products    = get_option( 'ocellaris_msi_mp_products', array() );
	$cart_msg    = get_option( 'ocellaris_msi_mp_mixed_cart_message', 'Algunos productos de tu carrito no son elegibles para Meses Sin Intereses. Para comprar a MSI, retira del carrito los productos que no participan en esta promoción.' );

	if ( ! is_array( $products ) ) {
		$products = array();
	}

	// Show success message
	if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] === 'true' ) {
		echo '<div class="notice notice-success is-dismissible"><p><strong>¡Configuración de MSI guardada correctamente!</strong></p></div>';
	}

	?>
	<div class="wrap ocellaris-msi-wrap">
		<div class="ocellaris-msi-header">
			<span class="dashicons dashicons-tickets-alt"></span>
			<h1>MSI MercadoPago</h1>
			<span class="ocellaris-msi-status-badge <?php echo $enabled === '1' ? 'active' : 'inactive'; ?>">
				<?php echo $enabled === '1' ? 'Activo' : 'Inactivo'; ?>
			</span>
		</div>

		<p class="description">
			Gestiona qué productos pueden ser comprados a Meses Sin Intereses (MSI) con tarjeta de crédito
			a través de MercadoPago. Los productos que no estén en esta lista se bloquearán a pago de contado (1 mensualidad).
		</p>

		<div class="ocellaris-msi-info-box">
			<strong>¿Cómo funciona?</strong><br>
			Cuando un cliente llega al checkout con tarjeta de crédito, el sistema verifica los productos del carrito.
			Si <strong>todos</strong> los productos están en la whitelist, se muestran las cuotas configuradas.
			Si hay <strong>productos mixtos</strong> (algunos con MSI y otros sin), se bloquea a 1 mensualidad y se muestra un aviso al cliente.
			Si <strong>ningún producto</strong> tiene MSI, se bloquea automáticamente a 1 mensualidad.
		</div>

		<form action="options.php" method="post" id="ocellaris-msi-form">
			<?php settings_fields( 'ocellaris_msi_mercadopago' ); ?>

			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="ocellaris_msi_mp_enabled">Activar control de MSI</label>
					</th>
					<td>
						<label>
							<input type="checkbox"
								   id="ocellaris_msi_mp_enabled"
								   name="ocellaris_msi_mp_enabled"
								   value="1"
								   <?php checked( $enabled, '1' ); ?> />
							<strong>Habilitar filtro de MSI en el checkout</strong>
						</label>
						<p class="description">
							Si está desactivado, el comportamiento de MercadoPago será el predeterminado
							(todos los productos podrán usar MSI según la configuración del plugin).
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="ocellaris_msi_mp_mixed_cart_message">Mensaje de carrito mixto</label>
					</th>
					<td>
						<textarea id="ocellaris_msi_mp_mixed_cart_message"
								  name="ocellaris_msi_mp_mixed_cart_message"
								  rows="3"
								  class="large-text"
						><?php echo esc_textarea( $cart_msg ); ?></textarea>
						<p class="description">
							Mensaje que se mostrará cuando el carrito tenga una combinación de productos
							elegibles y no elegibles para MSI.
						</p>
					</td>
				</tr>
			</table>

			<hr>
			<h2>Productos elegibles para MSI</h2>

			<!-- Add product search -->
			<div class="ocellaris-msi-add-product-row">
				<select id="ocellaris-msi-product-search"
						class="wc-product-search"
						data-placeholder="Buscar producto por nombre o SKU..."
						data-action="woocommerce_json_search_products"
						data-exclude_type="grouped"
						style="min-width: 350px;">
				</select>
				<button type="button" id="ocellaris-msi-add-product" class="button button-primary">
					<span class="dashicons dashicons-plus-alt2" style="margin-top: 3px;"></span> Agregar producto
				</button>
			</div>

			<!-- Bulk month selection -->
			<div id="ocellaris-msi-select-all-months" style="display: <?php echo ! empty( $products ) ? 'block' : 'none'; ?>;">
				<strong>Seleccionar meses para todos:</strong>
				<label><input type="checkbox" class="bulk-month" value="3"> 3 meses</label>
				<label><input type="checkbox" class="bulk-month" value="6"> 6 meses</label>
				<label><input type="checkbox" class="bulk-month" value="9"> 9 meses</label>
				<label><input type="checkbox" class="bulk-month" value="12"> 12 meses</label>
			</div>

			<!-- Products table -->
			<table class="ocellaris-msi-product-table" id="ocellaris-msi-products-table">
				<thead>
					<tr>
						<th style="width: 45%;">Producto</th>
						<th>Meses disponibles</th>
						<th style="width: 50px;"></th>
					</tr>
				</thead>
				<tbody id="ocellaris-msi-products-body">
					<?php if ( empty( $products ) ) : ?>
						<tr class="ocellaris-msi-empty-state" id="ocellaris-msi-empty-row">
							<td colspan="3">
								<span class="dashicons dashicons-cart"></span>
								<p>No hay productos configurados para MSI.<br>Usa el buscador de arriba para agregar productos.</p>
							</td>
						</tr>
					<?php else : ?>
						<?php foreach ( $products as $product_id => $config ) :
							$product = wc_get_product( $product_id );
							if ( ! $product ) continue;
							$thumb_url = wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' );
							$months = isset( $config['months'] ) ? $config['months'] : array();
						?>
						<tr data-product-id="<?php echo esc_attr( $product_id ); ?>">
							<td>
								<div class="product-info">
									<?php if ( $thumb_url ) : ?>
										<img src="<?php echo esc_url( $thumb_url ); ?>" class="product-thumb" alt="">
									<?php endif; ?>
									<div>
										<div class="product-name"><?php echo esc_html( $product->get_name() ); ?></div>
										<?php if ( $product->get_sku() ) : ?>
											<div class="product-sku">SKU: <?php echo esc_html( $product->get_sku() ); ?></div>
										<?php endif; ?>
									</div>
								</div>
							</td>
							<td>
								<div class="month-checks">
									<?php foreach ( array( 3, 6, 9, 12 ) as $m ) :
										$checked = in_array( $m, $months, true );
									?>
									<label class="<?php echo $checked ? 'checked' : ''; ?>">
										<input type="checkbox"
											   name="ocellaris_msi_mp_products[<?php echo esc_attr( $product_id ); ?>][months][]"
											   value="<?php echo esc_attr( $m ); ?>"
											   <?php checked( $checked ); ?>
											   onchange="this.parentElement.classList.toggle('checked', this.checked);" />
										<?php echo esc_html( $m ); ?>m
									</label>
									<?php endforeach; ?>
								</div>
							</td>
							<td>
								<button type="button" class="remove-product" title="Quitar producto" onclick="ocellarisRemoveMsiProduct(this);">
									<span class="dashicons dashicons-trash"></span>
								</button>
							</td>
						</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>

			<?php submit_button( 'Guardar configuración de MSI' ); ?>
		</form>
	</div>

	<script type="text/javascript">
	jQuery(function($) {
		// Add product button
		$('#ocellaris-msi-add-product').on('click', function() {
			var $select = $('#ocellaris-msi-product-search');
			var productId = $select.val();
			var productName = $select.find('option:selected').text();

			if (!productId) {
				alert('Por favor, selecciona un producto primero.');
				return;
			}

			// Check if product already exists
			if ($('#ocellaris-msi-products-body tr[data-product-id="' + productId + '"]').length) {
				alert('Este producto ya está en la lista.');
				return;
			}

			// Remove empty state
			$('#ocellaris-msi-empty-row').remove();
			$('#ocellaris-msi-select-all-months').show();

			// Build row HTML — product image will be fetched via AJAX
			var rowHtml = '<tr data-product-id="' + productId + '">' +
				'<td>' +
					'<div class="product-info">' +
						'<div>' +
							'<div class="product-name">' + $('<span>').text(productName).html() + '</div>' +
						'</div>' +
					'</div>' +
				'</td>' +
				'<td>' +
					'<div class="month-checks">';

			[3, 6, 9, 12].forEach(function(m) {
				rowHtml += '<label>' +
					'<input type="checkbox" ' +
						'name="ocellaris_msi_mp_products[' + productId + '][months][]" ' +
						'value="' + m + '" ' +
						'checked ' +
						'onchange="this.parentElement.classList.toggle(\'checked\', this.checked);" /> ' +
					m + 'm' +
				'</label>';
			});

			rowHtml += '</div></td>' +
				'<td>' +
					'<button type="button" class="remove-product" title="Quitar producto" onclick="ocellarisRemoveMsiProduct(this);">' +
						'<span class="dashicons dashicons-trash"></span>' +
					'</button>' +
				'</td></tr>';

			$('#ocellaris-msi-products-body').append(rowHtml);

			// Mark all labels as checked
			$('#ocellaris-msi-products-body tr[data-product-id="' + productId + '"] .month-checks label').addClass('checked');

			// Clear the search
			$select.val(null).trigger('change');
		});

		// Bulk month selection
		$('.bulk-month').on('change', function() {
			var month = $(this).val();
			var isChecked = $(this).is(':checked');

			$('#ocellaris-msi-products-body tr[data-product-id]').each(function() {
				var $checkbox = $(this).find('input[value="' + month + '"]');
				$checkbox.prop('checked', isChecked);
				$checkbox.parent().toggleClass('checked', isChecked);
			});
		});
	});

	// Remove product from table
	function ocellarisRemoveMsiProduct(button) {
		var $row = jQuery(button).closest('tr');
		$row.fadeOut(200, function() {
			$row.remove();

			// Show empty state if no products left
			if (jQuery('#ocellaris-msi-products-body tr[data-product-id]').length === 0) {
				jQuery('#ocellaris-msi-products-body').html(
					'<tr class="ocellaris-msi-empty-state" id="ocellaris-msi-empty-row">' +
						'<td colspan="3">' +
							'<span class="dashicons dashicons-cart"></span>' +
							'<p>No hay productos configurados para MSI.<br>Usa el buscador de arriba para agregar productos.</p>' +
						'</td>' +
					'</tr>'
				);
				jQuery('#ocellaris-msi-select-all-months').hide();
			}
		});
	}
	</script>
	<?php
}
