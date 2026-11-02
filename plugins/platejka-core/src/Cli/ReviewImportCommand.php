<?php

namespace Platejka\Core\Cli;

use Platejka\Core\Reviews\ReviewPost;
use Platejka\Core\Reviews\Settings;

defined( 'ABSPATH' ) || exit;

/** Import the JSON produced by tools/yandex_reviews.py. */
final class ReviewImportCommand {
	/**
	 * Import reviews from a local JSON file.
	 *
	 * ## OPTIONS
	 * <file>
	 * : Absolute or relative path to the JSON file.
	 *
	 * ## EXAMPLES
	 * wp platejka reviews import wp-content/plugins/platejka-core/tools/yandex-reviews-export.json
	 *
	 * @param array<int,string> $args Positional arguments.
	 */
	public function __invoke( array $args ): void {
		$path = $args[0] ?? '';
		if ( ! is_file( $path ) || ! is_readable( $path ) ) {
			\WP_CLI::error( 'JSON file is not readable.' );
		}
		$rows = json_decode( (string) file_get_contents( $path ), true );
		if ( ! is_array( $rows ) || ! array_is_list( $rows ) ) {
			\WP_CLI::error( 'JSON must contain an array of reviews.' );
		}
		$created = 0;
		$updated = 0;
		$skipped = 0;
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) || ! self::valid( $row ) ) {
				++$skipped;
				continue;
			}
			$source = sanitize_key( (string) $row['source'] );
			$source_id = sanitize_text_field( (string) $row['source_id'] );
			$existing = get_posts( array(
				'post_type'      => ReviewPost::TYPE,
				'post_status'    => 'any',
				'numberposts'    => 1,
				'fields'         => 'ids',
				'meta_query'     => array( 'relation' => 'AND',
					array( 'key' => '_platejka_review_source', 'value' => $source ),
					array( 'key' => '_platejka_review_source_id', 'value' => $source_id ),
				),
			) );
			$existing_id = (int) ( $existing[0] ?? 0 );
			$timestamp = strtotime( (string) $row['updated_at'] );
			$post = array(
				'post_type'    => ReviewPost::TYPE,
				'post_title'   => sanitize_text_field( (string) $row['author'] ),
				'post_content' => sanitize_textarea_field( (string) $row['text'] ),
			);
			if ( $existing_id ) {
				$post['ID'] = $existing_id;
				$post_id = wp_update_post( $post, true );
			} else {
				$post['post_status'] = 'automatic' === Settings::getMode() ? 'publish' : 'pending';
				if ( $timestamp ) {
					$post['post_date_gmt'] = gmdate( 'Y-m-d H:i:s', $timestamp );
					$post['post_date'] = get_date_from_gmt( $post['post_date_gmt'] );
				}
				$post_id = wp_insert_post( $post, true );
			}
			if ( is_wp_error( $post_id ) ) {
				\WP_CLI::warning( $post_id->get_error_message() );
				++$skipped;
				continue;
			}
			foreach ( array( 'source', 'source_id', 'source_url', 'author', 'rating' ) as $key ) {
				$value = (string) $row[ $key ];
				update_post_meta( $post_id, '_platejka_review_' . $key, 'source_url' === $key ? esc_url_raw( $value ) : sanitize_text_field( $value ) );
			}
			update_post_meta( $post_id, '_platejka_review_reviewed_at', $timestamp ? gmdate( 'Y-m-d', $timestamp ) : '' );
			$existing_id ? ++$updated : ++$created;
		}
		\WP_CLI::success( sprintf( 'Created: %d, updated: %d, skipped: %d.', $created, $updated, $skipped ) );
	}

	/** @param array<string,mixed> $row */
	public static function valid( array $row ): bool {
		return in_array( $row['source'] ?? null, array( 'yandex', '2gis', 'yell' ), true )
			&& is_string( $row['source_id'] ?? null ) && '' !== $row['source_id']
			&& is_string( $row['author'] ?? null ) && '' !== trim( $row['author'] )
			&& is_string( $row['text'] ?? null ) && '' !== trim( $row['text'] )
			&& is_numeric( $row['rating'] ?? null ) && (float) $row['rating'] >= 1 && (float) $row['rating'] <= 5
			&& is_string( $row['source_url'] ?? null ) && (bool) wp_http_validate_url( $row['source_url'] )
			&& is_string( $row['updated_at'] ?? null ) && false !== strtotime( $row['updated_at'] );
	}
}
