<?php
/** Focused integration checks for inherited section content and rendering helpers. */

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

$required = array(
	'platejka_resolve_scalar',
	'platejka_resolve_collection',
	'platejka_resolve_tristate',
	'platejka_merge_section_data',
	'platejka_section_content_slug',
	'platejka_resolve_section_data',
	'platejka_section_dom_id',
	'platejka_section_image',
	'platejka_section_heading',
	'platejka_cf7_form',
);
foreach ( $required as $function ) {
	$assert( function_exists( $function ), 'Section content exposes ' . $function . '().' );
}
$assert( class_exists( 'Platejka\\Core\\Acf\\SectionSeed' ), 'The versioned section seed reader is available.' );
if ( $failures ) {
	WP_CLI::log( sprintf( 'Section content: %d checks, %d failures.', $checks, count( $failures ) ) );
	WP_CLI::halt( 1 );
	return;
}

$assert( 'Глобально' === platejka_resolve_scalar( '', 'Глобально' ), 'Empty scalar inherits the global value.' );
$assert( 'Глобально' === platejka_resolve_scalar( false, 'Глобально' ), 'An empty ACF false value inherits the global value.' );
$assert( 'Локально' === platejka_resolve_scalar( 'Локально', 'Глобально' ), 'Non-empty scalar overrides the global value.' );
$assert( '0' === platejka_resolve_scalar( '0', 'Глобально' ), 'The string zero remains an intentional local value.' );
$assert( array( 'global' ) === platejka_resolve_collection( array(), array( 'global' ) ), 'Empty collection inherits globally.' );
$assert( array( 'local' ) === platejka_resolve_collection( array( 'local' ), array( 'global', 'second' ) ), 'Non-empty collection replaces globally without merging.' );
$assert( false === platejka_resolve_tristate( 'off', true ), 'Explicit off beats a true global value.' );
$assert( true === platejka_resolve_tristate( 'on', false ), 'Explicit on beats a false global value.' );
$assert( true === platejka_resolve_tristate( 'inherit', true ), 'Inherit returns the global boolean.' );

$merged = platejka_merge_section_data(
	array(
		'title' => '',
		'note'  => '0',
		'items' => array( array( 'title' => 'Локальная строка' ) ),
		'nested' => array( 'caption' => 'Локально' ),
	),
	array(
		'title' => 'Глобальный заголовок',
		'note'  => 'Глобально',
		'items' => array( array( 'title' => 'Глобальная строка' ) ),
		'nested' => array( 'caption' => 'Глобально', 'value' => '10' ),
	)
);
$assert( 'Глобальный заголовок' === ( $merged['title'] ?? null ), 'Nested merge inherits empty scalar fields.' );
$assert( '0' === ( $merged['note'] ?? null ), 'Nested merge preserves local zero strings.' );
$assert( array( array( 'title' => 'Локальная строка' ) ) === ( $merged['items'] ?? null ), 'Nested merge replaces list collections in full.' );
$assert( array( 'caption' => 'Локально', 'value' => '10' ) === ( $merged['nested'] ?? null ), 'Nested associative groups merge field by field.' );

$first = platejka_resolve_section_data(
	array( 'slug' => 'faq', 'mode' => 'default', 'row' => array( 'overrides' => array( 'title' => 'Первый' ) ), 'instance' => 'section-24-1' ),
	24
);
$second = platejka_resolve_section_data(
	array( 'slug' => 'faq', 'mode' => 'default', 'row' => array( 'overrides' => array( 'title' => 'Второй' ) ), 'instance' => 'section-24-2' ),
	24
);
$assert( 'Первый' === ( $first['title'] ?? null ) && 'Второй' === ( $second['title'] ?? null ), 'Repeated layouts resolve their own row overrides.' );

$assert( 'section-24-2-faq-question-1' === platejka_section_dom_id( 'section-24-2', 'faq-question-1' ), 'DOM IDs combine stable sanitized instance and local identifiers.' );

$assert( 'review-main' === platejka_section_content_slug( 'review' ), 'Internal review maps to canonical review-main content.' );
$assert( 'protection' === platejka_section_content_slug( 'protection' ), 'Other valid slugs keep their own canonical content name.' );
$global_review = get_field( 'review_main', 'platejka_section_defaults' );
$internal_review = platejka_resolve_section_data(
	array(
		'slug'         => 'review',
		'content_slug' => 'review-main',
		'row'          => array( 'overrides' => array( 'review_main' => array( 'title' => 'Локальный заголовок отзывов' ) ) ),
	)
);
$assert( 'Локальный заголовок отзывов' === ( $internal_review['title'] ?? null ), 'Internal review unwraps a prefixed review-main clone override.' );
$assert( count( $global_review['video_items'] ?? array() ) === count( $internal_review['video_items'] ?? array() ), 'Internal review inherits the canonical review-main collection.' );

