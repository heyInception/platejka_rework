<?php
/** Local-only, read-only page for exercising actual Task 7 block rendering in a browser. */
defined( 'ABSPATH' ) || exit;

function platejka_task7_live_page(): void {
	if ( 'local' !== wp_get_environment_type() || ! isset( $_GET['platejka_task7_live'] ) ) { return; }
	$markup = do_blocks( '<!-- wp:platejka/calculator /--><!-- wp:platejka/call {"variant":"default"} /-->' );
	status_header( 200 );
	nocache_headers();
	?><!doctype html>
	<html <?php language_attributes(); ?>><head><meta charset="<?php bloginfo( 'charset' ); ?>"><meta name="viewport" content="width=device-width, initial-scale=1"><?php wp_head(); ?></head>
	<body <?php body_class( 'platejka-task7-live' ); ?>><?php wp_body_open(); ?><main><?php echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Actual block render output under test. ?></main><?php wp_footer(); ?></body></html><?php
	exit;
}
add_action( 'template_redirect', 'platejka_task7_live_page', 0 );
