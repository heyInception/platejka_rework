<?php
/** Shared real-filesystem behavior checks for PHPUnit and Studio. */

use Platejka\Core\Rates\ExchangeRates;

/** Catch reversed fallback priority, partial validation and destructive reads. */
function platejka_test_exchange_rates( callable $assert ): void {
	$cache = tempnam( sys_get_temp_dir(), 'platejka-cache-' );
	$seed  = tempnam( sys_get_temp_dir(), 'platejka-seed-' );
	$payload = array( 'schema_version' => 1, 'base' => 'RUB', 'rates' => array( 'USD' => 80, 'EUR' => 90, 'CNY' => 11 ) );
	try {
		file_put_contents( $cache, json_encode( $payload ) );
		$seed_payload = $payload;
		$seed_payload['rates'] = array( 'USD' => '79', 'EUR' => 89, 'CNY' => 10 );
		file_put_contents( $seed, json_encode( $seed_payload ) );
		$service = new ExchangeRates( $cache, $seed );
		$before = hash_file( 'sha256', $cache );
		$assert( array( 'USD' => 80.0, 'EUR' => 90.0, 'CNY' => 11.0 ) === $service->getRates(), 'Valid cache must precede seed and return ordered floats.' );
		$assert( $before === hash_file( 'sha256', $cache ), 'Reading valid cache must preserve bytes.' );
		$invalid = array( '{broken', '[]' );
		foreach ( array( array( 'schema_version', 2 ), array( 'base', 'USD' ), array( 'rates', array( 'USD' => 80, 'EUR' => 90 ) ) ) as $change ) {
			$bad = $payload;
			$bad[ $change[0] ] = $change[1];
			$invalid[] = json_encode( $bad );
		}
		foreach ( array( 0, -1, 'Infinity', '1e999', null, true, array() ) as $value ) {
			$bad = $payload;
			$bad['rates']['EUR'] = $value;
			$invalid[] = json_encode( $bad );
		}
		foreach ( $invalid as $index => $contents ) {
			file_put_contents( $cache, $contents );
			$before = hash_file( 'sha256', $cache );
			$assert( array( 'USD' => 79.0, 'EUR' => 89.0, 'CNY' => 10.0 ) === $service->getRates(), 'Invalid cache must use complete seed: ' . $index );
			$assert( $before === hash_file( 'sha256', $cache ), 'Reading invalid cache must preserve bytes: ' . $index );
		}
		file_put_contents( $seed, '{broken' );
		$assert( array( 'USD' => 81.0929, 'EUR' => 96.75, 'CNY' => 12.16 ) === $service->getRates(), 'Invalid cache and seed must use legacy safe rates.' );
		$missing = new ExchangeRates( $cache . '-missing', $seed . '-missing' );
		$assert( array( 'USD' => 81.0929, 'EUR' => 96.75, 'CNY' => 12.16 ) === $missing->getRates(), 'Missing files must use safe rates without creating files.' );
		$assert( ! file_exists( $cache . '-missing' ) && ! file_exists( $seed . '-missing' ), 'Missing snapshots must remain missing.' );
	} finally {
		unlink( $cache );
		unlink( $seed );
	}
}

if ( class_exists( \PHPUnit\Framework\TestCase::class ) ) {
	final class ExchangeRatesTest extends \PHPUnit\Framework\TestCase {
		public function testSnapshotFallbackNeverMutatesTheCache(): void {
			require_once dirname( __DIR__ ) . '/src/Rates/ExchangeRates.php';
			platejka_test_exchange_rates( static fn ( bool $condition, string $message ) => self::assertTrue( $condition, $message ) );
		}
	}
}
