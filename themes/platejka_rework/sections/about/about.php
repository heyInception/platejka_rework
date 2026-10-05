<section class="about" data-about>
  <div class="container">
    <div class="about__layout">
      <div class="about__media">
        <img src="img/about-img.png" width="592" height="592" alt="">
      </div>

      <div class="about__content">
        <div class="about__intro">
          <span class="about__badge">О компании</span>
          <h2>Официальный платёжный агент в реестре ЦБ РФ</h2>
          <div class="about__title">
            <span class="about__title-accent">Официальный</span>
            <img class="about__title-check" src="img/svg/сheck.svg" width="40" height="40" alt="">
            платёжный агент в реестре
            <img class="about__title-bank" src="img/svg/about-cb-rf.svg" width="40" height="40" alt="">
            ЦБ РФ
          </div>
          <p class="about__description">
            Компания работает как платёжный агент с полной юридической идентификацией. Реквизиты, реестровые
            записи и адрес офиса открыты — их можно сверить в государственных источниках за несколько минут.
          </p>
          <a class="ui-link about__agreement" href="#">
            <span>Образец агентского договора</span>
          </a>
        </div>

        <div class="about__cards">
          <article class="about-card">
            <div class="about-card__image">
              <img src="img/about-sro.png" width="120" height="173" alt="">
            </div>
            <div class="about-card__content">
              <div class="about-card__heading">
                <img class="about-card__logo about-card__logo_sro" src="img/svg/about-SRO.svg" width="25" height="20" alt="">
                <h3 class="about-card__title">Выписка из реестра СРО</h3>
              </div>
              <a class="ui-button ui-button--small about-card__link" href="#">Смотреть</a>
            </div>
          </article>

          <article class="about-card">
            <div class="about-card__image">
              <img src="img/cb-img.png" width="120" height="173" alt="">
            </div>
            <div class="about-card__content">
              <div class="about-card__heading">
                <img class="about-card__logo" src="img/svg/about-cb-rf.svg" width="20" height="20" alt="">
                <h3 class="about-card__title">Выписка из реестра ЦБ РФ</h3>
              </div>
              <a class="ui-button ui-button--small about-card__link" href="#">Смотреть</a>
            </div>
          </article>
        </div>
      </div>
    </div>

    <div class="about__cta">
      <button class="ui-button about__cta-button" type="button" data-about-dialog-open>
        Записаться на встречу в офисе
      </button>
      <address class="about__address">Москва, Долгопрудненское шоссе, 3</address>
    </div>
  </div>

  <dialog class="about-dialog" data-about-dialog aria-label="Записаться на встречу">
    <div class="about-dialog__content">
      <button class="btn-reset about-dialog__close" type="button" data-about-dialog-close aria-label="Закрыть окно">×</button>
      <h2 class="about-dialog__title">Записаться на встречу</h2>
      <p class="about-dialog__text">Форма записи появится здесь на следующем этапе.</p>
    </div>
  </dialog>
</section>
