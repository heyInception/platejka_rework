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
 * Template Name: О Компании
 * @package platejka_rework
 */

$fallback_sections = array(
		'about-hero',
		'location',
		'review-main',
		'infrastructure',
		'financial',
		'employees',
		'exhibitions',
		'developing',
		'call-about',
	);
$post_id  = get_queried_object_id();
$sections = platejka_get_about_page_sections( $post_id, $fallback_sections );
$sections = platejka_compose_cross_blocks( $sections, platejka_get_cross_block_rows( $post_id ), $post_id );
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
