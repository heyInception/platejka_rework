# Карта секций `platejka_rework`

Источник и приоритет: утверждённый владельцем файл `themes/platejka_rework/section.xlsx` от 2026-10-02. Эта карта заменяет предварительные списки секций в плане. Порядок фиксирован. Отсутствующая здесь строка отключена.

## Правила источников

- `legacy_acf` — read-only чтение существующих meta names через ACF; field keys ниже не переименовываются.
- `legacy_template` — владелец указал старый блок, но его текст уже был статическим в named template; до отдельной миграции он считается явным существующим источником, а не HTML fallback.
- `legacy_post` — заголовок/`post_content` существующей страницы.
- `design_html` — только строка, где владелец явно указал отсутствие старого блока или полностью новый блок. Указанный HTML экспорта является утверждённым начальным текстом. Другого автоматического fallback нет.
- `planned_acf` — владелец запросил новый редактируемый ACF-блок. Field/meta keys ещё не согласованы и здесь не придумываются; до появления схемы и значений секция disabled.
- `local_acf` — владелец явно разрешил новую редактируемую секцию; стабильные local-field keys и безопасные значения по умолчанию определены вместе с её визуальным блоком, без записи meta в страницу.

Для `legacy_acf` секция скрывается при выключенном toggle, если он указан; без toggle — когда все перечисленные значения пусты. `design_html` видим благодаря явному owner approval. `legacy_template` видим как существующий mapped source. Медиа из ACF остаются редактируемыми; декоративные медиа из export HTML относятся к теме.

## Точные наборы существующих ACF-полей

| Набор | Meta name → immutable field key | Медиа |
| --- | --- | --- |
| `hero` | `zaglovok_hero` → `field_66051d3d9621e`; `podzaglovok_hero` → `field_66c7356f59226`; `tekst_hero` → `field_660097aea1c13`; `pereklyuchatel_hero` → `field_6790c6855c315`; `video_hero` → `field_6790c6ee5c316`; `izobrazhenie` → `field_6790c7105c317`; `izobrazhenie_m_hero` → `field_6790c7205c318`; `ssylka_hero` → `field_660097eda1c14`; `priemushhestva_ach` → `field_6600997fcc8e6` | `video_hero`: file; `izobrazhenie`, `izobrazhenie_m_hero`, `priemushhestva_ach[].izobrazheniya`: image |
| `service` | `vklyuchit_blok_service` → `field_66058b55c5841`; `zagolovok_service` → `field_66009b6944e4e`; `podzagolovok_service` → `field_66c7353832cc2`; `uslugi_service` → `field_66009b8844e4f` | none |
| `work` | `work_section` → `field_6790b56810062` | `work_section[].elementy[].izobrazhenie`: image |
| `calculator` | `vklyuchit_kalkulyator` → `field_662923f0aa5ff`; `calc_country` → `field_66c74a32a6868` | none |
| `features` | `features` → `field_67901d0ed26ae` | `features.izobrazhenie`: image |
| `destinations` | `vklyuchit_blok_destinations` → `field_679016442a75d`; `zagolovok_destinations` → `field_6790163a2a75c`; `podzagolovok_destinations` → `field_6790167b2a75e`; `napravleniya_destinations` → `field_6790168c2a75f` | `napravleniya_destinations[].izobrazhenie`: image |
| `advantages` | `advantages` → `field_67901ae8b64ba` | `advantages.elementy[].izobrazhenie`: image |
| `rev` | `vklyuchit_blok_rev` → `field_66058b6dc5842`; `zagolovok_reev` → `field_66009d32fec37`; `izobrazheniya_reev` → `field_6790c497eeee4`; `klienty_rev` → `field_66009d41fec38` | `izobrazheniya_reev[].izobrazhenie`, `klienty_rev[].izobrazheie`: image |
| `faq` | `repeater_column_1` → `field_65a95b8fc77c8`; `repeater_column_2` → `field_65a95bcbc77c9` | none |

