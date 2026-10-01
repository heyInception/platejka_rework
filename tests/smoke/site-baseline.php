<?php
/**
 * Read-only characterization of the original local site.
 * Run with Studio WP-CLI; no options, posts, metadata or files are written.
 */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    exit( "Run this smoke test through Studio WP-CLI.\n" );
}

$failures = 0;
$checks   = 0;
$check    = static function ( bool $passed, string $label, string $actual ) use ( &$failures, &$checks ): void {
    ++$checks;
    if ( ! $passed ) {
        ++$failures;
    }
    WP_CLI::line( ( $passed ? 'PASS' : 'FAIL' ) . ': ' . $label . ' | actual=' . $actual );
};

$pages = array(
    24   => array( 'glavnaya', 'page-home.php' ),
    1873 => array( 'china', 'page-payments.php' ),
    22   => array( 'o-kompanii', 'page-company.php' ),
);
foreach ( $pages as $id => $expected ) {
    $page = get_post( $id );
    $type = $page ? $page->post_type : '(missing)';
    $slug = $page ? $page->post_name : '(missing)';
    $template = (string) get_post_meta( $id, '_wp_page_template', true );
    $check( 'page' === $type, "page {$id} exists as page", $type );
    $check( $expected[0] === $slug, "page {$id} slug={$expected[0]}", $slug );
    $check( $expected[1] === $template, "page {$id} template={$expected[1]}", $template );
}

$theme = wp_get_theme();
$check( 'platejka-pagespeed' === $theme->get_stylesheet(), 'active theme=platejka-pagespeed', $theme->get_stylesheet() );
$check( '1.2.6' === $theme->get( 'Version' ), 'active theme version=1.2.6', $theme->get( 'Version' ) );
$wp_version = get_bloginfo( 'version' );
$check( '7.0.2' === $wp_version, 'WordPress=7.0.2', $wp_version );
$check( 1 === preg_match( '/^8\.4\./', PHP_VERSION ), 'PHP=8.4.x', PHP_VERSION );

require_once ABSPATH . 'wp-admin/includes/plugin.php';
$plugins = get_plugins();
$required = array(
    'advanced-custom-fields-pro/acf.php' => 'ACF Pro',
    'contact-form-7/wp-contact-form-7.php' => 'Contact Form 7',
    'wordpress-seo/wp-seo.php' => 'Yoast SEO',
    'wp-rocket/wp-rocket.php' => 'WP Rocket',
);
foreach ( $required as $file => $label ) {
    $active = is_plugin_active( $file );
    $version = isset( $plugins[ $file ] ) ? $plugins[ $file ]['Version'] : '(missing)';
    $check( $active && isset( $plugins[ $file ] ), "required plugin {$label} active", ( $active ? 'active' : 'inactive' ) . ' version=' . $version );
}

WP_CLI::line( "Summary: {$checks} checks, {$failures} failures." );
WP_CLI::halt( 0 === $failures ? 0 : 1 );
