<?php
/** Compact post-apply contract for the migrated China page. */

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

$class = 'Platejka\\Core\\Cli\\DefaultPageMigrationCommand';
$assert( class_exists( $class ), 'Default-page migration command is available after apply.' );
if ( ! class_exists( $class ) ) {
	WP_CLI::halt( 1 );
	return;
}

$expected_order = array( 'hero', 'about', 'shipments', 'guarantees', 'documents', 'protection', 'review', 'work', 'problems', 'calculator', 'seo', 'faq', 'call' );
$rows           = get_field( 'platejka_sections', 1873 );
$hero           = get_field( 'hero', 'platejka_section_defaults' );
$protection     = get_field( 'protection', 'platejka_section_defaults' );

$assert( 1 === (int) get_option( 'platejka_default_page_builder_version', 0 ), 'Default-page migration marker is version 1.' );
$assert( (bool) get_field( 'platejka_page_builder', 1873 ), 'Post 1873 builder is enabled.' );
$assert( is_array( $rows ) && 13 === count( $rows ) && $expected_order === array_column( $rows, 'acf_fc_layout' ), 'Post 1873 has the exact approved 13-row order.' );
$assert( 13 === count( array_filter( array_column( $rows, 'enabled' ) ) ), 'All migrated rows are enabled.' );
$assert( 'default' === ( $rows[2]['variant'] ?? null ) && 'default' === ( $rows[3]['variant'] ?? null ) && 'default' === ( $rows[4]['variant'] ?? null ), 'The three variant rows explicitly select default.' );
$assert( is_numeric( $hero['background_image'] ?? null ) && is_numeric( $protection['items'][0]['image'] ?? null ), 'New canonical media values are attachment IDs.' );
$assert( ! str_contains( (string) wp_json_encode( array( $hero, $protection ) ), 'theme://' ), 'Applied canonical values contain no seed media paths.' );

$repeat = $class::apply();
$assert( false === ( $repeat['writes']['performed'] ?? true ) && array() === ( $repeat['media']['imported'] ?? null ), 'A repeated apply performs no writes and imports no media.' );

WP_CLI::log( sprintf( 'Applied default-page migration: %d checks, %d failures.', $checks, count( $failures ) ) );
if ( $failures ) {
	WP_CLI::halt( 1 );
}
WP_CLI::success( 'Applied default-page migration contract passed.' );