## Главная — page ID 24, slug `glavnaya`

| # | Новая секция | Источник | Template/default | Toggle | ACF | Пустое значение |
| ---: | --- | --- | --- | --- | --- | --- |
| 1 | `hero-main` | `legacy_acf` (`hero`) | `template-parts/pages/hero.php` | none (`pereklyuchatel_hero` меняет layout, не visibility) | set `hero` | hide if all empty |
| 2 | `about` | `design_html` | `sections/about/about.html` | none | planned later, no keys assigned | approved HTML default |
| 3 | `shipments` | `legacy_acf` (`service`) | `template-parts/pages/service-block.php` | `vklyuchit_blok_service` | set `service` | hide if toggle off |
| 4 | `guarantees` | `legacy_template` | `template-parts/pages/guarantees.php` | none | none | mapped static source |
| 5 | `documents` | `design_html` | `sections/documents/documents.html` | none | planned later, no keys assigned | approved HTML default |
| 6 | `compliance` | `design_html` | `sections/compliance/compliance.html` | none | planned later, no keys assigned | approved HTML default |
| 7 | `review-main` | `local_acf` (`reviews`) | `sections/review-main/review-main.html` | `home_review_main_enabled` | `home_review_main_enabled`, `home_review_main_eyebrow`, `home_review_main_title`, `home_review_main_lead` | approved HTML defaults until an editor saves overrides |
| 8 | `work` | `legacy_acf` (`work`) | `template-parts/pages/work.php` | none | set `work` | hide if empty |
| 9 | `calculator` | `legacy_acf` (`calc`) | `template-parts/pages/calc.php` | `vklyuchit_kalkulyator` | set `calculator` | hide if toggle off |
| 10 | `with-us` | `legacy_acf` (`features`) | `template-parts/pages/features.php` | none | set `features` | hide if empty |
| 11 | `destinations` | `legacy_acf` | `template-parts/pages/destinations.php` | `vklyuchit_blok_destinations` | set `destinations` | hide if toggle off |
| 12 | `seo` | `design_html` | `sections/seo/seo.html` | none | planned later, no keys assigned | approved HTML default |
| 13 | `problems` | `design_html` | `sections/problems/problems.html` | none | planned later, no keys assigned | approved HTML default |
| 14 | `serves` | `legacy_acf` (`advantages`) | `template-parts/pages/advantages.php` | none | set `advantages` | hide if empty |
| 15 | `cases` | `legacy_acf` (`rev`) | `template-parts/pages/rev.php` | `vklyuchit_blok_rev` | set `rev` | hide if toggle off |
| 16 | `table` | `legacy_template` (`compare`) | `template-parts/pages/compare.php` | none | none | mapped static source |
| 17 | `faq` | `legacy_acf` | `template-parts/pages/faq.php` | none | set `faq` | hide if empty |
| 18 | `call` | `local_acf` | shared `sections/call/call.html` component | `home_call_enabled` | `home_call_enabled`, `home_call_title` | approved HTML defaults until an editor saves overrides |

## Платежи в Китай — page ID 1873, slug `china`

