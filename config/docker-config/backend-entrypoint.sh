#!/bin/sh
MARKER_FILE="/usr/local/bin/.initial_setup_done"
rm -rf /var/www/html/var/cache
rm -rf /var/www/html/public/bundles
composer install
if [ ! -f "$MARKER_FILE" ]; then
sleep 10
rm -rf /var/www/html/migrations/*.php
php /var/www/html/bin/console doctrine:schema:drop --force --full-database
php /var/www/html/bin/console make:migration -n
php /var/www/html/bin/console doctrine:migrations:migrate -n
touch "$MARKER_FILE"
fi
symfony server:start --allow-http --port=8000 --listen-ip='0.0.0.0'