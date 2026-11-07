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
# Simulate Railway restoring an incompatible base-image module at runtime.
# Only this disposable CI container starts as root; the application runs as www-data.
docker run -d --name railway-app --user root --network crypto-usdt-pay_default --env-file .env -p 127.0.0.1:8080:8080 payment-railway:ci \
    sh -c 'ln -sf ../mods-available/mpm_event.load /etc/apache2/mods-enabled/mpm_event.load; exec su -s /bin/sh www-data -c "sh deploy/railway/start.sh"'
attempt=0
until curl -fsS http://127.0.0.1:8080/login > /tmp/railway-login; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge 45 ]; then docker logs railway-app; exit 1; fi
    sleep 2
done
grep -q '/build/assets/' /tmp/railway-login
# Verify that the supervisor actually runs the minute scheduler, not just Apache.
attempt=0
until docker exec railway-app php -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); exit(Illuminate\Support\Facades\Cache::get("payments:last_success") ? 0 : 1);'; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge 40 ]; then exit 1; fi
    sleep 2
done
docker exec railway-app php -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); if (Illuminate\Support\Facades\DB::table("users")->count() !== 1) { exit(1); } Illuminate\Support\Facades\DB::table("app_settings")->update(["min_invoice_amount_minor" => 123456]);'
docker restart railway-app
curl --retry 20 --retry-all-errors --retry-delay 1 -fsS http://127.0.0.1:8080/login > /dev/null
docker exec railway-app php -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); exit(Illuminate\Support\Facades\DB::table("users")->count() === 1 && !Illuminate\Support\Facades\DB::table("app_settings")->where("min_invoice_amount_minor", "!=", 123456)->exists() ? 0 : 1);'
test "$(curl -s -o /dev/null -w '%{http_code}' -X POST http://127.0.0.1:8080/telegram/webhook)" = 403
