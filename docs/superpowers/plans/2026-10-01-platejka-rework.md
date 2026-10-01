# Platejka Rework Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Создать блочную тему `platejka_rework` и companion-плагин `platejka-core`, которые воспроизводят новый дизайн для общих компонентов и трёх существующих страниц без потери ACF-контента, SEO и бизнес-логики.

**Architecture:** Тема владеет представлением, блочными шаблонами и динамическими секциями; плагин владеет курсами, расчётами, ACF Local JSON и интеграционными контрактами. Существующие ACF-ключи читаются через явную карту данных, а HTML-экспорт используется только как эталон дизайна.

**Tech Stack:** WordPress 6.8+, PHP 8.1+, ACF Pro, Contact Form 7, `theme.json` v3, CSS/JavaScript modules, PHPUnit, Node test runner, Playwright, WordPress Studio CLI.

**Spec:** `docs/superpowers/specs/2026-10-01-platejka-rework-design.md`

## Global Constraints

- Все WP-CLI команды выполнять только как `studio wp --path C:\Users\Inception\Studio\platejka ...`.
- Не изменять `platejka-pagespeed`; он является источником поведения и сравнительным эталоном.
- Не изменять `C:\project\platejka-new\wordpress`; он является визуальным эталоном.
- Не создавать дубли страниц ID 24, 1873 и 22 и не менять их slug.
- Не переименовывать существующие ACF field keys и meta keys.
- Не выводить тестовый HTML-контент как fallback.
- Не удалять и не заменять действующие плагины.
- Минимум WordPress 6.8/PHP 8.1; основная проверка WordPress 7.0.x/PHP 8.4.
- Компоненты обязаны учитывать клавиатуру, focus-visible и `prefers-reduced-motion`.
- Каждый этап заканчивается тестами, визуальной проверкой и отдельным коммитом.

## Review Focus

- Пустое или отсутствующее ACF-поле: элемент/секция скрывается, тестовая строка не появляется.
- Недоступный источник курса: используется действующий кеш/последнее корректное значение, форма не выдаёт ложный расчёт.
- Повторная инициализация JS после динамического рендера: обработчики не дублируются.
- Очень длинные русские строки и большие суммы: разметка не переполняется на 360 px.
- Смена темы и синхронизация ACF JSON: существующие значения и field keys остаются неизменными.

---

### Task 1: Безопасная граница репозитория и базовая диагностика

**Files:**
- Create: `.gitignore`
- Create: `README.md`
- Create: `THIRD_PARTY_LICENSES.md`
- Create: `docs/baseline.md`
- Create: `tests/smoke/site-baseline.php`

**Interfaces:**
- Consumes: пустой удалённый репозиторий `https://github.com/heyInception/platejka_rework.git`.
- Produces: воспроизводимая инвентаризация среды и Git-граница, отслеживающая только тему, плагин, docs и tests.

- [ ] **Step 1: Зафиксировать тест, который проверяет ID/slug/шаблон страниц 24, 1873 и 22, активную тему, версии WP/PHP и наличие обязательных плагинов.**
- [ ] **Step 2: Выполнить `studio wp ... eval-file tests/smoke/site-baseline.php` и сохранить фактический вывод в `docs/baseline.md`; ожидается PASS для текущего тестового сайта.**
- [ ] **Step 3: Создать allowlist-style `.gitignore`, исключающий database, uploads, cache, сторонние плагины и темы, но включающий согласованные каталоги.**
- [ ] **Step 4: Добавить `THIRD_PARTY_LICENSES.md` с основанием распространения Google Sans и лицензиями остальных переносимых ресурсов.**
- [ ] **Step 5: Инициализировать Git, подключить remote и убедиться через `git status --short`, что старые темы, БД и uploads не индексируются.**
- [ ] **Step 6: Закоммитить `chore: establish rework repository baseline`.**

### Task 2: Инструменты качества и пустые каркасы поставки

**Files:**
- Create: `package.json`
- Create: `composer.json`
- Create: `phpunit.xml.dist`
- Create: `playwright.config.ts`
- Create: `themes/platejka_rework/blocks/.gitkeep`
- Create: `plugins/platejka-core/platejka-core.php`
- Create: `tests/bootstrap.php`

**Interfaces:**
- Consumes: Git-границу Task 1.
- Produces: команды `composer test`, `composer lint`, `npm run test:unit`, `npm run test:e2e`, `npm run lint`.

