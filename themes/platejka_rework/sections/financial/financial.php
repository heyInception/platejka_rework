<?php
$data       = platejka_section_array( $section_data ?? array() );
$instance   = isset( $section_instance ) ? (string) $section_instance : 'financial';
$anchor     = isset( $section_anchor ) ? trim( (string) $section_anchor ) : '';
$title_id   = platejka_section_dom_id( $instance, 'financial-title' );
$banks_id   = platejka_section_dom_id( $instance, 'financial-banks-title' );
$test_id    = platejka_section_dom_id( $instance, 'financial-test-title' );
$benefits   = platejka_section_array( $data['benefits'] ?? array() );
$case       = platejka_section_array( $data['case'] ?? array() );
$banks      = platejka_section_array( $data['banks'] ?? array() );
$test       = platejka_section_array( $data['test'] ?? array() );
$link       = platejka_section_array( $data['link'] ?? array() );
$link_url   = esc_url( (string) ( $link['url'] ?? '' ) );
$test_link  = platejka_section_array( $test['link'] ?? array() );
$test_url   = esc_url( (string) ( $test_link['url'] ?? '' ) );
?>
<section class="financial"<?php echo '' !== $anchor ? ' id="' . esc_attr( $anchor ) . '"' : ''; ?> aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
  <div class="container">
    <div class="financial__layout">
      <div class="financial__intro">
        <div class="financial__intro-copy">
          <span class="financial__eyebrow"><?php echo esc_html( (string) ( $data['eyebrow'] ?? '' ) ); ?></span>
          <?php echo platejka_section_heading( platejka_section_array( $data['heading'] ?? array() ), 'financial__title', $title_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
          <?php echo wpautop( wp_kses_post( (string) ( $data['description'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
          <?php if ( '' !== $link_url ) : ?><a class="ui-button financial__button" href="<?php echo esc_attr( $link_url ); ?>"<?php echo ! empty( $link['target'] ) ? ' target="' . esc_attr( $link['target'] ) . '" rel="noopener"' : ''; ?>><?php echo esc_html( (string) ( $link['title'] ?? '' ) ); ?></a><?php endif; ?>
        </div>
        <ul class="financial__benefits list-reset">
          <?php foreach ( $benefits as $benefit ) : $benefit = platejka_section_array( $benefit ); ?><li class="financial__benefit"><?php echo platejka_section_image( $benefit['image'] ?? 0, 'thumbnail', array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '40px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( (string) ( $benefit['text'] ?? '' ) ); ?></li><?php endforeach; ?>
        </ul>
      </div>
      <div class="financial__details">
        <article class="financial__case">
          <div class="financial__labels"><span><?php echo esc_html( (string) ( $case['label'] ?? '' ) ); ?></span><strong><?php echo esc_html( (string) ( $case['badge'] ?? '' ) ); ?></strong></div>
          <div class="financial__case-copy"><h3><?php echo esc_html( (string) ( $case['title'] ?? '' ) ); ?></h3><?php echo wpautop( wp_kses_post( (string) ( $case['text'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
          <?php echo platejka_section_image( $case['image'] ?? 0, 'large', array( 'class' => 'financial__case-image', 'alt' => (string) ( $case['title'] ?? '' ), 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '(max-width: 767px) 100vw, 635px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
          <?php echo platejka_section_image( $case['logo'] ?? 0, 'thumbnail', array( 'class' => 'financial__case-logo', 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '40px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </article>
        <section class="financial__banks" aria-labelledby="<?php echo esc_attr( $banks_id ); ?>">
          <h3 id="<?php echo esc_attr( $banks_id ); ?>"><?php echo esc_html( (string) ( $data['banks_title'] ?? '' ) ); ?></h3>
          <p><?php echo esc_html( (string) ( $data['banks_description'] ?? '' ) ); ?></p>
          <ul class="list-reset"><?php foreach ( $banks as $bank ) : $bank = platejka_section_array( $bank ); ?><li class="financial__bank"><?php echo platejka_section_image( $bank['image'] ?? 0, 'thumbnail', array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '40px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( (string) ( $bank['title'] ?? '' ) ); ?></li><?php endforeach; ?></ul>
        </section>
      </div>
    </div>
    <aside class="financial__test" aria-labelledby="<?php echo esc_attr( $test_id ); ?>">
      <div class="financial__test-copy"><h3 id="<?php echo esc_attr( $test_id ); ?>"><?php echo esc_html( (string) ( $test['title'] ?? '' ) ); ?></h3><?php echo wpautop( wp_kses_post( (string) ( $test['text'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php if ( '' !== $test_url ) : ?><a class="ui-button ui-button--secondary" href="<?php echo esc_attr( $test_url ); ?>"<?php echo ! empty( $test_link['target'] ) ? ' target="' . esc_attr( $test_link['target'] ) . '" rel="noopener"' : ''; ?>><?php echo esc_html( (string) ( $test_link['title'] ?? '' ) ); ?></a><?php endif; ?></div>
      <?php echo platejka_section_image( $test['image'] ?? 0, 'large', array( 'class' => 'financial__test-image', 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '(max-width: 767px) 100vw, 448px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </aside>
  </div>
</section>
