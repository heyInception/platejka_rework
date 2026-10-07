<?php
/** Rendering contracts for secondary About-page ACF sections. */

defined( 'ABSPATH' ) || exit;
$checks = 0; $failures = array();
$assert = static function ( bool $condition, string $message ) use ( &$checks, &$failures ): void { ++$checks; if ( ! $condition ) { $failures[] = $message; WP_CLI::warning( $message ); } };
$image_id = 0;
foreach ( get_posts( array( 'post_type' => 'attachment', 'post_mime_type' => 'image', 'post_status' => 'inherit', 'posts_per_page' => 50, 'fields' => 'ids' ) ) as $candidate ) {
	if ( wp_get_attachment_metadata( $candidate ) && wp_get_attachment_image_srcset( $candidate, 'large' ) ) { $image_id = (int) $candidate; break; }
}
$assert( $image_id > 0, 'Secondary About fixtures have a responsive image attachment.' );
$render = static function ( string $relative, array $data, string $instance, string $anchor ): string {
	$section_data = $data; $section_instance = $instance; $section_anchor = $anchor;
	ob_start(); include get_theme_file_path( 'sections/' . $relative ); return (string) ob_get_clean();
};
$heading = static fn( string $text, string $accent = '' ) => array( 'text' => $text, 'accent' => $accent, 'decorative' => 1 );

$financial = $render( 'financial/financial.php', array(
	'eyebrow' => 'Fixture compliance', 'heading' => $heading( 'Fixture financial accent', 'accent' ), 'description' => '<p>Fixture financial body<script>x</script></p>',
	'link' => array( 'url' => 'javascript:alert(1)', 'title' => 'Unsafe', 'target' => '' ),
	'benefits' => array( array( 'image' => $image_id, 'text' => 'Fixture benefit' ) ),
	'case' => array( 'label' => 'Fixture case', 'badge' => '24 hours', 'title' => 'Fixture bank case', 'text' => '<p>Fixture case body</p>', 'image' => $image_id, 'logo' => $image_id ),
	'banks_title' => 'Fixture banks', 'banks_description' => 'Fixture bank lead', 'banks' => array( array( 'image' => $image_id, 'title' => 'Fixture bank' ) ),
	'test' => array( 'title' => 'Fixture test', 'text' => '<p>Fixture test body</p>', 'link' => array( 'url' => '#test', 'title' => 'Fixture test link', 'target' => '' ), 'image' => $image_id ),
), 'section-22-5', 'fixture-financial' );
$assert( str_contains( $financial, 'class="financial"' ) && str_contains( $financial, 'id="fixture-financial"' ), 'Financial preserves its root and public anchor.' );
$assert( 1 === substr_count( $financial, '<h2' ) && str_contains( $financial, 'class="screen-reader-text"') && str_contains( $financial, 'aria-hidden="true"') && str_contains( $financial, 'Fixture financial' ), 'Financial decorative heading keeps one semantic H2.' );
$assert( str_contains( $financial, 'Fixture benefit' ) && str_contains( $financial, 'Fixture bank case' ) && str_contains( $financial, 'Fixture bank' ), 'Financial renders its fixture collections.' );
$assert( ! str_contains( $financial, 'javascript:') && str_contains( $financial, 'href="#test"') && str_contains( $financial, 'srcset="' ), 'Financial suppresses unsafe links and renders responsive media.' );

$employees = $render( 'employees/employees.php', array(
	'heading' => array( 'text' => 'Fixture employees', 'accent' => '', 'decorative' => 0 ), 'quote' => '<p>Fixture quote</p>',
	'author_name' => 'Fixture author', 'author_role' => 'Fixture role', 'author_image' => $image_id, 'team_image' => $image_id,
), 'section-22-6', 'fixture-employees' );
$assert( str_contains( $employees, 'data-employees') && str_contains( $employees, 'Fixture employees') && str_contains( $employees, 'Fixture quote'), 'Employees preserves hooks and renders fixture copy.' );
$assert( str_contains( $employees, 'Fixture author') && str_contains( $employees, 'srcset="') && str_contains( $employees, 'width="') && str_contains( $employees, 'section-22-6-employees-title'), 'Employees renders responsive author/team media and an instance-safe title.' );

