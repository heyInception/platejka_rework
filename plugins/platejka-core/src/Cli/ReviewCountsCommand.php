<?php

namespace Platejka\Core\Cli;

defined( 'ABSPATH' ) || exit;

/** Store verified review counts from tools/review_counts.py. */
final class ReviewCountsCommand {
	/**
	 * Import counts from a local JSON file.
	 *
	 * ## OPTIONS
	 * <file>
	 * : JSON file produced by review_counts.py.
	 *
	 * @param array<int,string> $args Positional arguments.
	 */
	public function __invoke( array $args ): void {
		$path = $args[0] ?? '';
		if ( ! is_file( $path ) || ! is_readable( $path ) ) {
			\WP_CLI::error( 'Counts JSON file is not readable.' );
		}
		$data = json_decode( (string) file_get_contents( $path ), true );
		$counts = $data['counts'] ?? null;
		if ( ! is_array( $counts ) ) {
			\WP_CLI::error( 'Counts JSON has no counts object.' );
		}
		foreach ( array( 'yandex', '2gis', 'yell' ) as $source ) {
			if ( ! isset( $counts[ $source ] ) || ! is_int( $counts[ $source ] ) || $counts[ $source ] < 0 ) {
				\WP_CLI::error( 'Invalid count for ' . $source . '.' );
			}
		}
		update_option( 'platejka_review_counts', array_intersect_key( $counts, array_flip( array( 'yandex', '2gis', 'yell' ) ) ), false );
		update_option( 'platejka_review_counts_updated_at', sanitize_text_field( (string) ( $data['updated_at'] ?? '' ) ), false );
		\WP_CLI::success( sprintf( 'Yandex: %d, 2GIS: %d, Yell: %d.', $counts['yandex'], $counts['2gis'], $counts['yell'] ) );
	}
}