- [ ] **Step 1: Добавить падающий smoke-тест активации `platejka-core`, проверяющий константу `PLATEJKA_CORE_VERSION` и отсутствие PHP warnings.**
- [ ] **Step 2: Запустить smoke-тест; ожидается FAIL, потому что bootstrap ещё пуст.**
- [ ] **Step 3: Добавить минимальный защищённый bootstrap плагина и конфигурации Composer/Node/Playwright без бизнес-логики.**
- [ ] **Step 4: Запустить все пять команд; ожидается PASS или корректный zero-test exit только там, где набор тестов ещё пуст.**
- [ ] **Step 5: Закоммитить `chore: add project quality toolchain`.**

### Task 3: Версионирование ACF и снимок контента

**Files:**
- Create: `plugins/platejka-core/src/Acf/LocalJson.php`
- Create: `plugins/platejka-core/acf-json/*.json`
- Create: `tests/integration/acf-local-json.php`
- Create: `tests/fixtures/acf-page-baseline.json`
- Modify: `plugins/platejka-core/platejka-core.php`

**Interfaces:**
- Produces: `Platejka\Core\Acf\LocalJson::loadPaths(array $paths): array` и `savePath(string $path): string`; неизменяемый снимок ключей и заполненности полей трёх страниц.

- [ ] **Step 1: Экспортировать семь опубликованных ACF-групп и сделать снимок meta keys/типов значений страниц 24, 1873 и 22.**
- [ ] **Step 2: Написать тесты, требующие сохранения исходных group keys, field keys и загрузки JSON из плагина.**
- [ ] **Step 3: Запустить тесты; ожидается FAIL до регистрации фильтров путей.**
- [ ] **Step 4: Реализовать `LocalJson`, не меняя ключи и не выполняя автоматическую запись значений полей.**
- [ ] **Step 5: Активировать плагин на тестовом сайте, синхронизировать определения и повторить снимок; ожидается отсутствие изменений значений.**
- [ ] **Step 6: Закоммитить `feat: version existing ACF schemas`.**

### Task 4: Перенос курсов и расчётного контракта в `platejka-core`

**Files:**
- Create: `plugins/platejka-core/src/Rates/ExchangeRates.php`
- Create: `plugins/platejka-core/src/Calculator/Calculator.php`
- Create: `plugins/platejka-core/src/Rest/CalculationController.php`
- Create: `plugins/platejka-core/tests/ExchangeRatesTest.php`
- Create: `plugins/platejka-core/tests/CalculatorParityTest.php`
- Create: `tests/fixtures/calculator-parity.json`
- Modify: `plugins/platejka-core/platejka-core.php`

**Interfaces:**
- Produces: `platejka_core_get_exchange_rates(): array`, `platejka_core_calculate(string $currency, float $amount): array`, REST `POST /platejka/v1/calculation`.

- [ ] **Step 1: Снять из старой темы набор эталонных расчётов для CNY, USD и EUR, включая минимум, типовую и большую сумму, и сохранить fixture.**
- [ ] **Step 2: Написать parity-тесты результатов, тест невалидной валюты/суммы и тест поведения при недоступном свежем курсе.**
- [ ] **Step 3: Запустить тесты; ожидается FAIL до реализации сервисов.**
- [ ] **Step 4: Перенести существующие формулы, источник ЦБ и кеширование в плагин без изменения числовых результатов.**
- [ ] **Step 5: Добавить REST-контроллер с валидацией входа и без изменения публичного кеша при ошибке источника.**
- [ ] **Step 6: Запустить PHPUnit и сравнение со старой темой; ожидается полное совпадение fixture.**
- [ ] **Step 7: Закоммитить `feat: extract exchange rates and calculator core`.**

### Task 5: Основа блочной темы и система ассетов

**Files:**
- Replace: `themes/platejka_rework/style.css`
- Replace: `themes/platejka_rework/functions.php`
- Create: `themes/platejka_rework/theme.json`
- Create: `themes/platejka_rework/templates/index.html`
- Create: `themes/platejka_rework/templates/page.html`
- Create: `themes/platejka_rework/parts/header.html`
- Create: `themes/platejka_rework/parts/footer.html`
- Create: `themes/platejka_rework/inc/assets.php`
- Create: `themes/platejka_rework/assets/src/css/tokens.css`
- Create: `themes/platejka_rework/assets/src/css/base.css`
- Create: `themes/platejka_rework/assets/src/js/runtime.js`
- Create: `tests/theme/block-theme-structure.php`

**Interfaces:**
- Produces: `platejka_rework_register_section_asset(string $section, array $assets): void` и автоматическое подключение ассетов зарегистрированных блоков.

