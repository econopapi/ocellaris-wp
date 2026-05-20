<?php
/**
 * Ocellaris Text Bar Module
 *
 * @package Ocellaris Custom Astra
 * @since 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register text bar settings.
 */
function ocellaris_register_text_bar_settings() {
	register_setting(
		'ocellaris_text_bar_settings',
		'ocellaris_text_bar_active',
		array( 'sanitize_callback' => 'ocellaris_sanitize_checkbox' )
	);

	register_setting(
		'ocellaris_text_bar_settings',
		'ocellaris_text_bar_content',
		array( 'sanitize_callback' => 'sanitize_text_field' )
	);

	register_setting(
		'ocellaris_text_bar_settings',
		'ocellaris_text_bar_link',
		array( 'sanitize_callback' => 'esc_url_raw' )
	);

	register_setting(
		'ocellaris_text_bar_settings',
		'ocellaris_text_bar_color',
		array(
			'sanitize_callback' => 'sanitize_hex_color',
			'default'           => '#003866;',
		)
	);
}
add_action( 'admin_init', 'ocellaris_register_text_bar_settings' );

/**
 * Sanitize checkbox values.
 *
 * @param mixed $value Checkbox value.
 * @return string
 */
function ocellaris_sanitize_checkbox( $value ) {
	return ( isset( $value ) && '1' === (string) $value ) ? '1' : '0';
}

/**
 * Render text bar settings page.
 */
