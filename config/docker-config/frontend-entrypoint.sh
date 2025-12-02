#!/bin/sh

echo "=== Sentinel Kit Frontend Entrypoint ==="
echo "Environment: $APP_ENV"

if [ "$APP_ENV" = "prod" ]; then
    echo "Starting frontend in PRODUCTION mode"

    MARKER_FILE="/app/.production_build_complete"
    SOURCE_HASH_FILE="/app/.source_hash"
    
    if [ -d "/app/src" ]; then
        CURRENT_HASH=$(find /app/src -type f \( -name "*.vue" -o -name "*.js" -o -name "*.ts" -o -name "*.json" \) -exec md5sum {} \; 2>/dev/null | md5sum | cut -d' ' -f1)
    else
        CURRENT_HASH=""
    fi
    
    NEED_BUILD=false
    
    if [ ! -f "$MARKER_FILE" ] || [ ! -d "/app/dist" ] || [ ! -f "/app/dist/index.html" ]; then
        echo "Production build not found or incomplete."
        NEED_BUILD=true
    elif [ -n "$CURRENT_HASH" ] && [ -f "$SOURCE_HASH_FILE" ]; then
        STORED_HASH=$(cat "$SOURCE_HASH_FILE")
        if [ "$CURRENT_HASH" != "$STORED_HASH" ]; then
            echo "Source code changes detected - rebuild required."
            NEED_BUILD=true
        fi
    elif [ -n "$CURRENT_HASH" ] && [ ! -f "$SOURCE_HASH_FILE" ]; then
        echo "No source hash found - rebuild to establish baseline."
        NEED_BUILD=true
    fi
    
    if [ "$NEED_BUILD" = true ]; then
        echo "Building application for production..."
        
        if [ ! -d "/app/node_modules" ] || [ ! -f "/app/package-lock.json" ]; then
            echo "Installing npm dependencies..."
            npm install
        else
            echo "Dependencies already installed - using cached node_modules."
        fi
        
        echo "Compiling application..."
        npm run build
        
        if [ ! -d "/app/dist" ] || [ ! -f "/app/dist/index.html" ]; then
            echo "ERROR: Build failed - dist directory or index.html not found!"
            exit 1
        fi
        
        if [ -n "$CURRENT_HASH" ]; then
            echo "$CURRENT_HASH" > "$SOURCE_HASH_FILE"
        fi
        touch "$MARKER_FILE"
        echo "Production build completed and marked."
    else
        echo "Production build is up-to-date - skipping build for faster startup."
    fi
    
    echo "Starting nginx on port 3000..."
    nginx -g 'daemon off;'
    
else
    echo "Starting in DEVELOPMENT mode"
    
    echo "Installing npm dependencies..."
    npm install
    
    echo "Starting Vite dev server on port 3000..."
    npm run dev -- --host '0.0.0.0' --port 3000
fi