$about_global_review = static fn( $global, $content_slug ) => 'review-main' === $content_slug ? array(
	'title'       => 'Сквозной заголовок',
	'video_items' => array( array( 'title' => 'Сквозное видео' ) ),
) : $global;
add_filter( 'platejka_section_global_data', $about_global_review, 10, 2 );
$about_inherited_review = platejka_resolve_section_data(
	array( 'slug' => 'review-main', 'content_slug' => 'review-main', 'row' => array( 'overrides' => array() ) ),
	22
);
$about_local_review = platejka_resolve_section_data(
	array(
		'slug'         => 'review-main',
		'content_slug' => 'review-main',
		'row'          => array( 'overrides' => array( 'review_main' => array( 'title' => '', 'video_items' => array( array( 'title' => 'Локальное видео' ) ) ) ) ),
	),
	22
);
remove_filter( 'platejka_section_global_data', $about_global_review, 10 );
$assert( 'Сквозной заголовок' === ( $about_inherited_review['title'] ?? null ), 'An empty About review override inherits shared scalar content.' );
$assert( array( array( 'title' => 'Сквозное видео' ) ) === ( $about_inherited_review['video_items'] ?? null ), 'An empty About review collection inherits the shared collection.' );
$assert( 'Сквозной заголовок' === ( $about_local_review['title'] ?? null ), 'An empty local review scalar still inherits its shared value.' );
$assert( array( array( 'title' => 'Локальное видео' ) ) === ( $about_local_review['video_items'] ?? null ), 'A non-empty local review collection replaces the shared collection.' );

$old_default_page_marker = get_option( 'platejka_default_page_builder_version', null );
delete_option( 'platejka_default_page_builder_version' );
$force_empty_hero = static fn() => false;
add_filter( 'acf/load_value/name=hero', $force_empty_hero );
$seeded_internal_hero = platejka_resolve_section_data( array( 'slug' => 'hero', 'row' => array() ) );
remove_filter( 'acf/load_value/name=hero', $force_empty_hero );
$assert( 'Платежи в Китай' === ( $seeded_internal_hero['title']['primary'] ?? null ), 'Internal-page seed keeps fallback templates populated before migration apply.' );
$old_about_marker = get_option( 'platejka_about_page_builder_version', null );
delete_option( 'platejka_about_page_builder_version' );
$seeded_about_hero = platejka_resolve_section_data( array( 'slug' => 'about-hero', 'row' => array() ), 22 );
$assert( 'Ваш надёжный платёжный агент' === ( $seeded_about_hero['title'] ?? null ), 'About-page seed keeps its fallback templates populated before migration apply.' );
if ( null === $old_about_marker ) {
	delete_option( 'platejka_about_page_builder_version' );
} else {
	update_option( 'platejka_about_page_builder_version', $old_about_marker, false );
}
if ( null === $old_default_page_marker ) {
	delete_option( 'platejka_default_page_builder_version' );
} else {
	update_option( 'platejka_default_page_builder_version', $old_default_page_marker, false );
}

$plain_heading = platejka_section_heading( array( 'text' => "Первая строка\nВторая строка", 'decorative' => false ), 'example__title' );
$assert( str_contains( $plain_heading, '<h2 class="example__title">' ) && str_contains( $plain_heading, 'Первая строка<br>Вторая строка' ), 'Ordinary headings stay visible semantic h2 elements with controlled line breaks.' );
$decorative_heading = platejka_section_heading( array( 'text' => 'Основной текст', 'accent' => 'Акцент', 'decorative' => true ), 'example__title' );
$assert( 1 === substr_count( $decorative_heading, '<h2 class="screen-reader-text">' ) && str_contains( $decorative_heading, '<div class="example__title" aria-hidden="true">' ), 'Decorative headings pair one screen-reader h2 with one aria-hidden div.' );
$assert( 2 === substr_count( wp_strip_all_tags( $decorative_heading ), 'Основной текст' ) && 2 === substr_count( wp_strip_all_tags( $decorative_heading ), 'Акцент' ), 'Semantic and decorative headings derive from the same text.' );
$embedded_accent_heading = platejka_section_heading( array( 'text' => 'Основной Акцент текст', 'accent' => 'Акцент', 'decorative' => true ), 'example__title' );
$assert( 2 === substr_count( wp_strip_all_tags( $embedded_accent_heading ), 'Акцент' ) && ! str_contains( wp_strip_all_tags( $embedded_accent_heading ), 'Акцент Акцент' ), 'An accent already present in the title is highlighted without duplicate visible or semantic text.' );