$exhibitions = $render( 'exhibitions/exhibitions.php', array(
	'title' => 'Fixture exhibitions', 'subtitle' => 'Fixture exhibition subtitle', 'description' => '<p>Fixture exhibition body</p>',
	'trademark' => array( 'image' => $image_id, 'title' => 'Fixture trademark', 'caption' => 'Fixture patent' ),
	'gallery_label' => 'Fixture exhibition gallery', 'prev_label' => 'Fixture previous', 'next_label' => 'Fixture next',
	'gallery' => array( array( 'image' => $image_id, 'alt' => 'Fixture exhibition photo' ) ),
), 'section-22-7', 'fixture-exhibitions' );
$assert( str_contains( $exhibitions, 'data-horizontal-slider') && str_contains( $exhibitions, 'Fixture exhibitions') && str_contains( $exhibitions, 'Fixture trademark'), 'Exhibitions preserves slider hooks and renders fixture copy.' );
$assert( 1 === substr_count( $exhibitions, 'Fixture exhibition photo') && ! str_contains( $exhibitions, 'slider-img-2.png'), 'Exhibitions gallery is generated only from its repeater.' );

$developing_data = array(
	'heading' => array( 'text' => 'Fixture timeline', 'accent' => '', 'decorative' => 0 ), 'region_label' => 'Fixture history', 'prev_label' => 'Fixture previous', 'next_label' => 'Fixture next',
	'items' => array( array( 'year' => '2030', 'title' => 'Fixture milestone', 'text' => '<p>Fixture milestone body</p>', 'image' => $image_id ) ),
);
$developing = $render( 'developing/developing.php', $developing_data, 'section-22-8', 'fixture-developing' );
$developing_second = $render( 'developing/developing.php', $developing_data, 'section-22-10', 'fixture-developing-two' );
$assert( str_contains( $developing, 'data-horizontal-slider') && str_contains( $developing, 'Fixture milestone') && 2 === substr_count( $developing, '2030'), 'Developing derives one card and one year control from the same row.' );
$assert( str_contains( $developing, 'section-22-8-developing-title') && str_contains( $developing_second, 'section-22-10-developing-title'), 'Repeated developing sections emit distinct title IDs.' );

$call = $render( 'call/call-about.php', array(
	'heading' => array( 'text' => "Fixture call\nsecond line", 'accent' => '', 'decorative' => 0 ), 'form' => 305, 'trust_label' => 'Fixture trust',
	'trust_items' => array( array( 'variant' => 'registry', 'value' => '', 'title' => 'Fixture registry', 'text' => '', 'image' => $image_id ) ),
	'cards' => array( array( 'title' => 'Fixture contact card', 'text' => '<p>Fixture card body</p>', 'links' => array(
		array( 'icon' => 'telegram', 'link' => array( 'url' => 'https://t.me/example', 'title' => '', 'target' => '_blank' ), 'label' => 'Fixture Telegram' ),
		array( 'icon' => 'whatsapp', 'link' => array( 'url' => 'javascript:alert(1)', 'title' => '', 'target' => '' ), 'label' => 'Unsafe WhatsApp' ),
	) ) ),
), 'section-22-9', 'fixture-call' );
$assert( str_contains( $call, 'data-call') && str_contains( $call, 'Fixture call<br>second line') && str_contains( $call, 'wpcf7'), 'Call-about preserves its hook, heading breaks, and renders the selected CF7 form.' );
$assert( str_contains( $call, 'Fixture registry') && str_contains( $call, 'Fixture contact card') && str_contains( $call, 'https://t.me/example'), 'Call-about renders local trust and contact cards.' );
$assert( ! str_contains( $call, 'javascript:') && ! str_contains( $call, 'href="#"'), 'Call-about omits unsafe and placeholder links.' );

WP_CLI::log( sprintf( 'Secondary About templates: %d checks, %d failures.', $checks, count( $failures ) ) );
if ( $failures ) { WP_CLI::halt( 1 ); }
WP_CLI::success( 'Secondary About template contract passed.' );
