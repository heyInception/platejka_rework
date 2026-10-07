<?php

namespace Platejka\Core\Acf;

defined( 'ABSPATH' ) || exit;

/** Read the immutable seed for the first migrated default-template page. */
final class DefaultPageSeed {
	/** @return array{schema_version:int,sections:array<string,mixed>,page_rows:array<int,mixed>} */
	public static function get(): array {
		$empty = array(
			'schema_version' => 1,
			'sections'       => array(),
			'page_rows'      => array(),
		);
		$path  = (string) apply_filters( 'platejka_default_page_seed_path', dirname( __DIR__, 2 ) . '/data/default-page-defaults-v1.json' );
		if ( ! is_readable( $path ) ) {
			return $empty;
		}

		$decoded = json_decode( (string) file_get_contents( $path ), true );
		if ( ! is_array( $decoded ) || 1 !== ( $decoded['schema_version'] ?? null ) || ! is_array( $decoded['sections'] ?? null ) || ! is_array( $decoded['page_rows'] ?? null ) ) {
			return $empty;
		}

		return array(
			'schema_version' => 1,
			'sections'       => $decoded['sections'],
			'page_rows'      => $decoded['page_rows'],
		);
	}
}
