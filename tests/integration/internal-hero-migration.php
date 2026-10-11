<?php
/** Read-only integration contract for the internal Hero migration preview. */

defined( 'ABSPATH' ) || exit;

$failures = array();
$assert   = static function ( bool $condition, string $message ) use ( &$failures ): void {
	if ( ! $condition ) {
		$failures[] = $message;
		WP_CLI::warning( $message );
	}
};

$snapshot = static function (): string {
	$ids   = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => array( 'publish', 'future', 'draft', 'pending', 'private' ),
			'fields'         => 'ids',
			'posts_per_page' => -1,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		)
	);
	$state = array( 'marker' => get_option( \Platejka\Core\Acf\LegacyFieldVisibility::HERO_VERSION_OPTION, 0 ), 'pages' => array() );
	foreach ( $ids as $id_value ) {
		$id = (int) $id_value;
		$state['pages'][ $id ] = array(
			'builder'  => get_field( 'field_platejka_page_builder_v1', $id, false ),
			'rows'     => get_field( 'field_platejka_sections_v1', $id, false ),
			'template' => get_post_meta( $id, '_wp_page_template', true ),
		);
	}
	return hash( 'sha256', (string) wp_json_encode( $state, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
};

$before = $snapshot();
$report = \Platejka\Core\Cli\InternalHeroMigrationCommand::preview();
$after  = $snapshot();

$assert( 'dry-run' === ( $report['mode'] ?? null ), 'Internal Hero preview is not in dry-run mode.' );
$assert( false === ( $report['writes']['performed'] ?? null ), 'Internal Hero preview reported performed writes.' );
$assert( $before === $after, 'Internal Hero preview changed WordPress content or options.' );
$already_migrated = true === ( $report['already_migrated'] ?? null );
if ( $already_migrated ) {
	$assert( array( 'target_pages' => 0, 'conflicts' => 0, 'writes' => 0 ) === ( $report['totals'] ?? null ), 'Completed migration preview is not idempotent.' );
	WP_CLI::log( (string) wp_json_encode( array( 'already_migrated' => true, 'totals' => $report['totals'] ?? array(), 'failure_count' => count( $failures ) ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
	if ( $failures ) {
		WP_CLI::halt( 1 );
	}
	WP_CLI::success( 'Completed internal Hero migration preview is read-only and idempotent.' );
	return;
}
$assert( false === ( $report['already_migrated'] ?? null ), 'Internal Hero migration marker unexpectedly exists before apply.' );
$assert( 137 === ( $report['totals']['target_pages'] ?? null ), 'Internal Hero preview does not target exactly 137 pages.' );
$assert( 0 === ( $report['totals']['conflicts'] ?? null ), 'Internal Hero preview contains existing-Hero conflicts.' );
$media_sources = array_merge( array_column( (array) ( $report['media']['reused'] ?? array() ), 'source' ), (array) ( $report['media']['to_import'] ?? array() ) );
sort( $media_sources, SORT_STRING );
$assert( array( 'theme://sections/hero/img/hero-bg.png', 'theme://sections/hero/img/hero-blur.png' ) === $media_sources, 'Internal Hero media plan is incomplete.' );
foreach ( (array) ( $report['media']['to_import'] ?? array() ) as $source ) {
	$assert( is_file( get_theme_file_path( ltrim( substr( $source, 8 ), '/' ) ) ), 'Internal Hero media source is missing: ' . $source . '.' );
}

$pages = array_column( (array) ( $report['pages'] ?? array() ), null, 'id' );
foreach ( array( 22, 24, 1873, 3632 ) as $excluded_id ) {
	$assert( ! isset( $pages[ $excluded_id ] ), 'Excluded page appears in the migration: ' . $excluded_id . '.' );
}
foreach ( array( 1860, 2075, 2098, 2109 ) as $page_id ) {
	$assert( 'default' === ( $pages[ $page_id ]['target_template'] ?? null ), 'Custom template is not switched to default for page ' . $page_id . '.' );
}
foreach ( $pages as $page_id => $page ) {
	$assert( 'hero' === ( $page['target_sections'][0] ?? null ), 'Hero is not first on page ' . $page_id . '.' );
}

$row_writes = array();
$template_writes = array();
$builder_writes = array();
foreach ( (array) ( $report['writes']['planned'] ?? array() ) as $write ) {
	if ( 'field_platejka_sections_v1' === ( $write['field'] ?? null ) ) {
		$row_writes[ (int) $write['post_id'] ] = $write;
	}
	if ( '_wp_page_template' === ( $write['field'] ?? null ) ) {
		$template_writes[ (int) $write['post_id'] ] = $write;
	}
	if ( 'field_platejka_page_builder_v1' === ( $write['field'] ?? null ) ) {
		$builder_writes[ (int) $write['post_id'] ] = $write;
	}
}
$assert( 137 === count( $row_writes ), 'Preview does not plan one Hero row write per target page.' );
$approved_template_ids = array( 1860, 2075, 2098, 2109 );
$assert( array() === array_values( array_diff( array_keys( $template_writes ), $approved_template_ids ) ), 'Preview contains an unapproved template write.' );
foreach ( $approved_template_ids as $page_id ) {
	$current_template = (string) get_page_template_slug( $page_id );
	$assert( isset( $template_writes[ $page_id ] ) || in_array( $current_template, array( '', 'default' ), true ), 'Approved page is neither already on the default template nor planned for switching: ' . $page_id . '.' );
}

foreach ( $row_writes as $page_id => $write ) {
	$hero = $write['target'][0] ?? array();
	$local = $hero['overrides']['hero'] ?? array();
	$assert( 'hero' === ( $hero['acf_fc_layout'] ?? null ), 'First target row is not Hero on page ' . $page_id . '.' );
	$assert( 1 === ( $hero['shared_content_only'] ?? null ), 'Shared-only inheritance is missing on page ' . $page_id . '.' );
	$assert( trim( (string) get_field( 'zaglovok_hero', $page_id, false ) ) === ( $local['title']['primary'] ?? null ), 'Legacy title mapping differs on page ' . $page_id . '.' );
	$assert( '' === ( $local['title']['secondary'] ?? null ), 'Secondary title is not empty on page ' . $page_id . '.' );
	$assert( trim( (string) get_field( 'podzaglovok_hero', $page_id, false ) ) === ( $local['subtitle'] ?? null ), 'Legacy subtitle mapping differs on page ' . $page_id . '.' );
	$assert( '#25252a' === ( $local['background_color'] ?? null ), 'Background color differs on page ' . $page_id . '.' );
	$assert( ! array_key_exists( 'badge', $local ) && ! array_key_exists( 'items', $local ), 'Shared China content was copied locally on page ' . $page_id . '.' );
}

WP_CLI::log( (string) wp_json_encode( array( 'totals' => $report['totals'] ?? array(), 'media' => $report['media'] ?? array(), 'builder_writes' => array_map( static fn( int $page_id ): array => array( 'id' => $page_id, 'title' => get_the_title( $page_id ) ), array_keys( $builder_writes ) ), 'template_writes' => array_keys( $template_writes ), 'failure_count' => count( $failures ) ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
if ( $failures ) {
	WP_CLI::halt( 1 );
}
WP_CLI::success( 'Internal Hero migration preview is read-only and matches the approved scope.' );
