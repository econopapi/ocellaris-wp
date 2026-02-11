<?php
/**
 * The template for displaying search results pages
 *
 * @package Ocellaris Custom Astra
 */

get_header(); ?>

<div id="primary" class="content-area">
	<main id="main" class="site-main">

		<?php
		// Get search query early so we can use it for all searches
		$search_query = get_search_query();
		?>

		<header class="page-header">
			<h1 class="page-title">
				<?php
				/* translators: %s: search query. */
				printf( esc_html__( 'Resultados de búsqueda para: %s', 'ocellaris-custom-astra' ), '<span>' . $search_query . '</span>' );
				?>
			</h1>
		</header><!-- .page-header -->

		<?php

			// --- Search products by TEXT (title/content) ---
			$text_search_args = array(
				'post_type'      => 'product',
				's'              => $search_query,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			);
			$text_query = new WP_Query( $text_search_args );
			$text_product_ids = $text_query->posts;
			wp_reset_postdata();

			// --- Search products by SKU (meta field) ---
			$sku_search_args = array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'     => '_sku',
						'value'   => $search_query,
						'compare' => 'LIKE',
					),
				),
			);
			$sku_query = new WP_Query( $sku_search_args );
			$sku_product_ids = $sku_query->posts;
			wp_reset_postdata();

			// --- Combine and deduplicate: SKU matches first, then text matches ---
			$all_product_ids = array_unique( array_merge( $sku_product_ids, $text_product_ids ) );
			$total_count = count( $all_product_ids );

			// Get the first 12 products for display
			$display_ids = array_slice( $all_product_ids, 0, 12 );

			if ( ! empty( $display_ids ) ) {
				$product_args = array(
					'post_type'      => 'product',
					'post__in'       => $display_ids,
					'post_status'    => 'publish',
					'posts_per_page' => 12,
					'orderby'        => 'post__in',
				);
				$product_query = new WP_Query( $product_args );
			} else {
				$product_query = new WP_Query( array( 'post__in' => array( 0 ) ) ); // Empty result set
			}

			if ( ! empty( $display_ids ) && $product_query->have_posts() ) : ?>
				<section class="search-products-section">
					<div class="search-section-header">
						<h2 class="search-section-title">Productos</h2>
						<?php if ( $total_count > 0 ) : ?>
							<span class="search-results-count">
								<?php 
								$showing = min( $product_query->post_count, 12 );
								printf( 'Mostrando %d de %d productos', $showing, $total_count );
								?>
							</span>
						<?php endif; ?>
					</div>
					
					<div class="woocommerce">
						<ul class="products columns-4 ocellaris-products-catalog featured-products-grid products-count-4">
							<?php 
							while ( $product_query->have_posts() ) : 
								$product_query->the_post();
								
								// Set up global product object
								global $woocommerce_loop, $product;
								$product = wc_get_product( get_the_ID() );
								
								if ( $product && $product->is_visible() ) {
									// Include our custom content-product template
									include( get_stylesheet_directory() . '/woocommerce/content-product.php' );
								}
							endwhile; 
							wp_reset_postdata();
							?>
						</ul>
					</div>
					
					<?php if ( $total_count > 12 ) : ?>
						<div class="search-view-all-products">
							<a href="<?php echo esc_url( home_url( '/shop/?s=' . urlencode( $search_query ) ) ); ?>" class="view-all-products-btn">
								Ver todos los productos (<?php echo $total_count; ?>)
							</a>
						</div>
					<?php endif; ?>
				</section>
			<?php endif;

			// Then, search for blog posts (excluding products)
			$post_args = array(
				'post_type'      => 'post',
				's'              => $search_query,
				'post_status'    => 'publish',
				'posts_per_page' => 10,
			);

			$post_query = new WP_Query( $post_args );

			if ( $post_query->have_posts() ) : ?>
				<section class="search-posts-section">
					<h2 class="search-section-title">Artículos del Blog</h2>
					
					<div class="search-posts-grid">
						<?php 
						while ( $post_query->have_posts() ) : 
							$post_query->the_post();
							?>
							<article id="post-<?php the_ID(); ?>" <?php post_class( 'search-post-item' ); ?>>
								<?php if ( has_post_thumbnail() ) : ?>
									<div class="search-post-thumbnail">
										<a href="<?php the_permalink(); ?>">
											<?php the_post_thumbnail( 'medium' ); ?>
										</a>
									</div>
								<?php endif; ?>

								<div class="search-post-content">
									<div class="search-post-meta">
										<?php
										$categories = get_the_category();
										if ( ! empty( $categories ) ) {
											echo '<span class="search-post-category">' . esc_html( $categories[0]->name ) . '</span>';
										}
										?>
									</div>

									<h3 class="search-post-title">
										<a href="<?php the_permalink(); ?>">
											<?php the_title(); ?>
										</a>
									</h3>

									<div class="search-post-meta-info">
										<span class="search-post-author">
											<?php echo get_the_author(); ?>
										</span>
										<span class="search-post-date">
											<?php echo get_the_date(); ?>
										</span>
									</div>

									<div class="search-post-excerpt">
										<?php echo wp_trim_words( get_the_excerpt(), 20, '...' ); ?>
									</div>
								</div>
							</article>
						<?php 
						endwhile; 
						wp_reset_postdata();
						?>
					</div>
				</section>
			<?php endif;

			// If no results found
			if ( empty( $all_product_ids ) && ! $post_query->have_posts() ) : ?>
				<section class="no-results not-found">
					<header class="page-header">
						<h1 class="page-title"><?php esc_html_e( 'Nada por aquí', 'ocellaris-custom-astra' ); ?></h1>
					</header><!-- .page-header -->

					<div class="page-content">
						<p><?php esc_html_e( 'Lo siento, pero nada coincide con tus términos de búsqueda. Por favor, intenta de nuevo con algunas palabras clave diferentes.', 'ocellaris-custom-astra' ); ?></p>
						
					</div><!-- .page-content -->
				</section><!-- .no-results -->
			<?php endif; ?>

	</main><!-- #main -->
</div><!-- #primary -->

<?php get_footer(); ?>