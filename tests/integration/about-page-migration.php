<?php
/** Read-only contract for the page-22 About builder migration preview. */

defined( 'ABSPATH' ) || exit;
$checks = 0; $failures = array();
$assert = static function ( bool $condition, string $message ) use ( &$checks, &$failures ): void { ++$checks; if ( ! $condition ) { $failures[] = $message; WP_CLI::warning( $message ); } };
$snapshot = static function (): string {
	global $wpdb;
	return hash( 'sha256', serialize( array(
		$wpdb->get_results( "SELECT * FROM {$wpdb->postmeta} WHERE post_id = 22 ORDER BY meta_id", ARRAY_A ),
		$wpdb->get_results( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name = 'platejka_about_page_builder_version' ORDER BY option_name", ARRAY_A ),
		$wpdb->get_results( "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_platejka_section_seed_source' ORDER BY post_id", ARRAY_A ),
	) ) );
};
$class = 'Platejka\\Core\\Cli\\AboutPageMigrationCommand';
$assert( class_exists( $class ), 'About-page migration command is available.' );
if ( class_exists( $class ) ) {
	$before = $snapshot(); $report = $class::preview(); $repeat = $class::preview();
	$expected = array( 'about-hero', 'location', 'review-main', 'infrastructure', 'financial', 'employees', 'exhibitions', 'developing', 'call-about' );
	$assert( 'dry-run' === ( $report['mode'] ?? null ), 'Preview reports dry-run mode.' );
	$assert( 22 === ( $report['page']['id'] ?? null ), 'Preview targets only page 22.' );
	$assert( $expected === ( $report['page']['sections'] ?? null ), 'Preview contains the exact approved nine-section order.' );
	$assert( array( 'about-hero', 'location', 'infrastructure', 'financial', 'employees', 'exhibitions', 'developing', 'call-about' ) === ( $report['page_local_sections'] ?? null ), 'Preview seeds exactly eight local content sections.' );
	$assert( false === ( $report['writes']['performed'] ?? true ), 'Preview performs no writes.' );
	$assert( false === ( $report['conflict']['meaningful_rows'] ?? true ), 'An interrupted canonical ACF row save is recognized as resumable, not as an editor conflict.' );
	$assert( $report === $repeat, 'Repeated previews are deterministic.' );
	$sources = array_merge( $report['media']['reused'] ?? array(), $report['media']['to_import'] ?? array() );
	$names = array_map( static fn( $item ) => is_array( $item ) ? ( $item['source'] ?? '' ) : $item, $sources );
	$assert( count( $names ) === count( array_unique( $names ) ) && count( $names ) > 20, 'Preview plans every editable raster source at most once.' );
	$assert( hash_equals( $before, $snapshot() ), 'Preview leaves page 22, marker, media, and legacy fields unchanged.' );

	$meaningful = static fn() => array( array( 'acf_fc_layout' => 'location', 'enabled' => 1, 'overrides' => array( 'title' => 'Editor value' ) ) );
	add_filter( 'platejka_about_page_migration_current_rows', $meaningful );
	$conflict = $class::preview();
	$assert( true === ( $conflict['conflict']['meaningful_rows'] ?? false ), 'Preview reports meaningful pre-existing About rows as a conflict.' );
	remove_filter( 'platejka_about_page_migration_current_rows', $meaningful );
	$command_source = file_get_contents( WP_CONTENT_DIR . '/plugins/platejka-core/src/Cli/AboutPageMigrationCommand.php' );
	$conflict_guard = strpos( $command_source, 'if ( $apply && $conflict )' );
	$media_import = strpos( $command_source, 'importThemeMedia( $source )' );
	$assert( false !== $conflict_guard && false !== $media_import && $conflict_guard < $media_import && hash_equals( $before, $snapshot() ), 'Meaningful-row conflict guard precedes media or field writes.' );

	$temp = wp_tempnam( 'invalid-about-seed.json' ); file_put_contents( $temp, '{"schema_version":2}' );
	$invalid_path = static fn() => $temp; add_filter( 'platejka_about_page_seed_path', $invalid_path );
	$invalid_refused = false; try { $class::preview(); } catch ( RuntimeException $exception ) { $invalid_refused = str_contains( $exception->getMessage(), 'seed' ); }
	remove_filter( 'platejka_about_page_seed_path', $invalid_path ); wp_delete_file( $temp );
	$assert( $invalid_refused, 'Preview rejects an invalid or incomplete seed.' );
}
WP_CLI::log( sprintf( 'About-page migration preview: %d checks, %d failures.', $checks, count( $failures ) ) );
if ( $failures ) { WP_CLI::halt( 1 ); }
WP_CLI::success( 'About-page migration preview is read-only.' );
