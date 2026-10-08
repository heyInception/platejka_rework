<?php
/**
 * Plugin Name: Platejka Core
 * Description: Core functionality for the Platejka site.
 * Version: 0.1.0
 * Requires PHP: 8.1
 * Text Domain: platejka-core
 * License: GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

define( 'PLATEJKA_CORE_VERSION', '0.1.0' );

if ( ! defined( 'PLATEJKA_CORE_PATH' ) ) {
	define( 'PLATEJKA_CORE_PATH', wp_normalize_path( plugin_dir_path( __FILE__ ) ) );
}

require_once PLATEJKA_CORE_PATH . 'src/Acf/SectionSeed.php';
require_once PLATEJKA_CORE_PATH . 'src/Acf/DefaultPageSeed.php';
require_once PLATEJKA_CORE_PATH . 'src/Acf/AboutPageSeed.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once PLATEJKA_CORE_PATH . 'src/Cli/SectionBuilderMigrationCommand.php';
	require_once PLATEJKA_CORE_PATH . 'src/Cli/DefaultPageMigrationCommand.php';
	require_once PLATEJKA_CORE_PATH . 'src/Cli/AboutPageMigrationCommand.php';
	require_once PLATEJKA_CORE_PATH . 'src/Cli/FaqMigrationCommand.php';
	\WP_CLI::add_command( 'platejka section-builder migrate', \Platejka\Core\Cli\SectionBuilderMigrationCommand::class );
	\WP_CLI::add_command( 'platejka section-builder migrate-default-page', \Platejka\Core\Cli\DefaultPageMigrationCommand::class );
	\WP_CLI::add_command( 'platejka section-builder migrate-about-page', \Platejka\Core\Cli\AboutPageMigrationCommand::class );
	\WP_CLI::add_command( 'platejka section-builder migrate-faq', \Platejka\Core\Cli\FaqMigrationCommand::class );
}

// Wait for all plugins so ACF may load before or after Platejka Core.
$platejka_register_acf = static function (): void {
	if ( function_exists( 'acf' ) && function_exists( 'add_filter' ) ) {
		require_once PLATEJKA_CORE_PATH . 'src/Acf/LocalJson.php';
		require_once PLATEJKA_CORE_PATH . 'src/Acf/SectionDefaults.php';
		\Platejka\Core\Acf\LocalJson::register();
		\Platejka\Core\Acf\SectionDefaults::register();
	}
};

if ( did_action( 'plugins_loaded' ) ) {
	// Activation and diagnostic eval-file loads may occur after this action.
	$platejka_register_acf();
} else {
	add_action( 'plugins_loaded', $platejka_register_acf );
}
unset( $platejka_register_acf );

require_once PLATEJKA_CORE_PATH . 'src/Rates/ExchangeRates.php';
require_once PLATEJKA_CORE_PATH . 'src/Calculator/Calculator.php';
require_once PLATEJKA_CORE_PATH . 'src/Integrations/ContactForm7.php';

\Platejka\Core\Integrations\ContactForm7::register();

/** @return array<string,float> Ordered RUB-per-unit rates. */
function platejka_core_get_exchange_rates(): array {
	$uploads = wp_upload_dir( null, false );
	$cache   = (string) apply_filters( 'platejka_core_exchange_rates_cache_path', trailingslashit( $uploads['basedir'] ) . 'platejka/exchange-rates.json' );
	$seed    = (string) apply_filters( 'platejka_core_exchange_rates_seed_path', PLATEJKA_CORE_PATH . 'data/exchange-rates.json' );
	return ( new \Platejka\Core\Rates\ExchangeRates( $cache, $seed ) )->getRates();
}

/**
 * @param string $currency Currency code.
 * @param float  $amount   Native-currency amount.
 * @return array Calculation payload or stable error envelope.
 */
function platejka_core_calculate( string $currency, float $amount ): array {
	return ( new \Platejka\Core\Calculator\Calculator( platejka_core_get_exchange_rates() ) )->calculate( $currency, $amount );
}

require_once PLATEJKA_CORE_PATH . 'src/Rest/CalculationController.php';
add_action( 'rest_api_init', array( new \Platejka\Core\Rest\CalculationController(), 'register_routes' ) );
