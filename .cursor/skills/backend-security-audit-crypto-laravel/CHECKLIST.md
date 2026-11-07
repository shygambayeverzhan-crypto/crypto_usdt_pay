# Backend Crypto Payment Security Checklist

## Threat Model

Assume attackers include unauthenticated users, payers, malicious merchants, compromised merchant API keys, webhook URL owners, replay attackers, bots, compromised workers, insiders/admin misuse, supply-chain attackers, and unreliable or manipulated external APIs.

Protect wallet private keys, balances, invoice/payment integrity, webhook secrets, API keys, sessions, admin accounts, transaction history, personal data, and infrastructure credentials.

## Laravel Access Control

Check every access to `Invoice`, `Payment`, `Merchant`, `User`, `Wallet`, `Webhook`, `ApiKey`, `Transaction`, `Payout`, `Balance`, `Role`, and `Permission`.

Danger patterns:

- `Model::find($id)`, `findOrFail($id)`, `where('id', $id)`, `whereKey($id)` without merchant scoping or policy
- route model binding without ownership checks
- service methods accepting model IDs without scoped queries
- `hasRole('merchant')` as the only control
- frontend permissions replacing backend authorization

Expected controls:

- policies or gates for all key models
- merchant ownership checks in queries
- admin bypass only when explicit and narrow
- route middleware for auth, verified, admin, throttle, signed routes where needed
- no state-changing `GET` routes

Safe pattern:

```php
$invoice = Invoice::query()
    ->where('merchant_id', $user->merchant_id)
    ->whereKey($id)
    ->firstOrFail();

$this->authorize('view', $invoice);
```

## Authentication, Sessions, CSRF

Review `config/fortify.php`, auth providers, Fortify customizations, login/register/reset flows, middleware, and sensitive routes.

Check:

- login throttling and user enumeration
- inactive/banned users
- password confirmation for sensitive actions
- session regeneration after login and invalidation on logout/password change
- remember tokens and reset tokens
- optional 2FA for admin/payout/wallet actions
- CSRF on all browser state changes
- webhook CSRF exclusions paired with signature verification
- no logout or mutation via `GET`

## Injection and RCE

Search and inspect:

- `DB::raw`, `whereRaw`, `selectRaw`, `orderByRaw`, `havingRaw`, `statement`, `unprepared`
- `exec`, `shell_exec`, `system`, `passthru`, `proc_open`, Symfony Process
- `eval`, `unserialize`, unsafe `decrypt`
- dynamic sorting/filtering/reporting/search
- wallet signer subprocesses or CLI commands

User input must be parameterized, whitelisted, or rejected. Dynamic column names must use allowlists.

## XSS and Inertia Exposure

Check:

- Inertia shared props and resources
- `HandleInertiaRequests`
- `auth()->user()` returned directly
- Vue `v-html`
- Blade `{!! !!}`
- translations, flash messages, merchant names, invoice descriptions, admin notes, Telegram messages

Resources and props must not expose secrets, API keys, internal wallet data, other merchants' data, or hidden model attributes.

## Mass Assignment and Validation

Inspect models and write paths:

- `$fillable`, `$guarded`, `forceFill`, `create($request->all())`, `update($request->all())`, `fill($request->all())`
- `updateOrCreate` and `firstOrCreate` with user-controlled attributes
- FormRequest coverage for sensitive endpoints

Sensitive fields must never be client controlled:

- `user_id`, `merchant_id`, `role`, `permissions`, `status`, `amount`, `currency`, `network`
- `paid_at`, `confirmed_at`, `tx_hash`, `balance`, `is_admin`, `email_verified_at`
- `api_key`, `secret`, `private_key`, `webhook_secret`, `callback_url`, `commission`, `rate`, `risk_score`

Validate enums, money bounds, URL safety, TRON addresses, tx hashes, network/currency allowlists, pagination limits, sortable/filterable fields, and file uploads.

## Money and Precision

Treat as critical.

Check:

