<?php $root_id = $section_anchor ?: $section_instance;
$title_id = platejka_section_dom_id($section_instance, 'title');
$items = is_array($section_data['items'] ?? null) ? $section_data['items'] : array();
$labels = platejka_section_label_map($section_data['labels'] ?? array()); ?>
<section class="problems" id="<?php echo esc_attr($root_id); ?>" aria-labelledby="<?php echo esc_attr($title_id); ?>">
  <div class="container">
    <h2 id="<?php echo esc_attr($title_id); ?>"><?php echo esc_html($section_data['title'] ?? ''); ?></h2>
    <div class="problems__intro"><?php echo wp_kses_post($section_data['description'] ?? ''); ?></div>
    <div class="problems__viewport" data-problems-slider tabindex="0" role="region" aria-roledescription="slider" aria-label="<?php echo esc_attr($labels['region'] ?? ''); ?>">
      <div class="problems__track"><?php foreach ($items as $item) : ?><article class="problems__card">
            <div class="problems__card-content">
              <div class="h3"><?php echo esc_html($item['title'] ?? ''); ?></div>
              <div><?php echo wp_kses_post($item['text'] ?? ''); ?></div><?php if (! empty($item['value'])) : ?><strong><?php echo esc_html($item['value']); ?></strong><?php endif; ?>
            </div><?php echo platejka_section_image($item['image'] ?? 0, 'large', array('loading' => 'lazy', 'sizes' => '(max-width: 767px) 100vw, 360px')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
                  ?>
          </article><?php endforeach; ?></div>
    </div>
    <div class="problems__cta">
      <div>
        <div class="h3"><?php echo esc_html($labels['cta_title'] ?? ''); ?></div>
        <p><?php echo esc_html($labels['cta_text'] ?? ''); ?></p>
        <ul>
          <li><?php echo esc_html($labels['benefit_1'] ?? ''); ?></li>
          <li><?php echo esc_html($labels['benefit_2'] ?? ''); ?></li>
          <li><?php echo esc_html($labels['benefit_3'] ?? ''); ?></li>
        </ul>
      </div>
      <div class="problems__actions"><button class="ui-button problems__submit" type="button" data-contact-modal-open data-modal-context="problems" data-modal-source="problems-cta"><?php echo esc_html($labels['submit'] ?? ''); ?></button>
        <div>
          <a href="mailto:<?php echo esc_attr( platejka_schema_value( 'email' ) ); ?>"><?php echo esc_html($labels['write'] ?? ''); ?></a>
          <a href="tel:<?php echo esc_html( platejka_schema_value( 'phone' ) ); ?>"><?php echo esc_html($labels['call'] ?? ''); ?></a>
        </div>
      </div>
    </div>
  </div>
</section>  
