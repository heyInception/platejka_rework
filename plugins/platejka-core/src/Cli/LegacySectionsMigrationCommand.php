<?php

namespace Platejka\Core\Cli;

defined( 'ABSPATH' ) || exit;

/** Preview-first migration from legacy services and cases into section-builder data. */
final class LegacySectionsMigrationCommand {
	/**
	 * Convert raw ACF field-key arrays to stable field-name arrays without display formatting.
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

		$normalize = static function ( $current, ?array $field = null ) use ( &$normalize, $field_resolver ) {
			if ( is_array( $current ) ) {
				$normalized = array();
				foreach ( $current as $key => $child ) {
					$child_field = null;
					$child_key   = $key;
					if ( is_string( $key ) && str_starts_with( $key, 'field_' ) ) {
						$child_field = $field_resolver( $key );
						if ( is_array( $child_field ) ) {
							$child_key = (string) ( $child_field['name'] ?? $key );
						}
					}
					$normalized[ $child_key ] = $normalize( $child, $child_field );
				}
				return $normalized;
			}

			$type = (string) ( $field['type'] ?? '' );
			if ( 'true_false' === $type ) {
				return (int) (bool) $current;
			}
			if ( in_array( $type, array( 'image', 'file', 'post_object' ), true ) && is_numeric( $current ) ) {
				return (int) $current;
			}
			return $current;
		};

		return $normalize( $value );
	}

	/** @param array<int,mixed> $legacy @param array<int,mixed> $current @return array<int,array<string,mixed>> */
	public static function mapHomeShipmentItems( array $legacy, array $current ): array {
		$items = array();
		foreach ( $legacy as $index => $row ) {
			if ( ! is_array( $row ) || ! self::hasMeaningfulValue( $row ) ) {
				continue;
			}
			$item          = isset( $current[ $index ] ) && is_array( $current[ $index ] ) ? $current[ $index ] : array();
			$item['title'] = (string) ( $row['zagolovok'] ?? '' );
			$item['text']  = (string) ( $row['tekst'] ?? '' );
			$item['price'] = (string) ( $row['czena'] ?? '' );
			$item['link']  = is_array( $row['ssylka'] ?? null ) ? $row['ssylka'] : '';
			$items[]       = $item;
		}
		return $items;
	}

	/** @param array<int,mixed> $legacy @return array<string,mixed> */
	public static function mapInternalShipments( string $title, array $legacy ): array {
		$items = array();
		foreach ( $legacy as $row ) {
			if ( ! is_array( $row ) || ! self::hasMeaningfulValue( $row ) ) {
				continue;
			}
			$items[] = array(
				'title' => (string) ( $row['zagolovok'] ?? '' ),
				'text'  => (string) ( $row['tekst'] ?? '' ),
				'image' => self::imageId( $row['izobrazhenie_services'] ?? 0 ),
				'link'  => '',
				'tags'  => array(),
			);
		}
		return array( 'title' => $title, 'description' => '', 'items' => $items );
	}

	/** @param array<int,mixed> $legacy @return array<string,mixed> */
	public static function mapCases( string $title, array $legacy ): array {
		$items = array();
		foreach ( $legacy as $row ) {
			if ( ! is_array( $row ) || ! self::hasMeaningfulValue( $row ) ) {
				continue;
			}
			$items[] = array(
				'title'   => (string) ( $row['imya'] ?? '' ),
				'text'    => (string) ( $row['zagolovok'] ?? '' ),
				'value'   => '★★★★★',
				'caption' => (string) ( $row['kompaniya'] ?? '' ),
				'image'   => self::imageId( $row['izobrazheie'] ?? 0 ),
				'link'    => '',
			);
		}
		return array( 'title' => $title, 'description' => '', 'items' => $items );
	}

