<?php
/**
 * Preview and apply the production cross-block migration.
 *
 * @package Platejka_Core
 */

namespace Platejka\Core\Cli;

use Platejka\Core\Migration\CrossBlockMigrationPlanner;
use Platejka\Core\Migration\CrossBlockSqlDumpReader;
use RuntimeException;

final class CrossBlockMigrationCommand {
	private const SCHEMA          = 'cross-block-migration-v1';
	private const DEFAULT_CONTEXT = 'platejka_section_defaults';
	private const BUILDER_ENABLED = 'field_platejka_page_builder_v1';
	private const BUILDER_ROWS    = 'field_platejka_sections_v1';
	private const EXCLUDED        = array( 'destinations', 'review', 'review-main', 'reviews', 'client_reviews', 'documents', 'transfer-calculator', 'transfer_calculator', 'work', 'tariff' );

	/** @param array<int,string> $args @param array<string,mixed> $assoc_args */
	public function __invoke( array $args, array $assoc_args ): void {
		unset( $args );
		if ( isset( $assoc_args['apply'] ) ) {
			$manifest_path = (string) ( $assoc_args['manifest'] ?? '' );
			$backup_path   = (string) ( $assoc_args['backup'] ?? '' );
			if ( ! is_readable( $manifest_path ) ) {
				throw new RuntimeException( 'Approved manifest is missing or unreadable.' );
			}
			$manifest = json_decode( (string) file_get_contents( $manifest_path ), true );
			if ( ! is_array( $manifest ) ) {
				throw new RuntimeException( 'Approved manifest is not valid JSON.' );
			}
			$result = self::applyManifest( $manifest, $backup_path );
			\WP_CLI::line( self::json( $result ) );
			\WP_CLI::success( 'Cross-block migration applied and verified.' );
			return;
		}

		$sql_path    = (string) ( $assoc_args['sql'] ?? '' );
		$output_path = (string) ( $assoc_args['output'] ?? '' );
		if ( '' === $output_path ) {
			throw new RuntimeException( 'Dry-run requires an absolute --output path.' );
		}
		$manifest = self::preview( $sql_path );
		self::writeJsonFile( $output_path, $manifest, false );
		\WP_CLI::line( self::json( $manifest['plan']['totals'] ?? array() ) );
		\WP_CLI::success( 'Dry-run manifest created; no database writes performed.' );
	}

	/** @return array<string,mixed> */
	public static function preview( string $sql_path ): array {
		if ( ! is_file( $sql_path ) || ! is_readable( $sql_path ) ) {
			throw new RuntimeException( 'SQL dump is missing or unreadable.' );
		}
		$reader     = new CrossBlockSqlDumpReader( $sql_path );
		$production = CrossBlockMigrationPlanner::extractProduction( $reader->read() );
		$local      = self::collectLocalState( $production );
		$manifest   = array(
			'schema' => self::SCHEMA,
			'mode'   => 'dry-run',
			'source' => array(
				'filename' => basename( $sql_path ),
				'sha256'   => hash_file( 'sha256', $sql_path ),
			),
			'production' => $production,
			'plan'       => CrossBlockMigrationPlanner::plan( $production, $local ),
		);
		return self::sealManifest( $manifest );
	}

	/** Add or refresh the integrity hash after intentionally constructing a manifest. @return array<string,mixed> */
	public static function sealManifest( array $manifest ): array {
		unset( $manifest['integrity'] );
		$manifest['integrity'] = hash( 'sha256', self::stable( $manifest ) );
		return $manifest;
	}

