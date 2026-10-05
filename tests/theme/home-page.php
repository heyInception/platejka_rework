<?php
/** Targeted FAST MODE smoke for the supplied home-page port. */
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
		$wpdb->get_results( "SELECT * FROM {$wpdb->posts} WHERE ID = 24", ARRAY_A ),
		$wpdb->get_results( "SELECT * FROM {$wpdb->postmeta} WHERE post_id = 24 ORDER BY meta_id", ARRAY_A ),
	) ) );
};

$before = $snapshot();
$page   = get_post( 24 );
$assert( $page instanceof WP_Post && 'glavnaya' === $page->post_name, 'Home must remain existing page 24/glavnaya.' );

$sections = array( 'hero-main', 'about', 'shipments', 'guarantees', 'documents', 'compliance', 'review-main', 'work', 'calculator', 'with-us', 'destinations', 'seo', 'problems', 'serves', 'cases', 'table', 'faq', 'call' );
$template = (string) file_get_contents( get_theme_file_path( 'templates/front-page.html' ) );
$offset   = -1;
foreach ( $sections as $section ) {
	$needle = '<!-- wp:platejka/' . $section;
	$found  = strpos( $template, $needle );
	$assert( false !== $found && $found > $offset, 'Locked home template order: ' . $section );
	$offset = false === $found ? $offset : $found;
	$assert( WP_Block_Type_Registry::get_instance()->is_registered( 'platejka/' . $section ), 'Registered home block: ' . $section );
}

$hero       = do_blocks( '<!-- wp:platejka/hero-main /-->' );
$about      = do_blocks( '<!-- wp:platejka/about /-->' );
$shipments  = do_blocks( '<!-- wp:platejka/shipments /-->' );
$guarantees = do_blocks( '<!-- wp:platejka/guarantees /-->' );
$documents  = do_blocks( '<!-- wp:platejka/documents /-->' );
$compliance = do_blocks( '<!-- wp:platejka/compliance /-->' );
$review     = do_blocks( '<!-- wp:platejka/review-main /-->' );
$problems   = do_blocks( '<!-- wp:platejka/problems /-->' );
$call       = do_blocks( '<!-- wp:platejka/call /-->' );
$assert( str_contains( $hero, 'data-export-source="sections/hero/hero-main.html"' ) && str_contains( $hero, 'hero__title' ), 'Hero must preserve the supplied export contract.' );
$assert( str_contains( $shipments, 'shipments--main' ) && ! str_contains( $shipments, 'shipments--default' ), 'Home must render the main shipments export variant.' );
$assert( str_contains( $guarantees, 'guarantees--main' ) && ! str_contains( $guarantees, 'guarantees--default' ), 'Home must render the main guarantees export variant.' );
$assert( str_contains( $documents, 'documents--main' ) && ! str_contains( $documents, 'documents--default' ), 'Home must render the main documents export variant.' );
$assert( str_contains( $about, 'data-export-source="sections/about/about.html"' ) && str_contains( $about, 'Официальный платёжный агент' ), 'Approved about design default must render.' );
$assert( str_contains( $review, 'data-export-source="sections/review-main/review-main.html"' ) && str_contains( $review, 'review-main__header' ), 'Owner-authorized review-main default must render.' );
$assert( str_contains( $call, 'data-export-source="sections/call/call.html"' ), 'Shared supplied call component must be reused.' );
$assert( ! preg_match( '/href=["\']#["\']|javascript:/i', $about . $review ), 'Ported sections must suppress demo and unsafe links.' );

foreach ( array( 'about', 'documents', 'compliance', 'review-main', 'problems' ) as $section ) {
	$assert( wp_style_is( 'platejka-rework-section-' . $section, 'enqueued' ), 'Visible section must enqueue only its registered CSS: ' . $section );
}

$after = $snapshot();
$assert( hash_equals( $before, $after ), 'Home rendering must not write page content or ACF values.' );
WP_CLI::log( sprintf( 'Task 9 FAST MODE: %d checks, %d failures.', $checks, count( $failures ) ) );
if ( $failures ) {
	WP_CLI::halt( 1 );
}
WP_CLI::success( 'Task 9 targeted home-page contract passed.' );
