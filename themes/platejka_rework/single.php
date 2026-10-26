<?php
/** Single blog post. */
get_header();
while ( have_posts() ) :
	the_post();
	$post_id  = get_the_ID();
	$category = platejka_blog_primary_category( $post_id );
	$content  = apply_filters( 'the_content', get_the_content() );
	$toc      = platejka_blog_table_of_contents( $content );
	$content  = platejka_blog_add_heading_ids( $content );
	$related  = platejka_blog_related_query( $post_id );
	$url      = get_permalink();
	$title    = get_the_title();
?>
<main id="primary" class="site-main blog-single" itemprop="mainEntity" itemscope itemtype="https://schema.org/Article" itemid="<?php echo esc_url( $url . '#article' ); ?>">
	<meta itemprop="mainEntityOfPage" content="<?php echo esc_url( $url ); ?>">
	<span itemprop="publisher" itemscope itemtype="https://schema.org/FinancialService" itemid="<?php echo esc_url( home_url( '/#organization' ) ); ?>"><meta itemprop="name" content="<?php echo esc_attr( platejka_schema_value( 'name' ) ); ?>"><meta itemprop="logo" content="<?php echo esc_url( platejka_schema_value( 'logo' ) ); ?>"></span>
	<div class="container">
		<?php do_action( 'pretty_breadcrumb' ); ?>
		<header class="blog-single__header<?php echo has_post_thumbnail() ? '' : ' blog-single__header--no-image'; ?>">
			<div class="blog-single__intro">
				<div class="blog-single__meta">
					<span>Дата создания <time itemprop="datePublished" datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date( 'd.m.Y' ) ); ?></time></span>
					<?php if ( get_the_modified_time( 'U' ) > get_the_time( 'U' ) ) : ?><span>Дата обновления <time itemprop="dateModified" datetime="<?php echo esc_attr( get_the_modified_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_modified_date( 'd.m.Y' ) ); ?></time></span><?php else : ?><meta itemprop="dateModified" content="<?php echo esc_attr( get_the_modified_date( DATE_W3C ) ); ?>"><?php endif; ?>
					<span><?php echo esc_html( sprintf( '%d мин на чтение', platejka_blog_reading_time( $post_id ) ) ); ?></span>
				</div>
				<?php if ( $category ) : ?><a class="blog-single__category" href="<?php echo esc_url( get_category_link( $category ) ); ?>"><?php echo esc_html( $category->name ); ?></a><?php endif; ?>
				<h1 itemprop="headline"><?php echo esc_html( $title ); ?></h1>
			</div>
			<?php if ( has_post_thumbnail() ) : ?><figure class="blog-single__hero"><?php the_post_thumbnail( 'full', array( 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async', 'itemprop' => 'image' ) ); ?></figure><?php endif; ?>
		</header>
		<div class="blog-single__layout">
			<aside class="blog-single__aside">
				<?php if ( $toc ) : ?><nav class="blog-toc" data-blog-toc aria-label="Оглавление"><button class="blog-toc__toggle" type="button" aria-expanded="false">Оглавление</button><ol><?php foreach ( $toc as $item ) : ?><li class="blog-toc__level-<?php echo esc_attr( (string) $item['level'] ); ?>"><a href="#<?php echo esc_attr( $item['id'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a></li><?php endforeach; ?></ol></nav><?php endif; ?>
				<div class="blog-share" aria-label="Поделиться статьёй">
					<span>Поделиться</span>
					<a data-blog-share="telegram" href="<?php echo esc_url( 'https://max.ru/:share?text=' . rawurlencode( $url ) ); ?>" target="_blank" rel="noopener"><img src="<?php echo esc_url( get_theme_file_uri( 'sections/call/img/call-about/whatsapp.svg' ) ); ?>" alt="" width="44" height="44"><span class="screen-reader-text">MAX</span></a>
					<a data-blog-share="telegram" href="<?php echo esc_url( 'https://t.me/share/url?url=' . rawurlencode( $url ) . '&text=' . rawurlencode( $title ) ); ?>" target="_blank" rel="noopener"><img src="<?php echo esc_url( get_theme_file_uri( 'sections/call/img/call-about/telegram.svg' ) ); ?>" alt="" width="44" height="44"><span class="screen-reader-text">Telegram</span></a>
					<a data-blog-share="vk" href="<?php echo esc_url( 'https://vk.com/share.php?url=' . rawurlencode( $url ) . '&title=' . rawurlencode( $title ) ); ?>" target="_blank" rel="noopener"><img src="<?php echo esc_url( get_theme_file_uri( 'sections/call/img/call-about/vk.svg' ) ); ?>" alt="" width="44" height="44"><span class="screen-reader-text">ВКонтакте</span></a>
					<p data-blog-share-status role="status" aria-live="polite"></p>
				</div>
			</aside>
			<article class="blog-content article-blog" itemprop="articleBody"><?php echo platejka_blog_rendered_content( $content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content is filtered by the_content for trusted editors. ?></article>
		</div>
		<section class="blog-cta"><div><p class="blog-cta__eyebrow">Есть задача?</p><h2>Обсудим решение для вашего бизнеса</h2><p>Оставьте заявку — команда «Платёжки» свяжется с вами и ответит на вопросы.</p></div><div class="blog-cta__actions"><button class="blog-button" type="button" data-graph-path="call" data-contact-modal-open data-modal-context="blog" data-modal-source="blog-article-cta">Обсудить задачу</button><a href="https://t.me/platejka_com" target="_blank" rel="noopener">Написать в Telegram</a></div></section>
		<?php if ( $related->have_posts() ) : ?><section class="blog-related" data-blog-related><div class="blog-section__heading"><h2>Читайте также</h2></div><div class="blog-grid"><?php while ( $related->have_posts() ) : $related->the_post(); get_template_part( 'template-parts/content-single', 'card' ); endwhile; ?></div></section><?php endif; wp_reset_postdata(); ?>
	</div>
</main>
<?php endwhile; get_footer(); ?>
