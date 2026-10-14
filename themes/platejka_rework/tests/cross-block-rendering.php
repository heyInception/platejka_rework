<?php
/** Shared cross-block renderer and asset regression checks. */

$theme_dir   = dirname( __DIR__ );
$integration = $theme_dir . '/inc/cross-blocks.php';
$template    = $theme_dir . '/sections/cross-blocks/cross-blocks.php';
$stylesheet  = $theme_dir . '/sections/cross-blocks/cross-blocks.css';
$script      = $theme_dir . '/sections/cross-blocks/cross-blocks.js';

require_once $integration;

function platejka_cross_render_assert( bool $condition, string $message ): void {
	if ( ! $condition ) {
		WP_CLI::error( $message );
	}
}

function platejka_cross_render_fixture( string $template, string $layout, array $data, string $instance ): string {
	$legacy_layout    = $layout;
	$section_data     = $data;
	$section_instance = $instance;
	$section_anchor   = '';
	$mode             = 'default';
	ob_start();
	include $template;
	return (string) ob_get_clean();
}

platejka_cross_render_assert( is_file( $template ), 'Shared cross-block renderer is missing.' );
platejka_cross_render_assert( is_file( $stylesheet ), 'Shared cross-block stylesheet is missing.' );
platejka_cross_render_assert( ! is_file( $script ), 'Cross-block JavaScript must not exist.' );
platejka_cross_render_assert( function_exists( 'platejka_cross_block_aliases' ), 'Cross-block aliases are missing.' );

$aliases = platejka_cross_block_aliases();
$slugs   = array( 'cross-exchange', 'cross-company-metrics', 'cross-external-ratings', 'cross-key-facts', 'cross-transfer-goals', 'cross-swift-problems', 'cross-guarantees', 'cross-audience' );
platejka_cross_render_assert( $slugs === array_keys( $aliases ), 'Dedicated cross-block slugs changed.' );
foreach ( $aliases as $alias ) {
	platejka_cross_render_assert( array( 'directory' => 'cross-blocks', 'template' => 'cross-blocks' ) === $alias, 'Every dedicated slug must use the shared renderer/assets.' );
}

$exchange = platejka_cross_render_fixture( $template, 'wtt', array( 'title' => 'Курсы валют' ), 'cross-exchange-test' );
platejka_cross_render_assert( str_contains( $exchange, 'id="cross-exchange-test"' ), 'Exchange section ID is missing.' );
platejka_cross_render_assert( str_contains( $exchange, 'Курсы валют' ) && str_contains( $exchange, 'Обменять валюту' ), 'Exchange heading/CTA is missing.' );
platejka_cross_render_assert( str_contains( $exchange, 'USD' ) && str_contains( $exchange, 'EUR' ) && str_contains( $exchange, 'CNY' ), 'Server exchange values are missing.' );
foreach ( array( 'class="cross-block cb-exchange calculate"', 'data-cb-exchange-calculator', 'class="calculate__buttons"', 'type="range"', 'class="calculate__range slider-progress"', 'data-action="buy"', 'data-action="sell"', 'data-exchange-rates', 'data-role="exchange-prompt"', 'data-role="exchange-value"', 'data-role="exchange-total"', 'Сколько вы хотите купить?', 'Сумма сделки' ) as $required ) {
	platejka_cross_render_assert( str_contains( $exchange, $required ), 'Exchange source structure is missing: ' . $required );
}

$metrics = platejka_cross_render_fixture(
	$template,
	'company_metrics',
	array(
		'title' => 'Показатели компании',
		'metrics' => array(
			array( 'prefix' => '>', 'value' => 100, 'suffix' => '', 'label' => 'клиентов' ),
			array( 'prefix' => '', 'value' => 24, 'suffix' => '/7', 'label' => 'поддержка' ),
			array( 'prefix' => '', 'value' => 5, 'suffix' => ' лет', 'label' => 'на рынке' ),
		),
		'cta' => array( 'url' => '/about/', 'title' => 'О компании' ),
	),
	'cross-metrics-test'
);
platejka_cross_render_assert( str_contains( $metrics, 'id="cross-metrics-test"' ) && str_contains( $metrics, '100' ) && str_contains( $metrics, 'О компании' ), 'Company metrics content is incomplete.' );
platejka_cross_render_assert( str_contains( $metrics, 'class="cross-block cb-metrics"' ) && str_contains( $metrics, 'data-cb-count="100"' ), 'Company metrics source structure changed.' );
platejka_cross_render_assert( '' === platejka_cross_render_fixture( $template, 'company_metrics', array( 'metrics' => array( array( 'value' => 1, 'label' => 'один' ) ) ), 'bad-metrics' ), 'Incomplete metrics must be omitted.' );

