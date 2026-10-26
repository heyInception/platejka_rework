<?php
/** HTML microdata and the editable source of organization details. */

function platejka_schema_defaults(): array {
	return array(
		'name' => 'Сервис «Платёжка»',
		'phone' => '+7 985 786 98 09',
		'email' => 'a@platejka.com',
		'street' => 'ул. Нобеля, дом 7, этаж 4, помещение 10',
		'city' => 'Москва',
		'postcode' => '121205',
		'country' => 'RU',
		'logo' => 'https://platejka.com/wp-content/uploads/2024/03/logo.svg',
		'image' => 'https://platejka.com/wp-content/uploads/2024/03/logo.svg',
		'social' => 'https://telegram.me/platejka_com',
		'price_range' => 'от 1%',
		'latitude' => '55.673309',
		'longitude' => '37.632546',
		'opening_days' => 'Mo-Su',
		'opens' => '09:00',
		'closes' => '18:00',
	);
}

function platejka_schema_value( string $name ): string {
	$defaults = platejka_schema_defaults();
	if ( ! array_key_exists( $name, $defaults ) ) {
		return '';
	}
	$value = function_exists( 'get_field' ) ? get_field( 'platejka_schema_' . $name, 'platejka-structured-data' ) : false;
	return is_scalar( $value ) && false !== $value ? trim( (string) $value ) : $defaults[ $name ];
}

function platejka_schema_phone_href(): string {
	return '+' . preg_replace( '/\D+/', '', platejka_schema_value( 'phone' ) );
}

function platejka_schema_opening_days_label(): string {
	$labels = array( 'Mo-Su' => 'Пн–Вс', 'Mo-Sa' => 'Пн–Сб', 'Mo-Fr' => 'Пн–Пт' );
	return $labels[ platejka_schema_value( 'opening_days' ) ] ?? '';
}

function platejka_schema_address(): string {
	return implode( ', ', array_filter( array( platejka_schema_value( 'postcode' ), platejka_schema_value( 'city' ), platejka_schema_value( 'street' ) ) ) );
}

/** Service landing pages use the default template; editorial and legal pages do not. */
function platejka_is_service_page( int $post_id ): bool {
	if ( ! $post_id || (int) get_option( 'page_on_front' ) === $post_id || (int) get_option( 'page_for_posts' ) === $post_id ) {
		return false;
	}
	if ( function_exists( 'platejka_rework_is_legal_page' ) && platejka_rework_is_legal_page( $post_id ) ) {
		return false;
	}
	$template = (string) get_page_template_slug( $post_id );
	if ( '' !== $template && 'default' !== $template ) {
		return false;
	}
	return ! in_array( get_post_field( 'post_name', $post_id ), array( 'price', 'thanks', 'site-map', 'test-cross-blocks' ), true );
}

function platejka_register_schema_options(): void {
	if ( ! function_exists( 'acf_add_options_page' ) || ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}
	acf_add_options_page( array(
		'page_title' => 'Микроразметка и контакты',
		'menu_title' => 'Микроразметка',
		'menu_slug' => 'platejka-structured-data',
		'post_id' => 'platejka-structured-data',
		'capability' => 'manage_options',
		'redirect' => false,
	) );
	$labels = array(
		'name' => 'Название организации', 'phone' => 'Телефон', 'email' => 'Электронная почта',
		'street' => 'Улица, дом и помещение', 'city' => 'Город', 'postcode' => 'Почтовый индекс',
		'country' => 'Код страны', 'logo' => 'URL логотипа', 'image' => 'URL изображения',
		'social' => 'URL соцсети', 'price_range' => 'Диапазон цен',
		'latitude' => 'Широта', 'longitude' => 'Долгота',
		'opening_days' => 'Дни работы (для посетителей)', 'opens' => 'Открытие', 'closes' => 'Закрытие',
	);
	$fields = array();
	foreach ( platejka_schema_defaults() as $name => $default ) {
		$fields[] = array(
			'key' => 'field_platejka_schema_' . $name . '_v1',
			'label' => $labels[ $name ],
			'name' => 'platejka_schema_' . $name,
			'type' => 'opening_days' === $name ? 'select' : ( in_array( $name, array( 'logo', 'image', 'social' ), true ) ? 'url' : ( 'email' === $name ? 'email' : 'text' ) ),
			'default_value' => $default,
			'choices' => 'opening_days' === $name ? array( 'Mo-Su' => 'Пн–Вс', 'Mo-Sa' => 'Пн–Сб', 'Mo-Fr' => 'Пн–Пт' ) : array(),
		);
	}
	acf_add_local_field_group( array(
		'key' => 'group_platejka_schema_v1',
		'title' => 'Данные организации для микроразметки и контактов',
		'fields' => $fields,
		'location' => array( array( array( 'param' => 'options_page', 'operator' => '==', 'value' => 'platejka-structured-data' ) ) ),
	) );
}
add_action( 'acf/init', 'platejka_register_schema_options' );

/** Keep the Yoast editor and settings intact while replacing frontend JSON-LD. */
function platejka_disable_yoast_frontend_schema( $schema ) {
	return is_admin() ? $schema : false;
}
add_filter( 'wpseo_json_ld_output', 'platejka_disable_yoast_frontend_schema' );
