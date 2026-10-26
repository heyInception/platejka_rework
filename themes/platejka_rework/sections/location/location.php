<?php
$data       = platejka_section_array( $section_data ?? array() );
$instance   = isset( $section_instance ) ? (string) $section_instance : 'location';
$title_id   = platejka_section_dom_id( $instance, 'location-title' );
$map_id     = platejka_section_dom_id( $instance, 'location-map' );
$anchor     = isset( $section_anchor ) ? trim( (string) $section_anchor ) : '';
$gallery    = platejka_section_array( $data['gallery'] ?? array() );
$phone      = preg_replace( '/[^0-9+]/', '', (string) ( $data['phone'] ?? '' ) );
$email      = sanitize_email( (string) ( $data['email'] ?? '' ) );
?>
<section class="location"<?php echo '' !== $anchor ? ' id="' . esc_attr( $anchor ) . '"' : ''; ?> aria-labelledby="<?php echo esc_attr( $title_id ); ?>" data-horizontal-slider data-horizontal-slider-min-controls="4" data-horizontal-slider-desktop-controls>
  <div class="container">
    <div class="location__layout">
      <div class="location__content">
        <header class="location__header">
          <h2 id="<?php echo esc_attr( $title_id ); ?>"><?php echo platejka_heading_text( $data['title'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
          <?php echo wpautop( wp_kses_post( (string) ( $data['description'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
          <address><?php echo esc_html( (string) ( $data['address'] ?? '' ) ); ?></address>
        </header>
        <div class="location__contacts">
          <?php if ( '' !== $phone ) : ?>
            <a class="location__contact location__contact--phone" href="tel:<?php echo esc_attr( $phone ); ?>">
              <span class="location__contact-icon" aria-hidden="true"></span>
              <span><strong><?php echo esc_html( (string) ( $data['phone'] ?? '' ) ); ?></strong><small><?php echo esc_html( (string) ( $data['phone_label'] ?? '' ) ); ?></small></span>
            </a>
          <?php endif; ?>
          <?php if ( '' !== $email ) : ?>
            <a class="location__contact location__contact--mail" href="mailto:<?php echo esc_attr( $email ); ?>">
              <span class="location__contact-icon" aria-hidden="true"></span>
              <span><strong><?php echo esc_html( $email ); ?></strong><small><?php echo esc_html( (string) ( $data['email_label'] ?? '' ) ); ?></small></span>
            </a>
          <?php endif; ?>
        </div>
      </div>
      <div class="location__map" id="<?php echo esc_attr( $map_id ); ?>" data-location-map role="region" aria-label="<?php echo esc_attr( (string) ( $data['map_label'] ?? '' ) ); ?>"></div>
      <div class="location__gallery">
        <div class="location__viewport" data-horizontal-slider-viewport tabindex="0" role="region" aria-label="<?php echo esc_attr( (string) ( $data['gallery_label'] ?? '' ) ); ?>">
          <div class="location__track" data-horizontal-slider-track>
            <?php foreach ( $gallery as $index => $item ) : $item = platejka_section_array( $item );
				$image = $item['image'] ?? 0;
				$relative = is_string( $image ) && str_starts_with( $image, 'theme://' ) ? ltrim( substr( $image, 8 ), '/' ) : '';
				$full_url = '' !== $relative && preg_match( '~^[a-zA-Z0-9_./-]+$~', $relative ) && ! str_contains( $relative, '..' ) && is_file( get_theme_file_path( $relative ) ) ? get_theme_file_uri( $relative ) : wp_get_attachment_image_url( absint( $image ), 'full' );
				if ( ! $full_url ) { continue; }
				?>
              <button class="location__thumb" type="button" data-horizontal-slider-slide data-location-gallery-thumb data-full-src="<?php echo esc_url( $full_url ); ?>" data-full-alt="<?php echo esc_attr( (string) ( $item['alt'] ?? '' ) ); ?>" aria-label="<?php echo esc_attr( sprintf( 'Открыть фото %d', $index + 1 ) ); ?>"><?php echo platejka_section_image( $image, 'medium', array( 'alt' => (string) ( $item['alt'] ?? '' ), 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '(max-width: 767px) 100vw, 224px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="slider-controls" data-horizontal-slider-controls hidden>
          <button type="button" data-horizontal-slider-prev aria-label="<?php echo esc_attr( (string) ( $data['prev_label'] ?? '' ) ); ?>">‹</button>
          <button type="button" data-horizontal-slider-next aria-label="<?php echo esc_attr( (string) ( $data['next_label'] ?? '' ) ); ?>">›</button>
        </div>
      </div>
    </div>
  </div>
  <?php if ( $gallery ) : ?>
    <dialog class="location-lightbox" data-location-lightbox aria-label="Галерея офиса">
      <button class="location-lightbox__close" type="button" data-location-lightbox-close aria-label="Закрыть">×</button>
      <button class="location-lightbox__arrow location-lightbox__arrow--prev" type="button" data-location-lightbox-prev aria-label="Предыдущее фото">‹</button>
      <figure class="location-lightbox__figure"><img data-location-lightbox-image alt="" draggable="false"><figcaption data-location-lightbox-count></figcaption></figure>
      <button class="location-lightbox__arrow location-lightbox__arrow--next" type="button" data-location-lightbox-next aria-label="Следующее фото">›</button>
    </dialog>
  <?php endif; ?>
</section>
