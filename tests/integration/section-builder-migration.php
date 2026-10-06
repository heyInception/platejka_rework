<?php
/** Read-only contract for the section-builder migration preview. */

defined( 'ABSPATH' ) || exit;

$checks = 0; $failures = array();
$assert = static function ( bool $condition, string $message ) use ( &$checks, &$failures ): void {
	++$checks;
	if ( ! $condition ) { $failures[] = $message; WP_CLI::warning( $message ); }
};
$snapshot = static function (): string {
	global $wpdb;
	return hash( 'sha256', serialize( array(
		$wpdb->get_results( "SELECT * FROM {$wpdb->posts} ORDER BY ID", ARRAY_A ),
		$wpdb->get_results( "SELECT * FROM {$wpdb->postmeta} ORDER BY meta_id", ARRAY_A ),
		$wpdb->get_results( "SELECT * FROM {$wpdb->options} ORDER BY option_id", ARRAY_A ),
	) ) );
};

$before = $snapshot();
$assert( class_exists( 'Platejka\\Core\\Cli\\SectionBuilderMigrationCommand' ), 'Migration command class is available in WP-CLI.' );
if ( $failures ) { WP_CLI::halt( 1 ); return; }

$report = \Platejka\Core\Cli\SectionBuilderMigrationCommand::preview();
$second_report = \Platejka\Core\Cli\SectionBuilderMigrationCommand::preview();
$expected = array( 'hero-main', 'about', 'shipments', 'guarantees', 'documents', 'compliance', 'review-main', 'work', 'calculator', 'with-us', 'destinations', 'seo', 'problems', 'serves', 'cases', 'table', 'faq', 'call' );
$assert( 'dry-run' === ( $report['mode'] ?? null ), 'Preview is dry-run by default.' );
$assert( $expected === ( $report['page']['sections'] ?? null ), 'Preview preserves the approved 18-section order.' );
$assert( $expected === ( $report['options']['sections'] ?? null ), 'Preview writes global defaults in the canonical section order.' );
$assert( 18 === count( $report['page']['rows'] ?? array() ), 'Preview contains exactly 18 enabled home rows.' );
$assert( 305 === ( $report['options']['cf7']['call'] ?? null ) && 4966 === ( $report['options']['cf7']['calculator'] ?? null ), 'Preview uses the approved CF7 IDs.' );
$sources = array_merge( $report['media']['reused'] ?? array(), $report['media']['to_import'] ?? array() );
$source_names = array_map( static fn( $item ) => is_array( $item ) ? ( $item['source'] ?? '' ) : $item, $sources );
$assert( count( $source_names ) === count( array_unique( $source_names ) ), 'Preview plans each media source at most once.' );
$assert( false === ( $report['writes']['performed'] ?? true ), 'Preview reports no performed writes.' );
$assert( $report === $second_report, 'Repeated previews are deterministic.' );
$assert( hash_equals( $before, $snapshot() ), 'Preview leaves posts, postmeta, options, and media unchanged.' );

WP_CLI::log( sprintf( 'Section migration preview: %d checks, %d failures.', $checks, count( $failures ) ) );
if ( $failures ) { WP_CLI::halt( 1 ); }
WP_CLI::success( 'Section builder migration preview is read-only.' );
