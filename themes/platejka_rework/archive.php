<?php
/** Category and tag archive. */
get_header();
$object   = get_queried_object();
$taxonomy = is_tag() ? 'post_tag' : 'category';
$term     = $object instanceof WP_Term ? $object->slug : '';
?>
<main id="primary" class="site-main blog-page blog-archive" data-blog-section data-surface="archive" data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>" data-term="<?php echo esc_attr( $term ); ?>">
	<div class="container blog-page__header">
		<?php do_action( 'pretty_breadcrumb' ); ?>
		<?php the_archive_title( '<h1>', '</h1>' ); ?>
		<?php the_archive_description( '<div class="blog-page__description">', '</div>' ); ?>
		<?php get_template_part( 'template-parts/blog-toolbar' ); ?>
	</div>
	<div class="container">
		<?php if ( have_posts() ) : ?><div class="blog-grid" data-blog-grid><?php while ( have_posts() ) : the_post(); get_template_part( 'template-parts/content-single', 'card' ); endwhile; ?></div><?php else : get_template_part( 'template-parts/blog-empty' ); endif; ?>
		<?php global $wp_query; if ( $wp_query->max_num_pages > 1 ) : ?><button class="blog-load-more" type="button" data-blog-load-more data-page="1"><?php esc_html_e( 'Показать ещё', 'platejka_rework' ); ?></button><?php endif; ?>
		<p class="blog-status" data-blog-status role="status" aria-live="polite"></p>
	</div>
</main>
<?php get_footer(); ?>
