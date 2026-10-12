<?php

namespace Platejka\Core\Cli;

use Platejka\Core\Acf\LegacyFieldVisibility;
use RuntimeException;

defined( 'ABSPATH' ) || exit;

/** Preview-first migration from legacy first-screen fields to internal Hero rows. */
final class InternalHeroMigrationCommand {
	private const BUILDER_ENABLED = 'field_platejka_page_builder_v1';
	private const BUILDER_ROWS    = 'field_platejka_sections_v1';
	private const LEGACY_TITLE    = 'field_66051d3d9621e';
	private const LEGACY_SUBTITLE = 'field_66c7356f59226';

	/** @param array<int,string> $args @param array<string,mixed> $assoc_args */
	public function __invoke( array $args, array $assoc_args ): void {
		unset( $args );
		$apply  = isset( $assoc_args['apply'] );
		$report = self::report( $apply );
		\WP_CLI::line( (string) wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
		\WP_CLI::success( ! empty( $report['already_migrated'] ) ? 'Internal Hero migration was already applied; no writes performed.' : ( $apply ? 'Internal Hero migration applied and verified.' : 'Dry-run only; no writes performed.' ) );
	}

	/** @return array<string,mixed> */
	public static function preview(): array {
		return self::report( false );
	}

	/** @return array<string,mixed> */
	public static function apply(): array {
		return self::report( true );
	}

	public static function isEligibleStatus( string $status ): bool {
		return in_array( $status, array( 'publish', 'future', 'draft', 'pending', 'private' ), true );
	}

	public static function isExcludedPage( int $page_id ): bool {
		return in_array( $page_id, array( 22, 24, 1873, 3632 ), true );
	}

	public static function shouldSwitchToDefaultTemplate( int $page_id ): bool {
		return in_array( $page_id, array( 1860, 2075, 2098, 2109 ), true );
	}

	/** @return array<string,mixed> */
	public static function buildHeroRow( string $title, string $subtitle ): array {
		return array(
			'acf_fc_layout'       => 'hero',
			'enabled'             => 1,
			'anchor'              => '',
			'shared_content_only' => 1,
			'overrides'           => array(
				'hero' => array(
					'background_color' => '#25252a',
					'background_image' => 'theme://sections/hero/img/hero-bg.png',
					'blur_image'       => 'theme://sections/hero/img/hero-blur.png',
					'title'            => array(
						'primary'   => $title,
						'secondary' => '',
					),
					'subtitle'         => $subtitle,
				),
			),
		);
	}

	/**
	 * @param array<int,mixed>       $rows
	 * @param array<string,mixed>     $hero_row
	 * @return array{rows:array<int,mixed>,conflict:bool,recovered:bool}
	 */
	public static function upsertHeroRow( array $rows, array $hero_row, bool $recover_incomplete = false ): array {
		foreach ( $rows as $index => $row ) {
			if ( is_array( $row ) && 'hero' === ( $row['acf_fc_layout'] ?? '' ) ) {
				if ( $recover_incomplete && 0 === $index && self::isRecoverableHeroRow( $row, $hero_row ) ) {
					$rows[0] = $hero_row;
					return array( 'rows' => array_values( $rows ), 'conflict' => false, 'recovered' => true );
				}
				return array( 'rows' => $rows, 'conflict' => true, 'recovered' => false );
			}
		}
		array_unshift( $rows, $hero_row );
		return array( 'rows' => array_values( $rows ), 'conflict' => false, 'recovered' => false );
	}

	/** @return array<string,mixed> */
	private static function report( bool $apply ): array {
		if ( ! function_exists( 'get_field' ) || ! function_exists( 'update_field' ) ) {
			throw new RuntimeException( 'ACF is required for the internal Hero migration.' );
		}

		$already_migrated = 1 === (int) get_option( LegacyFieldVisibility::HERO_VERSION_OPTION, 0 );
		$media_map        = array();
		$reused_media     = array();
		$missing_media    = array();
		foreach ( self::mediaSources( self::buildHeroRow( '', '' ) ) as $source ) {
			$attachment_id = self::attachmentForSource( $source );
			if ( $attachment_id ) {
				$media_map[ $source ] = $attachment_id;
				$reused_media[]       = array( 'source' => $source, 'attachment_id' => $attachment_id );
			} else {
				$missing_media[] = $source;
			}
		}
		$imported_media = array();
		if ( $apply && ! $already_migrated ) {
			foreach ( $missing_media as $source ) {
				$attachment_id       = self::importThemeMedia( $source );
				$media_map[ $source ] = $attachment_id;
				$imported_media[]     = array( 'source' => $source, 'attachment_id' => $attachment_id );
			}
		}
		if ( $already_migrated ) {
			return array(
				'mode'             => $apply ? 'apply' : 'dry-run',
				'already_migrated' => true,
				'totals'           => array( 'target_pages' => 0, 'conflicts' => 0, 'writes' => 0 ),
				'pages'            => array(),
				'conflicts'        => array(),
				'media'            => array( 'reused' => $reused_media, 'imported' => array(), 'to_import' => array() ),
				'writes'           => array( 'performed' => false, 'planned' => array() ),
			);
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
		$conflicts = array();
		$writes    = array();
		foreach ( $page_ids as $page_id_value ) {
			$page_id = (int) $page_id_value;
			$status  = (string) get_post_status( $page_id );
			if ( ! self::isEligibleStatus( $status ) || self::isExcludedPage( $page_id ) ) {
				continue;
			}

			$title    = trim( (string) get_field( self::LEGACY_TITLE, $page_id, false ) );
			$subtitle = trim( (string) get_field( self::LEGACY_SUBTITLE, $page_id, false ) );
			if ( '' === $title && '' === $subtitle ) {
				continue;
			}

			$current_rows = get_field( self::BUILDER_ROWS, $page_id, false );
			$current_rows = is_array( $current_rows ) ? self::normalizeRawValue( $current_rows ) : array();
			$hero_row     = self::replaceMediaSources( self::buildHeroRow( $title, $subtitle ), $media_map );
			$replacement  = self::upsertHeroRow( $current_rows, $hero_row, ! $already_migrated );
			$template     = (string) get_page_template_slug( $page_id );
			$page          = array(
				'id'              => $page_id,
				'title'           => get_the_title( $page_id ),
				'status'          => $status,
				'current_template' => $template,
				'target_template'  => self::shouldSwitchToDefaultTemplate( $page_id ) ? 'default' : $template,
				'legacy_title'    => $title,
				'legacy_subtitle' => $subtitle,
			);

			if ( $replacement['conflict'] ) {
				$conflicts[] = $page;
				continue;
			}

			$current_enabled = (bool) get_field( self::BUILDER_ENABLED, $page_id, false );
			if ( self::acfStable( $current_rows ) !== self::acfStable( $replacement['rows'] ) ) {
				$writes[] = array( 'post_id' => $page_id, 'field' => self::BUILDER_ROWS, 'current' => $current_rows, 'target' => $replacement['rows'] );
			}
			if ( ! $current_enabled ) {
				$writes[] = array( 'post_id' => $page_id, 'field' => self::BUILDER_ENABLED, 'current' => false, 'target' => true );
			}
			if ( self::shouldSwitchToDefaultTemplate( $page_id ) && ! in_array( $template, array( '', 'default' ), true ) ) {
				$writes[] = array( 'post_id' => $page_id, 'field' => '_wp_page_template', 'current' => $template, 'target' => 'default' );
			}
			$page['target_sections'] = array_values( array_filter( array_column( $replacement['rows'], 'acf_fc_layout' ), 'is_string' ) );
			$page['recovered']       = $replacement['recovered'];
			$pages[] = $page;
		}

		if ( ! $already_migrated ) {
			$writes[] = array(
				'post_id' => 'wp_options',
				'field'   => LegacyFieldVisibility::HERO_VERSION_OPTION,
				'current' => 0,
				'target'  => 1,
			);
		}

		$performed = false;
		if ( $apply && ! $already_migrated ) {
			foreach ( $writes as $write ) {
				if ( 'wp_options' === $write['post_id'] ) {
					continue;
				}
				if ( '_wp_page_template' === $write['field'] ) {
					update_post_meta( (int) $write['post_id'], '_wp_page_template', 'default' );
				} else {
					update_field( $write['field'], $write['target'], (int) $write['post_id'] );
				}
			}
			self::verifyWrites( $writes );
			update_option( LegacyFieldVisibility::HERO_VERSION_OPTION, 1, false );
			if ( 1 !== (int) get_option( LegacyFieldVisibility::HERO_VERSION_OPTION, 0 ) ) {
				throw new RuntimeException( 'Internal Hero values were verified, but the completion marker could not be saved.' );
			}
			$performed = true;
		}

		return array(
			'mode'             => $apply ? 'apply' : 'dry-run',
			'already_migrated' => $already_migrated,
			'totals'           => array(
				'target_pages' => count( $pages ),
				'conflicts'    => count( $conflicts ),
				'writes'       => count( $writes ),
			),
			'pages'            => $pages,
			'conflicts'        => $conflicts,
			'media'            => array(
				'reused'    => $reused_media,
				'imported'  => $imported_media,
				'to_import' => $apply ? array() : $missing_media,
			),
			'writes'           => array( 'performed' => $performed, 'planned' => $writes ),
		);
	}

	/** @param array<string,mixed> $row @param array<string,mixed> $target */
	private static function isRecoverableHeroRow( array $row, array $target ): bool {
		$current = is_array( $row['overrides']['hero'] ?? null ) ? $row['overrides']['hero'] : array();
		$expected = is_array( $target['overrides']['hero'] ?? null ) ? $target['overrides']['hero'] : array();
		$current_title = is_array( $current['title'] ?? null ) ? $current['title'] : array();
		$expected_title = is_array( $expected['title'] ?? null ) ? $expected['title'] : array();
		$empty_media = static fn( $value ): bool => in_array( $value, array( 0, '0', false, null, '' ), true );

		return ! empty( $row['shared_content_only'] )
			&& '#25252a' === ( $current['background_color'] ?? null )
			&& (string) ( $current_title['primary'] ?? '' ) === (string) ( $expected_title['primary'] ?? '' )
			&& '' === (string) ( $current_title['secondary'] ?? '' )
			&& (string) ( $current['subtitle'] ?? '' ) === (string) ( $expected['subtitle'] ?? '' )
			&& $empty_media( $current['background_image'] ?? null )
			&& $empty_media( $current['blur_image'] ?? null );
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

		$path = get_theme_file_path( ltrim( substr( $source, 8 ), '/' ) );
		if ( ! is_file( $path ) ) {
			return 0;
		}
		$candidates = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'meta_key'       => '_wp_attached_file', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => wp_basename( $path ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'meta_compare'   => 'LIKE',
			)
		);
		$source_hash = hash_file( 'sha256', $path );
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
			throw new RuntimeException( 'Unsafe internal Hero media path: ' . $source );
		}
		$path = get_theme_file_path( $relative );
		if ( ! is_file( $path ) ) {
			throw new RuntimeException( 'Internal Hero media file does not exist: ' . $source );
		}
		$bits = wp_upload_bits( wp_basename( $path ), null, (string) file_get_contents( $path ) );
		if ( ! empty( $bits['error'] ) ) {
			throw new RuntimeException( 'Internal Hero media import failed for ' . $source . ': ' . $bits['error'] );
		}
		$filetype = wp_check_filetype( $bits['file'] );
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

	/** @param mixed $value @param array<string,mixed>|null $field @return mixed */
	private static function normalizeRawValue( $value, ?array $field = null ) {
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
				$normalized[ $child_key ] = self::normalizeRawValue( $child, $child_field );
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

	/** @param mixed $value */
	private static function stable( $value ): string {
		return (string) wp_json_encode( $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}

	/** @param mixed $value */
	private static function acfStable( $value ): string {
		return self::stable( self::compactAcfValue( self::normalizeRawValue( $value ) ) );
	}

	/** @param mixed $value @return mixed */
	private static function compactAcfValue( $value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}
		$list = array_is_list( $value );
		$compacted = array();
		foreach ( $value as $key => $child ) {
			if ( ! self::hasMeaningfulValue( $child ) ) {
				continue;
			}
			if ( $list ) {
				$compacted[] = self::compactAcfValue( $child );
			} else {
				$compacted[ $key ] = self::compactAcfValue( $child );
			}
		}
		if ( ! $list ) {
			ksort( $compacted, SORT_STRING );
		}
		return $compacted;
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
			if ( '_wp_page_template' === $write['field'] ) {
				$actual = (string) get_page_template_slug( (int) $write['post_id'] );
				if ( ! in_array( $actual, array( '', 'default' ), true ) ) {
					throw new RuntimeException( 'Internal Hero template verification failed for page ' . (int) $write['post_id'] . '.' );
				}
				continue;
			}
			$actual = get_field( $write['field'], (int) $write['post_id'], false );
			$actual = is_array( $actual ) ? self::normalizeRawValue( $actual ) : $actual;
			if ( self::acfStable( $actual ) !== self::acfStable( $write['target'] ) ) {
				throw new RuntimeException( 'Internal Hero migration verification failed for ' . $write['field'] . ' on page ' . (int) $write['post_id'] . '.' );
			}
		}
	}
}
