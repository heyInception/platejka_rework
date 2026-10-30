<?php
/** Read-only checks for exchange settings and ACF registration. */

if ( ! function_exists( 'acf_get_setting' ) || ! function_exists( 'acf_is_pro' ) || ! acf_is_pro() || ! function_exists( 'acf_add_options_page' ) ) {
	WP_CLI::error( 'ACF Pro options API is unavailable.' );
}
if ( ! function_exists( 'platejka_exchange_commission' ) || ! function_exists( 'platejka_register_exchange_settings' ) ) {
	WP_CLI::error( 'Exchange settings functions are unavailable.' );
}
$buy = platejka_exchange_commission( 'buy' );
$sell = platejka_exchange_commission( 'sell' );
if ( $buy < 0 || $buy >= 100 || $sell < 0 || $sell >= 100 ) {
	WP_CLI::error( 'Exchange commissions are outside the allowed range.' );
}
$group = acf_get_local_field_group( 'group_platejka_exchange_settings_v1' );
if ( ! $group ) {
	WP_CLI::error( 'Exchange options field group is not registered.' );
}
WP_CLI::success( sprintf( 'ACF %s Pro; commissions buy=%s%%, sell=%s%%; options group registered.', acf_get_setting( 'version' ), $buy, $sell ) );
