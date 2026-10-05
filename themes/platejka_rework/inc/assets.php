<?php
/** Conditional section assets and rendering. */

function platejka_normalize_section( $section ): ?array {
	$config = is_array( $section ) ? $section : array( 'slug' => $section );
	$slug   = isset( $config['slug'] ) && is_string( $config['slug'] ) ? $config['slug'] : '';

	if ( ! preg_match( '/^[a-z0-9-]+$/', $slug ) ) {
		return null;
	}

	$aliases = array(
		'hero-main' => array( 'directory' => 'hero', 'template' => 'hero-main' ),
		'call-about' => array( 'directory' => 'call', 'template' => 'call-about' ),
	);
	$resolved = $aliases[ $slug ] ?? array( 'directory' => $slug, 'template' => $slug );

	return array(
		'slug'      => $slug,
		'directory' => $resolved['directory'],
		'template'  => $resolved['template'],
		'mode'      => isset( $config['mode'] ) && 'main' === $config['mode'] ? 'main' : 'default',
	);
}

function platejka_use_sections( array $sections ): array {
	$normalized = array();
	foreach ( $sections as $section ) {
		$config = platejka_normalize_section( $section );
		if ( null !== $config ) {
			$normalized[] = $config;
		}
	}
	$GLOBALS['platejka_rework_sections'] = $normalized;
	return $normalized;
}

function platejka_section_asset_version( string $path ): string {
	$modified = filemtime( $path );
	return false === $modified ? _S_VERSION : (string) $modified;
}

function platejka_rework_assets(): void {
	$sections = $GLOBALS['platejka_rework_sections'] ?? array();
	$sections = array_merge( array( 'header', 'preloader', 'footer' ), $sections );
	$loaded   = array();

	foreach ( $sections as $section ) {
		$config = platejka_normalize_section( $section );
		if ( null === $config || isset( $loaded[ $config['directory'] ] ) ) {
			continue;
		}

		$loaded[ $config['directory'] ] = true;
		$directory                     = $config['directory'];
		$base_path                     = get_theme_file_path( 'sections/' . $directory );
		$base_url                      = get_theme_file_uri( 'sections/' . $directory );
		$style_path                    = $base_path . '/' . $directory . '.css';
		$script_path                   = $base_path . '/' . $directory . '.js';
		$handle                        = 'platejka-rework-section-' . $directory;

		if ( is_file( $style_path ) ) {
			wp_enqueue_style( $handle, $base_url . '/' . $directory . '.css', array( 'platejka_rework-commoncss' ), platejka_section_asset_version( $style_path ) );
		}
		if ( is_file( $script_path ) ) {
			wp_enqueue_script( $handle, $base_url . '/' . $directory . '.js', array( 'platejka_rework-common' ), platejka_section_asset_version( $script_path ), true );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'platejka_rework_assets' );

function platejka_filter_section_mode( string $output, string $mode ): string {
	$pattern = "/^@if \(mode === 'main'\) \{\R(.*?)^\}\R^@if \(mode !== 'main'\) \{\R(.*?)^\}\R?/ms";
	return (string) preg_replace_callback(
		$pattern,
		static function ( array $matches ) use ( $mode ): string {
			return 'main' === $mode ? $matches[1] : $matches[2];
		},
		$output
	);
}

function platejka_rewrite_section_urls( string $output, string $directory ): string {
	$base_url = trailingslashit( get_theme_file_uri( 'sections/' . $directory ) );
	$output   = (string) preg_replace_callback(
		'~\b(src|href|poster)=([\'\"])(img/[^\'\"]+)\2~i',
		static function ( array $matches ) use ( $base_url ): string {
			return $matches[1] . '=' . $matches[2] . $base_url . $matches[3] . $matches[2];
		},
		$output
	);
	return (string) preg_replace_callback(
		'~url\((([\'\"])?)(img/[^)\'\"]+)\2\)~i',
		static function ( array $matches ) use ( $base_url ): string {
			return 'url(' . $matches[1] . $base_url . $matches[3] . $matches[1] . ')';
		},
		$output
	);
}

function platejka_render_section( $section ): void {
	$config = platejka_normalize_section( $section );
	if ( null === $config ) {
		return;
	}

	$sections_root = realpath( get_theme_file_path( 'sections' ) );
	$template      = realpath( get_theme_file_path( 'sections/' . $config['directory'] . '/' . $config['template'] . '.php' ) );
	if ( false === $sections_root || false === $template || ! str_starts_with( $template, $sections_root . DIRECTORY_SEPARATOR ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Platejka section template not found: ' . $config['slug'] ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
		return;
	}

	$mode = $config['mode'];
	ob_start();
	include $template;
	$output = (string) ob_get_clean();
	$output = platejka_filter_section_mode( $output, $mode );
	$output = platejka_rewrite_section_urls( $output, $config['directory'] );
	echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted theme templates.
}

function platejka_render_sections( ?array $sections = null ): void {
	$sections = null === $sections ? ( $GLOBALS['platejka_rework_sections'] ?? array() ) : $sections;
	foreach ( $sections as $section ) {
		platejka_render_section( $section );
	}
}
