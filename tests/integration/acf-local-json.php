<?php
/**
 * Read-only integration checks via Studio WP-CLI eval-file.
 * Skip platejka-core to capture bootstrap diagnostics; pass the pre-activation
 * ACF reads hash as the first argument on GREEN. Pass without-acf with ACF skipped
 * to verify the optional dependency separately. No values are printed or stored.
 */

defined( 'ABSPATH' ) || exit;

require_once ABSPATH . 'wp-admin/includes/plugin.php';
$failures = array();
$checks = 0;
$assert = static function ( bool $condition, string $message ) use ( &$failures, &$checks ): void {
	++$checks;
	if ( ! $condition ) {
		$failures[] = $message;
		WP_CLI::warning( $message );
	}
};
$finish = static function () use ( &$checks, &$failures ): void {
	WP_CLI::log( sprintf( 'ACF Local JSON: %d checks, %d failures.', $checks, count( $failures ) ) );
	if ( $failures ) {
		WP_CLI::halt( 1 );
	}
	WP_CLI::success( 'ACF Local JSON integration passed with zero failures.' );
};

$plugin = 'platejka-core/platejka-core.php';
$plugin_path = WP_PLUGIN_DIR . '/platejka-core/';
$json_path = $plugin_path . 'acf-json';
$fixture_path = WP_CONTENT_DIR . '/tests/fixtures/acf-page-baseline.json';
$without_acf = isset( $args[0] ) && 'without-acf' === $args[0];
$loaded_early = class_exists( 'Platejka\\Core\\Acf\\LocalJson', false );
$assert( is_plugin_active( $plugin ), 'Platejka Core must be persistently active.' );
$diagnostics = array();
set_error_handler(
	static function ( $severity, $message ) use ( &$diagnostics ): bool {
		$diagnostics[] = $message;
		return true;
	},
	E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE
);
try {
	require_once $plugin_path . 'platejka-core.php';
} catch ( Throwable $error ) {
	$assert( false, 'Bootstrap threw an exception.' );
} finally {
	restore_error_handler();
}
$assert( array() === $diagnostics, 'Bootstrap must not emit warnings or notices.' );
$assert( defined( 'PLATEJKA_CORE_PATH' ) && PLATEJKA_CORE_PATH === $plugin_path, 'Canonical plugin path must be defined with a trailing slash.' );

if ( $without_acf ) {
	$assert( ! function_exists( 'acf' ), 'Optional dependency check requires ACF to be skipped.' );
	$assert( ! class_exists( 'Platejka\\Core\\Acf\\LocalJson', false ), 'ACF integration must not load without ACF.' );
	$assert( false === has_filter( 'acf/settings/load_json', array( 'Platejka\\Core\\Acf\\LocalJson', 'loadPaths' ) ), 'No ACF load hook may be registered without ACF.' );
	$finish();
	return;
}

$assert( function_exists( 'acf_get_field_group' ), 'ACF APIs must be available.' );
if ( ! function_exists( 'acf_get_field_group' ) ) {
	$finish();
	return;
}

$page_ids = array( 22, 24, 1873 );
$reads = array();
foreach ( $page_ids as $id ) {
	$reads[ $id ] = get_fields( $id );
	$assert( is_array( $reads[ $id ] ) && count( $reads[ $id ] ) > 0, 'Representative ACF page read must be non-empty: ' . $id );
}
$reads_hash = hash( 'sha256', serialize( $reads ) );
unset( $reads );
WP_CLI::log( 'ACF reads SHA-256: ' . $reads_hash );
if ( isset( $args[0] ) ) {
	$assert( hash_equals( $args[0], $reads_hash ), 'Existing ACF reads must equal the pre-activation hash.' );
}

