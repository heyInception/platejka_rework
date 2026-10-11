<?php

namespace Platejka\Core\Cli;

use RuntimeException;

defined( 'ABSPATH' ) || exit;

/** Preview-first migration for compact internal-page section assemblies. */
final class InternalPageAssemblyMigrationCommand {
	public const VERSION_OPTION = 'platejka_internal_page_assembly_version';

	private const BUILDER_ENABLED = 'field_platejka_page_builder_v1';
	private const BUILDER_ROWS    = 'field_platejka_sections_v1';
	private const LEGACY_PROCESS  = 'field_6790b8aa79094';

	/** @param array<int,string> $args @param array<string,mixed> $assoc_args */
	public function __invoke( array $args, array $assoc_args ): void {
		unset( $args );
		$apply  = isset( $assoc_args['apply'] );
		$report = self::report( $apply );
		\WP_CLI::line( (string) wp_json_encode( self::summarizeReport( $report ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
		\WP_CLI::success( ! empty( $report['already_migrated'] ) ? 'Internal-page assembly migration was already applied; no writes performed.' : ( $apply ? 'Internal-page assembly migration applied and verified.' : 'Dry-run only; no writes performed.' ) );
	}

	/** @return array<string,mixed> */
	public static function preview(): array {
		return self::report( false );
	}

	/** @return array<string,mixed> */
	public static function apply(): array {
		return self::report( true );
	}

	/** @param array<string,mixed> $report @return array<string,mixed> */
	public static function summarizeReport( array $report ): array {
		$summary = $report;
		foreach ( (array) ( $summary['pages'] ?? array() ) as $index => $page ) {
			if ( is_array( $page ) ) {
				unset( $page['target_rows'] );
				$summary['pages'][ $index ] = $page;
			}
		}
		foreach ( (array) ( $summary['writes']['planned'] ?? array() ) as $index => $write ) {
			if ( ! is_array( $write ) ) {
				continue;
			}
			$compact = array( 'post_id' => $write['post_id'] ?? 0, 'field' => $write['field'] ?? '' );
			if ( self::BUILDER_ROWS !== ( $write['field'] ?? null ) ) {
				$compact['from'] = $write['current'] ?? null;
				$compact['to']   = $write['target'] ?? null;
			}
			$summary['writes']['planned'][ $index ] = $compact;
		}
		return $summary;
	}

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
			if ( null !== $target && self::acfStable( $rows ) === self::acfStable( $target ) ) {
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

	/** @return array<string,mixed> */
	private static function report( bool $apply ): array {
		if ( ! function_exists( 'get_field' ) || ! function_exists( 'update_field' ) ) {
			throw new RuntimeException( 'ACF is required for the internal-page assembly migration.' );
		}

		$already_migrated = 1 === (int) get_option( self::VERSION_OPTION, 0 );
		if ( $already_migrated ) {
			return self::emptyReport( $apply, true );
		}

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

		$pages     = array();
		$excluded  = array();
		$conflicts = array();
		$writes    = array();
		foreach ( $page_ids as $page_id_value ) {
			$page_id = (int) $page_id_value;
			$status  = (string) get_post_status( $page_id );
			if ( ! self::isEligibleStatus( $status ) ) {
				continue;
			}

			$template = (string) get_page_template_slug( $page_id );
			if ( ! in_array( $template, array( '', 'default' ), true ) ) {
				$excluded[] = self::pageSummary( $page_id, $status, 'special_template', array() );
				continue;
			}

			$builder_enabled = (bool) get_field( self::BUILDER_ENABLED, $page_id, false );
			if ( ! $builder_enabled ) {
				$excluded[] = self::pageSummary( $page_id, $status, 'builder_disabled', array() );
				continue;
			}

			$raw_rows = get_field( self::BUILDER_ROWS, $page_id, false );
			$rows     = is_array( $raw_rows ) ? self::normalizeRawValue( $raw_rows ) : array();
			$slugs    = self::rowSlugs( $rows );
			$raw_process = get_field( self::LEGACY_PROCESS, $page_id, false );
			$process  = self::mapLegacyProcess( self::normalizeRawValue( $raw_process ) );
			$result   = self::reconcileRows( $rows, $process );
			$compact  = self::isCompactSequence( $rows );

			if ( ! $compact && ! empty( $result['conflicts'] ) ) {
				$only_compact_slugs = array() === array_diff( $slugs, array( 'hero', 'shipments', 'cases', 'faq' ) );
				if ( $only_compact_slugs && in_array( 'hero', $slugs, true ) && in_array( 'faq', $slugs, true ) ) {
					$conflicts[] = array_merge( self::pageSummary( $page_id, $status, 'invalid_compact_sequence', $slugs ), array( 'details' => $result['conflicts'] ) );
				} else {
					$excluded[] = self::pageSummary( $page_id, $status, 'special_composition', $slugs );
				}
				continue;
			}

			$target_rows = $result['rows'];
			$page        = self::pageSummary( $page_id, $status, 'eligible', $slugs );
			$page['target_sections'] = self::rowSlugs( $target_rows );
			$page['target_rows']     = $target_rows;
			$page['process_mapped']  = null !== $process;
			$pages[] = $page;

			if ( self::acfStable( $rows ) !== self::acfStable( $target_rows ) ) {
				$writes[] = array(
					'post_id' => $page_id,
					'field'   => self::BUILDER_ROWS,
					'current' => $rows,
					'target'  => $target_rows,
				);
			}
		}

		$writes[] = array( 'post_id' => 'wp_options', 'field' => self::VERSION_OPTION, 'current' => 0, 'target' => 1 );
		if ( $apply && $conflicts ) {
			throw new RuntimeException( 'Internal-page assembly migration has unresolved conflicts; no writes were performed.' );
		}

		$performed = false;
		if ( $apply ) {
			foreach ( $writes as $write ) {
				if ( 'wp_options' !== $write['post_id'] ) {
					update_field( $write['field'], $write['target'], (int) $write['post_id'] );
				}
			}
			self::verifyWrites( $writes );
			update_option( self::VERSION_OPTION, 1, false );
			if ( 1 !== (int) get_option( self::VERSION_OPTION, 0 ) ) {
				throw new RuntimeException( 'Internal-page rows were verified, but the completion marker could not be saved.' );
			}
			$performed = true;
		}

		return array(
			'mode'             => $apply ? 'apply' : 'dry-run',
			'already_migrated' => false,
			'totals'           => array(
				'target_pages' => count( $pages ),
				'excluded'     => count( $excluded ),
				'conflicts'    => count( $conflicts ),
				'writes'       => count( $writes ),
			),
			'pages'            => $pages,
			'excluded'         => $excluded,
			'conflicts'        => $conflicts,
			'writes'           => array( 'performed' => $performed, 'planned' => $writes ),
		);
	}

	/** @return array<string,mixed> */
	private static function emptyReport( bool $apply, bool $already_migrated ): array {
		return array(
			'mode'             => $apply ? 'apply' : 'dry-run',
			'already_migrated' => $already_migrated,
			'totals'           => array( 'target_pages' => 0, 'excluded' => 0, 'conflicts' => 0, 'writes' => 0 ),
			'pages'            => array(),
			'excluded'         => array(),
			'conflicts'        => array(),
			'writes'           => array( 'performed' => false, 'planned' => array() ),
		);
	}

	/** @param array<int,string> $sections @return array<string,mixed> */
	private static function pageSummary( int $page_id, string $status, string $reason, array $sections ): array {
		return array(
			'id'               => $page_id,
			'title'            => get_the_title( $page_id ),
			'status'           => $status,
			'reason'           => $reason,
			'current_sections' => $sections,
		);
	}

	/** @param mixed $value */
	private static function acfStable( $value ): string {
		$compact = static function ( $current ) use ( &$compact ) {
			if ( ! is_array( $current ) ) {
				return $current;
			}
			$list   = array_is_list( $current );
			$result = array();
			foreach ( $current as $key => $child ) {
				if ( ! self::hasMeaningfulValue( $child ) ) {
					continue;
				}
				if ( $list ) {
					$result[] = $compact( $child );
				} else {
					$result[ $key ] = $compact( $child );
				}
			}
			if ( ! $list ) {
				ksort( $result, SORT_STRING );
			}
			return $result;
		};
		$compacted = $compact( $value );
		return function_exists( 'wp_json_encode' )
			? (string) wp_json_encode( $compacted, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
			: (string) json_encode( $compacted, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}

	/** @param mixed $value */
	private static function hasMeaningfulValue( $value ): bool {
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
		return null !== $value && false !== $value;
	}

	/** @param array<int,array<string,mixed>> $writes */
	private static function verifyWrites( array $writes ): void {
		foreach ( $writes as $write ) {
			if ( 'wp_options' === $write['post_id'] ) {
				continue;
			}
			$actual = get_field( $write['field'], (int) $write['post_id'], false );
			$actual = is_array( $actual ) ? self::normalizeRawValue( $actual ) : $actual;
			if ( self::acfStable( $actual ) !== self::acfStable( $write['target'] ) ) {
				throw new RuntimeException( 'Internal-page assembly verification failed on page ' . (int) $write['post_id'] . '.' );
			}
		}
	}
}
