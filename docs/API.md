# Crypto Processing Platform — REST API v1

Документация для интеграции мерчантов. Сгенерировано по коду приложения (маршруты, контроллеры, Form Request, Resources, middleware).

## Базовый URL

```
{APP_URL}/api/v1
```

Пример: `https://pay.example.com/api/v1`

Токены API и callback-токен выдаются в личном кабинете на странице **API и документация** (`GET /api` в веб-интерфейсе, маршрут `api.docs`).

---

## Аутентификация

Все эндпоинты API v1 защищены middleware `api.key`.

### Обязательные заголовки

| Заголовок | Значение | Описание |
|-----------|----------|----------|
| `X-Api-Key` | публичный API-токен | Токен с именем `default` в таблице `api_tokens` |
| `Accept` | `application/json` | Без него ошибки Laravel могут вернуться в HTML |

Для запросов с телом JSON дополнительно:

| Заголовок | Значение |
|-----------|----------|
| `Content-Type` | `application/json` |

### Ошибки аутентификации

| HTTP | Тело | Причина |
|------|------|---------|
| `401` | `{"message":"..."}` | Отсутствует или неверный `X-Api-Key` |
| `403` | `{"message":"..."}` | IP запроса не в whitelist токена (если whitelist не пуст) |

### Whitelist IP

- Если у API-токена **нет** записей в списке разрешённых IP — запросы принимаются с любого IP.
- Если записи **есть** — принимаются только перечисленные адреса (сравнение без учёта регистра).
- Управление списком — только через веб-интерфейс (не через REST API).

---

## Лимиты запросов (rate limit)

Группа `api.v1`:

| Лимит | Ключ |
|-------|------|
| 60 запросов / мин | по IP |
| 120 запросов / мин | по `X-Api-Key` (или `key:missing` + IP, если ключ не передан) |

При превышении: `429` и `{"message":"..."}`.

---

## Поддерживаемые валюты и сети

| Валюта (`currency`) | Сеть (`network`) |
|---------------------|------------------|
| `usdt` | `tron` |

Пары валюта+сеть проверяются при создании инвойса (`NetworkCurrency::isSupported`).

### Формат суммы (`amount`)

Сумма передаётся **строкой** в десятичном виде (точка как разделитель).

| Валюта | Макс. знаков после точки | Пример |
|--------|--------------------------|--------|
| `usdt` | 2 | `12.50` |
| прочие (default) | 6 | `12.123456` |

Правила задаются в `config/currency_amount_rules.php`. Сумма должна быть **строго больше 0**.

---

## Статусы инвойса

| Значение | Описание |
|----------|----------|
| `pending` | Создан, ожидает оплату |
| `processing` | Транзакция найдена, недостаточно подтверждений |
| `paid` | Оплачен |
| `expired` | Истёк срок оплаты |
| `cancelled` | Отменён вручную или через API |

**Активные** (можно отменить): `pending`, `processing`  
**Финальные** (отмена через API невозможна): `paid`, `expired`, `cancelled`

Идентификатор инвойса — **ULID** (строка, например `01JABCDE1234XYZ`).

---

## Общие соглашения

- Ответы одиночных ресурсов (create/show/cancel) — **плоский JSON** объекта (через `->resolve()`, без обёртки `data`).
- Списки с пагинацией (`GET /invoices`, `GET /clients`) — стандартная обёртка Laravel Resource Collection: `data`, `links`, `meta`.
- `GET /merchants` — массив мерчантов внутри `data` (коллекция без пагинации).
- Даты в ISO 8601 (`toISOString()`), например `2025-01-01T10:00:00.000000Z`.
- Денежные поля (`amount`, `amount_received`) — строки с десятичной точкой (формат из `MoneyService`).
- `currency` / `network` в ответах — lowercase; дополнительно `currency_label` / `network_label` — uppercase.

---

## Эндпоинты

### POST `/invoices` — создать инвойс

Создаёт инвойс, назначает адрес для оплаты, отправляет callback `created` (если указан `callback_url`).

#### Тело запроса (JSON)

