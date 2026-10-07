<?php
/** Focused read-only contract for the new ACF section-builder schemas. */

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

$json_path = WP_PLUGIN_DIR . '/platejka-core/acf-json';
$walk_fields = static function ( array $fields ) use ( &$walk_fields ): array {
	$found = array();
	foreach ( $fields as $field ) {
		if ( ! is_array( $field ) ) {
			continue;
		}
		$found[] = $field;
		$found = array_merge( $found, $walk_fields( $field['sub_fields'] ?? array() ) );
		foreach ( (array) ( $field['layouts'] ?? array() ) as $layout ) {
			$found = array_merge( $found, $walk_fields( $layout['sub_fields'] ?? array() ) );
		}
	}
	return $found;
};

$schemas = array();
foreach (
	array(
		'group_platejka_section_defaults_v1' => 'Стандартный контент секций',
		'group_platejka_page_builder_v1'     => 'Конструктор секций страницы',
	) as $key => $title
) {
	$path = $json_path . '/' . $key . '.json';
	$assert( is_file( $path ), 'Schema file exists: ' . $key );
	$schema = is_file( $path ) ? json_decode( file_get_contents( $path ), true ) : null;
	$assert( is_array( $schema ) && JSON_ERROR_NONE === json_last_error(), 'Schema parses as JSON: ' . $key );
	if ( ! is_array( $schema ) ) {
		continue;
	}
	$schemas[ $key ] = $schema;
	$assert( $key === ( $schema['key'] ?? null ) && $title === ( $schema['title'] ?? null ), 'Schema key/title is stable: ' . $key );
	$assert( false === acf_get_field_group_post( $key ), 'Schema has no DB-backed field-group post: ' . $key );
	$local = acf_get_local_field_group( $key );
	$assert( is_array( $local ) && 'json' === ( $local['local'] ?? null ) && $path === ( $local['local_file'] ?? null ), 'Schema is loaded from canonical Local JSON: ' . $key );
}

$supported_types = array_keys( (array) acf_get_field_types_info() );
$field_keys = array();
foreach ( $schemas as $schema ) {
	foreach ( $walk_fields( $schema['fields'] ?? array() ) as $field ) {
		$assert( in_array( $field['type'] ?? '', $supported_types, true ), 'Field type is installed: ' . ( $field['key'] ?? 'missing-key' ) );
		$assert( ! array_key_exists( 'value', $field ), 'Schema excludes runtime field values: ' . ( $field['key'] ?? 'missing-key' ) );
		$field_keys[] = $field['key'] ?? '';
	}
}
$assert( ! in_array( '', $field_keys, true ) && count( $field_keys ) === count( array_unique( $field_keys ) ), 'Every new field key is present and globally unique.' );

$defaults = $schemas['group_platejka_section_defaults_v1'] ?? array();
$assert(
	array( array( array( 'param' => 'options_page', 'operator' => '==', 'value' => 'platejka-section-defaults' ) ) ) === ( $defaults['location'] ?? null ),
	'Defaults group targets only the new options page.'
);

$builder = $schemas['group_platejka_page_builder_v1'] ?? array();
$assert(
	array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'page' ) ) ) === ( $builder['location'] ?? null ),
	'Builder group targets every page.'
);
$assert( 'acf_after_title' === ( $builder['position'] ?? null ), 'Builder group appears immediately after the page title.' );

$builder_toggle = null;
$sections_field = null;
foreach ( (array) ( $builder['fields'] ?? array() ) as $field ) {
	if ( 'platejka_page_builder' === ( $field['name'] ?? null ) ) {
		$builder_toggle = $field;
	}
	if ( 'platejka_sections' === ( $field['name'] ?? null ) ) {
		$sections_field = $field;
	}
}
$assert( 'field_platejka_page_builder_v1' === ( $builder_toggle['key'] ?? null ) && 'true_false' === ( $builder_toggle['type'] ?? null ), 'Builder toggle uses its stable key and boolean type.' );
$assert( 'field_platejka_sections_v1' === ( $sections_field['key'] ?? null ) && 'flexible_content' === ( $sections_field['type'] ?? null ), 'Sections field uses its stable key and flexible-content type.' );

