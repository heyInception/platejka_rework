<?php
/** Live-data regression for the production legal-entities payment page. */

$theme_dir = dirname( __DIR__ );
require_once $theme_dir . '/inc/cross-blocks.php';
require_once $theme_dir . '/inc/cross-block-schema.php';

function platejka_cross_runtime_assert( bool $condition, string $message ): void {
	if ( ! $condition ) {
		WP_CLI::error( $message );
	}
}

$page = get_page_by_path( 'platezhi-za-graniczu-dlya-yuridicheskih-licz', OBJECT, 'page' );
platejka_cross_runtime_assert( $page instanceof WP_Post, 'Production regression page was not found.' );

$warnings = array();
set_error_handler(
	static function ( int $severity, string $message, string $file, int $line ) use ( &$warnings ): bool {
		if ( E_WARNING === $severity || E_NOTICE === $severity || E_USER_WARNING === $severity || E_USER_NOTICE === $severity || E_DEPRECATED === $severity ) {
			$warnings[] = $message . ' at ' . $file . ':' . $line;
			return true;
		}
		return false;
	}
);

$rows    = platejka_get_cross_block_rows( (int) $page->ID );
$layouts = array_values( array_filter( array_column( $rows, 'acf_fc_layout' ), 'is_string' ) );
foreach ( array( 'destinations', 'wtt', 'problems_solved' ) as $required ) {
	platejka_cross_runtime_assert( in_array( $required, $layouts, true ), 'Stored cross-block row is unavailable: ' . $required . '. Loaded: ' . implode( ', ', $layouts ) );
}

$fallback = array( 'hero', 'about', 'shipments', 'guarantees', 'documents', 'protection', 'review', 'work', 'problems', 'calculator', 'seo', 'faq', 'call' );
$sections = platejka_get_page_sections( (int) $page->ID, $fallback );
if ( '1' !== getenv( 'PLATEJKA_TEST_DISABLE_CROSS_COMPOSITION' ) ) {
	$sections = platejka_compose_cross_blocks( $sections, $rows, (int) $page->ID );
}
$slugs    = array_map( 'platejka_cross_block_section_slug', $sections );

platejka_cross_runtime_assert( 1 === count( array_keys( $slugs, 'destinations', true ) ), 'Destinations must be composed exactly once.' );
platejka_cross_runtime_assert( 1 === count( array_keys( $slugs, 'cross-exchange', true ) ), 'Static exchange must be composed exactly once.' );
platejka_cross_runtime_assert( 1 === count( array_keys( $slugs, 'problems', true ) ), 'Problems must be composed exactly once.' );
foreach ( array( 'transfer-calculator', 'transfer_calculator', 'tariff', 'reviews' ) as $excluded ) {
	platejka_cross_runtime_assert( ! in_array( $excluded, $slugs, true ), 'Excluded cross-block was composed: ' . $excluded );
}

$boundary_positions = array_filter( array( array_search( 'faq', $slugs, true ), array_search( 'call', $slugs, true ) ), static fn( $position ): bool => false !== $position );
if ( ! empty( $boundary_positions ) ) {
	$boundary = min( $boundary_positions );
	platejka_cross_runtime_assert( array_search( 'cross-exchange', $slugs, true ) < $boundary, 'Exchange must render before FAQ/call.' );
	platejka_cross_runtime_assert( array_search( 'problems', $slugs, true ) < $boundary, 'Problems must render before FAQ/call.' );
}

$render = static function ( array $config ) use ( $theme_dir, $page ): string {
	$slug             = platejka_cross_block_section_slug( $config );
	$legacy_layout    = (string) ( $config['legacy_layout'] ?? '' );
	$section_instance = (string) ( $config['instance'] ?? $slug );
	$section_anchor   = '';
	$mode             = 'default';
	$base             = platejka_resolve_section_data( $config, (int) $page->ID );
	$adapted          = platejka_adapt_cross_block_data( $legacy_layout, is_array( $config['cross_block_row'] ?? null ) ? $config['cross_block_row'] : array() );
	$section_data     = platejka_merge_cross_block_data( $adapted, $base );
	$template         = 'cross-exchange' === $slug ? $theme_dir . '/sections/cross-blocks/cross-blocks.php' : $theme_dir . '/sections/' . $slug . '/' . $slug . '.php';
	ob_start();
	include $template;
	return (string) ob_get_clean();
};

$target_html = array();
foreach ( $sections as $section ) {
	$config = is_array( $section ) ? $section : array( 'slug' => $section );
	if ( in_array( platejka_cross_block_section_slug( $config ), array( 'destinations', 'cross-exchange', 'problems' ), true ) ) {
		$target_html[] = $render( $config );
	}
}
$html = implode( "\n", $target_html );

restore_error_handler();

platejka_cross_runtime_assert( 1 === substr_count( $html, 'class="destinations"' ), 'Rendered destinations count changed.' );
platejka_cross_runtime_assert( 1 === substr_count( $html, 'cross-blocks--exchange' ), 'Rendered static exchange count changed.' );
platejka_cross_runtime_assert( 1 === substr_count( $html, 'class="problems"' ), 'Rendered problems count changed.' );
platejka_cross_runtime_assert( ! str_contains( $html, 'data-cb-exchange-calculator' ) && ! str_contains( $html, 'type="range"' ), 'Exchange unexpectedly exposes calculator behavior.' );
platejka_cross_runtime_assert( array() === $warnings, 'Runtime emitted PHP warnings/notices: ' . implode( ' | ', $warnings ) );

WP_CLI::success( 'Cross-block runtime checks passed for page ' . $page->ID . '.' );
