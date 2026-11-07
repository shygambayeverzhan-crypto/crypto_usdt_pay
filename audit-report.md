# Полный отчёт по аудиту безопасности backend
Проект: Crypto Processing Platform
Стек: Laravel 12, PHP 8.4, Vue/Inertia 2, MySQL, Redis/Horizon, TRON/TRC-20 USDT, Node wallet-signer

## Executive Summary
Архитектура в целом зрелая: сервисы/контракты, FormRequest-валидация, MoneyAmount в minor-формате, scoping по `user_id` в API/web, SSRF-фильтр для callback-URL, проверка Telegram secret через `hash_equals`, Horizon под ролью admin. Однако есть несколько серьёзных проблем уровня High, которые в продакшене ведут к обходу контроля доступа и утечке данных.

## Overall Risk
**High**

## Top Risks
1. `trustProxies(at: '*')` — спуфинг `X-Forwarded-For` обходит IP-allowlist API-ключей, троттлинг логина и rate limit.
2. Публичная страница оплаты отдаёт полный `InvoiceResource` без авторизации (PII клиента, `callback_url`, `metadata`, данные мерчанта).
3. API-токены хранятся в открытом виде и сверяются прямым сравнением (не хэш, не constant-time).

---

## Critical Findings
Подтверждённых Critical (утечка приватного ключа в репозиторий, прямой слив средств, форж платежа) не выявлено: `.env` и `wallet-signer/.env` корректно в `.gitignore` и не закоммичены; signer вызывается только из dev/sandbox-флоу на testnet (Nile).

---

## High Findings

## [HIGH] Доверие всем прокси приводит к спуфингу IP и обходу контроля доступа
**Location:**
- `bootstrap/app.php:23` (`$middleware->trustProxies(at: '*')`)
- `app/Http/Middleware/ApiKeyAuth.php:33-47` (allowlist по `$request->ip()`)
- `app/Providers/AppServiceProvider.php:86-91` (rate limit по IP)
- `app/Providers/FortifyServiceProvider.php:87` (login throttle по IP)

**Category:** Laravel / Access Control

**Description:** Доверяются заголовки `X-Forwarded-*` от любого источника. `$request->ip()` тогда берётся из клиентского заголовка.

**Impact:** Атакующий ставит `X-Forwarded-For: <разрешённый_ip>` и обходит IP-allowlist API-токена; обнуляет ключ троттлинга логина/API (брутфорс, rate-limit bypass).

**Exploit scenario:** Утёкший/перебранный API-ключ ограничен allowlist'ом. Атакующий шлёт запрос с заголовком `X-Forwarded-For` равным разрешённому IP и получает полный доступ к API мерчанта.

**Recommendation:** Указывать конкретные доверенные подсети прокси (Cloudflare/Nginx), а не `*`:
```php
$middleware->trustProxies(at: [
    '173.245.48.0/20', '103.21.244.0/22', /* ... диапазоны CF ... */
], headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PROTO);
```

**Suggested test:** Запрос с поддельным `X-Forwarded-For` не должен проходить IP-allowlist.

---

## [HIGH] Публичные endpoint'ы оплаты раскрывают чувствительные данные
**Location:**
- `routes/web.php:46-48` (`pay/{invoice}`, `/data`, `/qr` — без auth)
- `app/Http/Controllers/InvoiceController.php:196-215` (`public`, `publicData`)
- `app/Http/Resources/InvoiceResource.php:20-52`

**Category:** OWASP (Broken Access Control / Sensitive Data Exposure)

**Description:** Публичная страница оплаты использует тот же полный `InvoiceResource`, что и приватный кабинет. Он отдаёт `callback_url`, `metadata`, `external_invoice_id`, `tag`, полный `merchant` и полный `client` (имя, telegram, контакт, external_id).

**Impact:** Любой, кто знает ULID инвойса (а ULID есть в платёжной ссылке и сортируется по времени), читает PII плательщика/клиента, внутренний callback-URL и метаданные мерчанта.

**Recommendation:** Сделать отдельный `PublicInvoiceResource` только с полями, нужными для оплаты: `amount`, `currency`, `network`, `address`, `status`, `expires_at`, `product_name/description`, `payment_url`, white-label-поля мерчанта. Убрать `callback_url`, `metadata`, `client`, `external_invoice_id`, `tag`.

