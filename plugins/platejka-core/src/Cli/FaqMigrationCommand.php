<?php

namespace Platejka\Core\Cli;

use RuntimeException;

defined( 'ABSPATH' ) || exit;

/** Migrate legacy two-column FAQ repeaters into the page section builder. */
final class FaqMigrationCommand {
	private const LEGACY_COLUMN_1 = 'field_65a95b8fc77c8';
	private const LEGACY_COLUMN_2 = 'field_65a95bcbc77c9';
	private const BUILDER_ENABLED = 'field_platejka_page_builder_v1';
	private const BUILDER_ROWS    = 'field_platejka_sections_v1';

	/** @param array<int,string> $args @param array<string,mixed> $assoc_args */
	public function __invoke( array $args, array $assoc_args ): void {
		unset( $args );
		$apply  = isset( $assoc_args['apply'] );
		$report = self::report( $apply );
		\WP_CLI::line( (string) wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
		\WP_CLI::success( $apply ? 'FAQ migration applied and verified.' : 'Dry-run only; no writes performed.' );
	}

	/** @return array<string,mixed> */
	public static function preview(): array {
		return self::report( false );
	}

	/** @return array<string,mixed> */
	public static function apply(): array {
		return self::report( true );
	}

	/**
	 * @param array<int,mixed> $column_1
	 * @param array<int,mixed> $column_2
	 * @return array{items:array<int,array{title:string,text:string}>,warnings:array<int,array<string,mixed>>}
	 */
	public static function mergeLegacyRows( array $column_1, array $column_2 ): array {
		$items    = array();
		$warnings = array();
		$columns  = array(
			array( 'name' => 'repeater_column_1', 'rows' => $column_1, 'title' => 'column1_btn', 'text' => 'column1_text' ),
			array( 'name' => 'repeater_column_2', 'rows' => $column_2, 'title' => 'column2_btn', 'text' => 'column2_text' ),
		);

		foreach ( $columns as $column ) {
			foreach ( $column['rows'] as $index => $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$title = (string) ( $row[ $column['title'] ] ?? '' );
				$text  = (string) ( $row[ $column['text'] ] ?? '' );
				if ( '' === trim( $title ) && '' === trim( $text ) ) {
					continue;
				}
				$items[] = array( 'title' => $title, 'text' => $text );
				if ( '' === trim( $title ) || '' === trim( $text ) ) {
					$warnings[] = array(
						'type'    => 'partial_row',
						'column'  => $column['name'],
						'row'     => $index + 1,
						'missing' => '' === trim( $title ) ? 'question' : 'answer',
					);
				}
			}
		}

		return array( 'items' => $items, 'warnings' => $warnings );
	}

	/**
	 * @param array<int,mixed>                    $rows
	 * @param array<int,array{title:string,text:string}> $items
	 * @return array{rows:array<int,mixed>,faq_rows:int,created:bool}
	 */
	public static function replaceFaqRows( array $rows, array $items ): array {
		$faq_rows = 0;
		foreach ( $rows as &$row ) {
			if ( ! is_array( $row ) || 'faq' !== ( $row['acf_fc_layout'] ?? null ) ) {
				continue;
			}
			++$faq_rows;
			$row['enabled'] = 1;
			if ( ! isset( $row['overrides'] ) || ! is_array( $row['overrides'] ) ) {
				$row['overrides'] = array();
			}
			if ( ! isset( $row['overrides']['faq'] ) || ! is_array( $row['overrides']['faq'] ) ) {
				$row['overrides']['faq'] = array();
			}
			$row['overrides']['faq']['items'] = $items;
		}
		unset( $row );

		if ( $faq_rows ) {
			return array( 'rows' => array_values( $rows ), 'faq_rows' => $faq_rows, 'created' => false );
		}

		$faq = array(
			'acf_fc_layout' => 'faq',
			'enabled'       => 1,
			'anchor'        => '',
			'overrides'     => array(
				'faq' => array(
					'title'        => '',
					'description'  => '',
					'primary_link' => '',
					'items'        => $items,
				),
			),
		);
		$insert_at = count( $rows );
		foreach ( $rows as $index => $row ) {
			if ( is_array( $row ) && 'call' === ( $row['acf_fc_layout'] ?? null ) ) {
				$insert_at = $index;
				break;
			}
		}
		array_splice( $rows, $insert_at, 0, array( $faq ) );

		return array( 'rows' => array_values( $rows ), 'faq_rows' => 1, 'created' => true );
	}

	/**
	 * Convert raw ACF field-key arrays to stable field-name arrays without applying display formatting.
	 *
	 * @param mixed                                      $value
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

	/** @return array<string,mixed> */
	private static function report( bool $apply ): array {
		if ( ! function_exists( 'get_field' ) || ! function_exists( 'update_field' ) ) {
			throw new RuntimeException( 'ACF is required for the FAQ migration.' );
		}

		$page_ids = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => array( 'publish', 'future', 'draft', 'pending', 'private' ),
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'OR',
					array( 'key' => 'repeater_column_1', 'compare' => 'EXISTS' ),
					array( 'key' => 'repeater_column_2', 'compare' => 'EXISTS' ),
				),
			)
		);

		$pages   = array();
		$planned = array();
		$totals  = array( 'candidates' => count( $page_ids ), 'migrated' => 0, 'items' => 0, 'warnings' => 0, 'writes' => 0 );

		foreach ( $page_ids as $page_id ) {
			$page_id  = (int) $page_id;
			$column_1 = self::normalizeRawValue( get_field( self::LEGACY_COLUMN_1, $page_id, false ) );
			$column_2 = self::normalizeRawValue( get_field( self::LEGACY_COLUMN_2, $page_id, false ) );
			$merged   = self::mergeLegacyRows( is_array( $column_1 ) ? $column_1 : array(), is_array( $column_2 ) ? $column_2 : array() );
			if ( ! $merged['items'] ) {
				continue;
			}

			$current_rows    = self::normalizeRawValue( get_field( self::BUILDER_ROWS, $page_id, false ) );
			$current_rows    = is_array( $current_rows ) ? $current_rows : array();
			$current_enabled = (bool) get_field( self::BUILDER_ENABLED, $page_id, false );
			$replacement     = self::replaceFaqRows( $current_rows, $merged['items'] );
			$rows_changed    = self::stable( $current_rows ) !== self::stable( $replacement['rows'] );
			$enable_changed  = ! $current_enabled;

			if ( $rows_changed ) {
				$planned[] = array(
					'post_id' => $page_id,
					'field'   => self::BUILDER_ROWS,
					'current' => $current_rows,
					'target'  => $replacement['rows'],
				);
			}
			if ( $enable_changed ) {
				$planned[] = array(
					'post_id' => $page_id,
					'field'   => self::BUILDER_ENABLED,
					'current' => false,
					'target'  => true,
				);
			}

			if ( $apply ) {
				if ( $rows_changed ) {
					update_field( self::BUILDER_ROWS, $replacement['rows'], $page_id );
				}
				if ( $enable_changed ) {
					update_field( self::BUILDER_ENABLED, 1, $page_id );
				}
				self::verifyPage( $page_id, $replacement['rows'] );
			}

			$pages[] = array(
				'id'              => $page_id,
				'title'           => get_the_title( $page_id ),
				'status'          => get_post_status( $page_id ),
				'legacy_column_1' => is_array( $column_1 ) ? count( $column_1 ) : 0,
				'legacy_column_2' => is_array( $column_2 ) ? count( $column_2 ) : 0,
				'target_items'    => count( $merged['items'] ),
				'faq_rows'        => $replacement['faq_rows'],
				'created'         => $replacement['created'],
				'rows_changed'    => $rows_changed,
				'builder_enabled' => true,
				'enable_changed'  => $enable_changed,
				'warnings'        => $merged['warnings'],
			);
			++$totals['migrated'];
			$totals['items']    += count( $merged['items'] );
			$totals['warnings'] += count( $merged['warnings'] );
			$totals['writes']   += (int) $rows_changed + (int) $enable_changed;
		}

		return array(
			'mode'   => $apply ? 'apply' : 'dry-run',
			'totals' => $totals,
			'pages'  => $pages,
			'writes' => array( 'performed' => $apply && 0 < $totals['writes'], 'planned' => $planned ),
		);
	}

	/** @param array<int,mixed> $target_rows */
	private static function verifyPage( int $page_id, array $target_rows ): void {
		$actual_rows = self::normalizeRawValue( get_field( self::BUILDER_ROWS, $page_id, false ) );
		$enabled     = (bool) get_field( self::BUILDER_ENABLED, $page_id, false );
		if ( ! $enabled || self::stable( is_array( $actual_rows ) ? $actual_rows : array() ) !== self::stable( $target_rows ) ) {
			throw new RuntimeException( 'FAQ migration verification failed for page ' . $page_id . '.' );
		}
	}

	/** @param mixed $value */
	private static function stable( $value ): string {
		return (string) wp_json_encode( $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}
}