- [ ] **Step 1: Написать тест структуры, требующий block-theme detection, `theme.json` v3, templates/parts и отсутствие зависимости от PHP-шаблонов `_s`.**
- [ ] **Step 2: Запустить тест; ожидается FAIL для текущей классической заготовки.**
- [ ] **Step 3: Заменить `_s`-каркас минимальной блочной темой и перенести общие дизайн-токены без копирования тестового контента.**
- [ ] **Step 4: Реализовать реестр секционных CSS/JS; тест должен доказать отсутствие ассетов секции на странице без её блока.**
- [ ] **Step 5: Проверить тему через `studio wp --path C:\Users\Inception\Studio\platejka theme activate platejka_rework` на тестовом сайте и убедиться в отсутствии PHP/JS ошибок.**
- [ ] **Step 6: Закоммитить `feat: establish block theme foundation`.**

### Task 6: Общие шапка, подвал и меню

**Files:**
- Create: `themes/platejka_rework/blocks/site-header/block.json`
- Create: `themes/platejka_rework/blocks/site-header/render.php`
- Create: `themes/platejka_rework/blocks/site-footer/block.json`
- Create: `themes/platejka_rework/blocks/site-footer/render.php`
- Create: `themes/platejka_rework/assets/src/css/components/header.css`
- Create: `themes/platejka_rework/assets/src/css/components/footer.css`
- Create: `themes/platejka_rework/assets/src/js/components/header.js`
- Create: `tests/e2e/global-navigation.spec.ts`
- Modify: `themes/platejka_rework/parts/header.html`
- Modify: `themes/platejka_rework/parts/footer.html`

**Interfaces:**
- Consumes: существующие menu terms и ACF-группу «Контакты».
- Produces: серверные блоки `platejka/site-header` и `platejka/site-footer` без hardcoded рабочих URL.

- [ ] **Step 1: Написать E2E-тесты desktop/mobile меню, клавиатуры, Escape, focus return и отсутствия `href="#"`.**
- [ ] **Step 2: Запустить тесты; ожидается FAIL до реализации блоков.**
- [ ] **Step 3: Перенести HTML/CSS/JS шапки и подвала, заменив ссылки существующими меню и контактными ACF-значениями.**
- [ ] **Step 4: Добавить идемпотентную JS-инициализацию и reduced-motion режим.**
- [ ] **Step 5: Запустить E2E на 1440, 768 и 390 px; ожидается PASS и отсутствие горизонтального overflow.**
- [ ] **Step 6: Закоммитить `feat: add data-driven global header and footer`.**

### Task 7: Общие формы и визуальный калькулятор

**Files:**
- Create: `themes/platejka_rework/blocks/call/block.json`
- Create: `themes/platejka_rework/blocks/call/render.php`
- Create: `themes/platejka_rework/blocks/calculator/block.json`
- Create: `themes/platejka_rework/blocks/calculator/render.php`
- Create: `themes/platejka_rework/assets/src/css/components/forms.css`
- Create: `themes/platejka_rework/assets/src/css/components/calculator.css`
- Create: `themes/platejka_rework/assets/src/js/components/calculator.js`
- Create: `plugins/platejka-core/src/Integrations/ContactForm7.php`
- Create: `tests/e2e/forms-calculator.spec.ts`

**Interfaces:**
- Consumes: Task 4 calculation endpoint and существующие CF7 form IDs.
- Produces: `platejka/calculator`, `platejka/call` с вариантами `default|about`; скрытое поле CF7 `platejka_calculation_summary`.

- [ ] **Step 1: Инвентаризировать текущие CF7 IDs, поля, mail settings и amoCRM hooks без изменения конфигурации.**
- [ ] **Step 2: Написать E2E-тесты паритета расчёта, модального окна, передачи summary, валидации и успешного CF7-события.**
- [ ] **Step 3: Запустить тесты; ожидается FAIL до реализации компонентов.**
- [ ] **Step 4: Перенести представление калькулятора и форм, удалив hardcoded rates и демонстрационную отправку.**
- [ ] **Step 5: Реализовать CF7 integration adapter; не изменять существующую amoCRM-цепочку.**
- [ ] **Step 6: Прогнать тесты для CNY/USD/EUR и клавиатурного управления на 360 и 1440 px.**
- [ ] **Step 7: Закоммитить `feat: integrate calculator and Contact Form 7`.**

### Task 8: Контракт сопоставления секций и ACF

**Files:**
- Create: `docs/section-map.md`
- Create: `themes/platejka_rework/inc/content-map.php`
- Create: `tests/theme/content-map.php`

