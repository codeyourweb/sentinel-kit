# Monitoring Sentinel-Kit Services

Sentinel Kit includes a set of monitoring services that are enabled when the `internal-monitoring` profile is added to your `COMPOSE_PROFILES` in the `.env` file. These services rely on Prometheus, which is queried by Grafana.

By default, Grafana is accessible at:  
**[https://grafana.sentinel-kit.local](https://grafana.sentinel-kit.local)**

### Access Credentials

The access credentials for Grafana can be customized in the `.env` file:
```bash
GF_SECURITY_ADMIN_USER=sentinel-kit_grafana_admin
GF_SECURITY_ADMIN_PASSWORD=sentinel-kit_grafana_password
```
### Initial Setup

In its default configuration, Sentinel-Kit does not include any custom dashboards. However, the services for `fluentbit`, `mysql`, and `elasticsearch` are already configured within the platform. You can access all available metrics under the `metrics` section, and it is also possible to filter by service (jobs).

![Sentinel-Kit Grafana](img/sentinel-kit_grafana.png)

### Importing Additional Dashboards

You can import many additional dashboards from the official [Grafana website](https://grafana.com/grafana/dashboards/).

### Performance Consideration

**Warning**: On systems with limited memory or CPU resources, it is recommended to avoid enabling the `internal-monitoring` profile to ensure optimal performance.