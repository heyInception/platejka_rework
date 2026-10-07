<?php
/** Focused contracts for the page-22-only About section builder. */

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

$assert( function_exists( 'platejka_get_about_page_sections' ), 'About builder exposes its page-specific resolver.' );
$registry = platejka_section_builder_registry();
$expected = array( 'about-hero', 'location', 'review-main', 'infrastructure', 'financial', 'employees', 'exhibitions', 'developing', 'call-about' );
$assert( array() === array_diff( $expected, array_keys( $registry ) ), 'Renderer registry contains all nine About section slugs.' );

if ( function_exists( 'platejka_get_about_page_sections' ) ) {
	$fallback = array( 'about-hero', 'location' );
	$assert( $fallback === platejka_get_about_page_sections( 24, $fallback ), 'A non-About post always keeps its own fallback.' );
	$load_disabled = static fn() => 0;
	add_filter( 'acf/load_value/name=platejka_about_page_builder', $load_disabled );
	$assert( $fallback === platejka_get_about_page_sections( 22, $fallback ), 'A disabled About builder returns the static fallback unchanged.' );
	remove_filter( 'acf/load_value/name=platejka_about_page_builder', $load_disabled );
	if ( function_exists( 'acf_get_store' ) ) {
		acf_get_store( 'values' )->reset();
	}

	$load_enabled = static fn() => 1;
	$load_rows    = static fn() => array(
		array( 'acf_fc_layout' => 'about-hero', 'enabled' => 1, 'anchor' => 'intro' ),
		array( 'acf_fc_layout' => 'location', 'enabled' => 0 ),
		array( 'acf_fc_layout' => 'review-main', 'enabled' => 1, 'anchor' => 'reviews' ),
		array( 'acf_fc_layout' => 'financial' ),
		array( 'acf_fc_layout' => 'review-main', 'enabled' => 1, 'anchor' => 'more-reviews' ),
	);
	add_filter( 'acf/load_value/name=platejka_about_page_builder', $load_enabled );
	add_filter( 'acf/load_value/name=platejka_about_sections', $load_rows );
	$resolved = platejka_get_about_page_sections( 22, $fallback );
	remove_filter( 'acf/load_value/name=platejka_about_sections', $load_rows );
	remove_filter( 'acf/load_value/name=platejka_about_page_builder', $load_enabled );

	$assert( array( 'about-hero', 'review-main', 'review-main' ) === array_column( $resolved, 'slug' ), 'About rows keep order while disabled and default-disabled rows are omitted.' );
	$assert( array( 'section-22-1', 'section-22-3', 'section-22-5' ) === array_column( $resolved, 'instance' ), 'Repeatable About rows receive deterministic distinct instances.' );
	$assert( array( 'intro', 'reviews', 'more-reviews' ) === array_column( $resolved, 'anchor' ), 'About row anchors survive normalization.' );

}

$hero_error = platejka_validate_builder_rows( array(
	array( 'acf_fc_layout' => 'about-hero', 'enabled' => 1 ),
	array( 'acf_fc_layout' => 'about-hero', 'enabled' => 1 ),
) );
$assert( is_wp_error( $hero_error ) && 'multiple_hero_sections' === $hero_error->get_error_code(), 'About hero is limited to one row.' );
$anchor_error = apply_filters( 'acf/validate_value/key=field_platejka_about_sections_v1', true, array(
	array( 'acf_fc_layout' => 'location', 'anchor' => 'office' ),
	array( 'acf_fc_layout' => 'review-main', 'anchor' => 'office' ),
) );
$assert( is_string( $anchor_error ), 'The About field uses the shared duplicate-anchor validation.' );

$source = file_get_contents( get_theme_file_path( 'page-about.php' ) );
$assert( str_contains( $source, 'platejka_get_about_page_sections' ), 'About template calls only the dedicated resolver.' );

WP_CLI::log( sprintf( 'About section builder: %d checks, %d failures.', $checks, count( $failures ) ) );
if ( $failures ) {
	WP_CLI::halt( 1 );
}
WP_CLI::success( 'About section builder contract passed.' );
