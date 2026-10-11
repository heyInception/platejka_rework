<?php
/** Read-only integration contract for internal-page assembly migration. */

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
if ( ! class_exists( $class ) ) {
	WP_CLI::halt( 1 );
	return;
}

$snapshot = static function (): string {
	$page_ids = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => array( 'publish', 'future', 'draft', 'pending', 'private' ),
			'fields'         => 'ids',
			'posts_per_page' => -1,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		)
	);
	$state = array( 'marker' => get_option( 'platejka_internal_page_assembly_version', 0 ), 'pages' => array() );
	foreach ( $page_ids as $page_id_value ) {
		$page_id = (int) $page_id_value;
		$state['pages'][ $page_id ] = array(
			'builder'     => get_field( 'field_platejka_page_builder_v1', $page_id, false ),
			'rows'        => get_field( 'field_platejka_sections_v1', $page_id, false ),
			'template'    => get_post_meta( $page_id, '_wp_page_template', true ),
			'process'     => get_field( 'field_6790b8aa79094', $page_id, false ),
			'cross_blocks'=> get_field( 'field_67924d166072f', $page_id, false ),
		);
	}
	return hash( 'sha256', (string) wp_json_encode( $state, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
};
$cross_block_hash = static function (): string {
	$page_ids = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => array( 'publish', 'future', 'draft', 'pending', 'private' ),
			'fields'         => 'ids',
			'posts_per_page' => -1,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		)
	);
	$values = array();
	foreach ( $page_ids as $page_id_value ) {
		$page_id            = (int) $page_id_value;
		$values[ $page_id ] = get_field( 'field_67924d166072f', $page_id, false );
	}
	return hash( 'sha256', (string) wp_json_encode( $values, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
};

$before = $snapshot();
$report = $class::preview();
$after  = $snapshot();
$cli_report = $class::summarizeReport( $report );

foreach ( array( 'mode', 'already_migrated', 'totals', 'pages', 'excluded', 'conflicts', 'writes' ) as $key ) {
	$assert( array_key_exists( $key, $report ), 'Preview exposes report key: ' . $key . '.' );
}
$assert( 'dry-run' === ( $report['mode'] ?? null ), 'Preview reports dry-run mode.' );
$assert( false === ( $report['writes']['performed'] ?? null ), 'Preview reports no performed writes.' );
$assert( $before === $after, 'Preview changed the marker or page content.' );
$assert( ! array_key_exists( 'target_rows', $cli_report['pages'][0] ?? array() ), 'CLI summary duplicates full target rows inside pages.' );
foreach ( (array) ( $cli_report['writes']['planned'] ?? array() ) as $write ) {
	$assert( ! array_key_exists( 'current', $write ) && ! array_key_exists( 'target', $write ), 'CLI summary exposes a full ACF write payload.' );
}

$already_migrated = true === ( $report['already_migrated'] ?? null );
$planned          = (array) ( $report['writes']['planned'] ?? array() );
if ( $already_migrated ) {
	$assert( array() === $planned, 'Completed migration preview plans no writes.' );
} else {
	$assert( 135 === ( $report['totals']['target_pages'] ?? null ), 'Preview targets exactly 135 compact internal pages.' );
	$assert( 0 === ( $report['totals']['conflicts'] ?? null ), 'Preview has no unresolved page conflicts.' );
}

$pages = array_column( (array) ( $report['pages'] ?? array() ), null, 'id' );
$process_pages = array();
foreach ( $pages as $page_id => $page ) {
	$target_rows = (array) ( $page['target_rows'] ?? array() );
	$slugs       = array_column( $target_rows, 'acf_fc_layout' );
	if ( in_array( 'process', $slugs, true ) ) {
		$process_pages[] = (int) $page_id;
		$process_index = array_search( 'process', $slugs, true );
		$legacy_raw    = get_field( 'field_6790b8aa79094', (int) $page_id, false );
		$legacy_mapped = $class::mapLegacyProcess( $class::normalizeRawValue( $legacy_raw ) );
		$assert( $legacy_mapped === ( $target_rows[ $process_index ]['overrides']['process'] ?? null ), 'Local Process mapping differs on page ' . $page_id . '.' );
	}
	foreach ( $target_rows as $row ) {
		$slug = (string) ( $row['acf_fc_layout'] ?? '' );
		if ( in_array( $slug, array( 'calculator', 'guarantees', 'review', 'destinations', 'seo', 'call' ), true ) ) {
			$assert( array() === ( $row['overrides'] ?? null ), 'Shared row has a local override on page ' . $page_id . ': ' . $slug . '.' );
		}
	}
}
sort( $process_pages, SORT_NUMERIC );
$assert( array( 2098, 2109 ) === $process_pages, 'Process maps only to pages 2098 and 2109.' );

$excluded = array_column( (array) ( $report['excluded'] ?? array() ), null, 'id' );
$assert( isset( $excluded[1873] ), 'Expanded China page 1873 is explicitly excluded.' );
foreach ( array( 1469, 2279, 3075 ) as $page_id ) {
	$assert( isset( $excluded[ $page_id ] ), 'Non-compact page is explicitly excluded: ' . $page_id . '.' );
	$assert( ! in_array( $page_id, array_map( static fn( array $write ): int => (int) ( $write['post_id'] ?? 0 ), $planned ), true ), 'Excluded page has a planned write: ' . $page_id . '.' );
}

$source = (string) file_get_contents( WP_PLUGIN_DIR . '/platejka-core/src/Cli/InternalPageAssemblyMigrationCommand.php' );
$assert( ! str_contains( $source, 'skvoznye_bloki' ) && ! str_contains( $source, 'field_67924d166072f' ), 'Product migration code accesses legacy cross-block data.' );
$verify_position = strpos( $source, 'self::verifyWrites' );
$marker_position = strpos( $source, "update_option( self::VERSION_OPTION" );
$assert( false !== $verify_position && false !== $marker_position && $verify_position < $marker_position, 'Migration verifies writes before saving its marker.' );
$bootstrap = (string) file_get_contents( WP_PLUGIN_DIR . '/platejka-core/platejka-core.php' );
$assert( str_contains( $bootstrap, 'migrate-internal-pages' ) && str_contains( $bootstrap, 'InternalPageAssemblyMigrationCommand.php' ), 'Plugin registers the internal-page migration CLI command.' );

$sequence_counts = array_count_values(
	array_map(
		static fn( array $page ): string => implode( ' > ', (array) ( $page['current_sections'] ?? array() ) ),
		(array) ( $report['pages'] ?? array() )
	)
);
ksort( $sequence_counts, SORT_STRING );
$excluded_summary = array_map(
	static fn( array $page ): array => array(
		'id'       => (int) ( $page['id'] ?? 0 ),
		'title'    => (string) ( $page['title'] ?? '' ),
		'reason'   => (string) ( $page['reason'] ?? '' ),
		'sections' => (array) ( $page['current_sections'] ?? array() ),
	),
	array_values( $excluded )
);
WP_CLI::log( (string) wp_json_encode( array( 'checks' => $checks, 'totals' => $report['totals'] ?? array(), 'sequence_counts' => $sequence_counts, 'process_pages' => $process_pages, 'excluded' => $excluded_summary, 'cross_block_hash' => $cross_block_hash(), 'failure_count' => count( $failures ) ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
if ( $failures ) {
	WP_CLI::halt( 1 );
}
WP_CLI::success( 'Internal page assembly preview is read-only and matches the approved scope.' );
