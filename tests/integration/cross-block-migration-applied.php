<?php
/** Post-apply verification for the approved cross-block migration manifest. */

$manifest_path = WP_CONTENT_DIR . '/database/backups/cross-block-defaults-migration-manifest-20261013.json';
if ( ! is_readable( $manifest_path ) ) {
	WP_CLI::error( 'Approved cross-block manifest is unavailable.' );
}
$manifest = json_decode( (string) file_get_contents( $manifest_path ), true );
if ( ! is_array( $manifest ) ) {
	WP_CLI::error( 'Approved cross-block manifest is invalid.' );
}

foreach ( $manifest['plan']['defaults'] ?? array() as $change ) {
	$slug   = (string) ( $change['slug'] ?? '' );
	$field  = str_replace( '-', '_', $slug );
	$actual = get_field( $field, 'platejka_section_defaults' );
	if ( ! is_array( $actual ) || array() === $actual ) {
		WP_CLI::error( 'Cross-block defaults are empty after apply: ' . $slug );
	}
}

foreach ( $manifest['plan']['pages'] ?? array() as $change ) {
	$post_id = (int) ( $change['post_id'] ?? 0 );
	$slug    = (string) ( $change['slug'] ?? '' );
	if ( 'publish' !== get_post_status( $post_id ) || $slug !== get_post_field( 'post_name', $post_id ) ) {
		WP_CLI::error( 'Applied page identity changed: ' . $slug );
	}
	if ( ! get_field( 'field_platejka_page_builder_v1', $post_id, false ) ) {
		WP_CLI::error( 'Builder was not enabled after apply: ' . $slug );
	}
	$rows = get_field( 'platejka_sections', $post_id );
	$faq  = null;
	foreach ( is_array( $rows ) ? $rows : array() as $index => $row ) {
		if ( 'faq' === ( $row['acf_fc_layout'] ?? '' ) ) {
			$faq = $index;
			break;
		}
	}
	foreach ( is_array( $rows ) ? $rows : array() as $index => $row ) {
		if ( str_starts_with( (string) ( $row['acf_fc_layout'] ?? '' ), 'cross-' ) && null !== $faq && $index > $faq ) {
			WP_CLI::error( 'A migrated cross-block is positioned after FAQ: ' . $slug );
		}
	}
}

WP_CLI::success( 'Applied cross-block values and builder placement are present.' );
