<?php
/** Focused contract for the immutable default-page section seed. */

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

$class = 'Platejka\\Core\\Acf\\DefaultPageSeed';
$assert( class_exists( $class ), 'DefaultPageSeed is available.' );
if ( ! class_exists( $class ) ) {
	WP_CLI::halt( 1 );
	return;
}

$seed = $class::get();
$assert( 1 === ( $seed['schema_version'] ?? null ), 'Default-page seed uses schema version 1.' );
$assert( array( 'hero', 'protection' ) === array_keys( $seed['sections'] ?? array() ), 'Only hero and protection receive new canonical defaults.' );

$expected_order = array( 'hero', 'about', 'shipments', 'guarantees', 'documents', 'protection', 'review', 'work', 'problems', 'calculator', 'seo', 'faq', 'call' );
$rows           = $seed['page_rows'] ?? array();
$assert( 13 === count( $rows ), 'Default-page seed contains exactly 13 rows.' );
$assert( $expected_order === array_column( $rows, 'acf_fc_layout' ), 'Default-page seed preserves the approved section order.' );
$assert( 13 === count( array_filter( array_column( $rows, 'enabled' ) ) ), 'Every migrated row is explicitly enabled.' );

$variants = array();
foreach ( $rows as $row ) {
	if ( isset( $row['variant'] ) ) {
		$variants[ $row['acf_fc_layout'] ] = $row['variant'];
	}
}
$assert(
	array( 'shipments' => 'default', 'guarantees' => 'default', 'documents' => 'default' ) === $variants,
	'Only the three variant sections explicitly select default.'
);

$seed_json = (string) wp_json_encode( $seed['sections'] ?? array(), JSON_UNESCAPED_SLASHES );
preg_match_all( '~theme://[^"]+~', $seed_json, $media_matches );
$assert( 7 === count( array_unique( $media_matches[0] ?? array() ) ) && ! str_contains( $seed_json, '.svg' ), 'Default-page seed imports seven editable raster images and leaves decorative SVGs in the theme.' );

WP_CLI::log( sprintf( 'Default-page seed: %d checks, %d failures.', $checks, count( $failures ) ) );
if ( $failures ) {
	WP_CLI::halt( 1 );
}
WP_CLI::success( 'Default-page seed contract passed.' );
