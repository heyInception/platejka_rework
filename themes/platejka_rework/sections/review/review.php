<section class="review" aria-labelledby="review-title" data-review>
  <div class="container">
    <header class="review__intro">
      <span class="review__eyebrow">Отзывы</span>
      <h2 id="review-title">Клиенты остаются<br>с нами на долго</h2>
      <p>Обещание стопроцентной проходимости проверить невозможно, поэтому мы его не даём.</p>
    </header>

    <div class="review__body">
      <div class="review__tabs" role="tablist" aria-label="Вид отзывов" data-review-tabs>
        <button class="review__tab" type="button" role="tab" id="review-video-tab"
          aria-controls="review-video-panel" aria-selected="true">Видео-отзывы</button>
        <button class="review__tab" type="button" role="tab" id="review-text-tab"
          aria-controls="review-text-panel" aria-selected="false" tabindex="-1">Текстовые</button>
      </div>

      <div id="review-video-panel" role="tabpanel" aria-labelledby="review-video-tab" data-review-video-panel>
        <div class="review__slider" data-horizontal-slider>
          <div class="review__viewport" data-horizontal-slider-viewport tabindex="0" role="region"
            aria-label="Видеоотзывы клиентов">
            <div class="review__track" data-horizontal-slider-track>
              <article class="review__video-card review__video-card--1" data-horizontal-slider-slide>
                <div>
                  <h3>Елена С.</h3>
                  <p>Финансовый директор<br>ООО «Смирнов Групп»</p>
                  <button type="button" class="review__watch"
                    data-review-video="img/review/70960-536644237_medium.mp4">Смотреть отзыв</button>
                </div>
              </article>
              <article class="review__video-card review__video-card--2" data-horizontal-slider-slide>
                <div>
                  <h3>Милена К.</h3>
                  <p>Генеральный директор<br>ООО «Самострой7»</p>
                  <button type="button" class="review__watch" data-review-video="">Смотреть отзыв</button>
                </div>
              </article>
              <article class="review__video-card review__video-card--3" data-horizontal-slider-slide>
                <div>
                  <h3>Дарья Б.</h3>
                  <p>Менеджер по работе<br>с клиентами</p>
                  <button type="button" class="review__watch" data-review-video="">Смотреть отзыв</button>
                </div>
              </article>
            </div>
          </div>
          <div class="review__controls" data-horizontal-slider-controls>
            <button type="button" class="review__arrow review__arrow--prev" data-horizontal-slider-prev
              aria-label="Предыдущий отзыв">‹</button>
            <button type="button" class="review__arrow review__arrow--next" data-horizontal-slider-next
              aria-label="Следующий отзыв">›</button>
          </div>
        </div>
      </div>

      <div id="review-text-panel" role="tabpanel" aria-labelledby="review-text-tab" data-review-text-panel hidden>
        <div class="review__slider" data-horizontal-slider>
          <div class="review__viewport" data-horizontal-slider-viewport tabindex="0" role="region"
            aria-label="Текстовые отзывы клиентов">
            <div class="review__track review__text-track" data-horizontal-slider-track>
              <article class="review__text-card" data-horizontal-slider-slide>
                <div class="review__stars" aria-label="Оценка 5 из 5">★★★★★</div>
                <blockquote>Platejka.com — отличный международный бизнес-платежный сервис! Я воспользовался его
                  услугами и остался очень доволен. Платформа интуитивно понятна и проста в использовании, что
                  значительно упрощает процесс проведения трансакций. Платежи проходят быстро и надежно, а поддержка
                  клиентов всегда готова помочь с любыми вопросами. Рекомендую Platejka.com всем, кто ищет эффективное
                  решение для международных платежей!</blockquote>
                <span class="review__source">
                  <img src="img/review/yandex-main.png" width="20" height="20" alt="">
                  Отзыв на Яндекс <span aria-hidden="true">›</span>
                </span>
                <footer>
                  <strong>Sasha Mekhel</strong>
                  <time datetime="2026-08-28">28.08.2026</time>
                </footer>
              </article>
              <article class="review__text-card" data-horizontal-slider-slide>
                <div class="review__stars" aria-label="Оценка 5 из 5">★★★★★</div>
                <blockquote>Мы решили обратиться к Platejka.com из-за его простоты использования и эффективности, а
                  также из-за того, что он делает процесс оплаты и перевода простым и удобным. Особенно мне понравилась
                  прозрачность и скорость переводов. Размер комиссий был понятен с самого начала, что позволило мне
                  осуществлять комплексное финансовое планирование, не беспокоясь о скрытых или дополнительных
                  расходах.</blockquote>
                <span class="review__source">
                  <img src="img/review/yandex-main.png" width="20" height="20" alt="">
                  Отзыв на Яндекс <span aria-hidden="true">›</span>
                </span>
                <footer>
                  <strong>Ева Джозеф</strong>
                  <time datetime="2026-08-28">28.08.2026</time>
                </footer>
              </article>
            </div>
          </div>
          <div class="review__controls" data-horizontal-slider-controls>
            <button type="button" class="review__arrow review__arrow--prev" data-horizontal-slider-prev
              aria-label="Предыдущий отзыв">‹</button>
            <button type="button" class="review__arrow review__arrow--next" data-horizontal-slider-next
              aria-label="Следующий отзыв">›</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <dialog class="review__dialog" aria-labelledby="review-dialog-title" data-review-dialog>
    <button type="button" class="review__close" data-review-close aria-label="Закрыть отзыв">×</button>
    <div class="review__dialog-card">
      <video preload="none" playsinline></video>
      <div class="review__loading" data-review-loading role="status" aria-label="Загрузка видео" hidden></div>
      <div class="review__dialog-caption">
        <h2 id="review-dialog-title"></h2>
        <p class="review__dialog-role" data-review-dialog-role></p>
        <p class="review__video-empty" data-review-empty>Видео скоро появится.</p>
        <p class="review__video-error" data-review-error hidden>Не удалось загрузить видео.</p>
      </div>
    </div>
  </dialog>
</section>