$image_id = 0;
foreach ( get_posts( array( 'post_type' => 'attachment', 'post_mime_type' => 'image', 'post_status' => 'inherit', 'posts_per_page' => 20, 'fields' => 'ids' ) ) as $candidate ) {
	if ( wp_get_attachment_metadata( $candidate ) && wp_get_attachment_image_srcset( $candidate, 'large' ) ) {
		$image_id = (int) $candidate;
		break;
	}
}
$assert( $image_id > 0, 'Image helper test has an existing attachment fixture.' );
$image_html = $image_id ? platejka_section_image( $image_id, 'large', array( 'class' => 'fixture-image', 'loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '(max-width: 767px) 100vw, 553px' ) ) : '';
$assert( str_contains( $image_html, 'width=') && str_contains( $image_html, 'height=') && str_contains( $image_html, 'srcset=') && str_contains( $image_html, 'sizes="(max-width: 767px) 100vw, 553px"' ), 'Valid attachments render intrinsic responsive image attributes.' );
$assert( str_contains( $image_html, 'loading="eager"' ) && str_contains( $image_html, 'fetchpriority="high"' ), 'Image helper preserves requested loading priority.' );
$assert( '' === platejka_section_image( 999999999, 'large' ), 'Invalid attachment IDs render nothing.' );

$form_html = platejka_cf7_form( 305 );
$assert( str_contains( $form_html, 'wpcf7' ), 'A valid selected CF7 form renders through its numeric ID.' );
$assert( str_contains( platejka_cf7_form( 4966 ), 'wpcf7' ), 'The calculator CF7 form renders through its numeric ID.' );
$assert( '' === platejka_cf7_form( 999999999 ), 'A missing CF7 form renders nothing.' );
$assert( '' === platejka_cf7_form( 24 ), 'A non-CF7 post ID renders nothing.' );
$assert( $image_id > 0 && '' !== platejka_section_file_url( $image_id ), 'File helper returns a URL only for an attachment.' );
$assert( '' === platejka_section_file_url( 999999999 ), 'File helper rejects missing attachments.' );

$seed = \Platejka\Core\Acf\SectionSeed::get();
$assert( 1 === ( $seed['schema_version'] ?? null ) && is_array( $seed['sections'] ?? null ), 'Seed reader returns the validated version-one envelope.' );
$assert( 18 === count( $seed['sections'] ?? array() ) && 18 === count( $seed['home_rows'] ?? array() ), 'Seed contains all 18 section defaults and all 18 home rows.' );
$assert( 'Международные платежи' === ( $seed['sections']['hero-main']['title'] ?? null ), 'Seed preserves the pre-migration home content.' );
$seed_image = platejka_section_image( 'theme://sections/hero/img/hero-bg.png', 'large', array( 'class' => 'seed-image' ) );
$assert( str_contains( $seed_image, 'class="seed-image"' ) && str_contains( $seed_image, 'width="' ) && str_contains( $seed_image, 'height="' ), 'Pre-migration theme media renders with intrinsic dimensions.' );
$old_marker = get_option( 'platejka_section_builder_version', null );
delete_option( 'platejka_section_builder_version' );
$seed_main = platejka_resolve_section_data( array( 'slug' => 'shipments', 'mode' => 'main', 'row' => array() ) );
$seed_default = platejka_resolve_section_data( array( 'slug' => 'shipments', 'mode' => 'default', 'row' => array() ) );
$assert( ( $seed_main['title'] ?? '' ) !== ( $seed_default['title'] ?? '' ), 'Main and default variants resolve separate seed groups.' );
$main_override = platejka_resolve_section_data( array( 'slug' => 'shipments', 'mode' => 'main', 'row' => array( 'overrides_main' => array( 'main' => array( 'title' => 'Локальный main' ) ) ) ) );
$default_override = platejka_resolve_section_data( array( 'slug' => 'shipments', 'mode' => 'default', 'row' => array( 'overrides_default' => array( 'default' => array( 'title' => 'Локальный default' ) ) ) ) );
$assert( 'Локальный main' === ( $main_override['title'] ?? null ) && 'Локальный default' === ( $default_override['title'] ?? null ), 'Variant-specific conditional override groups resolve only their effective mode.' );
update_option( 'platejka_section_builder_version', 1, false );
$force_empty_global = static fn() => false;
add_filter( 'acf/load_value/name=call', $force_empty_global );
$migrated_empty = platejka_resolve_section_data( array( 'slug' => 'call', 'mode' => 'default', 'row' => array() ) );
remove_filter( 'acf/load_value/name=call', $force_empty_global );
$assert( array() === $migrated_empty, 'Migration marker prevents an empty global value from falling back to seed content.' );
if ( null === $old_marker ) {
	delete_option( 'platejka_section_builder_version' );
} else {
	update_option( 'platejka_section_builder_version', $old_marker, false );
}

WP_CLI::log( sprintf( 'Section content: %d checks, %d failures.', $checks, count( $failures ) ) );
if ( $failures ) {
	WP_CLI::halt( 1 );
}
WP_CLI::success( 'Section content integration passed.' );
