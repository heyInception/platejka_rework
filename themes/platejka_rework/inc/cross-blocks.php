<?php
/**
 * Legacy cross-block resolution and page composition.
 *
 * @package platejka_rework
 */

defined( 'ABSPATH' ) || exit;

/**
 * Approved legacy layouts and their target section adapters.
 *
 * @return array<string,array{slug:string,adapter:string}>
 */
function platejka_cross_block_map(): array {
	return array(
		'destinations'      => array( 'slug' => 'destinations', 'adapter' => 'destinations' ),
		'wtt'               => array( 'slug' => 'cross-exchange', 'adapter' => 'exchange' ),
		'company_metrics'   => array( 'slug' => 'cross-company-metrics', 'adapter' => 'company_metrics' ),
		'problems_solved'   => array( 'slug' => 'problems', 'adapter' => 'problems' ),
		'client_reviews'    => array( 'slug' => 'review', 'adapter' => 'reviews' ),
		'external_ratings'  => array( 'slug' => 'cross-external-ratings', 'adapter' => 'external_ratings' ),
		'documents'         => array( 'slug' => 'documents', 'adapter' => 'documents' ),
		'key_facts'         => array( 'slug' => 'cross-key-facts', 'adapter' => 'key_facts' ),
		'transfer_goals'    => array( 'slug' => 'cross-transfer-goals', 'adapter' => 'card_grid' ),
		'swift_problems'    => array( 'slug' => 'cross-swift-problems', 'adapter' => 'card_grid' ),
		'guarantees'        => array( 'slug' => 'guarantees', 'adapter' => 'guarantees' ),
		'audience'          => array( 'slug' => 'cross-audience', 'adapter' => 'card_grid' ),
		'data_table'        => array( 'slug' => 'table', 'adapter' => 'table' ),
	);
}

/** @param array<string,mixed> $row Legacy ACF row. */
function platejka_cross_block_is_enabled( array $row ): bool {
	foreach ( array( 'show_block', 'vklyuchit_blok' ) as $field ) {
		if ( array_key_exists( $field, $row ) && ! (bool) $row[ $field ] ) {
			return false;
		}
	}

	return true;
}

/**
 * Read formatted legacy rows without changing stored postmeta.
 *
 * @return array<int,array<string,mixed>>
 */
function platejka_get_cross_block_rows( int $post_id ): array {
	if ( ! function_exists( 'get_field' ) ) {
		return array();
	}

	$rows = get_field( 'field_67924d166072f', $post_id );
	if ( ! is_array( $rows ) ) {
		$rows = get_field( 'skvoznye_bloki', $post_id );
	}

	return is_array( $rows ) ? array_values( array_filter( $rows, 'is_array' ) ) : array();
}

/** @param mixed $section */
function platejka_cross_block_section_slug( $section ): string {
	if ( is_string( $section ) ) {
		return $section;
	}

	return is_array( $section ) && is_string( $section['slug'] ?? null ) ? $section['slug'] : '';
}

/**
 * Attach one cross-block row to a normalized-or-normalizable section config.
 *
 * @param mixed               $section Existing section.
 * @param array<string,mixed> $row     Legacy ACF row.
 * @param array{slug:string,adapter:string} $mapping Target mapping.
 * @return array<string,mixed>
 */
function platejka_enrich_section_with_cross_block( $section, array $row, string $layout, array $mapping, string $instance ): array {
	$config = is_array( $section ) ? $section : array( 'slug' => (string) $section );
	$config['legacy_layout']      = $layout;
	$config['cross_block_row']    = $row;
	$config['cross_block_adapter'] = $mapping['adapter'];
	$config['instance']           = isset( $config['instance'] ) && is_string( $config['instance'] ) && '' !== $config['instance'] ? $config['instance'] : $instance;

	return $config;
}

/**
 * Compose enabled legacy rows into the current section builder.
 *
 * @param array<int,mixed>               $sections Current page sections.
 * @param array<int,array<string,mixed>> $rows     Legacy ACF rows.
 * @return array<int,mixed>
 */
function platejka_compose_cross_blocks( array $sections, array $rows, int $post_id ): array {
	$map       = platejka_cross_block_map();
	$injected  = array();
	$processed = array();

	foreach ( $rows as $row_index => $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$layout = isset( $row['acf_fc_layout'] ) && is_string( $row['acf_fc_layout'] ) ? $row['acf_fc_layout'] : '';
		if ( ! isset( $map[ $layout ] ) || isset( $processed[ $layout ] ) || ! platejka_cross_block_is_enabled( $row ) ) {
			continue;
		}

		$processed[ $layout ] = true;
		$mapping              = $map[ $layout ];
		$instance             = sprintf( 'cross-%d-%d-%s', $post_id, $row_index + 1, sanitize_html_class( $layout ) );
		$existing_index       = null;

		foreach ( $sections as $section_index => $section ) {
			$section_slug = platejka_cross_block_section_slug( $section );
			$is_review    = 'client_reviews' === $layout && in_array( $section_slug, array( 'review', 'review-main' ), true );
			if ( $mapping['slug'] === $section_slug || $is_review ) {
				$existing_index = $section_index;
				break;
			}
		}

		if ( null !== $existing_index ) {
			$sections[ $existing_index ] = platejka_enrich_section_with_cross_block( $sections[ $existing_index ], $row, $layout, $mapping, $instance );
			continue;
		}

		$injected[] = platejka_enrich_section_with_cross_block( array( 'slug' => $mapping['slug'] ), $row, $layout, $mapping, $instance );
	}

	if ( array() === $injected ) {
		return $sections;
	}

	$boundary = count( $sections );
	foreach ( $sections as $section_index => $section ) {
		if ( in_array( platejka_cross_block_section_slug( $section ), array( 'faq', 'call' ), true ) ) {
			$boundary = $section_index;
			break;
		}
	}

	array_splice( $sections, $boundary, 0, $injected );
	return $sections;
}

