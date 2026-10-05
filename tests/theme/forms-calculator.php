<?php
/** Targeted FAST MODE contract for the faithfully ported Task 7 components. */
defined( 'ABSPATH' ) || exit;

$checks = 0;
$failures = array();
$assert = static function ( bool $condition, string $message ) use ( &$checks, &$failures ): void {
	++$checks;
	if ( ! $condition ) { $failures[] = $message; WP_CLI::warning( $message ); }
};
$snapshot = static function (): string {
	global $wpdb;
	return hash( 'sha256', serialize( array(
		$wpdb->get_results( "SELECT * FROM {$wpdb->posts} WHERE ID IN (22,24,1873,305,904,3321,3542,4966) ORDER BY ID", ARRAY_A ),
		$wpdb->get_results( "SELECT * FROM {$wpdb->postmeta} WHERE post_id IN (22,24,1873,305,904,3321,3542,4966) ORDER BY meta_id", ARRAY_A ),
		$wpdb->get_results( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'wpcf7%' OR option_name LIKE 'options_%' OR option_name LIKE '_options_%' ORDER BY option_name", ARRAY_A ),
	) ) );
};
$before = $snapshot();
WP_CLI::log( 'Task 7 remediation database SHA-256: ' . $before );

$calculator = do_blocks( '<!-- wp:platejka/calculator /-->' );
foreach ( array( 'calculator__heading', 'calculator__badge', 'calculator__columns', 'calculator__fields', 'calculator__indicator', 'calculator__route', 'calculator__amount-wrap', 'calculator__summary', 'calculator__results', 'calculator__actions', 'calculator__contract-note' ) as $class ) {
	$assert( str_contains( $calculator, $class ), 'Calculator must preserve export class: ' . $class );
}
$assert( str_contains( $calculator, 'data-export-source="sections/calculator/calculator.html"' ) && str_contains( $calculator, '/wp-json/platejka/v1/calculation' ), 'Calculator must declare export source and real REST boundary.' );
$assert( 3 === substr_count( $calculator, 'data-currency=' ) && ! preg_match( '/data-(?:currency-)?rates|href="#"/i', $calculator ), 'Calculator must expose export controls without embedded rates/placeholders.' );

$default = do_blocks( '<!-- wp:platejka/call {"variant":"default"} /-->' );
$about = do_blocks( '<!-- wp:platejka/call {"variant":"about"} /-->' );
foreach ( array( 'call__panel', 'call__row', 'call__layout', 'call__column', 'call__title', 'call__trust', 'call__form' ) as $class ) {
	$assert( str_contains( $default, $class ), 'Call must preserve export class: ' . $class );
}
$assert( str_contains( $default, 'data-export-source="sections/call/call.html"' ) && str_contains( $default, 'data-call-dialog' ), 'Default call must preserve export provenance and shared dialog.' );
$assert( str_contains( $about, 'data-export-source="sections/call/call-about.html"' ) && str_contains( $about, 'call-about__cards' ) && 2 === substr_count( $about, 'call-about__card"' ), 'About variant must preserve its distinct two-card extension.' );
$assert( ! preg_match( '/href="#"|javascript:/i', $default . $about ), 'Rendered forms and slots must not introduce demo/unsafe links.' );

preg_match( '/data-call-form="transfer"[^>]*>(.*?)<\/div>\s*<p class="call-dialog__status"/s', $default, $transfer );
$assert( ! empty( $transfer[1] ) && 1 === substr_count( $transfer[1], 'name="platejka_calculation_summary"' ) && str_contains( $transfer[1], 'value="4966"' ), 'Actual transfer CF7 render must contain the injected summary and stable form ID.' );
$assert( 1 === substr_count( $default, 'name="platejka_calculation_summary"' ), 'Summary field must stay absent from unrelated rendered CF7 forms.' );

$class = 'Platejka\\Core\\Integrations\\ContactForm7';
$posted = array( 'your-name' => 'Ivan', 'platejka_calculation_summary' => '<b>Total</b>   100' );
$assert( class_exists( $class ) && array( 'your-name' => 'Ivan', 'platejka_calculation_summary' => 'Total 100' ) === $class::sanitizePostedDataForForm( $posted, 4966 ), 'Transfer summary must be sanitized.' );
$assert( class_exists( $class ) && $posted === $class::sanitizePostedDataForForm( $posted, 305 ), 'Unrelated submission data must remain unchanged.' );

$rates = array( 'USD' => 80.0, 'EUR' => 90.0, 'CNY' => 11.0 );
$service = new \Platejka\Core\Calculator\Calculator( $rates );
foreach ( array( 'CNY' => 55550.0, 'USD' => 404000.0, 'EUR' => 454500.0 ) as $currency => $total ) {
	$assert( $total === ( $service->calculate( $currency, 5000.0 )['total'] ?? null ), 'Representative Task 4 parity: ' . $currency );
}

$root = get_stylesheet_directory();
foreach ( array( 'assets/src/css/components/img/cn-flag.png', 'assets/src/css/components/img/eur-flag.png', 'assets/src/css/components/img/ru-flag.png', 'assets/src/css/components/img/usa-flag.png', 'assets/src/css/components/img/Shield-Check.svg', 'assets/src/css/components/img/call-bg.jpg' ) as $asset ) {
	$assert( is_file( $root . '/' . $asset ), 'Used supplied asset must be copied: ' . $asset );
}
$assert( wp_style_is( 'platejka-rework-section-calculator', 'enqueued' ) && wp_script_is( 'platejka-rework-section-calculator', 'enqueued' ) && wp_style_is( 'platejka-rework-section-call', 'enqueued' ), 'Real visible blocks must enqueue conditional assets.' );

$after = $snapshot();
$assert( hash_equals( $before, $after ), 'Targeted rendering must not mutate content, CF7, ACF, or options.' );
WP_CLI::log( 'Task 7 remediation final database SHA-256: ' . $after );
WP_CLI::log( sprintf( 'Task 7 FAST MODE: %d checks, %d failures.', $checks, count( $failures ) ) );
if ( $failures ) { WP_CLI::halt( 1 ); }
WP_CLI::success( 'Task 7 targeted faithful-port contract passed.' );
