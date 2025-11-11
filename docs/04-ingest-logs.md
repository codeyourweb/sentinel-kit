# 💻 Log Ingestion into Sentinel-Kit

This document outlines the various methods available for ingesting logs into the Sentinel-Kit Elastic Stack.

---

## 🚀 Ingestion Methods

Logs can be funneled into the Elastic Stack using one of the following methods:

1.  **Direct Indexing:** Placing pre-extracted log files directly into a specific directory.
2.  **SFTP Transfer:** Routing logs via the built-in SFTP service.
3.  **HTTP Forwarding:** Sending logs using a forwarder (like Logstash, Fluent Bit, etc.) to Sentinel-Kit's dedicated HTTP ingestion service.

---

## 1. Direct Indexing

This is the **fastest way** to index data. To use this method, simply place the data you wish to index into the following directory: `./data/log_ingest_data`

### Supported Formats (Out-of-the-Box)

By default, the stack is configured for rapid indexing of the following log types:

* **Linux Audit Logs**
* **Windows Logs** in the `.evtx` format
* **JSON Line (jsonl)** files (one JSON object per line)

### Accessing Indexed Data

All indexed data is placed into Elasticsearch indices following the format: `ingest-<TYPE>-<YY>-<MM>-<DD>`.

You can access and visualize this data via Kibana:
* **URL:** `https://kibana.sentinel-kit.local`
* **Credentials:** Use the `elastic` user account and the password defined in your `.env` file (refer to the `ELASTICSEARCH_PASSWORD` variable).

---

## 2. SFTP Transfer

Sentinel-Kit exposes an **SFTP service** for data transfer.

> **⚠️ Note on Accessibility:** To make the SFTP service accessible over the internet, you **must** configure your network equipment (e.g., firewall rules).

### Credentials

The SFTP credentials are found in your local `.env` file:

```bash
SFTP_USER=sentinel-kit_sftp_user
SFTP_PASSWORD=sentinel-kit_sftp_passwd
```

### Data Handling

Once uploaded, the files will be available in:

`./data/ftp_data`

**Important:** Data placed here is **NOT** automatically indexed. You must manually move or copy the desired logs from this location into the `./data/log_ingest_data` directory to trigger direct indexing (Method 1).

---

## 3. HTTP Forwarding (Recommended for Automation)

You can send logs, primarily in **JSON format**, using a dedicated forwarder. This requires setting up a data source via the backend console.

### Step 1: Access the Backend Console

Execute the following command to enter the backend container:

```bash
docker exec -it sentinel-kit-app-backend /bin/bash
```


### Step 2: Create a New Data Source

Once inside the container, run the following command to create a new ingestion endpoint:

```bash
backend:/var/www/html# php bin/console app:datasource:create <name> <index> [<validFrom> [<validTo>]]
```

This command takes 4 arguments:
* name of the datasource (should be unique).
* name of the Elasticsearch Index (several datasource can specify the same index if you want to).
* (optional) The initial date of validity for log ingestion (logs dated before this will be rejected).
* (optional) The final date of validity for log ingestion (logs dated after this will be rejected). 

Example:
```bash
backend:/var/www/html# php bin/console app:datasource:create MyIngestName temp_index 2020-01-01 2030-01-01
MyIngestName - temp_index
[OK] Datasource "MyIngestName" created successfully 
Valid from 2020-01-01
Valid to 2030-01-01
Ingest key (header X-Ingest-Key): M2VmYjRiZTMtYThmNi00ZDhlLTliZTQtMGFjYWNhZDVjY2Mw
Forwarder URL: https://backend.sentinel-kit.local/ingest/json
``` 

### Step 3: Send logs

Once the source is created, logs must be sent to the Forwarder URL displayed in the console output.

* **Format:** Logs must be sent in __JSON format__, either as a single JSON object or a batch (array of JSON objects).
* **Authentication:** The header X-Ingest-Key must be included with the associated key value provided during creation (e.g., M2VmYjRiZTMtYThmNi00ZDhlLTliZTQtMGFjYWNhZDVjY2Mw).

### ➕ Additional Console Commands

Use these commands to manage your data sources:
* List Sources: php bin/console `app:datasource:list`
* Delete Source: php bin/console `app:datasource:delete <name>`