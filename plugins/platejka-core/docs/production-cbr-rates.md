# Курсы ЦБ РФ через cbrapi: настройка продакшена

Калькуляторы читают курсы из:

```text
wp-content/uploads/platejka/exchange-rates.json
```

Если рабочего кеша нет или JSON повреждён, плагин `platejka-core` использует поставляемый seed-файл `plugins/platejka-core/data/exchange-rates.json`. Обновление страницы сайта не запускает Python и не обращается к ЦБ РФ.

Если на production уже работает почасовая задача со скриптом старой темы, сначала убедитесь, что она успешно обновляет указанный файл в uploads. При развёртывании новой версии переключите путь запускаемой программы на `plugins/platejka-core/tools/update_cbr_rates.py`. Формат файла и место записи не меняются; одновременно запускать обе задачи не нужно.

## 1. Требования

- Python 3.10 или новее;
- доступ к cron;
- локальная копия <https://github.com/mbk-dev/cbrapi>;
- пользователь cron должен иметь право записи в `wp-content/uploads/platejka`, а PHP — право чтения файла.

Ниже замените `/var/www/platejka` на каталог WordPress, а `/opt/platejka-cbrapi` — на каталог клиента.

## 2. Установка Python-клиента

```bash
git clone https://github.com/mbk-dev/cbrapi.git /opt/platejka-cbrapi
python3 -m venv /opt/platejka-cbr-venv
/opt/platejka-cbr-venv/bin/python -m pip install --upgrade pip
/opt/platejka-cbr-venv/bin/python -m pip install -e /opt/platejka-cbrapi
```

Если репозиторий уже развёрнут из поставки, шаг `git clone` не нужен.

## 3. Первый запуск

```bash
/opt/platejka-cbr-venv/bin/python \
  /var/www/platejka/wp-content/plugins/platejka-core/tools/update_cbr_rates.py \
  --client-path /opt/platejka-cbrapi \
  --output /var/www/platejka/wp-content/uploads/platejka/exchange-rates.json
```

Успешный запуск выводит дату курса. Проверьте файл:

```bash
/opt/platejka-cbr-venv/bin/python -m json.tool \
  /var/www/platejka/wp-content/uploads/platejka/exchange-rates.json
```

В `rates` должны присутствовать положительные значения `USD`, `EUR` и `CNY`, а `base` должен быть равен `RUB`.

## 4. Почасовой cron

Откройте crontab пользователя, который может записывать в uploads:

```bash
crontab -e
```

Добавьте одну строку:

```cron
7 * * * * flock -n /tmp/platejka-cbr-rates.lock /opt/platejka-cbr-venv/bin/python /var/www/platejka/wp-content/plugins/platejka-core/tools/update_cbr_rates.py --client-path /opt/platejka-cbrapi --output /var/www/platejka/wp-content/uploads/platejka/exchange-rates.json >> /var/www/platejka/wp-content/uploads/platejka/update.log 2>&1
```

Задача запускается на седьмой минуте каждого часа. Скрипт ищет ближайшую дату, для которой ЦБ вернул все три фактические котировки, затем атомарно заменяет JSON и устанавливает права `0644`. При ошибке существующий рабочий кеш остаётся без изменений; подробности попадут в `update.log`.

## 5. Проверка после развёртывания

1. Запустите команду из раздела «Первый запуск» вручную.
2. Убедитесь, что JSON читается и содержит три курса.
3. Откройте страницу с калькулятором перевода и проверьте USD, EUR и CNY.
4. Проверьте границу: для 4 999 комиссия фиксированная, для 5 000 — 1%.
5. Проверьте калькулятор покупки/продажи: +5% для покупки и +2% для продажи.
