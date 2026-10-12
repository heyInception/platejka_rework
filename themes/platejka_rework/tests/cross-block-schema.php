<?php
/** Cross-block builder ownership regression checks. */

$theme_root = dirname( __DIR__ );
require_once $theme_root . '/inc/cross-blocks.php';
require_once $theme_root . '/inc/cross-block-schema.php';

function platejka_cross_block_schema_assert( bool $condition, string $message ): void {
	if ( ! $condition ) {
		WP_CLI::error( $message );
	}
}

$expected = array(
	'cross-exchange',
	'cross-company-metrics',
	'cross-external-ratings',
	'cross-key-facts',
	'cross-transfer-goals',
	'cross-swift-problems',
	'cross-audience',
);

platejka_cross_block_schema_assert( function_exists( 'platejka_extend_section_builder_with_cross_blocks' ), 'Section-builder cross-block extension is missing.' );
platejka_cross_block_schema_assert( false === has_filter( 'acf/load_field_group', 'platejka_extend_cross_blocks_group' ), 'Legacy cross-block group locations are still extended.' );
platejka_cross_block_schema_assert( false !== has_filter( 'acf/prepare_field/key=field_67924d166072f', 'platejka_hide_legacy_cross_blocks_field' ), 'Legacy cross-block editor field is not hidden.' );

$builder = acf_get_field( 'field_platejka_sections_v1' );
platejka_cross_block_schema_assert( is_array( $builder ), 'Page section builder field is unavailable.' );

$layouts = array();
foreach ( $builder['layouts'] ?? array() as $layout ) {
	if ( is_array( $layout ) && ! empty( $layout['name'] ) ) {
		$layouts[ $layout['name'] ] = $layout;
	}
}

foreach ( $expected as $name ) {
	platejka_cross_block_schema_assert( isset( $layouts[ $name ] ), 'Builder layout is missing: ' . $name );
	platejka_cross_block_schema_assert( str_starts_with( (string) $layouts[ $name ]['label'], 'Кросс-блок — ' ), 'Builder label is missing the cross-block prefix: ' . $name );
	platejka_cross_block_schema_assert( array( 'enabled', 'anchor' ) === array_column( $layouts[ $name ]['sub_fields'] ?? array(), 'name' ), 'Builder layout must contain only enabled and anchor controls: ' . $name );
}

$group = acf_get_field_group( 'group_67923f17e9f73' );
$expected_location = array(
	array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'default' ) ),
	array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'page-cryptocurrency.php' ) ),
);
platejka_cross_block_schema_assert( is_array( $group ) && $expected_location === ( $group['location'] ?? array() ), 'Legacy cross-block group location rules changed.' );

WP_CLI::success( 'Cross-block builder schema checks passed.' );
