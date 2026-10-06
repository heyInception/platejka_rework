<?php
$root_id  = $section_anchor ?: $section_instance;
$title_id = platejka_section_dom_id( $section_instance, 'title' );
$items    = is_array( $section_data['items'] ?? null ) ? $section_data['items'] : array();
$secondary = is_array( $section_data['secondary_items'] ?? null ) ? $section_data['secondary_items'] : array();
$icon_base = trailingslashit( get_theme_file_uri( 'sections/guarantees/img/guarantees' ) );
?>
<section class="guarantees guarantees--<?php echo esc_attr( $mode ); ?>" id="<?php echo esc_attr( $root_id ); ?>" data-guarantees aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
  <div class="container">
    <header class="guarantees__header">
      <?php if ( ! empty( $section_data['eyebrow'] ) ) : ?><span class="guarantees__badge"><?php echo esc_html( $section_data['eyebrow'] ); ?></span><?php endif; ?>
      <h2 class="guarantees__title" id="<?php echo esc_attr( $title_id ); ?>"><?php echo esc_html( $section_data['title'] ?? '' ); ?><?php if ( ! empty( $section_data['title_accent'] ) ) : ?> <span><?php echo esc_html( $section_data['title_accent'] ); ?></span><?php endif; ?></h2>
      <div class="guarantees__description"><?php echo wp_kses_post( $section_data['description'] ?? '' ); ?></div>
    </header>
    <div class="guarantees__cards">
      <?php foreach ( $items as $item ) : ?>
        <article class="guarantee-card <?php echo 'main' === $mode ? 'guarantee-card--main-featured' : 'guarantee-card_registry'; ?>">
          <div class="guarantee-card__content"><h3><?php echo esc_html( $item['title'] ?? '' ); ?></h3><div><?php echo wp_kses_post( $item['text'] ?? '' ); ?></div><?php if ( ! empty( $item['value'] ) ) : ?><strong><?php echo esc_html( $item['value'] ); ?></strong><?php endif; ?></div>
          <?php echo platejka_section_image( $item['image'] ?? 0, 'large', array( 'loading' => 'lazy', 'sizes' => '(max-width: 767px) 100vw, 592px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
          <?php if ( 'default' === $mode ) : ?>
            <div class="guarantee-card__actions">
              <button class="ui-button ui-button--medium ui-button--overlay" type="button" disabled><img class="guarantee-card__action-icon" src="<?php echo esc_url( $icon_base . 'bank.svg' ); ?>" width="20" height="20" alt="">Запись в реестре ЦБ <img src="<?php echo esc_url( $icon_base . 'chevron.svg' ); ?>" width="20" height="20" alt=""></button>
              <button class="ui-button ui-button--medium ui-button--overlay" type="button" disabled><img class="guarantee-card__action-icon" src="<?php echo esc_url( $icon_base . 'file.svg' ); ?>" width="20" height="20" alt="">Выписка из СРО <img src="<?php echo esc_url( $icon_base . 'chevron.svg' ); ?>" width="20" height="20" alt=""></button>
            </div>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
      <div class="guarantees__slider" data-guarantees-slider tabindex="0" role="region">
        <?php foreach ( $secondary as $index => $item ) : $variant = 'main' === $mode ? ( 0 === $index ? 'guarantee-card--main-secondary guarantee-card--main-risk' : 'guarantee-card--main-secondary guarantee-card--main-fintech' ) : array( 'guarantee-card_office', 'guarantee-card_clients', 'guarantee-card_risk' )[ $index % 3 ]; ?>
          <article class="guarantee-card <?php echo esc_attr( $variant ); ?>"><div class="guarantee-card__content"><h3><?php echo esc_html( $item['title'] ?? '' ); ?></h3><div><?php echo wp_kses_post( $item['text'] ?? '' ); ?></div><?php if ( ! empty( $item['value'] ) ) : ?><strong><?php echo esc_html( $item['value'] ); ?></strong><?php endif; ?></div><?php echo platejka_section_image( $item['image'] ?? 0, 'medium', array( 'loading' => 'lazy', 'sizes' => '(max-width: 767px) 100vw, 320px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></article>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
