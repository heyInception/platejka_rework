<?php

namespace Platejka\Core\Acf;

defined( 'ABSPATH' ) || exit;

/** Hide migrated legacy editor controls only after their data has been verified. */
final class LegacyFieldVisibility {
	public const VERSION_OPTION = 'platejka_legacy_sections_migration_version';

	public static function register(): void {
		if ( 1 !== (int) get_option( self::VERSION_OPTION, 0 ) ) {
			return;
		}
		add_filter( 'acf/prepare_field', array( self::class, 'prepareField' ) );
	}

	/** @param array<string,mixed>|false $field @return array<string,mixed>|false */
	public static function prepareField( $field ) {
		if ( ! is_array( $field ) ) {
			return $field;
		}
		return in_array( (string) ( $field['key'] ?? '' ), self::legacyFieldKeys(), true ) ? false : $field;
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
}
