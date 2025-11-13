# Sentinel-Kit - Stack documentation

**Sentinel Kit** is a comprehensive Docker stack designed to provide **Digital Forensics and Incident Response (DFIR)** and **Security Operations Center (SOC)** capabilities with unparalleled deployment simplicity.

## In which cases is it intended and how to deploy it
Sentinel Kit server launches as a `docker-compose` stack and exposes the following services. It is designed so that the Sentinel Kit server can be deployed directly within your information system or in the cloud. Main features are:
* **Log Collection & Parsing (SIEM Lite)**: Embedding a Elasticsearch cluster, indexing static logs files or forwarded ones. 
* **Advanced Analysis & Triage**: with **Sigma** rules for log-based detection and **YARA** for suspicious file triage
* **Secure Uploads**: Provides a dedicated **SFTP** server for uploading evidence, logs, or suspicious files.
* **Comprehensive Visualization**: Monitoring dashboards via **Kibana** and **Grafana/Prometheus**.
* **Detection and Response (EDR)**: An __optional__ dedicated agent (integrating into the ecosystem) to provide real-time detection and response functionalities if you don't have any or if you are not sure of its current security status. In addition, this agent can act as a collection element to forward logs from your workstations to the sentinel-kit server. It also provide some DLP features to investigate on potential insider threats.

## Documentation Menu
* [Sentinel-Kit quickstart](01-start-sentinel-kit.md)
* [Custommize your stack, credential, elastic cluster...](02-customize-stack.md)   
* [Create a first admin account](03-create-admin-user.md)
* [Logs quick Ingest](04-ingest-logs.md)
* [Ingest custom sources](05-ingest-custom-sources.md)
* [Monitor services](06-monitor-services.md)