<?php
/**
 * Integration checks for the ACF-backed footer menus.
 *
 * Run with:
 * studio wp --path <site-root> eval-file wp-content/themes/platejka_rework/tests/footer-menu.php
 */

$template = dirname( __DIR__ ) . '/sections/footer/footer.php';
$menus    = array_filter(
	array_map(
		static fn ( string $field ): array => (array) get_field( $field, 'option' ),
		array( 'menyu_1', 'menyu_2', 'menyu_3' )
	),
	static fn ( array $menu ): bool => ! empty( $menu['zagolovok'] ) && ! empty( $menu['ssylki'] )
);

if ( 3 !== count( $menus ) ) {
	WP_CLI::error( 'The three Footer Menu groups must be populated in ACF Options.' );
}

ob_start();
require $template;
$html = (string) ob_get_clean();

foreach ( $menus as $menu ) {
	$title = (string) $menu['zagolovok'];
	if ( ! str_contains( $html, esc_html( $title ) ) ) {
		WP_CLI::error( 'Footer menu title is missing: ' . $title );
	}

	foreach ( $menu['ssylki'] as $row ) {
		$link = is_array( $row['ssylka_footer'] ?? null ) ? $row['ssylka_footer'] : array();
		if ( empty( $link['url'] ) || empty( $link['title'] ) ) {
			continue;
		}

		$anchor = '<a href="' . esc_url( (string) $link['url'] ) . '"';
		if ( ! str_contains( $html, $anchor ) || ! str_contains( $html, esc_html( (string) $link['title'] ) ) ) {
			WP_CLI::error( 'Footer menu link is missing: ' . (string) $link['title'] );
		}
		if ( '_blank' === ( $link['target'] ?? '' ) && ! str_contains( $html, $anchor . ' target="_blank" rel="noopener noreferrer"' ) ) {
			WP_CLI::error( 'Footer menu _blank link is missing safe rel attributes.' );
		}
	}
}

preg_match( '/<div class="footer__navigation">(.*?)<\/div>\s*<\/div>/s', $html, $navigation );
if ( empty( $navigation[1] ) || preg_match( '/<a[^>]+href=(?:"|\')\s*(?:#|javascript:)/i', $navigation[1] ) ) {
	WP_CLI::error( 'Footer contains a placeholder or executable URL.' );
}

WP_CLI::success( 'ACF footer menus render safely.' );
