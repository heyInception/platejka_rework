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
 * Template name: Услуги
 * @package platejka
 */

$post_id          = get_queried_object_id();
$builder_sections = platejka_get_page_sections( $post_id, array() );
$sections         = platejka_compose_services_page_sections( $builder_sections );
$sections         = platejka_compose_cross_blocks( $sections, platejka_get_cross_block_rows( $post_id ), $post_id );
$sections         = platejka_use_sections( $sections );

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
