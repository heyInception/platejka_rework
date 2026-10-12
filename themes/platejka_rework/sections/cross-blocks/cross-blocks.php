<?php
/** Shared renderer for approved cross-blocks without client-side behavior. */

defined( 'ABSPATH' ) || exit;

$layout = isset( $legacy_layout ) && is_string( $legacy_layout ) ? $legacy_layout : '';
$data   = is_array( $section_data ?? null ) ? $section_data : array();
$root   = $section_anchor ?: $section_instance;

$cta = static function ( $link, string $fallback_url = '', string $fallback_title = '' ): void {
	$link = is_array( $link ) ? $link : array();
	$url  = trim( (string) ( $link['url'] ?? $fallback_url ) );
	$text = trim( (string) ( $link['title'] ?? $fallback_title ) );
	if ( '' === $url || '' === $text ) {
		return;
	}
	$target = '_blank' === ( $link['target'] ?? '' ) || str_starts_with( $url, 'https://t.me/' ) ? '_blank' : '_self';
	?>
	<div class="cross-blocks__cta"><a class="ui-button" href="<?php echo esc_url( $url ); ?>" target="<?php echo esc_attr( $target ); ?>"<?php echo '_blank' === $target ? ' rel="noopener noreferrer"' : ''; ?>><?php echo esc_html( $text ); ?></a></div>
	<?php
};

$heading = static function ( string $title ): void {
	if ( '' !== trim( $title ) ) {
		?><h2 class="cross-blocks__title"><?php echo esc_html( $title ); ?></h2><?php
	}
};

if ( 'wtt' === $layout ) {
	$rates = platejka_cross_block_exchange_rates();
	?>
	<section class="cross-blocks cross-blocks--exchange" id="<?php echo esc_attr( $root ); ?>">
		<div class="container"><div class="cross-blocks__exchange-panel">
			<div><?php $heading( (string) ( $data['title'] ?? __( 'Актуальные курсы валют', 'platejka_rework' ) ) ); ?><p class="cross-blocks__lead"><?php echo esc_html__( 'Ориентировочные курсы к рублю. Точные условия сделки подтвердит менеджер.', 'platejka_rework' ); ?></p></div>
			<dl class="cross-blocks__rates"><?php foreach ( $rates as $currency => $rate ) : ?><div><dt><?php echo esc_html( $currency ); ?></dt><dd><?php echo esc_html( number_format_i18n( $rate, 2 ) ); ?> ₽</dd></div><?php endforeach; ?></dl>
			<?php $cta( $data['cta'] ?? array(), 'https://t.me/platejka_com', __( 'Обменять валюту', 'platejka_rework' ) ); ?>
		</div></div>
	</section>
	<?php
	return;
}

if ( 'company_metrics' === $layout ) {
	$metrics = array_values(
		array_filter(
			array_slice( is_array( $data['metrics'] ?? null ) ? $data['metrics'] : array(), 0, 5 ),
			static fn( $item ): bool => is_array( $item ) && isset( $item['value'] ) && is_numeric( $item['value'] ) && '' !== trim( (string) ( $item['label'] ?? '' ) )
		)
	);
	if ( count( $metrics ) < 3 ) {
		return;
	}
	?>
	<section class="cross-blocks cross-blocks--metrics" id="<?php echo esc_attr( $root ); ?>"><div class="container">
		<?php $heading( (string) ( $data['title'] ?? '' ) ); ?>
		<div class="cross-blocks__metrics"><?php foreach ( $metrics as $metric ) : ?><article class="cross-blocks__metric"><strong><?php echo esc_html( (string) ( $metric['prefix'] ?? '' ) . (string) $metric['value'] . (string) ( $metric['suffix'] ?? '' ) ); ?></strong><span><?php echo esc_html( $metric['label'] ); ?></span></article><?php endforeach; ?></div>
		<?php $cta( $data['cta'] ?? array() ); ?>
	</div></section>
	<?php
	return;
}

