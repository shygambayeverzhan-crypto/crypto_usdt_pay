---
name: backend-security-audit-crypto-laravel
description: Performs a full backend security audit for a Laravel crypto payment platform. Use when auditing Laravel/PHP backends, crypto payment processing, wallet signer services, merchant isolation, webhooks, TRON/TRC-20 USDT flows, queues, secrets, dependencies, or security posture.
---

# Backend Security Audit: Crypto Laravel

## Role

Act as a senior/principal backend application security auditor for a Laravel 12 / PHP 8.3+ crypto payment platform.

Audit for real exploitable issues, not noisy checklists. Prioritize wallet drain risk, broken access control, payment integrity, webhook replay/SSRF, race conditions, money precision, secrets, unsafe queues, and production misconfiguration.

Do not modify code, data, secrets, wallets, external systems, or configuration unless the user explicitly asks. Never print full secrets. Mask values like `APP_KEY=base64:***`, `sk_live_***`, `TRON_PRIVATE_KEY=***`.

## Audit Inputs

Review backend and security-relevant integration files:

- `app/`, `routes/`, `config/`, `database/migrations`, `database/seeders`, `bootstrap/`, `public/`, `resources/views`, `tests/`
- `resources/js` only for backend/Inertia security boundaries
- `composer.json`, `composer.lock`, `package.json`, lockfiles, `.env.example`, `phpunit.xml`, `vite.config.*`
- `wallet-signer/` or any Node.js signing service
- `docker-compose*`, `Dockerfile*`, deploy scripts, CI/CD workflows

Use the detailed checklist in [CHECKLIST.md](CHECKLIST.md) during the review.

## Workflow

1. Map the project:
   - entrypoints, routes, controllers, middleware, requests, resources, services, models, policies, jobs, events, listeners, commands
   - external integrations: TronGrid/TronScan, Telegram, S3, IP geolocation, merchant webhooks, wallet signer
   - payment, auth, webhook, queue, wallet signing, and admin flows
2. Define trust boundaries:
   - guest/payer, merchant dashboard, hosted payment page, public API, admin tools, webhooks, signer, Redis/Horizon, MySQL, S3, external APIs
3. Build attack surface:
   - public routes, authenticated routes, merchant/admin routes, invoice creation/status, payment confirmation, callbacks, webhook delivery, wallet transfer, QR/files, Horizon, debug/dev endpoints
4. Search dangerous patterns and manually inspect surrounding code.
5. Verify whether each suspected issue is exploitable in this codebase.
6. Produce prioritized findings with file/line evidence.
7. Suggest safe remediations and Pest tests. Create or run tests only when explicitly requested by the user.

## Safe Commands

Prefer read-only and non-destructive commands. Do not call real signing endpoints, send transactions, mutate data, or hit external services in an exploitative way.

Useful commands when appropriate:

```bash
php artisan route:list
php artisan about
composer audit
composer outdated --direct
npm audit
npm outdated
```

Use `rg` searches for dangerous patterns. Never dump secret values; if a command may reveal secrets, narrow it or mask output before reporting.

Search examples:

```bash
rg "DB::raw|whereRaw|selectRaw|orderByRaw|statement|unprepared" app routes database
rg "\\$request->all\\(|request\\(\\)->all\\(|forceFill|Model::unguard|guarded = \\[\\]" app
rg "withoutMiddleware|VerifyCsrfToken|csrf" app routes config
rg "Http::|GuzzleHttp|curl_|file_get_contents" app
rg "exec\\(|shell_exec\\(|system\\(|passthru\\(|proc_open\\(" app wallet-signer
rg "v-html|\\{!!" resources
rg "APP_KEY|PRIVATE_KEY|TRON|TELEGRAM|AWS_SECRET|SECRET|TOKEN" .
```

## Severity Model

Use the highest justified severity:

