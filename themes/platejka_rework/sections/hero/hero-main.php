<?php
$root_id = $section_anchor ?: $section_instance;
$items = is_array( $section_data['items'] ?? null ) ? $section_data['items'] : array();
$trust = is_array( $section_data['secondary_items'] ?? null ) ? $section_data['secondary_items'] : array();
?>
<section class="hero" id="<?php echo esc_attr( $root_id ); ?>" data-hero>
  <div class="container"><div class="hero__layout"><div class="hero__content">
    <div class="hero__wrap hero__wrap_top" data-hero-panel>
      <?php echo platejka_section_image( $section_data['image'] ?? 0, 'large', array( 'class' => 'hero__image', 'loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '(max-width: 767px) 100vw, 553px', 'data-hero-image' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
      <h1 class="hero__title" data-hero-copy><span class="hero__title-primary"><?php echo esc_html( $section_data['title'] ?? '' ); ?></span><?php if ( ! empty( $section_data['title_accent'] ) ) : ?><span class="hero__title-secondary"><?php echo esc_html( $section_data['title_accent'] ); ?></span><?php endif; ?></h1>
      <div class="hero__subtitle" data-hero-copy><?php echo wp_kses_post( $section_data['description'] ?? '' ); ?></div>
      <ul class="list-reset hero__items" data-hero-copy><?php foreach ( $items as $item ) : ?><li class="hero__item"><span class="hero__item-title"><?php echo esc_html( $item['title'] ?? '' ); ?></span><span class="hero__item-content"><?php echo wp_kses_post( $item['text'] ?? '' ); ?></span></li><?php endforeach; ?></ul>
    </div>
    <ul class="list-reset hero__trust" data-hero-cards><?php foreach ( $trust as $item ) : ?><li class="hero__trust-card"><div class="hero__trust-years"><?php echo platejka_section_image( $item['image'] ?? 0, 'medium', array( 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><strong><?php echo esc_html( $item['value'] ?? '' ); ?></strong></div><p class="hero__trust-title"><?php echo esc_html( $item['title'] ?? '' ); ?></p><div class="hero__trust-caption"><?php echo wp_kses_post( $item['text'] ?? '' ); ?></div><?php if ( ! empty( $item['link']['url'] ) ) : ?><a class="ui-link hero__trust-link" href="<?php echo esc_url( $item['link']['url'] ); ?>"><?php echo esc_html( $item['link']['title'] ?? '' ); ?></a><?php endif; ?></li><?php endforeach; ?></ul>
  </div></div></div>
</section>
