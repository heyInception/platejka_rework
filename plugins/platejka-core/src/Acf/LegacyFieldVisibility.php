<?php

namespace Platejka\Core\Acf;

defined( 'ABSPATH' ) || exit;

/** Hide migrated legacy editor controls only after their data has been verified. */
final class LegacyFieldVisibility {
	public const VERSION_OPTION = 'platejka_legacy_sections_migration_version';
	public const HERO_VERSION_OPTION = 'platejka_internal_hero_migration_version';

	public static function register(): void {
		if ( 1 !== (int) get_option( self::VERSION_OPTION, 0 ) && 1 !== (int) get_option( self::HERO_VERSION_OPTION, 0 ) ) {
			return;
		}
		add_filter( 'acf/prepare_field', array( self::class, 'prepareField' ) );
	}

	/** @param array<string,mixed>|false $field @return array<string,mixed>|false */
	public static function prepareField( $field ) {
		if ( ! is_array( $field ) ) {
			return $field;
		}
		$key = (string) ( $field['key'] ?? '' );
		if ( 1 === (int) get_option( self::VERSION_OPTION, 0 ) && in_array( $key, self::legacyFieldKeys(), true ) ) {
			return false;
		}
		if ( 1 === (int) get_option( self::HERO_VERSION_OPTION, 0 ) && in_array( $key, self::legacyHeroFieldKeys(), true ) ) {
			return false;
		}
		return $field;
	}

	/** @return array<int,string> */
	public static function legacyFieldKeys(): array {
		return array(
			'field_66009b5744e4d',
			'field_66058b55c5841',
			'field_66009b6944e4e',
			'field_66c7353832cc2',
			'field_66009b8844e4f',
			'field_66009d20fec36',
			'field_66058b6dc5842',
			'field_66009d32fec37',
			'field_6790c497eeee4',
			'field_66009d41fec38',
			'field_66058ec11c486',
			'field_6605213da31ef',
			'field_66052155a31f0',
			'field_66052169a31f1',
		);
	}

	/** @return array<int,string> */
	public static function legacyHeroFieldKeys(): array {
		return array(
			'field_66009656a1c12',
			'field_66051d3d9621e',
			'field_66c7356f59226',
			'field_660097aea1c13',
			'field_6790c6855c315',
			'field_6790c6ee5c316',
			'field_6790c7105c317',
			'field_6790c7205c318',
			'field_660097eda1c14',
		);
	}
}
