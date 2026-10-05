<?php
/**
 * Run with Studio WP-CLI eval-file and --skip-plugins=platejka-core.
 * Activation is temporary when the plugin was originally inactive.
 */

defined( 'ABSPATH' ) || exit;

$plugin = 'platejka-core/platejka-core.php';
$file = WP_PLUGIN_DIR . '/' . $plugin;
$failures = array();
$checks = 0;
$assert = static function ( $condition, $message ) use ( &$failures, &$checks ) {
	++$checks;
	if ( ! $condition ) {
		$failures[] = $message;
		WP_CLI::warning( $message );
	}
};

$assert( is_file( $file ), 'Platejka Core plugin file is missing.' );

if ( is_file( $file ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	$was_active = is_plugin_active( $plugin );
	$diagnostics = array();
	set_error_handler(
		static function ( $severity, $message, $source, $line ) use ( &$diagnostics ) {
			$diagnostics[] = $message . ' at ' . $source . ':' . $line;
			return true;
		},
		E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE
	);
	try {
		if ( $was_active ) {
			require_once $file;
			$result = null;
		} else {
			$result = activate_plugin( $plugin );
		}
		$assert( ! is_wp_error( $result ) && is_plugin_active( $plugin ), 'Platejka Core activation failed.' );
		$assert( defined( 'PLATEJKA_CORE_VERSION' ), 'PLATEJKA_CORE_VERSION is undefined.' );
		$assert( defined( 'PLATEJKA_CORE_VERSION' ) && '0.1.0' === PLATEJKA_CORE_VERSION, 'Platejka Core version must equal 0.1.0.' );
		$assert( 0 === count( $diagnostics ), 'Bootstrap emitted warnings/notices: ' . implode( '; ', $diagnostics ) );
	} catch ( Throwable $error ) {
		$assert( false, 'Bootstrap threw: ' . $error->getMessage() );
	} finally {
		restore_error_handler();
		if ( ! $was_active && is_plugin_active( $plugin ) ) {
			deactivate_plugins( $plugin, true );
		}
	}
}

WP_CLI::log( sprintf( 'Platejka Core smoke: %d checks, %d failures.', $checks, count( $failures ) ) );
if ( $failures ) {
	WP_CLI::halt( 1 );
}
WP_CLI::success( 'Platejka Core bootstrap passed with zero failures.' );
