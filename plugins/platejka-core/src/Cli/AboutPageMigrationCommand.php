<?php

namespace Platejka\Core\Cli;

use Platejka\Core\Acf\AboutPageSeed;
use RuntimeException;

defined( 'ABSPATH' ) || exit;

/** Preview-first migration for the dedicated About-page builder. */
final class AboutPageMigrationCommand {
	private const PAGE_ID = 22;
	private const VERSION_OPTION = 'platejka_about_page_builder_version';
	private const BUILDER_FIELD = 'field_platejka_about_page_builder_v1';
	private const ROWS_FIELD = 'field_platejka_about_sections_v1';

	/** @param array<int,string> $args @param array<string,mixed> $assoc_args */
	public function __invoke( array $args, array $assoc_args ): void {
		unset( $args );
		$apply = isset( $assoc_args['apply'] );
		$report = $apply ? self::apply() : self::preview();
		\WP_CLI::line( (string) wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
		\WP_CLI::success( $apply ? 'About-page section builder migration applied.' : 'Dry-run only; no writes performed.' );
	}

	/** @return array<string,mixed> */
	public static function preview(): array { return self::report( false ); }

	/** @return array<string,mixed> */
	public static function apply(): array {
		if ( ! function_exists( 'update_field' ) ) {
			throw new RuntimeException( 'ACF is required for the About-page migration.' );
		}
		return self::report( true );
	}

	/** @return array<string,mixed> */
	private static function report( bool $apply ): array {
		$seed = AboutPageSeed::get();
		$local_slugs = array( 'about-hero', 'location', 'infrastructure', 'financial', 'employees', 'exhibitions', 'developing', 'call-about' );
		$expected_order = array( 'about-hero', 'location', 'review-main', 'infrastructure', 'financial', 'employees', 'exhibitions', 'developing', 'call-about' );
		if ( 1 !== ( $seed['schema_version'] ?? null ) || $local_slugs !== array_keys( $seed['sections'] ?? array() ) || $expected_order !== array_column( $seed['page_rows'] ?? array(), 'acf_fc_layout' ) ) {
			throw new RuntimeException( 'About-page seed is incomplete or has an unsupported version.' );
		}
		if ( 'page' !== get_post_type( self::PAGE_ID ) ) {
			throw new RuntimeException( 'About-page target ID 22 is missing or is not a page.' );
		}

		$already_migrated = (bool) apply_filters( 'platejka_about_page_migration_already_migrated', 1 === (int) get_option( self::VERSION_OPTION, 0 ) );
		$current_enabled = (bool) get_field( self::BUILDER_FIELD, self::PAGE_ID, false );
		$current_rows = get_field( self::ROWS_FIELD, self::PAGE_ID, false );
		$current_rows = is_array( $current_rows ) ? self::normalizeAcfValue( $current_rows ) : array();
		$current_rows = apply_filters( 'platejka_about_page_migration_current_rows', $current_rows );
		$current_rows = is_array( $current_rows ) ? $current_rows : array();
		$has_meaningful_rows = self::hasMeaningfulRows( $current_rows );

		$media_map = array(); $reused = array(); $missing = array();
		foreach ( self::mediaSources( $seed['sections'] ) as $source ) {
			$id = self::attachmentForSource( $source );
			if ( $id ) { $media_map[ $source ] = $id; $reused[] = array( 'source' => $source, 'attachment_id' => $id ); }
			else { $missing[] = $source; }
		}
		$candidate_rows = self::seedRows( $seed, $media_map );
		$resumable = $has_meaningful_rows && array() === $missing && self::acfStable( $current_rows ) === self::acfStable( $candidate_rows );
		$conflict = ! $already_migrated && $has_meaningful_rows && ! $resumable;
		if ( $apply && $conflict ) {
			throw new RuntimeException( 'About-page migration refused: existing meaningful About rows require manual resolution.' );
		}
		$imported = array();
		if ( $apply && ! $already_migrated ) {
			foreach ( $missing as $source ) {
				$id = self::importThemeMedia( $source ); $media_map[ $source ] = $id;
				$imported[] = array( 'source' => $source, 'attachment_id' => $id );
			}
		}

		$target_rows = $already_migrated || $conflict ? $current_rows : self::seedRows( $seed, $media_map );
		$rows_changed = ! $already_migrated && ! $conflict && self::acfStable( $current_rows ) !== self::acfStable( $target_rows );
		$builder_changed = ! $already_migrated && ! $conflict && ! $current_enabled;
		$writes = array(
			'performed' => false,
			'page' => array( 'builder' => $builder_changed, 'rows' => $rows_changed ),
			'version' => false,
			'planned' => array(),
		);
		if ( $rows_changed ) { $writes['planned'][] = array( 'post_id' => self::PAGE_ID, 'field' => self::ROWS_FIELD, 'current' => $current_rows, 'target' => $target_rows ); }
		if ( $builder_changed ) { $writes['planned'][] = array( 'post_id' => self::PAGE_ID, 'field' => self::BUILDER_FIELD, 'current' => $current_enabled, 'target' => true ); }
		if ( ! $already_migrated && ! $conflict ) { $writes['planned'][] = array( 'post_id' => 'wp_options', 'field' => self::VERSION_OPTION, 'current' => 0, 'target' => 1 ); }

		if ( $apply && ! $already_migrated ) {
			if ( $rows_changed ) {
				update_field( self::ROWS_FIELD, $target_rows, self::PAGE_ID );
			}
			self::verifyContentAndRows( $target_rows );
			if ( $builder_changed ) {
				update_field( 'field_platejka_about_page_builder_v1', 1, self::PAGE_ID );
			}
			if ( ! (bool) get_field( self::BUILDER_FIELD, self::PAGE_ID, false ) ) {
				throw new RuntimeException( 'About-page migration could not enable the builder; completion marker was not written.' );
			}
			update_option( self::VERSION_OPTION, 1, false );
			if ( 1 !== (int) get_option( self::VERSION_OPTION, 0 ) ) {
				throw new RuntimeException( 'About-page values were verified, but the completion marker could not be saved.' );
			}
			$writes['performed'] = true; $writes['version'] = true;
		}

		return array(
			'mode' => $apply ? 'apply' : 'dry-run', 'schema_version' => 1,
			'page_local_sections' => $local_slugs,
			'page' => array( 'id' => self::PAGE_ID, 'builder_enabled' => $already_migrated ? $current_enabled : ! $conflict, 'sections' => array_values( array_filter( array_column( $target_rows, 'acf_fc_layout' ), 'is_string' ) ), 'rows' => $target_rows ),
			'conflict' => array( 'meaningful_rows' => $conflict ),
			'media' => $apply ? array( 'reused' => $reused, 'imported' => $imported ) : array( 'reused' => $reused, 'to_import' => $already_migrated ? array() : $missing ),
			'writes' => $writes,
		);
	}

	/** @param array<string,mixed> $seed @param array<string,int> $media_map @return array<int,mixed> */
	private static function seedRows( array $seed, array $media_map ): array {
		$rows = array();
		foreach ( $seed['page_rows'] as $row ) {
			$slug = (string) ( $row['acf_fc_layout'] ?? '' );
			if ( isset( $seed['sections'][ $slug ] ) ) {
				$row['overrides'] = self::replaceMediaSources( $seed['sections'][ $slug ], $media_map );
			}
			$rows[] = $row;
		}
		return $rows;
	}

	/** @param array<int,mixed> $rows */
	private static function hasMeaningfulRows( array $rows ): bool {
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) { if ( self::hasMeaningfulValue( $row ) ) { return true; } continue; }
			foreach ( $row as $key => $value ) {
				if ( 'acf_fc_layout' === $key || 'anchor' === $key || ( 'enabled' === $key && empty( $value ) ) ) { continue; }
				if ( self::hasMeaningfulValue( $value ) ) { return true; }
			}
		}
		return false;
	}

	/** @param mixed $value */
	private static function hasMeaningfulValue( $value ): bool {
		if ( is_array( $value ) ) { foreach ( $value as $child ) { if ( self::hasMeaningfulValue( $child ) ) { return true; } } return false; }
		if ( is_string( $value ) ) { return '' !== trim( $value ); }
		return null !== $value && false !== $value;
	}

	/** @param mixed $value @return array<int,string> */
	private static function mediaSources( $value ): array {
		$sources = array();
		$walk = static function ( $current ) use ( &$walk, &$sources ): void {
			if ( is_string( $current ) && str_starts_with( $current, 'theme://' ) ) { $sources[ $current ] = true; return; }
			if ( is_array( $current ) ) { foreach ( $current as $child ) { $walk( $child ); } }
		};
		$walk( $value ); $sources = array_keys( $sources ); sort( $sources, SORT_STRING ); return $sources;
	}

	private static function attachmentForSource( string $source ): int {
		$ids = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'fields' => 'ids', 'posts_per_page' => 1, 'meta_key' => '_platejka_section_seed_source', 'meta_value' => $source ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		if ( $ids ) { return (int) $ids[0]; }
		$path = self::themeMediaPath( $source );
		if ( ! is_file( $path ) ) { return 0; }
		$candidates = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'fields' => 'ids', 'posts_per_page' => -1, 'meta_key' => '_wp_attached_file', 'meta_value' => wp_basename( $path ), 'meta_compare' => 'LIKE' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		$hash = hash_file( 'sha256', $path );
		foreach ( $candidates as $candidate ) {
			$attached = get_attached_file( (int) $candidate );
			if ( is_string( $attached ) && is_file( $attached ) && hash_equals( $hash, hash_file( 'sha256', $attached ) ) ) { return (int) $candidate; }
		}
		return 0;
	}

	private static function themeMediaPath( string $source ): string {
		$relative = ltrim( substr( $source, 8 ), '/' );
		if ( ! preg_match( '~^[a-zA-Z0-9_./()-]+$~', $relative ) || str_contains( $relative, '..' ) ) { throw new RuntimeException( 'Unsafe About-page seed media path: ' . $source ); }
		return get_theme_file_path( $relative );
	}

	private static function importThemeMedia( string $source ): int {
		$path = self::themeMediaPath( $source );
		if ( ! is_file( $path ) ) { throw new RuntimeException( 'About-page seed media file does not exist: ' . $source ); }
		$bits = wp_upload_bits( wp_basename( $path ), null, (string) file_get_contents( $path ) );
		if ( ! empty( $bits['error'] ) ) { throw new RuntimeException( 'Media import failed for ' . $source . ': ' . $bits['error'] ); }
		$filetype = wp_check_filetype( $bits['file'] );
		$id = wp_insert_attachment( array( 'post_mime_type' => $filetype['type'] ?: 'application/octet-stream', 'post_title' => sanitize_text_field( pathinfo( $path, PATHINFO_FILENAME ) ), 'post_status' => 'inherit' ), $bits['file'] );
		if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$metadata = wp_generate_attachment_metadata( $id, $bits['file'] ); if ( is_array( $metadata ) ) { wp_update_attachment_metadata( $id, $metadata ); }
		update_post_meta( $id, '_platejka_section_seed_source', $source ); return (int) $id;
	}

	/** @param mixed $value @param array<string,int> $map @return mixed */
	private static function replaceMediaSources( $value, array $map ) {
		if ( is_string( $value ) && str_starts_with( $value, 'theme://' ) ) { return $map[ $value ] ?? $value; }
		if ( is_array( $value ) ) { foreach ( $value as $key => $child ) { $value[ $key ] = self::replaceMediaSources( $child, $map ); } }
		return $value;
	}

	/** @param mixed $value */
	private static function acfStable( $value ): string { return (string) wp_json_encode( self::compactAcfValue( self::normalizeAcfValue( $value ) ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); }
	/** @param mixed $value @return mixed */
	private static function compactAcfValue( $value ) {
		if ( ! is_array( $value ) ) { return $value; }
		$out = array();
		foreach ( $value as $key => $child ) {
			if ( ! self::hasMeaningfulValue( $child ) ) { continue; }
			if ( array_is_list( $value ) ) { $out[] = self::compactAcfValue( $child ); } else { $out[ $key ] = self::compactAcfValue( $child ); }
		}
		return $out;
	}
	/** @param mixed $value @param array<string,mixed>|null $field @return mixed */
	private static function normalizeAcfValue( $value, ?array $field = null ) {
		if ( is_array( $value ) ) {
			$out = array();
			foreach ( $value as $key => $child ) {
				$child_field = null; $child_key = $key;
				if ( is_string( $key ) && str_starts_with( $key, 'field_' ) && function_exists( 'acf_get_field' ) ) { $candidate = acf_get_field( $key ); if ( is_array( $candidate ) ) { $child_field = $candidate; $child_key = (string) ( $candidate['name'] ?? $key ); } }
				$out[ $child_key ] = self::normalizeAcfValue( $child, $child_field );
			}
			if ( ! array_is_list( $out ) ) { ksort( $out, SORT_STRING ); }
			return $out;
		}
		$type = (string) ( $field['type'] ?? '' );
		if ( 'true_false' === $type ) { return (int) (bool) $value; }
		if ( in_array( $type, array( 'image', 'file', 'post_object' ), true ) && is_numeric( $value ) ) { return (int) $value; }
		return $value;
	}

	/** @param array<int,mixed> $target_rows */
	private static function verifyContentAndRows( array $target_rows ): void {
		$actual = get_field( self::ROWS_FIELD, self::PAGE_ID, false );
		$actual = is_array( $actual ) ? self::normalizeAcfValue( $actual ) : array();
		$valid = self::acfStable( $actual ) === self::acfStable( $target_rows );
		$valid = (bool) apply_filters( 'platejka_about_page_migration_verify', $valid, $actual, $target_rows );
		if ( ! $valid ) { throw new RuntimeException( 'About-page migration verification failed for section rows. Completion marker was not written.' ); }
	}
}