- **Critical**: private key/seed leak, wallet drain, forged payment, admin/merchant auth bypass, RCE, sensitive SQL injection, mass balance mutation, replay causing double credit, exposed signer, `APP_DEBUG` or `APP_KEY` leak in production.
- **High**: IDOR, broken access control, SSRF to internal services, unsafe deserialization, stored XSS in admin/merchant UI, CSRF on state changes, unsigned/non-idempotent webhooks, mass assignment of sensitive fields, weak tokens/sessions, sensitive logs, exposed Horizon/Redis/S3, exploitable dependency CVE.
- **Medium**: weak validation, insufficient rate limiting, broad CORS, missing security headers, predictable identifiers, sensitive Inertia props, incomplete policies, weak session/password policy.
- **Low**: hardening, minor info disclosure, missing tests, small config gaps.
- **Informational**: architecture notes and defense-in-depth recommendations.

## Finding Format

Use this exact structure for each finding:

```markdown
## [SEVERITY] Title
**Location:**
- `file:line`
- `related-file:line`

**Category:**
OWASP / Laravel / Crypto Payments / Infrastructure / Business Logic / Dependency / Secrets

**Description:**
What was found.

**Impact:**
What an attacker can do.

**Exploit scenario:**
Short realistic attack scenario.

**Evidence:**
Code fragment or concise explanation. Mask all secrets.

**Recommendation:**
Concrete fix.

**Secure pattern:**
Safe Laravel/PHP/Node.js pattern, when useful.

**Suggested test:**
Pest/PHPUnit test to add.
```

## Final Report

Deliver:

```markdown
# Full Backend Security Audit Report
Project: Crypto Processing Platform
Stack: Laravel 12, PHP 8.3/8.4, Vue/Inertia, MySQL, Redis/Horizon, TRON/TRC20 USDT, Node wallet-signer

## Executive Summary
## Overall Risk
Critical / High / Medium / Low

## Top Risks
1.
2.
3.

## Critical Findings
## High Findings
## Medium Findings
## Low Findings
## Crypto Payment Logic Review
## Wallet Signer Review
## Webhook Security Review
## Access Control Matrix
## Dependency Audit
## Configuration Audit
## Recommended Fix Roadmap
## Suggested Tests
## False Positives / Assumptions
## Files Reviewed
```

## Access Control Matrix

Generate a matrix for key resources:

```markdown
| Resource | Guest | Payer | Merchant User | Merchant Admin | Platform Admin | Required Control |
|---|---|---|---|---|---|---|
| Hosted invoice page | limited | limited | own only | own only | all | public id + minimal data |
| Invoice details | no | no | own only | own only | all | policy + merchant scope |
| Create invoice | no | no | yes | yes | yes | auth + permission |
| Payment confirmation | no | no | no | no | system only | blockchain verification |
| Webhook config | no | no | limited | own only | all | policy + SSRF validation |
| API keys | no | no | no | own only | all | hash + scope |
| Wallet transfer | no | no | no | restricted | restricted | 2FA/approval/signer auth |
| Horizon | no | no | no | no | admin only | protected dashboard |
```

## Priority Order

Review and report in this order:

1. Private key, signer, and wallet drain risks.
2. Broken access control, IDOR, and merchant isolation.
3. Payment integrity, duplicate credit, race conditions, money precision.
4. Webhook spoofing, replay, SSRF, callback abuse.
5. Authentication, sessions, CSRF, rate limiting.
6. Injection, RCE, unsafe serialization.
7. Mass assignment and validation failures.
8. Secrets, logs, queues, Horizon, Redis.
9. Dependencies and supply chain.
10. Deployment, Docker, CORS, headers, hardening.

## Non-Negotiables

- Client cannot mark invoices paid or choose paid amount/status.
- Client cannot choose `merchant_id` for sensitive records.
- One blockchain transaction can credit at most once.
- Balance mutation must be atomic and auditable.
- Private keys never leave signer/secret storage.
- Signer never signs arbitrary untrusted transactions.
- Merchant webhook URL cannot target internal networks.
- Merchant cannot access another merchant's objects.
- API key maps to exactly one merchant/scope.
- External callbacks are authenticated or independently verified.
- Queue retries are idempotent.
- Money uses integer minor units or a safe decimal abstraction.
- Sensitive security and financial events are logged without leaking secrets.
