<?php $root_id = $section_anchor ?: $section_instance; $items = is_array( $section_data['items'] ?? null ) ? $section_data['items'] : array(); $show_label = (string) ( $section_data['show_label'] ?? '' ); $hide_label = (string) ( $section_data['hide_label'] ?? '' ); ?>
<section class="seo" id="<?php echo esc_attr( $root_id ); ?>" data-seo><div class="container"><div class="seo__wrap"><div class="seo__group" data-seo-content>
        <?php if (is_page(2054)) : ?>
          <h2><?php the_field('zagolovok_services'); ?></h2>
        <?php else : ?>
          <h2><?php the_title() ?></h2>
        <?php endif; ?>
        <?php the_content(); ?>
  <?php if ( ! empty( $section_data['description'] ) ) : ?><div class="seo__description"><?php echo wp_kses_post( $section_data['description'] ); ?></div><?php endif; ?>
  <div class="seo__cards"><?php foreach ( $items as $index => $item ) : ?><article class="seo__card"><span class="seo__number"><?php echo esc_html( ( $item['value'] ?? '' ) ?: (string) ( $index + 1 ) ); ?></span><h3><?php echo esc_html( $item['title'] ?? '' ); ?></h3><div><?php echo wp_kses_post( $item['text'] ?? '' ); ?></div></article><?php endforeach; ?></div>
</div><button class="btn-reset seo__toggle" type="button" data-seo-toggle data-seo-show-label="<?php echo esc_attr( $show_label ); ?>" data-seo-hide-label="<?php echo esc_attr( $hide_label ); ?>" aria-expanded="false" hidden><?php echo esc_html( $show_label ); ?></button></div></div></section>
