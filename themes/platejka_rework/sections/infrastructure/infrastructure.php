<?php
$data            = platejka_section_array( $section_data ?? array() );
$instance        = isset( $section_instance ) ? (string) $section_instance : 'infrastructure';
$title_id        = platejka_section_dom_id( $instance, 'infrastructure-title' );
$anchor          = isset( $section_anchor ) ? trim( (string) $section_anchor ) : '';
$specializations = platejka_section_array( $data['specializations'] ?? array() );
$facts           = platejka_section_array( $data['facts'] ?? array() );
$entities        = platejka_section_array( $data['entities'] ?? array() );
$entity_icons    = array( 'indonesia' => 'img/infrastructure/flag-indonesia.svg' );
?>
<section class="infrastructure"<?php echo '' !== $anchor ? ' id="' . esc_attr( $anchor ) . '"' : ''; ?> aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
  <div class="container">
    <header class="infrastructure__header">
      <?php
      echo platejka_section_heading( platejka_section_array( $data['heading'] ?? array() ), '', $title_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
      ?>
      <?php echo wpautop( wp_kses_post( (string) ( $data['description'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
      <div class="infrastructure__specializations">
        <span><?php echo esc_html( (string) ( $data['specializations_label'] ?? '' ) ); ?></span>
        <?php foreach ( $specializations as $item ) : $item = platejka_section_array( $item ); ?>
          <strong><?php echo platejka_section_image( $item['image'] ?? 0, 'thumbnail', array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '40px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( (string) ( $item['title'] ?? '' ) ); ?></strong>
        <?php endforeach; ?>
      </div>
    </header>
    <ul class="infrastructure__facts list-reset" aria-label="Инфраструктура в цифрах">
      <?php foreach ( $facts as $fact ) : $fact = platejka_section_array( $fact ); ?><li class="infrastructure__fact"><strong><?php echo esc_html( (string) ( $fact['value'] ?? '' ) ); ?></strong><span><?php echo esc_html( (string) ( $fact['text'] ?? '' ) ); ?></span></li><?php endforeach; ?>
    </ul>
    <div class="infrastructure__network">
      <?php echo platejka_section_image( $data['network_image'] ?? 0, 'large', array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '(max-width: 767px) 100vw, 1014px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
      <ul class="infrastructure__entities list-reset" aria-label="<?php echo esc_attr( (string) ( $data['entities_label'] ?? '' ) ); ?>">
        <?php foreach ( $entities as $entity ) :
          $entity = platejka_section_array( $entity );
          $state = in_array( $entity['state'] ?? '', array( 'active', 'thailand' ), true ) ? (string) $entity['state'] : '';
          $icon = $entity_icons[ $entity['icon'] ?? '' ] ?? '';
        ?>
          <li class="infrastructure__entity<?php echo '' !== $state ? ' infrastructure__entity--' . esc_attr( $state ) : ''; ?>">
            <?php if ( '' !== $icon ) : ?><img src="<?php echo esc_attr( $icon ); ?>" alt="" width="40" height="40"><?php else : echo platejka_section_image( $entity['image'] ?? 0, 'thumbnail', array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '40px' ) ); endif; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <span class="infrastructure__entity-name"><?php echo esc_html( (string) ( $entity['title'] ?? '' ) ); ?></span><small><?php echo esc_html( (string) ( $entity['caption'] ?? '' ) ); ?></small>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</section>
