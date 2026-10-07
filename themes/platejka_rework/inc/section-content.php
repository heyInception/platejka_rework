<?php
/** Section content inheritance and safe rendering helpers. */

/** @param mixed $local @param mixed $global @return mixed */
function platejka_resolve_scalar( $local, $global ) {
	return null === $local || false === $local || '' === $local || array() === $local ? $global : $local;
}

/** @param mixed $local @param mixed $global @return array<mixed> */
function platejka_resolve_collection( $local, $global ): array {
	return is_array( $local ) && array() !== $local ? $local : ( is_array( $global ) ? $global : array() );
}

/** @param mixed $local */
function platejka_resolve_tristate( $local, bool $global ): bool {
	if ( 'on' === $local || true === $local || 1 === $local || '1' === $local ) {
		return true;
	}
	if ( 'off' === $local || false === $local || 0 === $local || '0' === $local ) {
		return false;
	}
	return $global;
}

/**
 * Merge one local ACF group into its global default.
 * Numeric repeater arrays replace wholesale; associative groups merge recursively.
 *
 * @param mixed $local
 * @param mixed $global
 * @return mixed
 */
function platejka_merge_section_data( $local, $global ) {
	if ( ! is_array( $local ) || ! is_array( $global ) ) {
		return platejka_resolve_scalar( $local, $global );
	}
	if ( array() === $local ) {
		return $global;
	}
	if ( array_is_list( $local ) || array_is_list( $global ) ) {
		return $local;
	}

	$merged = $global;
	foreach ( $local as $key => $value ) {
		$merged[ $key ] = array_key_exists( $key, $global )
			? platejka_merge_section_data( $value, $global[ $key ] )
			: $value;
	}
	return $merged;
}

/** @param mixed $value @return array<string,mixed> */
function platejka_section_array( $value ): array {
	return is_array( $value ) ? $value : array();
}

/** @param mixed $rows @return array<string,string> */
function platejka_section_label_map( $rows ): array {
	$labels = array();
	foreach ( is_array( $rows ) ? $rows : array() as $row ) {
		if ( is_array( $row ) && isset( $row['key'] ) && is_string( $row['key'] ) && '' !== $row['key'] ) {
			$labels[ $row['key'] ] = (string) ( $row['label'] ?? '' );
		}
	}
	return $labels;
}

/** Keep the rendered template slug separate from its canonical options group. */
function platejka_section_content_slug( string $template_slug ): string {
	return 'review' === $template_slug ? 'review-main' : $template_slug;
}

/**
 * Resolve an enabled builder row against options-page defaults.
 *
 * @param array<string,mixed> $config Normalized section configuration.
 * @return array<string,mixed>
 */
function platejka_resolve_section_data( array $config, int $post_id = 0 ): array {
	unset( $post_id );
	$slug         = isset( $config['slug'] ) && is_string( $config['slug'] ) ? $config['slug'] : '';
	$content_slug = isset( $config['content_slug'] ) && is_string( $config['content_slug'] )
		? $config['content_slug']
		: platejka_section_content_slug( $slug );
	if ( ! preg_match( '/^[a-z0-9-]+$/', $slug ) || ! preg_match( '/^[a-z0-9-]+$/', $content_slug ) ) {
		return array();
	}

	$field_name = str_replace( '-', '_', $content_slug );
	$mode       = isset( $config['mode'] ) && 'main' === $config['mode'] ? 'main' : 'default';
	$variant    = in_array( $slug, array( 'shipments', 'guarantees', 'documents' ), true );
	$global     = function_exists( 'get_field' ) ? platejka_section_array( get_field( $field_name, 'platejka_section_defaults' ) ) : array();

	if ( array() === $global && 1 !== (int) get_option( 'platejka_section_builder_version', 0 ) && class_exists( 'Platejka\\Core\\Acf\\SectionSeed' ) ) {
		$seed   = \Platejka\Core\Acf\SectionSeed::get();
		$global = platejka_section_array( $seed['sections'][ $content_slug ] ?? array() );
	}
	if ( array() === $global && 1 !== (int) get_option( 'platejka_default_page_builder_version', 0 ) && class_exists( 'Platejka\\Core\\Acf\\DefaultPageSeed' ) ) {
		$seed   = \Platejka\Core\Acf\DefaultPageSeed::get();
		$global = platejka_section_array( $seed['sections'][ $content_slug ] ?? array() );
	}
	if ( array() === $global && 1 !== (int) get_option( 'platejka_about_page_builder_version', 0 ) && class_exists( 'Platejka\\Core\\Acf\\AboutPageSeed' ) ) {
		$seed   = \Platejka\Core\Acf\AboutPageSeed::get();
		$global = platejka_section_array( $seed['sections'][ $content_slug ] ?? array() );
	}
	$global = platejka_section_array( apply_filters( 'platejka_section_global_data', $global, $content_slug, $field_name ) );
	if ( $variant ) {
		$global = platejka_section_array( $global[ $mode ] ?? array() );
	}

	$row   = platejka_section_array( $config['row'] ?? array() );
	$variant_override_key = 'main' === $mode ? 'overrides_main' : 'overrides_default';
	$local = platejka_section_array( $row[ $variant_override_key ] ?? ( $row['overrides'] ?? array() ) );
	// ACF clone fields may wrap the cloned group depending on their display settings.
	if ( isset( $local[ $field_name ] ) && is_array( $local[ $field_name ] ) ) {
		$local = $local[ $field_name ];
	}
	if ( $variant && isset( $local[ $mode ] ) && is_array( $local[ $mode ] ) ) {
		$local = $local[ $mode ];
	}

	return platejka_section_array( platejka_merge_section_data( $local, $global ) );
}