- no `float`, `double`, `(float)`, unsafe `round()`, or JS number dependency for backend money decisions
- money stored as integer minor units or project-approved `MoneyAmount`
- explicit rounding and precision
- min/max amounts
- commission/rate calculation safety
- invoice amount/currency/network immutability
- overpayment, underpayment, partial payment behavior
- overflow risks in migrations and casts

## Crypto Payment Lifecycle

Review create invoice -> address/QR -> TRON/TRC-20 payment -> scanner/TronGrid -> transaction match -> confirmation -> balance update -> webhook -> status display.

Critical checks:

- client cannot submit trusted `tx_hash`, `paid_at`, `amount`, `status`, or confirmation state
- transaction exists, is confirmed, on expected network, correct token contract, correct recipient, correct amount, reasonable timestamp
- USDT TRC-20 contract is safely configured and cannot be substituted
- token decimals verified
- address belongs to system and is bound to invoice
- expired/cancelled/confirmed invoice transitions are guarded
- confirmed is terminal unless a deliberate audited process exists

Duplicate credit prevention:

- unique index on transaction identity such as `network + tx_hash + token_contract`
- database transactions
- `lockForUpdate` or equivalent locks
- idempotency keys
- queue retry safety
- one ledger entry per payment reference

Safe pattern:

```php
DB::transaction(function () use ($verifiedTx): void {
    $payment = Payment::query()
        ->where('network', $verifiedTx->network)
        ->where('tx_hash', $verifiedTx->hash)
        ->lockForUpdate()
        ->first();

    if ($payment?->is_confirmed) {
        return;
    }

    // Verify, create ledger entry once, update invoice safely.
});
```

## Wallet Signer

Treat Node.js wallet signing as the highest-risk component.

Check:

- private key storage and logging
- HTTP exposure, bind address, CORS, network isolation
- Laravel-to-signer authentication with HMAC/mTLS/internal network controls
- timestamp, nonce, body hash, replay rejection
- strict request schema
- rate limits and amount limits
- recipient validation and allowlists where appropriate
- network and token contract restrictions
- no arbitrary raw transaction signing
- no private keys, full request bodies, or signed tx secrets in logs
- safe dependency versions and lockfile

Danger:

```js
app.post('/sign', async (req, res) => {
  const signed = await tronWeb.trx.sign(req.body.transaction, PRIVATE_KEY)
  res.json(signed)
})
```

Expected: signer constructs transactions from validated parameters, enforces policy, authenticates callers, rejects replay, and masks logs.

## Webhooks

Merchant outbound webhooks:

- per-merchant secret
- HMAC SHA-256 signature
- timestamp and event ID
- replay protection and idempotency
- timeout, retry, backoff, max response size
- delivery visibility scoped to merchant
- secret rotation
- no sensitive headers forwarded

Headers example:

```text
X-Webhook-Timestamp: <unix timestamp>
X-Webhook-Signature: sha256=<hmac>
X-Webhook-Event: <uuid>
```

Incoming callbacks from Telegram, TronGrid, or custom sources:

- signature or secret verification
- source validation where possible
- payload validation
- idempotency
- no direct financial mutation without independent verification

## SSRF

Review every user-controlled or merchant-controlled URL:

- `Http::get`, `Http::post`, `Http::send`, Guzzle, curl, `file_get_contents`
- merchant webhook URLs
- return URLs
- IP geolocation provider config
- TronGrid endpoint config

Reject:

- non-HTTPS callback URLs unless explicitly safe
- localhost, loopback, link-local, private, multicast, reserved IPs
- internal hostnames
- `file`, `gopher`, `ftp`, and unexpected schemes
- redirects to private/internal IPs

Validate at save time and request time to reduce DNS rebinding risk.

## API Keys

Check:

- cryptographically secure generation
- key shown once only
- hash stored, not plaintext
- prefix used for lookup
- constant-time verification
- scopes/permissions
- merchant binding
- last-used timestamp
- rotation/revocation
- rate limiting per key
- never accepted in query string
- no frontend leakage

## Queues, Horizon, Redis

Check:

