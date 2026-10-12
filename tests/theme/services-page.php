<?php
/** Focused smoke test for the Services page template. */
defined( 'ABSPATH' ) || exit;

$checks   = 0;
$failures = array();
$assert   = static function ( bool $condition, string $message ) use ( &$checks, &$failures ): void {
	++$checks;
	if ( ! $condition ) {
		$failures[] = $message;
		WP_CLI::warning( $message );
	}
};

$page = get_post( 3632 );
$assert( $page instanceof WP_Post && 'services' === $page->post_name, 'Services must remain existing page 3632/services.' );
$assert( 'page-services.php' === get_page_template_slug( 3632 ), 'Services page uses the dedicated page-services.php template.' );

$assert( function_exists( 'platejka_compose_services_page_sections' ), 'Services page exposes a deterministic section-composition helper.' );
if ( function_exists( 'platejka_compose_services_page_sections' ) ) {
	$composed = platejka_compose_services_page_sections( array( 'call', 'faq' ) );
	$assert( array( 'services-section', 'call', 'faq' ) === $composed, 'Services stays first and preserves the builder section order.' );

	ob_start();
	platejka_render_sections( platejka_use_sections( platejka_compose_services_page_sections( array( 'call' ) ) ) );
	$ordered_output = (string) ob_get_clean();
	$services_offset = strpos( $ordered_output, 'class="services-section"' );
	$call_offset     = strpos( $ordered_output, 'data-call' );
	$assert( false !== $services_offset && false !== $call_offset && $services_offset < $call_offset, 'Rendered Services output precedes an enabled builder section.' );
}

$section = platejka_normalize_section( 'services-section' );
$assert( is_array( $section ), 'Services section is a valid registered section.' );
$assert( 'service-sections' === ( $section['directory'] ?? '' ), 'Services section resolves to the service-sections directory.' );
$assert( 'services-section' === ( $section['template'] ?? '' ), 'Services section resolves to the supplied services-section.php template.' );

wp_dequeue_style( 'platejka-rework-section-service-sections' );
platejka_use_sections( array( 'call' ) );
platejka_rework_assets();
$assert( ! wp_style_is( 'platejka-rework-section-service-sections', 'enqueued' ), 'A page without Services does not enqueue its stylesheet.' );

platejka_use_sections( array( 'services-section' ) );
platejka_rework_assets();
$assert( wp_style_is( 'platejka-rework-section-service-sections', 'enqueued' ), 'Declaring Services conditionally enqueues its stylesheet.' );

ob_start();
platejka_render_section( 'services-section' );
$rendered = (string) ob_get_clean();
$assert( str_contains( $rendered, 'class="services-section"' ), 'Services section renders its root element.' );
$assert( 5 === preg_match_all( '/<article class="services-section__item(?: services-section__item--wide)?">/', $rendered ), 'Services section renders all five menu cards.' );
$menu_index = 0;
foreach ( array(
	'mezhdunarodnye-platezhi-i-raschety',
	'valyutnye-perevody',
	'platezhi-v-kitaj',
	'platezhi-v-evropu',
	'autsorsing-i-upravlenie-ved',
) as $menu_slug ) {
	++$menu_index;
	$menu = wp_get_nav_menu_object( $menu_slug );
	$assert( $menu instanceof WP_Term, 'Existing Services menu is resolved by slug: ' . $menu_slug );
	if ( $menu instanceof WP_Term ) {
		$items = wp_get_nav_menu_items( $menu );
		$assert( is_array( $items ) && count( $items ) > 0, 'Existing Services menu has items: ' . $menu_slug );
		$assert( str_contains( $rendered, 'id="services-menu-' . $menu_index . '"' ), 'Rendered Services section contains menu: ' . $menu_slug );
	}
}

$response = wp_remote_get( add_query_arg( 'services-smoke', '1', home_url( '/services/' ) ), array( 'timeout' => 20 ) );
$assert( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ), 'Services page returns HTTP 200.' );
$html = is_wp_error( $response ) ? '' : (string) wp_remote_retrieve_body( $response );
$assert( str_contains( $html, 'class="services-section"' ), 'Services page renders the mandatory Services section.' );
$assert( ! str_contains( $html, 'data-hero' ), 'A disabled builder row is not rendered after Services.' );
$assert( str_contains( $html, '/sections/service-sections/service-sections.css' ), 'Services page includes the conditional section stylesheet.' );

WP_CLI::log( sprintf( 'Services page smoke: %d checks, %d failures.', $checks, count( $failures ) ) );
if ( $failures ) {
	WP_CLI::halt( 1 );
}
WP_CLI::success( 'Services page smoke passed.' );
