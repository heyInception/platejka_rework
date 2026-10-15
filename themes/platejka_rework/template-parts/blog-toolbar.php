<?php $blog_search = isset( $_GET['blog_search'] ) ? sanitize_text_field( wp_unslash( $_GET['blog_search'] ) ) : ''; ?>
<div class="blog-toolbar">
	<nav class="blog-toolbar__categories" aria-label="<?php esc_attr_e( 'Категории блога', 'platejka_rework' ); ?>">
		<?php foreach ( array( 'internet-buhgalteriya', 'buhgalterskie-uslugi', 'news' ) as $slug ) : $term = get_category_by_slug( $slug ); if ( ! $term ) { continue; } ?>
			<a href="<?php echo esc_url( get_category_link( $term ) ); ?>"><?php echo esc_html( $term->name ); ?></a>
		<?php endforeach; ?>
	</nav>
	<form class="blog-search" role="search" data-blog-search>
		<label class="screen-reader-text" for="blog-search-field"><?php esc_html_e( 'Поиск по статьям', 'platejka_rework' ); ?></label>
		<input id="blog-search-field" type="search" name="blog_search" value="<?php echo esc_attr( $blog_search ); ?>" placeholder="<?php esc_attr_e( 'Поиск по статьям', 'platejka_rework' ); ?>" autocomplete="off">
		<button type="submit"><?php esc_html_e( 'Найти', 'platejka_rework' ); ?></button>
	</form>
</div>