**Suggested test:** `GET pay/{invoice}/data` не должен содержать `callback_url`, `metadata`, `client`.

---

## [HIGH] API-токены хранятся в открытом виде и сверяются небезопасно
**Location:**
- `app/Models/ApiToken.php:21-49` (`token` в `$fillable`, plaintext)
- `app/Http/Middleware/ApiKeyAuth.php:24-27` (`where('token', $provided)`)
- `database/migrations/2025_12_05_010000_create_api_tokens_table.php`

**Category:** Crypto Payments / Secrets

**Description:** Токен хранится как есть и ищется прямым равенством — без хэширования и без constant-time сравнения. Тот же токен используется как `X-Callback-Token`.

**Impact:** Любой read-доступ к БД (бэкап, SQLi в другом месте, дамп) = полный захват API всех мерчантов. Прямой `where` по секрету также теоретически подвержен таймингам по индексу.

**Recommendation:** Хранить `hash('sha256', $token)` + короткий префикс для поиска, отдавать токен пользователю один раз, сверять через `hash_equals`. Развести API-ключ и callback-секрет.

**Suggested test:** В БД нет plaintext-токена; валидный/невалидный ключ дают 200/401.

---

## [HIGH] Архитектура wallet-signer: один захардкоженный кошелёк, мнемоника в плейнтексте, без политики лимитов
**Location:**
- `app/Services/WalletTransfer/WalletTransferService.php:14-56`
- `wallet-signer/send_usdt_cli.js:45-152`, `wallet-signer/.env:6,20`

**Category:** Crypto Payments / Wallet Signer

**Description:** Перевод выполняется запуском Node-CLI, который выводит приватный ключ из мнемоники (хранится открытым текстом в `.env`). Нет allowlist получателей, нет максимальной суммы, нет аутентификации вызывающего на уровне процесса, нет идемпотентной защиты от повторной отправки (ключ только логируется). Сейчас вызывается лишь из `SendSandboxInvoicePaymentJob` (Nile/testnet, планировщик закомментирован) — поэтому High, а не Critical, но это и есть продакшен-дизайн.

**Impact:** При выводе в прод и/или достижимости флоу — риск слива средств: повторные/некорректные переводы, отсутствие лимитов и allowlist.

**Recommendation:** Перед продом: хранить секреты в KMS/Vault (не в `.env`), вынести signer в изолированную сеть с HMAC/mTLS-аутентификацией от Laravel, добавить allowlist получателей и max-amount, обеспечить идемпотентность по `idempotencyKey` (persist + проверка), маскировать логи. Удалить из репозитория вспомогательные скрипты `send_usdt.js`/`check_wallet.js`.

---

## [HIGH] Уязвимые зависимости
**Location:** `composer.lock`, `wallet-signer/package-lock.json`

**Category:** Dependency / Supply Chain

**Description:**
- `composer audit`: 17 advisories / 12 пакетов. В рантайме значимо: `laravel/framework` (CVE-2026-48019, CRLF в email-правиле) — обновить до ≥ 12.60; `league/commonmark` (2 medium). Часть — dev (`phpunit`, `psysh`).
- `npm audit` (wallet-signer): `tronweb` тянет уязвимый `axios` (множество SSRF/prototype-pollution, high) и `ethers`, плюс `follow-redirects` (moderate).

**Recommendation:** `composer update laravel/framework league/commonmark`; в wallet-signer обновить `tronweb` до версии без уязвимого `axios` (проверить совместимость v6.x) и пересобрать lock.

---

## Medium Findings

## [MEDIUM] Ручная отметка инвойса PAID без верификации блокчейна и без аудита
**Location:** `app/Http/Controllers/InvoiceController.php:241-272`, `app/Services/Invoice/InvoiceService.php:176-197`

Владелец инвойса (любой approved-пользователь) может выставить статус `PAID` и произвольный `txid` (`updateStatusManually`) — без проверки транзакции, без записи в аудит. Это меняет статус и через `InvoiceObserver::updated` шлёт callback `status_changed`, которому доверяют downstream-интеграции. Рекомендация: ограничить ручной перевод в PAID (политика/право admin), писать аудит-лог, не доверять клиентскому `txid` без сверки в блокчейне.

## [MEDIUM] Нет БД-гарантии однократного зачёта транзакции
**Location:** `app/Services/Invoice/InvoiceService.php:115-174`, `database/migrations/2025_10_31_010000_create_invoices_table.php:21`

