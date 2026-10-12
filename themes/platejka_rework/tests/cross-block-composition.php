<?php
/** Builder-owned cross-block composition regression checks. */

require_once dirname( __DIR__ ) . '/inc/cross-blocks.php';

function platejka_cross_block_test_assert( bool $condition, string $message ): void {
	if ( ! $condition ) {
		WP_CLI::error( $message );
	}
}

platejka_cross_block_test_assert( function_exists( 'platejka_get_cross_block_row' ), 'Cross-block source lookup is missing.' );
platejka_cross_block_test_assert( function_exists( 'platejka_compose_cross_blocks' ), 'Cross-block composer is missing.' );

$rows = array(
	array( 'acf_fc_layout' => 'destinations', 'vklyuchit_blok' => 1, 'destination_title' => 'Направления' ),
	array( 'acf_fc_layout' => 'wtt', 'show_block' => 1, 'title' => 'Обмен' ),
	array( 'acf_fc_layout' => 'company_metrics', 'show_block' => 0 ),
	array( 'acf_fc_layout' => 'swift_problems', 'show_block' => 1, 'title' => 'SWIFT' ),
);

$sections = platejka_compose_cross_blocks(
	array(
		'hero',
		array( 'slug' => 'cross-exchange', 'row' => array( 'enabled' => 1 ), 'instance' => 'section-77-2' ),
		array( 'slug' => 'destinations', 'row' => array( 'enabled' => 1 ), 'instance' => 'section-77-3' ),
		array( 'slug' => 'cross-company-metrics', 'row' => array( 'enabled' => 1 ), 'instance' => 'section-77-4' ),
		array( 'slug' => 'cross-swift-problems', 'row' => array( 'enabled' => 1 ), 'instance' => 'section-77-5' ),
		array( 'slug' => 'cross-exchange', 'row' => array( 'enabled' => 1 ), 'instance' => 'section-77-6' ),
		'faq',
	),
	$rows,
	77
);

$slugs = array_map( 'platejka_cross_block_section_slug', $sections );
platejka_cross_block_test_assert(
	array( 'hero', 'cross-exchange', 'destinations', 'cross-swift-problems', 'faq' ) === $slugs,
	'Builder order, missing/disabled source filtering, or duplicate prevention changed.'
);
platejka_cross_block_test_assert( 'wtt' === $sections[1]['legacy_layout'], 'Dedicated builder row did not resolve its source layout.' );
platejka_cross_block_test_assert( 'Обмен' === $sections[1]['cross_block_row']['title'], 'Dedicated builder row did not receive source data.' );
platejka_cross_block_test_assert( 'destinations' === $sections[2]['legacy_layout'], 'Reusable builder section was not enriched.' );
platejka_cross_block_test_assert( 'section-77-3' === $sections[2]['instance'], 'Reusable section instance/order changed.' );

$no_injection = platejka_compose_cross_blocks(
	array( 'hero', 'faq' ),
	array( array( 'acf_fc_layout' => 'key_facts', 'show_block' => 1 ) ),
	12
);
platejka_cross_block_test_assert( array( 'hero', 'faq' ) === $no_injection, 'Legacy rows must not inject sections absent from the builder.' );

WP_CLI::success( 'Cross-block composition checks passed.' );
