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

$sections = platejka_use_sections(
	array(
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
	)
);
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
