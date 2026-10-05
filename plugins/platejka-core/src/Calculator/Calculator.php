<?php
/**
 * Transfer calculator, independent of request and file I/O.
 *
 * @package PlatejkaCore
 */

namespace Platejka\Core\Calculator;

/** Preserve the legacy transfer commission and rounding contract. */
final class Calculator {
	/** @var array<string,float> RUB-per-unit rates. */
	private array $rates;

	/** @param array<string,float> $rates RUB-per-unit rates. */
	public function __construct( array $rates ) {
		$this->rates = $rates;
	}

	/**
	 * @param string $currency Currency code, case insensitive.
	 * @param float  $amount   Native-currency amount.
	 * @return array Calculation payload or stable error envelope.
	 */
	public function calculate( string $currency, float $amount ): array {
		$currency = strtoupper( $currency );
		if ( ! in_array( $currency, array( 'USD', 'EUR', 'CNY' ), true ) ) {
			return $this->error( 'invalid_currency', __( 'Unsupported currency.', 'platejka-core' ) );
		}
		if ( ! is_finite( $amount ) || $amount <= 0 ) {
			return $this->error( 'invalid_amount', __( 'Amount must be a finite positive number.', 'platejka-core' ) );
		}
		$rate = $this->rates[ $currency ] ?? null;
		if ( ! $this->validRate( $rate ) ) {
			return $this->error( 'rate_unavailable', __( 'Exchange rate is unavailable.', 'platejka-core' ) );
		}
		$rate = (float) $rate;
		if ( $amount >= 5000 ) {
			$native_commission = $amount * 0.01;
		} elseif ( 'CNY' === $currency ) {
			$usd_rate = $this->rates['USD'] ?? null;
			if ( ! $this->validRate( $usd_rate ) ) {
				return $this->error( 'rate_unavailable', __( 'Exchange rate is unavailable.', 'platejka-core' ) );
			}
			$native_commission = 300 * (float) $usd_rate / $rate;
		} else {
			$native_commission = 300.0;
		}
		// Round commission in RUB before rounding nativeCommission for the payload.
		$base       = $this->roundMoney( $amount * $rate );
		$commission = $this->roundMoney( $native_commission * $rate );
		$total      = $this->roundMoney( $base + $commission );
		$native     = $this->roundMoney( $native_commission );
		foreach ( array( $base, $commission, $total, $native ) as $value ) {
			if ( ! is_finite( $value ) ) {
				return $this->error( 'invalid_amount', __( 'Amount exceeds the supported calculation range.', 'platejka-core' ) );
			}
		}
		return array(
			'currency'         => $currency,
			'amount'           => $amount,
			'rate'             => $rate,
			'nativeCommission' => $native,
			'commission'       => $commission,
			'base'             => $base,
			'total'            => $total,
		);
	}

	/** @param mixed $rate Candidate rate. @return bool Whether usable. */
	private function validRate( $rate ): bool {
		return is_numeric( $rate ) && is_finite( (float) $rate ) && (float) $rate > 0;
	}

	/**
	 * Match JS Math.round((value + Number.EPSILON) * 100) / 100 for positive money.
	 * PHP round() applies different correction around decimal ties.
	 *
	 * @param float $value Money value.
	 * @return float Rounded money.
	 */
	private function roundMoney( float $value ): float {
		$scaled = ( $value + PHP_FLOAT_EPSILON ) * 100;
		$floor  = floor( $scaled );
		return ( $floor + ( $scaled - $floor >= 0.5 ? 1.0 : 0.0 ) ) / 100;
	}

	/**
	 * @param string $code    Stable error code.
	 * @param string $message Translated description.
	 * @return array Error envelope.
	 */
	private function error( string $code, string $message ): array {
		return array( 'error' => array( 'code' => $code, 'message' => $message ) );
	}
}