$class = 'Platejka\\Core\\Acf\\LocalJson';
$assert( class_exists( $class ), 'LocalJson class must be available from the plugin.' );
if ( class_exists( $class ) ) {
	$existing = array( '/existing/theme-json', '/existing/other-json' );
	$assert( array_merge( $existing, array( $json_path ) ) === $class::loadPaths( $existing ), 'Load paths must preserve pre-existing paths and append the canonical path.' );
	$with_canonical = array( '/existing/theme-json', $json_path, '/existing/other-json' );
	$assert( $with_canonical === $class::loadPaths( $with_canonical ), 'Load paths must not append a duplicate canonical path.' );
	$assert( $json_path === $class::savePath( '/existing/theme-json' ), 'Save path must be canonical.' );
	$assert( $json_path === apply_filters( 'acf/settings/save_json', '/existing/theme-json' ), 'Save filter must be registered.' );
	$filtered = apply_filters( 'acf/settings/load_json', $existing );
	$assert( array_merge( $existing, array( $json_path ) ) === $filtered, 'Load filter must be registered without losing existing paths.' );
	$class::register();
	$assert( $filtered === apply_filters( 'acf/settings/load_json', $filtered ), 'Repeated registration/filtering must keep one canonical path.' );
	$live_paths = acf_get_setting( 'load_json' );
	$assert( 1 === count( array_keys( $live_paths, $json_path, true ) ), 'Live ACF load setting must contain the canonical path exactly once.' );
}

$groups = array(
	'group_67923f17e9f73' => 'Сквозные блоки',
	'group_661907c57a3f4' => 'О компании',
	'group_6617d7c2785fa' => 'УТП',
	'group_6605246c8e063' => 'Контакты',
	'group_6605213dd9e89' => 'Услуги',
	'group_66009656ed391' => 'Блоки',
	'group_65a959b489bb6' => 'Часто задаваемые вопросы',
);
$files = glob( $json_path . '/*.json' );
$assert( is_array( $files ) && 7 === count( $files ), 'Plugin must contain exactly seven JSON schema files.' );
$collect_keys = static function ( array $nodes ) use ( &$collect_keys, $assert ): array {
	$keys = array();
	foreach ( $nodes as $node ) {
		if ( ! is_array( $node ) ) {
			continue;
		}
		if ( isset( $node['key'] ) && str_starts_with( $node['key'], 'field_' ) ) {
			$keys[] = $node['key'];
			$assert( ! array_key_exists( 'value', $node ), 'Schema fields must exclude runtime content values.' );
		}
		$keys = array_merge( $keys, $collect_keys( $node ) );
	}
	return $keys;
};
$db_keys = static function ( int $parent_id ) use ( &$db_keys ): array {
	$keys = array();
	foreach ( acf_get_raw_fields( $parent_id ) as $field ) {
		$keys[] = $field['key'];
		$keys = array_merge( $keys, $db_keys( (int) $field['ID'] ) );
	}
	return $keys;
};
foreach ( $groups as $key => $title ) {
	$path = $json_path . '/' . $key . '.json';
	$assert( is_file( $path ), 'Required schema file is missing: ' . $key );
	if ( ! is_file( $path ) ) {
		continue;
	}
	$schema = json_decode( file_get_contents( $path ), true );
	$assert( JSON_ERROR_NONE === json_last_error() && is_array( $schema ), 'Schema must parse as JSON: ' . $key );
	if ( ! is_array( $schema ) ) {
		continue;
	}
	$assert( $key === ( $schema['key'] ?? null ) && $title === ( $schema['title'] ?? null ), 'Original group key/title must be retained: ' . $key );
	if ( $loaded_early ) {
		$local = acf_get_local_field_group( $key );
		$assert( $local && 'json' === ( $local['local'] ?? null ) && $path === ( $local['local_file'] ?? null ), 'Normal bootstrap must load the canonical local JSON group: ' . $key );
	}
	$db = acf_get_field_group_post( $key );
	$assert( $db && 'publish' === $db->post_status, 'Source must be a published DB-backed group: ' . $key );
	if ( $db ) {
		$expected = $db_keys( (int) $db->ID );
		$actual = $collect_keys( $schema['fields'] ?? array() );
		sort( $expected, SORT_STRING );
		sort( $actual, SORT_STRING );
		$assert( $expected === $actual && count( $actual ) === count( array_unique( $actual ) ), 'Every original DB field/sub-field key must be retained exactly once: ' . $key );
		WP_CLI::log( $key . ': ' . count( $actual ) . ' field keys.' );
	}
}

