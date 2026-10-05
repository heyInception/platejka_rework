<section class="calculator" data-transfer-calculator-section>
  <div class="container">
    <header class="calculator__heading">
      <span class="calculator__badge">Калькулятор</span>
      <h2>Рассчитайте стоимость международного перевода</h2>
    </header>

    <!-- Демонстрационные курсы: при переносе в WordPress data-currency-rates заполняется серверными значениями. -->
    <form
      class="calculator__panel"
      data-transfer-calculator
      data-currency-rates='{"CNY":12.3,"USD":81.0929,"EUR":90}'
      novalidate
    >
      <div class="calculator__columns">
        <div class="calculator__fields">
          <div class="ui-tabs calculator__currencies" role="group" aria-label="Валюта платежа">
            <span class="calculator__indicator" data-currency-indicator aria-hidden="true"></span>
            <button class="ui-tab calculator__currency is-active" type="button" data-currency="CNY" aria-pressed="true">
              <img src="img/cn-flag.png" width="20" height="20" alt=""> CNY
            </button>
            <button class="ui-tab calculator__currency" type="button" data-currency="USD" aria-pressed="false">
              <img src="img/usa-flag.png" width="20" height="20" alt=""> USD
            </button>
            <button class="ui-tab calculator__currency" type="button" data-currency="EUR" aria-pressed="false">
              <img src="img/eur-flag.png" width="20" height="20" alt=""> EUR
            </button>
          </div>

          <div class="calculator__route">
            <label class="calculator__field">
              <span class="calculator__field-label">Страна Отправитель</span>
              <span class="calculator__select-wrap">
                <img src="img/ru-flag.png" width="20" height="20" alt="" data-select-flag aria-hidden="true">
                <select
                  name="country_from"
                  data-role="country-from"
                  data-calculator-select="country"
                  data-search-enabled="false"
                >
                  <option value="RU" data-flag="img/ru-flag.png">Россия</option>
                </select>
              </span>
            </label>

            <label class="calculator__field">
              <span class="calculator__field-label">Страна Получатель</span>
              <span class="calculator__select-wrap">
                <img src="img/cn-flag.png" width="20" height="20" alt="" data-select-flag aria-hidden="true">
                <select
                  name="country_to"
                  data-role="country-to"
                  data-calculator-select="country"
                  data-search-enabled="true"
                >
                  <option value="CN" data-flag="img/cn-flag.png">Китай</option>
                  <option value="TR">Турция</option>
                  <option value="US" data-flag="img/usa-flag.png">Соединённые Штаты Америки</option>
                  <option value="AU">Австралия</option>
                  <option value="AT">Австрия</option>
                  <option value="AZ">Азербайджан</option>
                  <option value="AL">Албания</option>
                  <option value="AM">Армения</option>
                  <option value="AR">Аргентина</option>
                  <option value="BE">Бельгия</option>
                  <option value="BY">Беларусь</option>
                  <option value="BG">Болгария</option>
                  <option value="BA">Босния и Герцеговина</option>
                  <option value="BR">Бразилия</option>
                  <option value="HU">Венгрия</option>
                  <option value="AE">Объединённые Арабские Эмираты</option>
                  <option value="DE">Германия</option>
                  <option value="GR">Греция</option>
                  <option value="GE">Грузия</option>
                  <option value="DK">Дания</option>
                  <option value="EG">Египет</option>
                  <option value="IL">Израиль</option>
                  <option value="KZ">Казахстан</option>
                  <option value="GB">Великобритания</option>
                  <option value="IN">Индия</option>
                  <option value="ID">Индонезия</option>
                  <option value="IE">Ирландия</option>
                  <option value="ES">Испания</option>
                  <option value="IT">Италия</option>
                  <option value="CA">Канада</option>
                  <option value="KE">Кения</option>
                  <option value="KG">Киргизия</option>
                  <option value="CO">Колумбия</option>
                  <option value="CR">Коста-Рика</option>
                  <option value="LV">Латвия</option>
                  <option value="LT">Литва</option>
                  <option value="LI">Лихтенштейн</option>
                  <option value="LU">Люксембург</option>
                  <option value="MY">Малайзия</option>
                  <option value="MT">Мальта</option>
                  <option value="MX">Мексика</option>
                  <option value="MD">Молдавия</option>
                  <option value="MC">Монако</option>
                  <option value="NA">Намибия</option>
                  <option value="NL">Нидерланды</option>
                  <option value="NG">Нигерия</option>
                  <option value="NZ">Новая Зеландия</option>
                  <option value="NO">Норвегия</option>
                  <option value="PE">Перу</option>
                  <option value="PL">Польша</option>
                  <option value="PT">Португалия</option>
                  <option value="RO">Румыния</option>
                  <option value="SA">Саудовская Аравия</option>
                  <option value="MK">Северная Македония</option>
                  <option value="RS">Сербия</option>
                  <option value="SG">Сингапур</option>
                  <option value="SK">Словакия</option>
                  <option value="SI">Словения</option>
                  <option value="TJ">Таджикистан</option>
                  <option value="TH">Таиланд</option>
                  <option value="TW">Тайвань</option>
                  <option value="TZ">Танзания</option>
                  <option value="TN">Тунис</option>
                  <option value="TM">Туркмения</option>
                  <option value="UZ">Узбекистан</option>
                  <option value="PH">Филиппины</option>
                  <option value="FI">Финляндия</option>
                  <option value="FR">Франция</option>
                  <option value="HR">Хорватия</option>
                  <option value="TD">Чад</option>
                  <option value="ME">Черногория</option>
                  <option value="CZ">Чехия</option>
                  <option value="CL">Чили</option>
                  <option value="CH">Швейцария</option>
                  <option value="SE">Швеция</option>
                  <option value="ZA">Южно-Африканская Республика</option>
                  <option value="KR">Южная Корея</option>
                  <option value="VN">Вьетнам</option>
                  <option value="JP">Япония</option>
                </select>
              </span>
            </label>
          </div>

          <label class="calculator__field calculator__amount-field">
            <span class="calculator__field-label">Сумма:</span>
            <span class="calculator__amount-wrap">
              <input
                name="amount"
                type="text"
                inputmode="decimal"
                autocomplete="off"
                value="100 000"
                data-role="amount"
                required
              >
              <span data-role="currency-symbol" aria-hidden="true">¥</span>
            </span>
          </label>

          <p class="calculator__rate-note">
            <img src="img/svg/cb.svg" width="20" height="20" alt="">
            Расчёты проводим по курсу ЦБ РФ
          </p>
        </div>

        <div class="calculator__summary">
          <h3>Предварительный расчёт</h3>
          <dl class="calculator__results">
            <div><dt>Официальный курс</dt><dd><output data-role="official-rate">0 ₽</output></dd></div>
            <div><dt>Наш курс конвертации</dt><dd><output data-role="conversion-rate">0 ₽</output></dd></div>
            <div><dt>Комиссия агента</dt><dd><output data-role="commission">0 ₽</output></dd></div>
            <div class="calculator__total"><dt>Итого в рублях</dt><dd><output data-role="grand-total">0 ₽</output></dd></div>
          </dl>

          <div class="calculator__actions">
            <button class="ui-button" type="submit" data-action="request">Отправить заявку</button>
            <button class="ui-button calculator__telegram" type="button" data-action="telegram">
              <img src="img/svg/telegram-green.svg" width="20" height="20" alt=""> Написать
            </button>
          </div>

          <p class="calculator__contract-note">
            <img src="img/Shield-Check.svg" width="24" height="24" alt="">
            Курс и комиссию фиксируем в договоре
          </p>
        </div>
      </div>
    </form>
  </div>

  <dialog class="contact-dialog" data-contact-dialog aria-label="Оставить заявку">
    <div class="contact-dialog__content">
      <button class="btn-reset contact-dialog__close" type="button" data-dialog-close aria-label="Закрыть окно">×</button>
      <h2 class="contact-dialog__title">Оставить заявку</h2>
      <p class="contact-dialog__text">Параметры расчёта готовы для заявки. Форма будет подключена при переносе в WordPress.</p>
      <p class="contact-dialog__summary" data-dialog-summary>Расчёт не выбран.</p>
      <!-- При переносе в WordPress: вывести CF7 здесь; полю для сводки добавить data-cf7-summary. -->
      <div data-cf7-mount></div>
    </div>
  </dialog>
</section>
