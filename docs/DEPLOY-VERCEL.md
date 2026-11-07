# Vercel: Laravel + PostgreSQL

## Что изменилось

Основной проект: `YFPRD / crypto-usdt-pay`. Репозиторий связан также с `crypto-usdt-pay-if8i`: это второй независимый deployment, не второй компонент платёжки. Не подключайте оба проекта к рабочей БД и боту одновременно. Для preview используйте отдельную тестовую БД, отдельного бота и Nile.

`Dockerfile.vercel` включает PHP 8.4, Composer, Apache и собранные Vue/Inertia-ресурсы. HTTP слушает 8080. В Vercel выберите Framework **Container**, сбросьте старые Vite Build/Install/Output overrides и задайте `PORT=8080`. `Dockerfile.vercel` выбирается вместо обычного Dockerfile. Container Runtime пока Beta.

API, кабинеты и Telegram webhook остаются в одном Laravel-приложении. PostgreSQL хранит счета, уникальные резервации сумм, аудит, сессии, кеш/блокировки и очередь. Redis для Vercel не требуется. Локальный диск контейнера служит только для временных файлов/скомпилированных шаблонов. Загрузка логотипов отклоняется; магазины используют инициалы.

## Подключение БД и секретов

В Storage подключите Neon PostgreSQL или другую управляемую PostgreSQL с TLS. Приложение понимает `DB_URL`, `DATABASE_URL`, `POSTGRES_URL` (в таком порядке) либо отдельные `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`. Для обработки запросов используйте connection pooler. Не сохраняйте реквизиты в Git.

Задайте переменные отдельно для Preview и Production:

| Переменная | Значение |
|---|---|
| APP_KEY | уникальный постоянный ключ `base64:…`; получить `php artisan key:generate --show` в собранном образе |
| APP_URL | HTTPS-адрес соответствующего окружения |
| PORT | 8080 |
| DB_CONNECTION / DB_SSLMODE | pgsql / require |
| SESSION_DRIVER / CACHE_STORE / QUEUE_CONNECTION | database / database / database |
| APP_ENV / APP_DEBUG | production / false |
| LOG_CHANNEL | stderr |
| VERCEL_RUNTIME | true (также задано в образе) |
| CRON_SECRET | случайный секрет 32+ символа, отдельно для каждого окружения |
| VERCEL_PAYMENTS_ENABLED | false до завершения приёмки |
| ADMIN_NAME / ADMIN_EMAIL / ADMIN_PASSWORD | только для однократного создания владельца |
| PAYMENT_RECEIVE_ADDRESS | ваш публичный адрес TRON, без приватных ключей |
| TRONGRID_NETWORK / TRONGRID_BASE_URL | nile / https://nile.trongrid.io для теста |
| TRONGRID_USDT_CONTRACT | адрес вашего тестового токена Nile |
| TRONGRID_API_KEY | ключ API чтения блокчейна |
| TELEGRAM_BOT_TOKEN / TELEGRAM_BOT_NAME | настройки отдельного тестового бота |
| TELEGRAM_WEBHOOK_URL / TELEGRAM_WEBHOOK_SECRET | HTTPS `/telegram/webhook` и отдельный случайный секрет |
| SESSION_SECURE_COOKIE / ALLOW_REGISTRATION | true / false |
| PAYMENT_CALLBACKS_ENABLED | false |
| INERTIA_SSR_ENABLED | false |

Вместо хранения секретов в Dockerfile используйте Environment Variables в Vercel. Секреты не нужны при сборке. Изменение переменных требует нового deployment. Не копируйте production DB/APP_KEY/бота в preview.

## Инициализация и обновления

Миграции **не выполняются** при HTTP-запросе, сборке или холодном старте. Выполните их однократно с доверенной машины с Docker, указав приватный файл окружения нужной БД (на Windows используйте полноценный путь):

```sh
docker build -f Dockerfile.vercel -t payment-vercel .
# Первый запуск на новой БД; ADMIN_* присутствуют в приватном файле:
docker run --rm --env-file .env.vercel.private payment-vercel php artisan app:install
docker run --rm --env-file .env.vercel.private payment-vercel php artisan app:install-settings
docker run --rm --env-file .env.vercel.private payment-vercel php artisan payments:configure-address owner@example.com
docker run --rm --env-file .env.vercel.private payment-vercel php artisan telegram:set-webhook
# Последующие обновления (сначала backup БД, затем эта команда):
docker run --rm --env-file .env.vercel.private payment-vercel php artisan migrate --force
```

