<?php
/** Blog landing page. */
get_header();
$sections = array( 'internet-buhgalteriya' => 'Интернет-бухгалтерия', 'buhgalterskie-uslugi' => 'Бухгалтерские услуги', 'news' => 'Новости' );
?>
<main id="primary" class="site-main blog-page">
	<div class="container blog-page__header">
		<?php do_action( 'pretty_breadcrumb' ); ?>
		<h1><?php single_post_title(); ?></h1>
		<?php get_template_part( 'template-parts/blog-toolbar' ); ?>
	</div>
	<?php foreach ( $sections as $slug => $label ) :
		$term  = get_category_by_slug( $slug );
		$query = new WP_Query( array( 'post_type' => 'post', 'post_status' => 'publish', 'category_name' => $slug, 'posts_per_page' => platejka_blog_posts_per_page( 'landing' ), 'ignore_sticky_posts' => true ) );
	?>
	<section class="blog-section" data-blog-section data-surface="landing" data-taxonomy="category" data-term="<?php echo esc_attr( $slug ); ?>">
		<div class="container">
			<div class="blog-section__heading"><h2><?php echo esc_html( $term ? $term->name : $label ); ?></h2><?php if ( $term ) : ?><a href="<?php echo esc_url( get_category_link( $term ) ); ?>"><?php esc_html_e( 'Все статьи', 'platejka_rework' ); ?></a><?php endif; ?></div>
			<div class="blog-grid" data-blog-grid><?php while ( $query->have_posts() ) : $query->the_post(); get_template_part( 'template-parts/content-single', 'card' ); endwhile; ?></div>
			<?php if ( $query->max_num_pages > 1 ) : ?><button class="blog-load-more" type="button" data-blog-load-more data-page="1"><?php esc_html_e( 'Показать ещё', 'platejka_rework' ); ?></button><?php endif; ?>
			<p class="blog-status" data-blog-status role="status" aria-live="polite"></p>
		</div>
	</section>
	<?php wp_reset_postdata(); endforeach; ?>
</main>
<?php get_footer(); ?>
