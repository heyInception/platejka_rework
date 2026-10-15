<?php
/** Blog module contract tests without a WordPress bootstrap. */

$failures = array();
$checks   = 0;
$assert   = static function ( bool $condition, string $message ) use ( &$failures, &$checks ): void {
	++$checks;
	if ( ! $condition ) {
		$failures[] = $message;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
};

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( string $text ): string {
		return strip_tags( $text );
	}
}

$module = dirname( __DIR__, 2 ) . '/themes/platejka_rework/inc/blog.php';
$assert( is_file( $module ), 'Blog helper module must exist.' );
if ( is_file( $module ) ) {
	require_once $module;
}

$assert( function_exists( 'platejka_blog_posts_per_page' ), 'Batch-size helper must be available.' );
$assert( function_exists( 'platejka_blog_reading_time_from_content' ), 'Content reading-time helper must be available.' );
$assert( function_exists( 'platejka_blog_reading_time' ), 'Reading-time helper must be available.' );
$assert( function_exists( 'platejka_blog_view_count' ), 'Guarded views helper must be available.' );
$assert( function_exists( 'platejka_blog_rendered_content' ), 'Rendered-content boundary must be available.' );

if ( function_exists( 'platejka_blog_posts_per_page' ) ) {
	$assert( 3 === platejka_blog_posts_per_page( 'landing', 1440 ), 'Landing desktop batch must contain three cards.' );
	$assert( 2 === platejka_blog_posts_per_page( 'landing', 375 ), 'Landing mobile batch must contain two cards.' );
	$assert( 9 === platejka_blog_posts_per_page( 'archive', 1440 ), 'Archive desktop batch must contain nine cards.' );
	$assert( 6 === platejka_blog_posts_per_page( 'archive', 768 ), 'Archive tablet batch must contain six cards.' );
	$assert( 4 === platejka_blog_posts_per_page( 'archive', 375 ), 'Archive mobile batch must contain four cards.' );
}

if ( function_exists( 'platejka_blog_reading_time_from_content' ) ) {
	$assert( 2 === platejka_blog_reading_time_from_content( str_repeat( 'word ', 399 ) ), '399 words must report two minutes at 200 words per minute.' );
}

if ( function_exists( 'platejka_blog_view_count' ) ) {
	$assert( is_string( platejka_blog_view_count( 10 ) ), 'Views helper must always return a display-safe string.' );
}

if ( function_exists( 'platejka_blog_rendered_content' ) ) {
	$embed = '<p>Intro</p><iframe src="https://example.com/embed"></iframe>';
	$assert( $embed === platejka_blog_rendered_content( $embed ), 'Trusted filtered post content must preserve embeds.' );
}

$style = file_get_contents( dirname( __DIR__, 2 ) . '/themes/platejka_rework/style.css' );
$assert( false !== strpos( $style, '.wpcf7-spinner' ), 'Contact Form 7 spinner styles must remain present.' );

printf( "Blog contract: %d checks, %d failures.\n", $checks, count( $failures ) );
exit( $failures ? 1 : 0 );

