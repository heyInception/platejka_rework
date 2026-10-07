<?php
/** Post-apply contract; before apply it also pins verification ordering in source. */

defined( 'ABSPATH' ) || exit;
$checks = 0; $failures = array();
$assert = static function ( bool $condition, string $message ) use ( &$checks, &$failures ): void { ++$checks; if ( ! $condition ) { $failures[] = $message; WP_CLI::warning( $message ); } };
$class = 'Platejka\\Core\\Cli\\AboutPageMigrationCommand';
$assert( class_exists( $class ), 'About-page migration command is available for apply verification.' );
$source = file_get_contents( WP_CONTENT_DIR . '/plugins/platejka-core/src/Cli/AboutPageMigrationCommand.php' );
$verify_position = strpos( $source, 'verifyContentAndRows(' );
$enable_position = strpos( $source, "update_field( 'field_platejka_about_page_builder_v1'" );
$marker_position = strpos( $source, 'update_option( self::VERSION_OPTION' );
$assert( false !== $verify_position && false !== $enable_position && false !== $marker_position && $verify_position < $enable_position && $enable_position < $marker_position, 'Source verifies content before enabling the builder and writes the marker last.' );
$assert( str_contains( $source, 'platejka_about_page_migration_verify' ), 'Migration exposes a controlled verification-failure seam.' );
$assert( ! str_contains( $source, 'if ( $rows_changed && ! update_field' ), 'Flexible-content success is determined by read-back verification, not the root update_field return value.' );

if ( 1 === (int) get_option( 'platejka_about_page_builder_version', 0 ) && class_exists( $class ) ) {
	$expected = array( 'about-hero', 'location', 'review-main', 'infrastructure', 'financial', 'employees', 'exhibitions', 'developing', 'call-about' );
	$rows = get_field( 'platejka_about_sections', 22 );
	$assert( (bool) get_field( 'platejka_about_page_builder', 22 ), 'Page 22 About builder is enabled.' );
	$assert( is_array( $rows ) && $expected === array_column( $rows, 'acf_fc_layout' ), 'Applied About rows preserve the exact order.' );
	$assert( 9 === count( array_filter( array_column( $rows, 'enabled' ) ) ), 'All nine migrated rows are enabled.' );
	$assert( ! str_contains( (string) wp_json_encode( $rows ), 'theme://' ), 'Applied About rows contain attachment IDs instead of theme media sources.' );
	$repeat = $class::preview();
	$assert( false === ( $repeat['writes']['performed'] ?? true ) && array() === ( $repeat['media']['to_import'] ?? null ), 'Post-apply preview is idempotent and plans no imports.' );
} else {
	WP_CLI::log( 'Database apply checks pending reviewed --apply.' );
}
WP_CLI::log( sprintf( 'About-page migration apply contract: %d checks, %d failures.', $checks, count( $failures ) ) );
if ( $failures ) { WP_CLI::halt( 1 ); }
WP_CLI::success( 'About-page migration apply contract passed for the current state.' );