Используйте прямое соединение провайдера для миграций, если его pooler ограничивает DDL. После создания владельца удалите ADMIN_PASSWORD из Vercel и файла. APP_KEY сохраняйте вместе с защищёнными backup. Публичного HTTP endpoint для установки или миграций нет.

## Фоновая работа

Проверка аккаунта YFPRD показала **Hobby**: Vercel отклонил ежеминутные Cron. Поэтому корневой `vercel.json` развёртывает только приложение, **без автоматического расписания**; приём платежей по умолчанию выключен. Это позволяет проверить сборку и подключить тестовую БД, но не является запуском автоматической платёжки.

После перехода на Pro/Enterprise скопируйте `deploy/vercel/pro.json` в корневой `vercel.json` и выполните новый deployment. Он задаёт два ежеминутных Cron: `/internal/payments/tick` и `/internal/queue/tick`. Hobby поддерживает legacy Cron только раз в день — не заменяйте частоту на суточную ради зелёной сборки. Альтернатива без смены тарифа: отдельный доверенный планировщик вызывает оба HTTPS endpoint каждую минуту с Bearer CRON_SECRET. Его подключение и фактические успешные вызовы нужно проверить до включения приёма платежей.

Vercel Cron работает на production deployment, отправляя `Authorization: Bearer <CRON_SECRET>`. В preview проверяйте вручную с этим заголовком и через разрешённый доступ Deployment Protection. Не отключайте защиту preview глобально. Production Telegram webhook и публичная платёжная страница должны быть доступны посетителям; используйте соответствующий домен и настройки защиты Vercel. Telegram не может пройти интерактивную Vercel SSO.

Обработчик платежей имеет бюджет 40 секунд, максимум 25 счетов и 10 уведомлений за вызов. Счета выбираются по времени последней попытки; прогресс сохраняется **до** сетевого обращения. Каждая блокчейн-операция ограничена оставшимся временем. Таймаут/429 не устанавливает paid. Блокировка PostgreSQL на 240 секунд исключает параллельные проходы; после аварии следующий запуск восстановится после её истечения. Платёжная проверка и неизменяемые ограничения txid/суммы сохранены.

Обработчик очереди запускает ограниченный процесс `queue:work` и ожидает его в рамках запроса: только notifications/default, до 10 заданий/20 секунд, отдельный timeout задания 15 секунд и общий предел процесса 30 секунд. Не запускайте `schedule:work`, бесконечный `queue:work` или Horizon в контейнере Vercel. Callbacks и старые циклические invoice jobs не обрабатываются этим worker.

При `VERCEL_PAYMENTS_ENABLED=false` новые счета запрещены. После приёмки включите флаг: новые счета всё равно требуют успешного прохода проверки за последние 5 минут. Повторный идемпотентный запрос существующего заказа возвращает прежний счёт. Отслеживайте `payments:last_tick`, ошибки, возраст `last_checked_at` и рост очереди. При превышении пропускной способности этот MVP необходимо масштабировать/перейти на сканирование блоков; увеличение количества покупателей не ускоряет Cron.

## Приёмка

1. Сборка Container должна стать READY. `/up` проверяет только запуск PHP. Без APP_KEY/БД остальные страницы честно возвращают 503; READY ещё не означает готовность принимать оплату.
2. После миграций войдите владельцем, создайте селлера и проверьте разграничение доступа. Включите 2FA и настройте SMTP для восстановления доступа.
3. Повторите вход после redeploy: сессии и данные сохраняются в PostgreSQL.
4. Без Bearer maintenance endpoints возвращают 401; с правильным секретом — результат обработки без реквизитов.
5. В отдельной Nile БД выполните сценарии из DEPLOY-VPS: два равных заказа, идемпотентность, неверный токен/сумма/получатель, недобор подтверждений, повторный txid и восстановление после паузы.
6. Проверьте реальное получение Cron-запросов в production и Telegram-уведомление, затем включайте выдачу счетов. Mainnet запускайте на отдельной БД после проверки и согласованного контрольного перевода.

## Источники

- https://vercel.com/docs/functions/runtimes/container
- https://vercel.com/docs/schedules/cron-jobs/manage-cron-jobs
- https://vercel.com/docs/schedules/cron-jobs/usage-and-pricing

Wallet Street, исходящие выплаты и распределение средств между селлерами остаются отдельным этапом.
