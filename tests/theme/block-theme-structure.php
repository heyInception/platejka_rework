<?php
/**
 * Read-only block-theme contract checks; run via Studio WP-CLI eval-file.
 * Pass the pre-change content/ACF hash as the first argument for preservation.
 * Catches missing block templates/tokens, classic runtime coupling, and eager,
 * incorrect or duplicated section queues using real WordPress render hooks.
 */
defined( 'ABSPATH' ) || exit;

$checks = 0;
$failures = array();
$assert = static function ( bool $condition, string $message ) use ( &$checks, &$failures ): void {
	++$checks;
	if ( ! $condition ) {
		$failures[] = $message;
		WP_CLI::warning( $message );
	}
};
$root = get_stylesheet_directory();
$snapshot = array();
foreach ( array( 22, 24, 1873 ) as $id ) {
	$snapshot[ $id ] = array( get_post( $id ), get_post_meta( $id ), function_exists( 'get_fields' ) ? get_fields( $id ) : null );
}
$initial_hash = hash( 'sha256', serialize( $snapshot ) );
WP_CLI::log( 'Pre-render content/ACF SHA-256: ' . $initial_hash );
if ( isset( $args[0] ) ) {
	$assert( hash_equals( $args[0], $initial_hash ), 'Theme must preserve the baseline content and formatted ACF reads.' );
}
$assert( 'platejka_rework' === get_stylesheet(), 'The expected theme must already be active.' );
$assert( wp_is_block_theme(), 'Active theme must be a block theme.' );
$assert( 640 === ( $GLOBALS['content_width'] ?? null ), 'Compatibility content_width must remain 640 for formatted ACF/oEmbed reads.' );
$theme = wp_get_theme();
$assert( '6.8' === $theme->get( 'RequiresWP' ) && '8.1' === $theme->get( 'RequiresPHP' ), 'Theme must declare the agreed minimum versions.' );
$json_path = $root . '/theme.json';
$assert( is_file( $json_path ), 'theme.json must exist.' );
if ( is_file( $json_path ) ) {
	$json = json_decode( file_get_contents( $json_path ), true );
	$assert( JSON_ERROR_NONE === json_last_error() && is_array( $json ), 'theme.json must be valid JSON.' );
	$assert( 3 === ( $json['version'] ?? null ) && isset( $json['$schema'] ), 'theme.json must declare v3 and a schema.' );
	$assert( '1312px' === ( $json['settings']['layout']['contentSize'] ?? null ), 'Constrained content must match the design width.' );
	$palette = array_column( $json['settings']['color']['palette'] ?? array(), 'color', 'slug' );
	$assert( '#007336' === ( $palette['brand'] ?? null ) && '#25252a' === ( $palette['primary'] ?? null ) && '#f4f6fb' === ( $palette['surface-secondary'] ?? null ) && '#ffffff' === ( $palette['surface-primary'] ?? null ), 'Editor palette must expose supplied design colors.' );
	$families = array_column( $json['settings']['typography']['fontFamilies'] ?? array(), 'fontFamily', 'slug' );
	$assert( isset( $families['manrope'], $families['google-sans'] ), 'Editor must expose both supplied font families.' );
	$settings = WP_Theme_JSON_Resolver::get_theme_data()->get_settings();
	$assert( '1312px' === ( $settings['layout']['contentSize'] ?? null ), 'WordPress must consume the declared layout.' );
}
$flatten = static function ( array $blocks ) use ( &$flatten ): array {
	$result = array();
	foreach ( $blocks as $block ) {
		if ( $block['blockName'] ) {
			$result[] = $block;
		}
		$result = array_merge( $result, $flatten( $block['innerBlocks'] ) );
	}
	return $result;
};
foreach ( array( 'templates/index.html', 'templates/page.html', 'templates/404.html', 'templates/archive.html', 'templates/search.html', 'templates/single.html', 'parts/header.html', 'parts/footer.html' ) as $file ) {
	$assert( is_file( $root . '/' . $file ), 'Required block markup missing: ' . $file );
	if ( ! is_file( $root . '/' . $file ) ) {
		continue;
	}
	$content = file_get_contents( $root . '/' . $file );
	$blocks = $flatten( parse_blocks( $content ) );
	$assert( count( $blocks ) > 0, 'Markup must parse into blocks: ' . $file );
	foreach ( $blocks as $block ) {
		$expected = str_starts_with( $file, 'parts/' ) ? 'platejka/site-' . ( str_contains( $file, 'header' ) ? 'header' : 'footer' ) : '';
		$assert( $expected ? $expected === $block['blockName'] : str_starts_with( $block['blockName'], 'core/' ), 'Only expected core/global blocks may appear: ' . $file );
	}
	$assert( ! preg_match( '~(?:https?://|href\s*=|<p[\s>]|<img[\s>])~i', $content ), 'Foundation markup must not contain demo copy, links or media: ' . $file );
	if ( str_starts_with( $file, 'templates/' ) ) {
		$parts = array_column( array_filter( $blocks, static fn ( $block ) => 'core/template-part' === $block['blockName'] ), 'attrs' );
		$assert( array( 'header', 'footer' ) === array_column( $parts, 'slug' ), 'Template must resolve both shared parts: ' . $file );
		$names = array_column( $blocks, 'blockName' );
		if ( in_array( $file, array( 'templates/page.html', 'templates/single.html' ), true ) ) {
			$assert( in_array( 'core/post-content', $names, true ), 'Singular template must preserve existing post content: ' . $file );
		} elseif ( 'templates/404.html' === $file ) {
			$assert( in_array( 'core/search', $names, true ), '404 template must provide a content-neutral recovery control.' );
		} else {
			$assert( in_array( 'core/query', $names, true ) && in_array( 'core/post-template', $names, true ), 'Collection template must render the inherited query without demo fallback: ' . $file );
		}
		$main = array_values( array_filter( $blocks, static fn ( $block ) => 'main' === ( $block['attrs']['tagName'] ?? null ) ) );
		$assert( count( $main ) === 1 && 'constrained' === ( $main[0]['attrs']['layout']['type'] ?? null ), 'Template must have one constrained main landmark: ' . $file );
	} else {
		$tag = str_contains( $file, 'header' ) ? 'header' : 'footer';
		$assert( 1 === count( $blocks ) && 'platejka/site-' . $tag === $blocks[0]['blockName'], 'Part must render exactly one dynamic semantic landmark: ' . $file );
	}
}
foreach ( get_included_files() as $included ) {
	if ( str_starts_with( wp_normalize_path( $included ), wp_normalize_path( $root . '/inc/' ) ) ) {
		$assert( in_array( basename( $included ), array( 'assets.php', 'global-blocks.php' ), true ), 'Theme runtime must not load classic scaffold includes.' );
	}
}
$assert( ! has_action( 'widgets_init', 'platejka_rework_widgets_init' ), 'Theme must not register the classic widget runtime.' );
$assert( isset( get_registered_nav_menus()['menu-1'], get_registered_nav_menus()['menu-2'] ), 'Theme must expose existing primary/mobile menu locations.' );
$assert( false === has_filter( 'template_include', 'platejka_rework_block_template_fallback' ), 'Theme must rely on core block-template resolution without a duplicate template_include fallback.' );
foreach ( array( '404', 'archive', 'search', 'single' ) as $type ) {
	$resolved = get_query_template( $type );
	$assert( wp_normalize_path( ABSPATH . WPINC . '/template-canvas.php' ) === wp_normalize_path( $resolved ), 'Generic fallback must bypass preserved classic PHP templates: ' . $type );
}
$tokens_path = $root . '/assets/src/css/tokens.css';
$assert( is_file( $tokens_path ), 'Design tokens stylesheet must exist.' );
if ( is_file( $tokens_path ) ) {
	$tokens = file_get_contents( $tokens_path );
	foreach ( array( 'Manrope-Medium.ttf', 'Manrope-SemiBold.ttf', 'Manrope-Bold.ttf', 'GoogleSans-Regular.ttf' ) as $font ) {
		$assert( is_file( $root . '/assets/fonts/' . $font ), 'Supplied font must be present: ' . $font );
		$assert( str_contains( $tokens, '../../fonts/' . $font ) && ! str_contains( $tokens, 'fonts/fonts/' ), 'Font URL must resolve from CSS to the theme font directory: ' . $font );
	}
}

