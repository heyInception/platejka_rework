<?php
/** Targeted FAST MODE smoke for the supplied China payments page port. */
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
		$wpdb->get_results( "SELECT * FROM {$wpdb->posts} WHERE ID = 1873", ARRAY_A ),
		$wpdb->get_results( "SELECT * FROM {$wpdb->postmeta} WHERE post_id = 1873 ORDER BY meta_id", ARRAY_A ),
	) ) );
};

$before = $snapshot();
$page   = get_post( 1873 );
$assert( $page instanceof WP_Post && 'china' === $page->post_name, 'China page must remain existing page 1873/china.' );

$sections = array( 'hero-main', 'about', 'shipments', 'guarantees', 'documents', 'protection', 'review', 'work', 'problems', 'calculator', 'seo', 'faq', 'call' );
$template = (string) file_get_contents( get_theme_file_path( 'templates/page-china.html' ) );
$offset   = -1;
foreach ( $sections as $section ) {
	$needle = '<!-- wp:platejka/' . $section;
	$found  = strpos( $template, $needle );
	$assert( false !== $found && $found > $offset, 'Locked China template order: ' . $section );
	$offset = false === $found ? $offset : $found;
	$assert( WP_Block_Type_Registry::get_instance()->is_registered( 'platejka/' . $section ), 'Registered China block: ' . $section );
}

$hero       = platejka_rework_render_page_section( 'hero-main', 1873 );
$about      = platejka_rework_render_page_section( 'about', 1873 );
$shipments  = platejka_rework_render_page_section( 'shipments', 1873 );
$guarantees = platejka_rework_render_page_section( 'guarantees', 1873 );
$documents  = platejka_rework_render_page_section( 'documents', 1873 );
$protection = platejka_rework_render_page_section( 'protection', 1873 );
$review     = platejka_rework_render_page_section( 'review', 1873 );
$seo        = platejka_rework_render_page_section( 'seo', 1873 );
$faq        = platejka_rework_render_page_section( 'faq', 1873 );
$assert( str_contains( $hero, 'data-export-source="sections/hero/hero.html"' ) && str_contains( $hero, 'hero__title' ), 'China hero must preserve its supplied export variant.' );
$assert( str_contains( $hero, 'data-calculation-endpoint=' ) && ! str_contains( $hero, 'data-currency-rates=' ), 'China hero calculator must use the real REST endpoint instead of demo rates.' );
$assert( ! str_contains( $hero, 'contact-dialog' ) && ! str_contains( $hero, 'Форма будет подключена' ), 'China hero must suppress its fake export submission dialog in favor of shared CF7.' );
$assert( str_contains( $about, 'data-export-source="sections/about/about.html"' ) && str_contains( $about, 'Официальный платёжный агент' ), 'Approved China about design default must render.' );
$assert( str_contains( $shipments, 'shipments--default' ) && ! str_contains( $shipments, 'shipments--main' ), 'China must render the default shipments export variant.' );
$assert( str_contains( $guarantees, 'guarantees--default' ) && ! str_contains( $guarantees, 'guarantees--main' ), 'China must render the default guarantees export variant.' );
$assert( str_contains( $documents, 'documents--default' ) && ! str_contains( $documents, 'documents--main' ), 'China must render the default documents export variant.' );
$assert( str_contains( $protection, 'data-export-source="sections/protection/protection.html"' ) && str_contains( $protection, 'protection__cards' ), 'Approved protection export must render.' );
$assert( str_contains( $review, 'data-export-source="sections/review/review.html"' ) && str_contains( $review, 'review__tabs' ), 'Approved review export must render.' );
$assert( str_contains( $seo, 'data-export-source="sections/seo/seo.html"' ) && str_contains( $seo, (string) $page->post_title ), 'SEO shell must use the existing page title.' );
$assert( '' === trim( (string) $page->post_content ) || str_contains( $seo, wp_strip_all_tags( wp_trim_words( (string) $page->post_content, 6, '' ) ) ), 'SEO shell must use existing page content, never the export demo copy.' );
$assert( str_contains( $faq, 'data-export-source="sections/faq/faq.html"' ) && str_contains( $faq, 'faq__item' ), 'China FAQ must use existing legacy ACF in the supplied export shell.' );
$assert( ! preg_match( '/href=["\']#["\']|javascript:/i', $hero . $about . $protection . $review . $seo . $faq ), 'Ported China sections must suppress demo and unsafe links.' );

$common = wp_scripts()->registered['platejka-rework-export-common'] ?? null;
$assert( $common instanceof _WP_Dependency, 'Shared export runtime must stay registered once.' );
$assert( 1 === count( array_filter( array_keys( wp_scripts()->registered ), static fn ( string $handle ): bool => 'platejka-rework-export-common' === $handle ) ), 'Shared export runtime handle must have a single registration.' );

$after = $snapshot();
$assert( hash_equals( $before, $after ), 'China rendering must not write page content or ACF values.' );
WP_CLI::log( sprintf( 'Task 10 FAST MODE: %d checks, %d failures.', $checks, count( $failures ) ) );
if ( $failures ) {
	WP_CLI::halt( 1 );
}
WP_CLI::success( 'Task 10 targeted China-page contract passed.' );
