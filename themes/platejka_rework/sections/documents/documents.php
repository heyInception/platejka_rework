<?php
$root_id  = $section_anchor ?: $section_instance;
$title_id = platejka_section_dom_id( $section_instance, 'title' );
$items    = is_array( $section_data['items'] ?? null ) ? $section_data['items'] : array();
$icon_uri = get_theme_file_uri( 'sections/documents/img/documents/' );
?>
<section class="documents documents--<?php echo esc_attr( $mode ); ?>" id="<?php echo esc_attr( $root_id ); ?>" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
  <div class="container">
    <div class="documents__panel">
      <h2 class="documents__title" id="<?php echo esc_attr( $title_id ); ?>"><?php echo esc_html( $section_data['title'] ?? '' ); ?></h2>
      <div class="documents__intro"><?php echo wp_kses_post( $section_data['description'] ?? '' ); ?></div>
      <div class="documents__cards" data-documents-slider tabindex="0" role="region">
        <?php foreach ( $items as $item ) :
          $file_id   = absint( $item['file'] ?? 0 );
          $file_url  = platejka_section_file_url( $file_id );
          $details   = $file_url ? platejka_document_file_details( $file_id ) : array( 'label' => '', 'icon' => '', 'size' => '' );
          $file_text = implode( ', ', array_filter( array( $details['label'], $details['size'] ) ) );
          $link      = is_array( $item['link'] ?? null ) ? $item['link'] : array();
          ?>
          <article class="document-card document-card--<?php echo esc_attr( $mode ); ?>">
            <?php echo platejka_document_preview( $item['image'] ?? 0, $file_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <div class="document-card__content">
              <div class="h3"><?php echo esc_html( $item['title'] ?? '' ); ?></div>
              <div><?php echo wp_kses_post( $item['text'] ?? '' ); ?></div>
              <?php if ( '' !== $file_text ) : ?>
                <span class="document-card__file">
                  <?php if ( '' !== $details['icon'] ) : ?><img src="<?php echo esc_url( $icon_uri . $details['icon'] ); ?>" alt="" aria-hidden="true" width="16" height="16"><?php endif; ?>
                  <?php echo esc_html( $file_text ); ?>
                </span>
              <?php endif; ?>
            </div>
            <a class="document-card__download" href="<?php echo esc_url( $file_url ?: ( $link['url'] ?? '#' ) ); ?>" <?php echo $file_url ? 'download' : ''; ?>>
              <?php echo esc_html( $link['title'] ?? __( 'Скачать', 'platejka_rework' ) ); ?>
              <img src="<?php echo esc_url( $icon_uri . 'download.svg' ); ?>" alt="" aria-hidden="true" width="20" height="20">
            </a>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
