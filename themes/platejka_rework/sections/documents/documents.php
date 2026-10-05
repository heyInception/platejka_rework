@if (mode === 'main') {
<section class="documents documents--main" id="documents" aria-labelledby="documents-main-title">
  <div class="container"><div class="documents__panel">
    <h2 class="documents__title" id="documents-main-title">Документы для начала работы</h2>
    <p class="documents__intro">Скачайте и передайте юристу или бухгалтеру до разговора с менеджером. Регистрация и телефон не нужны.</p>
    <div class="documents__cards" data-documents-slider tabindex="0" role="region" aria-label="Документы для скачивания">
      <article class="document-card document-card--main"><img class="document-card__preview" src="img/documents/doc.png" width="128" height="144" alt="" loading="lazy"><div class="document-card__content"><h3>Официальный агентский контракт</h3><p>По нему проходит сделка</p></div><a class="document-card__download" href="#">Скачать <img src="img/documents/download.svg" width="20" height="20" alt=""></a></article>
      <article class="document-card document-card--main"><img class="document-card__preview" src="img/documents/doc.png" width="128" height="144" alt="" loading="lazy"><div class="document-card__content"><h3>Образец SWIFT-подтверждения</h3><p>Подтверждение проведённого платежа</p></div><a class="document-card__download" href="#">Скачать <img src="img/documents/download.svg" width="20" height="20" alt=""></a></article>
      <article class="document-card document-card--main"><img class="document-card__preview" src="img/documents/doc.png" width="128" height="144" alt="" loading="lazy"><div class="document-card__content"><h3>Образец поручения</h3><p>Фиксирует условия конкретного платежа</p></div><a class="document-card__download" href="#">Скачать <img src="img/documents/download.svg" width="20" height="20" alt=""></a></article>
      <article class="document-card document-card--main"><img class="document-card__preview" src="img/documents/doc.png" width="128" height="144" alt="" loading="lazy"><div class="document-card__content"><h3>Образец отчёта агента</h3><p>Закрывающий документ по сделке</p></div><a class="document-card__download" href="#">Скачать <img src="img/documents/download.svg" width="20" height="20" alt=""></a></article>
    </div>
  </div></div>
</section>
}
@if (mode !== 'main') {
<section class="documents documents--default" id="documents" aria-labelledby="documents-title">
  <div class="container">
    <div class="documents__panel">
      <h2 class="documents__title" id="documents-title">Документы для начала работы</h2>
      <p class="documents__intro">Скачайте и передайте юристу или бухгалтеру до разговора с менеджером. Регистрация и телефон не нужны.</p>

      <div class="documents__cards" data-documents-slider tabindex="0" role="region" aria-label="Документы для скачивания">
        <article class="document-card">
          <img class="document-card__preview" src="img/documents/doc.png" width="128" height="144" alt="" loading="lazy">
          <div class="document-card__content">
            <h3>Официальный агентский контракт</h3>
            <p>По нему проходит сделка</p>
            <span class="document-card__file"><img src="img/documents/word.svg" width="16" height="16" alt="">PDF, 2мб</span>
          </div>
          <a class="document-card__download" href="#">Скачать <img src="img/documents/download.svg" width="20" height="20" alt=""></a>
        </article>

        <article class="document-card">
          <img class="document-card__preview" src="img/documents/doc.png" width="128" height="144" alt="" loading="lazy">
          <div class="document-card__content">
            <h3>Образец SWIFT-подтверждения</h3>
            <p>Подтверждение проведённого платежа</p>
            <span class="document-card__file"><img src="img/documents/pdf.svg" width="16" height="16" alt="">PDF, 2мб</span>
          </div>
          <a class="document-card__download" href="#">Скачать <img src="img/documents/download.svg" width="20" height="20" alt=""></a>
        </article>

        <article class="document-card">
          <img class="document-card__preview" src="img/documents/doc.png" width="128" height="144" alt="" loading="lazy">
          <div class="document-card__content">
            <h3>Образец поручения</h3>
            <p>Фиксирует условия конкретного платежа</p>
            <span class="document-card__file"><img src="img/documents/word.svg" width="16" height="16" alt="">Word, 2мб</span>
          </div>
          <a class="document-card__download" href="#">Скачать <img src="img/documents/download.svg" width="20" height="20" alt=""></a>
        </article>

        <article class="document-card">
          <img class="document-card__preview" src="img/documents/doc.png" width="128" height="144" alt="" loading="lazy">
          <div class="document-card__content">
            <h3>Образец отчёта агента</h3>
            <p>Закрывающий документ по сделке</p>
            <span class="document-card__file"><img src="img/documents/pdf.svg" width="16" height="16" alt="">PDF, 2мб</span>
          </div>
          <a class="document-card__download" href="#">Скачать <img src="img/documents/download.svg" width="20" height="20" alt=""></a>
        </article>
      </div>
    </div>
  </div>
</section>
}
