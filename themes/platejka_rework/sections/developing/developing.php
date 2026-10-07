<?php
$data       = platejka_section_array( $section_data ?? array() );
$instance   = isset( $section_instance ) ? (string) $section_instance : 'developing';
$anchor     = isset( $section_anchor ) ? trim( (string) $section_anchor ) : '';
$title_id   = platejka_section_dom_id( $instance, 'developing-title' );
$items      = platejka_section_array( $data['items'] ?? array() );
?>
<section class="developing"<?php echo '' !== $anchor ? ' id="' . esc_attr( $anchor ) . '"' : ''; ?> aria-labelledby="<?php echo esc_attr( $title_id ); ?>" data-horizontal-slider data-horizontal-slider-align-every-slide>
  <div class="container">
    <?php echo platejka_section_heading( platejka_section_array( $data['heading'] ?? array() ), '', $title_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <div class="developing__viewport" data-horizontal-slider-viewport tabindex="0" role="region" aria-label="<?php echo esc_attr( (string) ( $data['region_label'] ?? '' ) ); ?>">
      <div class="developing__track" data-horizontal-slider-track>
        <?php foreach ( $items as $item ) : $item = platejka_section_array( $item ); ?>
          <article class="developing__card" data-horizontal-slider-slide><div class="developing__copy"><div><strong><?php echo esc_html( (string) ( $item['year'] ?? '' ) ); ?></strong><h3><?php echo platejka_heading_text( $item['title'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h3></div><?php echo wpautop( wp_kses_post( (string) ( $item['text'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div><?php echo platejka_section_image( $item['image'] ?? 0, 'large', array( 'class' => 'developing__image', 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '(max-width: 767px) 100vw, 429px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></article>
        <?php endforeach; ?>
      </div>
    </div>
    <nav class="developing__navigation slider-controls" aria-label="Этапы развития" data-horizontal-slider-controls>
      <button class="developing__arrow developing__arrow--previous" type="button" data-horizontal-slider-prev aria-label="<?php echo esc_attr( (string) ( $data['prev_label'] ?? '' ) ); ?>"><svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M12.9067 3.6921C13.1688 3.91674 13.1992 4.3113 12.9745 4.57338L8.32317 9.99997L12.9745 15.4266C13.1992 15.6886 13.1688 16.0832 12.9067 16.3078C12.6447 16.5325 12.2501 16.5021 12.0255 16.24L7.02546 10.4067C6.82485 10.1727 6.82485 9.82728 7.02546 9.59323L12.0255 3.75989C12.2501 3.49781 12.6447 3.46746 12.9067 3.6921Z" fill="#25252A" /></svg></button>
      <div class="developing__years"><?php foreach ( $items as $index => $item ) : $item = platejka_section_array( $item ); ?><button type="button" data-horizontal-slider-go-to="<?php echo esc_attr( (string) $index ); ?>"><?php echo esc_html( (string) ( $item['year'] ?? '' ) ); ?></button><?php endforeach; ?></div>
      <button class="developing__arrow developing__arrow--next" type="button" data-horizontal-slider-next aria-label="<?php echo esc_attr( (string) ( $data['next_label'] ?? '' ) ); ?>"><svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M7.09327 3.6921C7.35535 3.46746 7.74991 3.49781 7.97455 3.75989L12.9745 9.59323C13.1752 9.82728 13.1752 10.1727 12.9745 10.4067L7.97455 16.24C7.74991 16.5021 7.35535 16.5325 7.09327 16.3078C6.83119 16.0832 6.80084 15.6886 7.02548 15.4266L11.6768 9.99997L7.02548 4.57338C6.80084 4.3113 6.83119 3.91674 7.09327 3.6921Z" fill="#25252A" /></svg></button>
    </nav>
  </div>
</section>