$assert( function_exists( 'platejka_rework_register_section_asset' ), 'Conditional section registry must exist.' );
if ( function_exists( 'platejka_rework_register_section_asset' ) ) {
	// Core 6.9+ removes newly enqueued assets from an empty block. Use a real
	// registered dynamic block with output, as downstream section blocks will.
	register_block_type( 'platejka/test-section', array( 'render_callback' => static fn () => '<div>Controlled section</div>' ) );
	register_block_type( 'platejka/unrelated', array( 'render_callback' => static fn () => '<div>Unrelated section</div>' ) );
	register_block_type( 'platejka/malformed', array( 'render_callback' => static fn () => '<div>Malformed registration probe</div>' ) );
	register_block_type( 'platejka/invalid-assets', array( 'render_callback' => static fn () => '<div>Invalid asset probe</div>' ) );
	register_block_type( 'platejka/wrong-types', array( 'render_callback' => static fn () => '<div>Wrong asset type probe</div>' ) );
	register_block_type( 'platejka/empty-section', array( 'render_callback' => static fn () => '' ) );
	register_block_type( 'platejka/whitespace-section', array( 'render_callback' => static fn () => " \n\t" ) );
	$conditional_output = '<div>Visible conditional section</div>';
	register_block_type(
		'platejka/conditional-section',
		array(
			'render_callback' => static function () use ( &$conditional_output ) {
				return $conditional_output;
			},
		)
	);
	$diagnostics = array();
	set_error_handler( static function ( $severity, $message ) use ( &$diagnostics ): bool {
		$diagnostics[] = $message;
		return true;
	}, E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE );
	$assets = array(
		'style' => array( 'src' => 'assets/src/css/base.css', 'deps' => array( 'platejka-rework-tokens' ), 'version' => 'test-1', 'media' => 'screen' ),
		'script' => array( 'src' => 'assets/src/js/runtime.js', 'deps' => array(), 'version' => 'test-1', 'args' => array( 'in_footer' => true, 'strategy' => 'defer' ) ),
	);
	platejka_rework_register_section_asset( ' Test-Section ', $assets );
	platejka_rework_register_section_asset( 'unrelated', $assets );
	platejka_rework_register_section_asset( 'empty-section', $assets );
	platejka_rework_register_section_asset( 'whitespace-section', $assets );
	platejka_rework_register_section_asset( 'conditional-section', $assets );
	platejka_rework_register_section_asset( '../malformed', $assets );
	platejka_rework_register_section_asset( 'invalid-assets', array( 'style' => array( 'src' => array() ), 'script' => 'wrong' ) );
	platejka_rework_register_section_asset(
		'wrong-types',
		array(
			'style' => array( 'src' => 'assets/src/js/runtime.js' ),
			'script' => array( 'src' => 'assets/src/css/base.css' ),
		)
	);
	do_action( 'wp_enqueue_scripts' );
	foreach ( array( 'platejka-rework-tokens', 'platejka-rework-base' ) as $handle ) {
		$assert( wp_style_is( $handle, 'enqueued' ), 'Global style must enqueue: ' . $handle );
	}
	$assert( wp_script_is( 'platejka-rework-runtime', 'enqueued' ), 'Global runtime must enqueue.' );
	$style = 'platejka-rework-section-test-section';
	$script = $style;
	$assert( ! wp_style_is( $style, 'enqueued' ) && ! wp_script_is( $script, 'enqueued' ), 'Absent section must not enqueue.' );
	do_blocks( '<!-- wp:paragraph --><p>Controlled test</p><!-- /wp:paragraph -->' );
	$assert( ! wp_style_is( $style, 'enqueued' ), 'Unrelated core render must not enqueue the section.' );
	do_blocks( '<!-- wp:platejka/test-section /-->' );
	$assert( wp_style_is( $style, 'enqueued' ) && wp_script_is( $script, 'enqueued' ), 'Matching custom block render must enqueue its style/script.' );
	$assert( ! wp_style_is( 'platejka-rework-section-unrelated', 'enqueued' ) && ! wp_script_is( 'platejka-rework-section-unrelated', 'enqueued' ), 'Unrendered registration must stay absent.' );
	if ( wp_style_is( $style, 'registered' ) && wp_script_is( $script, 'registered' ) ) {
		$registered = wp_styles()->registered[ $style ];
		$assert( get_theme_file_uri( 'assets/src/css/base.css' ) === $registered->src && array( 'platejka-rework-tokens' ) === $registered->deps && 'test-1' === $registered->ver && 'screen' === $registered->args, 'Section style must preserve the public asset arguments.' );
		$assert( 'defer' === wp_scripts()->get_data( $script, 'strategy' ) && 1 === wp_scripts()->get_data( $script, 'group' ), 'Section script must support footer/defer arguments.' );
	}
	do_blocks( '<!-- wp:platejka/test-section /--><!-- wp:platejka/test-section /-->' );
	$assert( 1 === count( array_keys( wp_styles()->queue, $style, true ) ) && 1 === count( array_keys( wp_scripts()->queue, $script, true ) ), 'Repeated render must not duplicate queues.' );
	$preserve_empty_assets = static fn () => true;
	add_filter( 'enqueue_empty_block_content_assets', $preserve_empty_assets );
	do_blocks( '<!-- wp:platejka/empty-section /--><!-- wp:platejka/whitespace-section /-->' );
	foreach ( array( 'empty-section', 'whitespace-section' ) as $empty_section ) {
		$empty_handle = 'platejka-rework-section-' . $empty_section;
		$assert( ! wp_style_is( $empty_handle, 'enqueued' ) && ! wp_script_is( $empty_handle, 'enqueued' ), 'Empty and whitespace-only sections must not enqueue assets: ' . $empty_section );
	}
	do_blocks( '<!-- wp:platejka/conditional-section /-->' );
	$conditional_handle = 'platejka-rework-section-conditional-section';
	$assert( wp_style_is( $conditional_handle, 'enqueued' ) && wp_script_is( $conditional_handle, 'enqueued' ), 'A visible conditional section must enqueue its assets.' );
	$conditional_output = " \n\t";
	do_blocks( '<!-- wp:platejka/conditional-section /-->' );
	$assert( wp_style_is( $conditional_handle, 'enqueued' ) && wp_script_is( $conditional_handle, 'enqueued' ), 'A later empty instance must not dequeue assets required by a visible instance.' );
	remove_filter( 'enqueue_empty_block_content_assets', $preserve_empty_assets );
	platejka_rework_register_section_asset( 'test-section', array( 'style' => array( 'src' => 'assets/src/css/tokens.css', 'version' => 'replacement' ) ) );
	do_blocks( '<!-- wp:platejka/test-section /-->' );
	$assert( 'replacement' === ( wp_styles()->registered[ $style ]->ver ?? null ), 'Deliberate registration replacement must be deterministic.' );
	do_blocks( '<!-- wp:platejka/malformed /--><!-- wp:platejka/invalid-assets /--><!-- wp:platejka/wrong-types /-->' );
	$assert( ! wp_style_is( 'platejka-rework-section-malformed', 'enqueued' ) && ! wp_style_is( 'platejka-rework-section-invalid-assets', 'enqueued' ), 'Malformed registration must not silently bind assets.' );
	$assert( ! wp_style_is( 'platejka-rework-section-wrong-types', 'enqueued' ) && ! wp_script_is( 'platejka-rework-section-wrong-types', 'enqueued' ), 'Style and script descriptors must reject the wrong file type.' );
	foreach ( array( 'index', 'page', '404', 'archive', 'search', 'single' ) as $slug ) {
		$template = get_block_template( 'platejka_rework//' . $slug, 'wp_template' );
		$assert( $template instanceof WP_Block_Template && 'theme' === $template->source, 'WordPress must resolve the filesystem template: ' . $slug );
		if ( $template ) {
			$rendered = do_blocks( $template->content );
			$assert( str_contains( $rendered, '<main' ) && str_contains( $rendered, '<header' ) && str_contains( $rendered, '<footer' ), 'WordPress must render the semantic template landmarks: ' . $slug );
		}
	}
	restore_error_handler();
	$assert( array() === $diagnostics, 'Registrations and template rendering must produce no PHP notices/warnings.' );
}

$snapshot = array();
foreach ( array( 22, 24, 1873 ) as $id ) {
	$snapshot[ $id ] = array( get_post( $id ), get_post_meta( $id ), function_exists( 'get_fields' ) ? get_fields( $id ) : null );
}
$final_hash = hash( 'sha256', serialize( $snapshot ) );
$assert( hash_equals( $initial_hash, $final_hash ), 'Template rendering and registry checks must preserve content/ACF reads.' );
WP_CLI::log( 'Content/ACF SHA-256: ' . $final_hash );
WP_CLI::log( sprintf( 'Block theme: %d checks, %d failures.', $checks, count( $failures ) ) );
if ( $failures ) {
	WP_CLI::halt( 1 );
}
WP_CLI::success( 'Block-theme structure and real asset behavior passed.' );
