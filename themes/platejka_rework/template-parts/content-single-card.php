<?php
/** Shared blog card. */
$post_id  = get_the_ID();
$category = platejka_blog_primary_category( $post_id );
$views    = platejka_blog_view_count( $post_id );
?>
<article id="post-<?php echo esc_attr( (string) $post_id ); ?>" <?php post_class( 'blog-card' ); ?>>
	<a class="blog-card__link" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr( get_the_title() ); ?>">
		<div class="blog-card__media">
			<?php if ( has_post_thumbnail() ) : ?>
				<?php the_post_thumbnail( 'large', array( 'class' => 'blog-card__image', 'loading' => 'lazy', 'decoding' => 'async' ) ); ?>
			<?php else : ?>
				<span class="blog-card__placeholder" aria-hidden="true"><span>платёжка</span></span>
			<?php endif; ?>
		</div>
		<div class="blog-card__body">
			<?php if ( $category ) : ?><span class="blog-card__category"><?php echo esc_html( $category->name ); ?></span><?php endif; ?>
			<h3 class="blog-card__title"><?php the_title(); ?></h3>
			<p class="blog-card__excerpt"><?php echo esc_html( platejka_blog_excerpt( $post_id ) ); ?></p>
			<div class="blog-card__meta">
				<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date( 'j F Y' ) ); ?></time>
				<span class="blog-card__reading-time"><?php echo esc_html( sprintf( '%d мин', platejka_blog_reading_time( $post_id ) ) ); ?></span>
				<?php if ( '' !== $views ) : ?><span class="blog-card__views"><?php echo esc_html( $views ); ?></span><?php endif; ?>
			</div>
		</div>
	</a>
</article>
