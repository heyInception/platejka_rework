<?php
/** Rendering contracts for primary About-page ACF sections. */

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

$image_id = 0;
foreach ( get_posts( array( 'post_type' => 'attachment', 'post_mime_type' => 'image', 'post_status' => 'inherit', 'posts_per_page' => 50, 'fields' => 'ids' ) ) as $candidate ) {
	if ( wp_get_attachment_metadata( $candidate ) && wp_get_attachment_image_srcset( $candidate, 'large' ) ) {
		$image_id = (int) $candidate;
		break;
	}
}
$assert( $image_id > 0, 'Primary About fixtures have a responsive image attachment.' );

$render = static function ( string $relative, array $data, string $instance, string $anchor ): string {
	$section_data = $data;
	$section_instance = $instance;
	$section_anchor = $anchor;
	ob_start();
	include get_theme_file_path( 'sections/' . $relative );
	return (string) ob_get_clean();
};

$hero = $render( 'about-hero/about-hero.php', array(
	'eyebrow' => 'Fixture company', 'title' => 'Fixture hero title', 'lead' => '<strong>Fixture lead</strong>',
	'proofs' => array( array( 'image' => $image_id, 'icon' => $image_id, 'title' => 'Fixture proof', 'text' => 'Fixture proof text', 'link' => array( 'url' => '#proof', 'title' => 'Proof', 'target' => '' ) ) ),
	'metrics' => array( array( 'variant' => 'simple', 'value' => '777+', 'title' => '', 'text' => 'Fixture metric', 'links' => array() ) ),
), 'section-22-1', 'fixture-hero' );
$assert( str_contains( $hero, 'class="about-hero"' ) && str_contains( $hero, 'data-about-hero' ) && str_contains( $hero, 'id="fixture-hero"' ), 'About hero preserves its root hook and applies the public anchor.' );
$assert( str_contains( $hero, '<h1') && str_contains( $hero, 'Fixture hero title' ) && str_contains( $hero, 'Fixture lead' ), 'About hero renders fixture H1 and lead.' );
$assert( str_contains( $hero, 'Fixture proof' ) && str_contains( $hero, '777+' ) && ! str_contains( $hero, '318+' ), 'About hero repeaters replace static proof and metric content.' );
$assert( str_contains( $hero, 'srcset="' ) && str_contains( $hero, 'sizes="' ) && str_contains( $hero, 'loading="eager"' ), 'About hero renders responsive high-priority proof media.' );
$assert( str_contains( $hero, 'section-22-1-about-hero-title' ) && str_contains( $hero, 'section-22-1-about-hero-signature-clip' ), 'About hero derives accessible and SVG IDs from its instance.' );

$location = $render( 'location/location.php', array(
	'title' => 'Fixture office', 'description' => '<p>Fixture location body<script>alert(1)</script></p>', 'address' => 'Fixture address',
	'phone' => '+70000000000', 'phone_label' => 'Fixture phone', 'email' => 'office@example.com', 'email_label' => 'Fixture mail',
	'map_label' => 'Fixture map', 'gallery_label' => 'Fixture gallery', 'prev_label' => 'Fixture previous', 'next_label' => 'Fixture next',
	'gallery' => array( array( 'image' => $image_id, 'alt' => 'Fixture office photo' ) ),
), 'section-22-2', 'fixture-location' );
$assert( str_contains( $location, 'class="location"' ) && str_contains( $location, 'data-location-map' ) && str_contains( $location, 'data-horizontal-slider-track' ), 'Location preserves map and slider hooks.' );
$assert( str_contains( $location, 'Fixture office' ) && str_contains( $location, 'Fixture address' ) && ! str_contains( $location, '<script>' ), 'Location renders and sanitizes fixture content.' );
$assert( str_contains( $location, 'href="tel:+70000000000"' ) && str_contains( $location, 'href="mailto:office@example.com"' ), 'Location renders safe phone and email protocols.' );
$assert( 1 === substr_count( $location, 'Fixture office photo' ) && str_contains( $location, 'srcset="' ), 'Location renders exactly one responsive gallery fixture.' );
$assert( str_contains( $location, 'section-22-2-location-title' ) && str_contains( $location, 'section-22-2-location-map' ), 'Location emits instance-safe heading and map IDs.' );

$infrastructure = $render( 'infrastructure/infrastructure.php', array(
	'heading' => array( 'text' => 'Fixture infrastructure', 'accent' => '', 'decorative' => 0 ), 'description' => '<p>Fixture infrastructure body</p>',
	'specializations_label' => 'Fixture specializations', 'specializations' => array( array( 'image' => $image_id, 'title' => 'Fixture direction' ) ),
	'facts' => array( array( 'value' => '99', 'text' => 'Fixture fact' ) ), 'network_image' => $image_id, 'entities_label' => 'Fixture entities',
	'entities' => array( array( 'image' => $image_id, 'icon' => '', 'title' => 'Fixture country', 'caption' => 'Fixture entity', 'state' => 'active' ) ),
), 'section-22-4', 'fixture-infrastructure' );
$assert( str_contains( $infrastructure, 'class="infrastructure"' ) && str_contains( $infrastructure, 'id="fixture-infrastructure"' ), 'Infrastructure preserves its root and applies the public anchor.' );
$assert( str_contains( $infrastructure, 'Fixture infrastructure' ) && str_contains( $infrastructure, 'Fixture direction' ) && str_contains( $infrastructure, 'Fixture country' ), 'Infrastructure renders fixture groups and repeaters.' );
$assert( str_contains( $infrastructure, 'infrastructure__entity--active' ) && ! str_contains( $infrastructure, 'Таиланд' ), 'Infrastructure uses explicit state modifiers and removes static entities.' );
$assert( str_contains( $infrastructure, 'srcset="' ) && str_contains( $infrastructure, 'section-22-4-infrastructure-title' ), 'Infrastructure renders responsive media and an instance-safe heading ID.' );

WP_CLI::log( sprintf( 'Primary About templates: %d checks, %d failures.', $checks, count( $failures ) ) );
if ( $failures ) {
	WP_CLI::halt( 1 );
}
WP_CLI::success( 'Primary About template contract passed.' );
