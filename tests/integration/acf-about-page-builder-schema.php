<?php
/** Contract for the page-22-only About section builder schema. */

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

$about_path = WP_CONTENT_DIR . '/plugins/platejka-core/acf-json/group_platejka_about_page_builder_v1.json';
$generic_path = WP_CONTENT_DIR . '/plugins/platejka-core/acf-json/group_platejka_page_builder_v1.json';
$assert( is_readable( $about_path ), 'About builder Local JSON exists.' );
$about = is_readable( $about_path ) ? json_decode( (string) file_get_contents( $about_path ), true ) : array();
$generic = json_decode( (string) file_get_contents( $generic_path ), true );
$assert( is_array( $about ), 'About builder Local JSON is valid.' );

$about_rules = $about['location'][0] ?? array();
$assert( array( array( 'param' => 'post', 'operator' => '==', 'value' => '22' ) ) === $about_rules, 'About builder is visible only on post ID 22.' );
$generic_rules = $generic['location'][0] ?? array();
$assert( in_array( array( 'param' => 'post', 'operator' => '!=', 'value' => '22' ), $generic_rules, true ), 'Generic builder explicitly excludes post ID 22.' );

$fields = $about['fields'] ?? array();
$assert( array( 'platejka_about_page_builder', 'platejka_about_sections' ) === array_column( $fields, 'name' ), 'About group owns dedicated switch and rows fields.' );
$flex = $fields[1] ?? array();
$layouts = $flex['layouts'] ?? array();
$expected = array( 'about-hero', 'location', 'review-main', 'infrastructure', 'financial', 'employees', 'exhibitions', 'developing', 'call-about' );
$assert( $expected === array_column( array_values( $layouts ), 'name' ), 'About builder exposes exactly the approved nine layouts in order.' );

$all_keys = array();
$types = array_keys( (array) acf_get_field_types_info() );
$walk = static function ( array $field ) use ( &$walk, &$all_keys, $types, $assert ): void {
	if ( isset( $field['key'] ) ) {
		$assert( ! isset( $all_keys[ $field['key'] ] ), 'ACF field/layout key is unique: ' . $field['key'] );
		$all_keys[ $field['key'] ] = true;
	}
	if ( isset( $field['type'] ) ) {
		$assert( in_array( $field['type'], $types, true ), 'Installed ACF supports field type: ' . $field['type'] );
	}
	foreach ( $field['sub_fields'] ?? array() as $sub_field ) {
		$walk( $sub_field );
	}
};

$review_clones = array();
foreach ( $layouts as $layout ) {
	$assert( '1' !== (string) ( $layout['sub_fields'][0]['default_value'] ?? '' ), 'New ' . ( $layout['name'] ?? '' ) . ' row defaults disabled.' );
	$assert( 'about-hero' !== ( $layout['name'] ?? '' ) || 1 === (int) ( $layout['max'] ?? 0 ), 'About hero is limited to one row.' );
	$walk( $layout );
	foreach ( $layout['sub_fields'] ?? array() as $sub_field ) {
		if ( 'clone' === ( $sub_field['type'] ?? '' ) ) {
			$review_clones[ $layout['name'] ] = $sub_field['clone'] ?? array();
		}
	}
}
$assert( array( 'review-main' => array( 'field_platejka_defaults_review_main_v1' ) ) === $review_clones, 'Only review-main clones the canonical shared group.' );

WP_CLI::log( sprintf( 'About ACF schema: %d checks, %d failures.', $checks, count( $failures ) ) );
if ( $failures ) {
	WP_CLI::halt( 1 );
}
WP_CLI::success( 'About ACF schema contract passed.' );