if ( 'external_ratings' === $layout ) {
	$platforms = array_values(
		array_filter(
			is_array( $data['platforms'] ?? null ) ? $data['platforms'] : array(),
			static fn( $item ): bool => is_array( $item ) && '' !== trim( (string) ( $item['name'] ?? '' ) ) && '' !== trim( (string) ( $item['link'] ?? '' ) )
		)
	);
	if ( empty( $platforms ) && empty( $data['overall_rating'] ) ) {
		return;
	}
	?>
	<section class="cross-blocks cross-blocks--ratings" id="<?php echo esc_attr( $root ); ?>"><div class="container">
		<?php $heading( (string) ( $data['title'] ?? __( 'Отзывы о нашем сервисе', 'platejka_rework' ) ) ); ?>
		<?php if ( ! empty( $data['overall_rating'] ) ) : ?><div class="cross-blocks__rating-summary"><span><?php echo esc_html__( 'Общий рейтинг', 'platejka_rework' ); ?></span><strong><?php echo esc_html( number_format_i18n( (float) $data['overall_rating'], 1 ) ); ?></strong><span class="cross-blocks__stars" aria-label="<?php echo esc_attr( sprintf( __( 'Рейтинг %s из 5', 'platejka_rework' ), number_format_i18n( (float) $data['overall_rating'], 1 ) ) ); ?>">★★★★★</span><?php if ( ! empty( $data['reviews_count'] ) ) : ?><small><?php echo esc_html( number_format_i18n( absint( $data['reviews_count'] ) ) . ' ' . __( 'отзывов', 'platejka_rework' ) ); ?></small><?php endif; ?></div><?php endif; ?>
		<div class="cross-blocks__ratings"><?php foreach ( $platforms as $platform ) : ?><a href="<?php echo esc_url( $platform['link'] ); ?>" target="_blank" rel="noopener noreferrer" class="cross-blocks__rating-card"><?php echo platejka_section_image( platejka_cross_block_media_id( $platform['logo'] ?? 0 ), 'thumbnail', array( 'loading' => 'lazy', 'alt' => (string) $platform['name'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><strong><?php echo esc_html( $platform['name'] ); ?></strong><?php if ( is_numeric( $platform['rating'] ?? null ) ) : ?><span><?php echo esc_html( number_format_i18n( (float) $platform['rating'], 1 ) ); ?> / 5</span><?php endif; ?><?php if ( ! empty( $platform['reviews_count'] ) ) : ?><small><?php echo esc_html( number_format_i18n( absint( $platform['reviews_count'] ) ) . ' ' . __( 'отзывов', 'platejka_rework' ) ); ?></small><?php endif; ?></a><?php endforeach; ?></div>
		<?php $cta( $data['cta'] ?? array() ); ?>
	</div></section>
	<?php
	return;
}

if ( 'key_facts' === $layout ) {
	$facts = array_values(
		array_filter(
			array_slice( is_array( $data['facts'] ?? null ) ? $data['facts'] : array(), 0, 4 ),
			static fn( $fact ): bool => is_array( $fact ) && '' !== trim( (string) ( $fact['label'] ?? '' ) ) && '' !== trim( (string) ( $fact['value'] ?? '' ) )
		)
	);
	if ( 4 !== count( $facts ) ) {
		return;
	}
	?>
	<section class="cross-blocks cross-blocks--facts" id="<?php echo esc_attr( $root ); ?>"><div class="container">
		<?php $heading( (string) ( $data['title'] ?? '' ) ); ?>
		<dl class="cross-blocks__facts"><?php foreach ( $facts as $fact ) : ?><div><dt><?php echo esc_html( $fact['label'] ); ?></dt><dd><?php echo esc_html( $fact['value'] ); ?></dd></div><?php endforeach; ?></dl>
		<?php $cta( $data['cta'] ?? array() ); ?>
	</div></section>
	<?php
	return;
}

if ( in_array( $layout, array( 'transfer_goals', 'swift_problems', 'audience' ), true ) ) {
	$items = array_values(
		array_filter(
			is_array( $data['items'] ?? null ) ? $data['items'] : array(),
			static fn( $item ): bool => is_array( $item ) && ( '' !== trim( (string) ( $item['title'] ?? '' ) ) || '' !== trim( wp_strip_all_tags( (string) ( $item['text'] ?? '' ) ) ) )
		)
	);
	if ( empty( $items ) ) {
		return;
	}
	$defaults = array( 'transfer_goals' => __( 'Частые цели международных переводов', 'platejka_rework' ), 'swift_problems' => __( 'Почему прямой SWIFT не работает', 'platejka_rework' ), 'audience' => __( 'Кому подходит', 'platejka_rework' ) );
	$title    = trim( (string) ( $data['title'] ?? '' ) );
	?>
	<section class="cross-blocks cross-blocks--cards cross-blocks--<?php echo esc_attr( str_replace( '_', '-', $layout ) ); ?>" id="<?php echo esc_attr( $root ); ?>"><div class="container">
		<?php $heading( '' !== $title ? $title : $defaults[ $layout ] ); ?>
		<?php if ( ! empty( $data['intro'] ) ) : ?><div class="cross-blocks__lead"><?php echo wp_kses_post( $data['intro'] ); ?></div><?php endif; ?>
		<div class="cross-blocks__cards"><?php foreach ( $items as $item ) : ?><article class="cross-blocks__card"><div class="cross-blocks__icon"><?php echo platejka_section_image( platejka_cross_block_media_id( $item['icon'] ?? 0 ), 'thumbnail', array( 'loading' => 'lazy', 'alt' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div><?php if ( ! empty( $item['title'] ) ) : ?><h3><?php echo esc_html( $item['title'] ); ?></h3><?php endif; ?><?php if ( ! empty( $item['text'] ) ) : ?><div><?php echo wp_kses_post( $item['text'] ); ?></div><?php endif; ?></article><?php endforeach; ?></div>
		<?php $cta( $data['cta'] ?? array() ); ?>
	</div></section>
	<?php
}
