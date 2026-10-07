<?php
/** Contract for the immutable About-page migration seed. */

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

$class = 'Platejka\\Core\\Acf\\AboutPageSeed';
$assert( class_exists( $class ), 'AboutPageSeed is available.' );
$seed = class_exists( $class ) ? $class::get() : array();
$assert( 1 === ( $seed['schema_version'] ?? null ), 'About seed uses schema version 1.' );
$expected_sections = array( 'about-hero', 'location', 'infrastructure', 'financial', 'employees', 'exhibitions', 'developing', 'call-about' );
$assert( $expected_sections === array_keys( $seed['sections'] ?? array() ), 'Only eight page-local sections receive seed content.' );
$expected_rows = array( 'about-hero', 'location', 'review-main', 'infrastructure', 'financial', 'employees', 'exhibitions', 'developing', 'call-about' );
$rows = $seed['page_rows'] ?? array();
$assert( $expected_rows === array_column( $rows, 'acf_fc_layout' ), 'About seed preserves the approved nine-row order.' );
$assert( 9 === count( array_filter( array_column( $rows, 'enabled' ) ) ), 'Every migrated About row is explicitly enabled.' );

$json = (string) wp_json_encode( $seed['sections'] ?? array(), JSON_UNESCAPED_SLASHES );
preg_match_all( '~theme://[^" ]+~', $json, $matches );
$sources = array_unique( $matches[0] ?? array() );
$assert( ! str_contains( $json, '.svg' ), 'Decorative SVG assets stay in the theme.' );
$assert( count( $sources ) > 0, 'Editable raster media sources are declared for migration.' );
$assert( count( $sources ) === count( array_filter( $sources, static fn( string $source ): bool => (bool) preg_match( '/\.(?:png|jpe?g|webp)$/i', $source ) ) ), 'Every migratable About media source is a supported raster image.' );

WP_CLI::log( sprintf( 'About seed: %d checks, %d failures.', $checks, count( $failures ) ) );
if ( $failures ) {
	WP_CLI::halt( 1 );
}
WP_CLI::success( 'About seed contract passed.' );
