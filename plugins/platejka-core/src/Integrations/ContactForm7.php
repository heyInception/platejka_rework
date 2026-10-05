<?php
/** Contact Form 7 boundary for the calculator summary. */

namespace Platejka\Core\Integrations;

/** Add one sanitized, opt-in field without changing stored CF7 forms. */
final class ContactForm7 {
	private const TRANSFER_FORM_ID = 4966;
	private const SUMMARY_FIELD = 'platejka_calculation_summary';

	/** Register public CF7 filters; harmless when CF7 is not installed. */
	public static function register(): void {
		add_filter( 'wpcf7_form_hidden_fields', array( self::class, 'hiddenFields' ) );
		add_filter( 'wpcf7_posted_data', array( self::class, 'sanitizePostedData' ) );
	}

	/** @param array<string,mixed> $fields Existing hidden fields. @return array<string,mixed> */
	public static function hiddenFields( array $fields ): array {
		return self::hiddenFieldsForForm( $fields, self::currentFormId() );
	}

	/** Pure boundary used by the live filter and integration tests. */
	public static function hiddenFieldsForForm( array $fields, int $form_id ): array {
		if ( self::transferFormId() === $form_id && ! array_key_exists( self::SUMMARY_FIELD, $fields ) ) {
			$fields[ self::SUMMARY_FIELD ] = '';
		}
		return $fields;
	}

	/** @param array<string,mixed> $posted_data Sanitized CF7 submission data. @return array<string,mixed> */
	public static function sanitizePostedData( array $posted_data ): array {
		return self::sanitizePostedDataForForm( $posted_data, self::currentFormId() );
	}

	/** Preserve unrelated submissions exactly; sanitize only the transfer field. */
	public static function sanitizePostedDataForForm( array $posted_data, int $form_id ): array {
		if ( self::transferFormId() !== $form_id || ! array_key_exists( self::SUMMARY_FIELD, $posted_data ) ) {
			return $posted_data;
		}
		$posted_data[ self::SUMMARY_FIELD ] = self::sanitizeSummary( $posted_data[ self::SUMMARY_FIELD ] );
		return $posted_data;
	}

	/** @param mixed $summary Browser-provided deterministic summary. */
	public static function sanitizeSummary( $summary ): string {
		if ( ! is_scalar( $summary ) ) { return ''; }
		$value = wp_strip_all_tags( (string) $summary, true );
		$value = preg_replace( '/\s+/u', ' ', $value );
		$value = trim( is_string( $value ) ? $value : '' );
		return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, 1000 ) : substr( $value, 0, 1000 );
	}

	private static function transferFormId(): int {
		return absint( apply_filters( 'platejka_core_cf7_transfer_form_id', self::TRANSFER_FORM_ID ) );
	}

	private static function currentFormId(): int {
		if ( ! class_exists( 'WPCF7_ContactForm' ) || ! is_callable( array( 'WPCF7_ContactForm', 'get_current' ) ) ) { return 0; }
		$form = \WPCF7_ContactForm::get_current();
		return is_object( $form ) && is_callable( array( $form, 'id' ) ) ? absint( $form->id() ) : 0;
	}
}
