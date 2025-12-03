#!/bin/bash

echo "=== Sentinel Kit Backend Entrypoint ==="
echo "Environment: $APP_ENV"

chown -R www-data:www-data /var/www/html
chown -R www-data:www-data /detection-rules

calculate_source_hash() {
    find /var/www/html/src /var/www/html/config -type f \( -name "*.php" -o -name "*.yaml" -o -name "*.yml" \) 2>/dev/null | \
    sort | xargs cat | sha256sum | cut -d' ' -f1
}


check_composer_changes() {
    if [ ! -f "/var/www/html/.composer_hash" ]; then
        return 1
    fi
    local current_hash
    current_hash=$(cat /var/www/html/composer.json /var/www/html/composer.lock 2>/dev/null | sha256sum | cut -d' ' -f1)
    local stored_hash
    stored_hash=$(cat /var/www/html/.composer_hash 2>/dev/null)
    [ "$current_hash" = "$stored_hash" ]
}

su -s /bin/bash www-data << 'EOF'
setup_symfony() {
    MARKER_FILE="/var/www/html/.initial_setup_done"
    CACHE_MARKER="/var/www/html/.cache_ready"
    
    rm -rf /var/www/html/var/cache
    rm -rf /var/www/html/public/bundles
    

    if ! check_composer_changes || [ ! -d "/var/www/html/vendor" ] || [ ! -f "/var/www/html/vendor/autoload.php" ]; then
        echo "Installing/updating Composer dependencies..."
        composer install --no-scripts --optimize-autoloader
        composer dump-autoload --optimize
        
        # Save composer hash
        cat /var/www/html/composer.json /var/www/html/composer.lock 2>/dev/null | sha256sum | cut -d' ' -f1 > /var/www/html/.composer_hash
    else
        echo "Composer dependencies are up-to-date - using cached vendor/."
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
        if ! php /var/www/html/bin/console lexik:jwt:generate-keypair --overwrite; then
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
    
    current_hash=$(calculate_source_hash)
    if [ "$APP_ENV" = "prod" ]; then
        if [ -f "$CACHE_MARKER" ] && [ -f "/var/www/html/.source_hash" ]; then
            stored_hash=$(cat /var/www/html/.source_hash 2>/dev/null)
            if [ "$current_hash" = "$stored_hash" ]; then
                echo "Source code unchanged - production cache is up-to-date, skipping rebuild."
            else
                echo "Source code changed - warming up production cache..."
                php /var/www/html/bin/console cache:clear --env=prod --no-debug
                php /var/www/html/bin/console cache:warmup --env=prod --no-debug
                echo "$current_hash" > /var/www/html/.source_hash
                touch "$CACHE_MARKER"
            fi
        else
            echo "Initial cache warmup for production..."
            php /var/www/html/bin/console cache:clear --env=prod --no-debug
            php /var/www/html/bin/console cache:warmup --env=prod --no-debug
            echo "$current_hash" > /var/www/html/.source_hash
            touch "$CACHE_MARKER"
        fi
    else
        echo "Development mode - clearing cache..."
        php /var/www/html/bin/console cache:clear
        echo "$current_hash" > /var/www/html/.source_hash
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