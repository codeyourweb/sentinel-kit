#!/bin/sh

echo "=== Sentinel Kit Backend Entrypoint ==="
echo "Environment: $APP_ENV"

chown -R www-data:www-data /var/www/html
chown -R www-data:www-data /detection-rules

setup_symfony() {
    MARKER_FILE="/var/www/html/.initial_setup_done"
    rm -rf /var/www/html/var/cache
    rm -rf /var/www/html/public/bundles
    rm -rf /detection-rules/elastalert/*
    
    # Vérifier si les dépendances Composer sont installées
    if [ ! -d "/var/www/html/vendor" ] || [ ! -f "/var/www/html/vendor/autoload.php" ]; then
        echo "Installing Composer dependencies..."
        composer install --no-scripts
        composer dump-autoload --optimize
    else
        echo "Composer dependencies already installed."
    fi
    
    if [ ! -f "$MARKER_FILE" ]; then
        echo "Running initial setup..."
        sleep 10
        rm -rf /var/www/html/migrations/*.php
        php /var/www/html/bin/console doctrine:schema:drop --force --full-database
        php /var/www/html/bin/console make:migration -n
        php /var/www/html/bin/console doctrine:migrations:migrate -n
        php /var/www/html/bin/console lexik:jwt:generate-keypair
        php /var/www/html/bin/console lexik:jwt:check-config
        touch "$MARKER_FILE"
        echo "Initial setup completed."
    fi
    
    if [ "$APP_ENV" = "prod" ]; then
        echo "Warming up production cache..."
        php /var/www/html/bin/console cache:clear --env=prod --no-debug
        php /var/www/html/bin/console cache:warmup --env=prod --no-debug
    else
        echo "Clearing development cache..."
        php /var/www/html/bin/console cache:clear
    fi
}

su -s /bin/sh www-data << 'EOF'
$(declare -f setup_symfony)
setup_symfony
EOF

if [ "$APP_ENV" = "prod" ]; then
    echo "Starting PRODUCTION mode with Nginx + PHP-FPM..."
    
    echo "Starting PHP-FPM..."
    php-fpm -D
    
    echo "Starting Nginx on port 8000..."
    nginx -g 'daemon off;'
    
else
    echo "Starting DEVELOPMENT mode with Symfony server..."
    
    su -s /bin/sh www-data << 'EOF'
echo "Starting Symfony development server on port 8000..."
symfony server:start --allow-http --port=8000 --listen-ip='0.0.0.0'
EOF
fi