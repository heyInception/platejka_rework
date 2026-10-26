<?php
/** Source-compatible renderer for dedicated cross-blocks. */

defined( 'ABSPATH' ) || exit;

$layout   = isset( $legacy_layout ) && is_string( $legacy_layout ) ? $legacy_layout : '';
$row      = is_array( $section_data ?? null ) ? $section_data : array();
$instance = sanitize_html_class( $section_anchor ?: $section_instance );

if ( 'wtt' === $layout ) {
	$rates = platejka_cross_block_exchange_rates();
	?>
	<section id="<?php echo esc_attr( $instance ); ?>" class="cross-block cb-exchange calculate" data-cb-exchange-calculator data-exchange-rates="<?php echo esc_attr( wp_json_encode( $rates ) ); ?>">
		<div class="container">
			<div class="calculate__row">
				<div class="calculate__wrap">
					<div class="calculate__title"><h2><?php echo esc_html( $row['title'] ?? 'Рассчитайте стоимость международного перевода' ); ?></h2></div>
					<div class="calculate__rates"><?php foreach ( $rates as $currency => $rate ) : ?><div class="calculate__rate"><strong><?php echo esc_html( $currency ); ?></strong> <span><?php echo esc_html( number_format_i18n( $rate, 2 ) ); ?> ₽</span></div><?php endforeach; ?></div>
				</div>
				<div class="calculate__wrap calculate__wrap_width">
					<div class="calculate__buttons"><button type="button" class="btn-reset calculate__button calculate__button_active is-active" data-action="buy">Купить</button><button type="button" class="btn-reset calculate__button" data-action="sell">Продать</button></div>
					<div class="calculate__buttons"><?php foreach ( $rates as $currency => $rate ) : ?><button type="button" class="btn-reset calculate__button<?php echo 'USD' === $currency ? ' calculate__button_active is-active' : ''; ?>" data-currency="<?php echo esc_attr( $currency ); ?>"><?php echo esc_html( $currency ); ?></button><?php endforeach; ?></div>
				</div>
				<div class="calculate__column calculate__column_active">
					<div class="calculate__wrapper"><div class="calculate__head" data-role="exchange-prompt">Сколько вы хотите купить?</div><div class="calculate__value" data-role="exchange-value">1 $</div><input type="range" class="calculate__range slider-progress" data-role="exchange-range" step="1" min="1" max="100000" value="1"></div>
					<div class="calculate__wrapper"><div class="calculate__head">Сумма сделки</div><div class="calculate__value calculate__value_summ" data-role="exchange-total">0 ₽</div><a class="btn-reset calculate__button calculate__button_tg" href="https://t.me/platejka_com" target="_blank" rel="noopener noreferrer">Обменять валюту</a></div>
				</div>
			</div>
		</div>
	</section>
	<?php
	return;
}

