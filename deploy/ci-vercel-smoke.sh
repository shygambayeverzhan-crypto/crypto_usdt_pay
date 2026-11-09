#!/bin/sh
set -eu
# Disposable credentials on an isolated GitHub runner only.
cat > .env <<'ENV'
APP_NAME="Payment CI"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=
APP_DOMAIN=localhost
APP_URL=http://127.0.0.1:8080
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=payments_ci
DB_USERNAME=payments_ci
DB_PASSWORD=ci-disposable-database-password
DB_SSLMODE=disable
ADMIN_NAME=CI
ADMIN_EMAIL=ci@example.com
ADMIN_PASSWORD=ci-disposable-admin-password
SESSION_SECURE_COOKIE=false
MAIL_MAILER=log
CRON_SECRET=ci-disposable-maintenance-secret-0123456789
ENV
docker build -f Dockerfile.vercel -t payment-vercel:ci .
docker compose up -d --wait postgres
docker run --rm --network crypto-usdt-pay_default --env-file .env payment-vercel:ci php artisan app:install
docker run --rm --network crypto-usdt-pay_default --env-file .env payment-vercel:ci php artisan app:install-settings
docker run -d --name vercel-app --network crypto-usdt-pay_default --env-file .env -p 127.0.0.1:8080:8080 payment-vercel:ci
attempt=0
until curl -fsS http://127.0.0.1:8080/up > /dev/null; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge 15 ]; then docker logs vercel-app; exit 1; fi
    sleep 2
done
curl -fsS http://127.0.0.1:8080/ready > /dev/null
docker exec vercel-app php artisan deployment:check
curl -fsS -c /tmp/ci-cookies http://127.0.0.1:8080/login > /tmp/ci-login.html
grep -q '/build/assets/' /tmp/ci-login.html
csrf=$(python3 -c 'import http.cookiejar, urllib.parse; j=http.cookiejar.MozillaCookieJar("/tmp/ci-cookies"); j.load(ignore_discard=True); print(urllib.parse.unquote(next(c.value for c in j if c.name == "XSRF-TOKEN")))')
curl -fsS -b /tmp/ci-cookies -c /tmp/ci-cookies -H "X-XSRF-TOKEN: $csrf" -H 'Accept: application/json' \
    --data 'email=ci@example.com&password=ci-disposable-admin-password' http://127.0.0.1:8080/login > /dev/null
curl -fsS -b /tmp/ci-cookies http://127.0.0.1:8080/dashboard > /tmp/ci-dashboard.html
grep -q 'Dashboard' /tmp/ci-dashboard.html
test "$(curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1:8080/internal/payments/tick)" = 401
curl -fsS -H 'Authorization: Bearer ci-disposable-maintenance-secret-0123456789' http://127.0.0.1:8080/internal/payments/tick
curl -fsS -H 'Authorization: Bearer ci-disposable-maintenance-secret-0123456789' http://127.0.0.1:8080/internal/queue/tick
docker restart vercel-app
curl --retry 10 --retry-all-errors --retry-delay 1 -fsS -b /tmp/ci-cookies http://127.0.0.1:8080/dashboard > /tmp/ci-dashboard-restarted.html
grep -q 'Dashboard' /tmp/ci-dashboard-restarted.html
