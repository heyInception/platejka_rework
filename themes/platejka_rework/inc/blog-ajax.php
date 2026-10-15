<?php
/** Secure blog search and pagination endpoint. */

defined( 'ABSPATH' ) || exit;

function platejka_blog_ajax_posts(): void {
	if ( ! check_ajax_referer( 'platejka_blog_posts', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Сессия истекла. Обновите страницу.', 'platejka_rework' ) ), 403 );
	}
	$request = array(
		'surface'  => isset( $_POST['surface'] ) ? sanitize_key( $_POST['surface'] ) : 'archive',
		'page'     => isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1,
		'viewport' => isset( $_POST['viewport'] ) ? absint( $_POST['viewport'] ) : 1440,
		'taxonomy' => isset( $_POST['taxonomy'] ) ? sanitize_key( $_POST['taxonomy'] ) : '',
		'term'     => isset( $_POST['term'] ) ? sanitize_title( wp_unslash( $_POST['term'] ) ) : '',
		'search'   => isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '',
	);
	$query = new WP_Query( platejka_blog_query_args( $request ) );
	ob_start();
	while ( $query->have_posts() ) {
		$query->the_post();
		get_template_part( 'template-parts/content-single', 'card' );
	}
	$html = (string) ob_get_clean();
	wp_reset_postdata();
	wp_send_json_success( array( 'html' => $html, 'has_more' => $request['page'] < (int) $query->max_num_pages, 'page' => $request['page'], 'found_posts' => (int) $query->found_posts ) );
}
add_action( 'wp_ajax_platejka_blog_posts', 'platejka_blog_ajax_posts' );
add_action( 'wp_ajax_nopriv_platejka_blog_posts', 'platejka_blog_ajax_posts' );

