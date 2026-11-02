<?php

namespace Platejka\Core\Reviews;

defined( 'ABSPATH' ) || exit;

/** Shared review records used by both review sections. */
final class ReviewPost {
	public const TYPE = 'platejka_review';
	public const META = array( 'source', 'source_id', 'source_url', 'author', 'rating', 'reviewed_at' );

	public static function register(): void {
		add_action( 'init', array( self::class, 'registerType' ) );
		add_action( 'add_meta_boxes', array( self::class, 'addMetaBox' ) );
		add_action( 'save_post_' . self::TYPE, array( self::class, 'saveMeta' ) );
	}

	public static function registerType(): void {
		register_post_type(
			self::TYPE,
			array(
				'labels'       => array(
					'name'          => __( 'Отзывы', 'platejka-core' ),
					'singular_name' => __( 'Отзыв', 'platejka-core' ),
					'add_new_item'  => __( 'Добавить отзыв', 'platejka-core' ),
					'edit_item'     => __( 'Редактировать отзыв', 'platejka-core' ),
				),
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => true,
				'supports'     => array( 'title', 'editor' ),
				'map_meta_cap' => true,
			)
		);
	}

	public static function addMetaBox(): void {
		add_meta_box( 'platejka-review-details', __( 'Данные отзыва', 'platejka-core' ), array( self::class, 'renderMetaBox' ), self::TYPE );
	}

	public static function renderMetaBox( \WP_Post $post ): void {
		wp_nonce_field( 'platejka_review_meta', 'platejka_review_meta_nonce' );
		$fields = array(
			'source'      => __( 'Площадка (yandex, 2gis, yell)', 'platejka-core' ),
			'source_id'   => __( 'ID на площадке', 'platejka-core' ),
			'source_url'  => __( 'Ссылка на источник', 'platejka-core' ),
			'author'      => __( 'Автор', 'platejka-core' ),
			'rating'      => __( 'Оценка от 1 до 5', 'platejka-core' ),
			'reviewed_at' => __( 'Дата отзыва (ГГГГ-ММ-ДД)', 'platejka-core' ),
		);
		foreach ( $fields as $key => $label ) {
			$value = get_post_meta( $post->ID, '_platejka_review_' . $key, true );
			printf(
				'<p><label for="platejka-review-%1$s">%2$s</label><br><input class="widefat" id="platejka-review-%1$s" name="platejka_review_meta[%1$s]" value="%3$s"></p>',
				esc_attr( $key ),
				esc_html( $label ),
				esc_attr( (string) $value )
			);
		}
	}

	public static function saveMeta( int $post_id ): void {
		if ( ! isset( $_POST['platejka_review_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['platejka_review_meta_nonce'] ) ), 'platejka_review_meta' ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$input = isset( $_POST['platejka_review_meta'] ) && is_array( $_POST['platejka_review_meta'] ) ? wp_unslash( $_POST['platejka_review_meta'] ) : array();
		foreach ( self::META as $key ) {
			$value = isset( $input[ $key ] ) && is_scalar( $input[ $key ] ) ? (string) $input[ $key ] : '';
			$value = 'source_url' === $key ? esc_url_raw( $value ) : sanitize_text_field( $value );
			if ( 'rating' === $key ) {
				$value = (string) max( 1, min( 5, (int) $value ) );
			}
			update_post_meta( $post_id, '_platejka_review_' . $key, $value );
		}
	}

	/** @return array<int,array<string,mixed>> */
	public static function cards( int $limit = 9 ): array {
		$posts = get_posts( array( 'post_type' => self::TYPE, 'post_status' => 'publish', 'numberposts' => $limit, 'orderby' => 'date', 'order' => 'DESC' ) );
		$cards = array();
		foreach ( $posts as $post ) {
			$source = (string) get_post_meta( $post->ID, '_platejka_review_source', true );
			$rating = (int) get_post_meta( $post->ID, '_platejka_review_rating', true );
			$label  = array( 'yandex' => 'Яндекс', '2gis' => '2ГИС', 'yell' => 'Yell' )[ $source ] ?? $source;
			$logos  = array( 'yandex' => 'yandex-main.png', '2gis' => '2gis-main.png', 'yell' => 'yell-main.png' );
			$date   = (string) get_post_meta( $post->ID, '_platejka_review_reviewed_at', true );
			$cards[] = array(
				'id'      => $post->ID,
				'title'   => (string) get_post_meta( $post->ID, '_platejka_review_author', true ) ?: $post->post_title,
				'text'    => wpautop( esc_html( $post->post_content ) ),
				'value'   => str_repeat( '★', max( 1, min( 5, $rating ) ) ),
				'caption' => 'Отзыв на ' . $label,
				'date'    => $date,
				'image'   => isset( $logos[ $source ] ) ? 'theme://sections/review-main/img/review/' . $logos[ $source ] : 0,
				'link'    => (string) get_post_meta( $post->ID, '_platejka_review_source_url', true ),
			);
		}
		return $cards;
	}
}
