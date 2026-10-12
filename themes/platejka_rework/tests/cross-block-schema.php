<?php
/** Cross-block ACF schema regression checks. */

$theme_root = dirname( __DIR__ );
require_once $theme_root . '/inc/cross-blocks.php';
if ( is_file( $theme_root . '/inc/cross-block-schema.php' ) ) {
	require_once $theme_root . '/inc/cross-block-schema.php';
}

function platejka_cross_block_schema_assert( bool $condition, string $message ): void {
	if ( ! $condition ) {
		WP_CLI::error( $message );
	}
}

platejka_cross_block_schema_assert( function_exists( 'platejka_extend_cross_blocks_field' ), 'Cross-block schema extension is missing.' );

$group = acf_get_field_group( 'group_67923f17e9f73' );
$field = acf_get_field( 'field_67924d166072f' );
platejka_cross_block_schema_assert( is_array( $group ) && is_array( $field ), 'Existing ACF group or field is unavailable.' );

$layouts = array();
foreach ( $field['layouts'] ?? array() as $layout ) {
	if ( is_array( $layout ) && ! empty( $layout['name'] ) ) {
		$layouts[ $layout['name'] ] = $layout;
	}
}

platejka_cross_block_schema_assert( array_keys( platejka_cross_block_map() ) === array_keys( $layouts ), 'ACF choices must equal the approved layout allowlist.' );
foreach ( $layouts as $layout ) {
	platejka_cross_block_schema_assert( str_starts_with( (string) $layout['label'], 'Кросс-блок — ' ), 'Layout label is missing the cross-block prefix: ' . $layout['name'] );
}
foreach ( array( 'work', 'reviews', 'tariff', 'transfer_calculator' ) as $excluded ) {
	platejka_cross_block_schema_assert( ! isset( $layouts[ $excluded ] ), 'Excluded layout remains available: ' . $excluded );
}

$destination_fields = array_column( $layouts['destinations']['sub_fields'] ?? array(), 'name' );
platejka_cross_block_schema_assert( in_array( 'vklyuchit_blok', $destination_fields, true ), 'Destination visibility field was removed.' );
platejka_cross_block_schema_assert( in_array( 'destination_items', $destination_fields, true ), 'Destination content fields were not added.' );

$metrics_fields = array_column( $layouts['company_metrics']['sub_fields'] ?? array(), 'name' );
platejka_cross_block_schema_assert( array( 'show_block', 'title', 'metrics', 'cta' ) === $metrics_fields, 'Company metrics field structure changed.' );

$expected_location = array(
	array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'default' ) ),
	array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'page-cryptocurrency.php' ) ),
);
platejka_cross_block_schema_assert( $expected_location === ( $group['location'] ?? array() ), 'Cross-block group location rules changed.' );
platejka_cross_block_schema_assert( false === has_action( 'acf/init', 'platejka_seed_cross_block_defaults' ), 'Schema must not register database seed writes.' );

WP_CLI::success( 'Cross-block schema checks passed.' );
