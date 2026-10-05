@if (mode === 'main') {
<section class="guarantees guarantees--main" data-guarantees aria-labelledby="guarantees-main-title">
  <div class="container">
    <header class="guarantees__header">
      <span class="guarantees__badge">Гарантии</span>
      <h2 class="guarantees__title" id="guarantees-main-title">Гарантии безопасности транзакций</h2>
      <p class="guarantees__description">Мы отвечаем за платёж не словами, а статусом в госреестрах и документами в открытом доступе. Всё ниже проверяется за несколько минут.</p>
    </header>
    <div class="guarantees__cards">
      <article class="guarantee-card guarantee-card--main-featured">
        <div><h3>Гарантируем поступление средств вашему контрагенту своими деньгами</h3><p>Мы берём на себя все финансовые риски, гарантируя, что средства будут поступать вашему контрагенту вовремя и без задержек (при платежах в евро через SWIFT).</p></div>
        <img src="img/guarantees/guarantee-card-main.png" width="578" height="560" alt="">
      </article>
      <div class="guarantees__slider" data-guarantees-slider tabindex="0" role="region" aria-label="Другие гарантии">
        <article class="guarantee-card guarantee-card--main-secondary guarantee-card--main-risk"><div><h3>Страхуем риски</h3><p>Компенсируем штрафы, если они возникнут по нашей вине.</p></div><img src="img/guarantees/risks-main.png" width="340" height="300" alt=""></article>
        <article class="guarantee-card guarantee-card--main-secondary guarantee-card--main-fintech"><div><h3>Специалисты из финтеха</h3><p>Аккредитованы государственными органами, используем собственное платёжное ПО. Но также работаем с ведущими платёжными системами мира (PayPal, Wise, Revolut, Payoneer, AliPay).</p></div><img src="img/guarantees/fintech-main.png" width="340" height="300" alt=""></article>
      </div>
    </div>
  </div>
</section>
}
@if (mode !== 'main') {
<section class="guarantees guarantees--default" aria-labelledby="guarantees-title" data-guarantees>
  <div class="container">
    <header class="guarantees__header">
      <span class="guarantees__badge">Гарантии</span>
      <h2 class="guarantees__title" id="guarantees-title"><span>Гарантии,</span> которые можно проверить, а не обещания</h2>
      <p class="guarantees__description">Мы отвечаем за платёж не словами, а статусом в госреестрах и документами в открытом доступе. Всё ниже проверяется за несколько минут.</p>
    </header>

    <div class="guarantees__cards">
      <article class="guarantee-card guarantee-card_registry">
        <div class="guarantee-card__content">
          <h3>В реестре ЦБ РФ и в СРО платёжных агентов</h3>
          <p>Попасть в реестр ЦБ сложно и дорого, а удержаться — ещё сложнее: одной обоснованной жалобы достаточно, чтобы его потерять. <strong>Членство в СРО означает обязательство компенсировать ущерб, если ошибёмся мы.</strong></p>
        </div>
        <div class="guarantee-card__actions">
          <button class="ui-button ui-button--medium ui-button--overlay" type="button" disabled><img class="guarantee-card__action-icon" src="img/guarantees/bank.svg" width="20" height="20" alt="">Запись в реестре ЦБ <img src="img/guarantees/chevron.svg" width="20" height="20" alt=""></button>
          <button class="ui-button ui-button--medium ui-button--overlay" type="button" disabled><img class="guarantee-card__action-icon" src="img/guarantees/file.svg" width="20" height="20" alt="">Выписка из СРО <img src="img/guarantees/chevron.svg" width="20" height="20" alt=""></button>
        </div>
      </article>

      <div class="guarantees__slider" data-guarantees-slider tabindex="0" role="region" aria-label="Другие гарантии">
        <article class="guarantee-card guarantee-card_office">
          <div class="guarantee-card__content">
            <h3>Один офис с 2024 года</h3>
            <p>Работаем по одному адресу в Москве, со штатом сотрудников. Можно приехать, познакомиться с командой и обсудить сделку с руководством.</p>
            <address>Москва, Долгопрудненское шоссе, 3</address>
          </div>
        </article>
        <article class="guarantee-card guarantee-card_clients">
          <strong>354+</strong>
          <p>Компании, которые проводят платежи в Китай через нас регулярно.</p>
        </article>
        <article class="guarantee-card guarantee-card_risk">
          <h3>Риски платежа берём на себя</h3>
          <p>Отвечаем за то, что средства дойдут до вашего контрагента.</p>
        </article>
      </div>
    </div>
  </div>
</section>
}
