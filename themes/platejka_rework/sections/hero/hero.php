<section class="hero" data-hero>
  <div class="container">
    <div class="hero__layout">
      <div class="hero__content">
        <div
          class="hero__wrap hero__wrap_top"
          style="--hero-image: url('img/china-hero-bg.png'); --hero-blur: url('img/china-hero-blur.png')"
          data-hero-panel
        >
          <span class="hero__image" aria-hidden="true" data-hero-image></span>
          <span class="hero__blur" aria-hidden="true" data-hero-blur></span>

          <p class="hero__badge" data-hero-copy>Юридическим лицам</p>
          <h1 class="hero__title" data-hero-copy>
            <span class="hero__title-primary">Платежи в&nbsp;Китай</span>
            <span class="hero__title-secondary">для&nbsp;юридических лиц по&nbsp;агентскому договору</span>
          </h1>
          <p class="hero__subtitle" data-hero-copy>
            Когда прямые переводы из&nbsp;российских банков блокируются, мы&nbsp;находим подходящий способ доставить ваши
            деньги поставщику в&nbsp;КНР
          </p>

          <ul class="list-reset hero__items" data-hero-copy>
            <li class="hero__item">
              <span class="hero__item-title">3%</span>
              <span class="hero__item-content">Комиссия</span>
            </li>
            <li class="hero__item">
              <span class="hero__item-title">до 6 часов</span>
              <span class="hero__item-content">Проведение платежа</span>
            </li>
            <li class="hero__item">
              <span class="hero__item-title">100 000 ₽</span>
              <span class="hero__item-content">Минимальная сумма платежа</span>
            </li>
          </ul>

          <p class="hero__info" data-hero-copy>
            Полный пакет закрывающих документов для валютного контроля и таможни
          </p>
        </div>

        <ul class="list-reset hero__trust" data-hero-cards>
          <li class="hero__trust-card hero__trust-card_experience">
            <div class="hero__trust-years" aria-label="Пять лет">
              <img src="img/hero__trust-years-left.svg" width="22" height="72" alt="">
              <span aria-hidden="true">5 лет</span>
              <img src="img/hero__trust-years-right.svg" width="22" height="72" alt="">
            </div>
            <p class="hero__trust-caption">На рынке</p>
          </li>
          <li class="hero__trust-card hero__trust-card_registry">
            <p class="hero__trust-title">В&nbsp;реестре ЦБ&nbsp;РФ и&nbsp;СРО</p>
            <img class="hero__trust-decor hero__trust-decor_registry" src="img/registry.png" width="161" height="161" alt="">
            <a class="ui-link ui-link--overlay hero__trust-link" href="#">Проверить</a>
          </li>
          <li class="hero__trust-card hero__trust-card_association">
            <p class="hero__trust-title">В Ассоциации<br>платёжных агентов</p>
            <img class="hero__trust-decor hero__trust-decor_association" src="img/associations.png" width="161" height="161" alt="">
            <a class="ui-link ui-link--inverse hero__trust-link" href="#">Документы</a>
          </li>
        </ul>
      </div>

      <form
        class="hero-calculator"
        data-hero-calculator
        data-currency-rates='{"CNY":12.3,"USD":81.0929}'
        novalidate
      >
        <div class="hero-calculator__banner">
          <img src="img/svg/cb.svg" width="20" height="20" alt="">
          <span>В реестре ЦБ РФ и СРО платёжных агентов</span>
        </div>

        <div class="hero-calculator__body">
          <h2 class="hero-calculator__title">Рассчитайте платёж</h2>

          <div class="ui-tabs hero-calculator__currencies" aria-label="Валюта платежа">
            <span class="hero-calculator__indicator" data-currency-indicator aria-hidden="true"></span>
            <button class="ui-tab hero-calculator__currency is-active" type="button" data-currency="CNY" aria-pressed="true">
              <img class="hero-calculator__flag" src="img/cn-flag.png" width="20" height="20" alt="">
              CNY
            </button>
            <button class="ui-tab hero-calculator__currency" type="button" data-currency="USD" aria-pressed="false">
              <img class="hero-calculator__flag" src="img/usa-flag.png" width="20" height="20" alt="">
              USD
            </button>
          </div>

          <div class="hero-calculator__field">
            <label class="hero-calculator__label" for="hero-amount">Сумма:</label>
            <div class="hero-calculator__amount-wrap">
              <input
                class="ui-amount-input hero-calculator__amount"
                id="hero-amount"
                name="amount"
                type="number"
                min="1"
                step="1"
                inputmode="numeric"
                value="100000"
                data-role="amount"
                required
              >
              <span class="hero-calculator__symbol" data-role="currency-symbol" aria-hidden="true">¥</span>
            </div>
          </div>

          <dl class="hero-calculator__results">
            <div class="hero-calculator__result-row">
              <dt>Официальный курс</dt>
              <dd><output data-role="official-rate">0 ₽</output></dd>
            </div>
            <div class="hero-calculator__result-row">
              <dt>Наш курс конвертации</dt>
              <dd><output data-role="conversion-rate">0 ₽</output></dd>
            </div>
            <div class="hero-calculator__result-row">
              <dt>Комиссия агента</dt>
              <dd><output data-role="commission">0 ₽</output></dd>
            </div>
            <div class="hero-calculator__result-row hero-calculator__result-row_total">
              <dt>Итого в рублях</dt>
              <dd><output data-role="grand-total">0 ₽</output></dd>
            </div>
          </dl>

          <button class="ui-button hero-calculator__submit" type="submit">Рассчитать стоимость платежа</button>

          <p class="hero-calculator__note">
            <img src="img/Shield-Check.svg" width="24" height="24" alt="">
            Курс и комиссию фиксируем в договоре
          </p>
        </div>
      </form>
    </div>
  </div>

  <dialog class="contact-dialog" data-contact-dialog aria-labelledby="contact-dialog-title">
    <div class="contact-dialog__content">
      <button class="btn-reset contact-dialog__close" type="button" data-dialog-close aria-label="Закрыть окно">×</button>
      <h2 class="contact-dialog__title" id="contact-dialog-title">Оставить заявку</h2>
      <p class="contact-dialog__text">Параметры расчёта готовы для заявки. Форма будет подключена при переносе в WordPress.</p>
      <p class="contact-dialog__summary" data-dialog-summary>Расчёт не выбран.</p>
      <!-- При переносе в WordPress: вывести CF7 здесь; полю для сводки добавить data-cf7-summary. -->
      <div data-cf7-mount></div>
    </div>
  </dialog>
</section>
