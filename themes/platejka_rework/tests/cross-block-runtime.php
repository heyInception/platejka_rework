<?php
/** Runtime regression for builder-controlled cross-block composition. */

$theme_dir = dirname( __DIR__ );
require_once $theme_dir . '/inc/cross-blocks.php';
require_once $theme_dir . '/inc/cross-block-schema.php';

function platejka_cross_runtime_assert( bool $condition, string $message ): void {
	if ( ! $condition ) {
		WP_CLI::error( $message );
	}
}

$warnings = array();
set_error_handler(
	static function ( int $severity, string $message, string $file, int $line ) use ( &$warnings ): bool {
		if ( E_WARNING === $severity || E_NOTICE === $severity || E_USER_WARNING === $severity || E_USER_NOTICE === $severity || E_DEPRECATED === $severity ) {
			$warnings[] = $message . ' at ' . $file . ':' . $line;
			return true;
		}
		return false;
	}
);

$rows = array(
	array(
		'acf_fc_layout' => 'wtt',
		'show_block'    => 1,
		'title'         => 'Обмен валют',
	),
	array(
		'acf_fc_layout' => 'company_metrics',
		'show_block'    => 1,
		'title'         => 'Показатели компании',
		'metrics'       => array(
			array( 'value' => 100, 'suffix' => '+', 'label' => 'клиентов' ),
			array( 'value' => 24, 'suffix' => '/7', 'label' => 'поддержка' ),
			array( 'value' => 5, 'suffix' => ' лет', 'label' => 'на рынке' ),
		),
	),
);

$builder_sections = array(
	array( 'slug' => 'hero', 'instance' => 'section-test-1' ),
	array( 'slug' => 'cross-exchange', 'instance' => 'section-test-2', 'anchor' => 'exchange' ),
	array( 'slug' => 'cross-company-metrics', 'instance' => 'section-test-3' ),
	array( 'slug' => 'cross-exchange', 'instance' => 'section-test-4' ),
	array( 'slug' => 'faq', 'instance' => 'section-test-5' ),
);

$sections = platejka_compose_cross_blocks( $builder_sections, $rows, 999999 );
$slugs    = array_map( 'platejka_cross_block_section_slug', $sections );

platejka_cross_runtime_assert( array( 'hero', 'cross-exchange', 'cross-company-metrics', 'faq' ) === $slugs, 'Builder order or dedicated de-duplication changed.' );
platejka_cross_runtime_assert( 'wtt' === ( $sections[1]['legacy_layout'] ?? '' ), 'Exchange legacy row was not attached to its builder section.' );
platejka_cross_runtime_assert( 'company_metrics' === ( $sections[2]['legacy_layout'] ?? '' ), 'Metrics legacy row was not attached to its builder section.' );
platejka_cross_runtime_assert( 'exchange' === ( $sections[1]['anchor'] ?? '' ), 'Builder anchor was not preserved.' );

$without_builder_rows = platejka_compose_cross_blocks(
	array( array( 'slug' => 'hero' ), array( 'slug' => 'faq' ) ),
	$rows,
	999999
);
platejka_cross_runtime_assert( array( 'hero', 'faq' ) === array_map( 'platejka_cross_block_section_slug', $without_builder_rows ), 'Legacy rows must not inject themselves outside the builder.' );

$without_source = platejka_compose_cross_blocks(
	array( array( 'slug' => 'hero' ), array( 'slug' => 'cross-exchange' ), array( 'slug' => 'faq' ) ),
	array(),
	999999
);
platejka_cross_runtime_assert( array( 'hero', 'faq' ) === array_map( 'platejka_cross_block_section_slug', $without_source ), 'Dedicated builder row without source content must be omitted.' );

ob_start();
platejka_render_section( $sections[1] );
$exchange_html = (string) ob_get_clean();

ob_start();
platejka_render_section( $sections[2] );
$metrics_html = (string) ob_get_clean();

restore_error_handler();

platejka_cross_runtime_assert( str_contains( $exchange_html, 'id="exchange"' ), 'Integrated renderer lost the builder anchor.' );
platejka_cross_runtime_assert( str_contains( $exchange_html, 'class="cross-block cb-exchange calculate"' ), 'Integrated renderer lost the source exchange structure.' );
platejka_cross_runtime_assert( str_contains( $exchange_html, 'data-action="buy"' ) && str_contains( $exchange_html, 'data-action="sell"' ), 'Integrated exchange controls are incomplete.' );
platejka_cross_runtime_assert( str_contains( $exchange_html, 'type="range"' ) && str_contains( $exchange_html, 'data-role="exchange-total"' ), 'Integrated exchange range/result structure is incomplete.' );
platejka_cross_runtime_assert( str_contains( $metrics_html, 'class="cross-block cb-metrics"' ) && str_contains( $metrics_html, 'data-cb-count="100"' ), 'Integrated metrics structure is incomplete.' );
platejka_cross_runtime_assert( array() === $warnings, 'Runtime emitted PHP warnings/notices: ' . implode( ' | ', $warnings ) );

WP_CLI::success( 'Builder-controlled cross-block runtime checks passed.' );
