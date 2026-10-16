<?php
/**
 * Contact modal integration contract.
 *
 * Run with: php tests/contact-modals.php
 */

$theme_dir = dirname( __DIR__ );
$module    = $theme_dir . '/inc/contact-modals.php';

if ( ! file_exists( $module ) ) {
	fwrite( STDERR, "Missing contact modal module.\n" );
	exit( 1 );
}

$php = file_get_contents( $module );
$js  = file_get_contents( $theme_dir . '/assets/contact-modals.js' );
$css = file_get_contents( $theme_dir . '/assets/contact-modals.css' );

$checks = array(
	'ACF options page'          => "platejka-contact-modals",
	'consultation form option' => "consultation_form",
	'calculator form option'   => "calculator_form",
	'global consultation modal'=> "data-consultation-dialog",
	'CTA source field'         => "platejka-request-source",
	'page URL field'           => "platejka-page-url",
	'CF7 success event'        => "wpcf7mailsent",
	'blog CTA selector'        => ".blog-button",
	'accessible modal CSS'     => ".contact-modal",
	'custom consent checkbox'  => '.contact-modal__consent input[type="checkbox"]',
);

foreach ( $checks as $label => $needle ) {
	$haystack = str_contains( $needle, 'wpcf7' ) || str_contains( $needle, '.blog' ) ? $js : ( str_contains( $needle, '.contact') ? $css : $php );
	if ( false === strpos( $haystack, $needle ) ) {
		fwrite( STDERR, sprintf( "Missing %s (%s).\n", $label, $needle ) );
		exit( 1 );
	}
}

if ( false === strpos( $css, '.contact-modal__consent input[type="checkbox"]:checked+.wpcf7-list-item-label::before' ) ) {
	fwrite( STDERR, "Missing checked consent state.\n" );
	exit( 1 );
}

foreach ( array( 'call.php', 'call-about.php' ) as $template ) {
	$call_php = file_get_contents( $theme_dir . '/sections/call/' . $template );
	if ( false === strpos( $call_php, "platejka_cf7_remove_field_paragraph( \$form_html, 'your-message' )" ) ) {
		fwrite( STDERR, sprintf( "%s must remove the textarea field.\n", $template ) );
		exit( 1 );
	}
}

$sample = '<span class="wpcf7-form-control-wrap" data-name="your-message"><textarea name="your-message"></textarea></span>';
if ( false !== strpos( platejka_cf7_remove_field_paragraph( $sample, 'your-message' ), '<textarea' ) ) {
	fwrite( STDERR, "Textarea wrapper without an enclosing paragraph must be removed.\n" );
	exit( 1 );
}

$styled_textarea = platejka_contact_modal_style_form_html( '<textarea class="wpcf7-form-control wpcf7-textarea" name="your-message"></textarea>' );
if ( false === strpos( $styled_textarea, 'ui-input' ) || false === strpos( $styled_textarea, 'contact-modal__message' ) ) {
	fwrite( STDERR, "Modal textarea must receive the shared input and message classes.\n" );
	exit( 1 );
}

echo "Contact modal contract passed.\n";
