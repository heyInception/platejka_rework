<?php

namespace Platejka\Core\Cli;

use Platejka\Core\Acf\DefaultPageSeed;
use RuntimeException;

defined( 'ABSPATH' ) || exit;

/** Preview-first migration for the China page using the default page template. */
final class DefaultPageMigrationCommand {
	private const PAGE_ID = 1873;
	private const OPTIONS_ID = 'platejka_section_defaults';
	private const VERSION_OPTION = 'platejka_default_page_builder_version';

	/** @param array<int,string> $args @param array<string,mixed> $assoc_args */
	public function __invoke( array $args, array $assoc_args ): void {
		unset( $args );
		$apply  = isset( $assoc_args['apply'] );
		$report = $apply ? self::apply() : self::preview();
		\WP_CLI::line( (string) wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
		\WP_CLI::success( $apply ? 'Default-page section builder migration applied.' : 'Dry-run only; no writes performed.' );
	}

	/** @return array<string,mixed> */
	public static function preview(): array {
		return self::report( false );
	}

	/** @return array<string,mixed> */
	public static function apply(): array {
		if ( ! function_exists( 'update_field' ) ) {
			throw new RuntimeException( 'ACF is required for the default-page migration.' );
		}
		return self::report( true );
	}

	/** @return array<string,mixed> */
	private static function report( bool $apply ): array {
		$seed = DefaultPageSeed::get();
		if ( 1 !== ( $seed['schema_version'] ?? null ) || array( 'hero', 'protection' ) !== array_keys( $seed['sections'] ?? array() ) || 13 !== count( $seed['page_rows'] ?? array() ) ) {
			throw new RuntimeException( 'Default-page seed is incomplete or has an unsupported version.' );
		}

		$already_migrated = 1 === (int) get_option( self::VERSION_OPTION, 0 );
		$current_options   = array();
		$current_has_value = array();
		$seed_targets      = array();
		foreach ( $seed['sections'] as $slug => $value ) {
			$field_key = self::fieldKey( $slug );
			$current   = get_field( $field_key, self::OPTIONS_ID, false );
			$current_options[ $slug ] = self::normalizeAcfValue( $current );
			$current_has_value[ $slug ] = self::hasMeaningfulValue( $current_options[ $slug ] );
			if ( ! $already_migrated && ! $current_has_value[ $slug ] ) {
				$seed_targets[ $slug ] = $value;
			}
		}

		$media_map = array();
		$reused    = array();
		$missing   = array();
		foreach ( self::mediaSources( $seed_targets ) as $source ) {
			$attachment_id = self::attachmentForSource( $source );
			if ( $attachment_id ) {
				$media_map[ $source ] = $attachment_id;
				$reused[]             = array( 'source' => $source, 'attachment_id' => $attachment_id );
			} else {
				$missing[] = $source;
			}
		}

		$imported = array();
		if ( $apply && ! $already_migrated ) {
			foreach ( $missing as $source ) {
				$attachment_id       = self::importThemeMedia( $source );
				$media_map[ $source ] = $attachment_id;
				$imported[]           = array( 'source' => $source, 'attachment_id' => $attachment_id );
			}
		}

		$target_options = array();
		foreach ( $seed['sections'] as $slug => $seed_value ) {
			$target_options[ $slug ] = $already_migrated || $current_has_value[ $slug ]
				? self::sectionArray( $current_options[ $slug ] )
				: self::sectionArray( self::replaceMediaSources( $seed_value, $media_map ) );
		}

		$current_enabled = (bool) get_field( 'field_platejka_page_builder_v1', self::PAGE_ID, false );
		$current_rows    = get_field( 'field_platejka_sections_v1', self::PAGE_ID, false );
		$current_rows    = is_array( $current_rows ) ? self::normalizeAcfValue( $current_rows ) : array();
		$target_rows     = $already_migrated ? $current_rows : $seed['page_rows'];
		$writes          = array(
			'performed' => false,
			'options'   => array(),
			'page'      => array( 'builder' => false, 'rows' => false ),
			'version'   => false,
			'planned'   => array(),
		);

		foreach ( $target_options as $slug => $value ) {
			$changed = ! $already_migrated && self::acfStable( $current_options[ $slug ] ) !== self::acfStable( $value );
			$writes['options'][ $slug ] = $changed;
			if ( $changed ) {
				$writes['planned'][] = array( 'post_id' => self::OPTIONS_ID, 'field' => self::fieldKey( $slug ), 'current' => $current_options[ $slug ], 'target' => $value );
			}
		}

		if ( ! $already_migrated ) {
			$writes['page']['builder'] = ! $current_enabled;
			$writes['page']['rows']    = self::acfStable( $current_rows ) !== self::acfStable( $target_rows );
			if ( $writes['page']['rows'] ) {
				$writes['planned'][] = array( 'post_id' => self::PAGE_ID, 'field' => 'field_platejka_sections_v1', 'current' => $current_rows, 'target' => $target_rows );
			}
			if ( $writes['page']['builder'] ) {
				$writes['planned'][] = array( 'post_id' => self::PAGE_ID, 'field' => 'field_platejka_page_builder_v1', 'current' => $current_enabled, 'target' => true );
			}
			$writes['planned'][] = array( 'post_id' => 'wp_options', 'field' => self::VERSION_OPTION, 'current' => (int) get_option( self::VERSION_OPTION, 0 ), 'target' => 1 );
		}

		if ( $apply && ! $already_migrated ) {
			foreach ( $target_options as $slug => $value ) {
				if ( $writes['options'][ $slug ] ) {
					update_field( self::fieldKey( $slug ), $value, self::OPTIONS_ID );
				}
			}
			if ( $writes['page']['rows'] ) {
				update_field( 'field_platejka_sections_v1', $target_rows, self::PAGE_ID );
			}

			self::verifyContentAndRows( $target_options, $target_rows );

			if ( $writes['page']['builder'] ) {
				update_field( 'field_platejka_page_builder_v1', 1, self::PAGE_ID );
			}
			if ( ! (bool) get_field( 'field_platejka_page_builder_v1', self::PAGE_ID, false ) ) {
				throw new RuntimeException( 'Default-page migration could not enable the page builder; completion marker was not written.' );
			}

			update_option( self::VERSION_OPTION, 1, false );
			if ( 1 !== (int) get_option( self::VERSION_OPTION, 0 ) ) {
				throw new RuntimeException( 'Default-page migration values were verified, but the completion marker could not be saved.' );
			}

			$writes['version']   = true;
			$writes['performed'] = true;
		}

		return array(
			'mode'           => $apply ? 'apply' : 'dry-run',
			'schema_version' => 1,
			'options'        => array( 'post_id' => self::OPTIONS_ID, 'sections' => array_keys( $target_options ) ),
			'page'           => array(
				'id'              => self::PAGE_ID,
				'builder_enabled' => $already_migrated ? $current_enabled : true,
				'sections'        => array_values( array_filter( array_column( $target_rows, 'acf_fc_layout' ), 'is_string' ) ),
				'rows'            => $target_rows,
			),
			'media'          => $apply
				? array( 'reused' => $reused, 'imported' => $imported )
				: array( 'reused' => $reused, 'to_import' => $already_migrated ? array() : $missing ),
			'writes'         => $writes,
		);
	}

	private static function fieldKey( string $slug ): string {
		return 'field_platejka_defaults_' . str_replace( '-', '_', $slug ) . '_v1';
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

	/** @param mixed $value @return array<string,mixed> */
	private static function sectionArray( $value ): array {
		return is_array( $value ) ? $value : array();
	}

	/** @param mixed $value @return array<int,string> */
	private static function mediaSources( $value ): array {
		$sources = array();
		$walk    = static function ( $current ) use ( &$walk, &$sources ): void {
			if ( is_string( $current ) && str_starts_with( $current, 'theme://' ) ) {
				$sources[ $current ] = true;
				return;
			}
			if ( is_array( $current ) ) {
				foreach ( $current as $child ) {
					$walk( $child );
				}
			}
		};
		$walk( $value );
		$sources = array_keys( $sources );
		sort( $sources, SORT_STRING );
		return $sources;
	}

	private static function attachmentForSource( string $source ): int {
		$ids = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'fields'         => 'ids',
				'posts_per_page' => 1,
				'meta_key'       => '_platejka_section_seed_source', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $source, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
		if ( $ids ) {
			return (int) $ids[0];
		}

		$source_path = get_theme_file_path( ltrim( substr( $source, 8 ), '/' ) );
		if ( ! is_file( $source_path ) ) {
			return 0;
		}
		$candidates  = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'meta_key'       => '_wp_attached_file', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => wp_basename( $source_path ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'meta_compare'   => 'LIKE',
			)
		);
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
			throw new RuntimeException( 'Unsafe default-page seed media path: ' . $source );
		}
		$path = get_theme_file_path( $relative );
		if ( ! is_file( $path ) ) {
			throw new RuntimeException( 'Default-page seed media file does not exist: ' . $source );
		}
		$bits = wp_upload_bits( wp_basename( $path ), null, (string) file_get_contents( $path ) );
		if ( ! empty( $bits['error'] ) ) {
			throw new RuntimeException( 'Media import failed for ' . $source . ': ' . $bits['error'] );
		}
		$filetype      = wp_check_filetype( $bits['file'] );
		$attachment_id = wp_insert_attachment(
			array(
				'post_mime_type' => $filetype['type'] ?: 'application/octet-stream',
				'post_title'     => sanitize_text_field( pathinfo( $path, PATHINFO_FILENAME ) ),
				'post_status'    => 'inherit',
			),
			$bits['file']
		);
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
			foreach ( $value as $key => $child ) {
				$value[ $key ] = self::replaceMediaSources( $child, $map );
			}
		}
		return $value;
	}

	/** @param mixed $value */
	private static function stable( $value ): string {
		return (string) wp_json_encode( $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}

	/** @param mixed $value */
	private static function acfStable( $value ): string {
		return self::stable( self::compactAcfValue( self::normalizeAcfValue( $value ) ) );
	}

	/**
	 * Remove only ACF's empty structural defaults before comparison.
	 * Meaningful scalar zeroes, IDs, row order, and selected variants remain intact.
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	private static function compactAcfValue( $value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}

		$compacted = array();
		foreach ( $value as $key => $child ) {
			if ( ! self::hasMeaningfulValue( $child ) ) {
				continue;
			}
			if ( array_is_list( $value ) ) {
				$compacted[] = self::compactAcfValue( $child );
			} else {
				$compacted[ $key ] = self::compactAcfValue( $child );
			}
		}
		return $compacted;
	}

	/** @param mixed $value @param array<string,mixed>|null $field @return mixed */
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

	/** @param array<string,mixed> $target_options @param array<int,mixed> $target_rows */
	private static function verifyContentAndRows( array $target_options, array $target_rows ): void {
		$failures = array();
		foreach ( $target_options as $slug => $value ) {
			$actual = self::normalizeAcfValue( get_field( self::fieldKey( $slug ), self::OPTIONS_ID, false ) );
			if ( self::acfStable( $actual ) !== self::acfStable( $value ) ) {
				$failures[] = self::fieldKey( $slug );
			}
		}
		$actual_rows = get_field( 'field_platejka_sections_v1', self::PAGE_ID, false );
		$actual_rows = is_array( $actual_rows ) ? self::normalizeAcfValue( $actual_rows ) : array();
		if ( self::acfStable( $actual_rows ) !== self::acfStable( $target_rows ) ) {
			$failures[] = 'field_platejka_sections_v1';
		}
		if ( $failures ) {
			throw new RuntimeException( 'Default-page migration verification failed for: ' . implode( ', ', $failures ) . '. Completion marker was not written.' );
		}
	}
}
