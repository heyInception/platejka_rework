<?php $root_id = $section_anchor ?: $section_instance; $items = is_array( $section_data['items'] ?? null ) ? $section_data['items'] : array(); ?>
<section class="about" id="<?php echo esc_attr( $root_id ); ?>" data-about>
  <div class="container"><div class="about__layout">
    <div class="about__media"><?php echo platejka_section_image( $section_data['image'] ?? 0, 'large', array( 'loading' => 'lazy', 'sizes' => '(max-width: 767px) 100vw, 592px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
    <div class="about__content"><div class="about__intro">
      <?php if ( ! empty( $section_data['eyebrow'] ) ) : ?><span class="about__badge"><?php echo esc_html( $section_data['eyebrow'] ); ?></span><?php endif; ?>
      <?php echo platejka_section_heading( array( 'text' => $section_data['title'] ?? '', 'accent' => $section_data['title_accent'] ?? '', 'decorative' => ! empty( $section_data['title_accent'] ) ), 'about__title' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
      <div class="about__description"><?php echo wp_kses_post( $section_data['description'] ?? '' ); ?></div>
      <?php if ( ! empty( $section_data['primary_link']['url'] ) ) : ?><a class="ui-link about__agreement" href="<?php echo esc_url( $section_data['primary_link']['url'] ); ?>"><?php echo esc_html( $section_data['primary_link']['title'] ?? '' ); ?></a><?php endif; ?>
    </div><div class="about__cards"><?php foreach ( $items as $item ) : ?><article class="about-card"><div class="about-card__image"><?php echo platejka_section_image( $item['image'] ?? 0, 'medium', array( 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div><div class="about-card__content"><h3 class="about-card__title"><?php echo esc_html( $item['title'] ?? '' ); ?></h3><div><?php echo wp_kses_post( $item['text'] ?? '' ); ?></div><?php if ( ! empty( $item['link']['url'] ) ) : ?><a class="ui-button ui-button--small about-card__link" href="<?php echo esc_url( $item['link']['url'] ); ?>"><?php echo esc_html( $item['link']['title'] ?? '' ); ?></a><?php endif; ?></div></article><?php endforeach; ?></div></div>
  </div></div>
</section>
