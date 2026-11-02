# Импорт отзывов Яндекса

Общий набор отзывов доступен в админке WordPress в меню **Отзывы**. Новые импортированные записи получают статус согласно настройке **Настройки → Отзывы**: «На утверждении» или «Опубликовано». Повторный импорт обновляет текст и оценку по паре `source + source_id`, сохраняя выбранный редактором статус.

Python 3.10+ и WP-CLI нужны на машине, которая выполняет импорт. Скрипт выгружает девять отзывов с наиболее поздним `updatedTime` из публичной страницы Яндекс Карт. Это время последнего обновления отзыва, которое Яндекс предоставляет в данных страницы.

```powershell
py wp-content/plugins/platejka-core/tools/yandex_reviews.py --output wp-content/plugins/platejka-core/tools/yandex-reviews-export.json
studio wp --path C:\Users\Inception\Studio\platejka platejka reviews import C:\Users\Inception\Studio\platejka\wp-content\plugins\platejka-core\tools\yandex-reviews-export.json
py wp-content/plugins/platejka-core/tools/review_counts.py --output wp-content/plugins/platejka-core/tools/review-counts.json
studio wp --path C:\Users\Inception\Studio\platejka platejka reviews import-counts C:\Users\Inception\Studio\platejka\wp-content\plugins\platejka-core\tools\review-counts.json
```

Для другого количества передайте `--limit N`. На сервере с обычным WP-CLI используйте `wp platejka reviews import <путь-к-json>` вместо `studio wp`.

Обе секции, `review` и `review-main`, показывают до девяти опубликованных записей из этого набора. До публикации первой записи продолжают показываться прежние карточки из настроек секций. Видеоотзывы и рейтинги площадок остаются в прежних полях.

## Регулярный запуск на SprintHost

Перенесите текущую версию плагина на сайт. По SSH проверьте `which wp`, `which python` и путь к корню WordPress (каталог с `wp-config.php`). Если WP-CLI не установлен, его нужно установить в аккаунте хостинга; путь к исполняемому файлу можно передать через `WP_BIN`. Скриптам Python нужны только модули стандартной библиотеки.

Запустите один раз вручную, подставив свой логин и домен:

```bash
WP_ROOT=/home/username/domains/domain.ru/public_html \
  /bin/bash /home/username/domains/domain.ru/public_html/wp-content/plugins/platejka-core/tools/sync_reviews.sh
```

При отличающихся путях к программам задайте `PYTHON_BIN=/путь/к/python` и `WP_BIN=/путь/к/wp`. Скрипт сначала получает оба JSON во временный каталог, а затем импортирует данные в WordPress. При ошибке загрузки импорт не начинается; временные файлы удаляются после завершения.

После успешного ручного запуска добавьте в панели SprintHost → **Сайты → Планировщик Cron** ежедневное задание (пример для 03:30 по времени сервера):

```cron
30 3 * * * /bin/bash /home/username/domains/domain.ru/public_html/wp-content/plugins/platejka-core/tools/sync_reviews.sh
```

Если WordPress находится вне обычного `public_html` или пути к Python/WP-CLI отличаются, укажите переменные `WP_ROOT`, `PYTHON_BIN`, `WP_BIN` перед `/bin/bash` и в задании Cron. Запросы к площадкам выполняются только при запуске сценария, не при открытии страниц сайта.

Счётчики рейтингов обновляются отдельной парой команд выше. Если площадка не ответит или изменит формат страницы, скрипт завершится с ошибкой, а последние сохранённые числа останутся на сайте. Для 2ГИС отсутствие рейтинга и счётчика у карточки сейчас трактуется как ноль отзывов.
