<?php
/**
 * Configuration for isolated PHPUnit tests.
 * WordPress integration tests run separately through Studio WP-CLI.
 */

define( 'PLATEJKA_TEST_ROOT', dirname( __DIR__ ) );

// Isolated service tests only need WordPress's translation boundary.
if ( ! function_exists( '__' ) ) {
	function __( string $text, string $domain = 'default' ): string {
		return $text;
	}
}
