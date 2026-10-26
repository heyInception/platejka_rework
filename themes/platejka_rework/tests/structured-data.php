<?php
/** Run with: studio wp eval-file wp-content/themes/platejka_rework/tests/structured-data.php */

$check = static function ( bool $condition, string $message ): void {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
};

$check( 'platejka_rework' === get_stylesheet(), 'Expected the rework theme to be active.' );
$check( (bool) acf_get_local_field_group( 'group_platejka_schema_v1' ), 'ACF schema group is missing.' );
$check( false === apply_filters( 'wpseo_json_ld_output', array( 'probe' => true ), '' ), 'Yoast schema was not disabled.' );
$check( platejka_is_service_page( 4944 ), 'Service page was not classified as Service.' );
$check( ! platejka_is_service_page( 5170 ), 'Legal page was classified as Service.' );
$check( ! platejka_is_service_page( 24 ), 'Home page was classified as Service.' );
$check( '' !== platejka_schema_value( 'name' ), 'Organization name is empty.' );

ob_start();
platejka_render_section( 'footer' );
$footer = (string) ob_get_clean();
$check( str_contains( $footer, 'https://schema.org/FinancialService' ), 'Footer FinancialService missing.' );
$check( str_contains( $footer, 'https://schema.org/PostalAddress' ), 'Footer address missing.' );
$check( str_contains( $footer, esc_html( platejka_schema_value( 'phone' ) ) ), 'Footer phone differs from ACF.' );

$section_anchor = 'faq-test';
$section_instance = 'faq-test';
$section_data = array( 'title' => 'Вопросы', 'items' => array( array( 'title' => 'Как?', 'text' => '<p>Так.</p>' ) ) );
ob_start();
include get_theme_file_path( 'sections/faq/faq.php' );
$faq = (string) ob_get_clean();
$check( str_contains( $faq, 'https://schema.org/FAQPage' ), 'FAQPage missing.' );
$check( str_contains( $faq, 'itemprop="acceptedAnswer"' ), 'FAQ answer missing.' );

echo "Structured data checks passed.\n";
