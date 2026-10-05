<?php
/** Focused smoke test for conditional section loading and rendering. */
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

$required_functions = array(
	'platejka_use_sections',
	'platejka_render_section',
	'platejka_render_sections',
);

foreach ( $required_functions as $function ) {
	$assert( function_exists( $function ), 'Section loader exposes ' . $function . '().' );
}

if ( $failures ) {
	WP_CLI::halt( 1 );
}

$sections = platejka_use_sections(
	array(
		'hero-main',
		array(
			'slug' => 'shipments',
			'mode' => 'main',
		),
		'cases',
		'../footer',
		'call-about',
	)
);

$assert( 4 === count( $sections ), 'Traversal-like section slugs are rejected.' );
$assert( 'hero' === $sections[0]['directory'] && 'hero-main' === $sections[0]['template'], 'hero-main resolves to the hero directory and hero-main template.' );
$assert( 'main' === $sections[1]['mode'], 'The main render mode is preserved.' );
$assert( 'call' === $sections[3]['directory'] && 'call-about' === $sections[3]['template'], 'call-about resolves to the call directory and call-about template.' );

foreach ( array( 'hero', 'shipments', 'cases', 'call', 'faq' ) as $slug ) {
	wp_dequeue_style( 'platejka-rework-section-' . $slug );
	wp_dequeue_script( 'platejka-rework-section-' . $slug );
}
platejka_rework_assets();

$assert( wp_style_is( 'platejka-rework-section-hero', 'enqueued' ), 'A declared section enqueues its CSS.' );
$assert( wp_script_is( 'platejka-rework-section-hero', 'enqueued' ), 'A declared section enqueues its JavaScript.' );
$assert( wp_style_is( 'platejka-rework-section-cases', 'enqueued' ), 'A CSS-only section enqueues its CSS.' );
$assert( ! wp_script_is( 'platejka-rework-section-cases', 'enqueued' ), 'A missing optional section JavaScript file is ignored.' );
$assert( ! wp_style_is( 'platejka-rework-section-faq', 'enqueued' ), 'An undeclared section does not enqueue its CSS.' );
$assert( ! wp_script_is( 'platejka-rework-section-faq', 'enqueued' ), 'An undeclared section does not enqueue its JavaScript.' );

ob_start();
platejka_render_section(
	array(
		'slug' => 'shipments',
		'mode' => 'main',
	)
);
$main_shipments = (string) ob_get_clean();
$assert( str_contains( $main_shipments, 'shipments--main' ), 'Main mode renders the main export branch.' );
$assert( ! str_contains( $main_shipments, 'shipments--default' ), 'Main mode omits the default export branch.' );
$assert( ! str_contains( $main_shipments, '@if' ), 'Export mode directives are removed from rendered output.' );
$assert( str_contains( $main_shipments, '/sections/shipments/img/shipments/main-1.png' ), 'Relative image paths become absolute section URLs.' );

ob_start();
platejka_render_section( 'shipments' );
$default_shipments = (string) ob_get_clean();
$assert( str_contains( $default_shipments, 'shipments--default' ), 'Default mode renders the default export branch.' );
$assert( ! str_contains( $default_shipments, 'shipments--main' ), 'Default mode omits the main export branch.' );

WP_CLI::log( sprintf( 'Section loader smoke: %d checks, %d failures.', $checks, count( $failures ) ) );
if ( $failures ) {
	WP_CLI::halt( 1 );
}
WP_CLI::success( 'Section loader smoke passed.' );
