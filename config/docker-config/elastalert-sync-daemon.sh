#!/bin/bash

while true; do
    echo "[$(date)] Running ElastAlert sync..."
    cd /var/www/html
    /usr/local/bin/php bin/console app:elastalert:sync-alerts --since='-1 minute' >> /var/log/elastalert-sync.log 2>&1
    if [ $? -eq 0 ]; then
        echo "[$(date)] Sync completed successfully" >> /var/log/elastalert-sync.log
    else
        echo "[$(date)] Sync failed with exit code $?" >> /var/log/elastalert-sync.log
    fi
    sleep 30
done