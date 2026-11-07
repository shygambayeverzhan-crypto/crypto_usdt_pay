#!/bin/sh
set -eu
# Disposable credentials, isolated CI runner. No Telegram or blockchain traffic.
cat > .env <<'ENV'
APP_NAME="Railway CI"
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
ENV
docker build -f Dockerfile.railway -t payment-railway:ci .
docker compose up -d --wait postgres
docker run -d --name railway-app --network crypto-usdt-pay_default --env-file .env -p 127.0.0.1:8080:8080 payment-railway:ci
attempt=0
until curl -fsS http://127.0.0.1:8080/login > /tmp/railway-login; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge 45 ]; then docker logs railway-app; exit 1; fi
    sleep 2
done
grep -q '/build/assets/' /tmp/railway-login
# Verify that the supervisor actually runs the minute scheduler, not just Apache.
attempt=0
until docker exec railway-app php artisan tinker --execute='exit(Cache::get("payments:last_success") ? 0 : 1);'; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge 40 ]; then exit 1; fi
    sleep 2
done
docker exec railway-app php artisan tinker --execute='if (DB::table("users")->count() !== 1) { exit(1); } DB::table("app_settings")->update(["min_invoice_amount_minor" => 123456]);'
docker restart railway-app
curl --retry 20 --retry-all-errors --retry-delay 1 -fsS http://127.0.0.1:8080/login > /dev/null
docker exec railway-app php artisan tinker --execute='exit(DB::table("users")->count() === 1 && !DB::table("app_settings")->where("min_invoice_amount_minor", "!=", 123456)->exists() ? 0 : 1);'
test "$(curl -s -o /dev/null -w '%{http_code}' -X POST http://127.0.0.1:8080/telegram/webhook)" = 403
