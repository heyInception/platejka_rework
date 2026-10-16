<?php
/**
 * Frontend contract for the legal pages and sitemap.
 *
 * Run with Studio WP-CLI eval-file. This catches legal pages falling back to
 * the section builder, broken footer destinations, and an unexpanded sitemap
 * shortcode.
 */

defined( 'ABSPATH' ) || exit;

$checks   = 0;
$failures = array();
$assert   = static function ( bool $condition, string $message ) use ( &$checks, &$failures ): void {
	++$checks;
	if ( ! $condition ) {
		$failures[] = $message;
		WP_CLI::warning( $message );
	}
};

$pages = array(
	3    => 'Политика конфиденциальности',
	5170 => 'Политика использования cookie-файлов',
	5114 => 'Пользовательское соглашение',
	1469 => 'Карта сайта',
);

$rendered = array();
foreach ( $pages as $post_id => $label ) {
	$post = get_post( $post_id );
	$assert( $post instanceof WP_Post && 'publish' === $post->post_status, $label . ' must remain a published page.' );

	$url      = add_query_arg( 'legal-pages-smoke', (string) $post_id, get_permalink( $post_id ) );
	$response = wp_remote_get( $url, array( 'timeout' => 20 ) );
	$html     = is_wp_error( $response ) ? '' : (string) wp_remote_retrieve_body( $response );
	$rendered[ $post_id ] = $html;

	$assert( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ), $label . ' must return HTTP 200.' );
	$assert( 1 === preg_match( '/class="[^"]*\blegal-page\b[^"]*"/', $html ), $label . ' must use the dedicated readable content layout.' );
	$assert( str_contains( $html, 'class="legal-page__content"' ), $label . ' must render editor content.' );
	$assert( ! str_contains( $html, 'data-hero' ), $label . ' must not render the default marketing section stack.' );
}

$sitemap_html = $rendered[1469] ?? '';
$assert( ! str_contains( $sitemap_html, '[wp_sitemap_page]' ), 'The sitemap shortcode must be expanded by the content pipeline.' );
$assert( str_contains( $sitemap_html, 'wsp-container' ) || str_contains( $sitemap_html, 'wp-sitemap-page' ), 'The sitemap plugin must render its page listing.' );

$footer_html = $rendered[3] ?? '';
foreach ( array_keys( $pages ) as $post_id ) {
	$permalink = get_permalink( $post_id );
	$assert( is_string( $permalink ) && str_contains( $footer_html, 'href="' . esc_url( $permalink ) . '"' ), 'Footer must link to page ID ' . $post_id . '.' );
}

$cookie_url = get_permalink( 5170 );
$assert( is_string( $cookie_url ) && str_contains( $footer_html, 'consentContent' ) && str_contains( $footer_html, 'href="' . esc_url( $cookie_url ) . '"' ), 'Cookie notice must link to the cookie policy.' );

WP_CLI::log( sprintf( 'Legal pages: %d checks, %d failures.', $checks, count( $failures ) ) );
if ( $failures ) {
	WP_CLI::halt( 1 );
}

WP_CLI::success( 'Legal pages and sitemap contract passed.' );
