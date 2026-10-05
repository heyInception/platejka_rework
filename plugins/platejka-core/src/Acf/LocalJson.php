<?php

namespace Platejka\Core\Acf;

defined( 'ABSPATH' ) || exit;

/**
 * Keep existing ACF schemas in the core plugin's canonical JSON directory.
 */
final class LocalJson {
	public static function register(): void {
		add_filter( 'acf/settings/load_json', array( self::class, 'loadPaths' ) );
		add_filter( 'acf/settings/save_json', array( self::class, 'savePath' ) );
	}

	public static function loadPaths( array $paths ): array {
		$canonical = PLATEJKA_CORE_PATH . 'acf-json';
		if ( ! in_array( $canonical, $paths, true ) ) {
			$paths[] = $canonical;
		}
		return $paths;
	}

	public static function savePath( string $path ): string {
		return PLATEJKA_CORE_PATH . 'acf-json';
	}
}
