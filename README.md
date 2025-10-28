![Sentinel Kit](./.github/img/sentinel-kit_logo.png)
# 🛡️ Sentinel Kit: The Simplified Platform for Incident Response (DFIR/SOC)

## WARNING: This project is currently in an early stage of development. Not all components have been ported to this repository, and the features are not yet stable enough for production use. 
---

**Sentinel Kit** is a comprehensive Docker stack designed to provide **Digital Forensics and Incident Response (DFIR)** and **Security Operations Center (SOC)** capabilities with unparalleled deployment simplicity.

Ideal for **situational monitoring** or **rapid security incident response**, this integrated platform enables collection, analysis, detection, and immediate response to threats.

---

## ✨ Key Features

Sentinel Kit is an all-in-one toolkit that covers the entire security incident lifecycle:

* **Log Collection & Parsing (SIEM Lite)**: Uses **Fluent Bit** for data ingestion and **Elasticsearch** for storage and indexing.
* **Advanced Analysis & Triage**: Planned integration of **Sigma** rules for log-based detection and **YARA** for suspicious file triage (via upload mechanisms).
* **Detection and Response (EDR)**: A dedicated agent (integrating into the ecosystem) is planned to provide real-time detection and response functionalities.
* **Secure Uploads**: Provides a dedicated **SFTP** server for uploading evidence, logs, or suspicious files.
* **Comprehensive Visualization**: Monitoring dashboards via **Kibana** and **Grafana/Prometheus**.

---

## 🚀 Quick Start (Installation)

This project is designed to be deployed in minutes using Docker Compose.

### Prerequisites

* **Docker**
* **Docker Compose** (or Docker Engine including Compose)
* Minimum **8 GB of RAM** (essential for Elasticsearch)

### Deployment Steps

1.  **Clone the Repository:**
    ```bash
    git clone 
    cd sentinel-kit
    ```

2.  **Launch the Stack:**
    ```bash
    docker-compose up -d
    ```
    *Startup may take several minutes, especially the first time, as Elasticsearch initializes and the backend installs its dependencies.*

3.  **Check Status:**
    ```bash
    docker-compose ps
    ```
    All services should be in the `Up` status.

---

## 🌐 Component Access

Once the stack is running, you can access the interfaces via the default ports exposed by the Caddy service:

| Service | Role | Default Access |
| :--- | :--- | :--- |
| **Web Interface** (Frontend) | Access to the main application | `http://localhost:80` or `https://localhost:443` (via Caddy) |
| **Kibana** | Exploration and visualization of Elastic logs | `http://localhost:5601` |
| **Grafana** | Monitoring dashboards | `http://localhost:3000` |
| **phpMyAdmin** | MySQL database management | `http://localhost:8080` |
| **SFTP Server** | Secure file/evidence upload | Port `2222` |

### Default Credentials (Utilities)

| Tool | Username | Password |
| :--- | :--- | :--- |
| **Grafana** | `sentinel-kit_grafana_admin` | `sentinel-kit_grafana_password` |
| **MySQL (DB)** | `sentinel-kit_user` | `sentinel-kit_passwd` |
| **SFTP** | `sentinel-kit_ftp_user` | `sentinel-kit_ftp_passwd` |

---

## 🛠️ Technical Architecture (via `docker-compose.yml`)

The architecture is modular and relies on the interconnection of several services via the **sentinel-kit-network** network.

### Application Components

* `sentinel-kit-frontend-app`: User Interface.
* `sentinel-kit-backend-app`: Business logic, API, and data management (depends on MySQL).
* `sentinel-kit-ftp-server`: Entry point for manual file collection (evidence, YARA/Sigma files).
* `sentinel-kit-caddy-server`: Reverse proxy managing HTTP/HTTPS access (ports 80/443) and routing to the frontend and  backend.

### Collection & Storage Components

* `sentinel-kit-fluentbit-server`: Log collector (Ingestion on port `24224`) that sends data to Elasticsearch.
* `sentinel-kit-elasticsearch-db`: Search and log storage engine.
* `sentinel-kit-mysql-db`: Relational database (for the backend).

### Utility Components (Monitoring & DB)

* `sentinel-kit-kibana-utils`: Visualization of Elasticsearch data (log analysis).
* `sentinel-kit-prometheus-utils`: Ingestion , parsing, and forwarding metrics collection.
* `sentinel-kit-grafana-utils`: Metrics visualization (Prometheus) and potentially other data.
* `sentinel-kit-phpmyadmin-utils`: Web interface for MySQL management (dev / admin).

## ⚙️ Configuration

Main configurations are located in the `config/` folder: (edit these elements only if you know what you are doing 😊)

* `config/fluentbit_server`: Fluent Bit configuration files (inputs, filters, outputs to Elasticsearch).
* `config/caddy_server`: Reverse proxy that serve front and back-end web applications
* `config/docker-config`: Server stack configuration (dockerfile, entrypoints...).
* `config/grafana`: Grafana initial setup (datasources and dashboards).
* `config/prometheus/prometheus.yml`: Prometheus monitoring targets configuration.
* `config/sigma_ruleset`: sigma rules used on elasticsearch ingested logs
* `config/yara_ruleset`: yara rules used on `data/yara_triage_data` folder or by *sentinel-kit_datamonitor* agent

## 📖 Data

Persistent data are located in the `data/` folder:

* `data/ftp_data`: Store file uploaded on the SFTP server
* `data/grafana`: Contains a persistence of your grafana profile if you want to make your own dashboard and customizations
* `data/log_ingest_data`: Is designed to forward logs if you don't want to use fluentbit HTTP forwarder
* `data/mysql_data`: Constains a persistence of the web backend database
* `data/yara_triage_data`: is used to automatically scan any file placed in this folder  

---

## 🛑 Stopping and Cleaning the Stack

To stop and remove the containers, networks, and volumes created by Docker Compose:

```bash
docker-compose down -v