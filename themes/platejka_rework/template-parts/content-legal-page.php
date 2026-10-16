<?php
/**
 * Content layout for legal documents and the sitemap.
 *
 * @package platejka_rework
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'legal-page' ); ?>>
	<div class="container legal-page__container">
		<header class="legal-page__header">
			<?php the_title( '<h1 class="legal-page__title">', '</h1>' ); ?>
		</header>

		<div class="legal-page__content">
			<?php
			the_content();
			wp_link_pages(
				array(
					'before' => '<nav class="page-links" aria-label="' . esc_attr__( 'Страницы документа', 'platejka_rework' ) . '">',
					'after'  => '</nav>',
				)
			);
			?>
		</div>
	</div>
</article>
