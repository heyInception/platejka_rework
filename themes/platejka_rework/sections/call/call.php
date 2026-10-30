<?php
$root_id = $section_anchor ?: $section_instance;
$title_id = platejka_section_dom_id($section_instance, 'title');
$items = is_array($section_data['items'] ?? null) ? $section_data['items'] : array();
$call_heading = platejka_section_heading(array('text' => $section_data['title'] ?? '', 'accent' => $section_data['title_accent'] ?? '', 'decorative' => ! empty($section_data['title_accent'])), 'call__title');
$call_heading = str_replace('class="call__title"', 'class="call__title" id="' . esc_attr($title_id) . '" data-call-title', $call_heading);
$form_id   = platejka_contact_modal_form_id('consultation', $section_data['form'] ?? 0);
$form_html = platejka_contact_modal_form($form_id, 'call-section');
$form_html = platejka_cf7_remove_field_paragraph($form_html, 'your-message');
$form_html = str_replace(array('contact-modal__submit', 'contact-modal__consent', 'contact-modal__message'), array('call__submit', 'call__consent', 'call__message'), $form_html);
?>
<section class="call" id="<?php echo esc_attr($root_id); ?>" data-call aria-label="<?php echo esc_attr(wp_strip_all_tags((string) ($section_data['title'] ?? ''))); ?>">
  <div class="container">
    <div class="call__panel call__row">
      <div class="call__layout">
        <div class="call__column"><?php echo $call_heading; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
                                  ?>
          <?php if ($section_data['description']) : ?><div class="call__description"><?php echo wp_kses_post($section_data['description'] ?? ''); ?></div><?php endif; ?>
          <ul class="list-reset hero__trust call__trust"><?php foreach ($items as $index => $item) : $trust_variant = array('experience', 'registry', 'association')[$index % 3]; ?>
              <li class="hero__trust-card hero__trust-card_<?php echo esc_attr($trust_variant); ?>" data-call-card><?php if (0 === $index) : ?>
                  <div class="hero__trust-years">
                    <img src="img/hero__trust-years-left.svg" width="22" height="72" alt="<?php echo esc_html($item['value'] ?? ''); ?> - левая">
                      <span><?php echo esc_html($item['value'] ?? ''); ?></span>
                      <img src="img/hero__trust-years-right.svg" width="22" height="72" alt="<?php echo esc_html($item['value'] ?? ''); ?> - правая">
                  </div>
                <?php else : ?>
                  <?php echo platejka_section_image($item['image'] ?? 0, 'medium', array('class' => 'hero__trust-decor hero__trust-decor_' . $trust_variant, 'loading' => 'lazy', 'sizes' => '161px')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
                  ?><?php endif; ?><p class="hero__trust-title"><?php echo esc_html($item['title'] ?? ''); ?></p>
                  <?php if ($item['text']) : ?><div class="hero__trust-caption"><?php echo wp_kses_post($item['text'] ?? ''); ?></div><?php endif; ?>
              </li><?php endforeach; ?>
          </ul>
        </div>
        <div class="call__form" data-call-form><?php echo $form_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
                                                ?></div>
      </div>
    </div>
  </div>
</section>