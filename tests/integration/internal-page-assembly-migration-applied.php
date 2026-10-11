<?php
/** Applied-state audit. Run only after explicit approval and --apply. */

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

$class = '\\Platejka\\Core\\Cli\\InternalPageAssemblyMigrationCommand';
$assert( class_exists( $class ), 'InternalPageAssemblyMigrationCommand is loaded.' );
$assert( 1 === (int) get_option( 'platejka_internal_page_assembly_version', 0 ), 'Internal-page assembly marker is missing.' );
$has_meaningful_value = static function ( $value ) use ( &$has_meaningful_value ): bool {
	if ( is_array( $value ) ) {
		foreach ( $value as $child ) {
			if ( $has_meaningful_value( $child ) ) {
				return true;
			}
		}
		return false;
	}
	return is_string( $value ) ? '' !== trim( $value ) : null !== $value && false !== $value;
};
$is_canonical = static function ( array $slugs ): bool {
	$expected = array( 'hero' );
	if ( in_array( 'shipments', $slugs, true ) ) {
		$expected[] = 'shipments';
	}
	if ( in_array( 'cases', $slugs, true ) ) {
		$expected[] = 'cases';
	}
	$expected[] = 'calculator';
	if ( in_array( 'process', $slugs, true ) ) {
		$expected[] = 'process';
	}
	$expected = array_merge( $expected, array( 'guarantees', 'review', 'destinations', 'seo', 'faq', 'call' ) );
	return $slugs === $expected;
};

$target_ids   = get_posts(
	array(
		'post_type'      => 'page',
		'post_status'    => array( 'publish', 'future', 'draft', 'pending', 'private' ),
		'fields'         => 'ids',
		'posts_per_page' => -1,
		'orderby'        => 'ID',
		'order'          => 'ASC',
	)
);
$targets = 0;
foreach ( $target_ids as $page_id_value ) {
	$page_id = (int) $page_id_value;
	$rows    = get_field( 'field_platejka_sections_v1', $page_id, false );
	$rows    = is_array( $rows ) && class_exists( $class ) ? $class::normalizeRawValue( $rows ) : array();
	$slugs   = array_column( $rows, 'acf_fc_layout' );
	if ( ! $is_canonical( $slugs ) ) {
		continue;
	}
	++$targets;
	$assert( 'hero' === ( $slugs[0] ?? null ) && 'call' === end( $slugs ), 'Canonical boundary order differs on page ' . $page_id . '.' );
	foreach ( array( 'calculator', 'guarantees', 'review', 'destinations', 'seo', 'call' ) as $slug ) {
		$index = array_search( $slug, $slugs, true );
		$assert( false !== $index && ! $has_meaningful_value( $rows[ $index ]['overrides'] ?? array() ), 'Shared section does not inherit on page ' . $page_id . ': ' . $slug . '.' );
	}
	$process_index = array_search( 'process', $slugs, true );
	if ( in_array( $page_id, array( 2098, 2109 ), true ) ) {
		$assert( false !== $process_index && ! empty( $rows[ $process_index ]['overrides']['process']['items'] ), 'Local Process data is missing on page ' . $page_id . '.' );
	} else {
		$assert( false === $process_index, 'Unexpected Process row exists on page ' . $page_id . '.' );
	}
}
$assert( 135 === $targets, 'Applied target count is not 135.' );

$cross_values = array();
foreach ( $target_ids as $page_id_value ) {
	$page_id                   = (int) $page_id_value;
	$cross_values[ $page_id ] = get_field( 'field_67924d166072f', $page_id, false );
}
$cross_hash = hash( 'sha256', (string) wp_json_encode( $cross_values, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
$assert( '7317202f9c7e3d6235bf2f85c8ff6418fa875402fcca6ed997ec4cb51bd234c0' === $cross_hash, 'Legacy cross-block postmeta changed after migration.' );

if ( class_exists( $class ) ) {
	$repeat = $class::preview();
	$assert( true === ( $repeat['already_migrated'] ?? null ) && array() === ( $repeat['writes']['planned'] ?? null ), 'Second preview is not idempotent.' );
}

WP_CLI::log( (string) wp_json_encode( array( 'checks' => $checks, 'targets' => $targets, 'failure_count' => count( $failures ) ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
if ( $failures ) {
	WP_CLI::halt( 1 );
}
WP_CLI::success( 'Applied internal page assembly passed the independent audit.' );