$ratings = platejka_cross_render_fixture(
	$template,
	'external_ratings',
	array(
		'title' => 'Нас оценивают', 'overall_rating' => 4.9, 'reviews_count' => 125,
		'platforms' => array( array( 'name' => 'Яндекс', 'rating' => 5, 'reviews_count' => 80, 'link' => 'https://example.test/rating' ) ),
	),
	'cross-ratings-test'
);
platejka_cross_render_assert( str_contains( $ratings, 'Нас оценивают' ) && str_contains( $ratings, 'Яндекс' ) && str_contains( $ratings, '4,9' ), 'External ratings content is incomplete.' );
platejka_cross_render_assert( str_contains( $ratings, 'class="cross-block cb-ratings"' ) && str_contains( $ratings, 'class="cb-rating-card"' ), 'External ratings source structure changed.' );
$rating_warnings = array();
set_error_handler(
	static function ( int $severity, string $message ) use ( &$rating_warnings ): bool {
		if ( E_WARNING === $severity || E_NOTICE === $severity ) {
			$rating_warnings[] = $message;
			return true;
		}
		return false;
	}
);
platejka_cross_render_fixture( $template, 'external_ratings', array( 'overall_rating' => 5 ), 'cross-ratings-no-title' );
restore_error_handler();
platejka_cross_render_assert( array() === $rating_warnings, 'External ratings without a custom title emitted a PHP warning.' );

$facts = platejka_cross_render_fixture(
	$template,
	'key_facts',
	array(
		'title' => 'Условия',
		'facts' => array(
			array( 'label' => 'Срок', 'value' => '1 день' ), array( 'label' => 'Комиссия', 'value' => 'от 1%' ),
			array( 'label' => 'Валюты', 'value' => '3' ), array( 'label' => 'Поддержка', 'value' => '24/7' ),
		),
	),
	'cross-facts-test'
);
platejka_cross_render_assert( str_contains( $facts, 'id="cross-facts-test"' ) && str_contains( $facts, 'от 1%' ), 'Key facts content is incomplete.' );
platejka_cross_render_assert( str_contains( $facts, 'class="cross-block cb-facts"' ) && str_contains( $facts, 'class="cb-facts__grid"' ), 'Key facts source structure changed.' );
platejka_cross_render_assert( '' === platejka_cross_render_fixture( $template, 'key_facts', array( 'facts' => array() ), 'bad-facts' ), 'Incomplete key facts must be omitted.' );

foreach ( array( 'transfer_goals' => 'Частые цели переводов', 'swift_problems' => 'Почему SWIFT не работает', 'audience' => 'Кому подходит' ) as $layout => $title ) {
	$html = platejka_cross_render_fixture(
		$template,
		$layout,
		array( 'title' => $title, 'items' => array( array( 'title' => 'Карточка', 'text' => '<p>Описание</p>' ) ), 'cta' => array( 'url' => '/contact/', 'title' => 'Связаться' ) ),
		'cross-' . $layout . '-test'
	);
	platejka_cross_render_assert( str_contains( $html, $title ) && str_contains( $html, 'Карточка' ) && str_contains( $html, 'Связаться' ), 'Card-grid layout is incomplete: ' . $layout );
	platejka_cross_render_assert( str_contains( $html, 'cb-card-section--' . $layout ) && str_contains( $html, 'class="cb-card-grid"' ), 'Card-grid source structure changed: ' . $layout );
}

$guarantees = platejka_cross_render_fixture(
	$template,
	'guarantees',
	array(
		'title' => 'Гарантии и безопасность',
		'intro' => '<p>Отвечаем за результат перевода.</p>',
		'items' => array(
			array( 'title' => 'Минимальные риски', 'text' => '<p>Контролируем каждый этап.</p>' ),
			array( 'title' => 'Юридическая безопасность', 'text' => '<p>Закрепляем обязательства договором.</p>' ),
		),
	),
	'cross-guarantees-test'
);
platejka_cross_render_assert( str_contains( $guarantees, 'id="cross-guarantees-test"' ) && str_contains( $guarantees, 'Гарантии и безопасность' ), 'Cross-guarantees heading/ID is incomplete.' );
platejka_cross_render_assert( str_contains( $guarantees, 'class="cross-block cb-guarantees"' ) && str_contains( $guarantees, 'class="cb-guarantees__panel"' ), 'Cross-guarantees adapted source structure is missing.' );
platejka_cross_render_assert( 2 === substr_count( $guarantees, 'class="cb-guarantee"' ), 'Cross-guarantees cards changed.' );

$assets_source = (string) file_get_contents( $theme_dir . '/inc/assets.php' );
platejka_cross_render_assert( str_contains( $assets_source, 'platejka_cross_block_aliases' ), 'Asset normalization does not consume shared aliases.' );
platejka_cross_render_assert( ! str_contains( $assets_source, 'platejka-pagespeed/js/cross-blocks.js' ), 'Legacy cross-block JavaScript must never be enqueued.' );
platejka_cross_render_assert( ! str_contains( $assets_source, 'transfer-calculator' ), 'Transfer calculator assets must never be enqueued.' );

WP_CLI::success( 'Cross-block rendering checks passed.' );
