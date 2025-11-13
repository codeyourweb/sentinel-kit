# Ingest Custom Sources into Sentinel-Kit Indexer

**Sentinel-Kit** natively supports multiple direct log ingestion modes by placing log files in the subdirectories of `/data/log_ingest_data`.  
It also includes an HTTP forwarder for JSON-formatted log ingestion — see the [Ingest logs](04-ingest-logs.md) section for details.  

However, for advanced use cases, you may want to create your own custom ingestion source.  
Sentinel-Kit uses **Fluent Bit** as its log ingestion engine, which can be directly extended to handle additional sources.

---

## Data Inputs and Parsers

All Fluent Bit configuration files are loaded from the `/config/fluentbit_server` directory.  
The main configuration file is `fluent-bit.conf`, which references other configuration files as needed.  

It is recommended to create one configuration file per log source type for better readability and maintainability.  
Each existing ingestion configuration in Sentinel-Kit can be found in this same directory.

---

## Data Filters and Transformations

Data transformations can be applied using Fluent Bit’s native filters, or through custom code extensions using **Lua scripts**.  
You can also use the **`exec`** plugin to execute shell commands directly within the container.  

Several working examples are already available within existing Sentinel-Kit source configurations.  
For more advanced usage, refer to the [official Fluent Bit documentation](https://docs.fluentbit.io/manual) and community resources.

---

## Output to Elasticsearch

By default, logs are forwarded to **Elasticsearch** using the following configuration:

```bash
[OUTPUT]
    Name                es
    Match               __SETUP_YOUR_INPUT_TAG_HERE__
    Host                sentinel-kit-db-elasticsearch-es01
    Port                9200
    Buffer_Size         5M
    Logstash_Format     On
    Logstash_Prefix     __SET YOUR ELASTICSEARCH INDEX PATTERN HERE__
    Logstash_DateFormat %Y.%m.%d
    Type                _doc
    Time_Key            @timestamp
    Replace_Dots        On
    Suppress_Type_Name  On
    Retry_Limit         False
    TLS                 On
    TLS.Verify          Off
    HTTP_User           elastic
    HTTP_Passwd         ${ELASTIC_PASSWORD}  # Do not modify this line — it is injected via environment variables for authentication
```

## Applying Configuration Changes

To activate your new Fluent Bit configuration, include it in the main `fluent-bit.conf` file:

```bash
# Fluent Bit service configuration
[SERVICE]
    Flush              1
    Daemon             off
    Log_Level          debug
    Parsers_File       parsers.conf
    HTTP_Server        On
    HTTP_Listen        0.0.0.0
    HTTP_Port          2020
    Health_Check       On
    HTTP_Buffer_Size   1048576

@include /fluent-bit/etc/logs-evtx.conf
@include /fluent-bit/etc/logs-auditd.conf
@include /fluent-bit/etc/logs-json.conf
@include /fluent-bit/etc/logs-http.conf
# ==> Add your new configurations below using @include directives
```

You do not need to restart the entire stack. Restarting the Fluent Bit service is sufficient:

```bash
docker compose restart sentinel-kit-server-fluentbit
```

## Verifying and Debugging

You can check Fluent Bit logs to confirm that your configuration is working correctly or to debug issues:

```bash
docker logs -f sentinel-kit-server-fluentbit
```

These logs will provide detailed information about configuration loading, parsing, and any encountered errors.