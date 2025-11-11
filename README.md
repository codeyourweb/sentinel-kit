![Sentinel Kit](docs/img/sentinel-kit_logo.png)
# 🛡️ Sentinel Kit: The Simplified Platform for Incident Response (SOC & DFIR)

$\color{red}{\textsf{**WARNING**: This project is currently in an early stage of development. Not all components have been ported to this repository, and the features are not yet stable enough for production use.}}$

The features already available online are documented [here](docs/index.md).
---

**Sentinel Kit** is a comprehensive Docker stack designed to provide **Digital Forensics and Incident Response (DFIR)** and **Security Operations Center (SOC)** capabilities with unparalleled deployment simplicity.

Ideal for **situational monitoring** or **small-scale security incident response**, this integrated platform enables collection, analysis, detection, and immediate response to threats.

---

## ✨ Key Features

Sentinel Kit is an all-in-one toolkit that covers the entire security incident lifecycle:

* **Log Collection & Parsing (SIEM Lite)**: Uses **Fluent Bit** for data ingestion and **Elasticsearch** for storage and indexing.
* **Advanced Analysis & Triage**: Planned integration of **Sigma** rules for log-based detection and **YARA** for suspicious file triage (via upload mechanisms).
* **Detection and Response (EDR)**: A dedicated agent (integrating into the ecosystem) provide real-time detection and response functionalities. In addition, this optional agent can act as a collection element to forward logs from your workstations to the sentinel-kit server.
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

2. **Set the following DNS entry (in hosts file if you are running it locally):**
```bash
# OS host file
127.0.0.1   sentinel-kit.local
127.0.0.1   backend.sentinel-kit.local
127.0.0.1   phpmyadmin.sentinel-kit.local
127.0.0.1   kibana.sentinel-kit.local
127.0.0.1   grafana.sentinel-kit.local
```

3.  **Launch the Stack:**
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
| **Web Interface** (Admin frontend) | Access to the admin application | `https://sentinel-kit.local` |
| **Web API**  | Used for clients<->server communications and admin actions over the web interface | `https://backend.sentinel-kit.local` |
| **Kibana** | Exploration and visualization of Elastic logs | `http://kibana.sentinel-kit.local` |
| **Grafana** | Monitoring dashboards | `http://grafana.sentinel-kit.local` |
| **phpMyAdmin** | MySQL database management | `http://phpmyadmin.sentinel-kit.local` |
| **SFTP Server** | Secure file/evidence upload | Port `2222` |

### Default Credentials (Utilities)

| Tool | Username | Password |
| :--- | :--- | :--- |
| **Grafana** | `sentinel-kit_grafana_admin` | `sentinel-kit_grafana_password` |
| **MySQL (DB)** | `sentinel-kit_user` | `sentinel-kit_passwd` |
| **SFTP** | `sentinel-kit_ftp_user` | `sentinel-kit_ftp_passwd` |

All of this can be edited in `.env` file

---

## 🛠️ Technical Architecture (via `docker-compose.yml`)

The architecture is modular and relies on the interconnection of several services
![Sentinel-Kit architecture](docs/img/sentinel-kit_network_flow.png)

## ⚙️ Configuration

Main configurations are located in the `config/` folder: (edit these elements only if you know what you are doing 😊)

* `config/caddy_server`: Reverse proxy that serve front and back-end web applications.
* `config/docker-config`: Server stack configuration (dockerfile, entrypoints...).
* `config/elasticsearch`: Configuration of the Elasticsearch certification chain and nodes cluster.
* `config/fluentbit_server`: Fluent Bit configuration files (inputs, filters, outputs to Elasticsearch).
* `config/grafana`: Grafana initial setup (datasources and dashboards).
* `config/prometheus`: Prometheus monitoring targets configuration.
* `config/sigma_ruleset`: sigma rules used on elasticsearch ingested logs
* `config/yara_ruleset`: yara rules used on `data/yara_triage_data` folder or by *sentinel-kit_datamonitor* agent

## 📖 Data

Persistent data are located in the `data/` folder:

* `data/caddy_logs`: Store the caddy server access & error logs.
* `data/fluentbit_db`: fluentbit ingest database (to avoid indexing same data several times).
* `data/ftp_data`: Store file uploaded on the SFTP server.
* `data/grafana`: Contains a persistence of your grafana profile if you want to make your own dashboard and customizations.
* `data/kibana`: Kibana user customizations (dashboard, config...).
* `data/log_ingest_data`: Is designed to forward logs if you don't want to use fluentbit HTTP forwarder.
* `data/mysql_data`: Constains a persistence of the web backend database.
* `data/yara_triage_data`: is used to automatically scan any file placed in this folder.  

---

## 🛑 Stopping and Cleaning the Stack

To stop and remove the containers, networks, and volumes created by Docker Compose:

```bash
docker-compose down -v
```

If you want to erase all user data, and start from a fresh and clean installation, there is a `clean-user-data` sh or powershell (depending on your OS) to help you erasing all personal data. Then, you can rebuild the whole stack with: 

```bash
docker-compose up --build --force-recreate
```