#!/bin/sh
set -eu
# Isolated GitHub runner only. These credentials are disposable test fixtures.
cat > .env <<'ENV'
APP_NAME="Payment CI"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=
APP_DOMAIN=localhost
APP_URL=https://localhost
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=payments_ci
DB_USERNAME=payments_ci
DB_PASSWORD=ci-disposable-database-password
ADMIN_NAME=CI
ADMIN_EMAIL=ci@example.com
ADMIN_PASSWORD=ci-disposable-admin-password
REDIS_HOST=redis
REDIS_CLIENT=phpredis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
CACHE_STORE=redis
MAIL_MAILER=log
INERTIA_SSR_ENABLED=false
ALLOW_REGISTRATION=false
ENV
docker compose config --quiet
docker compose build
docker compose up -d postgres redis
docker compose run --rm app php artisan app:install
docker compose run --rm app php artisan app:install-settings
docker compose up -d app web
attempt=0
until docker compose exec -T web wget -qO- http://127.0.0.1:8080/up; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge 10 ]; then docker compose logs app web; exit 1; fi
    sleep 2
done
docker compose exec -T web wget -qO- http://127.0.0.1:8080/login > /tmp/payment-login.html
grep -q '/build/assets/' /tmp/payment-login.html
# Readiness check uses DB + Redis, with no wallet, bot, or blockchain requests.
docker compose exec -T -e XDG_CONFIG_HOME=/tmp app php artisan tinker --execute='if (DB::table("users")->count() !== 1) { throw new RuntimeException("Owner bootstrap failed"); } Cache::put("ci-smoke", "ok", 60); if (Cache::get("ci-smoke") !== "ok") { throw new RuntimeException("Redis failed"); }'
