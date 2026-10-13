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
	'cross-exchange'         => 'Обмен валют',
	'cross-company-metrics'  => 'Показатели компании',
	'cross-external-ratings' => 'Рейтинги на внешних площадках',
	'cross-key-facts'        => 'Конкретные цифры и условия',
	'cross-transfer-goals'   => 'Частые цели переводов',
	'cross-swift-problems'   => 'Почему прямой SWIFT не работает',
	'cross-audience'         => 'Кому подходит',
);

platejka_cross_block_schema_assert( function_exists( 'platejka_extend_section_builder_with_cross_blocks' ), 'Section-builder cross-block extension is missing.' );
platejka_cross_block_schema_assert( function_exists( 'platejka_cross_block_default_groups' ), 'Canonical cross-block default groups are missing.' );
platejka_cross_block_schema_assert( function_exists( 'platejka_cross_block_default_field_key' ), 'Canonical cross-block default key helper is missing.' );
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

foreach ( $expected as $name => $label ) {
	platejka_cross_block_schema_assert( isset( $layouts[ $name ] ), 'Builder layout is missing: ' . $name );
	platejka_cross_block_schema_assert( 'Кросс-блок — ' . $label === (string) ( $layouts[ $name ]['label'] ?? '' ), 'Builder label is not exact: ' . $name );
	$fields = array_column( $layouts[ $name ]['sub_fields'] ?? array(), null, 'name' );
	platejka_cross_block_schema_assert( array( 'enabled', 'anchor', 'overrides' ) === array_keys( $fields ), 'Builder layout must expose enabled, anchor, and overrides: ' . $name );
	platejka_cross_block_schema_assert( array( platejka_cross_block_default_field_key( $name ) ) === ( $fields['overrides']['clone'] ?? null ), 'Builder clone points at the wrong defaults group: ' . $name );
	platejka_cross_block_schema_assert( 'group' === ( $fields['overrides']['display'] ?? null ) && 0 === ( $fields['overrides']['prefix_name'] ?? null ), 'Builder clone must use unprefixed group display: ' . $name );
}

$default_groups = platejka_cross_block_default_groups();
platejka_cross_block_schema_assert( array_keys( $expected ) === array_keys( $default_groups ), 'Canonical defaults expose exactly the seven approved cross-blocks.' );
foreach ( $default_groups as $slug => $field ) {
	platejka_cross_block_schema_assert( platejka_cross_block_default_field_key( $slug ) === ( $field['key'] ?? null ), 'Default group uses the stable key: ' . $slug );
	platejka_cross_block_schema_assert( 'group' === ( $field['type'] ?? null ) && ! empty( $field['sub_fields'] ), 'Default group contains editable content fields: ' . $slug );
	platejka_cross_block_schema_assert( ! in_array( 'show_block', array_column( $field['sub_fields'], 'name' ), true ), 'Default group must not expose legacy visibility: ' . $slug );
}

$group = acf_get_field_group( 'group_67923f17e9f73' );
$expected_location = array(
	array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'default' ) ),
	array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'page-cryptocurrency.php' ) ),
);
platejka_cross_block_schema_assert( is_array( $group ) && $expected_location === ( $group['location'] ?? array() ), 'Legacy cross-block group location rules changed.' );

WP_CLI::success( 'Cross-block builder schema checks passed.' );
