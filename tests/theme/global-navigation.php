<?php
/** Read-only integration: missing data, unsafe URLs, lost hierarchy, eager assets and database writes. */
defined( 'ABSPATH' ) || exit;

function platejka_navigation_snapshot(): array {
	global $wpdb;
	return array(
		'options' => hash( 'sha256', serialize( $wpdb->get_results( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'theme_mods_%' OR option_name LIKE 'options_%' OR option_name LIKE '_options_%' OR option_name IN ('stylesheet','template','blogname','home','siteurl','active_plugins') ORDER BY option_name", ARRAY_A ) ) ),
		'menus' => hash( 'sha256', serialize( array( $wpdb->get_results( "SELECT t.*, tt.* FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} tt ON t.term_id=tt.term_id WHERE tt.taxonomy='nav_menu' ORDER BY t.term_id", ARRAY_A ), $wpdb->get_results( "SELECT * FROM {$wpdb->posts} WHERE post_type='nav_menu_item' ORDER BY ID", ARRAY_A ), $wpdb->get_results( "SELECT pm.* FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON pm.post_id=p.ID WHERE p.post_type='nav_menu_item' ORDER BY pm.meta_id", ARRAY_A ), $wpdb->get_results( "SELECT tr.* FROM {$wpdb->term_relationships} tr JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id=tt.term_taxonomy_id WHERE tt.taxonomy='nav_menu' ORDER BY tr.object_id,tr.term_taxonomy_id", ARRAY_A ) ) ) ),
		'content' => hash( 'sha256', serialize( array( $wpdb->get_results( "SELECT * FROM {$wpdb->posts} WHERE ID IN (22,24,1873) ORDER BY ID", ARRAY_A ), $wpdb->get_results( "SELECT * FROM {$wpdb->postmeta} WHERE post_id IN (22,24,1873) ORDER BY meta_id", ARRAY_A ) ) ) ),
	);
}
$before = platejka_navigation_snapshot();
WP_CLI::log( 'State hashes: ' . wp_json_encode( $before ) );
WP_CLI::log( 'Active theme: ' . get_stylesheet() );
$menus = wp_get_nav_menus();
foreach ( $menus as $menu ) {
	$items = wp_get_nav_menu_items( $menu->term_id );
	WP_CLI::log( sprintf( 'Menu term %d: %d items, %d child items.', $menu->term_id, count( $items ?: array() ), count( array_filter( $items ?: array(), static fn ( $item ) => (int) $item->menu_item_parent > 0 ) ) ) );
}
WP_CLI::log( 'Current assignments: ' . wp_json_encode( get_nav_menu_locations() ) );
WP_CLI::log( 'Legacy assignments: ' . wp_json_encode( get_option( 'theme_mods_platejka-pagespeed', array() )['nav_menu_locations'] ?? array() ) );
if ( 'inventory' === ( $args[0] ?? '' ) ) {
	$populated = array();
	foreach ( array( 'adres', 'vremya_raboty', 'pochta', 'telefon', 'dannye', 'servis', 'socz_seti_header', 'socz_seti_all', 'menyu_1', 'menyu_2', 'menyu_3' ) as $field ) {
		$value = get_field( $field, 'option' );
		$populated[ $field ] = is_array( $value ) ? count( $value ) : ! empty( $value );
	}
	WP_CLI::log( 'Contact population: ' . wp_json_encode( $populated ) );
	return;
}
$checks = 0;
$failures = array();
$assert = static function ( bool $condition, string $message ) use ( &$checks, &$failures ): void {
	++$checks;
	if ( ! $condition ) { $failures[] = $message; WP_CLI::warning( $message ); }
};
$assert( 'platejka_rework' === get_stylesheet(), 'Expected active theme must already be active.' );
foreach ( array( 'site-header', 'site-footer' ) as $section ) {
	$type = WP_Block_Type_Registry::get_instance()->get_registered( 'platejka/' . $section );
	$assert( $type && 3 === $type->api_version && is_callable( $type->render_callback ), 'Metadata dynamic block required: ' . $section );
	$part = str_replace( 'site-', '', $section );
	$blocks = array_values( array_filter( parse_blocks( file_get_contents( get_theme_file_path( 'parts/' . $part . '.html' ) ) ), static fn ( $block ) => null !== $block['blockName'] ) );
	$assert( 1 === count( $blocks ) && 'platejka/' . $section === $blocks[0]['blockName'] && ! $blocks[0]['innerBlocks'], 'Part must contain exactly its dynamic block.' );
	$handle = 'platejka-rework-section-' . $section;
	$assert( ! wp_style_is( $handle, 'enqueued' ) && ! wp_script_is( $handle, 'enqueued' ), 'Absent component must not enqueue.' );
}
$assert( isset( get_registered_nav_menus()['menu-1'], get_registered_nav_menus()['menu-2'] ), 'Legacy primary/mobile locations required.' );
foreach ( array( '404', 'archive', 'index', 'page', 'search', 'single' ) as $template ) {
	$blocks = parse_blocks( file_get_contents( get_theme_file_path( 'templates/' . $template . '.html' ) ) );
	foreach ( array( 'header', 'footer' ) as $slug ) {
		$part = array_values( array_filter( $blocks, static fn ( $block ) => 'core/template-part' === $block['blockName'] && $slug === ( $block['attrs']['slug'] ?? '' ) ) );
		$rendered = $part ? do_blocks( serialize_block( $part[0] ) ) : '';
		$assert( 1 === preg_match_all( '/<' . $slug . '\\b/', $rendered ), 'Template part must render one ' . $slug . ' landmark: ' . $template );
	}
}
$header = do_blocks( '<!-- wp:platejka/site-header /-->' );
$footer = do_blocks( '<!-- wp:platejka/site-footer /-->' );
$assert( str_contains( $header, '<header' ) && str_contains( $footer, '<footer' ), 'Live contact/menu rendering must produce landmarks.' );
foreach ( array( 'site-header', 'site-footer' ) as $section ) {
	$handle = 'platejka-rework-section-' . $section;
	$assert( wp_style_is( $handle, 'enqueued' ), 'Visible component style must enqueue.' );
}
$assert( wp_script_is( 'platejka-rework-section-site-header', 'enqueued' ) && ! wp_script_is( 'platejka-rework-section-site-footer', 'enqueued' ), 'Only header owns frontend script.' );
if ( function_exists( 'platejka_rework_menu_id' ) ) {
	$legacy = get_option( 'theme_mods_platejka-pagespeed', array() )['nav_menu_locations'] ?? array();
	$current = get_nav_menu_locations();
	foreach ( array( 'menu-1', 'menu-2' ) as $location ) {
		$expected = (int) ( $current[ $location ] ?? $legacy[ $location ] ?? 0 );
		$assert( $expected === platejka_rework_menu_id( $location ), 'Menu resolver must use current/legacy existing term ID: ' . $location );
	}
	$clear_current = static fn () => array( 'nav_menu_locations' => array() );
	add_filter( 'pre_option_theme_mods_platejka_rework', $clear_current );
	add_filter( 'pre_option_theme_mods_platejka-pagespeed', $clear_current );
	$assert( 0 === platejka_rework_menu_id( 'menu-1' ), 'Ambiguous menu terms must not be guessed.' );
	remove_filter( 'pre_option_theme_mods_platejka_rework', $clear_current );
	remove_filter( 'pre_option_theme_mods_platejka-pagespeed', $clear_current );
}
$diagnostics = array();
set_error_handler( static function ( $severity, $message ) use ( &$diagnostics ): bool { $diagnostics[] = $message; return true; }, E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE );
if ( function_exists( 'platejka_rework_navigation' ) ) {
	$items = array(
		(object) array( 'ID' => 91, 'menu_item_parent' => 0, 'title' => 'Parent <unsafe>', 'url' => '#', 'target' => '' ),
		(object) array( 'ID' => 92, 'menu_item_parent' => 91, 'title' => 'Child & title', 'url' => '/child?x=1&y=2', 'target' => '_blank' ),
		(object) array( 'ID' => 93, 'menu_item_parent' => 92, 'title' => 'Nested', 'url' => '/nested', 'target' => '' ),
		(object) array( 'ID' => 94, 'menu_item_parent' => 0, 'title' => 'Invalid', 'url' => 'javascript:alert(1)', 'target' => '' ),
	);
	$html = platejka_rework_navigation( $items, 'test-one' ) . platejka_rework_navigation( $items, 'test-two' );
	$assert( str_contains( $html, 'Parent &lt;unsafe&gt;' ) && str_contains( $html, 'Child &amp; title' ), 'Menu titles must be escaped.' );
	$assert( str_contains( $html, '/nested' ) && 4 === substr_count( $html, 'data-nav-toggle' ), 'Hierarchy must include each nested submenu.' );
	$assert( ! str_contains( $html, 'href="#"' ) && ! str_contains( $html, 'javascript:' ), 'Unsafe/demo URLs must not become anchors.' );
	$assert( str_contains( $html, 'target="_blank" rel="noopener noreferrer"' ), 'External target must be safe.' );
	preg_match_all( '/ id="([^"]+)"/', $html, $ids );
	$assert( count( $ids[1] ) === count( array_unique( $ids[1] ) ), 'Repeated navigation must have unique submenu IDs.' );
	$assert( '' === platejka_rework_navigation( array(), 'empty' ), 'Empty menu must not emit empty navigation.' );
}
$fixture = array(
	'adres' => 'Address <script>bad</script>', 'telefon' => '+7 (123) 456-78-90', 'pochta' => 'fixture@example.org', 'vremya_raboty' => 'Hours & time', 'servis' => 'Service & legal',
	'dannye' => array( 'inn' => 'Company <value>', 'bik' => 'Bank value' ),
	'socz_seti_header' => array( array( 'ssylka' => array( 'url' => 'https://example.org/social', 'title' => 'Social & label', 'target' => '_blank' ), 'svg' => array( 'url' => 'https://example.org/icon.svg', 'alt' => 'Icon', 'width' => 24, 'height' => 24 ), 'class' => 'unsafe" onclick="bad' ) ),
	'socz_seti_all' => array( array( 'ssylka' => array( 'url' => 'https://example.org/all', 'title' => 'All social', 'target' => '' ), 'class' => 'social' ) ),
	'menyu_1' => array( 'zagolovok' => 'Footer & title', 'ssylki' => array( array( 'ssylka_footer' => array( 'url' => '/footer-fixture', 'title' => 'Footer link', 'target' => '_blank' ) ) ) ),
);
$data = static function () use ( &$fixture ) { return $fixture; };
add_filter( 'platejka_rework_contact_data', $data );
$html = do_blocks( '<!-- wp:platejka/site-header /--><!-- wp:platejka/site-footer /-->' );
foreach ( array( 'Address &lt;script&gt;', 'tel:+71234567890', 'mailto:fixture@example.org', 'Service &amp; legal', 'Company &lt;value&gt;', '/footer-fixture', 'Footer &amp; title', 'https://example.org/social' ) as $text ) {
	$assert( str_contains( $html, $text ), 'Controlled ACF boundary must populate escaped output: ' . $text );
}
$export_contract = array(
	'header' => array( 'header__top', 'header__row_top', 'header__left', 'header__right', 'header__bottom', 'header__row_bottom', 'header__wrapper', 'header__logo', 'header__logo-mark', 'header__logo-word', 'header__navigation', 'header-menu-info', 'header__wrap', 'header__button_call' ),
	'footer' => array( 'footer__panel', 'footer__main', 'footer__contacts', 'footer__navigation', 'footer__bottom', 'footer__brand', 'footer__logo-link', 'footer__legal-links', 'footer__disclaimer' ),
);
foreach ( $export_contract as $area => $classes ) {
	foreach ( $classes as $class ) {
		$assert( str_contains( $html, 'class="' . $class ) || preg_match( '/class="[^"]*\\b' . preg_quote( $class, '/' ) . '\\b/', $html ), 'Supplied export class contract missing from ' . $area . ': ' . $class );
	}
}
$assert( str_contains( $html, 'data-export-source="sections/header/header.html"' ) && str_contains( $html, 'data-export-source="sections/footer/footer.html"' ), 'Rendered landmarks must declare their supplied export provenance.' );
$assert( str_contains( $html, '<svg class="header__logo-mark"' ) && str_contains( $html, '<svg class="header__logo-word"' ), 'Header must retain the supplied inline two-part brand artwork.' );
$assert( str_contains( $html, '/assets/src/images/footer/footerLogo.svg' ), 'Footer must use the supplied footer logo asset.' );
$header_css = file_get_contents( get_theme_file_path( 'assets/src/css/components/header.css' ) );
$footer_css = file_get_contents( get_theme_file_path( 'assets/src/css/components/footer.css' ) );
foreach ( array( '.header__row_top', '.header__wrapper', '.header__wrap', '@media(max-width: 1024px)', '@media(max-width: 768px)' ) as $needle ) {
	$assert( str_contains( $header_css, $needle ), 'Header stylesheet must retain supplied export selector/breakpoint: ' . $needle );
}
foreach ( array( '.footer__brand', '.footer__legal-links', 'grid-template-columns:minmax(0, 448px)', '@media(max-width: 1024px)' ) as $needle ) {
	$assert( str_contains( $footer_css, $needle ), 'Footer stylesheet must retain supplied export selector/breakpoint: ' . $needle );
}
$assert( ! preg_match( '/<script| onclick=|href="#"|javascript:/i', $html ), 'Controlled data must not introduce executable markup.' );
wp_dequeue_style( 'platejka-rework-section-site-footer' );
$fixture = array( 'telefon' => array(), 'pochta' => 'not-email', 'adres' => array(), 'servis' => false, 'dannye' => 'bad', 'menyu_1' => array( 'ssylki' => array( false, array( 'ssylka_footer' => array( 'url' => '#', 'title' => 'Bad' ) ) ) ), 'socz_seti_all' => array( false, array( 'ssylka' => array( 'url' => 'javascript:bad', 'title' => 'Bad' ) ) ) );
$empty = do_blocks( '<!-- wp:platejka/site-footer /-->' );
$assert( '' === trim( $empty ) && ! wp_style_is( 'platejka-rework-section-site-footer', 'enqueued' ), 'Malformed/empty footer must emit nothing and enqueue nothing.' );
$html = do_blocks( '<!-- wp:platejka/site-header /-->' );
$assert( ! str_contains( $html, 'tel:' ) && ! str_contains( $html, 'mailto:' ), 'Malformed contacts must not create broken anchors.' );
remove_filter( 'platejka_rework_contact_data', $data );
if ( function_exists( 'platejka_rework_register_global_blocks' ) ) { platejka_rework_register_global_blocks(); }
restore_error_handler();
$assert( ! $diagnostics, 'Repeated registration/malformed data must produce no warnings: ' . implode( '; ', $diagnostics ) );
$after = platejka_navigation_snapshot();
$assert( $before === $after, 'Rendering must not write content, ACF, menu terms, assignments or options.' );
if ( isset( $args[0] ) ) { $assert( wp_json_encode( $before ) === $args[0], 'State must match the pre-implementation baseline.' ); }
WP_CLI::log( 'Final state hashes: ' . wp_json_encode( $after ) );
WP_CLI::log( sprintf( 'Global navigation: %d checks, %d failures.', $checks, count( $failures ) ) );
if ( $failures ) { WP_CLI::halt( 1 ); }
WP_CLI::success( 'Global navigation render/data/preservation passed.' );
