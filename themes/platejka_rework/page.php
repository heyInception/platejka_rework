<?php
/**
 * The template for displaying all pages
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site may use a
 * different template.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package platejka_rework
 */

$post_id = get_queried_object_id();

if ( platejka_rework_is_legal_page( $post_id ) ) {
	platejka_use_sections( array( 'legal-page' ) );
	get_header();
	?>
	<main id="primary" class="site-main">
		<?php
		while ( have_posts() ) :
			the_post();
			get_template_part( 'template-parts/content', 'legal-page' );
		endwhile;
		?>
	</main><!-- #main -->
	<?php
	get_footer();
	return;
}

$fallback_sections = array(
		'hero',
		'about',
		'shipments',
		'guarantees',
		'documents',
		'protection',
		'review',
		'work',
		'problems',
		'calculator',
		'seo',
		'faq',
		'call',
	);
$sections = platejka_get_page_sections( $post_id, $fallback_sections );
$sections = platejka_use_sections( $sections );
get_header();
?>

	<main id="primary" class="site-main">

		<?php
		while ( have_posts() ) :
			the_post();

			platejka_render_sections( $sections );

		endwhile; // End of the loop.
		?>

	</main><!-- #main -->

<?php
get_footer();
