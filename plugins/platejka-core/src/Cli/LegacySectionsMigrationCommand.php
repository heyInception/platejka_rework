<?php

namespace Platejka\Core\Cli;

use Platejka\Core\Acf\LegacyFieldVisibility;
use Platejka\Core\Acf\SectionSeed;
use RuntimeException;

defined( 'ABSPATH' ) || exit;

/** Preview-first migration from legacy services and cases into section-builder data. */
final class LegacySectionsMigrationCommand {
	private const HOME_ID            = 24;
	private const OPTIONS_ID         = 'platejka_section_defaults';
	private const LEGACY_HOME_ITEMS  = 'field_66009b8844e4f';
	private const LEGACY_CASE_TITLE  = 'field_66009d32fec37';
	private const LEGACY_CASE_ITEMS  = 'field_66009d41fec38';
	private const LEGACY_SERVICE_TITLE = 'field_66052155a31f0';
	private const LEGACY_SERVICE_ITEMS = 'field_66052169a31f1';
	private const DEFAULT_SHIPMENTS  = 'field_platejka_defaults_shipments_v1';
	private const DEFAULT_CASES      = 'field_platejka_defaults_cases_v1';
	private const BUILDER_ENABLED    = 'field_platejka_page_builder_v1';
	private const BUILDER_ROWS       = 'field_platejka_sections_v1';

