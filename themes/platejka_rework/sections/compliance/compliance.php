<?php
$root_id    = $section_anchor ?: $section_instance;
$heading_id = platejka_section_dom_id($section_instance, 'title');
$items      = is_array($section_data['items'] ?? null) ? $section_data['items'] : array();
$benefits   = array_slice($items, 0, 3);
$cards      = array_slice($items, 3);
$labels     = platejka_section_label_map($section_data['labels'] ?? array());
$banks      = is_array($section_data['banks'] ?? null) ? $section_data['banks'] : array();
$heading    = trim((string) ($section_data['title'] ?? ''));
$split_at   = mb_strpos($heading, ',');
$heading_first = false === $split_at ? $heading : mb_substr($heading, 0, $split_at + 1);
$heading_second = false === $split_at ? '' : trim(mb_substr($heading, $split_at + 1));
?>
<section class="compliance" id="<?php echo esc_attr($root_id); ?>" data-compliance aria-labelledby="<?php echo esc_attr($heading_id); ?>">
  <div class="container compliance__layout">
    <div class="compliance__intro">
      <?php if (! empty($section_data['eyebrow'])) : ?><p class="compliance__eyebrow"><?php echo esc_html($section_data['eyebrow']); ?></p><?php endif; ?>
      <h2 id="<?php echo esc_attr($heading_id); ?>"><?php echo platejka_heading_text($heading_first); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
                                                    ?><?php if ('' !== $heading_second) : ?><span><?php echo platejka_heading_text($heading_second); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
                                                                                                  ?></span><?php endif; ?></h2>
      <div class="compliance__lead"><?php echo wp_kses_post($section_data['description'] ?? ''); ?></div>
      <button class="ui-button compliance__button" type="button" data-contact-modal-open data-modal-context="compliance_contact" data-modal-source="compliance-contact"><?php echo esc_html($labels['contact'] ?? ''); ?> <span aria-hidden="true">›</span></button>
      <ul class="list-reset compliance__benefits"><?php foreach ($benefits as $item) : ?><li><?php echo platejka_section_image($item['image'] ?? 0, 'thumbnail', array('loading' => 'lazy', 'sizes' => '40px')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
                                                                                              ?><span><?php echo esc_html($item['title'] ?? ''); ?></span><?php if (! empty($item['text'])) : ?><div><?php echo wp_kses_post($item['text']); ?></div><?php endif; ?></li><?php endforeach; ?></ul>
    </div>
    <div class="compliance__cards"><?php foreach ($cards as $index => $item) : ?><?php if (0 === $index) : ?><article class="compliance__banks-card">
        <div class="h3"><?php echo esc_html($item['title'] ?? ''); ?></div>
        <div><?php echo wp_kses_post($item['text'] ?? ''); ?></div>
        <ul class="list-reset compliance__banks" aria-label="<?php echo esc_attr($item['title'] ?? ''); ?>"><?php foreach ($banks as $bank) : ?><li><?php echo platejka_section_image($bank['image'] ?? 0, 'thumbnail', array('loading' => 'lazy', 'sizes' => '48px', 'alt' => $bank['title'] ?? '')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
                                                                                                                                                    ?><span><?php echo esc_html($bank['title'] ?? ''); ?></span></li><?php endforeach; ?></ul>
      </article><?php else : ?><article class="compliance__test">
        <div class="compliance__test-content">
          <div class="h3"><?php echo esc_html($item['title'] ?? ''); ?></div>
          <div><?php echo wp_kses_post($item['text'] ?? ''); ?></div>
          <button class="compliance__test-button" type="button" data-contact-modal-open data-modal-context="compliance_test" data-modal-source="compliance-test-payment"><?php echo esc_html($labels['test_payment'] ?? ''); ?> <span aria-hidden="true">›</span></button>
        </div>
        <?php echo platejka_section_image($item['image'] ?? 0, 'large', array('loading' => 'lazy', 'sizes' => '(max-width: 767px) 100vw, 310px')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
        ?>
      </article><?php endif; ?><?php endforeach; ?></div>
  </div>
</section>