	/** @return array<string,mixed> */
	public static function applyManifest( array $manifest, string $backup_path ): array {
		if ( '' === trim( $backup_path ) ) {
			throw new RuntimeException( 'A non-empty backup path is required before apply.' );
		}
		if ( ! self::isAbsolutePath( $backup_path ) ) {
			throw new RuntimeException( 'The backup path must be absolute.' );
		}
		self::validateIntegrity( $manifest );
		if ( self::SCHEMA !== ( $manifest['schema'] ?? '' ) ) {
			throw new RuntimeException( 'Unsupported migration manifest schema.' );
		}
		$plan = is_array( $manifest['plan'] ?? null ) ? $manifest['plan'] : array();
		self::validatePlanTargets( $plan );
		self::validateCurrentState( $plan );

		$backup = self::captureBackup( $plan );
		self::writeJsonFile( $backup_path, $backup, true );

		$writes = array( 'defaults' => 0, 'pages' => 0 );
		foreach ( is_array( $plan['defaults'] ?? null ) ? $plan['defaults'] : array() as $change ) {
			$slug = (string) ( $change['slug'] ?? '' );
			$key  = 'field_platejka_defaults_' . str_replace( '-', '_', $slug ) . '_v1';
			update_field( $key, $change['target'] ?? array(), self::DEFAULT_CONTEXT );
			$actual = self::normalizeRawValue( get_field( $key, self::DEFAULT_CONTEXT, false ) );
			if ( ! self::equivalentValue( $actual, $change['target'] ?? array() ) ) {
				throw new RuntimeException( 'Defaults verification failed for ' . $slug . '.' );
			}
			++$writes['defaults'];
		}

		foreach ( is_array( $plan['pages'] ?? null ) ? $plan['pages'] : array() as $change ) {
			$post_id = (int) ( $change['post_id'] ?? 0 );
			// ACF returns false both on failure and when the stored value is already
			// identical. The mandatory reread below is the authoritative result.
			$target_rows = is_array( $change['target_rows'] ?? null ) ? $change['target_rows'] : array();
			$builder_field = self::builderFieldForUpdate();
			$prepared_rows = self::prepareRowsForUpdate( $target_rows, $builder_field );
			if ( is_array( $builder_field ) && function_exists( 'acf_update_value' ) ) {
				// update_field() reloads the flexible-content schema by key. ACF leaves
				// nested clone fields unexpanded on that path, so their local values are
				// silently discarded. Passing the hydrated schema preserves sub_fields.
				acf_update_value( $prepared_rows, $post_id, $builder_field );
			} else {
				update_field( self::BUILDER_ROWS, $prepared_rows, $post_id );
			}
			update_field( self::BUILDER_ENABLED, 1, $post_id );
			$actual_rows = self::normalizeRawValue( get_field( self::BUILDER_ROWS, $post_id, false ) );
			$actual_enabled = (bool) get_field( self::BUILDER_ENABLED, $post_id, false );
			if ( ! $actual_enabled || ! self::equivalentValue( $actual_rows, $change['target_rows'] ?? array() ) ) {
				throw new RuntimeException( 'Page verification failed for ' . $post_id . '.' );
			}
			++$writes['pages'];
		}

		return array( 'mode' => 'apply', 'backup' => $backup_path, 'writes' => $writes, 'verified' => true );
	}

	/** @param array<string,mixed> $production @return array<string,mixed> */
	private static function collectLocalState( array $production ): array {
		if ( ! function_exists( 'get_field' ) || ! function_exists( 'get_page_by_path' ) ) {
			throw new RuntimeException( 'WordPress and ACF are required for migration preview.' );
		}
		$slugs = array_fill_keys( array_keys( is_array( $production['defaults'] ?? null ) ? $production['defaults'] : array() ), true );
		foreach ( is_array( $production['pages'] ?? null ) ? $production['pages'] : array() as $page ) {
			foreach ( array_keys( is_array( $page['layouts'] ?? null ) ? $page['layouts'] : array() ) as $slug ) {
				$slugs[ $slug ] = true;
			}
		}
		$defaults = array();
		foreach ( array_keys( $slugs ) as $slug ) {
			$field = str_replace( '-', '_', $slug );
			$defaults[ $slug ] = self::normalizeRawValue( get_field( $field, self::DEFAULT_CONTEXT, false ) );
			if ( ! is_array( $defaults[ $slug ] ) ) {
				$defaults[ $slug ] = array();
			}
		}

		$pages = array();
		foreach ( array_keys( is_array( $production['pages'] ?? null ) ? $production['pages'] : array() ) as $slug ) {
			$post = get_page_by_path( $slug, 'OBJECT', 'page' );
			if ( ! is_object( $post ) || ! isset( $post->ID ) ) {
				continue;
			}
			$post_id = (int) $post->ID;
			$pages[ $slug ] = array(
				'id'              => $post_id,
				'status'          => (string) ( $post->post_status ?? get_post_status( $post_id ) ),
				'builder_enabled' => (bool) get_field( self::BUILDER_ENABLED, $post_id, false ),
				'rows'            => self::normalizeRawValue( get_field( self::BUILDER_ROWS, $post_id, false ) ),
				'attachments'     => array(),
			);
		}
		return array( 'defaults' => $defaults, 'pages' => $pages, 'attachments' => array() );
	}

