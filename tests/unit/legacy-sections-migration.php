<?php

define( 'ABSPATH', dirname( __DIR__, 2 ) . DIRECTORY_SEPARATOR );
$class_file = ABSPATH . 'plugins/platejka-core/src/Cli/LegacySectionsMigrationCommand.php';
if ( is_file( $class_file ) ) {
	require_once $class_file;
}
$visibility_file = ABSPATH . 'plugins/platejka-core/src/Acf/LegacyFieldVisibility.php';
if ( is_file( $visibility_file ) ) {
	require_once $visibility_file;
}

$class    = '\\Platejka\\Core\\Cli\\LegacySectionsMigrationCommand';
$failures = array();
$assert   = static function ( bool $condition, string $message ) use ( &$failures ): void {
	if ( ! $condition ) {
		$failures[] = $message;
	}
};

	$assert( class_exists( $class ), 'LegacySectionsMigrationCommand is missing.' );
if ( class_exists( $class ) ) {
	$assert( $class::isEligibleStatus( 'publish' ) && $class::isEligibleStatus( 'future' ) && $class::isEligibleStatus( 'draft' ) && $class::isEligibleStatus( 'pending' ) && $class::isEligibleStatus( 'private' ), 'Required page statuses are not all eligible.' );
	$assert( ! $class::isEligibleStatus( 'trash' ) && ! $class::isEligibleStatus( 'auto-draft' ), 'Trash or auto-drafts must not be eligible.' );
	$assert( $class::usesDefaultTemplate( '' ) && $class::usesDefaultTemplate( 'default' ) && ! $class::usesDefaultTemplate( 'page-about.php' ), 'Default-template eligibility is incorrect.' );
	$fields = array(
		'field_title' => array( 'name' => 'zagolovok', 'type' => 'text' ),
		'field_image' => array( 'name' => 'izobrazheie', 'type' => 'image' ),
	);
	$normalized = $class::normalizeRawValue(
		array( array( 'field_title' => 'Raw title', 'field_image' => '19' ) ),
		static fn( string $key ): ?array => $fields[ $key ] ?? null
	);
	$assert( 'Raw title' === ( $normalized[0]['zagolovok'] ?? null ), 'Raw ACF field keys are not normalized.' );
	$assert( 19 === ( $normalized[0]['izobrazheie'] ?? null ), 'Raw ACF image IDs are not normalized to integers.' );

	$home = $class::mapHomeShipmentItems(
		array(
			array( 'zagolovok' => 'Китай', 'tekst' => '<p>Новый текст</p>', 'czena' => '5%', 'ssylka' => array( 'url' => '/china/' ) ),
		),
		array(
			array( 'title' => 'Старый', 'text' => 'Старый', 'image' => 42, 'tags' => array( array( 'label' => 'Сквозная' ) ), 'link' => array( 'url' => '/old/' ) ),
		)
	);
	$assert( 'Китай' === ( $home[0]['title'] ?? null ), 'Home shipment title is not mapped.' );
	$assert( '5%' === ( $home[0]['price'] ?? null ), 'Home shipment price is not mapped.' );
	$assert( 42 === ( $home[0]['image'] ?? null ), 'Home shipment image is not preserved.' );
	$assert( 'Сквозная' === ( $home[0]['tags'][0]['label'] ?? null ), 'Home shipment tags are not preserved.' );
	$assert( '/china/' === ( $home[0]['link']['url'] ?? null ), 'Home shipment link is not replaced.' );

	$internal = $class::mapInternalShipments(
		'Локальные направления',
		array(
			array( 'zagolovok' => 'Карточка', 'tekst' => 'Текст', 'izobrazhenie_services' => 73, 'cardclass' => 'legacy-class' ),
		)
	);
	$assert( 'Локальные направления' === ( $internal['title'] ?? null ), 'Internal section title is not mapped.' );
	$assert( 73 === ( $internal['items'][0]['image'] ?? null ), 'Internal shipment image is not mapped.' );
	$assert( array() === ( $internal['items'][0]['tags'] ?? null ), 'Internal shipment tags must be empty.' );
	$assert( '' === ( $internal['items'][0]['link'] ?? null ), 'Internal shipment link must be empty.' );
	$assert( ! array_key_exists( 'cardclass', $internal['items'][0] ?? array() ), 'Legacy card class must be skipped.' );

	$cases = $class::mapCases(
		'Кейсы страницы',
		array(
			array( 'zagolovok' => '<p>Отзыв</p>', 'imya' => 'Анна', 'kompaniya' => 'Компания', 'izobrazheie' => 91, 'colors' => 'green' ),
		)
	);
	$assert( 'Анна' === ( $cases['items'][0]['title'] ?? null ), 'Case author is not mapped to title.' );
	$assert( '<p>Отзыв</p>' === ( $cases['items'][0]['text'] ?? null ), 'Case review is not mapped to text.' );
	$assert( 'Компания' === ( $cases['items'][0]['caption'] ?? null ), 'Case company is not mapped to caption.' );
	$assert( '★★★★★' === ( $cases['items'][0]['value'] ?? null ), 'Case rating must contain five stars.' );
	$assert( 91 === ( $cases['items'][0]['image'] ?? null ), 'Case image is not mapped.' );
	$assert( ! array_key_exists( 'colors', $cases['items'][0] ?? array() ), 'Legacy case color class must be skipped.' );

	$rows = array(
		array( 'acf_fc_layout' => 'hero', 'enabled' => 1 ),
		array( 'acf_fc_layout' => 'about', 'enabled' => 1 ),
		array( 'acf_fc_layout' => 'faq', 'enabled' => 1 ),
	);
	$upserted = $class::upsertBuilderRows( $rows, $internal, $cases, false );
	$assert( array( 'hero', 'shipments', 'cases', 'about', 'faq' ) === array_column( $upserted['rows'] ?? array(), 'acf_fc_layout' ), 'Builder rows are not in canonical order.' );
	$assert( 'default' === ( $upserted['rows'][1]['variant'] ?? null ), 'Internal shipment variant must be default.' );
	$assert( 'hide' === ( $upserted['rows'][1]['description_mode'] ?? null ), 'Internal shipment description must be hidden.' );
	$assert( 'hide' === ( $upserted['rows'][2]['description_mode'] ?? null ), 'Internal cases description must be hidden.' );
	$assert( 1 === ( $upserted['rows'][1]['enabled'] ?? null ) && 1 === ( $upserted['rows'][2]['enabled'] ?? null ), 'Migrated rows must be enabled.' );

	$manual = array(
		array( 'acf_fc_layout' => 'hero', 'enabled' => 1 ),
		array( 'acf_fc_layout' => 'shipments', 'enabled' => 0, 'variant' => 'default', 'overrides_default' => array( 'default' => array( 'title' => 'Редактор' ) ) ),
		array( 'acf_fc_layout' => 'cases', 'enabled' => 0, 'overrides' => array( 'cases' => array( 'title' => 'Редакторские кейсы' ) ) ),
	);
	$conflicted = $class::upsertBuilderRows( $manual, $internal, $cases, false );
	$assert( 'Редактор' === ( $conflicted['rows'][1]['overrides_default']['default']['title'] ?? null ), 'Manual shipment override was overwritten.' );
	$assert( 0 === ( $conflicted['rows'][1]['enabled'] ?? null ), 'Manual shipment enabled state was overwritten.' );
	$assert( 'Редакторские кейсы' === ( $conflicted['rows'][2]['overrides']['cases']['title'] ?? null ), 'Manual cases override was overwritten.' );
	$assert( array( 'shipments', 'cases' ) === array_column( $conflicted['conflicts'] ?? array(), 'section' ), 'Conflicts are not reported.' );

	$home_rows = $class::upsertBuilderRows( array( array( 'acf_fc_layout' => 'hero-main', 'enabled' => 1 ) ), null, null, true );
	$assert( array( 'hero-main', 'shipments', 'cases' ) === array_column( $home_rows['rows'] ?? array(), 'acf_fc_layout' ), 'Home rows are not created in canonical order.' );
	$assert( 'main' === ( $home_rows['rows'][1]['variant'] ?? null ), 'Home shipment variant must be main.' );
	$assert( 'inherit' === ( $home_rows['rows'][1]['description_mode'] ?? null ) && 'inherit' === ( $home_rows['rows'][2]['description_mode'] ?? null ), 'Home descriptions must inherit.' );

	$again = $class::upsertBuilderRows( $upserted['rows'], $internal, $cases, false );
	$assert( $upserted['rows'] === ( $again['rows'] ?? null ), 'Equivalent target generation is not idempotent.' );

	$acf_round_trip = $upserted['rows'];
	$acf_round_trip[1]['overrides_default']['default']['eyebrow'] = false;
	$acf_round_trip[2]['overrides']['cases']['primary_link'] = false;
	$round_trip = $class::upsertBuilderRows( $acf_round_trip, $internal, $cases, false );
	$assert( array() === ( $round_trip['conflicts'] ?? null ), 'ACF empty structural fields create false conflicts after migration.' );
}

$visibility = '\\Platejka\\Core\\Acf\\LegacyFieldVisibility';
$assert( class_exists( $visibility ), 'LegacyFieldVisibility is missing.' );
if ( class_exists( $visibility ) ) {
	$hidden = $visibility::legacyFieldKeys();
	$assert( in_array( 'field_66009b8844e4f', $hidden, true ), 'Legacy homepage services repeater is not hidden.' );
	$assert( in_array( 'field_66052169a31f1', $hidden, true ), 'Legacy internal services repeater is not hidden.' );
	$assert( in_array( 'field_66009d41fec38', $hidden, true ), 'Legacy cases repeater is not hidden.' );
	$assert( in_array( 'field_6790c497eeee4', $hidden, true ), 'Skipped legacy plural case images must disappear with the old editor section.' );
}

if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}

echo "Legacy sections migration contracts passed.\n";
