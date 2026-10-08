<?php
/**
 * Regression check for calculator select flag URLs.
 *
 * Run with:
 * studio wp --path <site-root> eval-file wp-content/themes/platejka_rework/tests/calculator-flag-urls.php
 */

$section_anchor   = 'calculator-test';
$section_instance = 'calculator-test';
$section_data     = array(
	'labels' => array(
		array(
			'key'   => 'country_ru',
			'label' => 'Россия',
		),
	),
);

ob_start();
require dirname( __DIR__ ) . '/sections/calculator/calculator.php';
$html = (string) ob_get_clean();

preg_match_all( '/\sdata-flag="([^"]+)"/', $html, $matches );
$actual = array_values( array_unique( $matches[1] ?? array() ) );
$base   = trailingslashit( get_theme_file_uri( 'sections/calculator/img' ) );
$expected = array(
	$base . 'ru-flag.png',
	$base . 'cn-flag.png',
	$base . 'usa-flag.png',
);

if ( $expected !== $actual ) {
	WP_CLI::error(
		'Calculator select flag URLs are not absolute. Actual: ' . wp_json_encode( $actual )
	);
}

WP_CLI::success( 'Calculator select flag URLs are absolute.' );
