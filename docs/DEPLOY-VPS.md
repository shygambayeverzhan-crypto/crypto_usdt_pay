# Развёртывание MVP на VPS

Стартовая конфигурация: Linux VPS с Docker Engine и Compose v2, домен с DNS на VPS, открытые 80/443 и ограниченный SSH. PostgreSQL и Redis не публикуют порты наружу. Используйте отдельные БД, домены и ботов для тестовой и основной сети.

## Подготовка

```sh
git clone https://github.com/shygambayeverzhan-crypto/crypto_usdt_pay.git
cd crypto_usdt_pay
git checkout codex/trust-wallet-mvp
cp .env.example .env
chmod 600 .env
```

В приватном `.env` задайте:

| Переменная | Значение |
|---|---|
| APP_ENV / APP_DEBUG | production / false |
| APP_DOMAIN / APP_URL | ваш домен / https://ваш-домен |
| DB_DATABASE / DB_USERNAME / DB_PASSWORD | имя БД, отдельный пользователь и случайный пароль |
| ADMIN_NAME / ADMIN_EMAIL / ADMIN_PASSWORD | первый владелец; пароль минимум 12 символов |
| PAYMENT_RECEIVE_ADDRESS | публичный адрес получения TRON из вашего Trust Wallet |
| TRONGRID_NETWORK | сначала nile; mainnet только после проверки на тестовой сети |
| TRONGRID_BASE_URL | https://nile.trongrid.io или https://api.trongrid.io строго по сети |
| TRONGRID_USDT_CONTRACT | контракт тестового TRC-20 в Nile; в mainnet — TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t |
| TRONGRID_API_KEY | собственный ключ чтения API провайдера |
| TRONSCAN_BASE_URL | https://nile.tronscan.org либо https://tronscan.org |
| TELEGRAM_BOT_TOKEN / TELEGRAM_BOT_NAME | токен BotFather и имя вашего бота |
| TELEGRAM_WEBHOOK_URL | https://ваш-домен/telegram/webhook |
| TELEGRAM_WEBHOOK_SECRET | случайная строка из A-Z, a-z, 0-9, _ и -, 32+ символа |
| PAYMENT_MIN_CONFIRMATIONS | 20 или выше |
| PAYMENT_CALLBACKS_ENABLED | false для MVP; используйте API статуса |
| ALLOW_REGISTRATION | false; селлеров создаёт владелец |
| SESSION_SECURE_COOKIE | true |

`TRONGRID_USDT_CONTRACT` — адрес контракта токена, **не** адрес получения денег. Не копируйте адрес из тестов в настройки. Приватные ключи и seed-фразы остаются вне сервера. В production-образ не включён старый `wallet-signer`.

Существующий код callbacks сохранён, но отключён: включать его до отдельной проверки сетевых ограничений SSRF/DNS rebinding и повторной доставки нельзя. Это не требуется для API-опроса или Telegram.

## Первый запуск

```sh
docker compose build
docker compose up -d postgres redis
docker compose run --rm app php artisan key:generate --show
```

Скопируйте полученное значение в `APP_KEY` внутри `.env`. Сохраните этот ключ отдельно в защищённой резервной копии; не меняйте его при обновлениях.

```sh
docker compose run --rm app php artisan app:install
docker compose run --rm app php artisan app:install-settings
docker compose run --rm app php artisan payments:configure-address owner@example.com
docker compose up -d
docker compose exec app php artisan telegram:set-webhook
```

`owner@example.com` замените на ваш ADMIN_EMAIL. Проверьте название команды настроек через `php artisan list`, если обновляете старую версию. После создания владельца удалите `ADMIN_PASSWORD` из `.env` и пересоздайте app/worker/scheduler: `docker compose up -d --force-recreate app worker scheduler`. Команда установки не сбрасывает БД.

Войдите через `/login`, включите двухфакторную аутентификацию в настройках. В `/admin/users` создайте селлера с ролью `user`, а не `admin`. Селлер создаёт магазин, затем счёт. Все новые счета используют общий адрес владельца. Владелец видит общую статистику и все счета; селлер — свои. Для проверки email настройте SMTP; не используйте почтовый log-драйвер в рабочем сценарии восстановления паролей.

## Обязательная приёмка

1. Создать два счёта с одинаковой ценой от разных селлеров: суммы должны отличаться, адрес совпадать.
2. Повторить API-запрос с тем же `external_invoice_id`: должен вернуться прежний счёт. Изменённая цена должна отклоняться.
3. В Nile отправить тестовый токен точно по счёту. До завершения сетевой проверки `paid` не появляется. После нужных подтверждений обновляются кабинет, публичная страница, `/status` и уведомление клиента.
4. Остановить scheduler, сделать своевременный тестовый перевод, дождаться истечения счёта и снова запустить scheduler. Платёж должен восстановиться по времени блока.
5. Проверить чужой счёт, блокировку селлера, webhook без секрета, неправильный токен/сумму/сеть, недоплату и повторную обработку txid. Они не должны давать ложный `paid`.
6. Проверить резервное копирование и восстановление на отдельной БД. Только затем включать mainnet на **отдельной новой БД** и проводить согласованную контрольную оплату. Здесь реальные переводы не выполнялись.

## Наблюдение и обновление

```sh
docker compose ps
docker compose logs --tail=100 scheduler worker app
docker compose exec app php artisan queue:failed
docker compose exec app php artisan payments:reconcile
```

`/up` проверяет Laravel, но не свежесть блокчейна. Проверяйте код завершения reconcile, ошибки/429 и возраст `payments:last_success` в cache; отсутствие успешного прохода более 5 минут — повод остановить выдачу новых счетов и разобраться. После падения процесса сначала убедитесь, что он остановлен, и только затем при необходимости выполните `php artisan schedule:clear-cache`.

Перед обновлением остановите scheduler/worker, сделайте backup, соберите образ, выполните `docker compose run --rm app php artisan migrate --force`, затем пересоздайте процессы. Для отката приложения используйте предыдущий commit/image; миграции платёжной истории назад не откатывайте автоматически.

## Резервное копирование

```sh
mkdir -p backups
chmod 700 backups
docker compose exec -T postgres sh -c 'pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB" -Fc' > backups/payments.dump
```

Храните зашифрованные копии PostgreSQL и приватного `.env` вне VPS. Не включайте их в Git. Проверяйте восстановление отдельно:

```sh
# Только на отдельной пустой восстановительной установке, не поверх рабочей БД:
docker compose exec -T postgres sh -c 'pg_restore -U "$POSTGRES_USER" -d "$POSTGRES_DB" --no-owner' < backups/payments.dump
```

После восстановления устаревшего backup НЕ выдавайте новые суммы на старом адресе: потерянные резервации могли уже быть оплачены. Сначала полная сверка и восстановление резервов либо переход на новый публичный адрес. История резервирования сумм является частью платёжных данных.

## Существующая БД

Миграция добавляет уникальности `(address_id, amount)`, `(network, txid)`, `(user_id, external_invoice_id)`. Если в старой БД есть дубликаты, она остановится. Не удаляйте дубликаты автоматически: архивируйте исходную БД, сверяйте платежи и переносите учёт отдельно. Счета без `chain_network` не подтверждаются новым обработчиком. Рекомендуемый MVP-запуск — новая PostgreSQL БД, старую сохранить только для сверки.
