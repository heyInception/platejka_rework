@if (mode === 'main') {
<section class="shipments shipments--main" data-shipments aria-labelledby="shipments-main-title">
  <div class="container">
    <header class="shipments__header">
      <h2 id="shipments-main-title" class="shipments__title">Предоставьте платежи за рубеж нам и фокусируйтесь на своём бизнесе</h2>
      <p class="shipments__description">Мы предлагаем надёжные и проверенные решения для вашего бизнеса, чтобы вы могли продолжать работать и развиваться даже в сложных условиях. Наша компания специализируется на международных платежах для юридических лиц, обеспечивая безопасность и соответствие всем законодательным требованиям.</p>
    </header>
    <div class="shipments__list" data-shipments-slider tabindex="0" role="region" aria-label="Направления международных платежей">
      <article class="shipment-card shipment-card--main shipment-card_warm">
        <div class="shipment-card__content"><h3 class="shipment-card__title">Платежи в Китай</h3><p class="shipment-card__text">Расчёты с китайским поставщиком за 1–2 дня</p></div>
        <div class="shipment-card__tags" aria-label="Возможности"><span>Оплата поставщику</span><span>Импорт из Китая</span><span>Инвойс в CNY</span></div>
        <a class="shipment-card__link" href="#">Подробнее</a><img class="shipment-card__image" data-shipments-image src="img/shipments/main-1.png" width="300" height="300" alt="">
      </article>
      <article class="shipment-card shipment-card--main shipment-card_lime">
        <div class="shipment-card__content"><h3 class="shipment-card__title">SWIFT переводы для бизнеса</h3><p class="shipment-card__text">Быстрые переводы в евро по всему миру</p></div>
        <div class="shipment-card__tags" aria-label="Возможности"><span>Оплата за рубеж</span><span>Перевод в EUR</span><span>Зарубежные контрагенты</span></div>
        <a class="shipment-card__link" href="#">Подробнее</a><img class="shipment-card__image" data-shipments-image src="img/shipments/main-2.png" width="300" height="300" alt="">
      </article>
      <article class="shipment-card shipment-card--main shipment-card_light">
        <div class="shipment-card__content"><h3 class="shipment-card__title">Валютный контроль</h3><p class="shipment-card__text">Сопровождение контрактов и взаимодействие с банком</p></div>
        <div class="shipment-card__tags" aria-label="Возможности"><span>Постановка контракта</span><span>Документы для банка</span><span>Сопровождение ВЭД</span></div>
        <a class="shipment-card__link" href="#">Подробнее</a><img class="shipment-card__image" data-shipments-image src="img/shipments/main-3.png" width="300" height="300" alt="">
      </article>
      <article class="shipment-card shipment-card--main shipment-card_dark">
        <div class="shipment-card__content"><h3 class="shipment-card__title">Международные переводы для юридических лиц</h3><p class="shipment-card__text">Проведём платежи через компании в Гонконге, Китае, Казахстане, Европе и США в любой валюте</p></div>
        <div class="shipment-card__tags" aria-label="Возможности"><span>Оплата инвойсов</span><span>В любой валюте</span><span>Сложные направления</span></div>
        <a class="shipment-card__link" href="#">Подробнее</a><img class="shipment-card__image" data-shipments-image src="img/shipments/main-4.png" width="300" height="300" alt="">
      </article>
      <article class="shipment-card shipment-card--main shipment-card_blue">
        <div class="shipment-card__content"><h3 class="shipment-card__title">Трансграничные платежи и переводы для юридических лиц</h3><p class="shipment-card__text">Поддерживаем трансграничные переводы в более чем 100 стран мира</p></div>
        <div class="shipment-card__tags" aria-label="Возможности"><span>Расчёты с партнёрами</span><span>Платежи в 100+ стран</span><span>Международный бизнес</span></div>
        <a class="shipment-card__link" href="#">Подробнее</a><img class="shipment-card__image" data-shipments-image src="img/shipments/main-5.png" width="300" height="300" alt="">
      </article>
    </div>
  </div>
</section>
}
@if (mode !== 'main') {
<section class="shipments shipments--default" data-shipments aria-labelledby="shipments-title">
  <div class="container">
    <header class="shipments__header">
      <h2 id="shipments-title" class="shipments__title">От обычного инвойса до сложных поставок</h2>
      <p class="shipments__description">
        Компания работает как платёжный агент с полной юридической идентификацией. Реквизиты, реестровые
        записи и адрес офиса открыты — их можно сверить в государственных источниках за несколько минут.
      </p>
    </header>

    <div class="shipments__list" data-shipments-slider tabindex="0" role="region" aria-label="Варианты сопровождения поставок">
      <article class="shipment-card shipment-card_warm">
        <div class="shipment-card__content">
          <h3 class="shipment-card__title">Документальное сопровождение</h3>
          <p class="shipment-card__text">ООО закупает товар в Китае и должно подтвердить сделку перед банком и бухгалтерией. Готовим агентский договор, акт, платёжное поручение и подтверждение SWIFT MT103.</p>
        </div>
        <div class="shipment-card__tags" aria-label="Кому подходит">
          <span>ООО с ВЭД</span><span>Финансовый директор</span><span>Бухгалтерия</span>
        </div>
        <img class="shipment-card__image" data-shipments-image src="img/shipments/documentary-support.png" width="300" height="300" alt="">
      </article>

      <article class="shipment-card shipment-card_lime">
        <div class="shipment-card__content">
          <h3 class="shipment-card__title">Оплата инвойсов поставщикам</h3>
          <p class="shipment-card__text">Поставщик выставил инвойс в юанях или долларах, а прямой перевод из РФ не проходит. Подбираем рабочий маршрут и отправляем оплату на счёт китайского контрагента.</p>
        </div>
        <div class="shipment-card__tags" aria-label="Кому подходит">
          <span>Импортёры из Китая</span><span>Отдел закупок</span><span>ООО и ИП</span>
        </div>
        <img class="shipment-card__image" data-shipments-image src="img/shipments/payment.png" width="300" height="300" alt="">
      </article>

      <article class="shipment-card shipment-card_light">
        <div class="shipment-card__content">
          <h3 class="shipment-card__title">Платёжный агент по 173-ФЗ</h3>
          <p class="shipment-card__text">Компания хочет оплачивать поставщика из России в рублях без самостоятельной работы с зарубежным банком. Принимаем платёж по агентскому договору и проводим расчёт в Китае.</p>
        </div>
        <div class="shipment-card__tags" aria-label="Кому подходит">
          <span>ООО 10–200 млн ₽</span><span>Финансовый директор</span><span>Собственник бизнеса</span>
        </div>
        <img class="shipment-card__image" data-shipments-image src="img/shipments/payment-agent.png" width="300" height="300" alt="">
      </article>

      <article class="shipment-card shipment-card_dark">
        <div class="shipment-card__content">
          <h3 class="shipment-card__title">Валютный контроль и комплаенс</h3>
          <p class="shipment-card__text">Банк запрашивает документы или уточняет назначение платежа по внешнеторговой сделке. Проверяем пакет документов заранее и помогаем корректно оформить платёж.</p>
        </div>
        <div class="shipment-card__tags" aria-label="Кому подходит">
          <span>Финансовый директор</span><span>Бухгалтерия</span><span>ВЭД-специалист</span>
        </div>
        <img class="shipment-card__image" data-shipments-image src="img/shipments/currency-control.png" width="300" height="300" alt="">
      </article>

      <article class="shipment-card shipment-card_blue">
        <div class="shipment-card__content">
          <h3 class="shipment-card__title">Сопровождение сложных платежей</h3>
          <p class="shipment-card__text">Новый поставщик, нестандартный товар или крупная сумма требуют отдельной платёжной схемы. Проверяем условия сделки, документы и подбираем подходящий маршрут перевода.</p>
        </div>
        <div class="shipment-card__tags" aria-label="Кому подходит">
          <span>Средний бизнес</span><span>Импортёры</span><span>Руководитель ВЭД</span>
        </div>
        <img class="shipment-card__image" data-shipments-image src="img/shipments/complex-payments.png" width="300" height="300" alt="">
      </article>

      <article class="shipment-card shipment-card_gray">
        <div class="shipment-card__content">
          <h3 class="shipment-card__title">Безопасные расчёты с поставщиками</h3>
          <p class="shipment-card__text">Бизнес регулярно оплачивает поставки из Китая и хочет снизить риск возврата или блокировки перевода. Проверяем реквизиты и документы до отправки и сопровождаем платёж.</p>
        </div>
        <div class="shipment-card__tags" aria-label="Кому подходит">
          <span>Регулярные закупки</span><span>Финансовый директор</span><span>Собственник бизнеса</span>
        </div>
        <img class="shipment-card__image shipment-card__image_secure" data-shipments-image src="img/shipments/secure.png" width="300" height="300" alt="">
      </article>
    </div>
  </div>
</section>
}
