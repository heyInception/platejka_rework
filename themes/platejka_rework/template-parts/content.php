<?php

/**
 * Template part for displaying posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package platejka_rework
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class("article-blog"); ?>>
	<div class="article-blog__column">
		<?php
		the_content();
		?>
	</div><!-- .entry-content -->
</article><!-- #post-<?php the_ID(); ?> -->
