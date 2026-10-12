<?php
/** Regression tests for the cross-block synchronization manifest. */

$tool = dirname( __DIR__ ) . '/tools/cross-block-sync.php';
if ( ! is_file( $tool ) ) {
	WP_CLI::error( 'Cross-block synchronization tool is missing.' );
}
require_once $tool;

function platejka_cross_sync_assert( bool $condition, string $message ): void {
	if ( ! $condition ) {
		WP_CLI::error( $message );
	}
}

platejka_cross_sync_assert(
	array( 'dry-run' => true, 'production-manifest' => 'manifest.json' ) === platejka_cross_sync_parse_arguments( array( 'dry-run', 'production-manifest=manifest.json' ) ),
	'WP-CLI positional argument parsing changed.'
);

$html = <<<'HTML'
<main>
	<section class="cross-block cb-exchange calculate"></section>
	<section class="cross-block cb-calculator calc"></section>
	<section class="cross-block destinations cb-destinations"></section>
	<section class="cross-block cb-card-section cb-card-section--swift_problems"></section>
	<section class="cross-block cb-card-section cb-card-section--audience"></section>
</main>
HTML;

platejka_cross_sync_assert(
	array( 'wtt', 'destinations', 'swift_problems', 'audience' ) === platejka_cross_sync_parse_html( $html ),
	'Production HTML parsing or transfer-calculator exclusion changed.'
);

$original = array(
	array( 'acf_fc_layout' => 'hero', 'field_platejka_row_hero_enabled_v1' => '1', 'custom' => array( 'keep' => "line one\nline two [shortcode]" ) ),
	array( 'acf_fc_layout' => 'destinations', 'field_platejka_row_destinations_enabled_v1' => '1', 'field_platejka_row_destinations_anchor_v1' => 'countries' ),
	array( 'acf_fc_layout' => 'faq', 'field_platejka_row_faq_enabled_v1' => '1' ),
	array( 'acf_fc_layout' => 'call', 'field_platejka_row_call_enabled_v1' => '1' ),
);
$merged = platejka_cross_sync_merge_builder_rows( $original, array( 'destinations', 'swift_problems', 'audience' ) );

platejka_cross_sync_assert(
	array( 'hero', 'destinations', 'cross-swift-problems', 'cross-audience', 'faq', 'call' ) === array_column( $merged, 'acf_fc_layout' ),
	'Production associations were not mapped into builder order.'
);
platejka_cross_sync_assert( $original[0] === $merged[0] && $original[1] === $merged[1] && $original[2] === $merged[4] && $original[3] === $merged[5], 'Unrelated builder rows were modified.' );
platejka_cross_sync_assert( array( 'acf_fc_layout' => 'cross-swift-problems', 'field_platejka_row_cross_swift_problems_enabled_v1' => 1, 'field_platejka_row_cross_swift_problems_anchor_v1' => '' ) === $merged[2], 'New dedicated raw row shape changed.' );
platejka_cross_sync_assert( $merged === platejka_cross_sync_merge_builder_rows( $merged, array( 'destinations', 'swift_problems', 'audience' ) ), 'Builder merge is not idempotent.' );

$plan = platejka_cross_sync_plan_page(
	'europe',
	2182,
	array( 'swift_problems', 'destinations', 'guarantees', 'problems_solved' ),
	$original,
	array(
		array( 'acf_fc_layout' => 'destinations', 'show_block' => 1 ),
		array( 'acf_fc_layout' => 'guarantees', 'show_block' => 0 ),
	)
);

platejka_cross_sync_assert( array( 'destinations' ) === $plan['resolvable_source_rows'], 'Resolvable source rows changed.' );
platejka_cross_sync_assert( array( 'swift_problems', 'guarantees', 'problems_solved' ) === $plan['unresolved_source_rows'], 'Missing or disabled source rows must be unresolved.' );
platejka_cross_sync_assert( array( 'cross-swift-problems', 'guarantees', 'problems' ) === $plan['add_builder_rows'], 'Exact proposed builder additions changed.' );
platejka_cross_sync_assert( true === $plan['would_change'], 'Manifest failed to report a builder change.' );

$second = platejka_cross_sync_plan_page(
	'europe',
	2182,
	array( 'swift_problems', 'destinations', 'guarantees', 'problems_solved' ),
	$plan['proposed_builder_rows'],
	array(
		array( 'acf_fc_layout' => 'destinations', 'show_block' => 1 ),
		array( 'acf_fc_layout' => 'guarantees', 'show_block' => 0 ),
	)
);
platejka_cross_sync_assert( false === $second['would_change'] && array() === $second['add_builder_rows'], 'Planning the same manifest twice must be idempotent.' );

$disabled = platejka_cross_sync_merge_builder_rows(
	array(
		array( 'acf_fc_layout' => 'destinations', 'field_platejka_row_destinations_enabled_v1' => '0', 'custom' => 'keep' ),
		array( 'acf_fc_layout' => 'seo', 'field_platejka_row_seo_enabled_v1' => '0', 'custom' => 'untouched' ),
	),
	array( 'destinations' )
);
platejka_cross_sync_assert( 1 === $disabled[0]['field_platejka_row_destinations_enabled_v1'], 'Production-associated existing section must be enabled in raw ACF data.' );
platejka_cross_sync_assert( array( 'acf_fc_layout' => 'seo', 'field_platejka_row_seo_enabled_v1' => '0', 'custom' => 'untouched' ) === $disabled[1], 'Unrelated disabled section must remain byte-for-byte unchanged.' );
$disabled_plan = platejka_cross_sync_plan_page( 'test', 1, array( 'destinations' ), array( array( 'acf_fc_layout' => 'destinations', 'field_platejka_row_destinations_enabled_v1' => '0' ), array( 'acf_fc_layout' => 'seo', 'field_platejka_row_seo_enabled_v1' => '0' ) ), array(), false );
platejka_cross_sync_assert( array( 'destinations' ) === $disabled_plan['enable_builder_rows'], 'Dry-run must report only production-associated rows as enabled.' );
platejka_cross_sync_assert( false === $disabled_plan['current_builder_enabled'] && true === $disabled_plan['proposed_builder_enabled'] && true === $disabled_plan['would_change'], 'Dry-run must explicitly plan enabling the page builder.' );

$null_paths = platejka_cross_sync_null_meta_paths(
	array(
		array(
			'acf_fc_layout' => 'hero',
			'field_group'   => array(
				'field_image' => null,
				'field_items' => array( array( 'field_icon' => null ) ),
			),
		),
	),
	static fn( string $key ): string => array( 'field_group' => 'content', 'field_image' => 'image', 'field_items' => 'items', 'field_icon' => 'icon' )[ $key ] ?? ''
);
platejka_cross_sync_assert( array( 'platejka_sections_0_content_image', 'platejka_sections_0_content_items_0_icon' ) === $null_paths, 'Null raw ACF leaves must map to their exact postmeta paths.' );

WP_CLI::success( 'Cross-block synchronization checks passed.' );
