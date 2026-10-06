<?php $root_id = $section_anchor ?: $section_instance; $heading_id = platejka_section_dom_id( $section_instance, 'title' ); $items = is_array( $section_data['items'] ?? null ) ? $section_data['items'] : array(); ?>
<section class="compliance" id="<?php echo esc_attr( $root_id ); ?>" data-compliance aria-labelledby="<?php echo esc_attr( $heading_id ); ?>">
  <div class="container compliance__layout"><div class="compliance__intro">
    <?php if ( ! empty( $section_data['eyebrow'] ) ) : ?><p class="compliance__eyebrow"><?php echo esc_html( $section_data['eyebrow'] ); ?></p><?php endif; ?>
    <h2 id="<?php echo esc_attr( $heading_id ); ?>"><?php echo esc_html( $section_data['title'] ?? '' ); ?></h2>
    <div class="compliance__lead"><?php echo wp_kses_post( $section_data['description'] ?? '' ); ?></div>
  </div><div class="compliance__cards"><?php foreach ( $items as $item ) : ?><article class="compliance__banks-card"><?php echo platejka_section_image( $item['image'] ?? 0, 'medium', array( 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><h3><?php echo esc_html( $item['title'] ?? '' ); ?></h3><div><?php echo wp_kses_post( $item['text'] ?? '' ); ?></div><?php if ( ! empty( $item['value'] ) ) : ?><strong><?php echo esc_html( $item['value'] ); ?></strong><?php endif; ?></article><?php endforeach; ?></div></div>
</section>
