FROM php:8.4-fpm-bookworm AS php-base
RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev libzip-dev libpng-dev libicu-dev unzip git \
    && docker-php-ext-install -j$(nproc) pdo_pgsql bcmath gd intl pcntl zip opcache \
    && pecl install redis && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html

FROM php-base AS build
COPY --from=node:22-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:22-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm
COPY . .
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
RUN npm ci --no-audit --no-fund && ALLOW_REGISTRATION=true npm run build

FROM php-base AS app
COPY --from=build --chown=www-data:www-data /var/www/html /var/www/html
RUN rm -rf node_modules tests .cursor wallet-signer \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache
USER www-data
CMD ["php-fpm", "-F"]

FROM nginxinc/nginx-unprivileged:stable-alpine AS web
COPY deploy/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=build /var/www/html/public /var/www/html/public
