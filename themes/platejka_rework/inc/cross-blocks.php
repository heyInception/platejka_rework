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

/** @return array<string,array{directory:string,template:string}> */
function platejka_cross_block_aliases(): array {
	$alias = array( 'directory' => 'cross-blocks', 'template' => 'cross-blocks' );
	return array_fill_keys(
		array( 'cross-exchange', 'cross-company-metrics', 'cross-external-ratings', 'cross-key-facts', 'cross-transfer-goals', 'cross-swift-problems', 'cross-audience' ),
		$alias
	);
}

/** @return array<string,float> */
function platejka_cross_block_exchange_rates(): array {
	$rates   = function_exists( 'platejka_core_get_exchange_rates' ) ? platejka_core_get_exchange_rates() : array();
	$default = array( 'USD' => 81.0929, 'EUR' => 96.75, 'CNY' => 12.16 );
	$result  = array();
	foreach ( $default as $currency => $fallback ) {
		$value               = $rates[ $currency ] ?? $fallback;
		$result[ $currency ] = is_numeric( $value ) && is_finite( (float) $value ) && (float) $value > 0 ? (float) $value : $fallback;
	}
	return $result;
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

/** @param mixed $media */
function platejka_cross_block_media_id( $media ): int {
	if ( is_array( $media ) ) {
		$media = $media['ID'] ?? ( $media['id'] ?? 0 );
	}

	return absint( $media );
}

/** @param array<string,mixed>|int|string $image */
function platejka_cross_block_icon( $image, string $alt = '' ): string {
	$image_id = platejka_cross_block_media_id( $image );
	if ( $image_id && function_exists( 'platejka_section_image' ) ) {
		return platejka_section_image( $image_id, 'thumbnail', array( 'class' => 'cb-card__icon-image', 'alt' => $alt, 'loading' => 'lazy' ) );
	}

	$url = is_array( $image ) ? (string) ( $image['url'] ?? '' ) : ( is_string( $image ) ? $image : '' );
	if ( '' === $url ) {
		return '';
	}

	return sprintf(
		'<img class="cb-card__icon-image" src="%1$s" alt="%2$s" loading="lazy" decoding="async">',
		esc_url( $url ),
		esc_attr( is_array( $image ) ? (string) ( $image['alt'] ?? $alt ) : $alt )
	);
}

function platejka_cross_block_fallback_icon(): string {
	return '<svg class="cb-card__icon-fallback" viewBox="0 0 48 48" aria-hidden="true" focusable="false"><path d="M24 4 7 11v11c0 10.8 7.3 18.6 17 22 9.7-3.4 17-11.2 17-22V11L24 4Zm-2.2 26.3-7-7 3-3 4 4 8.5-8.5 3 3-11.5 11.5Z"/></svg>';
}

/** @param array<string,mixed>|null $link */
function platejka_cross_block_cta( ?array $link, string $class = '' ): string {
	if ( empty( $link['url'] ) || empty( $link['title'] ) ) {
		return '';
	}

	$target = '_blank' === ( $link['target'] ?? '' ) ? '_blank' : '_self';
	return sprintf(
		'<a class="cb-button %1$s" href="%2$s" target="%3$s"%4$s>%5$s</a>',
		esc_attr( $class ),
		esc_url( $link['url'] ),
		esc_attr( $target ),
		'_blank' === $target ? ' rel="noopener noreferrer"' : '',
		esc_html( $link['title'] )
	);
}

/** @param mixed $items @return array<int,array<string,mixed>> */
function platejka_cross_block_card_items( $items ): array {
	$adapted = array();
	foreach ( is_array( $items ) ? $items : array() as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}

		$title = trim( (string) ( $item['title'] ?? '' ) );
		$text  = trim( (string) ( $item['text'] ?? '' ) );
		if ( '' === $title && '' === wp_strip_all_tags( $text ) ) {
			continue;
		}

		$adapted[] = array(
			'image' => platejka_cross_block_media_id( $item['icon'] ?? ( $item['image'] ?? 0 ) ),
			'title' => $title,
			'text'  => $text,
			'value' => trim( (string) ( $item['value'] ?? '' ) ),
		);
	}

	return $adapted;
}

/** @param mixed $cell */
function platejka_cross_block_table_cell( $cell ): string {
	return trim( (string) ( is_array( $cell ) ? ( $cell['c'] ?? '' ) : $cell ) );
}

/**
 * Convert one approved legacy row to the target section data shape.
 *
 * @param array<string,mixed> $row Legacy ACF row.
 * @return array<string,mixed>
 */
