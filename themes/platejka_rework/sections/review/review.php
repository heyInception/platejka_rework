<?php
$root_id        = $section_anchor ?: $section_instance;
$title_id       = platejka_section_dom_id( $section_instance, 'review-title' );
$video_tab_id   = platejka_section_dom_id( $section_instance, 'review-video-tab' );
$text_tab_id    = platejka_section_dom_id( $section_instance, 'review-text-tab' );
$video_panel_id = platejka_section_dom_id( $section_instance, 'review-video-panel' );
$text_panel_id  = platejka_section_dom_id( $section_instance, 'review-text-panel' );
$dialog_id      = platejka_section_dom_id( $section_instance, 'review-dialog-title' );
$labels         = platejka_section_label_map( $section_data['labels'] ?? array() );
$video_items    = is_array( $section_data['video_items'] ?? null ) ? $section_data['video_items'] : array();
$items          = is_array( $section_data['items'] ?? null ) ? $section_data['items'] : array();
?>
<section class="review" id="<?php echo esc_attr( $root_id ); ?>" aria-labelledby="<?php echo esc_attr( $title_id ); ?>" data-review>
  <div class="container">
    <header class="review__intro">
      <?php if ( ! empty( $section_data['eyebrow'] ) ) : ?><span class="review__eyebrow"><?php echo esc_html( $section_data['eyebrow'] ); ?></span><?php endif; ?>
      <h2 id="<?php echo esc_attr( $title_id ); ?>"><?php echo platejka_heading_text( $section_data['title'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
      <?php echo wp_kses_post( $section_data['description'] ?? '' ); ?>
    </header>

    <div class="review__body">
      <div class="review__tabs" role="tablist" aria-label="<?php echo esc_attr( $labels['tabs_label'] ?? '' ); ?>" data-review-tabs>
        <button class="review__tab" type="button" role="tab" id="<?php echo esc_attr( $video_tab_id ); ?>" aria-controls="<?php echo esc_attr( $video_panel_id ); ?>" aria-selected="true"><?php echo esc_html( $labels['video_tab'] ?? '' ); ?></button>
        <button class="review__tab" type="button" role="tab" id="<?php echo esc_attr( $text_tab_id ); ?>" aria-controls="<?php echo esc_attr( $text_panel_id ); ?>" aria-selected="false" tabindex="-1"><?php echo esc_html( $labels['text_tab'] ?? '' ); ?></button>
      </div>

      <div id="<?php echo esc_attr( $video_panel_id ); ?>" role="tabpanel" aria-labelledby="<?php echo esc_attr( $video_tab_id ); ?>" data-review-video-panel>
        <div class="review__slider" data-horizontal-slider><div class="review__viewport" data-horizontal-slider-viewport tabindex="0" role="region" aria-label="<?php echo esc_attr( $labels['video_region'] ?? '' ); ?>"><div class="review__track" data-horizontal-slider-track>
          <?php foreach ( $video_items as $item ) :
			$poster_url = is_numeric( $item['image'] ?? null ) ? wp_get_attachment_image_url( (int) $item['image'], 'large' ) : '';
			?>
            <article class="review__video-card" data-horizontal-slider-slide<?php echo $poster_url ? ' style="--review-image:url(\'' . esc_url( $poster_url ) . '\')"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
              <?php echo platejka_section_image( $item['image'] ?? 0, 'large', array( 'class' => 'review__video-card-image', 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '(max-width: 1024px) 280px, 254px', 'style' => 'position:absolute;inset:0;width:100%;height:100%;object-fit:cover' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
              <div><h3><?php echo esc_html( $item['title'] ?? '' ); ?></h3><p><?php echo platejka_heading_text( $item['text'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p><button type="button" class="review__watch" data-review-video="<?php echo esc_url( platejka_section_file_url( $item['file'] ?? 0 ) ); ?>"><?php echo esc_html( $labels['watch'] ?? '' ); ?></button></div>
            </article>
          <?php endforeach; ?>
        </div></div><div class="review__controls" data-horizontal-slider-controls><button type="button" class="review__arrow review__arrow--prev" data-horizontal-slider-prev aria-label="<?php echo esc_attr( $labels['prev_video'] ?? '' ); ?>">‹</button><button type="button" class="review__arrow review__arrow--next" data-horizontal-slider-next aria-label="<?php echo esc_attr( $labels['next_video'] ?? '' ); ?>">›</button></div></div>
      </div>

      <div id="<?php echo esc_attr( $text_panel_id ); ?>" role="tabpanel" aria-labelledby="<?php echo esc_attr( $text_tab_id ); ?>" data-review-text-panel hidden>
        <div class="review__slider" data-horizontal-slider><div class="review__viewport" data-horizontal-slider-viewport tabindex="0" role="region" aria-label="<?php echo esc_attr( $labels['text_region'] ?? '' ); ?>"><div class="review__track review__text-track" data-horizontal-slider-track>
          <?php foreach ( $items as $item ) :
			$review_date = ! empty( $item['date'] ) ? strtotime( (string) $item['date'] ) : false;
			?>
            <article class="review__text-card" data-horizontal-slider-slide><div class="review__stars" aria-label="<?php echo esc_attr( $item['value'] ?? '' ); ?>"><?php echo esc_html( $item['value'] ?? '' ); ?></div><blockquote><?php echo wp_kses_post( $item['text'] ?? '' ); ?></blockquote><span class="review__source"><?php echo platejka_section_image( $item['image'] ?? 0, 'thumbnail', array( 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '20px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( $item['caption'] ?? '' ); ?> <span aria-hidden="true">›</span></span><footer><strong><?php echo esc_html( $item['title'] ?? '' ); ?></strong><?php if ( $review_date ) : ?><time datetime="<?php echo esc_attr( gmdate( 'Y-m-d', $review_date ) ); ?>"><?php echo esc_html( wp_date( 'd.m.Y', $review_date ) ); ?></time><?php endif; ?></footer></article>
          <?php endforeach; ?>
        </div></div><div class="review__controls" data-horizontal-slider-controls hidden><button type="button" class="review__arrow review__arrow--prev" data-horizontal-slider-prev aria-label="<?php echo esc_attr( $labels['prev_text'] ?? '' ); ?>">‹</button><button type="button" class="review__arrow review__arrow--next" data-horizontal-slider-next aria-label="<?php echo esc_attr( $labels['next_text'] ?? '' ); ?>">›</button></div></div>
      </div>
    </div>
  </div>

  <dialog class="review__dialog" aria-labelledby="<?php echo esc_attr( $dialog_id ); ?>" data-review-dialog><button type="button" class="review__close" data-review-close aria-label="<?php echo esc_attr( $labels['close'] ?? '' ); ?>">×</button><div class="review__dialog-card"><video preload="none" playsinline></video><div class="review__loading" data-review-loading role="status" aria-label="<?php echo esc_attr( $labels['loading'] ?? '' ); ?>" hidden></div><div class="review__dialog-caption"><h2 id="<?php echo esc_attr( $dialog_id ); ?>"></h2><p class="review__dialog-role" data-review-dialog-role></p><p class="review__video-empty" data-review-empty><?php echo esc_html( $labels['empty'] ?? '' ); ?></p><p class="review__video-error" data-review-error hidden><?php echo esc_html( $labels['error'] ?? '' ); ?></p></div></div></dialog>
</section>
