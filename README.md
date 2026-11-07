# Crypto USDT Pay

MVP приёма USDT TRC-20 на публичный адрес Trust Wallet: владелец и селлеры, магазины, заказы, платёжная форма, API и Telegram-бот. Laravel/Vue монолит для VPS, PostgreSQL и Redis.

Персональные реквизиты не заданы. Заполните собственные настройки по инструкции [docs/CONFIGURATION.md](docs/CONFIGURATION.md).

- Основной шаблон: [.env.example](.env.example).
- Дополнительный тестовый отправитель: [wallet-signer/.env.example](wallet-signer/.env.example).
- Документация API: [docs/API.md](docs/API.md).

Начните с [архитектуры и модели платежей](docs/MVP-ARCHITECTURE.md), затем следуйте [инструкции VPS / Docker Compose](docs/DEPLOY-VPS.md). [Дорожная карта и ограничения](docs/MVP-ROADMAP.md) отделяют реализованное от обязательной приёмки перед mainnet. Новая инструкция VPS имеет приоритет над прежней CONFIGURATION.md.

Оплата подтверждается только по успешной solidified-квитанции TRON, точному Transfer и требуемым подтверждениям. Ручной `paid` запрещён. Суммы резервируются без повторного использования. Ключи кошелька и seed-фразы не требуются и не хранятся на сервере. Вывод средств не реализован.

Требования без Docker: PHP 8.4+ (согласно composer.lock), Composer, PostgreSQL, Redis и Node.js 22. Для сборки необязательной страницы регистрации используйте `ALLOW_REGISTRATION=true npm run build`; регистрация в production остаётся выключенной. Проверки: `php vendor/bin/pest`; отдельный критический набор — `php vendor/bin/pest tests/Feature/PaymentSafetyTest.php`. Перед тестами скопируйте `.env.example` в `.env`; используйте только отдельную тестовую БД.

Ветка требует приёмки с реальным Telegram-ботом, Nile и сервером. Реальные переводы в рамках разработки не выполнялись.

Лицензия и сведения об авторе исходного проекта сохранены в [LICENSE](LICENSE).