	/** @param array<int,string> $args @param array<string,mixed> $assoc_args */
	public function __invoke( array $args, array $assoc_args ): void {
		unset( $args );
		$apply  = isset( $assoc_args['apply'] );
		$report = self::report( $apply );
		\WP_CLI::line( (string) wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
		\WP_CLI::success( $apply ? 'Legacy services and cases migration applied and verified.' : 'Dry-run only; no writes performed.' );
	}

	/** @return array<string,mixed> */
	public static function preview(): array {
		return self::report( false );
	}

	/** @return array<string,mixed> */
	public static function apply(): array {
		return self::report( true );
	}

	/** Only real editorial page states are part of this migration. */
	public static function isEligibleStatus( string $status ): bool {
		return in_array( $status, array( 'publish', 'future', 'draft', 'pending', 'private' ), true );
	}

	/** Services belong only to pages rendered by the default page.php template. */
	public static function usesDefaultTemplate( string $template ): bool {
		return '' === $template || 'default' === $template;
	}

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

	/** @return array<string,mixed> */
	private static function report( bool $apply ): array {
		if ( ! function_exists( 'get_field' ) || ! function_exists( 'update_field' ) ) {
			throw new RuntimeException( 'ACF is required for the legacy sections migration.' );
		}

		$already_migrated = 1 === (int) get_option( LegacyFieldVisibility::VERSION_OPTION, 0 );
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

		$writes    = array();
		$pages     = array();
		$conflicts = array();
		$totals    = array(
			'eligible_pages'       => count( $page_ids ),
			'pages_with_source'    => 0,
			'home_shipment_items'  => 0,
			'global_case_items'    => 0,
			'local_shipment_items' => 0,
			'local_case_items'     => 0,
			'conflicts'            => 0,
			'writes'               => 0,
		);

		$seed = SectionSeed::get()['sections'] ?? array();
		$current_shipments = self::readFieldArray( self::DEFAULT_SHIPMENTS, self::OPTIONS_ID );
		$current_cases     = self::readFieldArray( self::DEFAULT_CASES, self::OPTIONS_ID );
		$target_shipments  = $current_shipments;
		$target_cases      = $current_cases;

		$home_services = self::readFieldArray( self::LEGACY_HOME_ITEMS, self::HOME_ID );
		$home_case_title = (string) self::readField( self::LEGACY_CASE_TITLE, self::HOME_ID );
		$home_cases       = self::readFieldArray( self::LEGACY_CASE_ITEMS, self::HOME_ID );
		$home_has_source  = self::hasMeaningfulValue( $home_services ) || self::hasMeaningfulValue( $home_case_title ) || self::hasMeaningfulValue( $home_cases );

		if ( self::hasMeaningfulValue( $home_services ) ) {
			if ( ! self::hasMeaningfulValue( $target_shipments ) && is_array( $seed['shipments'] ?? null ) ) {
				$target_shipments = $seed['shipments'];
			}
			$main = isset( $target_shipments['main'] ) && is_array( $target_shipments['main'] ) ? $target_shipments['main'] : array();
			$current_items = isset( $main['items'] ) && is_array( $main['items'] ) ? $main['items'] : array();
			$main['items'] = self::mapHomeShipmentItems( $home_services, $current_items );
			$target_shipments['main'] = $main;
			$totals['home_shipment_items'] = count( $main['items'] );
		}

		if ( self::hasMeaningfulValue( $home_case_title ) || self::hasMeaningfulValue( $home_cases ) ) {
			if ( ! self::hasMeaningfulValue( $target_cases ) && is_array( $seed['cases'] ?? null ) ) {
				$target_cases = $seed['cases'];
			}
			if ( self::hasMeaningfulValue( $home_case_title ) ) {
				$target_cases['title'] = $home_case_title;
			}
			if ( self::hasMeaningfulValue( $home_cases ) ) {
				$mapped = self::mapCases( $home_case_title, $home_cases );
				$target_cases['items'] = $mapped['items'];
				$totals['global_case_items'] = count( $mapped['items'] );
			}
		}

		self::planWrite( $writes, self::OPTIONS_ID, self::DEFAULT_SHIPMENTS, $current_shipments, $target_shipments );
		self::planWrite( $writes, self::OPTIONS_ID, self::DEFAULT_CASES, $current_cases, $target_cases );

		foreach ( $page_ids as $page_id_value ) {
			$page_id = (int) $page_id_value;
			$status  = (string) get_post_status( $page_id );
			if ( ! self::isEligibleStatus( $status ) ) {
				continue;
			}

			$current_rows    = self::readFieldArray( self::BUILDER_ROWS, $page_id );
			$current_enabled = (bool) self::readField( self::BUILDER_ENABLED, $page_id );
			$shipment_target = null;
			$case_target     = null;
			$source          = array();

			if ( self::HOME_ID === $page_id ) {
				if ( ! $home_has_source ) {
					continue;
				}
				$replacement = self::upsertBuilderRows( $current_rows, null, null, true );
				$source[]    = 'home';
			} else {
				$template = (string) get_page_template_slug( $page_id );
				if ( self::usesDefaultTemplate( $template ) ) {
					$service_title = (string) self::readField( self::LEGACY_SERVICE_TITLE, $page_id );
					$service_items = self::readFieldArray( self::LEGACY_SERVICE_ITEMS, $page_id );
					if ( self::hasMeaningfulValue( $service_title ) || self::hasMeaningfulValue( $service_items ) ) {
						$shipment_target = self::mapInternalShipments( $service_title, $service_items );
						$totals['local_shipment_items'] += count( $shipment_target['items'] );
						$source[] = 'shipments';
					}
				}

				$case_title = (string) self::readField( self::LEGACY_CASE_TITLE, $page_id );
				$case_items = self::readFieldArray( self::LEGACY_CASE_ITEMS, $page_id );
				if ( self::hasMeaningfulValue( $case_title ) || self::hasMeaningfulValue( $case_items ) ) {
					$case_target = self::mapCases( $case_title, $case_items );
					$totals['local_case_items'] += count( $case_target['items'] );
					$source[] = 'cases';
				}
				if ( null === $shipment_target && null === $case_target ) {
					continue;
				}
				$replacement = self::upsertBuilderRows( $current_rows, $shipment_target, $case_target, false );
			}

			++$totals['pages_with_source'];
			foreach ( $replacement['conflicts'] as $conflict ) {
				$conflict['page_id'] = $page_id;
				$conflicts[] = $conflict;
			}
			$rows_changed   = self::acfStable( $current_rows ) !== self::acfStable( $replacement['rows'] );
			$enable_changed = ! $current_enabled;
			if ( $rows_changed ) {
				self::planWrite( $writes, $page_id, self::BUILDER_ROWS, $current_rows, $replacement['rows'] );
			}
			if ( $enable_changed ) {
				self::planWrite( $writes, $page_id, self::BUILDER_ENABLED, false, true );
			}
			$pages[] = array(
				'id'              => $page_id,
				'title'           => get_the_title( $page_id ),
				'status'          => $status,
				'template'        => (string) get_page_template_slug( $page_id ),
				'source'          => $source,
				'rows_changed'    => $rows_changed,
				'enable_changed'  => $enable_changed,
				'target_sections' => array_values( array_filter( array_column( $replacement['rows'], 'acf_fc_layout' ), 'is_string' ) ),
			);
		}

		$totals['conflicts'] = count( $conflicts );
		if ( ! $already_migrated ) {
			$writes[] = array(
				'post_id' => 'wp_options',
				'field'   => LegacyFieldVisibility::VERSION_OPTION,
				'current' => 0,
				'target'  => 1,
			);
		}
		$totals['writes'] = count( $writes );

		if ( $apply ) {
			self::performWrites( $writes );
			self::verifyWrites( $writes );
			if ( ! $already_migrated ) {
				update_option( LegacyFieldVisibility::VERSION_OPTION, 1, false );
				if ( 1 !== (int) get_option( LegacyFieldVisibility::VERSION_OPTION, 0 ) ) {
					throw new RuntimeException( 'Content was saved, but the legacy-field completion marker could not be verified.' );
				}
			}
		}

		return array(
			'mode'             => $apply ? 'apply' : 'dry-run',
			'already_migrated' => $already_migrated,
			'totals'           => $totals,
			'pages'            => $pages,
			'conflicts'        => $conflicts,
			'writes'           => array( 'performed' => $apply && 0 < count( $writes ), 'planned' => $writes ),
		);
	}

	/** @param string|int $post_id @return mixed */
	private static function readField( string $field, $post_id ) {
		return self::normalizeRawValue( get_field( $field, $post_id, false ) );
	}

	/** @param string|int $post_id @return array<mixed> */
	private static function readFieldArray( string $field, $post_id ): array {
		$value = self::readField( $field, $post_id );
		return is_array( $value ) ? $value : array();
	}

	/** @param array<int,array<string,mixed>> $writes @param string|int $post_id @param mixed $current @param mixed $target */
	private static function planWrite( array &$writes, $post_id, string $field, $current, $target ): void {
		if ( self::acfStable( $current ) === self::acfStable( $target ) ) {
			return;
		}
		$writes[] = compact( 'post_id', 'field', 'current', 'target' );
	}

	/** @param array<int,array<string,mixed>> $writes */
	private static function performWrites( array $writes ): void {
		foreach ( $writes as $write ) {
			if ( 'wp_options' === $write['post_id'] ) {
				continue;
			}
			update_field( $write['field'], $write['target'], $write['post_id'] );
		}
	}

	/** @param array<int,array<string,mixed>> $writes */
	private static function verifyWrites( array $writes ): void {
		foreach ( $writes as $write ) {
			if ( 'wp_options' === $write['post_id'] ) {
				continue;
			}
			$actual = self::readField( $write['field'], $write['post_id'] );
			if ( self::acfStable( $actual ) !== self::acfStable( $write['target'] ) ) {
				throw new RuntimeException( sprintf( 'Migration verification failed for %s on %s.', $write['field'], (string) $write['post_id'] ) );
			}
		}
	}

	/** @param mixed $value */
	private static function acfStable( $value ): string {
		$compact = static function ( $current ) use ( &$compact ) {
			if ( ! is_array( $current ) ) {
				return $current;
			}
			$result = array();
			foreach ( $current as $key => $child ) {
				if ( ! self::hasMeaningfulValue( $child ) ) {
					continue;
				}
				if ( array_is_list( $current ) ) {
					$result[] = $compact( $child );
				} else {
					$result[ $key ] = $compact( $child );
				}
			}
			if ( ! array_is_list( $result ) ) {
				ksort( $result, SORT_STRING );
			}
			return $result;
		};
		return self::stable( $compact( $value ) );
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
