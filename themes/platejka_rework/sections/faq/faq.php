<?php $root_id = $section_anchor ?: $section_instance;
$title_id = platejka_section_dom_id($section_instance, 'title');
$items = is_array($section_data['items'] ?? null) ? $section_data['items'] : array(); ?>
<section class="faq" id="<?php echo esc_attr($root_id); ?>" data-faq aria-labelledby="<?php echo esc_attr($title_id); ?>" <?php if ($items) : ?> itemscope itemtype="https://schema.org/FAQPage" <?php endif; ?>>
  <div class="container">
    <div class="faq__layout">
      <header class="faq__intro">
        <h2 id="<?php echo esc_attr($title_id); ?>"><?php echo esc_html($section_data['title'] ?? ''); ?></h2>
        <div><?php echo wp_kses_post($section_data['description'] ?? ''); ?></div><?php if (! empty($section_data['primary_link']['title'])) : ?><button class="btn-reset ui-button ui-button--small faq__cta" type="button" data-contact-modal-open data-modal-context="faq" data-modal-source="faq"><?php echo esc_html($section_data['primary_link']['title']); ?></button><?php endif; ?>
      </header>
      <div class="faq__list"><?php foreach ($items as $index => $item) : $question_id = platejka_section_dom_id($section_instance, 'faq-question-' . ($index + 1));
                                $answer_id = platejka_section_dom_id($section_instance, 'faq-answer-' . ($index + 1)); ?><div class="faq__item<?php echo 0 === $index ? ' is-open' : ''; ?>" data-faq-item itemprop="mainEntity" itemscope itemtype="https://schema.org/Question">
            <div class="h3"><button class="btn-reset faq__trigger" id="<?php echo esc_attr($question_id); ?>" type="button" data-faq-trigger aria-expanded="<?php echo 0 === $index ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr($answer_id); ?>"><span class="faq__number" aria-hidden="true"><?php echo esc_html((string) ($index + 1)); ?></span><span class="faq__question" itemprop="name"><?php echo esc_html($item['title'] ?? ''); ?></span><span class="faq__icon" aria-hidden="true"></span></button></div>
            <div class="faq__panel" id="<?php echo esc_attr($answer_id); ?>" data-faq-panel role="region" aria-labelledby="<?php echo esc_attr($question_id); ?>" <?php echo 0 === $index ? '' : ' hidden'; ?>>
              <div class="faq__answer" itemprop="acceptedAnswer" itemscope itemtype="https://schema.org/Answer">
                <div itemprop="text"><?php echo wp_kses_post($item['text'] ?? ''); ?></div>
              </div>
            </div>
          </div><?php endforeach; ?></div>
    </div>
  </div>
</section>
