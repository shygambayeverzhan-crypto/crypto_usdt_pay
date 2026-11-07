#!/bin/sh
set -eu
php artisan railway:prepare
exec /usr/bin/supervisord -n -c /var/www/html/deploy/railway/supervisord.conf
