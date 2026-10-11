<?php

$root     = dirname( __DIR__, 2 );
$failures = array();
$assert   = static function ( bool $condition, string $message ) use ( &$failures ): void {
	if ( ! $condition ) {
		$failures[] = $message;
	}
};

$defaults = json_decode( (string) file_get_contents( $root . '/plugins/platejka-core/acf-json/group_platejka_section_defaults_v1.json' ), true );
$builder  = json_decode( (string) file_get_contents( $root . '/plugins/platejka-core/acf-json/group_platejka_page_builder_v1.json' ), true );
$fields   = array_column( $defaults['fields'] ?? array(), null, 'name' );
$variants = array_column( $fields['shipments']['sub_fields'] ?? array(), null, 'name' );
$main     = array_column( $variants['main']['sub_fields'] ?? array(), null, 'name' );
$items    = array_column( $main['items']['sub_fields'] ?? array(), null, 'name' );
$assert( 'text' === ( $items['price']['type'] ?? null ), 'Main shipment price field is missing.' );

$section_field = array_column( $builder['fields'] ?? array(), null, 'name' )['platejka_sections'] ?? array();
$layouts       = array_column( array_values( $section_field['layouts'] ?? array() ), null, 'name' );
$hero_fields   = array_column( $layouts['hero']['sub_fields'] ?? array(), null, 'name' );
$assert( 'true_false' === ( $hero_fields['shared_content_only']['type'] ?? null ), 'Internal Hero shared-only inheritance control is missing.' );
$assert( 0 === ( $hero_fields['shared_content_only']['default_value'] ?? null ), 'Shared-only inheritance must be opt-in.' );
foreach ( array( 'shipments', 'cases' ) as $layout_name ) {
	$layout_fields = array_column( $layouts[ $layout_name ]['sub_fields'] ?? array(), null, 'name' );
	$assert( array( 'inherit' => 'Наследовать', 'hide' => 'Скрыть' ) === ( $layout_fields['description_mode']['choices'] ?? null ), 'Description mode is missing for ' . $layout_name . '.' );
}

if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}

echo "Legacy section schema contract passed.\n";
