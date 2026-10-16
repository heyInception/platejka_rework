<?php
/** Blog presentation, query, and metadata helpers. */

defined( 'ABSPATH' ) || exit;

function platejka_blog_is_surface(): bool {
	return is_home() || is_archive() || is_search() || is_singular( 'post' );
}

function platejka_blog_posts_per_page( string $surface, ?int $viewport_width = null ): int {
	$width = $viewport_width ?? ( wp_is_mobile() ? 375 : 1440 );
	if ( 'landing' === $surface ) {
		return $width <= 576 ? 2 : 3;
	}
	if ( $width <= 576 ) {
		return 4;
	}
	return $width <= 1024 ? 6 : 9;
}

function platejka_blog_reading_time_from_content( string $content ): int {
	$text  = trim( wp_strip_all_tags( strip_shortcodes( $content ) ) );
	$words = '' === $text ? 0 : count( preg_split( '/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY ) );
	return max( 1, (int) ceil( $words / 200 ) );
}

function platejka_blog_reading_time( int $post_id ): int {
	return platejka_blog_reading_time_from_content( (string) get_post_field( 'post_content', $post_id ) );
}

function platejka_blog_view_count( int $post_id ): string {
	if ( function_exists( 'pvc_get_post_views' ) ) {
		return number_format_i18n( (int) pvc_get_post_views( $post_id ) );
	}
	return '';
}

function platejka_blog_primary_category( int $post_id ): ?WP_Term {
	$categories = get_the_category( $post_id );
	return $categories ? $categories[0] : null;
}

function platejka_blog_excerpt( int $post_id, int $words = 24 ): string {
	$excerpt = get_the_excerpt( $post_id );
	if ( '' === trim( $excerpt ) ) {
		$excerpt = get_post_field( 'post_content', $post_id );
	}
	return wp_trim_words( wp_strip_all_tags( strip_shortcodes( (string) $excerpt ) ), $words );
}

function platejka_blog_query_args( array $request ): array {
	$surface  = isset( $request['surface'] ) && 'landing' === $request['surface'] ? 'landing' : 'archive';
	$viewport = isset( $request['viewport'] ) ? absint( $request['viewport'] ) : null;
	$page     = max( 1, isset( $request['page'] ) ? absint( $request['page'] ) : 1 );
	$args     = array( 'post_type' => 'post', 'post_status' => 'publish', 'ignore_sticky_posts' => true, 'orderby' => 'date', 'order' => 'DESC', 'posts_per_page' => platejka_blog_posts_per_page( $surface, $viewport ), 'paged' => $page );
	$search   = isset( $request['search'] ) ? sanitize_text_field( wp_unslash( $request['search'] ) ) : '';
	if ( '' !== $search ) { $args['s'] = $search; }
	$taxonomy = isset( $request['taxonomy'] ) ? sanitize_key( $request['taxonomy'] ) : '';
	$term     = isset( $request['term'] ) ? sanitize_title( wp_unslash( $request['term'] ) ) : '';
	if ( in_array( $taxonomy, array( 'category', 'post_tag' ), true ) && '' !== $term ) {
		$args['tax_query'] = array( array( 'taxonomy' => $taxonomy, 'field' => 'slug', 'terms' => $term ) );
	}
	return $args;
}

function platejka_blog_table_of_contents( string $content ): array {
	$items = array();
	if ( ! preg_match_all( '/<h([23])([^>]*)>(.*?)<\/h\1>/isu', $content, $matches, PREG_SET_ORDER ) ) { return $items; }
	$seen = array();
	$transliteration = array(
			'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e',
			'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm',
			'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
			'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch',
			'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
	);
	$uppercase = preg_split( '//u', 'АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯ', -1, PREG_SPLIT_NO_EMPTY );
	$lowercase = preg_split( '//u', 'абвгдеёжзийклмнопрстуфхцчшщъыьэюя', -1, PREG_SPLIT_NO_EMPTY );
	foreach ( $matches as $match ) {
		$label = trim( wp_strip_all_tags( $match[3] ) );
		$id = sanitize_title( strtolower( strtr( str_replace( $uppercase, $lowercase, $label ), $transliteration ) ) );
		$id = trim( (string) preg_replace( '/[^a-z0-9-]+/', '', $id ), '-' ) ?: 'section';
		$count = ( $seen[ $id ] ?? 0 ) + 1;
		$seen[ $id ] = $count;
		$items[] = array( 'level' => (int) $match[1], 'id' => 1 === $count ? $id : $id . '-' . $count, 'label' => $label );
	}
	return $items;
}

function platejka_blog_add_heading_ids( string $content ): string {
	$items = platejka_blog_table_of_contents( $content );
	$index = 0;
	return (string) preg_replace_callback( '/<h([23])([^>]*)>(.*?)<\/h\1>/isu', static function ( array $match ) use ( $items, &$index ): string {
		$item = $items[ $index++ ] ?? null;
		$attrs = preg_replace( '/\s+id=("|\').*?\1/isu', '', $match[2] );
		return null === $item ? $match[0] : '<h' . $match[1] . $attrs . ' id="' . esc_attr( $item['id'] ) . '">' . $match[3] . '</h' . $match[1] . '>';
	}, $content );
}

function platejka_blog_rendered_content( string $content ): string {
	return $content;
}

function platejka_blog_related_query( int $post_id ): WP_Query {
	$category = platejka_blog_primary_category( $post_id );
	$base = array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 3, 'post__not_in' => array( $post_id ), 'ignore_sticky_posts' => true, 'no_found_rows' => true, 'fields' => 'ids' );
	$ids  = $category ? get_posts( array_merge( $base, array( 'cat' => $category->term_id ) ) ) : array();
	if ( count( $ids ) < 3 ) {
		$ids = array_merge( $ids, get_posts( array_merge( $base, array( 'posts_per_page' => 3 - count( $ids ), 'post__not_in' => array_merge( array( $post_id ), $ids ) ) ) ) );
	}
	return new WP_Query( array( 'post_type' => 'post', 'post_status' => 'publish', 'post__in' => $ids ?: array( 0 ), 'orderby' => 'post__in', 'posts_per_page' => 3, 'ignore_sticky_posts' => true, 'no_found_rows' => true ) );
}

function platejka_blog_search_robots( array $robots ): array {
	if ( isset( $_GET['blog_search'] ) && '' !== sanitize_text_field( wp_unslash( $_GET['blog_search'] ) ) ) { $robots['noindex'] = true; $robots['follow'] = true; }
	return $robots;
}
add_filter( 'wp_robots', 'platejka_blog_search_robots' );

function platejka_blog_archive_page_size( WP_Query $query ): void {
	if ( ! is_admin() && $query->is_main_query() && ( $query->is_archive() || $query->is_search() ) ) {
		$query->set( 'posts_per_page', platejka_blog_posts_per_page( 'archive' ) );
	}
}
add_action( 'pre_get_posts', 'platejka_blog_archive_page_size' );

function platejka_blog_canonical_url(): string {
	if ( is_category() || is_tag() ) { $link = get_term_link( get_queried_object() ); return is_wp_error( $link ) ? home_url( '/blog/' ) : $link; }
	return get_post_type_archive_link( 'post' ) ?: home_url( '/blog/' );
}

function platejka_blog_search_canonical( string $canonical ): string {
	if ( isset( $_GET['blog_search'] ) && '' !== sanitize_text_field( wp_unslash( $_GET['blog_search'] ) ) ) { return platejka_blog_canonical_url(); }
	return $canonical;
}
add_filter( 'wpseo_canonical', 'platejka_blog_search_canonical' );

function platejka_blog_enqueue_assets(): void {
	if ( ! platejka_blog_is_surface() ) {
		return;
	}
	$css = get_theme_file_path( 'sections/blog/blog.css' );
	$js  = get_theme_file_path( 'sections/blog/blog.js' );
	if ( is_file( $css ) ) {
		wp_enqueue_style( 'platejka-rework-blog', get_theme_file_uri( 'sections/blog/blog.css' ), array( 'platejka_rework-style' ), (string) filemtime( $css ) );
	}
	if ( is_file( $js ) ) {
		wp_enqueue_script( 'platejka-rework-blog', get_theme_file_uri( 'sections/blog/blog.js' ), array(), (string) filemtime( $js ), true );
		wp_localize_script(
			'platejka-rework-blog',
			'PlatejkaBlog',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'action'  => 'platejka_blog_posts',
				'nonce'   => wp_create_nonce( 'platejka_blog_posts' ),
				'copy'    => esc_html__( 'Ссылка скопирована', 'platejka_rework' ),
				'error'   => esc_html__( 'Не удалось загрузить статьи. Попробуйте ещё раз.', 'platejka_rework' ),
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'platejka_blog_enqueue_assets', 30 );

