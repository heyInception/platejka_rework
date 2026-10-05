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
 * Template Name: Главная
 *
 * @package platejka_rework
 */

$sections = platejka_use_sections(
	array(
		'hero-main',
		'about',
		array( 'slug' => 'shipments', 'mode' => 'main' ),
		array( 'slug' => 'guarantees', 'mode' => 'main' ),
		array( 'slug' => 'documents', 'mode' => 'main' ),
		'compliance',
		'review-main',
		'work',
		'calculator',
		'with-us',
		'destinations',
		'seo',
		'problems',
		'serves',
		'cases',
		'table',
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
