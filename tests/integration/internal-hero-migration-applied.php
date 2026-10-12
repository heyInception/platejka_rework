<?php
/** Applied-state audit for internal Hero migration. */

defined( 'ABSPATH' ) || exit;

$failures = array();
$checks   = 0;
$assert   = static function ( bool $condition, string $message ) use ( &$failures, &$checks ): void {
	++$checks;
	if ( ! $condition ) {
		$failures[] = $message;
		WP_CLI::warning( $message );
	}
};

$assert( 1 === (int) get_option( \Platejka\Core\Acf\LegacyFieldVisibility::HERO_VERSION_OPTION, 0 ), 'Internal Hero completion marker is missing.' );
$assert( 'page-services.php' === (string) get_page_template_slug( 3632 ), 'Services template changed unexpectedly.' );
foreach ( array( 1860, 2075, 2098, 2109 ) as $page_id ) {
	$assert( in_array( (string) get_page_template_slug( $page_id ), array( '', 'default' ), true ), 'Approved page is not using the default template: ' . $page_id . '.' );
}

$global = get_field( 'field_platejka_defaults_hero_v1', 'platejka_section_defaults' );
$global = is_array( $global ) ? $global : array();
$shared_keys = array( 'badge', 'items', 'info', 'experience', 'registry', 'association' );
$page_ids = get_posts(
	array(
		'post_type'      => 'page',
		'post_status'    => array( 'publish', 'future', 'draft', 'pending', 'private' ),
		'fields'         => 'ids',
		'posts_per_page' => -1,
		'orderby'        => 'ID',
		'order'          => 'ASC',
	)
);

$targets = 0;
$with_subtitle = 0;
$without_subtitle = 0;
$background_ids = array();
$blur_ids = array();
foreach ( $page_ids as $page_id_value ) {
	$page_id = (int) $page_id_value;
	if ( in_array( $page_id, array( 22, 24, 1873, 3632 ), true ) ) {
		continue;
	}
	$title = trim( (string) get_field( 'field_66051d3d9621e', $page_id, false ) );
	$subtitle = trim( (string) get_field( 'field_66c7356f59226', $page_id, false ) );
	if ( '' === $title && '' === $subtitle ) {
		continue;
	}
	++$targets;
	'' === $subtitle ? ++$without_subtitle : ++$with_subtitle;

	$assert( (bool) get_field( 'field_platejka_page_builder_v1', $page_id, false ), 'Builder is disabled on page ' . $page_id . '.' );
	$rows = get_field( 'field_platejka_sections_v1', $page_id );
	$rows = is_array( $rows ) ? $rows : array();
	$hero = is_array( $rows[0] ?? null ) ? $rows[0] : array();
	$local = is_array( $hero['overrides']['hero'] ?? null ) ? $hero['overrides']['hero'] : array();
	$assert( 'hero' === ( $hero['acf_fc_layout'] ?? null ), 'Hero is not first on page ' . $page_id . '.' );
	$assert( ! empty( $hero['enabled'] ) && ! empty( $hero['shared_content_only'] ), 'Hero flags are incorrect on page ' . $page_id . '.' );
	$assert( '#25252a' === ( $local['background_color'] ?? null ), 'Hero background color is incorrect on page ' . $page_id . '.' );
	$assert( $title === (string) ( $local['title']['primary'] ?? '' ), 'Legacy title was not preserved on page ' . $page_id . '.' );
	$assert( '' === (string) ( $local['title']['secondary'] ?? '' ), 'Secondary title is not empty on page ' . $page_id . '.' );
	$assert( $subtitle === (string) ( $local['subtitle'] ?? '' ), 'Legacy subtitle was not preserved on page ' . $page_id . '.' );

	$background_id = (int) ( $local['background_image'] ?? 0 );
	$blur_id = (int) ( $local['blur_image'] ?? 0 );
	$background_ids[ $background_id ] = true;
	$blur_ids[ $blur_id ] = true;
	$assert( 0 < $background_id && 0 < $blur_id, 'Hero media IDs are missing on page ' . $page_id . '.' );

	$resolved = function_exists( 'platejka_resolve_section_data' )
		? platejka_resolve_section_data( array( 'slug' => 'hero', 'row' => $hero ), $page_id )
		: array();
	$assert( $title === (string) ( $resolved['title']['primary'] ?? '' ) && $subtitle === (string) ( $resolved['subtitle'] ?? '' ), 'Resolved local Hero copy differs on page ' . $page_id . '.' );
	foreach ( $shared_keys as $key ) {
		$assert( wp_json_encode( $global[ $key ] ?? null ) === wp_json_encode( $resolved[ $key ] ?? null ), 'Shared China field ' . $key . ' differs on page ' . $page_id . '.' );
	}
}

$assert( 137 === $targets, 'Applied migration target count is not 137.' );
$assert( 69 === $with_subtitle && 68 === $without_subtitle, 'Legacy title/subtitle distribution changed.' );
$assert( array( 5339 ) === array_keys( $background_ids ), 'Internal Hero background attachment is inconsistent.' );
$assert( 1 === count( $blur_ids ) && 0 < (int) array_key_first( $blur_ids ), 'Internal Hero blur attachment is inconsistent.' );
$blur_id = (int) array_key_first( $blur_ids );
$assert( 'hero-blur.png' === wp_basename( (string) get_attached_file( $blur_id ) ), 'Internal Hero blur attachment uses the wrong file.' );

WP_CLI::log( (string) wp_json_encode( array( 'checks' => $checks, 'targets' => $targets, 'background_attachment_id' => (int) array_key_first( $background_ids ), 'blur_attachment_id' => $blur_id, 'failure_count' => count( $failures ) ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
if ( $failures ) {
	WP_CLI::halt( 1 );
}
WP_CLI::success( 'Applied internal Hero migration passed the independent audit.' );
