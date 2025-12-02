#!/bin/bash

echo "=== Sentinel Kit Backend Entrypoint ==="
echo "Environment: $APP_ENV"

chown -R www-data:www-data /var/www/html
chown -R www-data:www-data /detection-rules

su -s /bin/bash www-data << 'EOF'
setup_symfony() {
    MARKER_FILE="/var/www/html/.initial_setup_done"
    rm -rf /var/www/html/var/cache
    rm -rf /var/www/html/public/bundles
    rm -rf /detection-rules/elastalert/*
    
    if [ ! -d "/var/www/html/vendor" ] || [ ! -f "/var/www/html/vendor/autoload.php" ]; then
        echo "Installing Composer dependencies..."
        composer install --no-scripts
        composer dump-autoload --optimize
    else
        echo "Composer dependencies already installed."
    fi
    
    echo "Waiting for database to be ready..."
    max_attempts=30
    attempt=1
    while [ $attempt -le $max_attempts ]; do
        if php /var/www/html/bin/console doctrine:database:create --if-not-exists >/dev/null 2>&1; then
            echo "Database is ready!"
            break
        fi
        echo "Database not ready yet (attempt $attempt/$max_attempts), waiting 2 seconds..."
        sleep 2
        attempt=$((attempt + 1))
    done
    
    if [ $attempt -gt $max_attempts ]; then
        echo "ERROR: Database is not ready after $max_attempts attempts"
        exit 1
    fi
    
    if [ ! -f "$MARKER_FILE" ]; then
        echo "Running initial setup..."
        
        echo "Creating database if not exists..."
        php /var/www/html/bin/console doctrine:database:create --if-not-exists
        
        echo "Dropping existing schema..."
        php /var/www/html/bin/console doctrine:schema:drop --force --full-database || true
        
        echo "Creating database schema..."
        if ! php /var/www/html/bin/console doctrine:schema:create; then
            echo "ERROR: Failed to create database schema"
            exit 1
        fi
        
        echo "Verifying database schema..."
        if ! php /var/www/html/bin/console doctrine:schema:validate; then
            echo "WARNING: Database schema validation failed"
        fi
        
        echo "Generating JWT keypair..."
        if ! php /var/www/html/bin/console lexik:jwt:generate-keypair; then
            echo "ERROR: Failed to generate JWT keypair"
            exit 1
        fi
        
        php /var/www/html/bin/console lexik:jwt:check-config
        
        touch "$MARKER_FILE"
        echo "Initial setup completed successfully."
    else
        echo "Initial setup already completed (marker file exists)."
        
        if [ ! -f "/var/www/html/config/jwt/private.pem" ] || [ ! -f "/var/www/html/config/jwt/public.pem" ]; then
            echo "JWT keypair missing, regenerating..."
            if ! php /var/www/html/bin/console lexik:jwt:generate-keypair; then
                echo "ERROR: Failed to regenerate JWT keypair"
                exit 1
            fi
            php /var/www/html/bin/console lexik:jwt:check-config
            echo "JWT keypair regenerated successfully."
        else
            echo "JWT keypair exists."
        fi
        
        echo "Checking if database migrations are up to date..."
        if ! php /var/www/html/bin/console doctrine:migrations:up-to-date; then
            echo "Database migrations are not up to date, running migrations..."
            php /var/www/html/bin/console doctrine:migrations:migrate -n
        fi
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