`txid` не уникален, в `finalizeIfConfirmed` нет `DB::transaction`+`lockForUpdate`. Защита от двойного матчинга держится только на app-уровне (`AddressService::pickForPayment` запрещает два активных инвойса с одинаковой суммой на адресе). Это работает, но хрупко. Рекомендация: добавить уникальность идентичности транзакции (`network+txid` или таблицу ledger с unique) и оборачивать финализацию в транзакцию с блокировкой строки.

## [MEDIUM] Исходящие webhooks без HMAC-подписи и защиты от replay
**Location:** `app/Jobs/SendInvoiceCallbackJob.php:62-69`

Только статический переиспользуемый `X-Callback-Token` (тот же, что показывается в UI), нет HMAC SHA-256 от тела, нет `timestamp`/`event-id`/идемпотентности. `withoutRedirecting()` и timeout — это плюс. Рекомендация: подписывать payload HMAC по per-merchant секрету, добавить `X-Webhook-Timestamp` и уникальный `event_id`, дать получателю защиту от повтора.

## [MEDIUM] API доступен неподтверждённым/неодобренным пользователям
**Location:** `routes/api.php:8-9`, `bootstrap/app.php:41-45`

На web-группе стоит `verified`+`approved`, а API-группа защищена только `api.key`. Пользователь со статусом «ожидает одобрения» (или без verified email) может создавать инвойсы через API. Рекомендация: добавить проверку `approved_at`/`verified` в `ApiKeyAuth` или middleware на API-группу.

## [MEDIUM] Утечка внутренних сообщений об ошибках клиенту
**Location:** `app/Http/Controllers/InvoiceController.php:174-186, 259-271`; `app/Http/Controllers/Api/InvoiceController.php:96-100`

В `catch (\Throwable)` клиенту возвращается `$e->getMessage()`. Может раскрыть детали БД/внутренней логики. Рекомендация: отдавать обобщённое сообщение, детали — в `report()`/лог.

## [MEDIUM] Конфигурация для прод
**Location:** `.env`, `config/session.php:172`

