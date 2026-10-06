<?php

namespace Platejka\Core\Cli;

use Platejka\Core\Acf\SectionSeed;
use RuntimeException;

defined( 'ABSPATH' ) || exit;

/** Deterministic preview/apply migration for the new ACF section builder. */
final class SectionBuilderMigrationCommand {
	private const PAGE_ID = 24;
	private const OPTIONS_ID = 'platejka_section_defaults';
	private const VERSION_OPTION = 'platejka_section_builder_version';
	private const ADDITIVE_VERSION_OPTION = 'platejka_section_builder_additive_version';
	private const ADDITIVE_VERSION = 6;

	/** @param array<int,string> $args @param array<string,mixed> $assoc_args */
	public function __invoke( array $args, array $assoc_args ): void {
		unset( $args );
		$apply  = isset( $assoc_args['apply'] );
		$report = $apply ? self::apply() : self::preview();
		\WP_CLI::line( (string) wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
		\WP_CLI::success( $apply ? 'Section builder migration applied.' : 'Dry-run only; no writes performed.' );
	}

	/** @return array<string,mixed> */
	public static function preview(): array {
		return self::report( false );
	}

	/** @return array<string,mixed> */
	public static function apply(): array {
		if ( ! function_exists( 'update_field' ) ) {
			throw new RuntimeException( 'ACF is required for the section builder migration.' );
		}
		return self::report( true );
	}

	/** @return array<string,mixed> */
	private static function report( bool $apply ): array {
		$seed = SectionSeed::get();
		if ( 1 !== ( $seed['schema_version'] ?? null ) || 18 !== count( $seed['sections'] ?? array() ) || 18 !== count( $seed['home_rows'] ?? array() ) ) {
			throw new RuntimeException( 'Section seed is incomplete or has an unsupported version.' );
		}

		$already_migrated = 1 === (int) get_option( self::VERSION_OPTION, 0 );
		$stored_additive_version = (int) get_option( self::ADDITIVE_VERSION_OPTION, 0 );
		$additive_pending = $already_migrated && self::ADDITIVE_VERSION > $stored_additive_version;
		$migration_pending = ! $already_migrated || $additive_pending;
		$sources          = self::mediaSources( $seed['sections'] );
		$reused  = array();
		$missing = array();
		$media_map = array();
		foreach ( $sources as $source ) {
			$attachment_id = self::attachmentForSource( $source );
			if ( $attachment_id ) {
				$reused[] = array( 'source' => $source, 'attachment_id' => $attachment_id );
				$media_map[ $source ] = $attachment_id;
			} else {
				$missing[] = $source;
			}
		}

		$imported = array();
		if ( $apply && $migration_pending ) {
			foreach ( $missing as $source ) {
				$attachment_id = self::importThemeMedia( $source );
				$media_map[ $source ] = $attachment_id;
				$imported[] = array( 'source' => $source, 'attachment_id' => $attachment_id );
			}
		}

		$target_sections = self::replaceMediaSources( $seed['sections'], $media_map );
		$target_rows     = $seed['home_rows'];
		$sections_order  = array_values( array_map( static fn( array $row ): string => (string) $row['acf_fc_layout'], $target_rows ) );
		$ordered_sections = array();
		foreach ( $sections_order as $slug ) {
			$ordered_sections[ $slug ] = $target_sections[ $slug ];
		}
		$target_sections = $ordered_sections;
		$writes = array(
			'performed' => false,
			'options'   => array(),
			'page'      => array(),
			'version'   => false,
			'additive_version' => false,
			'planned'   => array(),
		);
		$write_targets = array();
		foreach ( $target_sections as $slug => $value ) {
			$field_key = 'field_platejka_defaults_' . str_replace( '-', '_', $slug ) . '_v1';
			$current   = get_field( $field_key, self::OPTIONS_ID, false );
			$write_value = $value;
			if ( $already_migrated ) {
				$write_value = self::normalizeAcfValue( is_array( $current ) ? $current : array() );
				foreach ( $additive_pending ? self::additiveFields( $slug ) : array() as $field_name ) {
					if ( ! self::hasStoredOptionField( $slug, $field_name ) && array_key_exists( $field_name, $value ) ) {
						$write_value[ $field_name ] = $value[ $field_name ];
					}
				}
				if ( $additive_pending && 2 > $stored_additive_version ) {
					$label_keys = self::versionTwoLabelKeys( $slug );
					if ( $label_keys && ! empty( $write_value['labels'] ) && is_array( $write_value['labels'] ) ) {
						$write_value['labels'] = self::mergeMissingLabels( $write_value['labels'], is_array( $value['labels'] ?? null ) ? $value['labels'] : array(), $label_keys );
					}
					if ( 'review-main' === $slug && ! empty( $write_value['items'] ) && is_array( $write_value['items'] ) ) {
						$write_value['items'] = self::upgradeReviewItems( $write_value['items'], is_array( $value['items'] ?? null ) ? $value['items'] : array() );
					}
				}
				if ( $additive_pending && 6 > $stored_additive_version && 'review-main' === $slug && ! empty( $write_value['labels'] ) && is_array( $write_value['labels'] ) ) {
					$write_value['labels'] = self::mergeMissingLabels( $write_value['labels'], is_array( $value['labels'] ?? null ) ? $value['labels'] : array(), array( 'tablet_lead' ) );
				}
				if ( $additive_pending && 'table' === $slug && 4 > $stored_additive_version && ! empty( $write_value['items'] ) && is_array( $write_value['items'] ) ) {
					$write_value['items'] = self::upgradeTableItems( $write_value['items'], is_array( $value['items'] ?? null ) ? $value['items'] : array() );
				}
			}
			$changed = self::acfStable( $current ) !== self::acfStable( $write_value );
			$writes['options'][ $slug ] = $changed;
			$write_targets[ $slug ]     = $write_value;
			if ( $changed ) {
				$writes['planned'][] = array(
					'post_id' => self::OPTIONS_ID,
					'field'   => $field_key,
					'current' => self::normalizeAcfValue( $current ),
					'target'  => self::normalizeAcfValue( $write_value ),
				);
			}
		}

		$current_enabled = (bool) get_field( 'field_platejka_page_builder_v1', self::PAGE_ID, false );
		$current_rows    = get_field( 'field_platejka_sections_v1', self::PAGE_ID, false );
		$enable_changed  = ! $already_migrated && ! $current_enabled;
		$rows_changed    = ! $already_migrated && self::acfStable( is_array( $current_rows ) ? $current_rows : array() ) !== self::acfStable( $target_rows );
		$writes['page']  = array( 'builder' => $enable_changed, 'rows' => $rows_changed );
		if ( $enable_changed ) {
			$writes['planned'][] = array( 'post_id' => self::PAGE_ID, 'field' => 'field_platejka_page_builder_v1', 'current' => $current_enabled, 'target' => true );
		}
		if ( $rows_changed ) {
			$writes['planned'][] = array(
				'post_id' => self::PAGE_ID,
				'field'   => 'field_platejka_sections_v1',
				'current' => self::normalizeAcfValue( is_array( $current_rows ) ? $current_rows : array() ),
				'target'  => self::normalizeAcfValue( $target_rows ),
			);
		}
		$version_changed  = ! $already_migrated;
		$additive_changed = ! $already_migrated || $additive_pending;
		if ( $version_changed ) {
			$writes['planned'][] = array( 'post_id' => 'wp_options', 'field' => self::VERSION_OPTION, 'current' => (int) get_option( self::VERSION_OPTION, 0 ), 'target' => 1 );
		}
		if ( $additive_changed ) {
			$writes['planned'][] = array( 'post_id' => 'wp_options', 'field' => self::ADDITIVE_VERSION_OPTION, 'current' => $stored_additive_version, 'target' => self::ADDITIVE_VERSION );
		}

		if ( $apply ) {
			foreach ( $write_targets as $slug => $value ) {
				$field_key = 'field_platejka_defaults_' . str_replace( '-', '_', $slug ) . '_v1';
				$changed   = $writes['options'][ $slug ];
				if ( $changed ) {
					update_field( $field_key, $value, self::OPTIONS_ID );
				}
			}

			if ( ! $already_migrated && $enable_changed ) {
				update_field( 'field_platejka_page_builder_v1', 1, self::PAGE_ID );
			}
			if ( ! $already_migrated && $rows_changed ) {
				update_field( 'field_platejka_sections_v1', $target_rows, self::PAGE_ID );
			}
			self::verifyAppliedValues( $write_targets, $target_rows, ! $already_migrated );

			if ( $version_changed ) {
				update_option( self::VERSION_OPTION, 1, false );
				if ( 1 !== (int) get_option( self::VERSION_OPTION, 0 ) ) {
					throw new RuntimeException( 'Section builder values were written, but the migration marker could not be saved.' );
				}
			}
			if ( $additive_changed ) {
				update_option( self::ADDITIVE_VERSION_OPTION, self::ADDITIVE_VERSION, false );
				if ( self::ADDITIVE_VERSION !== (int) get_option( self::ADDITIVE_VERSION_OPTION, 0 ) ) {
					throw new RuntimeException( 'Section builder additive values were written, but their migration marker could not be saved.' );
				}
			}
			$writes['version']          = $version_changed;
			$writes['additive_version'] = $additive_changed;
			$writes['performed'] = (bool) ( $imported || $version_changed || $additive_changed || $enable_changed || $rows_changed || in_array( true, $writes['options'], true ) );
		}

		return array(
			'mode'           => $apply ? 'apply' : 'dry-run',
			'schema_version' => 1,
			'options'        => array(
				'post_id'  => self::OPTIONS_ID,
				'sections' => array_keys( $target_sections ),
				'cf7'      => array(
					'call'       => (int) ( $write_targets['call']['form'] ?? 0 ),
					'calculator' => (int) ( $write_targets['calculator']['form'] ?? 0 ),
				),
			),
			'page' => array(
				'id'              => self::PAGE_ID,
				'builder_enabled' => $already_migrated ? $current_enabled : true,
				'sections'        => $already_migrated && is_array( $current_rows ) ? array_values( array_filter( array_column( $current_rows, 'acf_fc_layout' ), 'is_string' ) ) : $sections_order,
				'rows'            => $already_migrated && is_array( $current_rows ) ? self::normalizeAcfValue( $current_rows ) : $target_rows,
			),
			'media' => $apply
				? array( 'reused' => $reused, 'imported' => $imported )
				: array( 'reused' => $reused, 'to_import' => $migration_pending ? $missing : array() ),
			'writes' => $writes,
		);
	}

	/** @param mixed $value @return array<int,string> */
	private static function mediaSources( $value ): array {
		$sources = array();
		$walk = static function ( $current ) use ( &$walk, &$sources ): void {
			if ( is_string( $current ) && str_starts_with( $current, 'theme://' ) ) {
				$sources[ $current ] = true;
				return;
			}
			if ( is_array( $current ) ) {
				foreach ( $current as $child ) { $walk( $child ); }
			}
		};
		$walk( $value );
		$sources = array_keys( $sources );
		sort( $sources, SORT_STRING );
		return $sources;
	}

	private static function attachmentForSource( string $source ): int {
		$ids = get_posts( array(
			'post_type' => 'attachment', 'post_status' => 'inherit', 'fields' => 'ids', 'posts_per_page' => 1,
			'meta_key' => '_platejka_section_seed_source', 'meta_value' => $source, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		) );
		if ( $ids ) {
			return (int) $ids[0];
		}

		$source_path = get_theme_file_path( ltrim( substr( $source, 8 ), '/' ) );
		if ( ! is_file( $source_path ) ) {
			return 0;
		}
		$candidates = get_posts( array(
			'post_type' => 'attachment', 'post_status' => 'inherit', 'fields' => 'ids', 'posts_per_page' => -1,
			'meta_key' => '_wp_attached_file', 'meta_value' => wp_basename( $source_path ), 'meta_compare' => 'LIKE', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		) );
		$source_hash = hash_file( 'sha256', $source_path );
		foreach ( $candidates as $candidate ) {
			$attached_path = get_attached_file( (int) $candidate );
			if ( is_string( $attached_path ) && is_file( $attached_path ) && hash_equals( $source_hash, hash_file( 'sha256', $attached_path ) ) ) {
				return (int) $candidate;
			}
		}
		return 0;
	}

	private static function importThemeMedia( string $source ): int {
		$relative = ltrim( substr( $source, 8 ), '/' );
		if ( ! preg_match( '~^[a-zA-Z0-9_./-]+$~', $relative ) || str_contains( $relative, '..' ) ) {
			throw new RuntimeException( 'Unsafe seed media path: ' . $source );
		}
		$path = get_theme_file_path( $relative );
		if ( ! is_file( $path ) ) {
			throw new RuntimeException( 'Seed media file does not exist: ' . $source );
		}
		$bits = wp_upload_bits( wp_basename( $path ), null, (string) file_get_contents( $path ) );
		if ( ! empty( $bits['error'] ) ) {
			throw new RuntimeException( 'Media import failed for ' . $source . ': ' . $bits['error'] );
		}
		$filetype = wp_check_filetype( $bits['file'] );
		$attachment_id = wp_insert_attachment( array(
			'post_mime_type' => $filetype['type'] ?: 'application/octet-stream',
			'post_title'     => sanitize_text_field( pathinfo( $path, PATHINFO_FILENAME ) ),
			'post_status'    => 'inherit',
		), $bits['file'] );
		if ( is_wp_error( $attachment_id ) ) {
			throw new RuntimeException( $attachment_id->get_error_message() );
		}
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$metadata = wp_generate_attachment_metadata( $attachment_id, $bits['file'] );
		if ( is_array( $metadata ) ) {
			wp_update_attachment_metadata( $attachment_id, $metadata );
		}
		update_post_meta( $attachment_id, '_platejka_section_seed_source', $source );
		return (int) $attachment_id;
	}

	/** @param mixed $value @param array<string,int> $map @return mixed */
	private static function replaceMediaSources( $value, array $map ) {
		if ( is_string( $value ) && str_starts_with( $value, 'theme://' ) ) {
			return $map[ $value ] ?? $value;
		}
		if ( is_array( $value ) ) {
			foreach ( $value as $key => $child ) { $value[ $key ] = self::replaceMediaSources( $child, $map ); }
		}
		return $value;
	}

	/** @param mixed $value */
	private static function stable( $value ): string {
		return (string) wp_json_encode( $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}

	/** @param mixed $value */
	private static function acfStable( $value ): string {
		return self::stable( self::normalizeAcfValue( $value ) );
	}

	/**
	 * Convert raw ACF field-key arrays into their public field-name representation.
	 * This makes verification independent of ACF's raw storage keys and scalar ID strings.
	 *
	 * @param mixed              $value
	 * @param array<string,mixed>|null $field
	 * @return mixed
	 */
	private static function normalizeAcfValue( $value, ?array $field = null ) {
		if ( is_array( $value ) ) {
			$normalized = array();
			foreach ( $value as $key => $child ) {
				$child_field = null;
				$child_key   = $key;
				if ( is_string( $key ) && str_starts_with( $key, 'field_' ) && function_exists( 'acf_get_field' ) ) {
					$candidate = acf_get_field( $key );
					if ( is_array( $candidate ) ) {
						$child_field = $candidate;
						$child_key   = (string) ( $candidate['name'] ?? $key );
					}
				}
				$normalized[ $child_key ] = self::normalizeAcfValue( $child, $child_field );
			}
			if ( ! array_is_list( $normalized ) ) {
				ksort( $normalized, SORT_STRING );
			}
			return $normalized;
		}

		$type = (string) ( $field['type'] ?? '' );
		if ( 'true_false' === $type ) {
			return (int) (bool) $value;
		}
		if ( in_array( $type, array( 'image', 'file', 'post_object' ), true ) && is_numeric( $value ) ) {
			return (int) $value;
		}
		return $value;
	}

	/** @return array<int,string> */
	private static function additiveFields( string $slug ): array {
		$fields = array(
			'about'       => array( 'labels' ),
			'compliance'  => array( 'labels', 'banks' ),
			'review-main' => array( 'labels', 'video_items', 'ratings' ),
			'calculator'  => array( 'labels' ),
			'problems'    => array( 'labels' ),
			'destinations'=> array( 'image' ),
			'work'        => array( 'eyebrow', 'primary_link' ),
		);
		return $fields[ $slug ] ?? array();
	}

	private static function hasStoredOptionField( string $slug, string $field_name ): bool {
		$option_name = self::OPTIONS_ID . '_' . str_replace( '-', '_', $slug ) . '_' . $field_name;
		$sentinel    = '__platejka_missing_option__';
		return $sentinel !== get_option( '_' . $option_name, $sentinel ) || $sentinel !== get_option( $option_name, $sentinel );
	}

	/** @return array<int,string> */
	private static function versionTwoLabelKeys( string $slug ): array {
		$keys = array(
			'review-main' => array( 'ratings_label', 'prev_video', 'next_video', 'prev_text', 'next_text' ),
			'calculator'  => array( 'route', 'summary_empty', 'close' ),
		);
		return $keys[ $slug ] ?? array();
	}

	/** @param array<int,mixed> $current @param array<int,mixed> $seed @param array<int,string> $allowed_keys @return array<int,mixed> */
	private static function mergeMissingLabels( array $current, array $seed, array $allowed_keys ): array {
		$existing = array();
		foreach ( $current as $row ) {
			if ( is_array( $row ) && isset( $row['key'] ) ) {
				$existing[ (string) $row['key'] ] = true;
			}
		}
		foreach ( $seed as $row ) {
			$key = is_array( $row ) ? (string) ( $row['key'] ?? '' ) : '';
			if ( in_array( $key, $allowed_keys, true ) && ! isset( $existing[ $key ] ) ) {
				$current[] = $row;
				$existing[ $key ] = true;
			}
		}
		return $current;
	}

	/** @param array<int,mixed> $current @param array<int,mixed> $seed @return array<int,mixed> */
	private static function upgradeReviewItems( array $current, array $seed ): array {
		$legacy_text = array(
			'Sasha Mekhel' => '<p>Platejka.com — отличный международный бизнес-платежный сервис! Платформа понятна и проста в использовании, платежи проходят быстро и надёжно, а поддержка всегда готова помочь.</p>',
			'Ева Джозеф'   => '<p>Особенно понравились прозрачность и скорость переводов. Размер комиссий был понятен с самого начала, без скрытых или дополнительных расходов.</p>',
			'Вера Вера'    => '<p>Служба поддержки предоставляет рекомендации и решения в дружелюбной и профессиональной манере, а безопасность каждой транзакции вселяет уверенность.</p>',
		);
		$seed_by_title = array();
		foreach ( $seed as $row ) {
			if ( is_array( $row ) && isset( $row['title'] ) ) {
				$seed_by_title[ (string) $row['title'] ] = $row;
			}
		}
		foreach ( $current as &$row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$title = (string) ( $row['title'] ?? '' );
			$target = $seed_by_title[ $title ] ?? null;
			if ( ! is_array( $target ) ) {
				continue;
			}
			if ( isset( $legacy_text[ $title ] ) && (string) ( $row['text'] ?? '' ) === $legacy_text[ $title ] ) {
				$row['text'] = $target['text'] ?? $row['text'];
			}
			if ( empty( $row['date'] ) ) {
				$row['date'] = $target['date'] ?? '';
			}
		}
		unset( $row );
		return $current;
	}

	/** @param array<int,mixed> $current @param array<int,mixed> $seed @return array<int,mixed> */
	private static function upgradeTableItems( array $current, array $seed ): array {
		$seed_by_title = array();
		foreach ( $seed as $row ) {
			if ( is_array( $row ) && isset( $row['title'] ) ) {
				$seed_by_title[ (string) $row['title'] ] = $row;
			}
		}
		foreach ( $current as &$row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$target = $seed_by_title[ (string) ( $row['title'] ?? '' ) ] ?? null;
			if ( ! is_array( $target ) ) {
				continue;
			}
			foreach ( array( 'partner_value', 'partner_text', 'bank_value', 'bank_text' ) as $field_name ) {
				if ( empty( $row[ $field_name ] ) && array_key_exists( $field_name, $target ) ) {
					$row[ $field_name ] = $target[ $field_name ];
				}
			}
		}
		unset( $row );
		return $current;
	}

	/** @param array<string,mixed> $target_sections @param array<int,mixed> $target_rows */
	private static function verifyAppliedValues( array $target_sections, array $target_rows, bool $verify_page ): void {
		$failures = array();
		foreach ( $target_sections as $slug => $value ) {
			$field_key = 'field_platejka_defaults_' . str_replace( '-', '_', $slug ) . '_v1';
			$actual    = get_field( $field_key, self::OPTIONS_ID, false );
			if ( self::acfStable( $actual ) !== self::acfStable( $value ) ) {
				$failures[] = $field_key;
			}
		}
		if ( $verify_page ) {
			if ( ! (bool) get_field( 'field_platejka_page_builder_v1', self::PAGE_ID, false ) ) {
				$failures[] = 'field_platejka_page_builder_v1';
			}
			$actual_rows = get_field( 'field_platejka_sections_v1', self::PAGE_ID, false );
			if ( self::acfStable( is_array( $actual_rows ) ? $actual_rows : array() ) !== self::acfStable( $target_rows ) ) {
				$failures[] = 'field_platejka_sections_v1';
			}
		}
		if ( $failures ) {
			throw new RuntimeException( 'Section builder migration verification failed for: ' . implode( ', ', $failures ) . '. The completion marker was not written.' );
		}
	}
}
