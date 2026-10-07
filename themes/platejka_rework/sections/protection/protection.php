<?php
$root_id  = $section_anchor ?: $section_instance;
$title_id = platejka_section_dom_id( $section_instance, 'protection-title' );
$items    = is_array( $section_data['items'] ?? null ) ? $section_data['items'] : array();
$link     = is_array( $section_data['link'] ?? null ) ? $section_data['link'] : array();
?>
<section class="protection" id="<?php echo esc_attr( $root_id ); ?>" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
  <div class="container">
    <div class="protection__content">
      <?php if ( ! empty( $section_data['eyebrow'] ) ) : ?><span class="protection__eyebrow"><?php echo esc_html( $section_data['eyebrow'] ); ?></span><?php endif; ?>
      <h2 class="protection__title" id="<?php echo esc_attr( $title_id ); ?>"><?php echo platejka_heading_text( $section_data['title'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
      <?php if ( ! empty( $section_data['description'] ) ) : ?><p class="protection__description"><?php echo esc_html( $section_data['description'] ); ?></p><?php endif; ?>
      <?php if ( ! empty( $link['url'] ) ) : ?><a class="ui-button ui-button--small protection__link" href="<?php echo esc_url( $link['url'] ); ?>"<?php echo ! empty( $link['target'] ) ? ' target="' . esc_attr( $link['target'] ) . '" rel="noopener"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( $link['title'] ?? '' ); ?> <span aria-hidden="true">↗</span></a><?php endif; ?>
    </div>
    <div class="protection__cards">
      <?php foreach ( $items as $item ) : ?>
        <article class="protection__card">
          <?php echo platejka_section_image( $item['image'] ?? 0, 'thumbnail', array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '48px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
          <div><h3><?php echo esc_html( $item['title'] ?? '' ); ?></h3><p><?php echo esc_html( $item['text'] ?? '' ); ?></p><?php if ( ! empty( $item['detail'] ) ) : ?><span><?php echo esc_html( $item['detail'] ); ?></span><?php endif; ?></div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