В рабочем `.env`: `APP_ENV=local`, `APP_DEBUG=true`, `SESSION_SECURE_COOKIE` не задан, `SESSION_ENCRYPT=false`. Для прод обязательно `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, и желательно security-заголовки (CSP, HSTS, X-Frame-Options, nosniff).

## [MEDIUM] Ротация TronGrid API-ключа
**Location:** `wallet-signer/.env:2,16`

Реальный TronGrid API-ключ присутствует на диске (не закоммичен). Рекомендую ротацию и хранение только в секрет-хранилище.

---

## Low Findings
- **[LOW]** Inertia шарит весь объект `User` (`HandleInertiaRequests.php:59`); секреты закрыты `$hidden`, но лучше отдавать явный DTO. 
- **[LOW]** Нет rate-limit на публичных `pay/*` и `/qr` — усиление нагрузки на TronGrid/QR-генерацию (DoS). 
- **[LOW]** `CallbackUrlRule` валидирует SSRF только при сохранении; `SendInvoiceCallbackJob` не перепроверяет IP при отправке (окно DNS rebinding). Добавить проверку резолва на момент запроса.
- **[LOW]** CSRF отключён для `telegram/webhook` (оправдано, есть secret) и `dev/callback-sandbox` (только local).

---

## Crypto Payment Logic Review
Сопоставление платежа: `getIncomingTransactions` фильтрует по `to == address` и точному равенству суммы в окне `created_at..expires_at` — получатель и сумма привязаны корректно. `finalizeIfConfirmed` дёргает `txid` только за числом подтверждений (это ок, т.к. привязка сделана на этапе attach). Контракт USDT и сеть берутся из конфига, не управляются клиентом. Слабые места: отсутствие БД-уникальности `txid` и блокировки при финализации (Medium), ручной override статуса (Medium).

## Wallet Signer Review
См. High выше. Текущая достижимость — только dev/sandbox/testnet, но дизайн требует доработки до прода (KMS, аутентификация вызова, allowlist/лимиты, идемпотентность, маскирование логов).

## Webhook Security Review
Исходящие: HTTPS + `withoutRedirecting` + timeout — хорошо; нет HMAC/replay/идемпотентности — Medium. Входящие Telegram: secret-token через `hash_equals` — корректно. TronGrid: ключ из конфига, нет user-controlled base URL.

## Access Control Matrix
| Ресурс | Guest | Payer | Merchant User | Platform Admin | Текущий контроль | Замечание |
|---|---|---|---|---|---|---|
| Hosted invoice page | да | да | — | — | публичный ULID | over-exposure (High) |
| Invoice details (web/api) | нет | нет | только свои | все | `user_id`==Auth | ок |
| Create invoice | нет | нет | да (api без approved) | да | FormRequest+scope | approved-bypass в API (Medium) |
| Mark invoice PAID | нет | нет | да (свои) | да | — | без верификации (Medium) |
| API keys | нет | нет | свои | свои | scope по user | plaintext (High) |
| Wallet transfer | нет | нет | нет | sandbox-job | — | дизайн-риски (High) |
| Horizon | нет | нет | нет | да | role:admin | ок |
| Admin users/impersonate | нет | нет | нет | да | role:admin | ок |

## Dependency Audit
composer: 17 advisories (значимо — laravel/framework, league/commonmark). npm wallet-signer: axios (high, через tronweb), ethers, follow-redirects. Lockfiles закоммичены. Подозрительных postinstall не обнаружено.

## Configuration Audit
`APP_DEBUG=true`/`APP_ENV=local` (для прод исправить), `SESSION_SECURE_COOKIE` не задан, нет `config/cors.php` (используется дефолт). `trustProxies('*')` — см. High. Horizon под admin. Секреты в `.gitignore` — корректно.

## Recommended Fix Roadmap
1. Сузить `trustProxies` до подсетей прокси.
2. Ввести `PublicInvoiceResource` для `pay/*`.
3. Хэшировать API/callback-токены, constant-time сверка, разделить их.
4. Прод-конфиг: `APP_DEBUG=false`, secure cookies, security-заголовки; ротация TronGrid-ключа.
5. Обновить зависимости (laravel/framework, commonmark, tronweb/axios).
6. Платежи: unique по `txid` + `DB::transaction`/`lockForUpdate`, ограничить ручной PAID + аудит-лог.
7. Webhooks: HMAC + timestamp + event-id; `approved`/`verified` на API.
8. Дозреть wallet-signer перед продом (KMS, auth, лимиты, идемпотентность).

## Suggested Tests
```php
it('public invoice data does not expose callback_url, metadata or client PII');
it('rejects API IP allowlist bypass via spoofed X-Forwarded-For');
it('stores api token only as hash and verifies via hash_equals');
it('does not credit/finalize the same txid twice');
it('forbids API invoice creation for non-approved users');
it('rejects private/loopback callback urls');
```

## False Positives / Assumptions
- `wallet-signer` оценён как High (не Critical), т.к. достижим только из dev/sandbox на testnet; в проде — пересмотреть на Critical.
- `.env`/`wallet-signer/.env` считаю незакоммиченными (подтверждено `git ls-files`); если они были в истории git — это Critical с обязательной ротацией мнемоники/ключей.
- Защита от двойного матчинга работает благодаря `pickForPayment`; БД-уникальность рекомендована как defense-in-depth.

## Files Reviewed
`bootstrap/app.php`, `routes/{web,api,settings,console}.php`, `app/Http/Middleware/{ApiKeyAuth,VerifyTelegramSecretToken,EnsureUserApproved,HandleInertiaRequests}.php`, `app/Http/Controllers/{InvoiceController,ApiController,Admin/UsersController,ImpersonationController,TelegramWebhookController,Dev/CallbackSandboxController}.php`, `app/Http/Controllers/Api/{InvoiceController,ClientController,MerchantController}.php`, `app/Http/Requests/StoreInvoiceRequest.php`, `app/Http/Resources/InvoiceResource.php`, `app/Services/{Invoice,Address,Blockchain,WalletTransfer}/*`, `app/Jobs/{SendInvoiceCallbackJob,ConfirmInvoicePaymentJob,AttachIncomingPaymentJob}.php`, `app/Observers/InvoiceObserver.php`, `app/Models/{Invoice,ApiToken,User}.php`, `app/Rules/CallbackUrlRule.php`, `app/Providers/{AppServiceProvider,HorizonServiceProvider}.php`, `app/helpers.php`, `config/{session,services}.php`, `database/migrations/*`, `wallet-signer/*`, `composer.json/lock`, `package.json`.