**Interfaces:**
- Consumes: предоставленную владельцем таблицу «секция → старый блок/ACF».
- Produces: `platejka_rework_get_section_data(string $section, int $postId): array` и `platejka_rework_section_is_visible(string $section, int $postId): bool`.

- [ ] **Step 1: Заполнить для каждой секции страницу, порядок, старый template-part, toggle field, ACF keys, правила пустого значения и тип изображения.**
- [ ] **Step 2: Получить явное подтверждение владельца для всех строк главной, Китая и «О компании»; незаполненная строка означает disabled.**
- [ ] **Step 3: Написать тесты карты на существующих fixtures, включая пустые поля и запрет HTML-demo fallback.**
- [ ] **Step 4: Реализовать read-only adapter существующих полей без переименования meta keys.**
- [ ] **Step 5: Запустить тесты и сравнить значения с baseline Task 3; ожидается неизменность БД.**
- [ ] **Step 6: Закоммитить `feat: define legacy ACF section mapping`.**

### Task 9: Главная страница

**Files:**
- Create: `themes/platejka_rework/templates/front-page.html`
- Create: `themes/platejka_rework/blocks/hero/block.json`
- Create: `themes/platejka_rework/blocks/hero/render.php`
- Create: `themes/platejka_rework/blocks/shipments/{block.json,render.php}`
- Create: `themes/platejka_rework/blocks/infrastructure/{block.json,render.php}`
- Create: `themes/platejka_rework/blocks/destinations/{block.json,render.php}`
- Create: `themes/platejka_rework/blocks/serves/{block.json,render.php}`
- Create: `themes/platejka_rework/blocks/cases/{block.json,render.php}`
- Create: `themes/platejka_rework/blocks/review/{block.json,render.php}`
- Create: `themes/platejka_rework/blocks/compliance/{block.json,render.php}`
- Create: `themes/platejka_rework/assets/src/css/sections/{hero,shipments,infrastructure,destinations,serves,cases,review,compliance}.css`
- Create: `themes/platejka_rework/assets/src/js/sections/{hero,destinations,review}.js`
- Create: `tests/e2e/home.spec.ts`

**Interfaces:**
- Consumes: Task 8 mapping for page ID 24 plus global blocks Tasks 6–7.
- Produces: завершённый locked template главной без тестового контента.

- [ ] **Step 1: Написать content/SEO/interaction тесты страницы 24 и visual snapshots пяти контрольных ширин.**
- [ ] **Step 2: Реализовывать подтверждённые Task 8 секции из перечисленного набора по одной: сначала failing fixture test, затем render/CSS/JS, затем component screenshot; неподтверждённые блоки регистрировать нельзя.**
- [ ] **Step 3: Собрать `front-page.html` в подтверждённом порядке и заблокировать перестановку обязательных блоков.**
- [ ] **Step 4: Проверить ACF toggles, пустые значения, длинный текст и отсутствие demo copy.**
- [ ] **Step 5: Выполнить полный E2E/Lighthouse; ожидаются согласованные пороги и визуальное подтверждение владельца.**
- [ ] **Step 6: Закоммитить `feat: build redesigned home page`.**

### Task 10: Страница «Платежи в Китай»

**Files:**
- Create: `themes/platejka_rework/templates/page-china.html`
- Reuse: `themes/platejka_rework/blocks/hero/` with variant `china`
- Reuse: `themes/platejka_rework/blocks/review/` with variant `china`
- Create: `themes/platejka_rework/blocks/problems/{block.json,render.php}`
- Create: `themes/platejka_rework/blocks/work/{block.json,render.php}`
- Create: `themes/platejka_rework/blocks/financial/{block.json,render.php}`
- Create: `themes/platejka_rework/blocks/protection/{block.json,render.php}`
- Create: `themes/platejka_rework/blocks/guarantees/{block.json,render.php}`
- Create: `themes/platejka_rework/blocks/documents/{block.json,render.php}`
- Create: `themes/platejka_rework/blocks/seo/{block.json,render.php}`
- Create: `themes/platejka_rework/blocks/faq/{block.json,render.php}`
- Create: `themes/platejka_rework/assets/src/css/sections/{problems,work,financial,protection,guarantees,documents,seo,faq}.css`
- Create: `themes/platejka_rework/assets/src/js/sections/{problems,documents,faq}.js`
- Create: `tests/e2e/china.spec.ts`

**Interfaces:**
- Consumes: Task 8 mapping for page ID 1873 and shared calculator/form.
- Produces: locked template `/china` с сохранёнными Yoast/ACF и рабочим расчётом.