| Поле | Тип | Обязательно | Описание |
|------|-----|-------------|----------|
| `currency` | string | да | Код валюты, enum: `usdt` (регистр нормализуется в lowercase) |
| `network` | string | да | Сеть, enum: `tron` |
| `amount` | string | да | Сумма в десятичном виде |
| `merchant_id` | integer | да | ID мерчанта текущего пользователя |
| `external_invoice_id` | string | нет | Внешний ID заказа, max 64 |
| `callback_url` | string | нет | HTTPS URL для webhook, max 255 |
| `tag` | string | нет | Произвольная метка, max 255 |
| `metadata` | object | нет | Произвольный JSON-объект |
| `product_name` | string | нет | max 255 |
| `product_description` | string | нет | max 2000 |
| `client_id` | string | нет | **Внешний** ID клиента (`external_id`), max 128; при передаче клиент создаётся или находится |
| `client_name` | string | нет | Имя клиента (при создании через `client_id`) |
| `client_telegram` | string | нет | Telegram |
| `client_contact` | string | нет | Доп. контакт |

#### Правила `callback_url` (production)

- Только **HTTPS**
- Запрещены localhost, private/link-local IP, резолв DNS в запрещённые адреса
- В локальной среде (`is_local()`) проверка отключена

#### Ответ `200`

