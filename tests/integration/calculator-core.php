<?php
/** Read-only calculator contract checks; run through Studio WP-CLI eval-file. */

defined( 'ABSPATH' ) || exit;

$failures = array();
$checks = 0;
$assert = static function ( bool $condition, string $message ) use ( &$failures, &$checks ): void {
	++$checks;
	if ( ! $condition ) {
		$failures[] = $message;
		WP_CLI::warning( $message );
	}
};
$finish = static function () use ( &$checks, &$failures ): void {
	WP_CLI::log( sprintf( 'Calculator core: %d checks, %d failures.', $checks, count( $failures ) ) );
	if ( $failures ) {
		WP_CLI::halt( 1 );
	} else {
		WP_CLI::success( 'Calculator core passed with zero failures.' );
	}
};

$state = array( get_option( 'stylesheet' ), get_option( 'template' ), get_option( 'active_plugins' ) );
$uploads = wp_upload_dir( null, false );
$public_cache = $uploads['basedir'] . '/platejka/exchange-rates.json';
$cache_hash = is_file( $public_cache ) ? hash_file( 'sha256', $public_cache ) : null;
$diagnostics = array();
set_error_handler( static function ( $severity, $message ) use ( &$diagnostics ): bool {
	$diagnostics[] = $message;
	return true;
}, E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE );
try {
	require_once WP_PLUGIN_DIR . '/platejka-core/platejka-core.php';
	foreach ( array( 'Rates\\ExchangeRates', 'Calculator\\Calculator', 'Rest\\CalculationController' ) as $class ) {
		$assert( class_exists( 'Platejka\\Core\\' . $class ), 'Plugin must expose service: ' . $class );
	}
	$assert( function_exists( 'platejka_core_get_exchange_rates' ), 'Public rate wrapper must exist.' );
	$assert( function_exists( 'platejka_core_calculate' ), 'Public calculation wrapper must exist.' );
	$server = rest_get_server();
	$routes = $server->get_routes();
	$route = $routes['/platejka/v1/calculation'] ?? null;
	$assert( is_array( $route ), 'Calculation route must register on rest_api_init.' );
	if ( $failures ) {
		$finish();
		return;
	}

	require_once WP_PLUGIN_DIR . '/platejka-core/tests/ExchangeRatesTest.php';
	require_once WP_PLUGIN_DIR . '/platejka-core/tests/CalculatorParityTest.php';
	platejka_test_exchange_rates( $assert );
	platejka_test_calculator( $assert );
	platejka_test_calculator_errors( $assert );
	$assert( isset( $route[0]['methods']['POST'] ) && ! isset( $route[0]['methods']['GET'] ), 'Calculation must accept POST and not GET.' );
	$assert( '__return_true' === $route[0]['permission_callback'] && true === call_user_func( $route[0]['permission_callback'] ), 'Read-only calculation must be explicitly public.' );
	$assert( $route[0]['callback'][0] instanceof \Platejka\Core\Rest\CalculationController, 'REST callback must use the calculator controller.' );
	$options = $server->dispatch( new WP_REST_Request( 'OPTIONS', '/platejka/v1/calculation' ) );
	$discovery = $options->get_data();
	$args_schema = $discovery['endpoints'][0]['args'] ?? array();
	$assert( 200 === $options->get_status() && 'string' === ( $args_schema['currency']['type'] ?? null ) && true === ( $args_schema['currency']['required'] ?? null ) && array( 'USD', 'EUR', 'CNY' ) === ( $args_schema['currency']['enum'] ?? null ), 'OPTIONS must expose required currency enum.' );
	$assert( 'number' === ( $args_schema['amount']['type'] ?? null ) && true === ( $args_schema['amount']['required'] ?? null ) && 0 === ( $args_schema['amount']['minimum'] ?? null ) && true === ( $args_schema['amount']['exclusiveMinimum'] ?? null ), 'OPTIONS must expose required positive amount schema.' );
	$fixture = json_decode( file_get_contents( WP_CONTENT_DIR . '/tests/fixtures/calculator-parity.json' ), true, 512, JSON_THROW_ON_ERROR );
	$test_cache = tempnam( sys_get_temp_dir(), 'platejka-rest-' );
	file_put_contents( $test_cache, wp_json_encode( array( 'schema_version' => 1, 'base' => 'RUB', 'rates' => $fixture['rates'] ) ) );
	$cache_filter = static function ( $path ) use ( $assert, $public_cache, $test_cache ) {
		$assert( $public_cache === $path, 'Default cache path must remain uploads/platejka/exchange-rates.json.' );
		return $test_cache;
	};
	add_filter( 'platejka_core_exchange_rates_cache_path', $cache_filter );
	try {
		$assert( array( 'USD' => 80.0, 'EUR' => 90.0, 'CNY' => 11.0 ) === platejka_core_get_exchange_rates(), 'Public rate wrapper must honor plugin-prefixed cache path.' );
		$assert( 55550.0 === ( platejka_core_calculate( 'cny', 5000.0 )['total'] ?? null ), 'Public calculation wrapper must use controlled rates.' );
		$request = new WP_REST_Request( 'POST', '/platejka/v1/calculation' );
		$request->set_param( 'currency', 'cny' );
		$request->set_param( 'amount', 5000 );
		$response = $server->dispatch( $request );
		$assert( 200 === $response->get_status() && array( 'currency' => 'CNY', 'amount' => 5000.0, 'rate' => 11.0, 'nativeCommission' => 50.0, 'commission' => 550.0, 'base' => 55000.0, 'total' => 55550.0 ) === $response->get_data(), 'Real public POST must normalize currency and preserve payload parity.' );
		foreach ( array( array(), array( 'currency' => 'JPY', 'amount' => 1 ), array( 'currency' => 'USD' ), array( 'currency' => 'USD', 'amount' => 0 ), array( 'currency' => 'USD', 'amount' => -1 ), array( 'currency' => array(), 'amount' => 1 ), array( 'currency' => 'USD', 'amount' => 'bad' ), array( 'currency' => 'USD', 'amount' => INF ), array( 'currency' => 'USD', 'amount' => NAN ) ) as $index => $params ) {
			$bad = new WP_REST_Request( 'POST', '/platejka/v1/calculation' );
			foreach ( $params as $key => $value ) {
				$bad->set_param( $key, $value );
			}
			$assert( 400 === $server->dispatch( $bad )->get_status(), 'Invalid REST arguments must produce 400: ' . $index );
		}
		$assert( 404 === $server->dispatch( new WP_REST_Request( 'GET', '/platejka/v1/calculation' ) )->get_status(), 'GET must not execute the calculation.' );
		$controller = new \Platejka\Core\Rest\CalculationController( new \Platejka\Core\Calculator\Calculator( array() ) );
		$error = $controller->create_item( $request );
		$assert( is_wp_error( $error ) && 'rate_unavailable' === $error->get_error_code() && 503 === $error->get_error_data()['status'], 'Unavailable rate must map to WP_Error status 503.' );
		// Use the real registered argument schema with a real unavailable-rate service.
		$registration_route = $route;
		// get_routes() returns a method map; register_route() consumes a method list.
		$registration_route[0]['methods'] = array_keys( $route[0]['methods'] );
		$unavailable_route = $registration_route;
		$unavailable_route[0]['callback'] = array( $controller, 'create_item' );
		$server->register_route( 'platejka/v1', '/platejka/v1/calculation', $unavailable_route, true );
		try {
			$unavailable = $server->dispatch( $request );
			$assert( 503 === $unavailable->get_status() && 'rate_unavailable' === $unavailable->get_data()['code'], 'Actual REST dispatch must preserve unavailable-rate HTTP status: ' . $unavailable->get_status() . '/' . wp_json_encode( $unavailable->get_data() ) );
		} finally {
			$server->register_route( 'platejka/v1', '/platejka/v1/calculation', $registration_route, true );
		}
		$bad = new WP_REST_Request();
		$bad->set_param( 'currency', 'USD' );
		$bad->set_param( 'amount', 0 );
		$error = $controller->create_item( $bad );
		$assert( is_wp_error( $error ) && 'invalid_amount' === $error->get_error_code() && 400 === $error->get_error_data()['status'], 'Calculation invalid_amount envelope must map to WP_Error status 400.' );
		$bad->set_param( 'currency', 'JPY' );
		$error = $controller->create_item( $bad );
		$assert( is_wp_error( $error ) && 'invalid_currency' === $error->get_error_code() && 400 === $error->get_error_data()['status'], 'Calculation invalid_currency envelope must map to WP_Error status 400.' );
		$test_seed = tempnam( sys_get_temp_dir(), 'platejka-seed-filter-' );
		file_put_contents( $test_seed, wp_json_encode( array( 'schema_version' => 1, 'base' => 'RUB', 'rates' => array( 'USD' => 79, 'EUR' => 89, 'CNY' => 10 ) ) ) );
		$seed_filter = static function ( $path ) use ( $assert, $test_seed ) {
			$assert( PLATEJKA_CORE_PATH . 'data/exchange-rates.json' === $path, 'Default seed path must belong to the plugin.' );
			return $test_seed;
		};
		add_filter( 'platejka_core_exchange_rates_seed_path', $seed_filter );
		try {
			file_put_contents( $test_cache, '{broken' );
			$before = hash_file( 'sha256', $test_cache );
			$assert( array( 'USD' => 79.0, 'EUR' => 89.0, 'CNY' => 10.0 ) === platejka_core_get_exchange_rates(), 'Public rate wrapper must honor plugin-prefixed seed filter.' );
			$assert( $before === hash_file( 'sha256', $test_cache ), 'Public fallback must preserve corrupt cache bytes.' );
		} finally {
			remove_filter( 'platejka_core_exchange_rates_seed_path', $seed_filter );
			unlink( $test_seed );
		}
	} finally {
		remove_filter( 'platejka_core_exchange_rates_cache_path', $cache_filter );
		unlink( $test_cache );
	}
} catch ( Throwable $error ) {
	$assert( false, 'Contract threw: ' . $error->getMessage() );
} finally {
	restore_error_handler();
}
$assert( array() === $diagnostics, 'Bootstrap and contract must not emit warnings/notices: ' . implode( '; ', $diagnostics ) );
$assert( $state === array( get_option( 'stylesheet' ), get_option( 'template' ), get_option( 'active_plugins' ) ), 'Tests must preserve active theme and plugins.' );
$assert( $cache_hash === ( is_file( $public_cache ) ? hash_file( 'sha256', $public_cache ) : null ), 'Tests must preserve real public cache bytes/existence.' );
$finish();