function platejka_section_dom_id( string $instance, string $local_id ): string {
	$sanitize = static function ( string $value ): string {
		$value = strtolower( remove_accents( $value ) );
		$value = (string) preg_replace( '/[^a-z0-9_-]+/', '-', $value );
		return trim( $value, '-' );
	};
	return trim( $sanitize( $instance ) . '-' . $sanitize( $local_id ), '-' );
}

/** @param int|mixed $attachment_id @param string|array<int,int> $size @param array<string,mixed> $attributes */
function platejka_section_image( $attachment_id, $size = 'large', array $attributes = array() ): string {
	if ( is_string( $attachment_id ) && str_starts_with( $attachment_id, 'theme://' ) ) {
		$relative = ltrim( substr( $attachment_id, 8 ), '/' );
		if ( ! preg_match( '~^[a-zA-Z0-9_./-]+$~', $relative ) || str_contains( $relative, '..' ) ) {
			return '';
		}
		$path = get_theme_file_path( $relative );
		if ( ! is_file( $path ) ) {
			return '';
		}
		$dimensions = wp_getimagesize( $path );
		$attributes = array_merge( array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async' ), $attributes );
		$attributes['src'] = get_theme_file_uri( $relative );
		if ( is_array( $dimensions ) ) {
			$attributes['width']  = $dimensions[0];
			$attributes['height'] = $dimensions[1];
		}
		$html = '<img';
		foreach ( $attributes as $name => $value ) {
			if ( '' === $value && str_starts_with( (string) $name, 'data-' ) ) {
				$html .= ' ' . esc_attr( $name );
				continue;
			}
			$html .= ' ' . esc_attr( $name ) . '="' . esc_attr( (string) $value ) . '"';
		}
		return $html . '>';
	}
	$attachment_id = absint( $attachment_id );
	if ( ! $attachment_id || ! wp_attachment_is_image( $attachment_id ) ) {
		return '';
	}
	return (string) wp_get_attachment_image( $attachment_id, $size, false, $attributes );
}

/** @param mixed $value */
function platejka_heading_text( $value ): string {
	$lines = preg_split( '/\R/u', (string) $value ) ?: array();
	return implode( '<br>', array_map( 'esc_html', $lines ) );
}

/**
 * @param array{text?:mixed,accent?:mixed,decorative?:mixed} $heading
 */
function platejka_section_heading( array $heading, string $class = '' ): string {
	$text       = trim( (string) ( $heading['text'] ?? '' ) );
	$accent     = trim( (string) ( $heading['accent'] ?? '' ) );
	$accent_position = '' !== $accent ? mb_stripos( $text, $accent ) : false;
	$semantic  = trim( wp_strip_all_tags( false === $accent_position ? $text . ( '' !== $accent ? ' ' . $accent : '' ) : $text ) );
	$class_attr = esc_attr( $class );
	if ( false !== $accent_position ) {
		$before  = mb_substr( $text, 0, $accent_position );
		$matched = mb_substr( $text, $accent_position, mb_strlen( $accent ) );
		$after   = mb_substr( $text, $accent_position + mb_strlen( $accent ) );
		$visible = platejka_heading_text( $before ) . '<span>' . platejka_heading_text( $matched ) . '</span>' . platejka_heading_text( $after );
	} else {
		$visible = platejka_heading_text( $text );
		if ( '' !== $accent ) {
			$visible .= ' <span>' . platejka_heading_text( $accent ) . '</span>';
		}
	}
	if ( empty( $heading['decorative'] ) ) {
		return '<h2 class="' . $class_attr . '">' . $visible . '</h2>';
	}
	return '<h2 class="screen-reader-text">' . esc_html( $semantic ) . '</h2>'
		. '<div class="' . $class_attr . '" aria-hidden="true">' . $visible . '</div>';
}

/** @param int|mixed $form_id */
function platejka_cf7_form( $form_id ): string {
	$form_id = absint( $form_id );
	$post    = $form_id ? get_post( $form_id ) : null;
	if ( ! $post || 'wpcf7_contact_form' !== $post->post_type || 'publish' !== $post->post_status ) {
		if ( $form_id && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( 'Platejka section builder: Contact Form 7 form %d is missing, unpublished, or has the wrong post type.', $form_id ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
		return '';
	}
	return (string) do_shortcode( '[contact-form-7 id="' . $form_id . '"]' );
}

/** @param int|mixed $attachment_id */
function platejka_section_file_url( $attachment_id ): string {
	$attachment_id = absint( $attachment_id );
	if ( ! $attachment_id || 'attachment' !== get_post_type( $attachment_id ) ) {
		return '';
	}
	$url = wp_get_attachment_url( $attachment_id );
	return is_string( $url ) ? $url : '';
}
