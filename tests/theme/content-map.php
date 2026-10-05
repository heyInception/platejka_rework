<?php
/**
 * Targeted section-map contract and read-only state check.
 *
 * Run with Studio WP-CLI eval-file. The production change caught by this
 * test is a missing/wrong page-specific mapping, an unapproved HTML default,
 * or an adapter that mutates the three existing pages while reading them.
 */

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
	$state = array();
	foreach ( array( 22, 24, 1873 ) as $post_id ) {
		$state[ $post_id ] = array(
			'post' => get_post( $post_id ),
			'meta' => get_post_meta( $post_id ),
			'acf'  => function_exists( 'get_fields' ) ? get_fields( $post_id ) : null,
		);
	}

	return hash( 'sha256', serialize( $state ) );
};

$before = $snapshot();
WP_CLI::log( 'Pre-read content/ACF SHA-256: ' . $before );

$assert( function_exists( 'platejka_rework_get_section_data' ), 'Section data adapter must be loaded by the theme.' );
$assert( function_exists( 'platejka_rework_section_is_visible' ), 'Section visibility adapter must be loaded by the theme.' );

if ( function_exists( 'platejka_rework_get_section_data' ) && function_exists( 'platejka_rework_section_is_visible' ) ) {
	$home_hero = platejka_rework_get_section_data( 'hero-main', 24 );
	$assert( 'legacy_acf' === ( $home_hero['_source']['type'] ?? null ), 'Home hero must use its legacy ACF source.' );
	$assert( 'template-parts/pages/hero.php' === ( $home_hero['_source']['template'] ?? null ), 'Home hero must retain the named legacy template source.' );
	$assert( get_field( 'zaglovok_hero', 24 ) === ( $home_hero['zaglovok_hero'] ?? null ), 'Home hero must expose the existing title without renaming its meta key.' );
	$assert( get_field( 'priemushhestva_ach', 24 ) === ( $home_hero['priemushhestva_ach'] ?? null ), 'Home hero must expose the existing repeater unchanged.' );

	$home_about = platejka_rework_get_section_data( 'about', 24 );
	$assert( 'design_html' === ( $home_about['_source']['type'] ?? null ), 'Owner-approved missing home section must use an explicit design-HTML source.' );
	$assert( 'sections/about/about.html' === ( $home_about['_source']['default'] ?? null ), 'Home about default must point only to its matching supplied export.' );
	$assert( true === ( $home_about['_source']['owner_approved_default'] ?? null ), 'HTML copy must be explicitly owner-approved, never a global fallback.' );
	$assert( true === platejka_rework_section_is_visible( 'about', 24 ), 'An approved design default is visible before a later ACF override exists.' );

	$home_review = platejka_rework_get_section_data( 'review-main', 24 );
	$assert( 'local_acf' === ( $home_review['_source']['type'] ?? null ), 'Home review must expose the approved local ACF contract added with the page port.' );
	$assert(
		array(
			'home_review_main_enabled' => 'field_platejka_home_review_enabled_v1',
			'home_review_main_eyebrow' => 'field_platejka_home_review_eyebrow_v1',
			'home_review_main_title'   => 'field_platejka_home_review_title_v1',
			'home_review_main_lead'    => 'field_platejka_home_review_lead_v1',
		) === ( $home_review['_source']['acf_fields'] ?? null ),
		'Home review must retain the stable Task 9 local ACF keys.'
	);
	$assert( true === platejka_rework_section_is_visible( 'review-main', 24 ), 'Home review remains visible through its approved unsaved defaults until an editor disables it.' );

	$china_guarantees = platejka_rework_get_section_data( 'guarantees', 1873 );
	$assert( 'legacy_template' === ( $china_guarantees['_source']['type'] ?? null ), 'China guarantees must retain its owner-mapped legacy static source.' );
	$assert( true === platejka_rework_section_is_visible( 'guarantees', 1873 ), 'An existing mapped static legacy section remains visible.' );

	$about_hero = platejka_rework_get_section_data( 'about-hero', 22 );
	$assert( 'design_html' === ( $about_hero['_source']['type'] ?? null ), 'Company hero must follow the owner instruction to use supplied HTML.' );
	$assert( 'sections/about-hero/about-hero.html' === ( $about_hero['_source']['default'] ?? null ), 'Company hero must point to the matching export only.' );

	$assert( array() === platejka_rework_get_section_data( 'about', 22 ), 'A section absent from a page-specific owner map must not receive a fallback.' );
	$assert( array() === platejka_rework_get_section_data( 'not-approved', 24 ), 'An unknown section must remain disabled.' );
	$assert( false === platejka_rework_section_is_visible( 'not-approved', 24 ), 'Unknown sections must not render.' );
}

$after = $snapshot();
$assert( hash_equals( $before, $after ), 'Reading the map must not mutate posts, meta, or formatted ACF values.' );
WP_CLI::log( 'Post-read content/ACF SHA-256: ' . $after );
WP_CLI::log( sprintf( 'Content map: %d checks, %d failures.', $checks, count( $failures ) ) );

if ( $failures ) {
	WP_CLI::halt( 1 );
}

WP_CLI::success( 'Section mapping contract passed without content changes.' );
