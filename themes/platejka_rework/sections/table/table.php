<?php
$root_id  = $section_anchor ?: $section_instance;
$title_id = platejka_section_dom_id( $section_instance, 'title' );
$items    = is_array( $section_data['items'] ?? null ) ? $section_data['items'] : array();
?>
<section class="comparison" id="<?php echo esc_attr( $root_id ); ?>" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
  <div class="container">
    <h2 id="<?php echo esc_attr( $title_id ); ?>"><?php echo esc_html( $section_data['title'] ?? '' ); ?></h2>
    <div class="comparison__description"><?php echo wp_kses_post( $section_data['description'] ?? '' ); ?></div>
    <div class="comparison__scroll" tabindex="0" role="region">
      <table aria-label="<?php echo esc_attr__( 'Сравнение способов международных платежей', 'platejka_rework' ); ?>">
        <thead><tr><td></td><th scope="col" class="comparison__brand"><img src="img/footerLogo.svg" width="182" height="52" alt="Платёжка"></th><th scope="col"><?php echo esc_html__( 'Финансовые и логистические фирмы', 'platejka_rework' ); ?></th><th scope="col"><?php echo esc_html__( 'Банки', 'platejka_rework' ); ?></th></tr></thead>
        <tbody>
          <?php foreach ( $items as $item ) : $label = (string) ( $item['title'] ?? '' ); ?>
            <tr><th scope="row"><?php echo esc_html( $label ); ?></th><?php foreach ( array( array( 'value', 'text', 'check.png' ), array( 'partner_value', 'partner_text', 'danger.png' ), array( 'bank_value', 'bank_text', 'close-square.png' ) ) as $column ) : ?><td data-label="<?php echo esc_attr( $label ); ?>"><span class="comparison__line"><img src="img/table/<?php echo esc_attr( $column[2] ); ?>" width="20" height="20" alt=""><?php echo esc_html( $item[ $column[0] ] ?? '' ); ?></span><?php echo wp_kses_post( $item[ $column[1] ] ?? '' ); ?></td><?php endforeach; ?></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