	/** @param mixed $value @return mixed */
	private static function normalizeRawValue( $value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}
		$result = array();
		foreach ( $value as $key => $child ) {
			$name = $key;
			if ( is_string( $key ) && str_starts_with( $key, 'field_' ) && function_exists( 'acf_get_field' ) ) {
				$field = acf_get_field( $key );
				if ( is_array( $field ) && is_string( $field['name'] ?? null ) && '' !== $field['name'] ) {
					$name = $field['name'];
				}
			}
			$result[ $name ] = self::normalizeRawValue( $child );
		}
		return $result;
	}

	/** @param array<int,array<string,mixed>> $rows @return array<int,array<string,mixed>> */
	private static function prepareRowsForUpdate( array $rows, ?array $field = null ): array {
		if ( ! function_exists( 'acf_get_field' ) ) {
			return $rows;
		}
		$field = $field ?? self::builderFieldForUpdate();
		if ( ! is_array( $field ) || 'flexible_content' !== ( $field['type'] ?? '' ) ) {
			return $rows;
		}
		$layouts = array();
		foreach ( is_array( $field['layouts'] ?? null ) ? $field['layouts'] : array() as $layout ) {
			if ( is_array( $layout ) && is_string( $layout['name'] ?? null ) ) {
				$layouts[ $layout['name'] ] = $layout;
			}
		}
		$result = array();
		foreach ( $rows as $row ) {
			$slug = (string) ( $row['acf_fc_layout'] ?? '' );
			if ( ! isset( $layouts[ $slug ] ) ) {
				$result[] = $row;
				continue;
			}
			$encoded = array( 'acf_fc_layout' => $slug );
			foreach ( is_array( $layouts[ $slug ]['sub_fields'] ?? null ) ? $layouts[ $slug ]['sub_fields'] : array() as $sub_field ) {
				if ( ! is_array( $sub_field ) ) {
					continue;
				}
				$name = (string) ( $sub_field['name'] ?? '' );
				$key  = (string) ( $sub_field['key'] ?? '' );
				if ( '' !== $name && '' !== $key && array_key_exists( $name, $row ) ) {
					$encoded[ $key ] = self::prepareFieldValue( $row[ $name ], $sub_field );
				}
			}
			$result[] = $encoded;
		}
		return $result;
	}

	/** @return array<string,mixed>|null */
	private static function builderFieldForUpdate(): ?array {
		if ( ! function_exists( 'acf_get_field' ) ) {
			return null;
		}
		$field = acf_get_field( self::BUILDER_ROWS );
		if ( ! is_array( $field ) ) {
			return null;
		}
		foreach ( is_array( $field['layouts'] ?? null ) ? $field['layouts'] : array() as $layout_index => $layout ) {
			if ( ! is_array( $layout ) ) {
				continue;
			}
			foreach ( is_array( $layout['sub_fields'] ?? null ) ? $layout['sub_fields'] : array() as $field_index => $sub_field ) {
				$key = is_array( $sub_field ) ? (string) ( $sub_field['key'] ?? '' ) : '';
				$loaded = is_array( $sub_field ) && ! empty( $sub_field['sub_fields'] )
					? $sub_field
					: ( '' !== $key ? acf_get_field( $key ) : null );
				if ( is_array( $loaded ) ) {
					$field['layouts'][ $layout_index ]['sub_fields'][ $field_index ] = $loaded;
				}
			}
		}
		return $field;
	}

	/** @param mixed $value @param array<string,mixed> $field @return mixed */
	private static function prepareFieldValue( $value, array $field ) {
		$type = (string) ( $field['type'] ?? '' );
		if ( 'clone' === $type && is_array( $value ) ) {
			$result = array();
			foreach ( is_array( $field['clone'] ?? null ) ? $field['clone'] : array() as $clone_key ) {
				$clone = function_exists( 'acf_get_field' ) ? acf_get_field( $clone_key ) : null;
				if ( ! is_array( $clone ) ) {
					continue;
				}
				$name   = (string) ( $clone['name'] ?? '' );
				$source = '' !== $name && array_key_exists( $name, $value ) ? $value[ $name ] : $value;
				$result[ (string) $clone['key'] ] = self::prepareFieldValue( $source, $clone );
			}
			return $result;
		}
		if ( 'group' === $type && is_array( $value ) ) {
			return self::prepareSubFields( $value, is_array( $field['sub_fields'] ?? null ) ? $field['sub_fields'] : array() );
		}
		if ( 'repeater' === $type && is_array( $value ) ) {
			$result = array();
			foreach ( $value as $row ) {
				$result[] = is_array( $row )
					? self::prepareSubFields( $row, is_array( $field['sub_fields'] ?? null ) ? $field['sub_fields'] : array() )
					: $row;
			}
			return $result;
		}
		return $value;
	}

	/** @param array<string,mixed> $value @param array<int,array<string,mixed>> $fields @return array<string,mixed> */
	private static function prepareSubFields( array $value, array $fields ): array {
		$result = array();
		foreach ( $fields as $sub_field ) {
			$name = (string) ( $sub_field['name'] ?? '' );
			$key  = (string) ( $sub_field['key'] ?? '' );
			if ( '' !== $name && '' !== $key && array_key_exists( $name, $value ) ) {
				$result[ $key ] = self::prepareFieldValue( $value[ $name ], $sub_field );
			}
		}
		return $result;
	}

	/** @param array<string,mixed> $manifest */
	private static function validateIntegrity( array $manifest ): void {
		$integrity = (string) ( $manifest['integrity'] ?? '' );
		$copy = $manifest;
		unset( $copy['integrity'] );
		if ( '' === $integrity || ! hash_equals( $integrity, hash( 'sha256', self::stable( $copy ) ) ) ) {
			throw new RuntimeException( 'Manifest integrity check failed; the file was modified.' );
		}
	}

	/** @param array<string,mixed> $plan */
	private static function validatePlanTargets( array $plan ): void {
		foreach ( is_array( $plan['pages'] ?? null ) ? $plan['pages'] : array() as $page ) {
			$current = self::rowsBySlug( is_array( $page['current_rows'] ?? null ) ? $page['current_rows'] : array() );
			$target  = self::rowsBySlug( is_array( $page['target_rows'] ?? null ) ? $page['target_rows'] : array() );
			foreach ( self::EXCLUDED as $slug ) {
				if ( self::stable( $current[ $slug ] ?? array() ) !== self::stable( $target[ $slug ] ?? array() ) ) {
					throw new RuntimeException( 'Manifest contains an excluded target: ' . $slug . '.' );
				}
			}
		}
	}

	/** @param array<string,mixed> $plan */
	private static function validateCurrentState( array $plan ): void {
		foreach ( is_array( $plan['defaults'] ?? null ) ? $plan['defaults'] : array() as $change ) {
			$slug = (string) ( $change['slug'] ?? '' );
			$current = self::normalizeRawValue( get_field( str_replace( '-', '_', $slug ), self::DEFAULT_CONTEXT, false ) );
			if ( ! hash_equals( (string) ( $change['fingerprint'] ?? '' ), hash( 'sha256', self::stable( is_array( $current ) ? $current : array() ) ) ) ) {
				throw new RuntimeException( 'Defaults fingerprint is stale for ' . $slug . '.' );
			}
		}
		foreach ( is_array( $plan['pages'] ?? null ) ? $plan['pages'] : array() as $change ) {
			$post_id = (int) ( $change['post_id'] ?? 0 );
			$slug = (string) ( $change['slug'] ?? '' );
			if ( 'publish' !== get_post_status( $post_id ) || $slug !== get_post_field( 'post_name', $post_id ) ) {
				throw new RuntimeException( 'Page identity check failed for ' . $slug . '.' );
			}
			$current = array(
				'builder_enabled' => (bool) get_field( self::BUILDER_ENABLED, $post_id, false ),
				'rows' => self::normalizeRawValue( get_field( self::BUILDER_ROWS, $post_id, false ) ),
			);
			if ( ! hash_equals( (string) ( $change['fingerprint'] ?? '' ), hash( 'sha256', self::stable( $current ) ) ) ) {
				throw new RuntimeException( 'Page fingerprint is stale for ' . $slug . '.' );
			}
		}
	}

	/** @param array<string,mixed> $plan @return array<string,mixed> */
	private static function captureBackup( array $plan ): array {
		global $wpdb;
		$backup = array( 'schema' => self::SCHEMA . '-backup', 'defaults' => array(), 'option_records' => array(), 'pages' => array() );
		foreach ( is_array( $plan['defaults'] ?? null ) ? $plan['defaults'] : array() as $change ) {
			$slug = (string) ( $change['slug'] ?? '' );
			$field_name = str_replace( '-', '_', $slug );
			$backup['defaults'][ $slug ] = get_field( $field_name, self::DEFAULT_CONTEXT, false );
			if ( isset( $wpdb->options ) ) {
				$prefix     = self::DEFAULT_CONTEXT . '_' . $field_name;
				$like_value = $wpdb->esc_like( $prefix . '_' ) . '%';
				$like_ref   = $wpdb->esc_like( '_' . $prefix . '_' ) . '%';
				$backup['option_records'][ $slug ] = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT option_id, option_name, option_value, autoload FROM {$wpdb->options} WHERE option_name IN (%s, %s) OR option_name LIKE %s OR option_name LIKE %s ORDER BY option_id",
						$prefix,
						'_' . $prefix,
						$like_value,
						$like_ref
					),
					ARRAY_A
				);
			}
		}
		foreach ( is_array( $plan['pages'] ?? null ) ? $plan['pages'] : array() as $change ) {
			$post_id = (int) ( $change['post_id'] ?? 0 );
			$page_backup = array(
				'slug'            => get_post_field( 'post_name', $post_id ),
				'builder_enabled' => get_field( self::BUILDER_ENABLED, $post_id, false ),
				'rows'            => get_field( self::BUILDER_ROWS, $post_id, false ),
			);
			if ( isset( $wpdb->postmeta ) ) {
				$page_backup['postmeta_records'] = $wpdb->get_results(
					$wpdb->prepare( "SELECT meta_id, post_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d ORDER BY meta_id", $post_id ),
					ARRAY_A
				);
			} else {
				$page_backup['postmeta_records'] = function_exists( 'get_post_meta' ) ? get_post_meta( $post_id ) : array();
			}
			$backup['pages'][ $post_id ] = $page_backup;
		}
		return $backup;
	}

	private static function isAbsolutePath( string $path ): bool {
		return 1 === preg_match( '/^[A-Za-z]:[\\\\\/]/', $path ) || str_starts_with( $path, '/' );
	}

	/** @param mixed $actual @param mixed $expected */
	private static function equivalentValue( $actual, $expected ): bool {
		if ( $actual === $expected || ( self::semanticallyEmpty( $actual ) && self::semanticallyEmpty( $expected ) ) ) {
			return true;
		}
		if ( is_array( $actual ) && is_array( $expected ) ) {
			$actual_is_list   = array_is_list( $actual );
			$expected_is_list = array_is_list( $expected );
			if ( $actual_is_list !== $expected_is_list || ( $actual_is_list && count( $actual ) !== count( $expected ) ) ) {
				return false;
			}
			if ( ! $actual_is_list ) {
				foreach ( $actual as $key => $value ) {
					if ( ! array_key_exists( $key, $expected ) && ! self::semanticallyEmpty( $value ) ) {
						return false;
					}
				}
				foreach ( $expected as $key => $value ) {
					if ( ! array_key_exists( $key, $actual ) && ! self::semanticallyEmpty( $value ) ) {
						return false;
					}
				}
			}
			foreach ( $expected as $key => $value ) {
				if ( ! array_key_exists( $key, $actual ) ) {
					if ( self::semanticallyEmpty( $value ) ) {
						continue;
					}
					return false;
				}
				if ( ! self::equivalentValue( $actual[ $key ], $value ) ) {
					return false;
				}
			}
			return true;
		}
		if ( in_array( $actual, array( null, false, '' ), true ) && in_array( $expected, array( null, false, '' ), true ) ) {
			return true;
		}
		if ( is_numeric( $actual ) && is_numeric( $expected ) ) {
			return (string) (float) $actual === (string) (float) $expected;
		}
		return $actual === $expected;
	}

	/** @param mixed $value */
	private static function semanticallyEmpty( $value ): bool {
		if ( in_array( $value, array( null, false, '' ), true ) || array() === $value ) {
			return true;
		}
		if ( is_array( $value ) ) {
			foreach ( $value as $child ) {
				if ( ! self::semanticallyEmpty( $child ) ) {
					return false;
				}
			}
			return true;
		}
		return false;
	}

	/** @param array<int,array<string,mixed>> $rows @return array<string,array<int,array<string,mixed>>> */
	private static function rowsBySlug( array $rows ): array {
		$result = array();
		foreach ( $rows as $row ) {
			if ( is_array( $row ) ) {
				$result[ (string) ( $row['acf_fc_layout'] ?? '' ) ][] = $row;
			}
		}
		return $result;
	}

	/** @param array<string,mixed> $data */
	private static function writeJsonFile( string $path, array $data, bool $exclusive ): void {
		$directory = dirname( $path );
		if ( ! is_dir( $directory ) || ! is_writable( $directory ) ) {
			throw new RuntimeException( 'Output directory is missing or not writable: ' . $directory );
		}
		if ( $exclusive && file_exists( $path ) ) {
			throw new RuntimeException( 'Backup file already exists; refusing to overwrite it.' );
		}
		$flags = $exclusive ? LOCK_EX : 0;
		if ( false === file_put_contents( $path, self::json( $data ) . PHP_EOL, $flags ) ) {
			throw new RuntimeException( 'Could not write JSON file: ' . $path );
		}
	}

	/** @param mixed $value */
	private static function stable( $value ): string {
		return (string) json_encode( $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION );
	}

	/** @param mixed $value */
	private static function json( $value ): string {
		return (string) wp_json_encode( $value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}
}
