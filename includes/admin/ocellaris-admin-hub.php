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
					<td>Guia operativa paso a paso para administrar tema, bloques y configuraciones sin soporte tecnico.</td>
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

	$links = array(
		'dashboard' => admin_url( 'admin.php?page=ocellaris' ),
		'msi'       => admin_url( 'admin.php?page=ocellaris-msi-mercadopago' ),
		'text_bar'  => admin_url( 'admin.php?page=ocellaris-text-bar' ),
		'health'    => admin_url( 'admin.php?page=ocellaris-health-check' ),
		'new_post'  => admin_url( 'post-new.php' ),
		'posts'     => admin_url( 'edit.php' ),
		'pages'     => admin_url( 'edit.php?post_type=page' ),
	);

	$quick_start = array(
		'1) Abre Ocellaris > Health Check y confirma que todo este en OK.',
		'2) Si quieres un mensaje superior en todo el sitio, configuralo en Ocellaris > Text Bar.',
		'3) Si usas MSI, marca productos y mensualidades en Ocellaris > MSI MercadoPago.',
		'4) Crea una entrada (post) o edita una pagina y agrega bloques Ocellaris.',
		'5) Publica y revisa el resultado en celular y computadora.',
	);

	$common_tasks = array(
		array(
			'title' => 'Publicar una entrada con productos destacados',
			'steps' => array(
				'Ve a Entradas > Anadir nueva.',
				'Agrega el bloque Ocellaris Featured Products.',
				'En el panel derecho define titulo, tipo de filtro y cantidad visible.',
				'Si usas seleccion manual, marca productos y guarda.',
				'Publica la entrada y revisa que se vea bien en frontend.',
			),
		),
		array(
			'title' => 'Activar o cambiar la barra superior (Text Bar)',
			'steps' => array(
				'Entra a Ocellaris > Text Bar.',
				'Activa la barra, escribe el texto y define color.',
				'Guarda cambios.',
				'Abre el frontend y confirma que el mensaje aparece.',
			),
		),
		array(
			'title' => 'Definir productos con MSI',
			'steps' => array(
				'Entra a Ocellaris > MSI MercadoPago.',
				'Agrega productos elegibles y selecciona mensualidades.',
				'Guarda configuracion.',
				'Revisa un producto configurado en frontend para validar el badge MSI.',
			),
		),
	);

	$blocks_guide = array(
		array(
			'name' => 'Ocellaris Featured Products',
			'use'  => 'Mostrar productos por seleccion manual o filtros automaticos.',
			'how'  => array(
				'Seleccion manual: eliges productos uno por uno.',
				'Por etiquetas: selecciona tags y el bloque toma productos por esas etiquetas.',
				'En oferta: muestra productos con precio rebajado.',
				'Destacados: muestra productos marcados como featured.',
			),
			'tips' => array(
				'El selector del editor ya excluye productos sin stock para evitar inconsistencias.',
				'Si hay mas productos que tarjetas visibles, el frontend activa carrusel automaticamente.',
			),
		),
		array(
			'name' => 'Ocellaris Product Categories',
			'use'  => 'Mostrar categorias principales de tienda en formato visual.',
			'how'  => array(
				'Define titulo y subtitulo.',
				'Selecciona categorias que quieres destacar.',
				'Guarda y revisa el orden/resultado en frontend.',
			),
			'tips' => array(
				'Usa pocas categorias para mantener una portada limpia y facil de navegar.',
			),
		),
		array(
			'name' => 'Ocellaris Featured Brands / All Brands',
			'use'  => 'Destacar marcas en formato de logos.',
			'how'  => array(
				'Selecciona las marcas en el panel del bloque.',
				'Ajusta autoplay cuando aplique.',
				'Guarda y valida que los logos se lean bien en mobile.',
			),
			'tips' => array(
				'Si no aparece una marca, revisa que la taxonomia de marca exista y tenga terminos.',
			),
		),
		array(
			'name' => 'Ocellaris Filter Categories / Filter Brand',
			'use'  => 'Permitir al usuario filtrar catalogo por categoria y marca.',
			'how'  => array(
				'Agrega los bloques en pagina de tienda o landing de productos.',
				'Publica y prueba desde navegador como usuario final.',
			),
			'tips' => array(
				'Si no ves resultados, confirma que tus productos tengan categoria/marca asignada.',
			),
		),
	);

	$pre_publish_checklist = array(
		'La entrada o pagina esta publicada (no solo guardada como borrador).',
		'Los bloques muestran contenido real en frontend.',
		'No hay estados Revisar en Ocellaris > Health Check.',
		'Se reviso vista desktop y mobile.',
		'Productos MSI y bloques de productos no muestran items sin stock cuando no corresponde.',
	);

	$faq = array(
		array(
			'q' => 'No me aparecen productos en Featured Products. Que hago?',
			'a' => 'Revisa el tipo de filtro. Si es manual, selecciona productos uno por uno. Si es por etiquetas, confirma que los productos tengan esas etiquetas. Tambien valida que el producto este publicado y con stock.',
		),
		array(
			'q' => 'No veo marcas en los bloques de marcas. Que reviso?',
			'a' => 'Entra a Ocellaris > Health Check y valida taxonomia de marca. Luego confirma que existan terminos de marca y productos asociados.',
		),
		array(
			'q' => 'Active Text Bar pero no se ve.',
			'a' => 'Confirma que este activa, con texto guardado, y limpia cache del sitio/navegador si aplica.',
		),
		array(
			'q' => 'Como se cuando debo pedir ayuda tecnica?',
			'a' => 'Primero revisa Health Check y sigue esta guia. Si aun asi ves errores visibles o algo deja de funcionar por completo, entonces si conviene pedir ayuda tecnica.',
		),
	);
	?>
	<div class="wrap">
		<h1>Documentacion Ocellaris</h1>
		<p>Guia paso a paso para usuarios no tecnicos. Esta pensada para que puedas operar el tema sin depender del desarrollador.</p>

		<div style="max-width:1100px;background:#fff;border:1px solid #dcdcde;border-left:4px solid #2271b1;padding:12px 16px;margin:16px 0;">
			<strong>Accesos rapidos:</strong>
			<a class="button button-secondary" style="margin-left:8px;" href="<?php echo esc_url( $links['dashboard'] ); ?>">Dashboard</a>
			<a class="button button-secondary" href="<?php echo esc_url( $links['text_bar'] ); ?>">Text Bar</a>
			<a class="button button-secondary" href="<?php echo esc_url( $links['msi'] ); ?>">MSI MercadoPago</a>
			<a class="button button-secondary" href="<?php echo esc_url( $links['health'] ); ?>">Health Check</a>
			<a class="button button-primary" href="<?php echo esc_url( $links['new_post'] ); ?>">Crear entrada con bloques</a>
		</div>

		<h2>Inicio Rapido (5 minutos)</h2>
		<ol style="max-width:1000px;">
			<?php foreach ( $quick_start as $step ) : ?>
				<li style="margin-bottom:8px;"><?php echo esc_html( $step ); ?></li>
			<?php endforeach; ?>
		</ol>

		<h2>Tareas Mas Frecuentes</h2>
		<?php foreach ( $common_tasks as $task ) : ?>
			<div style="max-width:1100px;background:#fff;border:1px solid #dcdcde;padding:12px 16px;margin:12px 0;">
				<h3 style="margin-top:0;"><?php echo esc_html( $task['title'] ); ?></h3>
				<ol>
					<?php foreach ( $task['steps'] as $task_step ) : ?>
						<li style="margin-bottom:6px;"><?php echo esc_html( $task_step ); ?></li>
					<?php endforeach; ?>
				</ol>
			</div>
		<?php endforeach; ?>

		<h2>Guia de Bloques Ocellaris</h2>
		<?php foreach ( $blocks_guide as $block ) : ?>
			<div style="max-width:1100px;background:#fff;border:1px solid #dcdcde;padding:12px 16px;margin:12px 0;">
				<h3 style="margin-top:0;"><?php echo esc_html( $block['name'] ); ?></h3>
				<p><strong>Para que sirve:</strong> <?php echo esc_html( $block['use'] ); ?></p>
				<p><strong>Como usarlo:</strong></p>
				<ul>
					<?php foreach ( $block['how'] as $how_item ) : ?>
						<li><?php echo esc_html( $how_item ); ?></li>
					<?php endforeach; ?>
				</ul>
				<p><strong>Tips para evitar errores:</strong></p>
				<ul>
					<?php foreach ( $block['tips'] as $tip_item ) : ?>
						<li><?php echo esc_html( $tip_item ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endforeach; ?>

		<h2>Checklist Antes de Publicar</h2>
		<ul style="max-width:1000px;">
			<?php foreach ( $pre_publish_checklist as $check_item ) : ?>
				<li style="margin-bottom:6px;"><?php echo esc_html( $check_item ); ?></li>
			<?php endforeach; ?>
		</ul>

		<p>
			<a class="button button-secondary" href="<?php echo esc_url( $links['posts'] ); ?>">Ir a Todas las Entradas</a>
			<a class="button button-secondary" href="<?php echo esc_url( $links['pages'] ); ?>">Ir a Todas las Paginas</a>
			<a class="button button-secondary" href="<?php echo esc_url( $links['health'] ); ?>">Abrir Health Check</a>
		</p>

		<h2>Preguntas Frecuentes (FAQ)</h2>
		<?php foreach ( $faq as $faq_item ) : ?>
			<div style="max-width:1100px;background:#fff;border:1px solid #dcdcde;padding:10px 14px;margin:10px 0;">
				<p style="margin:0 0 6px;"><strong><?php echo esc_html( $faq_item['q'] ); ?></strong></p>
				<p style="margin:0;"><?php echo esc_html( $faq_item['a'] ); ?></p>
			</div>
		<?php endforeach; ?>

		<div style="max-width:1100px;background:#f0f6fc;border:1px solid #c5d9ed;padding:12px 16px;margin:16px 0;">
			<strong>Regla simple:</strong> si una configuracion no da el resultado esperado,
			primero revisa Health Check, luego esta guia, y por ultimo vuelve a probar en frontend.
			Esto resuelve la mayoria de casos sin soporte tecnico.
		</div>
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
