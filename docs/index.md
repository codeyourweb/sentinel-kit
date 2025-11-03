# About Sentinel-Kit - Stack documentation

**Sentinel Kit** is a comprehensive Docker stack designed to provide **Digital Forensics and Incident Response (DFIR)** and **Security Operations Center (SOC)** capabilities with unparalleled deployment simplicity.

Ideal for **situational monitoring** or **rapid security incident response**, this integrated platform enables collection, analysis, detection, and immediate response to threats.

## In which cases is it intended and how to deploy it
Sentinel Kit server launches as a `docker-compose` stack and exposes the following services.

This stack is designed so that the Sentinel Kit server can be deployed directly within your information system or in the cloud. Main features are:
* **Log Collection & Parsing (SIEM Lite)**: 
* **Advanced Analysis & Triage**: with **Sigma** rules for log-based detection and **YARA** for suspicious file triage
* **Secure Uploads**: Provides a dedicated **SFTP** server for uploading evidence, logs, or suspicious files.
* **Comprehensive Visualization**: Monitoring dashboards via **Kibana** and **Grafana/Prometheus**.
* **Detection and Response (EDR)**: An __optional__ dedicated agent (integrating into the ecosystem) to provide real-time detection and response functionalities if you don't have any or if you are not sure of its current security status. In addition, this agent can act as a collection element to forward logs from your workstations to the sentinel-kit server. It also provide some DLP features to investigate on potential insider threats.

## Security warning
Although the code in this stack is designed to be secure in terms of authentication and client/server exchanges (strong identification, certificates, JWT, etc.), it is your responsibility to limit the server's exposure (flow filtering, whitelisting, etc.). No filtering mechanism is provided in the project, so configure your flows to limit exposure as follows:

![Sentinel Kit architecture and network flow](img/sentinel-kit_network_flow.png)

**Exposed services:**
| **Web API**  | TCP/8443 | Used for clients<->server communications and admin actions over the web interface | 
| **SFTP Server** | TCP/2222 | Secure file/evidence upload | 

optional - for interoperability purpose with a forwarder like logstash / winlogbeat / fluentbit etc... It could also be done over Web API (see documentation)
| **Syslog forwarder** | TCP/24224 | Fluentbit log forwarder to elasticsearch 

**Admin access** 
| **Web front end** | TCP/443 | Access to the admin application (agents control, log analysis...) |

**Admin extended features**
| **PhpMyAdmin** | TCP/8080 | MySQL database management | 
| **Grafana** | TCP/3000 | Monitoring dashboards (fluentbit forwarder logs / elasticsearch ingestion / stack health)
| **Kibana** | TCP/5601 | Kibana complete access (for advanced usage only. Standard features are directly implemented in the admin web interface) 

## Let's start 
Everything comes as a full docker-compose stack to avoid configuration and depencies and simplify the deployment. 

First, in a local environment, you need to define the following DNS entries: 
```bash
# OS host file
127.0.0.1   sentinel-kit.local
127.0.0.1   backend.sentinel-kit.local
127.0.0.1   phpmyadmin.sentinel-kit.local
127.0.0.1   kibana.sentinel-kit.local
127.0.0.1   grafana.sentinel-kit.local
```

On advanced configuration, you can configure `config/caddy_server/Caddyfile` if you want to set your own nameserver. If so, you will also need to set your hostname, replacing the original ones in `.env` file

When your DNS configuration is ok. Start the stack with: 
```bash
    docker-compose up -d
```
Initial startup could be long as elastic configure two nodes and kibana. 

Caddy server will generate a complete certification chain for the exposed services (frontend, backend, phpmyadmin, kibana, grafana). If you don't want to always accept untrusted certification chains, add root and intermediate CA to your browser certificates. They are located in ./config/certificates/caddy_server

[https://sentinel-kit.local:8443/]https://sentinel-kit.local:8443 should return the following page:
![Sentinel Kit Homepage](img/sentinel-kit_homepage.png)

And, that's all, you are good to go!   
[Create a first admin account](01-create-admin-user.md)