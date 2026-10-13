<?php
/** Runtime regression for builder-controlled cross-block inheritance. */

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

$globals = array(
	'cross_exchange' => array( 'title' => 'Обмен из общих настроек' ),
	'cross_swift_problems' => array(
		'title' => 'Глобальный SWIFT',
		'intro' => '<p>Общее вступление</p>',
		'items' => array( array( 'title' => 'Глобальная карточка', 'text' => '<p>Глобальный текст</p>' ) ),
	),
);
$global_filter = static function ( $data, string $content_slug ) use ( $globals ) {
	return $globals[ str_replace( '-', '_', $content_slug ) ] ?? $data;
};
add_filter( 'platejka_section_global_data', $global_filter, 10, 2 );

$swift = array(
	'slug' => 'cross-swift-problems',
	'row' => array(
		'enabled' => 1,
		'overrides' => array(
			'cross_swift_problems' => array(
				'title' => 'Локальный SWIFT',
				'intro' => '',
				'items' => array( array( 'title' => 'Локальная карточка', 'text' => '<p>Локальный текст</p>' ) ),
			),
		),
	),
	'instance' => 'section-test-swift',
);
$resolved = platejka_resolve_section_data( $swift, 999999 );
platejka_cross_runtime_assert( 'Локальный SWIFT' === ( $resolved['title'] ?? '' ), 'Non-empty scalar override must replace the shared value.' );
platejka_cross_runtime_assert( '<p>Общее вступление</p>' === ( $resolved['intro'] ?? '' ), 'Empty scalar override must inherit the shared value.' );
platejka_cross_runtime_assert( array( 'Локальная карточка' ) === array_column( $resolved['items'] ?? array(), 'title' ), 'A non-empty local repeater must replace the shared collection wholesale.' );

ob_start();
platejka_render_section( $swift );
$swift_html = (string) ob_get_clean();

$exchange = array(
	'slug' => 'cross-exchange',
	'row' => array( 'enabled' => 1, 'overrides' => array() ),
	'instance' => 'section-test-exchange',
	'anchor' => 'exchange',
);
ob_start();
platejka_render_section( $exchange );
$exchange_html = (string) ob_get_clean();

remove_filter( 'platejka_section_global_data', $global_filter, 10 );
restore_error_handler();

platejka_cross_runtime_assert( str_contains( $swift_html, 'Локальный SWIFT' ) && str_contains( $swift_html, 'Локальная карточка' ), 'Dedicated section did not render resolved builder/default data.' );
platejka_cross_runtime_assert( ! str_contains( $swift_html, 'Глобальная карточка' ), 'Renderer merged a global repeater into a non-empty local repeater.' );
platejka_cross_runtime_assert( str_contains( $exchange_html, 'id="exchange"' ), 'Integrated renderer lost the builder anchor.' );
platejka_cross_runtime_assert( str_contains( $exchange_html, 'Обмен из общих настроек' ), 'Exchange did not inherit shared defaults.' );
platejka_cross_runtime_assert( str_contains( $exchange_html, 'data-action="buy"' ) && str_contains( $exchange_html, 'data-action="sell"' ), 'Integrated exchange buy/sell controls are incomplete.' );
platejka_cross_runtime_assert( str_contains( $exchange_html, 'type="range"' ) && str_contains( $exchange_html, 'data-role="exchange-total"' ), 'Integrated exchange range/result structure is incomplete.' );
platejka_cross_runtime_assert( array() === $warnings, 'Runtime emitted PHP warnings/notices: ' . implode( ' | ', $warnings ) );

WP_CLI::success( 'Builder-controlled cross-block runtime checks passed.' );
