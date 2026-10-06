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

		$sources = self::mediaSources( $seed['sections'] );
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
		if ( $apply ) {
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
		);

		if ( $apply ) {
			foreach ( $target_sections as $slug => $value ) {
				$field_key = 'field_platejka_defaults_' . str_replace( '-', '_', $slug ) . '_v1';
				$current   = get_field( $field_key, self::OPTIONS_ID, false );
				$changed   = self::stable( $current ) !== self::stable( $value );
				if ( $changed ) {
					update_field( $field_key, $value, self::OPTIONS_ID );
				}
				$writes['options'][ $slug ] = $changed;
			}

			$current_enabled = (bool) get_field( 'field_platejka_page_builder_v1', self::PAGE_ID, false );
			$current_rows    = get_field( 'field_platejka_sections_v1', self::PAGE_ID, false );
			$enable_changed  = ! $current_enabled;
			$rows_changed    = self::stable( is_array( $current_rows ) ? $current_rows : array() ) !== self::stable( $target_rows );
			if ( $enable_changed ) {
				update_field( 'field_platejka_page_builder_v1', 1, self::PAGE_ID );
			}
			if ( $rows_changed ) {
				update_field( 'field_platejka_sections_v1', $target_rows, self::PAGE_ID );
			}
			$writes['page'] = array( 'builder' => $enable_changed, 'rows' => $rows_changed );

			$version_changed = 1 !== (int) get_option( self::VERSION_OPTION, 0 );
			if ( $version_changed ) {
				update_option( self::VERSION_OPTION, 1, false );
			}
			$writes['version']   = $version_changed;
			$writes['performed'] = (bool) ( $imported || $version_changed || $enable_changed || $rows_changed || in_array( true, $writes['options'], true ) );
		}

		return array(
			'mode'           => $apply ? 'apply' : 'dry-run',
			'schema_version' => 1,
			'options'        => array(
				'post_id'  => self::OPTIONS_ID,
				'sections' => array_keys( $target_sections ),
				'cf7'      => array(
					'call'       => (int) ( $target_sections['call']['form'] ?? 0 ),
					'calculator' => (int) ( $target_sections['calculator']['form'] ?? 0 ),
				),
			),
			'page' => array(
				'id'              => self::PAGE_ID,
				'builder_enabled' => true,
				'sections'        => $sections_order,
				'rows'            => $target_rows,
			),
			'media' => $apply
				? array( 'reused' => $reused, 'imported' => $imported )
				: array( 'reused' => $reused, 'to_import' => $missing ),
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
}
