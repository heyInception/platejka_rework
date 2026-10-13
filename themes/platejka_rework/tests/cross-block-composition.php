<?php
/** Builder-owned cross-block composition regression checks. */

require_once dirname( __DIR__ ) . '/inc/cross-blocks.php';

function platejka_cross_block_test_assert( bool $condition, string $message ): void {
	if ( ! $condition ) {
		WP_CLI::error( $message );
	}
}

platejka_cross_block_test_assert( ! function_exists( 'platejka_get_cross_block_rows' ), 'Runtime must not read legacy cross-block rows.' );
platejka_cross_block_test_assert( ! function_exists( 'platejka_compose_cross_blocks' ), 'Runtime must not compose sections from legacy cross-block rows.' );

$sections = platejka_resolve_page_section_rows(
	true,
	array(
		array( 'acf_fc_layout' => 'hero', 'enabled' => 1 ),
		array( 'acf_fc_layout' => 'cross-exchange', 'enabled' => 1, 'anchor' => 'exchange' ),
		array( 'acf_fc_layout' => 'destinations', 'enabled' => 1 ),
		array( 'acf_fc_layout' => 'cross-swift-problems', 'enabled' => 1 ),
		array( 'acf_fc_layout' => 'cross-company-metrics', 'enabled' => 0 ),
		array( 'acf_fc_layout' => 'faq', 'enabled' => 1 ),
	),
	array(),
	77
);

platejka_cross_block_test_assert(
	array( 'hero', 'cross-exchange', 'destinations', 'cross-swift-problems', 'faq' ) === array_column( $sections, 'slug' ),
	'Enabled dedicated builder rows must survive without legacy source data.'
);
platejka_cross_block_test_assert( 'exchange' === ( $sections[1]['anchor'] ?? '' ), 'Dedicated builder anchor was not preserved.' );
platejka_cross_block_test_assert( ! isset( $sections[1]['legacy_layout'], $sections[1]['cross_block_row'], $sections[1]['cross_block_adapter'] ), 'Dedicated rows must not carry legacy enrichment data.' );
platejka_cross_block_test_assert( ! isset( $sections[2]['legacy_layout'], $sections[2]['cross_block_row'], $sections[2]['cross_block_adapter'] ), 'Reusable sections must not carry legacy enrichment data.' );

WP_CLI::success( 'Cross-block composition checks passed.' );