function platejka_adapt_cross_block_data( string $layout, array $row ): array {
	switch ( $layout ) {
		case 'destinations':
			$items = array();
			foreach ( is_array( $row['destination_items'] ?? null ) ? $row['destination_items'] : array() as $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}
				$title = trim( (string) ( $item['name'] ?? ( $item['nazvanie'] ?? '' ) ) );
				if ( '' === $title ) {
					continue;
				}
				$items[] = array(
					'image' => platejka_cross_block_media_id( $item['image'] ?? ( $item['izobrazhenie'] ?? 0 ) ),
					'title' => $title,
					'text'  => '',
					'link'  => is_array( $item['link'] ?? null ) ? $item['link'] : ( is_array( $item['ssylka'] ?? null ) ? $item['ssylka'] : array() ),
				);
			}
			return array(
				'title'       => trim( (string) ( $row['destination_title'] ?? '' ) ),
				'description' => (string) ( $row['destination_subtitle'] ?? '' ),
				'items'       => $items,
			);

		case 'problems_solved':
		case 'guarantees':
			return array(
				'title'       => trim( (string) ( $row['title'] ?? '' ) ),
				'description' => (string) ( $row['intro'] ?? '' ),
				'items'       => platejka_cross_block_card_items( $row['items'] ?? array() ),
			);

		case 'client_reviews':
			$items = array();
			foreach ( is_array( $row['reviews'] ?? null ) ? $row['reviews'] : array() as $review ) {
				if ( ! is_array( $review ) ) {
					continue;
				}
				$name = trim( (string) ( $review['name'] ?? '' ) );
				$text = trim( (string) ( $review['text'] ?? '' ) );
				if ( '' === $name || '' === wp_strip_all_tags( $text ) ) {
					continue;
				}
				$rating  = max( 1, min( 5, absint( $review['rating'] ?? 5 ) ) );
				$caption = trim( (string) ( $review['role'] ?? '' ) );
				if ( '' === $caption ) {
					$caption = trim( (string) ( $review['source'] ?? '' ) );
				}
				$items[] = array(
					'image'   => platejka_cross_block_media_id( $review['photo'] ?? 0 ),
					'title'   => $name,
					'text'    => $text,
					'value'   => str_repeat( '★', $rating ),
					'caption' => $caption,
					'date'    => trim( (string) ( $review['date'] ?? '' ) ),
					'source'  => trim( (string) ( $review['source'] ?? '' ) ),
				);
			}
			return array(
				'title' => trim( (string) ( $row['title'] ?? '' ) ),
				'items' => $items,
			);

		case 'documents':
			$items = array();
			foreach ( is_array( $row['documents'] ?? null ) ? $row['documents'] : array() as $document ) {
				if ( ! is_array( $document ) || empty( $document['file'] ) ) {
					continue;
				}
				$file    = $document['file'];
				$file_id = platejka_cross_block_media_id( $file );
				$url     = is_array( $file ) ? (string) ( $file['url'] ?? '' ) : '';
				if ( ! $file_id && '' === $url ) {
					continue;
				}
				$caption = '';
				if ( is_array( $file ) && ! empty( $file['filesize'] ) && function_exists( 'size_format' ) ) {
					$caption = 'PDF, ' . size_format( (int) $file['filesize'], 1 );
				}
				$items[] = array(
					'title'   => trim( (string) ( $document['title'] ?? '' ) ),
					'text'    => (string) ( $document['description'] ?? '' ),
					'file'    => $file_id,
					'caption' => $caption,
					'link'    => array( 'url' => $url, 'title' => __( 'Скачать', 'platejka_rework' ) ),
				);
			}
			return array(
				'title'       => trim( (string) ( $row['title'] ?? '' ) ),
				'description' => (string) ( $row['intro'] ?? '' ),
				'items'       => $items,
			);

		case 'data_table':
			$items = array();
			$table = is_array( $row['table_data'] ?? null ) ? $row['table_data'] : array();
			foreach ( is_array( $table['body'] ?? null ) ? $table['body'] : array() as $table_row ) {
				if ( ! is_array( $table_row ) ) {
					continue;
				}
				$cells = array_map( 'platejka_cross_block_table_cell', array_values( $table_row ) );
				if ( '' === implode( '', $cells ) ) {
					continue;
				}
				$items[] = array(
					'title'         => $cells[0] ?? '',
					'value'         => $cells[1] ?? '',
					'text'          => '',
					'partner_value' => $cells[2] ?? '',
					'partner_text'  => '',
					'bank_value'    => $cells[3] ?? '',
					'bank_text'     => '',
				);
			}
			return array(
				'title'       => trim( (string) ( $row['title'] ?? '' ) ),
				'description' => (string) ( $row['intro'] ?? '' ),
				'items'       => $items,
			);
	}

	return $row;
}

/**
 * Merge meaningful cross-block values over resolved current-theme data.
 *
 * @param array<string,mixed> $cross_data Adapted legacy data.
 * @param array<string,mixed> $resolved   Current section data.
 * @return array<string,mixed>
 */
function platejka_merge_cross_block_data( array $cross_data, array $resolved ): array {
	$cross_data = array_filter(
		$cross_data,
		static fn( $value ): bool => ! ( null === $value || false === $value || '' === $value || array() === $value )
	);

	return function_exists( 'platejka_merge_section_data' )
		? (array) platejka_merge_section_data( $cross_data, $resolved )
		: array_replace( $resolved, $cross_data );
}

/** @return array<string,string> Builder slug to legacy layout. */
function platejka_cross_block_builder_map(): array {
	return array(
		'cross-exchange'          => 'wtt',
		'cross-company-metrics'   => 'company_metrics',
		'cross-external-ratings'  => 'external_ratings',
		'cross-key-facts'         => 'key_facts',
		'cross-transfer-goals'    => 'transfer_goals',
		'cross-swift-problems'    => 'swift_problems',
		'cross-audience'          => 'audience',
	);
}

/** Resolve the legacy layout represented by a current builder section. */
function platejka_cross_block_layout_for_section( string $slug ): string {
	return platejka_cross_block_builder_map()[ $slug ] ?? '';
}
