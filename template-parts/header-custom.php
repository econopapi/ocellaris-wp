<?php
/**
 * Custom Header Template
 *
 * @package Ocellaris Custom Astra
 */

?>
<header class="ocellaris-header">
	<div class="ocellaris-header-container">
        <!-- Logo -->
		<div class="ocellaris-logo">
			<?php
			if ( has_custom_logo() ) {
				the_custom_logo();
			} else {
				?>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
					<?php bloginfo( 'name' ); ?>
				</a>
				<?php
			}
			?>
		</div>

		<!-- Hamburger Menu Button -->
		<button class="ocellaris-menu-toggle" aria-label="Toggle Menu">
			<span class="menu-icon">
				<span></span>
				<span></span>
				<span></span>
			</span>
			<span class="menu-text">Menu</span>
		</button>

		<!-- Search Bar -->
		<div class="ocellaris-search">
			<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<input type="search" class="search-field" placeholder="Buscar en Ocellaris" name="s" />
				<button type="submit" class="search-submit">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none">
						<path d="M21 21L16.65 16.65M19 11C19 15.4183 15.4183 19 11 19C6.58172 19 3 15.4183 3 11C3 6.58172 6.58172 3 11 3C15.4183 3 19 6.58172 19 11Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
					</svg>
				</button>
			</form>
		</div>

		<!-- Right Side Actions -->
		<div class="ocellaris-header-actions">
			<a href="<?php echo esc_url( wp_login_url() ); ?>" class="ocellaris-signin">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none">
					<path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21M16 7C16 9.20914 14.2091 11 12 11C9.79086 11 8 9.20914 8 7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
				<span>Acceder</span>
			</a>
			
			 <a href="<?php echo esc_url( function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '#' ); ?>" class="ocellaris-cart">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                <path d="M7 18C5.9 18 5 18.9 5 20C5 21.1 5.9 22 7 22C8.1 22 9 21.1 9 20C9 18.9 8.1 18 7 18ZM1 2V4H3L6.6 11.59L5.25 14.04C5.09 14.32 5 14.65 5 15C5 16.1 5.9 17 7 17H19V15H7.42C7.28 15 7.17 14.89 7.17 14.75L7.2 14.65L8.1 13H15.55C16.3 13 16.96 12.59 17.3 11.97L20.88 5.48C20.95 5.34 21 5.17 21 5C21 4.45 20.55 4 20 4H5.21L4.27 2H1ZM17 18C15.9 18 15 18.9 15 20C15 21.1 15.9 22 17 22C18.1 22 19 21.1 19 20C19 18.9 18.1 18 17 18Z" fill="currentColor"/>
                </svg>
			</a>
		</div>
	</div>
</header>

<!-- Sidebar Menu Overlay -->
<div class="ocellaris-sidebar-overlay"></div>

<!-- Sidebar Menu -->
<div class="ocellaris-sidebar-menu">
	<div class="sidebar-header">
		<h3>Quick Links</h3>
		<button class="sidebar-close" aria-label="Close Menu">
			<svg width="24" height="24" viewBox="0 0 24 24" fill="none">
				<path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
			</svg>
		</button>
	</div>
	
	<nav class="sidebar-navigation">
		<?php
		wp_nav_menu( array(
			'theme_location' => 'sidebar-menu',
			'menu_class'     => 'sidebar-menu-list',
			'container'      => false,
			'fallback_cb'    => 'ocellaris_default_sidebar_menu',
		) );
		?>
	</nav>
</div>

<?php
/**
 * Fallback menu if no menu is assigned
 */
function ocellaris_default_sidebar_menu() {
	?>
	<ul class="sidebar-menu-list">
		<li class="menu-item"><a href="#">Aquariums & Stands</a></li>
		<li class="menu-item"><a href="#">Lighting</a></li>
		<li class="menu-item"><a href="#">Pumps & Powerheads</a></li>
		<li class="menu-item"><a href="#">Plumbing</a></li>
		<li class="menu-item"><a href="#">Controllers & Testing</a></li>
		<li class="menu-item"><a href="#">Additives</a></li>
		<li class="menu-item"><a href="#">Reverse Osmosis</a></li>
		<li class="menu-item"><a href="#">Salt & Maintenance</a></li>
	</ul>
	<?php
}
?>