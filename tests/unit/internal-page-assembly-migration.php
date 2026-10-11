<?php

defined( 'ABSPATH' ) || define( 'ABSPATH', dirname( __DIR__, 2 ) . DIRECTORY_SEPARATOR );

$migration_file = ABSPATH . 'plugins/platejka-core/src/Cli/InternalPageAssemblyMigrationCommand.php';
if ( is_file( $migration_file ) ) {
	require_once $migration_file;
}

$class    = '\\Platejka\\Core\\Cli\\InternalPageAssemblyMigrationCommand';
$failures = array();
$assert   = static function ( bool $condition, string $message ) use ( &$failures ): void {
	if ( ! $condition ) {
		$failures[] = $message;
	}
};

$assert( class_exists( $class ), 'InternalPageAssemblyMigrationCommand is missing.' );
if ( class_exists( $class ) ) {
	foreach ( array( 'publish', 'future', 'draft', 'pending', 'private' ) as $status ) {
		$assert( $class::isEligibleStatus( $status ), 'Required status is not eligible: ' . $status . '.' );
	}
	foreach ( array( 'trash', 'auto-draft', 'inherit' ) as $status ) {
		$assert( ! $class::isEligibleStatus( $status ), 'Unsupported status is eligible: ' . $status . '.' );
	}

	$compact_sequences = array(
		array( 'hero', 'faq' ),
		array( 'hero', 'shipments', 'faq' ),
		array( 'hero', 'cases', 'faq' ),
		array( 'hero', 'shipments', 'cases', 'faq' ),
	);
	foreach ( $compact_sequences as $sequence ) {
		$rows = array_map( static fn( string $slug ): array => array( 'acf_fc_layout' => $slug ), $sequence );
		$assert( $class::isCompactSequence( $rows ), 'Accepted compact sequence was rejected: ' . implode( ' > ', $sequence ) . '.' );
	}
	foreach ( array( array( 'hero' ), array( 'faq' ), array( 'hero', 'cases', 'shipments', 'faq' ), array( 'hero', 'about', 'faq' ), array( 'hero', 'faq', 'faq' ) ) as $sequence ) {
		$rows = array_map( static fn( string $slug ): array => array( 'acf_fc_layout' => $slug ), $sequence );
		$assert( ! $class::isCompactSequence( $rows ), 'Invalid compact sequence was accepted: ' . implode( ' > ', $sequence ) . '.' );
	}

	$fields = array(
		'field_group' => array( 'name' => 'process', 'type' => 'group' ),
		'field_title' => array( 'name' => 'zagolovok', 'type' => 'text' ),
		'field_items' => array( 'name' => 'elementy', 'type' => 'repeater' ),
		'field_media' => array( 'name' => 'image', 'type' => 'image' ),
	);
	$normalized = $class::normalizeRawValue(
		array( 'field_group' => array( 'field_title' => '<b>Raw title</b>', 'field_items' => array(), 'field_media' => '19' ) ),
		static fn( string $key ): ?array => $fields[ $key ] ?? null
	);
	$assert( '<b>Raw title</b>' === ( $normalized['process']['zagolovok'] ?? null ), 'Raw scalar content was formatted during normalization.' );
	$assert( '19' === ( $normalized['process']['image'] ?? null ), 'Raw media ID was formatted during normalization.' );

	$six_items = array();
	for ( $step = 1; $step <= 6; ++$step ) {
		$six_items[] = array( 'zagolovok' => 'Шаг ' . $step, 'tekst' => '<p>Текст ' . $step . '</p>', 'vybor_elementa' => 'process__item_half' );
	}
	$mapped_six = $class::mapLegacyProcess( array( 'zagolovok' => 'Процесс', 'elementy' => $six_items, 'tekst' => '' ) );
	$assert( 'Процесс' === ( $mapped_six['title'] ?? null ) && 6 === count( $mapped_six['items'] ?? array() ), 'Six-step Process fixture is not mapped.' );
	$assert( ! array_key_exists( 'vybor_elementa', $mapped_six['items'][0] ?? array() ), 'Legacy Process width selector leaked into canonical data.' );
	$assert( ! array_key_exists( 'description', $mapped_six ?? array() ), 'Empty legacy Process description should be omitted.' );

	$seven_items   = $six_items;
	$seven_items[] = array( 'zagolovok' => 'Шаг 7', 'tekst' => 'Текст 7', 'vybor_elementa' => 'process__item_four' );
	$seven_items[] = array( 'zagolovok' => 'Неполный', 'tekst' => '' );
	$seven_items[] = 'corrupt';
	$mapped_seven  = $class::mapLegacyProcess( array( 'zagolovok' => 'Семь шагов', 'elementy' => $seven_items, 'tekst' => '<p>Финал</p>' ) );
	$assert( 7 === count( $mapped_seven['items'] ?? array() ), 'Incomplete legacy Process rows are not filtered.' );
	$assert( '<p>Финал</p>' === ( $mapped_seven['description'] ?? null ), 'Legacy Process description is not preserved.' );
	$assert( null === $class::mapLegacyProcess( array( 'zagolovok' => '', 'elementy' => $six_items ) ), 'Legacy Process without a title should not map.' );
	$assert( null === $class::mapLegacyProcess( array( 'zagolovok' => 'Пусто', 'elementy' => array( array( 'zagolovok' => 'Шаг', 'tekst' => '' ) ) ) ), 'Legacy Process without a complete step should not map.' );

	$hero      = array( 'acf_fc_layout' => 'hero', 'enabled' => 1, 'anchor' => 'hero', 'overrides' => array( 'hero' => array( 'title' => 'Оригинал' ) ) );
	$shipments = array( 'acf_fc_layout' => 'shipments', 'enabled' => 1, 'anchor' => 'routes', 'variant' => 'default', 'overrides_default' => array( 'default' => array( 'title' => 'Направления' ) ) );
	$cases     = array( 'acf_fc_layout' => 'cases', 'enabled' => 1, 'anchor' => 'cases', 'overrides' => array( 'cases' => array( 'title' => 'Кейсы' ) ) );
	$faq       = array( 'acf_fc_layout' => 'faq', 'enabled' => 1, 'anchor' => 'faq', 'overrides' => array( 'faq' => array( 'title' => 'FAQ' ) ) );
	$source    = array( $hero, $shipments, $cases, $faq );
	$result    = $class::reconcileRows( $source, $mapped_six );
	$expected_slugs = array( 'hero', 'shipments', 'cases', 'calculator', 'process', 'guarantees', 'review', 'destinations', 'seo', 'faq', 'call' );
	$assert( array() === ( $result['conflicts'] ?? null ), 'Valid compact rows produced a conflict.' );
	$assert( $expected_slugs === array_column( $result['rows'] ?? array(), 'acf_fc_layout' ), 'Rows are not in canonical internal-page order.' );
	$assert( $hero === ( $result['rows'][0] ?? null ) && $shipments === ( $result['rows'][1] ?? null ) && $cases === ( $result['rows'][2] ?? null ) && $faq === ( $result['rows'][9] ?? null ), 'Existing rows were not preserved byte-for-byte.' );
	foreach ( array( 3, 5, 6, 7, 8, 10 ) as $index ) {
		$row = $result['rows'][ $index ] ?? array();
		$assert( array( 'acf_fc_layout', 'enabled', 'anchor', 'overrides' ) === array_keys( $row ) && array() === ( $row['overrides'] ?? null ), 'Shared row contains local data at index ' . $index . '.' );
	}
	$assert( array( 'process' => $mapped_six ) === ( $result['rows'][4]['overrides'] ?? null ), 'Process data is not stored under overrides.process.' );

	$without_optional = $class::reconcileRows( array( $hero, $faq ), null );
	$assert( array( 'hero', 'calculator', 'guarantees', 'review', 'destinations', 'seo', 'faq', 'call' ) === array_column( $without_optional['rows'] ?? array(), 'acf_fc_layout' ), 'Missing Shipments, Cases, or Process rows were incorrectly added.' );

	$again = $class::reconcileRows( $result['rows'], $mapped_six );
	$assert( array() === ( $again['conflicts'] ?? null ) && $result['rows'] === ( $again['rows'] ?? null ), 'Equivalent post-migration rows are not idempotent.' );

	foreach (
		array(
			array( $hero, $shipments, $shipments, $faq ),
			array( $hero, $cases, $shipments, $faq ),
			array( $hero, array( 'acf_fc_layout' => 'about' ), $faq ),
		) as $conflicting_rows
	) {
		$conflict = $class::reconcileRows( $conflicting_rows, null );
		$assert( ! empty( $conflict['conflicts'] ), 'Duplicate, misordered, or extra rows were not reported as a conflict.' );
		$assert( $conflicting_rows === ( $conflict['rows'] ?? null ), 'Conflicting rows were mutated.' );
	}

	$manual_target = $result['rows'];
	$manual_target[3]['overrides'] = array( 'calculator' => array( 'title' => 'Редактор' ) );
	$manual_conflict = $class::reconcileRows( $manual_target, $mapped_six );
	$assert( ! empty( $manual_conflict['conflicts'] ) && $manual_target === ( $manual_conflict['rows'] ?? null ), 'Manual post-migration override was overwritten.' );
}

if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}

echo "Internal page assembly migration contracts passed.\n";
