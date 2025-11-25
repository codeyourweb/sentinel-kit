#!/bin/sh

echo "=== Sentinel Kit Frontend Entrypoint ==="
echo "Environment: $APP_ENV"

echo "Installing npm dependencies..."
npm install

if [ "$APP_ENV" = "prod" ]; then
    echo "Starting frontend in PRODUCTION mode"

    if [ ! -d "/app/dist" ]; then
        echo "Building application for production..."
        npm run build
    fi
    
    if [ ! -d "/app/dist" ]; then
        echo "ERROR: Build failed - dist directory not found!"
        exit 1
    fi
    
    echo "Starting nginx on port 3000..."
    nginx -g 'daemon off;'
    
else
    echo "Starting in DEVELOPMENT mode"
    echo "Starting Vite dev server on port 3000..."
    npm run dev -- --host '0.0.0.0' --port 3000
fi