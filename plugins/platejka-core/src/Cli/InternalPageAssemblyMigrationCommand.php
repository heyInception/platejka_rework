<?php

namespace Platejka\Core\Cli;

defined( 'ABSPATH' ) || exit;

/** Preview-first migration for compact internal-page section assemblies. */
final class InternalPageAssemblyMigrationCommand {
	public static function isEligibleStatus( string $status ): bool {
		return in_array( $status, array( 'publish', 'future', 'draft', 'pending', 'private' ), true );
	}

	/** @param array<int,mixed> $rows */
	public static function isCompactSequence( array $rows ): bool {
		$slugs = self::rowSlugs( $rows );
		if ( count( $slugs ) !== count( $rows ) ) {
			return false;
		}

		return in_array(
			$slugs,
			array(
				array( 'hero', 'faq' ),
				array( 'hero', 'shipments', 'faq' ),
				array( 'hero', 'cases', 'faq' ),
				array( 'hero', 'shipments', 'cases', 'faq' ),
			),
			true
		);
	}

	/**
	 * Convert raw ACF field-key arrays to field-name arrays without formatting values.
	 *
	 * @param mixed                                         $value
	 * @param callable(string):(?array<string,mixed>)|null $field_resolver
	 * @return mixed
	 */
	public static function normalizeRawValue( $value, ?callable $field_resolver = null ) {
		$field_resolver ??= static function ( string $key ): ?array {
			if ( ! function_exists( 'acf_get_field' ) ) {
				return null;
			}
			$field = acf_get_field( $key );
			return is_array( $field ) ? $field : null;
		};

		$normalize = static function ( $current ) use ( &$normalize, $field_resolver ) {
			if ( ! is_array( $current ) ) {
				return $current;
			}

			$normalized = array();
			foreach ( $current as $key => $child ) {
				$normalized_key = $key;
				if ( is_string( $key ) && str_starts_with( $key, 'field_' ) ) {
					$field = $field_resolver( $key );
					if ( is_array( $field ) && is_string( $field['name'] ?? null ) && '' !== $field['name'] ) {
						$normalized_key = $field['name'];
					}
				}
				$normalized[ $normalized_key ] = $normalize( $child );
			}
			return $normalized;
		};

		return $normalize( $value );
	}

	/** @param mixed $value @return array<string,mixed>|null */
	public static function mapLegacyProcess( $value ): ?array {
		if ( ! is_array( $value ) ) {
			return null;
		}
		if ( isset( $value['process'] ) && is_array( $value['process'] ) ) {
			$value = $value['process'];
		}

		$title       = is_scalar( $value['zagolovok'] ?? null ) ? (string) $value['zagolovok'] : '';
		$legacy_items = is_array( $value['elementy'] ?? null ) ? $value['elementy'] : array();
		$items       = array();
		foreach ( $legacy_items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$item_title = is_scalar( $item['zagolovok'] ?? null ) ? (string) $item['zagolovok'] : '';
			$item_text  = is_scalar( $item['tekst'] ?? null ) ? (string) $item['tekst'] : '';
			if ( ! self::hasText( $item_title ) || ! self::hasText( $item_text ) ) {
				continue;
			}
			$items[] = array( 'title' => $item_title, 'text' => $item_text );
		}

		if ( ! self::hasText( $title ) || array() === $items ) {
			return null;
		}

		$mapped = array( 'title' => $title, 'items' => $items );
		$description = is_scalar( $value['tekst'] ?? null ) ? (string) $value['tekst'] : '';
		if ( self::hasText( $description ) ) {
			$mapped['description'] = $description;
		}
		return $mapped;
	}

