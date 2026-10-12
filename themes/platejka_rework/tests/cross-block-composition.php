<?php
/**
 * Cross-block composition regression checks.
 *
 * Run with:
 * studio wp --path <site-root> eval-file <worktree>/themes/platejka_rework/tests/cross-block-composition.php
 */

$integration = dirname( __DIR__ ) . '/inc/cross-blocks.php';
if ( is_file( $integration ) ) {
	require_once $integration;
}

function platejka_cross_block_test_assert( bool $condition, string $message ): void {
	if ( ! $condition ) {
		WP_CLI::error( $message );
	}
}

platejka_cross_block_test_assert( function_exists( 'platejka_cross_block_map' ), 'Cross-block map is missing.' );
platejka_cross_block_test_assert( function_exists( 'platejka_compose_cross_blocks' ), 'Cross-block composer is missing.' );

$expected_layouts = array(
	'destinations',
	'wtt',
	'company_metrics',
	'problems_solved',
	'client_reviews',
	'external_ratings',
	'documents',
	'key_facts',
	'transfer_goals',
	'swift_problems',
	'guarantees',
	'audience',
	'data_table',
);
$map              = platejka_cross_block_map();

platejka_cross_block_test_assert( $expected_layouts === array_keys( $map ), 'Included layout allowlist changed.' );
foreach ( array( 'transfer_calculator', 'work', 'reviews', 'tariff' ) as $excluded ) {
	platejka_cross_block_test_assert( ! isset( $map[ $excluded ] ), 'Excluded layout is present: ' . $excluded );
}

platejka_cross_block_test_assert( platejka_cross_block_is_enabled( array() ), 'Rows without a visibility field must remain enabled.' );
platejka_cross_block_test_assert( ! platejka_cross_block_is_enabled( array( 'show_block' => 0 ) ), 'show_block=0 must disable a row.' );
platejka_cross_block_test_assert( ! platejka_cross_block_is_enabled( array( 'vklyuchit_blok' => '0' ) ), 'vklyuchit_blok=0 must disable a row.' );

$existing = platejka_compose_cross_blocks(
	array( 'hero', 'destinations', 'faq', 'call' ),
	array(
		array( 'acf_fc_layout' => 'unknown' ),
		array( 'acf_fc_layout' => 'destinations', 'vklyuchit_blok' => 1, 'destination_title' => 'Направления' ),
		array( 'acf_fc_layout' => 'wtt', 'vklyuchit_blok' => 0 ),
	),
	77
);

platejka_cross_block_test_assert( 4 === count( $existing ), 'Existing mapped sections must not be duplicated.' );
platejka_cross_block_test_assert( is_array( $existing[1] ), 'Existing string section must become an enriched config.' );
platejka_cross_block_test_assert( 'destinations' === $existing[1]['slug'], 'Existing mapped slug changed.' );
platejka_cross_block_test_assert( 'destinations' === $existing[1]['legacy_layout'], 'Legacy layout metadata is missing.' );
platejka_cross_block_test_assert( 'Направления' === $existing[1]['cross_block_row']['destination_title'], 'Original cross-block row is not attached.' );

$inserted = platejka_compose_cross_blocks(
	array( 'hero', 'faq', 'call' ),
	array(
		array( 'acf_fc_layout' => 'wtt', 'show_block' => 1 ),
		array( 'acf_fc_layout' => 'company_metrics', 'show_block' => 1 ),
		array( 'acf_fc_layout' => 'transfer_goals', 'show_block' => 1 ),
		array( 'acf_fc_layout' => 'swift_problems', 'show_block' => 1 ),
	),
	91
);
$inserted_slugs = array_map(
	static fn( $section ) => is_array( $section ) ? $section['slug'] : $section,
	$inserted
);

platejka_cross_block_test_assert(
	array( 'hero', 'cross-exchange', 'cross-company-metrics', 'cross-transfer-goals', 'cross-swift-problems', 'faq', 'call' ) === $inserted_slugs,
	'Injected cross-blocks must retain ACF order before FAQ/call.'
);
platejka_cross_block_test_assert( 'cross-91-1-wtt' === $inserted[1]['instance'], 'Injected instance must be deterministic.' );
platejka_cross_block_test_assert( $inserted[3]['slug'] !== $inserted[4]['slug'], 'Distinct card-grid layouts must remain distinct sections.' );

$appended = platejka_compose_cross_blocks(
	array( 'hero', 'seo' ),
	array( array( 'acf_fc_layout' => 'key_facts', 'show_block' => 1 ) ),
	12
);
platejka_cross_block_test_assert( 'cross-key-facts' === $appended[2]['slug'], 'Cross-block must append when FAQ/call is absent.' );

WP_CLI::success( 'Cross-block composition checks passed.' );