Объект инвойса (см. [Схема Invoice](#схема-invoice)). Загружаются связи: `address`, `merchant`, `client`.

#### Ошибки

| HTTP | Причина |
|------|---------|
| `401` | Нет/неверный API-ключ |
| `403` | IP не в whitelist |
| `422` | Ошибка валидации или бизнес-логики (`message` + опционально `errors`) |
| `429` | Rate limit |

#### Пример

```bash
curl -X POST 'https://pay.example.com/api/v1/invoices' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -H 'X-Api-Key: YOUR_PUBLIC_API_KEY' \
  -d '{
    "currency": "usdt",
    "network": "tron",
    "amount": "12.34",
    "merchant_id": 1,
    "client_id": "customer-123",
    "external_invoice_id": "order-1",
    "callback_url": "https://merchant.example.com/callbacks/invoice",
    "tag": "vip",
    "metadata": {"order_id": "100500"},
    "product_name": "Order #100500",
    "product_description": "Test payment"
  }'
```

---

### GET `/invoices` — список инвойсов

Возвращает только инвойсы **текущего пользователя** (владельца API-токена).

#### Query-параметры

| Параметр | Тип | Описание |
|----------|-----|----------|
| `search` | string | Поиск по id, external_invoice_id, tag, client.external_id, client.name, address |
| `status` | string | `pending`, `processing`, `paid`, `expired`, `cancelled` |
| `currency` | string | `usdt` |
| `network` | string | `tron` |
| `merchant_id` | integer | ID мерчанта |
| `client_id` | string | **Внешний** ID клиента (`clients.external_id`) |
| `external_invoice_id` | string | Подстрока (LIKE) |
| `tag` | string | Подстрока (LIKE) |
| `has_callback` | boolean | `1` / `true` — только с заполненным `callback_url` |
| `page` | integer | ≥ 1, по умолчанию 1 |
| `per_page` | integer | 1–100, по умолчанию 20 |

Сортировка: `id` DESC. Eager load: `merchant`, `client`.

#### Ответ `200`

```json
{
  "data": [ { "...": "Invoice" } ],
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta": { "current_page": 1, "per_page": 20, "total": 100, "...": "..." }
}
```

#### Пример

```bash
curl -G 'https://pay.example.com/api/v1/invoices' \
  -H 'Accept: application/json' \
  -H 'X-Api-Key: YOUR_PUBLIC_API_KEY' \
  --data-urlencode 'status=pending' \
  --data-urlencode 'client_id=customer-123' \
  --data-urlencode 'per_page=20'
```

---

### GET `/invoices/{id}` — получить инвойс

#### Path

| Параметр | Описание |
|----------|----------|
| `id` | ULID инвойса |

#### Ответ `200`

Объект инвойса с `address`, `merchant`, `client`.

#### Ошибки

| HTTP | Причина |
|------|---------|
| `404` | Инвойс не найден или принадлежит другому пользователю |

---

### GET `/invoices/{id}/status` — краткий статус

Урезанный ответ для polling.

#### Ответ `200`

```json
{
  "id": "01JABCDE1234XYZ",
  "status": "paid",
  "amount": "12.34000000",
  "amount_received": "12.34000000",
  "confirmations": 12,
  "expires_at": "2025-01-01T10:00:00.000000Z",
  "txid": "0xabc..."
}
```

---

### GET `/invoices/{id}/public` — публичные данные

По структуре совпадает с полным инвойсом (`InvoiceResource` + `address`, `merchant`, `client`). Доступ только владельцу API-токена (не анонимный публичный доступ).

---

### GET `/invoices/{id}/qr` — QR-код адреса оплаты

Возвращает **PNG** (`Content-Type: image/png`), данные QR — криптоадрес инвойса.

#### Ошибки

| HTTP | Причина |
|------|---------|
| `404` | Нет инвойса / нет адреса |

#### Пример

```bash
curl -o qr.png 'https://pay.example.com/api/v1/invoices/01JABCDE1234XYZ/qr' \
  -H 'Accept: application/json' \
  -H 'X-Api-Key: YOUR_PUBLIC_API_KEY'
```

---

### POST `/invoices/{id}/cancel` — отменить инвойс

Переводит активный инвойс в статус `cancelled` (через `InvoiceService::expire`).

#### Условия

- Статус не финальный (`paid`, `expired`, `cancelled`) → иначе `409`
- Не истёк `expires_at` → иначе `409`

#### Ответ `200`

Обновлённый объект инвойса (`InvoiceResource`).

#### Ошибки

| HTTP | Тело |
|------|------|
| `409` | `already_finalized` / `already_expired` |

---

### GET `/merchants` — список мерчантов

Все мерчанты пользователя, без пагинации, сортировка `id` DESC.

#### Ответ `200`

```json
{
  "data": [
    {
      "id": 1,
      "name": "My Shop",
      "description": "...",
      "initials": "MS",
      "logo_path": "merchants/...",
      "logo_url": "https://.../storage/...",
      "back_url": "https://shop.example.com",
      "white_label_enabled": true,
      "invoice_expires_in_minutes": 60,
      "created_at": "...",
      "updated_at": "..."
    }
  ]
}
```

---

### GET `/clients` — список клиентов

Клиенты текущего пользователя, пагинация как у инвойсов.

#### Query

| Параметр | По умолчанию |
|----------|--------------|
| `page` | 1 |
| `per_page` | 20 (max 100) |

#### Ответ `200`

```json
{
  "data": [
    {
      "id": 1,
      "external_id": "customer-123",
      "name": "John Doe",
      "telegram": "@johndoe",
      "contact": "john@example.com",
      "created_at": "...",
      "updated_at": "..."
    }
  ],
  "links": { "...": "..." },
  "meta": { "...": "..." }
}
```

---

### POST `/clients` — создать клиента

#### Тело

| Поле | Тип | Обязательно |
|------|-----|-------------|
| `external_id` | string | да, max 128, уникален в рамках user |
| `name` | string | нет |
| `telegram` | string | нет |
| `contact` | string | нет |

#### Ответ `200`

```json
{
  "client": {
    "id": 1,
    "external_id": "customer-123",
    "name": "John Doe",
    "telegram": "@johndoe",
    "contact": "john@example.com",
    "created_at": "...",
    "updated_at": "..."
  }
}
```

#### Ошибки

| HTTP | Причина |
|------|---------|
| `422` | Дубликат `external_id` |

#### Пример

```bash
curl -X POST 'https://pay.example.com/api/v1/clients' \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -H 'X-Api-Key: YOUR_PUBLIC_API_KEY' \
  -d '{
    "external_id": "customer-123",
    "name": "John Doe",
    "telegram": "@johndoe",
    "contact": "john@example.com"
  }'
```

---

## Схема Invoice

Поля объекта в ответах API и в теле callback (`invoice`):

| Поле | Тип | Описание |
|------|-----|----------|
| `id` | string (ULID) | ID инвойса |
| `external_invoice_id` | string\|null | Внешний ID |
| `address_id` | integer\|null | ID записи адреса |
| `address` | string\|null | Криптоадрес (если загружен `address`) |
| `payment_url` | string | URL hosted-страницы оплаты |
| `amount` | string | Запрошенная сумма |
| `currency` | string | `usdt` |
| `currency_label` | string | `USDT` |
| `network` | string | `tron` |
| `network_label` | string | `TRON` |
| `status` | string | См. [статусы](#статусы-инвойса) |
| `txid` | string\|null | Хеш транзакции |
| `tx_explorer_url` | string\|null | Ссылка в block explorer |
| `amount_received` | string | Полученная сумма |
| `confirmations` | integer | Число подтверждений |
| `expires_at` | string\|null | ISO datetime |
| `callback_url` | string\|null | URL webhook |
| `tag` | string\|null | Метка |
| `metadata` | object\|null | Метаданные |
| `merchant_id` | integer\|null | ID мерчанта |
| `merchant` | object\|null | Вложенный Merchant (если загружен) |
| `client_id` | integer\|null | Внутренний ID клиента |
| `client_external_id` | string\|null | Внешний ID клиента |
| `client` | object\|null | Вложенный Client (если загружен) |
| `product_name` | string\|null | |
| `product_description` | string\|null | |
| `created_at` | string\|null | ISO datetime |
| `updated_at` | string\|null | ISO datetime |

### Пример полного ответа

```json
{
  "id": "01JABCDE1234XYZ",
  "external_invoice_id": "order-100500",
  "address_id": 42,
  "address": "TXyz...",
  "payment_url": "https://pay.example.com/invoices/01JABCDE1234XYZ",
  "amount": "12.34000000",
  "currency": "usdt",
  "currency_label": "USDT",
  "network": "tron",
  "network_label": "TRON",
  "status": "pending",
  "txid": null,
  "tx_explorer_url": null,
  "amount_received": "0.00000000",
  "confirmations": 0,
  "expires_at": "2025-01-01T10:00:00.000000Z",
  "callback_url": "https://merchant.example.com/callback",
  "tag": "order-100500",
  "metadata": {
    "order_id": "100500"
  },
  "merchant_id": 1,
  "merchant": {
    "id": 1,
    "name": "My Shop",
    "description": "...",
    "initials": "MS",
    "logo_path": null,
    "logo_url": null,
    "back_url": "https://shop.example.com",
    "white_label_enabled": false,
    "invoice_expires_in_minutes": 60,
    "created_at": "2025-01-01T08:00:00.000000Z",
    "updated_at": "2025-01-01T08:00:00.000000Z"
  },
  "client_id": 10,
  "client_external_id": "customer-123",
  "client": {
    "id": 10,
    "external_id": "customer-123",
    "name": "John Doe",
    "telegram": "@johndoe",
    "contact": "john@example.com",
    "created_at": "2025-01-01T08:00:00.000000Z",
    "updated_at": "2025-01-01T08:00:00.000000Z"
  },
  "product_name": "Order #100500",
  "product_description": "Test payment",
  "created_at": "2025-01-01T09:00:00.000000Z",
  "updated_at": "2025-01-01T09:00:00.000000Z"
}
```

---

## Webhook (коллбеки)

Если при создании инвойса указан `callback_url`, система отправляет **POST** на этот URL при событиях.

### События

| `event` | Когда |
|---------|--------|
| `created` | Сразу после создания инвойса (`InvoiceObserver::created`) |
| `status_changed` | При каждом изменении поля `status` (`InvoiceObserver::updated`) |

Дополнительно из веб-панели возможна ручная отправка с `event: manual` (не через публичный REST API).

### Заголовки запроса к мерчанту

| Заголовок | Значение |
|-----------|----------|
| `Content-Type` | `application/json` |
| `X-Callback-Event` | `created` или `status_changed` |
| `X-Callback-Token` | Callback-токен из личного кабинета (отдельный от `X-Api-Key`) |

Редиректы **отключены** (`withoutRedirecting`).

### Тело запроса

```json
{
  "event": "status_changed",
  "invoice": { }
}
```

Объект `invoice` — тот же формат, что `GET /invoices/{id}` (`InvoiceResource`), без гарантии eager-load всех связей (в job загружается модель как есть).

### Правила доставки

| Параметр | Значение |
|----------|----------|
| Метод | `POST` |
| Таймаут | 10 секунд |
| Успех | HTTP 2xx |
| Повторы | **Нет** (одна попытка на событие) |
| Логирование | Каждая попытка пишется в `invoice_callback_logs` |

Проверяйте подпись/токен на стороне мерчанта: сравнивайте `X-Callback-Token` с сохранённым callback-токеном.

### Пример payload

```json
{
  "event": "status_changed",
  "invoice": {
    "id": "01JABCDE1234XYZ",
    "external_invoice_id": "order-100500",
    "payment_url": "https://pay.example.com/invoices/01JABCDE1234XYZ",
    "amount": "12.34000000",
    "currency": "usdt",
    "currency_label": "USDT",
    "network": "tron",
    "network_label": "TRON",
    "status": "paid",
    "txid": "0xabc...",
    "tx_explorer_url": "https://tronscan.org/#/transaction/0xabc...",
    "amount_received": "12.34000000",
    "confirmations": 12,
    "expires_at": "2025-01-01T10:00:00.000000Z",
    "callback_url": "https://merchant.example.com/callback",
    "tag": "order-100500",
    "metadata": {
      "order_id": "100500"
    },
    "merchant_id": 1,
    "client_id": 10,
    "client_external_id": "customer-123",
    "product_name": "Order #100500",
    "product_description": "Test payment",
    "created_at": "2025-01-01T09:00:00.000000Z",
    "updated_at": "2025-01-01T09:05:00.000000Z"
  }
}
```

---

## Типовой сценарий интеграции

1. Получить `X-Api-Key` и при необходимости настроить whitelist IP в кабинете.
2. `GET /merchants` — выбрать `merchant_id`.
3. Опционально `POST /clients` или передать `client_id` при создании инвойса.
4. `POST /invoices` с `callback_url` (HTTPS).
5. Показать плательщику `payment_url` или `GET /invoices/{id}/qr`.
6. Polling: `GET /invoices/{id}/status` или обработка webhook `status_changed`.
7. При необходимости отменить: `POST /invoices/{id}/cancel` (пока статус активный).

---

## Сводная таблица эндпоинтов

| Метод | Путь | Описание |
|-------|------|----------|
| `POST` | `/invoices` | Создать инвойс |
| `GET` | `/invoices` | Список инвойсов |
| `GET` | `/invoices/{id}` | Получить инвойс |
| `GET` | `/invoices/{id}/status` | Краткий статус |
| `GET` | `/invoices/{id}/public` | Данные инвойса (тот же resource) |
| `GET` | `/invoices/{id}/qr` | PNG QR адреса |
| `POST` | `/invoices/{id}/cancel` | Отменить инвойс |
| `GET` | `/merchants` | Список мерчантов |
| `GET` | `/clients` | Список клиентов |
| `POST` | `/clients` | Создать клиента |

---

## Ошибки валидации (422)

Формат Laravel:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "amount": ["..."],
    "merchant_id": ["..."]
  }
}
```

Отдельные бизнес-ошибки могут возвращать только `message` без `errors`.

---

## Исходники в репозитории

| Компонент | Путь |
|-----------|------|
| Маршруты | `routes/api.php` |
| Контроллеры | `app/Http/Controllers/Api/` |
| Валидация | `app/Http/Requests/StoreInvoiceRequest.php`, `app/Http/Requests/Api/` |
| Resources | `app/Http/Resources/InvoiceResource.php`, `ClientResource.php`, `MerchantResource.php` |
| Auth middleware | `app/Http/Middleware/ApiKeyAuth.php` |
| Callback job | `app/Jobs/SendInvoiceCallbackJob.php` |
| UI-документация | `resources/js/pages/api/` |
