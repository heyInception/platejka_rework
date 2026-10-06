<?php
$root_id = $section_anchor ?: $section_instance;
$items = is_array( $section_data['items'] ?? null ) ? $section_data['items'] : array();
$labels = platejka_section_label_map( $section_data['labels'] ?? array() );
$about_title = trim( (string) ( $section_data['title'] ?? '' ) );
$about_accent = trim( (string) ( $section_data['title_accent'] ?? '' ) );
$about_remainder = '' !== $about_accent && str_starts_with( $about_title, $about_accent ) ? trim( mb_substr( $about_title, mb_strlen( $about_accent ) ) ) : $about_title;
?>
<section class="about" id="<?php echo esc_attr( $root_id ); ?>" data-about>
  <div class="container"><div class="about__layout">
    <div class="about__media"><?php echo platejka_section_image( $section_data['image'] ?? 0, 'large', array( 'loading' => 'lazy', 'sizes' => '(max-width: 767px) 100vw, 592px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
    <div class="about__content"><div class="about__intro">
      <?php if ( ! empty( $section_data['eyebrow'] ) ) : ?><span class="about__badge"><?php echo esc_html( $section_data['eyebrow'] ); ?></span><?php endif; ?>
      <h2 class="screen-reader-text"><?php echo esc_html( $about_title ); ?></h2><div class="about__title" aria-hidden="true"><?php if ( $about_accent ) : ?><span class="about__title-accent"><?php echo esc_html( $about_accent ); ?></span><img class="about__title-check" src="img/svg/сheck.svg" width="40" height="40" alt=""><?php endif; ?><?php echo esc_html( $about_remainder ); ?><img class="about__title-bank" src="img/svg/about-cb-rf.svg" width="40" height="40" alt=""></div>
      <div class="about__description"><?php echo wp_kses_post( $section_data['description'] ?? '' ); ?></div>
      <?php if ( ! empty( $section_data['primary_link']['url'] ) ) : ?><a class="ui-link about__agreement" href="<?php echo esc_url( $section_data['primary_link']['url'] ); ?>"><?php echo esc_html( $section_data['primary_link']['title'] ?? '' ); ?></a><?php endif; ?>
    </div><div class="about__cards"><?php foreach ( $items as $index => $item ) : ?><article class="about-card"><div class="about-card__image"><?php echo platejka_section_image( $item['image'] ?? 0, 'medium', array( 'loading' => 'lazy', 'sizes' => '120px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div><div class="about-card__content"><div class="about-card__heading"><img class="about-card__logo<?php echo 0 === $index ? ' about-card__logo_sro' : ''; ?>" src="img/svg/<?php echo 0 === $index ? 'about-SRO.svg' : 'about-cb-rf.svg'; ?>" width="25" height="20" alt=""><h3 class="about-card__title"><?php echo esc_html( $item['title'] ?? '' ); ?></h3></div><div><?php echo wp_kses_post( $item['text'] ?? '' ); ?></div><?php if ( ! empty( $item['link']['url'] ) ) : ?><a class="ui-button ui-button--small about-card__link" href="<?php echo esc_url( $item['link']['url'] ); ?>"><?php echo esc_html( $item['link']['title'] ?? '' ); ?></a><?php endif; ?></div></article><?php endforeach; ?></div></div>
  </div></div>
  <div class="container"><div class="about__cta">
    <button class="ui-button about__cta-button" type="button" data-about-dialog-open><?php echo esc_html( $labels['meeting_button'] ?? '' ); ?></button>
    <address class="about__address"><?php echo esc_html( $labels['address'] ?? '' ); ?></address>
  </div></div>
  <dialog class="about-dialog" data-about-dialog aria-label="<?php echo esc_attr( $labels['dialog_title'] ?? '' ); ?>">
    <div class="about-dialog__content"><button class="btn-reset about-dialog__close" type="button" data-about-dialog-close aria-label="<?php echo esc_attr( $labels['close'] ?? '' ); ?>">×</button>
      <h2 class="about-dialog__title"><?php echo esc_html( $labels['dialog_title'] ?? '' ); ?></h2>
      <p class="about-dialog__text"><?php echo esc_html( $labels['dialog_text'] ?? '' ); ?></p>
    </div>
  </dialog>
</section>
