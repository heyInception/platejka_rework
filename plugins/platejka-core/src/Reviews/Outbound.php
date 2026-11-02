<?php

namespace Platejka\Core\Reviews;

defined( 'ABSPATH' ) || exit;

/** Resolve review and platform destinations only when a visitor clicks. */
final class Outbound {
	private const PLATFORMS = array(
		'yandex' => 'https://yandex.ru/profile/217714971749?lang=ru',
		'2gis'   => 'https://go.2gis.com/qUUel',
		'yell'   => 'https://www.yell.ru/moscow/com/internet-buxgalteriya-platezhka_14472104/',
	);
	private const LEGACY_YANDEX = array(
		'Sasha Mekhel' => 'saVoogO6rixyfpoAtrbEU0lNE8Bm_A1',
		'Ева Джозеф'  => 'BTskxENaOz1c_BO_gSrK402XZwvis_Nj',
		'Вера Вера'   => 'g6dEaV__hlwRY0NOJVGqeOzC8-qtqv',
	);

	public static function register(): void {
		add_action( 'wp_ajax_platejka_review_out', array( self::class, 'redirect' ) );
		add_action( 'wp_ajax_nopriv_platejka_review_out', array( self::class, 'redirect' ) );
	}

	public static function platformUrl( string $source ): string {
		return self::PLATFORMS[ $source ] ?? '';
	}

	public static function reviewUrl( int $post_id ): string {
		if ( ReviewPost::TYPE !== get_post_type( $post_id ) || 'publish' !== get_post_status( $post_id ) ) {
			return '';
		}
		$source = (string) get_post_meta( $post_id, '_platejka_review_source', true );
		$url    = (string) get_post_meta( $post_id, '_platejka_review_source_url', true );
		$hosts  = array( 'yandex' => array( 'yandex.ru', 'www.yandex.ru' ), '2gis' => array( '2gis.ru', 'go.2gis.com' ), 'yell' => array( 'yell.ru', 'www.yell.ru' ) );
		$host   = wp_parse_url( $url, PHP_URL_HOST );
		return 'https' === wp_parse_url( $url, PHP_URL_SCHEME ) && in_array( $host, $hosts[ $source ] ?? array(), true ) ? $url : '';
	}

	public static function legacyReviewUrl( string $author ): string {
		$review_id = self::LEGACY_YANDEX[ $author ] ?? '';
		return $review_id ? 'https://yandex.ru/maps/org/217714971749/reviews?' . http_build_query( array( 'reviews[publicId]' => $review_id ) ) : '';
	}

	public static function redirect(): void {
		$kind = isset( $_GET['kind'] ) ? sanitize_key( wp_unslash( $_GET['kind'] ) ) : '';
		if ( 'platform' === $kind ) {
			$source = isset( $_GET['source'] ) ? sanitize_key( wp_unslash( $_GET['source'] ) ) : '';
			$url = self::platformUrl( $source );
		} elseif ( 'review' === $kind ) {
			$url = self::reviewUrl( isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0 );
		} elseif ( 'legacy' === $kind ) {
			$author = isset( $_GET['author'] ) ? sanitize_text_field( wp_unslash( $_GET['author'] ) ) : '';
			$url = self::legacyReviewUrl( $author );
		} else {
			$url = '';
		}
		if ( '' === $url ) {
			status_header( 404 );
			wp_die( esc_html__( 'Отзыв не найден.', 'platejka-core' ) );
		}
		header( 'X-Robots-Tag: noindex, nofollow', true );
		nocache_headers();
		wp_redirect( $url, 302, 'Platejka' );
		exit;
	}
}
