<?php
/** Header logo sizing contract. */

$stylesheet = (string) file_get_contents(
	dirname( __DIR__, 2 ) . '/themes/platejka_rework/sections/header/header.css'
);

$checks = array(
	'desktop mark height is 60px'       => str_contains( $stylesheet, '.header__logo-mark{width:auto;height:60px}' ),
	'desktop wordmark width is 150px'   => str_contains( $stylesheet, '.header__logo-word{width:150px;height:auto}' ),
	'desktop logo gap is 12.5px'        => str_contains( $stylesheet, '.header__logo-link{gap:12.5px' ),
	'mobile mark height remains 32px'   => str_contains( $stylesheet, '@media(max-width: 768px)' ) && str_contains( $stylesheet, '.header__logo-mark{height:32px}' ),
);

$failures = array_keys( array_filter( $checks, static fn ( bool $passed ): bool => ! $passed ) );

if ( $failures ) {
	fwrite( STDERR, "Header logo sizing failed:\n- " . implode( "\n- ", $failures ) . "\n" );
	exit( 1 );
}

fwrite( STDOUT, "Header logo sizing passed.\n" );