- Horizon dashboard protected
- Redis not publicly exposed; auth/TLS where production requires
- job payloads do not serialize secrets/private keys
- retries idempotent
- failed jobs do not expose secrets
- financial jobs cannot double-credit or double-withdraw
- backoff, timeout, tries, poison-message behavior
- session/cache/queue separation when needed

Prioritize blockchain scanner, payment confirmation, webhook delivery, wallet transfer, payout, notification, and reconciliation jobs.

## Database and Accounting

Inspect migrations and models:

- unique constraints for tx identity, public invoice IDs, API key prefixes/hashes, webhook event IDs, idempotency keys
- foreign keys and indexes
- cascade deletes do not destroy financial history
- soft deletes do not bypass uniqueness/security
- critical fields not nullable without reason
- API keys hashed, webhook secrets encrypted, private keys not plaintext
- append-only ledger preferred
- every balance mutation has immutable reference, audit trail, and transaction boundary

## Rate Limiting and Abuse

Check throttles for:

- login, registration, reset, verification
- invoice creation
- payment status polling
- webhook resend
- API key endpoints
- hosted payment pages
- QR generation
- Telegram webhook
- TronGrid polling triggers
- wallet transfer and payout
- exports, reports, search

Use merchant/API key/IP-aware limits for public or expensive routes.

## Secrets and Logs

Search for:

- `APP_KEY`, private keys, mnemonics, seeds
- Telegram bot tokens, TronGrid keys, AWS credentials, DB/Redis passwords
- webhook secrets, API keys, `JWT_SECRET`, `sk_live`

Rules:

- never print full values
- report real committed secrets as Critical/High and recommend rotation
- check logs, tests, seeders, frontend props, config, `.env.example`, CI

Log security events without secrets:

- failed login, password reset, API key created/rotated/revoked
- webhook secret rotated
- payout requested/approved
- wallet transfer
- role/permission change
- manual invoice/payment action
- admin action

## Configuration and Infrastructure

Review:

- `config/session.php`: secure, httpOnly, sameSite, domain, lifetime, driver
- `config/cors.php`: no wildcard with credentials, explicit origins in production
- security headers: CSP, frame ancestors/X-Frame-Options, nosniff, Referrer-Policy, Permissions-Policy, HSTS
- `config/horizon.php`, `config/queue.php`, `config/cache.php`, `config/filesystems.php`, `config/services.php`
- `.env.example` has placeholders only
- `APP_DEBUG=false`, production env, safe log level
- Docker does not bake `.env`, expose Redis/MySQL publicly, run as root unnecessarily, or use `777`
- production image excludes dev tools and dev dependencies where possible

## Files, QR, Telegram, External APIs

File/S3:

- MIME/extension/size validation
- private buckets by default
- signed temporary URLs
- randomized filenames
- no path traversal or public write
- avoid unsafe SVG

QR:

- no secrets
- input length limits
- no path traversal in generated paths
- safe content type and escaping

Telegram:

- webhook secret token
- admin command authorization
- chat binding
- markdown escaping
- no financial actions without strong auth

TronGrid/TronScan:

- API key secrecy
- timeout/retry/rate limit handling
- response validation
- correct network
- no confirmation on API error
- no user-controlled base URL unless allowlisted

## Dependency and Supply Chain

Check:

- `composer audit`, abandoned packages, direct outdated packages
- `npm audit`, TronWeb issues, production dependency boundary
- lockfiles committed
- package and composer scripts
- suspicious postinstall scripts
- CI secrets exposure
- dev dependencies not required in production runtime

## Suggested Security Tests

Suggest focused Pest tests for confirmed issues:

```php
it('prevents merchant from viewing another merchant invoice', function () {
    // Merchant A cannot view Merchant B invoice.
});

it('does not credit the same blockchain transaction twice', function () {
    // Dispatch confirmation twice, assert one ledger entry and unchanged second balance.
});

it('does not allow merchant_id override during invoice creation', function () {
    // User from Merchant A submits Merchant B id; created invoice stays with Merchant A.
});

it('rejects private network webhook urls', function () {
    // POST http://127.0.0.1 callback URL and assert validation error.
});
```

Only create or run tests if the user explicitly asks.