	/**
	 * @param array<int,mixed>       $rows
	 * @param array<string,mixed>|null $shipments
	 * @param array<string,mixed>|null $cases
	 * @return array{rows:array<int,mixed>,conflicts:array<int,array<string,mixed>>}
	 */
	public static function upsertBuilderRows( array $rows, ?array $shipments, ?array $cases, bool $home ): array {
		$targets = array(
			'shipments' => $home || null !== $shipments,
			'cases'     => $home || null !== $cases,
		);
		$selected  = array( 'shipments' => null, 'cases' => null );
		$remaining = array();
		foreach ( $rows as $row ) {
			$slug = is_array( $row ) ? (string) ( $row['acf_fc_layout'] ?? '' ) : '';
			if ( isset( $targets[ $slug ] ) && $targets[ $slug ] && null === $selected[ $slug ] ) {
				$selected[ $slug ] = $row;
				continue;
			}
			$remaining[] = $row;
		}

		$conflicts = array();
		$canonical = array();
		if ( $targets['shipments'] ) {
			$row = is_array( $selected['shipments'] ) ? $selected['shipments'] : array( 'acf_fc_layout' => 'shipments', 'anchor' => '' );
			if ( $home ) {
				if ( ! self::shipmentOverride( $row, 'main' ) ) {
					$row['enabled']          = 1;
					$row['variant']          = 'main';
					$row['description_mode'] = 'inherit';
				}
			} else {
				$current = self::shipmentOverride( $row, 'default' );
				if ( self::hasMeaningfulValue( $current ) && self::stable( $current ) !== self::stable( $shipments ) ) {
					$conflicts[] = array( 'section' => 'shipments', 'current' => $current, 'target' => $shipments );
				} elseif ( ! self::hasMeaningfulValue( $current ) ) {
					$row['enabled']           = 1;
					$row['variant']           = 'default';
					$row['description_mode']  = 'hide';
					$row['overrides_default'] = array( 'default' => $shipments );
				}
			}
			$canonical[] = $row;
		}

		if ( $targets['cases'] ) {
			$row = is_array( $selected['cases'] ) ? $selected['cases'] : array( 'acf_fc_layout' => 'cases', 'anchor' => '' );
			if ( $home ) {
				if ( ! self::caseOverride( $row ) ) {
					$row['enabled']          = 1;
					$row['description_mode'] = 'inherit';
				}
			} else {
				$current = self::caseOverride( $row );
				if ( self::hasMeaningfulValue( $current ) && self::stable( $current ) !== self::stable( $cases ) ) {
					$conflicts[] = array( 'section' => 'cases', 'current' => $current, 'target' => $cases );
				} elseif ( ! self::hasMeaningfulValue( $current ) ) {
					$row['enabled']          = 1;
					$row['description_mode'] = 'hide';
					$row['overrides']        = array( 'cases' => $cases );
				}
			}
			$canonical[] = $row;
		}

		$insert_at = 0;
		foreach ( $remaining as $index => $row ) {
			$slug = is_array( $row ) ? (string) ( $row['acf_fc_layout'] ?? '' ) : '';
			if ( in_array( $slug, array( 'hero-main', 'hero', 'about-hero' ), true ) ) {
				$insert_at = $index + 1;
				break;
			}
		}
		array_splice( $remaining, $insert_at, 0, $canonical );

		return array( 'rows' => array_values( $remaining ), 'conflicts' => $conflicts );
	}

	/** @param mixed $value */
	public static function hasMeaningfulValue( $value ): bool {
		if ( is_array( $value ) ) {
			foreach ( $value as $child ) {
				if ( self::hasMeaningfulValue( $child ) ) {
					return true;
				}
			}
			return false;
		}
		if ( is_string( $value ) ) {
			return '' !== trim( $value );
		}
		return null !== $value && false !== $value && 0 !== $value;
	}

	/** @param mixed $value */
	private static function imageId( $value ): int {
		if ( is_array( $value ) ) {
			$value = $value['ID'] ?? $value['id'] ?? 0;
		}
		return is_numeric( $value ) ? (int) $value : 0;
	}

	/** @param array<string,mixed> $row @return array<string,mixed> */
	private static function shipmentOverride( array $row, string $variant ): array {
		$key   = 'main' === $variant ? 'overrides_main' : 'overrides_default';
		$value = isset( $row[ $key ] ) && is_array( $row[ $key ] ) ? $row[ $key ] : array();
		return isset( $value[ $variant ] ) && is_array( $value[ $variant ] ) ? $value[ $variant ] : $value;
	}

	/** @param array<string,mixed> $row @return array<string,mixed> */
	private static function caseOverride( array $row ): array {
		$value = isset( $row['overrides'] ) && is_array( $row['overrides'] ) ? $row['overrides'] : array();
		return isset( $value['cases'] ) && is_array( $value['cases'] ) ? $value['cases'] : $value;
	}

	/** @param mixed $value */
	private static function stable( $value ): string {
		return (string) json_encode( $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}
}
