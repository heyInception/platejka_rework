<?php
/** Read-only contract for the post-1873 builder migration preview. */

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
$snapshot = static function (): string {
	global $wpdb;
	return hash(
		'sha256',
		serialize(
			array(
				$wpdb->get_results( "SELECT * FROM {$wpdb->postmeta} WHERE post_id = 1873 ORDER BY meta_id", ARRAY_A ),
				$wpdb->get_results( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name = 'platejka_default_page_builder_version' OR option_name LIKE 'platejka_section_defaults_hero%' OR option_name LIKE '_platejka_section_defaults_hero%' OR option_name LIKE 'platejka_section_defaults_protection%' OR option_name LIKE '_platejka_section_defaults_protection%' ORDER BY option_name", ARRAY_A ),
				$wpdb->get_results( "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_platejka_section_seed_source' ORDER BY post_id", ARRAY_A ),
			)
		)
	);
};

$class = 'Platejka\\Core\\Cli\\DefaultPageMigrationCommand';
$assert( class_exists( $class ), 'Default-page migration command is available.' );
if ( ! class_exists( $class ) ) {
	WP_CLI::halt( 1 );
	return;
}

$before = $snapshot();
$report = $class::preview();
$repeat = $class::preview();
$expected_order = array( 'hero', 'about', 'shipments', 'guarantees', 'documents', 'protection', 'review', 'work', 'problems', 'calculator', 'seo', 'faq', 'call' );

$assert( 'dry-run' === ( $report['mode'] ?? null ), 'Preview reports dry-run mode.' );
$assert( 1873 === ( $report['page']['id'] ?? null ), 'Preview targets only post 1873.' );
$assert( $expected_order === ( $report['page']['sections'] ?? null ), 'Preview contains the approved 13-section order.' );
$assert( array( 'hero', 'protection' ) === ( $report['options']['sections'] ?? null ), 'Preview seeds only hero and protection canonical groups.' );
$assert( array( 'hero' => true, 'protection' => true ) === ( $report['writes']['options'] ?? null ), 'Structurally present but empty ACF groups are scheduled for seeding.' );
$assert( false === ( $report['writes']['performed'] ?? true ), 'Preview performs no writes.' );
$assert( $report === $repeat, 'Repeated previews are deterministic.' );

$sources = array_merge( $report['media']['reused'] ?? array(), $report['media']['to_import'] ?? array() );
$source_names = array_map( static fn( $item ) => is_array( $item ) ? ( $item['source'] ?? '' ) : $item, $sources );
$assert( count( $source_names ) === count( array_unique( $source_names ) ), 'Each seed media source is planned at most once.' );
$assert( 9 === count( $source_names ), 'Preview accounts for all nine hero/protection media sources.' );
$assert( hash_equals( $before, $snapshot() ), 'Preview leaves post 1873, target options, marker, and migrated media unchanged.' );

WP_CLI::log( sprintf( 'Default-page migration preview: %d checks, %d failures.', $checks, count( $failures ) ) );
if ( $failures ) {
	WP_CLI::halt( 1 );
}
WP_CLI::success( 'Default-page migration preview is read-only.' );