$assert( is_file( $fixture_path ), 'Structural page baseline fixture must exist.' );
if ( is_file( $fixture_path ) ) {
	$fixture = json_decode( file_get_contents( $fixture_path ), true );
	$assert( JSON_ERROR_NONE === json_last_error() && is_array( $fixture ), 'Baseline must parse as JSON.' );
	$assert( array( 'schema_version', 'pages' ) === array_keys( $fixture ?? array() ), 'Fixture root must contain only approved structural properties.' );
	$assert( 1 === ( $fixture['schema_version'] ?? null ), 'Fixture schema version must be one.' );
	$pages = $fixture['pages'] ?? array();
	$assert( $page_ids === array_column( $pages, 'id' ), 'Fixture must contain exactly the three pages in ID order.' );
	$expected_pages = array( 22 => array( 'o-kompanii', 'page-company.php' ), 24 => array( 'glavnaya', 'page-home.php' ), 1873 => array( 'china', 'page-payments.php' ) );
	foreach ( $pages as $page ) {
		$id = $page['id'] ?? 0;
		$assert( isset( $expected_pages[ $id ] ), 'Fixture contains an unexpected page.' );
		if ( ! isset( $expected_pages[ $id ] ) ) {
			continue;
		}
		$assert( array( 'id', 'slug', 'template', 'meta' ) === array_keys( $page ), 'Page may contain only structural properties: ' . $id );
		$assert( $expected_pages[ $id ] === array( $page['slug'] ?? null, $page['template'] ?? null ), 'Original page slug/template must be retained: ' . $id );
		$post = get_post( $id );
		$assert( $post && $post->post_name === $page['slug'] && get_post_meta( $id, '_wp_page_template', true ) === $page['template'], 'Page identity must match the database: ' . $id );
		$meta = get_post_meta( $id );
		$public_keys = array_values( array_filter( array_keys( $meta ), static fn ( $key ): bool => ! str_starts_with( $key, '_' ) ) );
		sort( $public_keys, SORT_STRING );
		$fixture_meta = $page['meta'] ?? array();
		$assert( $public_keys === array_keys( $fixture_meta ), 'Fixture must contain every non-private meta key in deterministic order: ' . $id );
		foreach ( $fixture_meta as $key => $shape ) {
			$allowed = array( 'type', 'empty', 'count' );
			$assert( is_array( $shape ) && array() === array_diff( array_keys( $shape ), $allowed ) && isset( $shape['type'], $shape['empty'] ), 'Meta records may contain only type, empty and count: ' . $id . '/' . $key );
			$assert( in_array( $shape['type'] ?? null, array( 'string', 'integer', 'float', 'boolean', 'array', 'object', 'null' ), true ) && is_bool( $shape['empty'] ?? null ), 'Meta type/filledness must use safe normalized values: ' . $id . '/' . $key );
			$assert( ! isset( $shape['count'] ) || ( is_int( $shape['count'] ) && $shape['count'] >= 0 ), 'Item count must be a non-negative integer: ' . $id . '/' . $key );
			if ( ! isset( $meta[ $key ] ) ) {
				continue;
			}
			$values = array_map( 'maybe_unserialize', $meta[ $key ] );
			$value = 1 === count( $values ) ? $values[0] : $values;
			$type = gettype( $value );
			$type = array( 'double' => 'float', 'NULL' => 'null' )[ $type ] ?? $type;
			$count = is_array( $value ) ? count( $value ) : null;
			$field_key = get_post_meta( $id, '_' . $key, true );
			$field = is_string( $field_key ) && str_starts_with( $field_key, 'field_' ) ? acf_get_raw_field( $field_key ) : false;
			if ( $field && 'repeater' === $field['type'] && is_numeric( $value ) && (int) $value >= 0 ) {
				$count = (int) $value;
			}
			$empty = null === $value || '' === $value || false === $value || array() === $value || ( $field && 'repeater' === $field['type'] && 0 === $count );
			$assert( $type === $shape['type'] && $empty === $shape['empty'], 'Structural metadata must match current type/filledness: ' . $id . '/' . $key );
			$assert( null === $count ? ! array_key_exists( 'count', $shape ) : $count === ( $shape['count'] ?? null ), 'Structural row/item count must match the database: ' . $id . '/' . $key );
		}
		WP_CLI::log( 'Page ' . $id . ': ' . count( $fixture_meta ) . ' public meta keys; content values excluded.' );
	}
}
$finish();
