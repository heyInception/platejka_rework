<?php
/** ACF page-builder row resolution and validation. */

/** @return array<string,array{hero:bool}> */
function platejka_section_builder_registry(): array {
	$slugs = array(
		'hero-main',
		'about',
		'shipments',
		'guarantees',
		'documents',
		'compliance',
		'review-main',
		'work',
		'calculator',
		'with-us',
		'destinations',
		'seo',
		'problems',
		'serves',
		'cases',
		'table',
		'faq',
		'call',
	);

	$registry = array();
	foreach ( $slugs as $slug ) {
		$registry[ $slug ] = array( 'hero' => 'hero-main' === $slug );
	}
	return $registry;
}

/**
 * @param array<int,array<string,mixed>> $rows ACF flexible-content rows.
 * @param array<int,mixed>               $fallback Existing PHP composition.
 * @return array<int,mixed>
 */
function platejka_resolve_page_section_rows( bool $builder_enabled, array $rows, array $fallback, int $post_id ): array {
	if ( ! $builder_enabled ) {
		return $fallback;
	}

	$registry = platejka_section_builder_registry();
	$sections = array();
	foreach ( $rows as $index => $row ) {
		$slug = isset( $row['acf_fc_layout'] ) && is_string( $row['acf_fc_layout'] ) ? $row['acf_fc_layout'] : '';
		if ( ! isset( $registry[ $slug ] ) || empty( $row['enabled'] ) ) {
			continue;
		}

		$requested_mode = isset( $row['variant'] ) && is_string( $row['variant'] ) ? $row['variant'] : 'default';
		$mode           = in_array( $requested_mode, array( 'main', 'default' ), true ) ? $requested_mode : 'default';
		$anchor         = isset( $row['anchor'] ) && is_string( $row['anchor'] ) ? $row['anchor'] : '';
		$sections[]     = array(
			'slug'     => $slug,
			'mode'     => $mode,
			'row'      => $row,
			'instance' => 'section-' . $post_id . '-' . ( $index + 1 ),
			'anchor'   => $anchor,
		);
	}

	return $sections;
}

/** @return array<int,mixed> */
function platejka_get_page_sections( int $post_id, array $fallback ): array {
	if ( ! function_exists( 'get_field' ) ) {
		return $fallback;
	}

	$enabled = (bool) get_field( 'platejka_page_builder', $post_id );
	$rows    = get_field( 'platejka_sections', $post_id );
	return platejka_resolve_page_section_rows( $enabled, is_array( $rows ) ? $rows : array(), $fallback, $post_id );
}

/**
 * @param array<int,array<string,mixed>> $rows ACF flexible-content rows.
 * @return true|WP_Error
 */
function platejka_validate_builder_rows( array $rows ) {
	$hero_count = 0;
	$anchors    = array();

	foreach ( $rows as $row ) {
		$slug = isset( $row['acf_fc_layout'] ) && is_string( $row['acf_fc_layout'] ) ? $row['acf_fc_layout'] : '';
		if ( in_array( $slug, array( 'hero-main', 'hero', 'about-hero' ), true ) ) {
			++$hero_count;
		}

		$anchor = isset( $row['anchor'] ) && is_string( $row['anchor'] ) ? $row['anchor'] : '';
		if ( '' === $anchor ) {
			continue;
		}
		if ( ! preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $anchor ) ) {
			return new WP_Error( 'invalid_section_anchor', __( 'Якорь секции может содержать только строчные латинские буквы, цифры и дефисы.', 'platejka_rework' ) );
		}
		if ( isset( $anchors[ $anchor ] ) ) {
			return new WP_Error( 'duplicate_section_anchor', __( 'Якоря секций на одной странице не должны повторяться.', 'platejka_rework' ) );
		}
		$anchors[ $anchor ] = true;
	}

	if ( $hero_count > 1 ) {
		return new WP_Error( 'multiple_hero_sections', __( 'На странице разрешена только одна hero-секция.', 'platejka_rework' ) );
	}

	return true;
}

/**
 * @param mixed $valid Previous ACF validation result.
 * @param mixed $value Submitted flexible-content rows.
 * @return mixed
 */
function platejka_validate_builder_field( $valid, $value ) {
	if ( true !== $valid || ! is_array( $value ) ) {
		return $valid;
	}

	$result = platejka_validate_builder_rows( $value );
	return is_wp_error( $result ) ? $result->get_error_message() : $valid;
}
add_filter( 'acf/validate_value/key=field_platejka_sections_v1', 'platejka_validate_builder_field', 10, 2 );

/**
 * @param mixed $title Existing ACF layout title.
 * @return mixed
 */
function platejka_builder_layout_title( $title ) {
	if ( ! function_exists( 'get_sub_field' ) ) {
		return $title;
	}

	$status = get_sub_field( 'enabled' ) ? __( 'включена', 'platejka_rework' ) : __( 'выключена', 'platejka_rework' );
	$anchor = (string) get_sub_field( 'anchor' );
	return $title . ' — ' . $status . ( '' !== $anchor ? ' — #' . esc_html( $anchor ) : '' );
}
add_filter( 'acf/fields/flexible_content/layout_title/name=platejka_sections', 'platejka_builder_layout_title' );