	/**
	 * @param array<int,mixed>         $rows
	 * @param array<string,mixed>|null $process
	 * @return array{rows:array<int,mixed>,conflicts:array<int,array<string,mixed>>}
	 */
	public static function reconcileRows( array $rows, ?array $process ): array {
		if ( ! self::isCompactSequence( $rows ) ) {
			$target = self::targetRowsFromCurrent( $rows, $process );
			if ( null !== $target && $rows === $target ) {
				return array( 'rows' => $rows, 'conflicts' => array() );
			}
			return array(
				'rows'      => $rows,
				'conflicts' => array(
					array(
						'reason'  => null === $target ? 'unrecognized_sequence' : 'target_mismatch',
						'current' => self::rowSlugs( $rows ),
						'target'  => null === $target ? array() : self::rowSlugs( $target ),
					),
				),
			);
		}

		$by_slug = array();
		foreach ( $rows as $row ) {
			$by_slug[ $row['acf_fc_layout'] ] = $row;
		}
		$target = array( $by_slug['hero'] );
		foreach ( array( 'shipments', 'cases' ) as $slug ) {
			if ( isset( $by_slug[ $slug ] ) ) {
				$target[] = $by_slug[ $slug ];
			}
		}
		$target[] = self::sharedRow( 'calculator' );
		if ( null !== $process ) {
			$target[] = self::processRow( $process );
		}
		foreach ( array( 'guarantees', 'review', 'destinations', 'seo' ) as $slug ) {
			$target[] = self::sharedRow( $slug );
		}
		$target[] = $by_slug['faq'];
		$target[] = self::sharedRow( 'call' );

		return array( 'rows' => $target, 'conflicts' => array() );
	}

	/** @param array<int,mixed> $rows @param array<string,mixed>|null $process @return array<int,mixed>|null */
	private static function targetRowsFromCurrent( array $rows, ?array $process ): ?array {
		$slugs = self::rowSlugs( $rows );
		if ( count( $slugs ) !== count( $rows ) || count( array_unique( $slugs ) ) !== count( $slugs ) ) {
			return null;
		}

		$expected = array( 'hero' );
		if ( in_array( 'shipments', $slugs, true ) ) {
			$expected[] = 'shipments';
		}
		if ( in_array( 'cases', $slugs, true ) ) {
			$expected[] = 'cases';
		}
		$expected[] = 'calculator';
		if ( null !== $process ) {
			$expected[] = 'process';
		}
		$expected = array_merge( $expected, array( 'guarantees', 'review', 'destinations', 'seo', 'faq', 'call' ) );
		if ( $slugs !== $expected ) {
			return null;
		}

		$by_slug = array_column( $rows, null, 'acf_fc_layout' );
		$target  = array( $by_slug['hero'] );
		foreach ( array( 'shipments', 'cases' ) as $slug ) {
			if ( isset( $by_slug[ $slug ] ) ) {
				$target[] = $by_slug[ $slug ];
			}
		}
		$target[] = self::sharedRow( 'calculator' );
		if ( null !== $process ) {
			$target[] = self::processRow( $process );
		}
		foreach ( array( 'guarantees', 'review', 'destinations', 'seo' ) as $slug ) {
			$target[] = self::sharedRow( $slug );
		}
		$target[] = $by_slug['faq'];
		$target[] = self::sharedRow( 'call' );
		return $target;
	}

	/** @return array{acf_fc_layout:string,enabled:int,anchor:string,overrides:array<mixed>} */
	private static function sharedRow( string $slug ): array {
		return array( 'acf_fc_layout' => $slug, 'enabled' => 1, 'anchor' => '', 'overrides' => array() );
	}

	/** @param array<string,mixed> $process @return array<string,mixed> */
	private static function processRow( array $process ): array {
		return array( 'acf_fc_layout' => 'process', 'enabled' => 1, 'anchor' => '', 'overrides' => array( 'process' => $process ) );
	}

	/** @param array<int,mixed> $rows @return array<int,string> */
	private static function rowSlugs( array $rows ): array {
		$slugs = array();
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) || ! is_string( $row['acf_fc_layout'] ?? null ) || '' === $row['acf_fc_layout'] ) {
				continue;
			}
			$slugs[] = $row['acf_fc_layout'];
		}
		return $slugs;
	}

	private static function hasText( string $value ): bool {
		return '' !== trim( strip_tags( $value ) );
	}
}
