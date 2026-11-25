#!/bin/sh
set -e

# Render tells the container which port to listen on.
PORT="${PORT:-10000}"
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs

php artisan config:cache
php artisan route:cache
php artisan migrate --force
chown -R www-data:www-data storage bootstrap/cache

# No separate worker/cron on the free plan: run the scheduler (reminders,
# booking auto-progress, email queue) alongside the web server.
php artisan schedule:work &

exec apache2-foreground
