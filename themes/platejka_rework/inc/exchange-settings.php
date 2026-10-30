<?php
/** Shared exchange commissions and rate status. */

defined( 'ABSPATH' ) || exit;

function platejka_exchange_commission( string $action ): float {
	$default = 'sell' === $action ? 5.0 : 2.0;
	$name = 'sell' === $action ? 'platejka_exchange_sell_commission' : 'platejka_exchange_buy_commission';
	$value = function_exists( 'get_field' ) ? get_field( $name, 'option' ) : null;
	return is_numeric( $value ) && (float) $value >= 0 && (float) $value < 100 ? (float) $value : $default;
}

function platejka_register_exchange_settings(): void {
	if ( ! function_exists( 'acf_add_options_page' ) || ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}
	acf_add_options_page( array(
		'page_title' => __( 'Обмен валют', 'platejka_rework' ),
		'menu_title' => __( 'Обмен валют', 'platejka_rework' ),
		'menu_slug' => 'platejka-exchange-settings',
		'capability' => 'manage_options',
		'redirect' => false,
	) );
	$fields = array();
	foreach ( array( 'buy' => array( 'Покупка: комиссия, %', 2 ), 'sell' => array( 'Продажа: комиссия, %', 5 ) ) as $action => $config ) {
		$fields[] = array(
			'key' => 'field_platejka_exchange_' . $action . '_commission_v1',
			'label' => $config[0],
			'name' => 'platejka_exchange_' . $action . '_commission',
			'type' => 'number',
			'default_value' => $config[1],
			'min' => 0,
			'max' => 99.99,
			'step' => 0.01,
		);
	}
	$uploads = wp_upload_dir( null, false );
	$path = trailingslashit( $uploads['basedir'] ) . 'platejka/exchange-rates.json';
	$payload = is_readable( $path ) ? json_decode( (string) file_get_contents( $path ), true ) : null;
	$effective = is_array( $payload ) ? (string) ( $payload['effective_date'] ?? '' ) : '';
	$updated = is_array( $payload ) ? (string) ( $payload['updated_at'] ?? '' ) : '';
	$fields[] = array( 'key' => 'field_platejka_exchange_status_v1', 'label' => __( 'Состояние курсов', 'platejka_rework' ), 'name' => 'platejka_exchange_status', 'type' => 'message', 'message' => esc_html( sprintf( 'Дата курса: %s. Последнее успешное обновление: %s. Источник: %s', $effective ?: 'нет данных', $updated ?: 'нет данных', $path ) ) );
	acf_add_local_field_group( array(
		'key' => 'group_platejka_exchange_settings_v1',
		'title' => __( 'Настройки обмена', 'platejka_rework' ),
		'fields' => $fields,
		'location' => array( array( array( 'param' => 'options_page', 'operator' => '==', 'value' => 'platejka-exchange-settings' ) ) ),
	) );
}
add_action( 'acf/init', 'platejka_register_exchange_settings' );
