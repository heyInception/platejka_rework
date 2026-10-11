<?php

defined( 'ABSPATH' ) || define( 'ABSPATH', dirname( __DIR__, 2 ) . DIRECTORY_SEPARATOR );

$migration_file = ABSPATH . 'plugins/platejka-core/src/Cli/InternalHeroMigrationCommand.php';
if ( is_file( $migration_file ) ) {
	require_once $migration_file;
}
$visibility_file = ABSPATH . 'plugins/platejka-core/src/Acf/LegacyFieldVisibility.php';
if ( is_file( $visibility_file ) ) {
	require_once $visibility_file;
}

$class    = '\\Platejka\\Core\\Cli\\InternalHeroMigrationCommand';
$failures = array();
$assert   = static function ( bool $condition, string $message ) use ( &$failures ): void {
	if ( ! $condition ) {
		$failures[] = $message;
	}
};

$assert( class_exists( $class ), 'InternalHeroMigrationCommand is missing.' );
if ( class_exists( $class ) ) {
	foreach ( array( 'publish', 'future', 'draft', 'pending', 'private' ) as $status ) {
		$assert( $class::isEligibleStatus( $status ), 'Required status is not eligible: ' . $status . '.' );
	}
	foreach ( array( 'trash', 'auto-draft', 'inherit' ) as $status ) {
		$assert( ! $class::isEligibleStatus( $status ), 'Unsupported status is eligible: ' . $status . '.' );
	}

	foreach ( array( 22, 24, 1873, 3632 ) as $page_id ) {
		$assert( $class::isExcludedPage( $page_id ), 'Required page exclusion is missing: ' . $page_id . '.' );
	}
	$assert( ! $class::isExcludedPage( 14 ), 'A normal internal page is excluded.' );

	foreach ( array( 1860, 2075, 2098, 2109 ) as $page_id ) {
		$assert( $class::shouldSwitchToDefaultTemplate( $page_id ), 'Template switch is missing for page ' . $page_id . '.' );
	}
	$assert( ! $class::shouldSwitchToDefaultTemplate( 3632 ), 'The Services page template must remain untouched.' );

	$row = $class::buildHeroRow( 'Локальный заголовок', '' );
	$hero = $row['overrides']['hero'] ?? array();
	$assert( 'hero' === ( $row['acf_fc_layout'] ?? null ) && 1 === ( $row['enabled'] ?? null ), 'Migrated Hero row is not enabled.' );
	$assert( 1 === ( $row['shared_content_only'] ?? null ), 'Migrated Hero does not request shared-only inheritance.' );
	$assert( '#25252a' === ( $hero['background_color'] ?? null ), 'Internal Hero background color is incorrect.' );
	$assert( 'theme://sections/hero/img/hero-bg.png' === ( $hero['background_image'] ?? null ), 'Internal Hero background image is incorrect.' );
	$assert( 'theme://sections/hero/img/hero-blur.png' === ( $hero['blur_image'] ?? null ), 'Internal Hero blur image is incorrect.' );
	$assert( 'Локальный заголовок' === ( $hero['title']['primary'] ?? null ), 'Legacy Hero title is not mapped to title.primary.' );
	$assert( '' === ( $hero['title']['secondary'] ?? null ), 'Internal Hero title.secondary must remain empty.' );
	$assert( '' === ( $hero['subtitle'] ?? null ), 'Missing legacy subtitle must remain empty.' );
	$assert( ! array_key_exists( 'badge', $hero ) && ! array_key_exists( 'items', $hero ), 'Shared Hero content was copied into local overrides.' );

	$existing = array(
		array( 'acf_fc_layout' => 'shipments', 'enabled' => 1 ),
		array( 'acf_fc_layout' => 'faq', 'enabled' => 1 ),
	);
	$inserted = $class::upsertHeroRow( $existing, $row );
	$assert( array( 'hero', 'shipments', 'faq' ) === array_column( $inserted['rows'] ?? array(), 'acf_fc_layout' ), 'Hero is not inserted as the first section.' );
	$assert( false === ( $inserted['conflict'] ?? null ), 'A missing Hero is incorrectly reported as a conflict.' );

	$partial = $row;
	$partial['overrides']['hero']['background_image'] = 0;
	$partial['overrides']['hero']['blur_image'] = 0;
	$recovered = $class::upsertHeroRow( array( $partial, $existing[0] ), $row, true );
	$assert( false === ( $recovered['conflict'] ?? null ) && true === ( $recovered['recovered'] ?? null ), 'A partial row created by this migration is not recoverable.' );
	$assert( $row === ( $recovered['rows'][0] ?? null ), 'Partial Hero recovery does not restore the target row.' );

	$manual = array(
		array( 'acf_fc_layout' => 'hero', 'enabled' => 1, 'overrides' => array( 'hero' => array( 'title' => array( 'primary' => 'Редактор' ) ) ) ),
		array( 'acf_fc_layout' => 'faq', 'enabled' => 1 ),
	);
	$conflict = $class::upsertHeroRow( $manual, $row );
	$assert( true === ( $conflict['conflict'] ?? null ), 'An existing Hero is not reported as a conflict.' );
	$assert( $manual === ( $conflict['rows'] ?? null ), 'An existing Hero was overwritten.' );
}

$visibility = '\\Platejka\\Core\\Acf\\LegacyFieldVisibility';
$assert( class_exists( $visibility ), 'LegacyFieldVisibility is missing.' );
if ( class_exists( $visibility ) ) {
	$assert( method_exists( $visibility, 'legacyHeroFieldKeys' ), 'LegacyFieldVisibility does not expose the legacy Hero field set.' );
	if ( method_exists( $visibility, 'legacyHeroFieldKeys' ) ) {
		$hero_keys = $visibility::legacyHeroFieldKeys();
		foreach ( array( 'field_66009656a1c12', 'field_66051d3d9621e', 'field_66c7356f59226', 'field_660097aea1c13', 'field_660097eda1c14' ) as $field_key ) {
			$assert( in_array( $field_key, $hero_keys, true ), 'Legacy Hero field remains visible: ' . $field_key . '.' );
		}
	}
}

if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}

echo "Internal Hero migration contracts passed.\n";
