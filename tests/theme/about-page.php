<?php
/** Targeted FAST MODE smoke for the supplied About page port. */
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
$snapshot = static function (): string {
	global $wpdb;
	return hash( 'sha256', serialize( array(
		$wpdb->get_results( "SELECT * FROM {$wpdb->posts} WHERE ID = 22", ARRAY_A ),
		$wpdb->get_results( "SELECT * FROM {$wpdb->postmeta} WHERE post_id = 22 ORDER BY meta_id", ARRAY_A ),
	) ) );
};

$before = $snapshot();
$page   = get_post( 22 );
$assert( $page instanceof WP_Post && 'o-kompanii' === $page->post_name, 'About page must remain existing page 22/o-kompanii.' );

$sections = array( 'about-hero', 'location', 'review-main', 'infrastructure', 'financial', 'employees', 'exhibitions', 'developing', 'call-about' );
$assert( function_exists( 'platejka_rework_about_sections' ) && $sections === platejka_rework_about_sections(), 'About section contract must contain the exact approved nine-item order.' );
$template = (string) file_get_contents( get_theme_file_path( 'templates/page-o-kompanii.html' ) );
$offset   = -1;
foreach ( $sections as $section ) {
	$block   = 'call-about' === $section ? 'call' : $section;
	$needle  = '<!-- wp:platejka/' . $block;
	$found   = strpos( $template, $needle, $offset + 1 );
	$assert( false !== $found && $found > $offset, 'Locked About template order: ' . $section );
	$offset = false === $found ? $offset : $found;
	$assert( WP_Block_Type_Registry::get_instance()->is_registered( 'platejka/' . $block ), 'Registered About block: ' . $block );
}
$assert( str_contains( $template, '"variant":"about"' ), 'The ninth position must use the distinct extended About call variant.' );

$hero           = platejka_rework_render_page_section( 'about-hero', 22 );
$location       = platejka_rework_render_page_section( 'location', 22 );
$review         = platejka_rework_render_page_section( 'review-main', 22 );
$infrastructure = platejka_rework_render_page_section( 'infrastructure', 22 );
$financial      = platejka_rework_render_page_section( 'financial', 22 );
$employees      = platejka_rework_render_page_section( 'employees', 22 );
$exhibitions    = platejka_rework_render_page_section( 'exhibitions', 22 );
$developing     = platejka_rework_render_page_section( 'developing', 22 );
$assert( str_contains( $hero, 'data-export-source="sections/about-hero/about-hero.html"' ) && str_contains( $hero, 'Ваш надёжный платёжный агент' ), 'About hero must render the approved supplied default.' );
$assert( str_contains( $location, 'data-export-source="sections/location/location.html"' ) && str_contains( $location, 'location__gallery' ), 'Location export must render.' );
$assert( str_contains( $review, 'data-export-source="sections/review-main/review-main.html"' ) && str_contains( $review, 'review-main__tabs' ), 'About reviews export must render.' );
$assert( str_contains( $infrastructure, 'data-export-source="sections/infrastructure/infrastructure.html"' ), 'Infrastructure export must render.' );
$assert( str_contains( $financial, 'data-export-source="sections/financial/financial.html"' ) && ! preg_match( '/href=["\']#["\']|javascript:/i', $financial ), 'Financial export must suppress demo links.' );
$assert( str_contains( $employees, 'data-export-source="sections/employees/employees.html"' ), 'Employees export must render.' );
$assert( str_contains( $exhibitions, 'data-export-source="sections/exhibitions/exhibitions.html"' ), 'Exhibitions export must render.' );
$assert( str_contains( $developing, 'data-export-source="sections/developing/developing.html"' ), 'Developing export must render.' );

add_filter( 'acf/load_value/name=about_about_hero_title', static fn (): string => 'Проверяемый заголовок ACF', 20 );
$overridden = platejka_rework_render_page_section( 'about-hero', 22 );
$assert( str_contains( $overridden, 'Проверяемый заголовок ACF' ) && ! str_contains( $overridden, '>Ваш надёжный платёжный агент<' ), 'A structured ACF value must override the supplied About default without a DB write.' );
remove_all_filters( 'acf/load_value/name=about_about_hero_title' );

$fields = function_exists( 'platejka_rework_about_acf_fields' ) ? platejka_rework_about_acf_fields() : array();
$assert( count( $fields ) >= 20, 'About page must expose structured editable text, media and link slots.' );
$assert( in_array( 'image', wp_list_pluck( $fields, 'type' ), true ) && in_array( 'url', wp_list_pluck( $fields, 'type' ), true ), 'About ACF schema must include media and link fields.' );

$call = render_block( array( 'blockName' => 'platejka/call', 'attrs' => array( 'variant' => 'about' ), 'innerBlocks' => array(), 'innerHTML' => '', 'innerContent' => array() ) );
$assert( str_contains( $call, 'data-export-source="sections/call/call-about.html"' ) && str_contains( $call, 'call-about__cards' ), 'About call must render the supplied extended layout.' );
$assert( str_contains( $call, 'Свяжитесь с нами' ) && str_contains( $call, 'Читайте нас в соцсетях' ), 'About call must include its supplied extension copy.' );
$assert( ! preg_match( '/href=["\']#["\']|javascript:/i', $call ), 'About call must not output fake or unsafe links.' );

$common = wp_scripts()->registered['platejka-rework-export-common'] ?? null;
$assert( $common instanceof _WP_Dependency, 'Shared export runtime must stay registered once.' );
$assert( 1 === count( array_filter( array_keys( wp_scripts()->registered ), static fn ( string $handle ): bool => 'platejka-rework-export-common' === $handle ) ), 'Shared export runtime handle must have a single registration.' );

$after = $snapshot();
$assert( hash_equals( $before, $after ), 'About rendering must not write page content or ACF values.' );
WP_CLI::log( sprintf( 'Task 11 FAST MODE: %d checks, %d failures.', $checks, count( $failures ) ) );
if ( $failures ) {
	WP_CLI::halt( 1 );
}
WP_CLI::success( 'Task 11 targeted About-page contract passed.' );
