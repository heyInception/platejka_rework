<?php
$data        = platejka_section_array($section_data ?? array());
$instance    = isset($section_instance) ? (string) $section_instance : 'call-about';
$anchor      = isset($section_anchor) ? trim((string) $section_anchor) : '';
$title_id    = platejka_section_dom_id($instance, 'call-title');
$trust_items = platejka_section_array($data['trust_items'] ?? array());
$cards       = platejka_section_array($data['cards'] ?? array());
$icons       = array(
  'telegram' => 'img/call-about/telegram.svg',
  'whatsapp' => 'img/call-about/whatsapp.svg',
  'vk' => 'img/call-about/vk.svg',
  'telegram-media' => 'img/call-about/telegram-media.svg',
  'vc' => 'img/call-about/vc.svg',
  'rbc' => 'img/call-about/rbc.svg',
  'lenta' => 'img/call-about/lenta.svg',
);
$form_id   = platejka_contact_modal_form_id('consultation', $section_data['form'] ?? 0);
$form_html = platejka_contact_modal_form($form_id, 'call-about-section');
$form_html = platejka_cf7_remove_field_paragraph($form_html, 'your-message');
$form_html = str_replace(array('contact-modal__submit', 'contact-modal__consent', 'contact-modal__message'), array('call__submit', 'call__consent', 'call__message'), $form_html);
?>
<section class="call" <?php echo '' !== $anchor ? ' id="' . esc_attr($anchor) . '"' : ''; ?> data-call aria-labelledby="<?php echo esc_attr($title_id); ?>">
  <div class="container">
    <div class="call__panel call__row">
      <div class="call__layout">
        <div class="call__column">
          <?php echo str_replace('<h2', '<h2 data-call-title', platejka_section_heading(platejka_section_array($data['heading'] ?? array()), 'call__title', $title_id)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
          ?>
          <ul class="list-reset hero__trust call__trust" aria-label="<?php echo esc_attr((string) ($data['trust_label'] ?? '')); ?>">
            <?php foreach ($trust_items as $item) :
              $item = platejka_section_array($item);
              $variant = in_array($item['variant'] ?? '', array('experience', 'registry', 'association'), true) ? (string) $item['variant'] : 'registry';
            ?>
              <li class="hero__trust-card hero__trust-card_<?php echo esc_attr($variant); ?>" data-call-card>
                <?php if ('experience' === $variant) : ?>
                  <div class="hero__trust-years" aria-label="<?php echo esc_attr((string) ($item['value'] ?? '')); ?>">
                    <img src="img/hero__trust-years-left.svg" width="16" height="52" alt="">
                    <span aria-hidden="true"><?php echo esc_html((string) ($item['value'] ?? '')); ?></span>
                    <img src="img/hero__trust-years-right.svg" width="16" height="52" alt="">
                  </div>
                  <p class="hero__trust-caption"><?php echo esc_html((string) ($item['title'] ?? '')); ?></p>
                <?php else : ?>
                  <?php echo platejka_section_image($item['image'] ?? 0, 'medium', array('class' => 'hero__trust-decor hero__trust-decor_' . $variant, 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '161px')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
                  ?>
                  <p class="hero__trust-title">
                    <?php echo platejka_heading_text($item['title'] ?? ''); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
                    ?>
                  </p>

                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
        <div class="call__form" data-call-form><?php echo $form_html; ?></div>
      </div>
    </div>
  </div>
  <div class="container">
    <div class="call-about__cards">
      <?php foreach ($cards as $card) : $card = platejka_section_array($card); ?>
        <article class="call-about__card">
          <h3><?php echo esc_html((string) ($card['title'] ?? '')); ?></h3><?php echo wpautop(wp_kses_post((string) ($card['text'] ?? ''))); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
                                                                            ?><div class="call-about__links">
            <?php foreach (platejka_section_array($card['links'] ?? array()) as $item) :
              $item = platejka_section_array($item);
              $link = platejka_section_array($item['link'] ?? array());
              $url = esc_url((string) ($link['url'] ?? ''));
              $icon = $icons[$item['icon'] ?? ''] ?? '';
              if ('' === $url || '#' === $url || '' === $icon) {
                continue;
              }
            ?><a href="<?php echo esc_attr($url); ?>" aria-label="<?php echo esc_attr((string) ($item['label'] ?? '')); ?>" <?php echo ! empty($link['target']) ? ' target="' . esc_attr($link['target']) . '" rel="noopener"' : ''; ?>><img src="<?php echo esc_attr($icon); ?>" alt="" width="52" height="52"></a><?php endforeach; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>