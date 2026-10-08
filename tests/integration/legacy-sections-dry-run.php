<?php
/** Read-only invariants and compact summary for the legacy sections dry-run. */

defined( 'ABSPATH' ) || exit;

$report   = \Platejka\Core\Cli\LegacySectionsMigrationCommand::preview();
$failures = array();
$assert   = static function ( bool $condition, string $message ) use ( &$failures ): void {
	if ( ! $condition ) {
		$failures[] = $message;
		WP_CLI::warning( $message );
	}
};

$assert( 'dry-run' === ( $report['mode'] ?? null ), 'Command is not in dry-run mode.' );
$assert( false === ( $report['writes']['performed'] ?? null ), 'Dry-run reported performed writes.' );
$marker = (int) get_option( \Platejka\Core\Acf\LegacyFieldVisibility::VERSION_OPTION, 0 );
$assert( in_array( $marker, array( 0, 1 ), true ), 'Migration marker has an unsupported value.' );
if ( 1 === $marker ) {
	$legacy_field = acf_get_field( 'field_66009b8844e4f' );
	$assert( false === apply_filters( 'acf/prepare_field', $legacy_field ), 'Migrated legacy services remain visible after the marker.' );
	$legacy_rows = \Platejka\Core\Cli\LegacySectionsMigrationCommand::normalizeRawValue( get_field( 'field_66009b8844e4f', 24, false ) );
	$assert( 5 === count( is_array( $legacy_rows ) ? $legacy_rows : array() ), 'Legacy homepage postmeta is no longer readable for rollback.' );
}

$home = null;
foreach ( (array) ( $report['pages'] ?? array() ) as $page ) {
	if ( 24 === (int) ( $page['id'] ?? 0 ) ) {
		$home = $page;
	}
	if ( in_array( 'shipments', (array) ( $page['source'] ?? array() ), true ) ) {
		$assert( '' === ( $page['template'] ?? null ) || 'default' === ( $page['template'] ?? null ), 'Shipments target a custom template on page ' . (int) $page['id'] . '.' );
	}
}

$assert( is_array( $home ), 'Homepage 24 is absent from the dry-run.' );
$assert(
	is_array( $home ) && array( 'hero-main', 'shipments', 'cases' ) === array_slice( (array) ( $home['target_sections'] ?? array() ), 0, 3 ),
	'Homepage does not begin with hero-main, shipments, cases.'
);
$assert( 0 === count( (array) ( $report['conflicts'] ?? array() ) ), 'Dry-run contains destination conflicts.' );

$global_shipments = null;
$global_cases     = null;
foreach ( (array) ( $report['writes']['planned'] ?? array() ) as $write ) {
	if ( 'platejka_section_defaults' === ( $write['post_id'] ?? null ) && 'field_platejka_defaults_shipments_v1' === ( $write['field'] ?? null ) ) {
		$global_shipments = $write;
	}
	if ( 'platejka_section_defaults' === ( $write['post_id'] ?? null ) && 'field_platejka_defaults_cases_v1' === ( $write['field'] ?? null ) ) {
		$global_cases = $write;
	}
	if ( 'field_platejka_sections_v1' !== ( $write['field'] ?? null ) || ! is_array( $write['target'] ?? null ) ) {
		continue;
	}
	foreach ( $write['target'] as $row ) {
		if ( 'shipments' === ( $row['acf_fc_layout'] ?? null ) && 'default' === ( $row['variant'] ?? null ) ) {
			$items = $row['overrides_default']['default']['items'] ?? array();
			foreach ( (array) $items as $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}
				$assert( array() === ( $item['tags'] ?? null ), 'Local shipment tags are not empty on page ' . (int) $write['post_id'] . '.' );
				$assert( '' === ( $item['link'] ?? null ), 'Local shipment link is not empty on page ' . (int) $write['post_id'] . '.' );
				$assert( ! array_key_exists( 'cardclass', $item ), 'Legacy cardclass reached page ' . (int) $write['post_id'] . '.' );
			}
		}
		if ( 'cases' === ( $row['acf_fc_layout'] ?? null ) ) {
			$items = $row['overrides']['cases']['items'] ?? array();
			foreach ( (array) $items as $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}
				$assert( '★★★★★' === ( $item['value'] ?? null ), 'Case rating is not five stars on page ' . (int) $write['post_id'] . '.' );
				$assert( ! array_key_exists( 'colors', $item ), 'Legacy colors reached page ' . (int) $write['post_id'] . '.' );
			}
		}
	}
}

$assert( 1 === $marker || is_array( $global_shipments ), 'Global shipments write is missing before migration.' );
$assert( 1 === $marker || is_array( $global_cases ), 'Global cases write is missing before migration.' );
if ( is_array( $global_shipments ) ) {
	$current_items = (array) ( $global_shipments['current']['main']['items'] ?? array() );
	$target_items  = (array) ( $global_shipments['target']['main']['items'] ?? array() );
	$assert( 5 === count( $target_items ), 'Homepage does not target five shipment cards.' );
	foreach ( $target_items as $index => $item ) {
		$assert( array_key_exists( 'price', $item ), 'Homepage shipment price is missing at position ' . ( $index + 1 ) . '.' );
		$assert( ( $current_items[ $index ]['image'] ?? null ) === ( $item['image'] ?? null ), 'Global shipment image changed at position ' . ( $index + 1 ) . '.' );
		$assert( ( $current_items[ $index ]['tags'] ?? null ) === ( $item['tags'] ?? null ), 'Global shipment tags changed at position ' . ( $index + 1 ) . '.' );
	}
}
if ( is_array( $global_cases ) ) {
	foreach ( (array) ( $global_cases['target']['items'] ?? array() ) as $index => $item ) {
		$assert( '★★★★★' === ( $item['value'] ?? null ), 'Global case rating is not five stars at position ' . ( $index + 1 ) . '.' );
	}
}

$summary = array(
	'mode'             => $report['mode'] ?? null,
	'writes_performed' => $report['writes']['performed'] ?? null,
	'marker'           => $marker,
	'totals'           => $report['totals'] ?? array(),
	'home'             => $home,
	'failure_count'    => count( $failures ),
);
WP_CLI::log( (string) wp_json_encode( $summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
if ( $failures ) {
	WP_CLI::halt( 1 );
}
WP_CLI::success( 'Legacy sections dry-run invariants passed.' );