function ocellaris_render_text_bar() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$active  = get_option( 'ocellaris_text_bar_active', '0' );
	$content = get_option( 'ocellaris_text_bar_content', '' );
	$color   = get_option( 'ocellaris_text_bar_color', '#003866;' );
	$link    = get_option( 'ocellaris_text_bar_link', '' );

	if ( isset( $_GET['settings-updated'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>Configuracion guardada correctamente.</p></div>';
	}
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<p class="description">
			Configuracion de barra de texto superior para el sitio Ocellaris.
			Este banner se muestra de forma global en todo el sitio web.
		</p>

		<div class="ocellaris-banner-preview" style="margin: 20px 0; padding: 20px; background: #f0f0f1; border-radius: 4px;">
			<h2 style="margin-top: 0;">Vista Previa</h2>
			<div id="ocellaris-text-bar-preview-container">
				<?php if ( '1' === $active && ! empty( $content ) ) : ?>
					<?php if ( ! empty( $link ) ) : ?>
						<a href="<?php echo esc_url( $link ); ?>" class="ocellaris-text-bar-preview" style="background-color: <?php echo esc_attr( $color ); ?>; display: block; padding: 12px 20px; text-align: center; text-decoration: none; color: white; font-weight: 600; transition: opacity 0.3s ease; border-radius: 4px;">
							<?php echo esc_html( $content ); ?>
						</a>
					<?php else : ?>
						<div class="ocellaris-text-bar-preview" id="ocellaris-text-bar-preview" style="background-color: <?php echo esc_attr( $color ); ?>; display: block; padding: 12px 20px; text-align: center; color: white; font-weight: 600; border-radius: 4px;">
							<?php echo esc_html( $content ); ?>
						</div>
					<?php endif; ?>
				<?php else : ?>
					<p style="color: #666; font-style: italic;">
						La barra no se mostrara porque <?php echo ( '1' !== $active ) ? 'esta desactivada' : 'no tiene contenido'; ?>.
					</p>
				<?php endif; ?>
			</div>
		</div>

		<form action="options.php" method="post" id="ocellaris-text-bar-form">
			<?php settings_fields( 'ocellaris_text_bar_settings' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="ocellaris_text_bar_active">Estado de la barra de texto</label></th>
					<td>
						<label>
							<input type="checkbox" id="ocellaris_text_bar_active" name="ocellaris_text_bar_active" value="1" <?php checked( $active, '1' ); ?> />
							<strong>Activar barra de texto</strong>
						</label>
						<p class="description">Marca esta opcion para mostrar la barra de texto en el sitio web.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ocellaris_text_bar_content">Contenido de la barra de texto</label></th>
					<td>
						<input type="text" id="ocellaris_text_bar_content" name="ocellaris_text_bar_content" value="<?php echo esc_attr( $content ); ?>" class="regular-text" />
						<p class="description">Mensaje que se mostrara en la barra.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ocellaris_text_bar_link">Enlace de la barra de texto</label></th>
					<td>
						<input type="url" id="ocellaris_text_bar_link" name="ocellaris_text_bar_link" value="<?php echo esc_url( $link ); ?>" class="regular-text" />
						<p class="description">Enlace al que se dirigira el usuario al hacer clic en la barra. Dejalo vacio si no deseas que sea enlace.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ocellaris_text_bar_color">Color de fondo de la barra de texto</label></th>
					<td>
						<input type="color" id="ocellaris_text_bar_color" name="ocellaris_text_bar_color" value="<?php echo esc_attr( $color ); ?>" class="regular-text ocellaris-color-field" />
						<p class="description">Selecciona el color de fondo de la barra de texto.</p>
					</td>
				</tr>
			</table>
			<?php submit_button( 'Guardar configuracion' ); ?>
		</form>

		<script>
			(function() {
				const activeCheckbox = document.getElementById('ocellaris_text_bar_active');
				const contentInput = document.getElementById('ocellaris_text_bar_content');
				const colorInput = document.getElementById('ocellaris_text_bar_color');
				const linkInput = document.getElementById('ocellaris_text_bar_link');
				const previewContainer = document.getElementById('ocellaris-text-bar-preview-container');

				function updatePreview() {
					const isActive = activeCheckbox.checked;
					const content = contentInput.value;
					const color = colorInput.value;
					const link = linkInput.value;

					if (!isActive || !content) {
						const message = !isActive ? 'esta desactivada' : 'no tiene contenido';
						previewContainer.innerHTML = `<p style="color: #666; font-style: italic;">La barra no se mostrara porque ${message}.</p>`;
						return;
					}

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

					if (link) {
						previewContainer.innerHTML = `
							<a href="${link}" class="ocellaris-text-bar-preview" style="${baseStyle} text-decoration: none;">
								${content}
							</a>
						`;
					} else {
						previewContainer.innerHTML = `
							<div class="ocellaris-text-bar-preview" style="${baseStyle}">
								${content}
							</div>
						`;
					}
				}

				[activeCheckbox, contentInput, colorInput, linkInput].forEach((field) => {
					field.addEventListener('input', updatePreview);
				});
			})();
		</script>
	</div>
	<?php
}

/**
 * Print text bar in frontend.
 */
function ocellaris_display_text_bar() {
	$active  = get_option( 'ocellaris_text_bar_active', '0' );
	$content = get_option( 'ocellaris_text_bar_content', '' );
	$color   = get_option( 'ocellaris_text_bar_color', '#003866;' );
	$link    = get_option( 'ocellaris_text_bar_link', '' );

	if ( '1' !== $active || empty( $content ) ) {
		return;
	}

	if ( ! empty( $link ) ) {
		?>
		<a href="<?php echo esc_url( $link ); ?>" class="ocellaris-text-bar" style="background-color: <?php echo esc_attr( $color ); ?>; display: block; padding: 12px 20px; text-align: center; text-decoration: none; color: white; font-weight: 600; transition: opacity 0.3s ease;">
			<?php echo esc_html( $content ); ?>
		</a>
		<?php
	} else {
		?>
		<div class="ocellaris-text-bar" style="background-color: <?php echo esc_attr( $color ); ?>; display: block; padding: 12px 20px; text-align: center; color: white; font-weight: 600;">
			<?php echo esc_html( $content ); ?>
		</div>
		<?php
	}
}
add_action( 'wp_body_open', 'ocellaris_display_text_bar' );

/**
 * Print frontend inline styles/scripts for text bar.
 */
function ocellaris_text_bar_frontend_styles() {
	?>
	<style>
		:root {
			--ocellaris-topbar-offset: 0px;
		}

		.ocellaris-text-bar {
			position: fixed;
			top: 0;
			left: 0;
			right: 0;
			width: 100%;
			z-index: 1900;
			box-sizing: border-box;
		}

		.ocellaris-header {
			top: var(--ocellaris-topbar-offset) !important;
		}

		body.admin-bar .ocellaris-text-bar {
			top: 32px;
		}

		@media screen and (max-width: 782px) {
			body.admin-bar .ocellaris-text-bar {
				top: 46px;
			}
		}

		.ocellaris-text-bar:hover {
			opacity: 0.9;
			cursor: pointer;
		}

		@media (max-width: 768px) {
			.ocellaris-text-bar {
				font-size: 14px;
				padding: 10px 15px !important;
			}
		}

		@media (max-width: 480px) {
			.ocellaris-text-bar {
				font-size: 12px;
				padding: 8px 10px !important;
			}
		}
	</style>
	<script>
		(function() {
			function updateBodyOffset() {
				var bar = document.querySelector('.ocellaris-text-bar');
				if (!document.body || !document.documentElement) {
					return;
				}

				if (!bar) {
					document.body.style.paddingTop = '';
					document.documentElement.style.setProperty('--ocellaris-topbar-offset', '0px');
					return;
				}

				var adminOffset = 0;
				if (document.body.classList.contains('admin-bar')) {
					adminOffset = window.innerWidth <= 782 ? 46 : 32;
				}

				var totalOffset = bar.offsetHeight + adminOffset;
				document.body.style.paddingTop = totalOffset + 'px';
				document.documentElement.style.setProperty('--ocellaris-topbar-offset', totalOffset + 'px');
			}

			window.addEventListener('load', updateBodyOffset);
			window.addEventListener('resize', updateBodyOffset);
			document.addEventListener('DOMContentLoaded', updateBodyOffset);
		})();
	</script>
	<?php
}
add_action( 'wp_head', 'ocellaris_text_bar_frontend_styles' );
