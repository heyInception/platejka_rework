<?php
/** Contract for left-side navigation tabs on the shared section defaults page. */

defined( 'ABSPATH' ) || exit;

$checks = 0; $failures = array();
$assert = static function ( bool $condition, string $message ) use ( &$checks, &$failures ): void {
	++$checks;
	if ( ! $condition ) { $failures[] = $message; WP_CLI::warning( $message ); }
};

$types = function_exists( 'acf_get_field_types_info' ) ? array_keys( (array) acf_get_field_types_info() ) : array();
WP_CLI::log( sprintf(
	'ACF environment: version=%s, pro=%s, options_pages=%s, tab=%s.',
	function_exists( 'acf_get_setting' ) ? (string) acf_get_setting( 'version' ) : 'inactive',
	function_exists( 'acf_is_pro' ) && acf_is_pro() ? 'yes' : 'no',
	function_exists( 'acf_add_options_page' ) ? 'yes' : 'no',
	in_array( 'tab', $types, true ) ? 'yes' : 'no'
) );
$assert( function_exists( 'acf_get_setting' ), 'ACF is active.' );
$assert( in_array( 'tab', $types, true ), 'Installed ACF supports the tab field type.' );
$assert( function_exists( 'acf_add_options_page' ), 'Installed ACF supports options pages.' );

$path = WP_CONTENT_DIR . '/plugins/platejka-core/acf-json/group_platejka_section_defaults_v1.json';
$group = is_readable( $path ) ? json_decode( (string) file_get_contents( $path ), true ) : array();
$assert( is_array( $group ) && 'group_platejka_section_defaults_v1' === ( $group['key'] ?? '' ), 'Shared defaults Local JSON is valid.' );

$expected = array(
	array( 'field_platejka_defaults_hero_main_v1', 'Hero — Главная', 'hero_main' ),
	array( 'field_platejka_defaults_about_v1', 'О компании', 'about' ),
	array( 'field_platejka_defaults_shipments_v1', 'Направления платежей', 'shipments' ),
	array( 'field_platejka_defaults_guarantees_v1', 'Гарантии', 'guarantees' ),
	array( 'field_platejka_defaults_documents_v1', 'Документы', 'documents' ),
	array( 'field_platejka_defaults_compliance_v1', 'Комплаенс', 'compliance' ),
	array( 'field_platejka_defaults_review_main_v1', 'Отзывы', 'review_main' ),
	array( 'field_platejka_defaults_work_v1', 'Как мы работаем', 'work' ),
	array( 'field_platejka_defaults_calculator_v1', 'Калькулятор', 'calculator' ),
	array( 'field_platejka_defaults_with_us_v1', 'Почему с нами', 'with_us' ),
	array( 'field_platejka_defaults_destinations_v1', 'Доступные направления', 'destinations' ),
	array( 'field_platejka_defaults_seo_v1', 'SEO-блок', 'seo' ),
	array( 'field_platejka_defaults_problems_v1', 'Проблемы', 'problems' ),
	array( 'field_platejka_defaults_serves_v1', 'Для кого работаем', 'serves' ),
	array( 'field_platejka_defaults_cases_v1', 'Кейсы', 'cases' ),
	array( 'field_platejka_defaults_table_v1', 'Сравнительная таблица', 'table' ),
	array( 'field_platejka_defaults_faq_v1', 'Часто задаваемые вопросы', 'faq' ),
	array( 'field_platejka_defaults_call_v1', 'Форма заявки', 'call' ),
	array( 'field_platejka_defaults_hero_v1', 'Hero — Внутренняя страница', 'hero' ),
	array( 'field_platejka_defaults_protection_v1', 'Защита платежа', 'protection' ),
);
$fields = $group['fields'] ?? array();
$actual = array(); $valid_tabs = true; $keys = array();
foreach ( $expected as $index => $expected_row ) {
	list( $group_key, $label, $name ) = $expected_row;
	$tab = $fields[ $index * 2 ] ?? array(); $field = $fields[ $index * 2 + 1 ] ?? array();
	$actual[] = array( $field['key'] ?? '', $tab['label'] ?? '', $field['name'] ?? '' );
	$valid_tabs = $valid_tabs && 'tab' === ( $tab['type'] ?? '' ) && 'left' === ( $tab['placement'] ?? '' ) && 0 === (int) ( $tab['endpoint'] ?? -1 ) && '' === ( $tab['name'] ?? null ) && 'group' === ( $field['type'] ?? '' );
	$keys[] = $tab['key'] ?? ''; $keys[] = $field['key'] ?? '';
}
$assert( count( $expected ) * 2 === count( $fields ), 'Each shared section group has exactly one navigation tab.' );
$assert( $expected === $actual, 'Tabs and existing groups keep the exact approved labels, keys, names, and order.' );
$assert( $valid_tabs, 'Every navigation field is a left tab followed by the unchanged section group.' );
$assert( ! in_array( '', $keys, true ) && count( $keys ) === count( array_unique( $keys ) ), 'Top-level ACF field keys are non-empty and unique.' );
$live_fields = function_exists( 'acf_get_fields' ) ? acf_get_fields( 'group_platejka_section_defaults_v1' ) : array();
$live_tabs = array_filter( is_array( $live_fields ) ? $live_fields : array(), static fn( $field ) => 'tab' === ( $field['type'] ?? '' ) && 'left' === ( $field['placement'] ?? '' ) );
$assert( 40 === count( is_array( $live_fields ) ? $live_fields : array() ) && 20 === count( $live_tabs ), 'Live ACF loads all 20 left tabs and 20 existing groups.' );

WP_CLI::log( sprintf( 'Shared section tabs: %d checks, %d failures.', $checks, count( $failures ) ) );
if ( $failures ) { WP_CLI::halt( 1 ); }
WP_CLI::success( 'Shared section tabs schema contract passed.' );