$expected_layouts = array( 'hero-main', 'about', 'shipments', 'guarantees', 'documents', 'compliance', 'review-main', 'work', 'calculator', 'with-us', 'destinations', 'seo', 'problems', 'serves', 'cases', 'table', 'faq', 'call', 'hero', 'protection', 'review' );
$layouts = array_values( (array) ( $sections_field['layouts'] ?? array() ) );
$assert( $expected_layouts === array_column( $layouts, 'name' ), 'Builder keeps the original layouts and appends the three approved internal-page layouts.' );
foreach ( $layouts as $layout ) {
	$by_name = array();
	foreach ( (array) ( $layout['sub_fields'] ?? array() ) as $field ) {
		$by_name[ $field['name'] ?? '' ] = $field;
	}
	$assert( 'true_false' === ( $by_name['enabled']['type'] ?? null ) && 0 === ( $by_name['enabled']['default_value'] ?? null ), 'Layout defaults disabled: ' . ( $layout['name'] ?? '' ) );
	$assert( 'text' === ( $by_name['anchor']['type'] ?? null ), 'Layout provides an anchor: ' . ( $layout['name'] ?? '' ) );
	if ( in_array( $layout['name'] ?? '', array( 'shipments', 'guarantees', 'documents' ), true ) ) {
		$assert( array( 'inherit' => 'Наследовать', 'main' => 'Главная', 'default' => 'Внутренняя' ) === ( $by_name['variant']['choices'] ?? null ), 'Variant choices are complete: ' . $layout['name'] );
		$assert( ! empty( $by_name['overrides_main']['conditional_logic'] ), 'Main overrides are conditionally visible: ' . $layout['name'] );
		$assert( ! empty( $by_name['overrides_default']['conditional_logic'] ), 'Default overrides are conditionally visible: ' . $layout['name'] );
	}
}

$layouts_by_name = array_column( $layouts, null, 'name' );
foreach (
	array(
		'hero'       => 'field_platejka_defaults_hero_v1',
		'protection' => 'field_platejka_defaults_protection_v1',
		'review'     => 'field_platejka_defaults_review_main_v1',
	) as $layout_name => $clone_key
) {
	$layout_fields = array_column( (array) ( $layouts_by_name[ $layout_name ]['sub_fields'] ?? array() ), null, 'name' );
	$assert( 0 === ( $layout_fields['enabled']['default_value'] ?? null ), 'New layout defaults disabled: ' . $layout_name );
	$assert( 'text' === ( $layout_fields['anchor']['type'] ?? null ), 'New layout provides an anchor: ' . $layout_name );
	$assert( array( $clone_key ) === ( $layout_fields['overrides']['clone'] ?? null ), 'New layout clones the approved canonical group: ' . $layout_name );
}

foreach ( array_merge( $walk_fields( $defaults['fields'] ?? array() ), $walk_fields( $builder['fields'] ?? array() ) ) as $field ) {
	if ( 'image' === ( $field['type'] ?? null ) ) {
		$assert( 'id' === ( $field['return_format'] ?? null ), 'Editorial image returns an attachment ID: ' . ( $field['key'] ?? '' ) );
	}
	if ( 'post_object' === ( $field['type'] ?? null ) ) {
		$assert( array( 'wpcf7_contact_form' ) === ( $field['post_type'] ?? null ) && 'id' === ( $field['return_format'] ?? null ), 'Form selector returns a CF7 post ID: ' . ( $field['key'] ?? '' ) );
	}
}

$options_page = function_exists( 'acf_get_options_page' ) ? acf_get_options_page( 'platejka-section-defaults' ) : false;
$assert( is_array( $options_page ), 'The Сквозные секции options page is registered.' );
$assert( 'platejka_section_defaults' === ( $options_page['post_id'] ?? null ), 'Options page uses the stable post ID.' );
$assert( 'edit_pages' === ( $options_page['capability'] ?? null ), 'Options page requires edit_pages.' );

WP_CLI::log( sprintf( 'ACF section-builder schema: %d checks, %d failures.', $checks, count( $failures ) ) );
if ( $failures ) {
	WP_CLI::halt( 1 );
}
WP_CLI::success( 'ACF section-builder schema contract passed.' );