- [ ] **Step 1: Написать content/SEO/calculator/FAQ/dialog tests и visual snapshots пяти ширин.**
- [ ] **Step 2: Реализовывать подтверждённые Task 8 секции из перечисленного набора по TDD-циклу, не перенося `seo.html` как источник текста; неподтверждённые блоки регистрировать нельзя.**
- [ ] **Step 3: Собрать `page-china.html`; все SEO-тексты выводить из сопоставленных ACF/контента страницы.**
- [ ] **Step 4: Сравнить расчёты и формы со старой страницей на parity fixtures.**
- [ ] **Step 5: Выполнить E2E/Lighthouse и получить визуальное подтверждение владельца.**
- [ ] **Step 6: Закоммитить `feat: build redesigned China payments page`.**

### Task 11: Страница «О компании»

**Files:**
- Create: `themes/platejka_rework/templates/page-o-kompanii.html`
- Reuse: `themes/platejka_rework/blocks/call/` with variant `about`
- Create: `themes/platejka_rework/blocks/about-hero/{block.json,render.php}`
- Create: `themes/platejka_rework/blocks/about/{block.json,render.php}`
- Create: `themes/platejka_rework/blocks/developing/{block.json,render.php}`
- Create: `themes/platejka_rework/blocks/employees/{block.json,render.php}`
- Create: `themes/platejka_rework/blocks/exhibitions/{block.json,render.php}`
- Create: `themes/platejka_rework/blocks/location/{block.json,render.php}`
- Create: `themes/platejka_rework/assets/src/css/sections/{about-hero,about,developing,employees,exhibitions,location}.css`
- Create: `themes/platejka_rework/assets/src/js/sections/{about-hero,about,developing,employees,location}.js`
- Create: `tests/e2e/about.spec.ts`

**Interfaces:**
- Consumes: Task 8 mapping for page ID 22 and global blocks.
- Produces: locked template `/o-kompanii` с сотрудниками, историей, документами и контактами из ACF.

- [ ] **Step 1: Написать content/SEO/slider/dialog tests и visual snapshots пяти ширин.**
- [ ] **Step 2: Реализовывать подтверждённые Task 8 секции из перечисленного набора по TDD-циклу, сохраняя порядок и ACF toggles; неподтверждённые блоки регистрировать нельзя.**
- [ ] **Step 3: Собрать `page-o-kompanii.html` и проверить отсутствие повторных JS handlers в слайдерах/диалогах.**
- [ ] **Step 4: Проверить пустые фото, длинные должности, документы без файла и внешние ссылки.**
- [ ] **Step 5: Выполнить E2E/Lighthouse и получить визуальное подтверждение владельца.**
- [ ] **Step 6: Закоммитить `feat: build redesigned company page`.**

### Task 12: Общая регрессия, производительность и готовность тестового релиза

**Files:**
- Create: `tests/e2e/regression.spec.ts`
- Create: `tests/performance/budgets.json`
- Create: `docs/test-report.md`
- Create: `docs/release-checklist.md`
- Modify: `README.md`

**Interfaces:**
- Consumes: все готовые компоненты и страницы Tasks 1–11.
- Produces: проверяемый test-site release candidate; продакшен-деплой не выполняется.

- [ ] **Step 1: Добавить regression suite для трёх URL, общих компонентов, canonical/Yoast, PHP log и JS console.**
- [ ] **Step 2: Добавить performance budgets: mobile Performance >= 90; Accessibility/Best Practices/SEO >= 95; CLS без заметного сдвига.**
- [ ] **Step 3: Прогнать unit, integration, E2E, visual и Lighthouse на всех контрольных ширинах/браузерах.**
- [ ] **Step 4: Исправить только подтверждённые отклонения; обновлять visual baseline лишь после явного визуального одобрения.**
- [ ] **Step 5: Задокументировать известные ограничения остальных страниц и очередь их дальнейшего переноса.**
- [ ] **Step 6: Создать тег тестового релиза только после чистого прогона и закоммитить `docs: record rework test release readiness`.**

## Исполнение по этапам

- Milestone 1 «Общие компоненты»: Tasks 1–7.
- Блокирующий вход: Task 8 и подтверждённая владельцем карта секций.
- Milestone 2 «Главная»: Task 9.
- Milestone 3 «Платежи в Китай»: Task 10.
- Milestone 4 «О компании»: Task 11.
- Общая приёмка тестового сайта: Task 12.

Продакшен-публикация, перенос остальных страниц и аудит удаления плагинов не входят в этот план и оформляются отдельными планами после приёмки четырёх milestones.
