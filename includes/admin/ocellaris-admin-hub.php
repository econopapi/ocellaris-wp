<?php
/**
 * Ocellaris Admin Hub
 *
 * Consolidates custom theme admin tools under a single top-level menu.
 *
 * @package Ocellaris Custom Astra
 * @since 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register top-level Ocellaris menu and submenus.
 */
function ocellaris_register_admin_hub_menu() {
	add_menu_page(
		'Ocellaris',
		'Ocellaris',
		'manage_options',
		'ocellaris',
		'ocellaris_render_admin_dashboard_page',
		'dashicons-admin-site-alt3',
		3
	);

	add_submenu_page(
		'ocellaris',
		'Ocellaris Dashboard',
		'Dashboard',
		'manage_options',
		'ocellaris',
		'ocellaris_render_admin_dashboard_page'
	);

	add_submenu_page(
		'ocellaris',
		'MSI MercadoPago',
		'MSI MercadoPago',
		'manage_woocommerce',
		'ocellaris-msi-mercadopago',
		'ocellaris_msi_render_mercadopago_page'
	);

	add_submenu_page(
		'ocellaris',
		'Ocellaris Text Bar',
		'Text Bar',
		'manage_options',
		'ocellaris-text-bar',
		'ocellaris_render_text_bar'
	);

	add_submenu_page(
		'ocellaris',
		'Documentacion Ocellaris',
		'Documentacion',
		'manage_options',
		'ocellaris-documentation',
		'ocellaris_render_admin_documentation_page'
	);

	add_submenu_page(
		'ocellaris',
		'Health Check Ocellaris',
		'Health Check',
		'manage_options',
		'ocellaris-health-check',
		'ocellaris_render_admin_health_check_page'
	);
}
add_action( 'admin_menu', 'ocellaris_register_admin_hub_menu', 5 );

/**
 * Render Ocellaris dashboard page.
 */
function ocellaris_render_admin_dashboard_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1>Ocellaris</h1>
		<p>Panel central para administrar funcionalidades personalizadas del tema.</p>

		<table class="widefat striped" style="max-width: 960px; margin-top: 16px;">
			<thead>
				<tr>
					<th>Modulo</th>
					<th>Descripcion</th>
					<th>Accion</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><strong>MSI MercadoPago</strong></td>
					<td>Configuracion de productos elegibles para meses sin intereses.</td>
					<td><a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=ocellaris-msi-mercadopago' ) ); ?>">Abrir</a></td>
				</tr>
				<tr>
					<td><strong>Text Bar</strong></td>
					<td>Configuracion del banner superior global.</td>
					<td><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=ocellaris-text-bar' ) ); ?>">Abrir</a></td>
				</tr>
				<tr>
					<td><strong>Documentacion</strong></td>
					<td>Inventario tecnico de bloques, hooks y personalizaciones.</td>
					<td><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=ocellaris-documentation' ) ); ?>">Ver</a></td>
				</tr>
				<tr>
					<td><strong>Health Check</strong></td>
					<td>Revision rapida de dependencias y estado operativo.</td>
					<td><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=ocellaris-health-check' ) ); ?>">Ejecutar</a></td>
				</tr>
			</tbody>
		</table>
	</div>
	<?php
}

/**
 * Render technical documentation page.
 */
function ocellaris_render_admin_documentation_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$blocks = array(
		'ocellaris/product-categories',
		'ocellaris/featured-brands',
		'ocellaris/all-brands',
		'ocellaris/filter-categories',
		'ocellaris/filter-brand',
		'ocellaris/featured-products',
	);

	$features = array(
		'Header personalizado + subcategorias via AJAX',
		'Footer personalizado con menus y metodos de pago',
		'Text Bar global configurable',
		'Sistema MSI MercadoPago (admin + checkout)',
		'Filtros de catalogo por categoria y marca',
		'Overrides de templates WooCommerce',
	);
	?>
	<div class="wrap">
		<h1>Documentacion Ocellaris</h1>
		<p>Resumen tecnico de personalizaciones activas en el tema.</p>

		<h2>Bloques Gutenberg</h2>
		<ul>
			<?php foreach ( $blocks as $block_name ) : ?>
				<li><code><?php echo esc_html( $block_name ); ?></code></li>
			<?php endforeach; ?>
		</ul>

		<h2>Caracteristicas Custom</h2>
		<ul>
			<?php foreach ( $features as $feature ) : ?>
				<li><?php echo esc_html( $feature ); ?></li>
			<?php endforeach; ?>
		</ul>

		<h2>Archivos Clave</h2>
		<p><code>functions.php</code>, <code>includes/msi-promotions/admin-page.php</code>, <code>template-parts/</code>, <code>woocommerce/</code>, <code>blocks/</code>.</p>
	</div>
	<?php
}

/**
 * Helper for health check badges.
 *
 * @param bool $is_ok Whether check passed.
 * @return string
 */
function ocellaris_admin_health_badge( $is_ok ) {
	if ( $is_ok ) {
		return '<span style="display:inline-block;padding:2px 8px;border-radius:999px;background:#e7f7ed;color:#146c2e;font-weight:600;">OK</span>';
	}

	return '<span style="display:inline-block;padding:2px 8px;border-radius:999px;background:#fdecec;color:#9f1c1c;font-weight:600;">Revisar</span>';
}

/**
 * Render health-check page.
 */
function ocellaris_render_admin_health_check_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$checks = array(
		array(
			'label'   => 'WooCommerce activo',
			'ok'      => class_exists( 'WooCommerce' ),
			'details' => class_exists( 'WooCommerce' ) ? 'Detectado.' : 'WooCommerce no esta activo.',
		),
		array(
			'label'   => 'Taxonomia product_cat disponible',
			'ok'      => taxonomy_exists( 'product_cat' ),
			'details' => taxonomy_exists( 'product_cat' ) ? 'Disponible.' : 'No disponible.',
		),
		array(
			'label'   => 'Taxonomia de marca disponible',
			'ok'      => taxonomy_exists( 'product_brand' ) || taxonomy_exists( 'pa_brand' ),
			'details' => ( taxonomy_exists( 'product_brand' ) || taxonomy_exists( 'pa_brand' ) ) ? 'Detectada.' : 'No detectada.',
		),
		array(
			'label'   => 'Template parts custom',
			'ok'      => file_exists( get_stylesheet_directory() . '/template-parts/header-custom.php' ) && file_exists( get_stylesheet_directory() . '/template-parts/footer-custom.php' ),
			'details' => 'Header/Footer custom.',
		),
		array(
			'label'   => 'Configuracion MSI guardada',
			'ok'      => is_array( get_option( 'ocellaris_msi_mp_products', array() ) ),
			'details' => 'Option: ocellaris_msi_mp_products',
		),
		array(
			'label'   => 'Configuracion Text Bar guardada',
			'ok'      => null !== get_option( 'ocellaris_text_bar_active', null ),
			'details' => 'Option: ocellaris_text_bar_active',
		),
	);
	?>
	<div class="wrap">
		<h1>Health Check Ocellaris</h1>
		<p>Validaciones rapidas no destructivas del entorno del tema.</p>

		<table class="widefat striped" style="max-width: 960px; margin-top: 16px;">
			<thead>
				<tr>
					<th>Check</th>
					<th>Estado</th>
					<th>Detalle</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $checks as $check ) : ?>
					<tr>
						<td><?php echo esc_html( $check['label'] ); ?></td>
						<td><?php echo wp_kses_post( ocellaris_admin_health_badge( (bool) $check['ok'] ) ); ?></td>
						<td><?php echo esc_html( $check['details'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}