| # | Новая секция | Источник | Template/default | Toggle | ACF | Пустое значение |
| ---: | --- | --- | --- | --- | --- | --- |
| 1 | `hero-main` | `legacy_acf` (`hero`) | `template-parts/pages/hero.php` | none | set `hero` | hide if all empty |
| 2 | `about` | `design_html` | `sections/about/about.html` | none | planned later, no keys assigned | approved HTML default |
| 3 | `shipments` | `design_html` | `sections/shipments/shipments.html` | none | planned later, no keys assigned | approved HTML default |
| 4 | `guarantees` | `legacy_template` | `template-parts/pages/guarantees.php` | none | none | mapped static source |
| 5 | `documents` | `design_html` | `sections/documents/documents.html` | none | planned later, no keys assigned | approved HTML default |
| 6 | `protection` | `design_html` | `sections/protection/protection.html` | none | planned later, no keys assigned | approved HTML default |
| 7 | `review` | `design_html` | `sections/review/review.html` | none | planned later, no keys assigned | approved HTML default |
| 8 | `work` | `design_html` | `sections/work/work.html` | none | planned later, no keys assigned | approved HTML default |
| 9 | `problems` | `design_html` | `sections/problems/problems.html` | none | planned later, no keys assigned | approved HTML default |
| 10 | `calculator` | `design_html` | `sections/calculator/calculator.html` | none | shared calculation data later | approved HTML default |
| 11 | `seo` | `legacy_post` | `template-parts/pages/seo.php` | none | post title/content | hide if both empty |
| 12 | `faq` | `legacy_acf` | `template-parts/pages/faq.php` | none | set `faq` | hide if empty |
| 13 | `call` | `local_acf` | shared `sections/call/call.html` component | `china_call_enabled` | `china_call_enabled`, `china_call_title` | approved HTML defaults until an editor saves overrides |

## О компании — page ID 22, slug `o-kompanii`

Владелец явно пометил первые восемь строк как полностью новые блоки с текстом из соответствующего HTML. Task 11 добавляет для них и расширенного `call-about` стабильные локальные ACF-поля: исходный HTML остаётся явным значением по умолчанию, а сохранённые редактором значения переопределяют только соответствующие текстовые, медиа- и ссылочные слоты без записи defaults в БД.

| # | Новая секция | Источник | Template/default | Toggle | ACF | Пустое значение |
| ---: | --- | --- | --- | --- | --- | --- |
| 1 | `about-hero` | `design_html` + local ACF override | `sections/about-hero/about-hero.html` | none | `about_about_hero_*` (`field_platejka_about_hero_*_v1`) | approved HTML defaults until editor override |
| 2 | `location` | `design_html` + local ACF override | `sections/location/location.html` | none | `about_location_*` (`field_platejka_about_location_*_v1`) | approved HTML defaults until editor override |
| 3 | `review-main` | `design_html` + local ACF override | `sections/review-main/review-main.html` | none | `about_review_main_*` (`field_platejka_about_review_*_v1`) | approved HTML defaults until editor override |
| 4 | `infrastructure` | `design_html` + local ACF override | `sections/infrastructure/infrastructure.html` | none | `about_infrastructure_*` (`field_platejka_about_infrastructure_*_v1`) | approved HTML defaults until editor override |
| 5 | `financial` | `design_html` + local ACF override | `sections/financial/financial.html` | none | `about_financial_*` (`field_platejka_about_financial_*_v1`) | approved HTML defaults; empty CTA URL removes fake link |
| 6 | `employees` | `design_html` + local ACF override | `sections/employees/employees.html` | none | `about_employees_*`, `about_team_photo` (`field_platejka_about_*_v1`) | approved HTML defaults until editor override |
| 7 | `exhibitions` | `design_html` + local ACF override | `sections/exhibitions/exhibitions.html` | none | `about_exhibitions_*` (`field_platejka_about_exhibitions_*_v1`) | approved HTML defaults until editor override |
| 8 | `developing` | `design_html` + local ACF override | `sections/developing/developing.html` | none | `about_developing_title` → `field_platejka_about_developing_title_v1` | approved HTML default until editor override |
| 9 | `call-about` | `local_acf` (shared form + About additions) | `sections/call/call-about.html` | none | `about_call_*` (`field_platejka_about_call_*_v1`) | approved HTML defaults; empty social URLs render disabled, never `href="#"` |

## Runtime API

- `platejka_rework_get_section_data(string $section, int $postId): array` returns only a page-approved source plus its read-only legacy values. Unknown page/section returns `[]`.
- `platejka_rework_section_is_visible(string $section, int $postId): bool` applies the rules above.
- Neither function writes ACF, post meta, page content, cache, or the supplied export.
