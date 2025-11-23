#!/bin/bash

echo "Starting ElastAlert with Prometheus monitoring..."

export ELASTALERT_CONFIG_FILE="/app/elastalert_config.yml"
export ELASTALERT_CONFIG_PROCESSED="/app/elastalert_config_processed.yml"
export PROMETHEUS_PORT="9091"

echo "Setting up SSL certificates..."
mkdir -p /app/certs/ca

if [ -f "/app/certs/ca/ca.crt" ]; then
    echo "SSL certificate found, using verify_certs=true"
else
    echo "WARNING: SSL certificate not found, falling back to verify_certs=false"
    sed 's/verify_certs: true/verify_certs: false/' "$ELASTALERT_CONFIG_FILE" > "$ELASTALERT_CONFIG_PROCESSED"
    sed -i '/ca_certs:/d' "$ELASTALERT_CONFIG_PROCESSED"
    export ELASTALERT_CONFIG_FILE="$ELASTALERT_CONFIG_PROCESSED"
fi

echo "Starting in daemon mode..."

if [ ! -f "$ELASTALERT_CONFIG_FILE" ]; then
    echo "ERROR: Configuration file not found: $ELASTALERT_CONFIG_FILE"
    exit 1
fi

echo "Configuration file found: $ELASTALERT_CONFIG_FILE"
echo "Testing Elasticsearch connectivity..."

python3 -c "
import yaml
import requests
import os
from requests.auth import HTTPBasicAuth
from urllib3 import disable_warnings
from urllib3.exceptions import InsecureRequestWarning

disable_warnings(InsecureRequestWarning)

es_password_env = os.environ.get('ES_PASSWORD') 

# Get config file path from shell argument
config_file = '$ELASTALERT_CONFIG_FILE' 

try:
    with open(config_file, 'r') as f:
        config = yaml.safe_load(f)
except FileNotFoundError:
    print(f'ERROR: Config file not found in Python: {config_file}')
    exit(1)
except Exception as e:
    print(f'ERROR: Failed to load YAML: {e}')
    exit(1)

es_host = config.get('es_host')
es_port = config.get('es_port')
es_username = config.get('es_username')

if not all([es_host, es_port, es_username, es_password_env]):
    print('ERROR: Missing required configuration (host, port, username, or ES_PASSWORD environment variable).')
    exit(1)

url = f'https://{es_host}:{es_port}/_cluster/health'
try:
    # Use the password from the environment variable
    response = requests.get(url, auth=HTTPBasicAuth(es_username, es_password_env), verify=False, timeout=10)
    if response.status_code == 200:
        print('SUCCESS: Elasticsearch connection successful')
    else:
        # Use a print statement that is compatible with older Python versions if needed
        print('ERROR: Elasticsearch connection failed. Status code: ' + str(response.status_code))
        exit(1)
except Exception as e:
    print('ERROR: Elasticsearch connection error: ' + str(e))
    exit(1)
"

if [ $? -ne 0 ]; then
    echo "ERROR: Elasticsearch connectivity test failed"
    exit 1
fi

echo "Creating ElastAlert writeback index if needed..."
elastalert-create-index --config "$ELASTALERT_CONFIG_FILE" --index elastalert_status || true

echo "Starting ElastAlert daemon..."
echo "Prometheus metrics will be available on port $PROMETHEUS_PORT"
echo "ElastAlert UI accessible for monitoring"

exec elastalert --config "$ELASTALERT_CONFIG_FILE" --verbose