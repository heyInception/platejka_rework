<?php
/**
 * Read-only RUB-per-unit exchange-rate snapshots.
 *
 * @package PlatejkaCore
 */

namespace Platejka\Core\Rates;

/** Prefer the public cache, then bundled seed, then legacy safe rates. */
final class ExchangeRates {
	/** @var string Public cache path. */
	private string $cache_path;

	/** @var string Bundled seed path. */
	private string $seed_path;

	/**
	 * @param string $cache_path Public cache path.
	 * @param string $seed_path  Bundled seed path.
	 */
	public function __construct( string $cache_path, string $seed_path ) {
		$this->cache_path = $cache_path;
		$this->seed_path  = $seed_path;
	}

	/** @return array<string,float> Ordered USD/EUR/CNY rates. */
	public function getRates(): array {
		return $this->readSnapshot( $this->cache_path )
			?? $this->readSnapshot( $this->seed_path )
			?? array( 'USD' => 81.0929, 'EUR' => 96.75, 'CNY' => 12.16 );
	}

	/**
	 * Reject partial or invalid snapshots as a whole; never repair them on read.
	 *
	 * @param string $path Snapshot path.
	 * @return array<string,float>|null Valid rates or unavailable snapshot.
	 */
	private function readSnapshot( string $path ): ?array {
		if ( ! is_file( $path ) || ! is_readable( $path ) ) {
			return null;
		}
		// A cache may be replaced between the readability check and the read.
		$contents = @file_get_contents( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Treat a racing/unreadable source as unavailable.
		if ( false === $contents ) {
			return null;
		}
		$data = json_decode( $contents, true );
		if ( ! is_array( $data ) || 1 !== ( $data['schema_version'] ?? null ) || 'RUB' !== ( $data['base'] ?? null ) || ! isset( $data['rates'] ) || ! is_array( $data['rates'] ) ) {
			return null;
		}
		$rates = array();
		foreach ( array( 'USD', 'EUR', 'CNY' ) as $currency ) {
			$value = $data['rates'][ $currency ] ?? null;
			if ( ! is_numeric( $value ) || ! is_finite( (float) $value ) || (float) $value <= 0 ) {
				return null;
			}
			$rates[ $currency ] = (float) $value;
		}
		return $rates;
	}
}
