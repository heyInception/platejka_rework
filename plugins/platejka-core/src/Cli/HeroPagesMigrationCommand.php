<?php

namespace Platejka\Core\Cli;

use RuntimeException;

defined( 'ABSPATH' ) || exit;

/** Preview-first migration of non-China hero rows to the new internal-pages variant. */
final class HeroPagesMigrationCommand {
	private const FIELD = 'field_platejka_sections_v1';
	private const BACKUP_META = '_platejka_hero_pages_migration_backup_v1';
	private const CHINA_PAGE_IDS = array( 1873, 2109, 2075 );

	/** @param array<int,string> $args @param array<string,mixed> $assoc_args */
	public function __invoke( array $args, array $assoc_args ): void {
		unset( $args );
		$apply = isset( $assoc_args['apply'] );
		$report = self::report( $apply, isset( $assoc_args['page'] ) ? absint( $assoc_args['page'] ) : 0 );
		\WP_CLI::line( (string) wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
		\WP_CLI::success( $apply ? 'Hero pages migration applied and verified.' : 'Dry-run only; no writes performed.' );
	}

	/** @return array<string,mixed> */
	public static function preview(): array {
		return self::report( false );
	}

	/** @return array<string,mixed> */
	public static function apply(): array {
		return self::report( true );
	}

	/** @return array<string,mixed> */
	private static function report( bool $apply, int $only_page = 0 ): array {
		if ( ! function_exists( 'get_field' ) || ! function_exists( 'update_field' ) || ! function_exists( 'platejka_resolve_section_data' ) ) {
			throw new RuntimeException( 'ACF and the active Platejka theme are required.' );
		}
		$page_ids = get_posts( array(
			'post_type' => 'page',
			'post_status' => array( 'publish', 'future', 'draft', 'pending', 'private' ),
			'fields' => 'ids',
			'posts_per_page' => -1,
			'orderby' => 'ID',
			'order' => 'ASC',
		) );
		$planned = array();
		$conflicts = array();
		$skipped_china = array();
		foreach ( $page_ids as $page_id_value ) {
			$page_id = (int) $page_id_value;
			if ( $only_page && $only_page !== $page_id ) {
				continue;
			}
			$rows = get_field( self::FIELD, $page_id );
			if ( ! is_array( $rows ) ) {
				continue;
			}
			$hero_indexes = array();
			foreach ( $rows as $index => $row ) {
				if ( is_array( $row ) && 'hero' === ( $row['acf_fc_layout'] ?? '' ) ) {
					$hero_indexes[] = $index;
				}
			}
			if ( ! $hero_indexes ) {
				continue;
			}
			if ( in_array( $page_id, self::CHINA_PAGE_IDS, true ) ) {
				$skipped_china[] = $page_id;
				continue;
			}
			if ( 1 !== count( $hero_indexes ) ) {
				$conflicts[] = array( 'page_id' => $page_id, 'reason' => 'multiple_hero_rows' );
				continue;
			}
			$index = $hero_indexes[0];
			$row = $rows[ $index ];
			$resolved = platejka_resolve_section_data( array( 'slug' => 'hero', 'mode' => 'default', 'row' => $row ), $page_id );
			$copy = array_intersect_key( $resolved, array_fill_keys( array( 'badge', 'title', 'subtitle', 'info', 'experience', 'registry', 'association' ), true ) );
			$copy['background_color'] = '#e3eeeb';
			$copy['background_image'] = 0;
			$copy['blur_image'] = 0;
			$copy['items_list'] = '';
			$new_row = array(
				'acf_fc_layout' => 'hero-pages',
				'enabled' => $row['enabled'] ?? 1,
				'anchor' => $row['anchor'] ?? '',
				'overrides' => array( 'hero_pages' => $copy ),
			);
			$target = $rows;
			$target[ $index ] = $new_row;
			$planned[] = array(
				'page_id' => $page_id,
				'title' => get_the_title( $page_id ),
				'row_index' => $index,
				'current' => $row,
				'target' => $new_row,
				'rows' => $target,
			);
		}
		if ( $apply && $conflicts ) {
			throw new RuntimeException( 'Conflicting hero rows found; no writes performed.' );
		}
		if ( $apply && $planned ) {
			foreach ( $planned as $change ) {
				$page_id = (int) $change['page_id'];
				add_post_meta( $page_id, self::BACKUP_META, get_field( self::FIELD, $page_id, false ), true );
				update_field( self::FIELD, $change['rows'], $page_id );
				$verify = get_field( self::FIELD, $page_id );
				if ( ! is_array( $verify ) || 'hero-pages' !== ( $verify[ $change['row_index'] ]['acf_fc_layout'] ?? '' ) ) {
					throw new RuntimeException( 'Migration verification failed for page ' . $page_id );
				}
				$verify_title = $verify[ $change['row_index'] ]['overrides']['hero_pages']['title']['primary'] ?? '';
				$expected_title = $change['target']['overrides']['hero_pages']['title']['primary'] ?? '';
				if ( $verify_title !== $expected_title ) {
					throw new RuntimeException( 'Title verification failed for page ' . $page_id );
				}
			}
		}
		foreach ( $planned as &$change ) {
			unset( $change['rows'] );
		}
		unset( $change );
		return array(
			'mode' => $apply ? 'apply' : 'dry-run',
			'planned_count' => count( $planned ),
			'pages' => $planned,
			'china_page_ids_unchanged' => $skipped_china,
			'conflicts' => $conflicts,
			'backup_meta' => self::BACKUP_META,
		);
	}
}
