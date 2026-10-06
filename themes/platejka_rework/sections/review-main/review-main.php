<?php
$root_id = $section_anchor ?: $section_instance; $title_id = platejka_section_dom_id( $section_instance, 'title' );
$panel_id = platejka_section_dom_id( $section_instance, 'reviews-panel' ); $tab_id = platejka_section_dom_id( $section_instance, 'reviews-tab' );
$items = is_array( $section_data['items'] ?? null ) ? $section_data['items'] : array();
?>
<section class="review-main" id="<?php echo esc_attr( $root_id ); ?>" data-review aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
  <div class="container"><header class="review-main__header"><div class="review-main__heading">
    <?php if ( ! empty( $section_data['eyebrow'] ) ) : ?><p class="review-main__eyebrow"><?php echo esc_html( $section_data['eyebrow'] ); ?></p><?php endif; ?>
    <h2 id="<?php echo esc_attr( $title_id ); ?>"><?php echo esc_html( $section_data['title'] ?? '' ); ?></h2><div class="review-main__lead"><?php echo wp_kses_post( $section_data['description'] ?? '' ); ?></div>
  </div><div class="review-main__tabs" role="tablist" data-review-tabs><button type="button" role="tab" id="<?php echo esc_attr( $tab_id ); ?>" aria-controls="<?php echo esc_attr( $panel_id ); ?>" aria-selected="true"><?php echo esc_html__( 'Отзывы', 'platejka_rework' ); ?></button></div></header>
  <div id="<?php echo esc_attr( $panel_id ); ?>" role="tabpanel" aria-labelledby="<?php echo esc_attr( $tab_id ); ?>"><div class="review-main__slider" data-horizontal-slider><div class="review-main__viewport" data-horizontal-slider-viewport tabindex="0"><div class="review-main__track" data-horizontal-slider-track>
    <?php foreach ( $items as $item ) : ?><article class="review-main__text-card" data-horizontal-slider-slide><?php echo platejka_section_image( $item['image'] ?? 0, 'medium', array( 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><h3><?php echo esc_html( $item['title'] ?? '' ); ?></h3><blockquote><?php echo wp_kses_post( $item['text'] ?? '' ); ?></blockquote><footer><strong><?php echo esc_html( $item['value'] ?? '' ); ?></strong><span><?php echo esc_html( $item['caption'] ?? '' ); ?></span></footer></article><?php endforeach; ?>
  </div></div></div></div></div>
</section>
