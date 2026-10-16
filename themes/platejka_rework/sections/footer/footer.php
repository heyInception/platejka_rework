<footer class="footer">
  <div class="container">
    <div class="footer__panel">
      <div class="footer__main">
        <address class="footer__contacts">
          <a class="footer__contact footer__contact_phone" href="#">
            <span class="footer__contact-icon" aria-hidden="true"></span>
            <span><strong>8 800 533-88-19</strong><small>По всем вопросам</small></span>
          </a>
          <a class="footer__contact footer__contact_mail" href="#">
            <span class="footer__contact-icon" aria-hidden="true"></span>
            <span><strong>a@platejka.com</strong><small>Поддержка</small></span>
          </a>
          <p class="footer__address">127204, Россия, Москва, Долгопрудненское шоссе, 3</p>
          <div class="footer__h2">Реквизиты и ИНН</div>
          <p class="footer__details">

            Расчётный счёт 40702810700000033434<br>
            ПАО «РосДорБанк»<br>
            Корреспондентский счёт 30101810945250000666<br>
            БИК 044525666
          </p>
          <a class="footer__registry" href="#">
            <img src="img/svg/cb.svg" width="20" height="20" alt="">
            <span class="footer__registry-label">Внесён в реестр ЦБ</span>
            <img src="img/svg/right-icon.svg" width="20" height="20" alt="">
          </a>
          <div id="consentBox" class="open">
            <div id="consentContent">
              <p>
                Мы используем cookies для оптимизации работы сайта и повышения его эффективности. Подробности в нашей <a href="<?php echo esc_url( get_permalink( 5170 ) ); ?>">политике использования cookie-файлов</a>.
              </p>
              <div class="buttons">
                <button class="consentButton btn-reset ui-button">
                  Хорошо
                </button>
              </div>
            </div>
          </div>
        </address>

        <?php
        $footer_menus = array_filter(
          array_map(
            static fn ( string $field ): array => (array) get_field( $field, 'option' ),
            array( 'menyu_1', 'menyu_2', 'menyu_3' )
          ),
          static fn ( array $menu ): bool => ! empty( $menu['zagolovok'] ) && ! empty( $menu['ssylki'] )
        );
        ?>
        <?php if ( $footer_menus ) : ?>
          <div class="footer__navigation">
            <?php foreach ( $footer_menus as $index => $menu ) : ?>
              <?php $heading_id = 'footer-menu-' . ( $index + 1 ) . '-title'; ?>
              <nav class="footer__nav" aria-labelledby="<?php echo esc_attr( $heading_id ); ?>">
                <h2 id="<?php echo esc_attr( $heading_id ); ?>"><?php echo esc_html( $menu['zagolovok'] ); ?></h2>
                <ul class="list-reset">
                  <?php foreach ( $menu['ssylki'] as $row ) : ?>
                    <?php
                    $link   = is_array( $row['ssylka_footer'] ?? null ) ? $row['ssylka_footer'] : array();
                    $url    = isset( $link['url'] ) ? esc_url( (string) $link['url'] ) : '';
                    $title  = isset( $link['title'] ) ? trim( (string) $link['title'] ) : '';
                    $target = '_blank' === ( $link['target'] ?? '' ) ? '_blank' : '';
                    if ( '' === $url || '#' === $url || '' === $title ) {
                      continue;
                    }
                    ?>
                    <li><a href="<?php echo esc_url( $url ); ?>"<?php echo $target ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>><?php echo esc_html( $title ); ?></a></li>
                  <?php endforeach; ?>
                </ul>
              </nav>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="footer__bottom">
        <div class="footer__brand">
          <a class="footer__logo-link" href="#" aria-label="Платёжка — на главную">
            <img src="img/footerLogo.svg" alt="Платёжка">
          </a>
          <p>2009–2026, сервис «Платёжка»</p>
        </div>

        <ul class="list-reset footer__legal-links">
          <?php foreach ( platejka_rework_legal_pages() as $page_id => $label ) : ?>
            <li><a href="<?php echo esc_url( get_permalink( $page_id ) ); ?>"><?php echo esc_html( $label ); ?></a></li>
          <?php endforeach; ?>
        </ul>


      </div>
      <div class="footer__disclaimer">
        <p>Сервис «Платёжка» проводит международные расчёты с зарубежными контрагентами. Вся переписка
          осуществляется через мессенджер удобным для клиента способом.</p>
        <p>Вся информация на сайте носит информационный характер и не является публичной офертой, определяемой
          положениями статьи 437 ГК РФ.</p>
      </div>
    </div>
  </div>
</footer>
