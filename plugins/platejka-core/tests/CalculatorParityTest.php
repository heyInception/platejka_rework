<?php
/** Shared legacy parity and invalid-input behavior checks. */

use Platejka\Core\Calculator\Calculator;

/** Catch altered commission branches, early rounding and PHP decimal rounding. */
function platejka_test_calculator( callable $assert ): void {
	$fixture = json_decode( file_get_contents( dirname( __DIR__, 3 ) . '/tests/fixtures/calculator-parity.json' ), true, 512, JSON_THROW_ON_ERROR );
	foreach ( array( array( 'rates', 'cases' ), array( 'rounding_rates', 'rounding_cases' ) ) as $group ) {
		$calculator = new Calculator( $fixture[ $group[0] ] );
		foreach ( $fixture[ $group[1] ] as $case ) {
			$result = $calculator->calculate( $case['currency'], (float) $case['amount'] );
			foreach ( array( 'currency', 'amount', 'nativeCommission', 'commission', 'base', 'total' ) as $key ) {
				$assert( isset( $result[ $key ] ) && $result[ $key ] == $case[ $key ], 'Legacy parity ' . $case['currency'] . '/' . $case['amount'] . '/' . $key );
			}
			$assert( $fixture[ $group[0] ][ $case['currency'] ] == ( $result['rate'] ?? null ), 'Calculation must expose the selected RUB rate.' );
			$assert( false !== json_encode( $result ), 'Success must be JSON safe.' );
		}
	}
	$calculator = new Calculator( $fixture['rates'] );
	$assert( $calculator->calculate( 'USD', 5000.0 ) === $calculator->calculate( 'usd', 5000.0 ), 'Currency must normalize to uppercase.' );
}

/** Catch missing validation and unavailable-rate checks before arithmetic. */
function platejka_test_calculator_errors( callable $assert ): void {
	$calculator = new Calculator( array( 'USD' => 80, 'EUR' => 90, 'CNY' => 11 ) );
	$assert( 'invalid_currency' === ( $calculator->calculate( 'JPY', 100.0 )['error']['code'] ?? null ), 'Unsupported currency must return invalid_currency.' );
	foreach ( array( 0.0, -1.0, INF, -INF, NAN, PHP_FLOAT_MAX ) as $amount ) {
		$result = $calculator->calculate( 'USD', $amount );
		$assert( 'invalid_amount' === ( $result['error']['code'] ?? null ) && false !== json_encode( $result ), 'Invalid or overflowing amount must return JSON-safe error.' );
	}
	foreach ( array( array(), array( 'USD' => 0 ), array( 'USD' => -1 ), array( 'USD' => INF ), array( 'USD' => NAN ) ) as $rates ) {
		$assert( 'rate_unavailable' === ( ( new Calculator( $rates ) )->calculate( 'USD', 10.0 )['error']['code'] ?? null ), 'Missing/non-positive/non-finite rate must be unavailable.' );
	}
	$assert( 'rate_unavailable' === ( ( new Calculator( array( 'CNY' => 11 ) ) )->calculate( 'CNY', 100.0 )['error']['code'] ?? null ), 'CNY fixed commission requires USD rate.' );
	$assert( 55550.0 === ( ( new Calculator( array( 'CNY' => 11 ) ) )->calculate( 'CNY', 5000.0 )['total'] ?? null ), 'Percentage CNY commission must not require unused USD rate.' );
}

if ( class_exists( \PHPUnit\Framework\TestCase::class ) ) {
	final class CalculatorParityTest extends \PHPUnit\Framework\TestCase {
		public function testLegacyCommissionAndRoundingParity(): void {
			require_once dirname( __DIR__ ) . '/src/Calculator/Calculator.php';
			platejka_test_calculator( static fn ( bool $condition, string $message ) => self::assertTrue( $condition, $message ) );
		}

		public function testInvalidAmountsAndMissingRatesCannotProduceUnsafeJson(): void {
			require_once dirname( __DIR__ ) . '/src/Calculator/Calculator.php';
			platejka_test_calculator_errors( static fn ( bool $condition, string $message ) => self::assertTrue( $condition, $message ) );
		}
	}
}
