<?php
/** Focused integration checks for page-builder row normalization and validation. */

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

foreach ( array( 'platejka_resolve_page_section_rows', 'platejka_get_page_sections', 'platejka_validate_builder_rows' ) as $function ) {
	$assert( function_exists( $function ), 'Section builder exposes ' . $function . '().' );
}
if ( $failures ) {
	WP_CLI::log( sprintf( 'Section builder: %d checks, %d failures.', $checks, count( $failures ) ) );
	WP_CLI::halt( 1 );
	return;
}

$fallback = array( 'hero-main', 'about', array( 'slug' => 'shipments', 'mode' => 'main' ) );
$assert( $fallback === platejka_resolve_page_section_rows( false, array(), $fallback, 24 ), 'Disabled builder returns the PHP fallback unchanged.' );
$assert( array() === platejka_resolve_page_section_rows( true, array(), $fallback, 24 ), 'Enabled builder with no rows renders no sections.' );
$assert( $fallback === platejka_get_page_sections( 22, $fallback ), 'An unmigrated real page keeps its PHP fallback.' );

$rows = array(
	array( 'acf_fc_layout' => 'faq', 'enabled' => 1, 'anchor' => 'first-faq', 'overrides' => array( 'title' => 'Первый' ) ),
	array( 'acf_fc_layout' => 'about', 'enabled' => 0, 'anchor' => '' ),
	array( 'acf_fc_layout' => 'faq', 'enabled' => 1, 'anchor' => 'second-faq', 'overrides' => array( 'title' => 'Второй' ) ),
);
$resolved = platejka_resolve_page_section_rows( true, $rows, $fallback, 24 );
$assert( array( 'faq', 'faq' ) === array_column( $resolved, 'slug' ), 'Enabled rows preserve stored order and disabled rows are omitted.' );
$assert( 'section-24-1' === ( $resolved[0]['instance'] ?? null ) && 'section-24-3' === ( $resolved[1]['instance'] ?? null ), 'Repeated rows receive deterministic distinct instance IDs.' );
$assert( 'first-faq' === ( $resolved[0]['anchor'] ?? null ) && 'second-faq' === ( $resolved[1]['anchor'] ?? null ), 'Valid custom anchors are preserved.' );
$assert( ( $resolved[0]['row']['overrides']['title'] ?? null ) === 'Первый', 'The complete source row is retained for later content resolution.' );

$variant_rows = array(
	array( 'acf_fc_layout' => 'shipments', 'enabled' => 1, 'variant' => 'main', 'anchor' => '' ),
	array( 'acf_fc_layout' => 'documents', 'enabled' => 1, 'variant' => 'default', 'anchor' => '' ),
	array( 'acf_fc_layout' => 'guarantees', 'enabled' => 1, 'variant' => 'unexpected', 'anchor' => '' ),
);
$variants = platejka_resolve_page_section_rows( true, $variant_rows, array(), 24 );
$assert( array( 'main', 'default', 'default' ) === array_column( $variants, 'mode' ), 'Variant modes normalize to main/default and invalid values fail closed to default.' );

$internal_rows = platejka_resolve_page_section_rows(
	true,
	array(
		array( 'acf_fc_layout' => 'hero', 'enabled' => 1 ),
		array( 'acf_fc_layout' => 'protection', 'enabled' => 1 ),
		array( 'acf_fc_layout' => 'review', 'enabled' => 1 ),
		array( 'acf_fc_layout' => 'unknown-section', 'enabled' => 1 ),
	),
	array(),
	1873
);
$assert( array( 'hero', 'protection', 'review' ) === array_column( $internal_rows, 'slug' ), 'Internal-page layouts resolve while an unknown slug fails closed.' );
$assert( array( 'hero', 'protection', 'review-main' ) === array_column( $internal_rows, 'content_slug' ), 'Normalized rows separate template slugs from canonical content slugs.' );