if ( 'company_metrics' === $layout ) {
	$metrics = array_values( array_filter( array_slice( is_array( $row['metrics'] ?? null ) ? $row['metrics'] : array(), 0, 5 ), static fn( $item ) => is_array( $item ) && isset( $item['value'] ) && is_numeric( $item['value'] ) && ! empty( $item['label'] ) ) );
	if ( count( $metrics ) < 3 ) {
		return;
	}
	?>
	<section id="<?php echo esc_attr( $instance ); ?>" class="cross-block cb-metrics" data-cb-metrics><div class="container">
		<?php if ( ! empty( $row['title'] ) ) : ?><h2 class="cb-heading"><?php echo esc_html( $row['title'] ); ?></h2><?php endif; ?>
		<div class="cb-metrics__grid"><?php foreach ( $metrics as $metric ) : ?><div class="cb-metric"><div class="cb-metric__value"><span><?php echo esc_html( $metric['prefix'] ?? '' ); ?></span><span data-cb-count="<?php echo esc_attr( (float) $metric['value'] ); ?>"><?php echo esc_html( $metric['value'] ); ?></span><span><?php echo esc_html( $metric['suffix'] ?? '' ); ?></span></div><div class="cb-metric__label"><?php echo esc_html( $metric['label'] ); ?></div></div><?php endforeach; ?></div>
		<?php echo platejka_cross_block_cta( is_array( $row['cta'] ?? null ) ? $row['cta'] : null ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div></section>
	<?php
	return;
}

if ( 'external_ratings' === $layout ) {
	$platforms = array_values( array_filter( is_array( $row['platforms'] ?? null ) ? $row['platforms'] : array(), static fn( $item ) => is_array( $item ) && ! empty( $item['name'] ) && ! empty( $item['link'] ) ) );
	if ( empty( $platforms ) && empty( $row['overall_rating'] ) ) {
		return;
	}
	?>
	<section id="<?php echo esc_attr( $instance ); ?>" class="cross-block cb-ratings"><div class="container">
		<h2 class="cb-heading"><?php echo esc_html( ( $row['title'] ?? '' ) ?: 'Отзывы о нашем сервисе' ); ?></h2>
		<?php if ( ! empty( $row['overall_rating'] ) ) : ?><div class="cb-ratings__summary"><span>Общий рейтинг</span><strong><?php echo esc_html( number_format_i18n( (float) $row['overall_rating'], 1 ) ); ?></strong><span class="cb-ratings__stars">★★★★★</span><?php if ( ! empty( $row['reviews_count'] ) ) : ?><span><?php echo esc_html( number_format_i18n( absint( $row['reviews_count'] ) ) ); ?> отзывов</span><?php endif; ?></div><?php endif; ?>
		<?php if ( ! empty( $platforms ) ) : ?><div class="cb-ratings__grid"><?php foreach ( $platforms as $platform ) : ?><a class="cb-rating-card" href="<?php echo esc_url( $platform['link'] ); ?>" target="_blank" rel="noopener noreferrer"><?php $icon = platejka_cross_block_icon( $platform['logo'] ?? 0, (string) $platform['name'] ); echo '' !== $icon ? $icon : platejka_cross_block_fallback_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><strong><?php echo esc_html( $platform['name'] ); ?></strong><?php if ( isset( $platform['rating'] ) && '' !== $platform['rating'] ) : ?><span><?php echo esc_html( number_format_i18n( (float) $platform['rating'], 1 ) ); ?> / 5</span><?php endif; ?><?php if ( ! empty( $platform['reviews_count'] ) ) : ?><small><?php echo esc_html( number_format_i18n( absint( $platform['reviews_count'] ) ) ); ?> отзывов</small><?php endif; ?></a><?php endforeach; ?></div><?php endif; ?>
		<?php echo platejka_cross_block_cta( is_array( $row['cta'] ?? null ) ? $row['cta'] : null ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div></section>
	<?php
	return;
}

if ( 'key_facts' === $layout ) {
	$facts = array_values( array_filter( array_slice( is_array( $row['facts'] ?? null ) ? $row['facts'] : array(), 0, 4 ), static fn( $fact ) => is_array( $fact ) && ! empty( $fact['label'] ) && ! empty( $fact['value'] ) ) );
	if ( 4 !== count( $facts ) ) {
		return;
	}
	?>
	<section id="<?php echo esc_attr( $instance ); ?>" class="cross-block cb-facts"><div class="container">
		<?php if ( ! empty( $row['title'] ) ) : ?><h2 class="cb-heading"><?php echo esc_html( $row['title'] ); ?></h2><?php endif; ?>
		<div class="cb-facts__grid"><?php foreach ( $facts as $fact ) : ?><div class="cb-fact"><span><?php echo esc_html( $fact['label'] ); ?></span><strong><?php echo esc_html( $fact['value'] ); ?></strong></div><?php endforeach; ?></div>
		<?php echo platejka_cross_block_cta( is_array( $row['cta'] ?? null ) ? $row['cta'] : null ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div></section>
	<?php
	return;
}

if ( 'guarantees' === $layout ) {
	$items = array_values( array_filter( is_array( $row['items'] ?? null ) ? $row['items'] : array(), static fn( $item ) => is_array( $item ) && ( ! empty( $item['title'] ) || ! empty( $item['text'] ) ) ) );
	if ( empty( $items ) ) {
		return;
	}
	?>
	<section id="<?php echo esc_attr( $instance ); ?>" class="cross-block cb-guarantees"><div class="container">
		<div class="cb-guarantees__panel">
			<div class="cb-guarantees__header">
				<div class="cb-guarantees__shield"><?php echo platejka_cross_block_fallback_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
				<div class="cb-guarantees__heading">
					<?php if ( ! empty( $row['title'] ) ) : ?><h2 class="cb-heading"><?php echo esc_html( $row['title'] ); ?></h2><?php endif; ?>
					<?php if ( ! empty( $row['intro'] ) ) : ?><div class="cb-intro"><?php echo wp_kses_post( $row['intro'] ); ?></div><?php endif; ?>
				</div>
			</div>
			<div class="cb-guarantees__items">
				<?php foreach ( $items as $item ) : ?>
					<article class="cb-guarantee">
						<div class="cb-guarantee__icon"><?php $icon = platejka_cross_block_icon( $item['icon'] ?? 0, (string) ( $item['title'] ?? '' ) ); echo '' !== $icon ? $icon : platejka_cross_block_fallback_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
						<?php if ( ! empty( $item['title'] ) ) : ?><div class="h3"><?php echo esc_html( $item['title'] ); ?></div><?php endif; ?>
						<?php if ( ! empty( $item['text'] ) ) : ?><div class="cb-guarantee__text"><?php echo wp_kses_post( $item['text'] ); ?></div><?php endif; ?>
					</article>
				<?php endforeach; ?>
			</div>
			<?php echo platejka_cross_block_cta( is_array( $row['cta'] ?? null ) ? $row['cta'] : null ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	</div></section>
	<?php
	return;
}

if ( in_array( $layout, array( 'transfer_goals', 'swift_problems', 'audience' ), true ) ) {
	$items = array_values( array_filter( is_array( $row['items'] ?? null ) ? $row['items'] : array(), static fn( $item ) => is_array( $item ) && ( ! empty( $item['title'] ) || ! empty( $item['text'] ) ) ) );
	if ( empty( $items ) ) {
		return;
	}
	$defaults = array( 'transfer_goals' => 'Частые цели международных переводов', 'swift_problems' => 'Почему прямой SWIFT не работает', 'audience' => 'Кому подходит' );
	$title    = trim( (string) ( $row['title'] ?? '' ) ) ?: $defaults[ $layout ];
	?>
	<section id="<?php echo esc_attr( $instance ); ?>" class="cross-block cb-card-section cb-card-section--<?php echo esc_attr( $layout ); ?>"><div class="container">
		<h2 class="cb-heading"><?php echo esc_html( $title ); ?></h2>
		<?php if ( ! empty( $row['intro'] ) ) : ?><div class="cb-intro"><?php echo wp_kses_post( $row['intro'] ); ?></div><?php endif; ?>
		<div class="cb-card-grid"><?php foreach ( $items as $item ) : ?><article class="cb-card"><div class="cb-card__icon"><?php $icon = platejka_cross_block_icon( $item['icon'] ?? 0, (string) ( $item['title'] ?? '' ) ); echo '' !== $icon ? $icon : platejka_cross_block_fallback_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div><?php if ( ! empty( $item['title'] ) ) : ?><div class="cb-card__title"><?php echo esc_html( $item['title'] ); ?></div><?php endif; ?><?php if ( ! empty( $item['text'] ) ) : ?><div class="cb-card__text"><?php echo wp_kses_post( $item['text'] ); ?></div><?php endif; ?></article><?php endforeach; ?></div>
		<?php echo platejka_cross_block_cta( is_array( $row['cta'] ?? null ) ? $row['cta'] : null ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div></section>
	<?php
}
