#!/bin/sh
set -eu
# Railway can restore base-image Apache module links at runtime. Normalize a
# private config copy as www-data, without granting the application root access.
APACHE_CONFDIR=$(mktemp -d /tmp/usdt-apache.XXXXXX)
export APACHE_CONFDIR
cp -RL /etc/apache2/. "$APACHE_CONFDIR/"
rm -f "$APACHE_CONFDIR/mods-enabled/mpm_event.load" "$APACHE_CONFDIR/mods-enabled/mpm_event.conf" \
    "$APACHE_CONFDIR/mods-enabled/mpm_worker.load" "$APACHE_CONFDIR/mods-enabled/mpm_worker.conf"
cp "$APACHE_CONFDIR/mods-available/mpm_prefork.load" "$APACHE_CONFDIR/mods-enabled/mpm_prefork.load"
cp "$APACHE_CONFDIR/mods-available/mpm_prefork.conf" "$APACHE_CONFDIR/mods-enabled/mpm_prefork.conf"
APACHE_ENVVARS="$APACHE_CONFDIR/envvars"
export APACHE_ENVVARS
. "$APACHE_ENVVARS"
apache2 -d "$APACHE_CONFDIR" -f "$APACHE_CONFDIR/apache2.conf" -t
php artisan railway:prepare
exec /usr/bin/supervisord -n -c /var/www/html/deploy/railway/supervisord.conf