$assert( true === platejka_validate_builder_rows( $rows ), 'Duplicate non-hero rows are valid.' );
$hero_error = platejka_validate_builder_rows(
	array(
		array( 'acf_fc_layout' => 'hero-main', 'enabled' => 1, 'anchor' => '' ),
		array( 'acf_fc_layout' => 'hero', 'enabled' => 1, 'anchor' => '' ),
	)
);
$assert( is_wp_error( $hero_error ) && 'multiple_hero_sections' === $hero_error->get_error_code(), 'Any two hero-family rows fail validation.' );
$filtered_hero_error = apply_filters(
	'acf/validate_value/key=field_platejka_sections_v1',
	true,
	array(
		array( 'acf_fc_layout' => 'hero-main', 'enabled' => 1, 'anchor' => '' ),
		array( 'acf_fc_layout' => 'hero', 'enabled' => 1, 'anchor' => '' ),
	),
	array(),
	'platejka_sections'
);
$assert( is_string( $filtered_hero_error ) && str_contains( $filtered_hero_error, 'hero' ), 'The ACF validation hook exposes the hero error to editors.' );

$invalid_anchor = platejka_validate_builder_rows( array( array( 'acf_fc_layout' => 'faq', 'enabled' => 1, 'anchor' => 'Bad Anchor!' ) ) );
$assert( is_wp_error( $invalid_anchor ) && 'invalid_section_anchor' === $invalid_anchor->get_error_code(), 'Invalid anchors fail validation instead of being silently changed.' );
$duplicate_anchor = platejka_validate_builder_rows(
	array(
		array( 'acf_fc_layout' => 'faq', 'enabled' => 1, 'anchor' => 'answers' ),
		array( 'acf_fc_layout' => 'call', 'enabled' => 1, 'anchor' => 'answers' ),
	)
);
$assert( is_wp_error( $duplicate_anchor ) && 'duplicate_section_anchor' === $duplicate_anchor->get_error_code(), 'Duplicate anchors fail validation.' );
$raw_invalid_anchor = platejka_validate_builder_rows(
	array( array( 'acf_fc_layout' => 'faq', 'field_platejka_row_faq_anchor_v1' => 'Bad Anchor!' ) )
);
$assert( is_wp_error( $raw_invalid_anchor ) && 'invalid_section_anchor' === $raw_invalid_anchor->get_error_code(), 'Raw ACF field-key anchors are validated.' );
$raw_duplicate_anchor = platejka_validate_builder_rows(
	array(
		array( 'acf_fc_layout' => 'faq', 'field_platejka_row_faq_anchor_v1' => 'answers' ),
		array( 'acf_fc_layout' => 'call', 'field_platejka_row_call_anchor_v1' => 'answers' ),
	)
);
$assert( is_wp_error( $raw_duplicate_anchor ) && 'duplicate_section_anchor' === $raw_duplicate_anchor->get_error_code(), 'Duplicate raw ACF field-key anchors are rejected.' );

$normalized = platejka_use_sections( $resolved );
$assert( 2 === count( $normalized ) && 'section-24-3' === ( $normalized[1]['instance'] ?? null ), 'The existing normalizer preserves builder instance context.' );
$normalized_review = platejka_normalize_section( array( 'slug' => 'review', 'content_slug' => 'review-main' ) );
$assert( 'review-main' === ( $normalized_review['content_slug'] ?? null ), 'The section normalizer preserves the canonical content slug.' );

foreach ( array( 'faq', 'about' ) as $slug ) {
	wp_dequeue_style( 'platejka-rework-section-' . $slug );
	wp_dequeue_script( 'platejka-rework-section-' . $slug );
}
platejka_rework_assets();
$assert( wp_style_is( 'platejka-rework-section-faq', 'enqueued' ), 'Repeated enabled rows enqueue their section stylesheet.' );
$assert( wp_script_is( 'platejka-rework-section-faq', 'enqueued' ), 'Repeated enabled rows enqueue their section script.' );
$assert( ! wp_style_is( 'platejka-rework-section-about', 'enqueued' ), 'Disabled rows do not enqueue section assets.' );

foreach ( array( 'page-home.php', 'page-about.php', 'page.php' ) as $template ) {
	$source = file_get_contents( get_theme_file_path( $template ) );
	$assert( str_contains( $source, 'platejka_get_page_sections' ), $template . ' resolves the builder before declaring active sections.' );
}

WP_CLI::log( sprintf( 'Section builder: %d checks, %d failures.', $checks, count( $failures ) ) );
if ( $failures ) {
	WP_CLI::halt( 1 );
}
WP_CLI::success( 'Section builder integration passed.' );
