<?php
$data       = platejka_section_array( $section_data ?? array() );
$instance   = isset( $section_instance ) ? (string) $section_instance : 'exhibitions';
$anchor     = isset( $section_anchor ) ? trim( (string) $section_anchor ) : '';
$title_id   = platejka_section_dom_id( $instance, 'exhibitions-title' );
$trademark  = platejka_section_array( $data['trademark'] ?? array() );
$gallery    = platejka_section_array( $data['gallery'] ?? array() );
?>
<section class="exhibitions"<?php echo '' !== $anchor ? ' id="' . esc_attr( $anchor ) . '"' : ''; ?> aria-labelledby="<?php echo esc_attr( $title_id ); ?>" data-horizontal-slider data-horizontal-slider-desktop-controls data-horizontal-slider-align-every-slide>
  <div class="container"><div class="exhibitions__layout">
    <div class="exhibitions__content">
      <div><h2 id="<?php echo esc_attr( $title_id ); ?>"><?php echo esc_html( (string) ( $data['title'] ?? '' ) ); ?></h2><h3><?php echo platejka_heading_text( $data['subtitle'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h3><?php echo wpautop( wp_kses_post( (string) ( $data['description'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
      <div class="exhibitions__trademark"><?php echo platejka_section_image( $trademark['image'] ?? 0, 'thumbnail', array( 'alt' => (string) ( $trademark['title'] ?? '' ), 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '144px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><div><strong><?php echo esc_html( (string) ( $trademark['title'] ?? '' ) ); ?></strong><span><?php echo esc_html( (string) ( $trademark['caption'] ?? '' ) ); ?></span></div></div>
    </div>
    <div class="exhibitions__slider">
      <div class="exhibitions__viewport" data-horizontal-slider-viewport tabindex="0" role="region" aria-label="<?php echo esc_attr( (string) ( $data['gallery_label'] ?? '' ) ); ?>"><div class="exhibitions__track" data-horizontal-slider-track>
        <?php foreach ( $gallery as $item ) : $item = platejka_section_array( $item ); echo platejka_section_image( $item['image'] ?? 0, 'large', array( 'alt' => (string) ( $item['alt'] ?? '' ), 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '(max-width: 767px) 100vw, 400px', 'data-horizontal-slider-slide' => '' ) ); endforeach; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
      </div></div>
      <div class="slider-controls" data-horizontal-slider-controls><button type="button" data-horizontal-slider-prev aria-label="<?php echo esc_attr( (string) ( $data['prev_label'] ?? '' ) ); ?>">‹</button><button type="button" data-horizontal-slider-next aria-label="<?php echo esc_attr( (string) ( $data['next_label'] ?? '' ) ); ?>">›</button></div>
    </div>
  </div></div>
</section>
