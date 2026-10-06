<?php
$root_id      = $section_anchor ?: $section_instance;
$title_id     = platejka_section_dom_id( $section_instance, 'title' );
$items        = is_array( $section_data['items'] ?? null ) ? $section_data['items'] : array();
$primary_link = is_array( $section_data['primary_link'] ?? null ) ? $section_data['primary_link'] : array();
$chevron_url  = get_theme_file_uri( 'sections/work/img/problems/ChevronRight.svg' );
?>
<section class="work" id="<?php echo esc_attr( $root_id ); ?>" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
  <div class="container">
    <div class="work__documents">
      <h2><?php echo esc_html( $section_data['eyebrow'] ?? '' ); ?></h2>
      <div class="work__description"><?php echo wp_kses_post( $section_data['description'] ?? '' ); ?></div>
      <?php foreach ( array_slice( $items, 0, 2 ) as $item ) : ?>
        <div class="work__document">
          <?php echo platejka_section_image( $item['image'] ?? 0, 'thumbnail', array( 'loading' => 'lazy', 'sizes' => '64px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
          <div class="work__wrap"><h3><?php echo esc_html( $item['title'] ?? '' ); ?></h3><div><?php echo wp_kses_post( $item['text'] ?? '' ); ?></div></div>
        </div>
      <?php endforeach; ?>
      <?php if ( ! empty( $primary_link['url'] ) ) : ?>
        <a class="ui-button work__link work__link--desktop" href="<?php echo esc_url( $primary_link['url'] ); ?>"<?php echo ! empty( $primary_link['target'] ) ? ' target="' . esc_attr( $primary_link['target'] ) . '"' : ''; ?>><?php echo esc_html( $primary_link['title'] ?? '' ); ?> <img src="<?php echo esc_url( $chevron_url ); ?>" width="20" height="20" alt=""></a>
      <?php endif; ?>
    </div>
    <div class="work__steps">
      <h2 id="<?php echo esc_attr( $title_id ); ?>"><?php echo esc_html( $section_data['title'] ?? '' ); ?></h2>
      <ol><?php foreach ( array_slice( $items, 2 ) as $item ) : ?><li><h3><?php echo esc_html( $item['title'] ?? '' ); ?></h3><div><?php echo wp_kses_post( $item['text'] ?? '' ); ?></div></li><?php endforeach; ?></ol>
    </div>
    <?php if ( ! empty( $primary_link['url'] ) ) : ?>
      <a class="ui-button work__link work__link--mobile" href="<?php echo esc_url( $primary_link['url'] ); ?>"<?php echo ! empty( $primary_link['target'] ) ? ' target="' . esc_attr( $primary_link['target'] ) . '"' : ''; ?>><?php echo esc_html( $primary_link['title'] ?? '' ); ?> <img src="<?php echo esc_url( $chevron_url ); ?>" width="20" height="20" alt=""></a>
    <?php endif; ?>
  </div>
</section>
