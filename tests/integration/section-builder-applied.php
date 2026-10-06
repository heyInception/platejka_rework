<?php
/** Post-apply verification for the migrated home section builder. */
defined( 'ABSPATH' ) || exit;

$checks = 0; $failures = array();
$assert = static function ( bool $condition, string $message ) use ( &$checks, &$failures ): void {
	++$checks;
	if ( ! $condition ) { $failures[] = $message; WP_CLI::warning( $message ); }
};

$expected = array( 'hero-main', 'about', 'shipments', 'guarantees', 'documents', 'compliance', 'review-main', 'work', 'calculator', 'with-us', 'destinations', 'seo', 'problems', 'serves', 'cases', 'table', 'faq', 'call' );
$assert( 1 === (int) get_option( 'platejka_section_builder_version', 0 ), 'Migration marker is version 1.' );
$assert( 6 === (int) get_option( 'platejka_section_builder_additive_version', 0 ), 'Additive migration marker is version 6.' );
$assert( (bool) get_field( 'platejka_page_builder', 24 ), 'Page 24 builder is enabled.' );
$rows = get_field( 'platejka_sections', 24 );
$assert( is_array( $rows ) && 18 === count( $rows ), 'Page 24 contains exactly 18 builder rows.' );
$assert( $expected === array_column( $rows, 'acf_fc_layout' ), 'Page 24 keeps the approved section order.' );
$assert( 18 === count( array_filter( array_column( $rows, 'enabled' ) ) ), 'All migrated home rows are enabled.' );

$options = array();
foreach ( $expected as $slug ) {
	$options[ $slug ] = get_field( str_replace( '-', '_', $slug ), 'platejka_section_defaults' );
	$assert( is_array( $options[ $slug ] ), 'Global defaults are available for ' . $slug . '.' );
}
$assert( ! str_contains( (string) wp_json_encode( $options ), 'theme://' ), 'Migrated global defaults contain attachment IDs, not seed paths.' );
$assert( 305 === (int) ( $options['call']['form'] ?? 0 ) && 4966 === (int) ( $options['calculator']['form'] ?? 0 ), 'Migrated defaults retain CF7 IDs 305 and 4966.' );
$assert( 16 <= count( $options['calculator']['labels'] ?? array() ), 'Calculator interface labels are stored as editable global defaults.' );
$assert( 5 === count( $options['review-main']['video_items'] ?? array() ), 'Review defaults contain all five video cards.' );
$assert( 3 === count( $options['review-main']['ratings'] ?? array() ), 'Review defaults contain all three rating sources.' );
$assert( 7 === count( array_filter( wp_list_pluck( $options['compliance']['banks'] ?? array(), 'image' ), static fn( $id ): bool => is_numeric( $id ) && (int) $id > 0 ) ), 'Compliance defaults contain all seven original bank logos as attachments.' );
$assert( 5 === count( array_filter( wp_list_pluck( $options['review-main']['video_items'] ?? array(), 'image' ), static fn( $id ): bool => is_numeric( $id ) && (int) $id > 0 ) ), 'Review video cards use migrated attachment images.' );
$assert( 3 === count( array_filter( wp_list_pluck( $options['review-main']['items'] ?? array(), 'date' ) ) ), 'Review defaults preserve all original review dates.' );
$assert( is_numeric( $options['destinations']['image'] ?? null ) && (int) $options['destinations']['image'] > 0, 'Destinations keeps its responsive globe attachment.' );
$assert( 5 === count( array_filter( wp_list_pluck( $options['table']['items'] ?? array(), 'partner_value' ) ) ), 'Comparison rows preserve the financial-company column.' );
$assert( 5 === count( array_filter( wp_list_pluck( $options['table']['items'] ?? array(), 'bank_value' ) ) ), 'Comparison rows preserve the bank column.' );

$sections = platejka_get_page_sections( 24, array() );
$assert( 18 === count( $sections ), 'Runtime resolver returns all 18 enabled sections.' );
ob_start();
platejka_render_sections( $sections );
$html = (string) ob_get_clean();
$assert( ! str_contains( $html, 'theme://' ), 'Rendered home HTML contains no seed media paths.' );
$assert( str_contains( $html, 'class="hero__image' ) && str_contains( $html, 'loading="eager"' ) && str_contains( $html, 'fetchpriority="high"' ), 'Hero image renders eagerly with high fetch priority.' );
$assert( str_contains( $html, 'width="' ) && str_contains( $html, 'height="' ) && str_contains( $html, 'srcset="' ) && str_contains( $html, 'sizes="' ), 'Rendered editorial images expose intrinsic responsive attributes.' );
$assert( substr_count( $html, 'wpcf7' ) >= 2, 'Both selected CF7 forms render on the migrated home.' );

preg_match_all( '/(?:^|\s)id="([^"]+)"/', $html, $matches );
$ids = $matches[1] ?? array();
$assert( count( $ids ) === count( array_unique( $ids ) ), 'Rendered home IDs are unique.' );
foreach ( $expected as $slug ) {
	$needle = 'class="' . ( 'table' === $slug ? 'comparison' : ( 'hero-main' === $slug ? 'hero' : $slug ) );
	$assert( str_contains( $html, $needle ), 'Rendered home includes section ' . $slug . '.' );
}

$snapshot = static function () use ( $expected ): string {
	global $wpdb;
	$values = array();
	foreach ( $expected as $slug ) {
		$values[ $slug ] = get_field( str_replace( '-', '_', $slug ), 'platejka_section_defaults', false );
	}
	return hash( 'sha256', serialize( array(
		$values,
		get_field( 'platejka_page_builder', 24, false ),
		get_field( 'platejka_sections', 24, false ),
		$wpdb->get_results( "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_platejka_section_seed_source' ORDER BY post_id", ARRAY_A ),
	) ) );
};
$before_repeat = $snapshot();
$repeat_report = \Platejka\Core\Cli\SectionBuilderMigrationCommand::apply();
$assert( false === ( $repeat_report['writes']['performed'] ?? true ) && array() === ( $repeat_report['media']['imported'] ?? null ), 'A repeated apply performs no writes and imports no duplicate media.' );
$assert( hash_equals( $before_repeat, $snapshot() ), 'A repeated apply preserves editor data, builder rows, and migrated media.' );

WP_CLI::log( sprintf( 'Applied section builder: %d checks, %d failures.', $checks, count( $failures ) ) );
if ( $failures ) { WP_CLI::halt( 1 ); }
WP_CLI::success( 'Applied section builder verification passed.' );
