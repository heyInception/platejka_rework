<?php

namespace Platejka\Core\Acf;

defined( 'ABSPATH' ) || exit;

/** Read the immutable, versioned fallback used before the first migration. */
final class SectionSeed {
	/** @return array{schema_version:int,sections:array<string,mixed>,home_rows:array<int,mixed>} */
	public static function get(): array {
		$empty = array(
			'schema_version' => 1,
			'sections'       => array(),
			'home_rows'      => array(),
		);
		$path  = (string) apply_filters( 'platejka_section_seed_path', dirname( __DIR__, 2 ) . '/data/section-defaults-v1.json' );
		if ( ! is_readable( $path ) ) {
			return $empty;
		}

		$decoded = json_decode( (string) file_get_contents( $path ), true );
		if ( ! is_array( $decoded ) || 1 !== ( $decoded['schema_version'] ?? null ) || ! is_array( $decoded['sections'] ?? null ) ) {
			return $empty;
		}

		return array(
			'schema_version' => 1,
			'sections'       => $decoded['sections'],
			'home_rows'      => is_array( $decoded['home_rows'] ?? null ) ? $decoded['home_rows'] : array(),
		);
	}
}
