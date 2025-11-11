# 🛠️ Environment Configuration

Most of the parameters for this stack are defined in the **`.env`** file located at the root directory. These settings are loaded **before the stack starts**. If the stack is already running, you must **restart it** for any changes to take effect.

---

## ⚙️ Services and Profiles

The following configuration block defines the active services using Docker Compose profiles and sets the Elasticsearch cluster mode.

```bash
COMPOSE_PROFILES=sftp,es-secondary-node,phpmyadmin,kibana,internal-monitoring
ELASTICSEARCH_CLUSTER_MODE=multi-node
``` 
## Profile Customization

You can remove certain services if you don't need them. Simply remove the corresponding profile from the `COMPOSE_PROFILES` list:

* Remove internal-monitoring to disable access to Grafana features.
* Remove phpmyadmin to disable direct access to the MySQL database.
* Remove sftp if no external file upload functionality is required.

## Elasticsearch Configuration

By default, Elasticsearch is configured as a two-node cluster (multi-node). For environments with limited resources, you can switch to a single-node setup by removing the `es-secondary-node` profile and updating `ELASTICSEARCH_CLUSTER_MODE` as shown below:

```bash
COMPOSE_PROFILES=sftp,phpmyadmin,kibana,internal-monitoring
ELASTICSEARCH_CLUSTER_MODE=single-node
```

**__Note:__** Switching between single-node and multi-node can be done at any point during the stack's lifecycle without requiring a complete reinstallation or data loss.

### Elasticsearch Memory Limit
To limit the memory allocated to the Elasticsearch cluster, modify the following variable (default is 4GB):
```bash
ELASTICSEARCH_MEMORY_LIMIT=4294967296
```


🌐 Domain Names
The hostnames for the exposed services are customizable here. It is mandatory to map these hostnames to the stack's IP address either in your DNS configuration or in your local hosts file (for isolated local installations).
| Service | Environment Variable | Default Hostname | 
| Frontend | SENTINELKIT_FRONTEND_HOSTNAME | sentinel-kit.local | 
| Backend API | SENTINELKIT_BACKEND_HOSTNAME | backend.sentinel-kit.local | 
| phpMyAdmin | SENTINELKIT_PMA_HOSTNAME | phpmyadmin.sentinel-kit.local | 
| Kibana | SENTINELKIT_KIBANA_HOSTNAME | kibana.sentinel-kit.local | 
| Grafana | SENTINELKIT_GRAFANA_HOSTNAME | grafana.sentinel-kit.local | 

🔒 Secrets and Credentials

For production usage, you can change any of the default credentials below.

⚠️ **__Important:__** Once the stack is initialized, changing certain secrets (like database or Elasticsearch credentials) can destabilize services. It is strongly recommended to modify these values only before the initial launch of the stack.

```bash
SENTINELKIT_DATAMONITOR_SERVER_TOKEN=9561ffd1b6de615286b9e52a9d5bc3226970449700c9461bdbe4225730b47b20
BACKEND_JWT_PASSPHRASE=f164cfc913d2faf65a1b7bc8ccd4aa8b11b5958bce7c20c8cf159a576f8a75f7
MYSQL_ROOT_PASSWORD=sentinel-kit_r00tp4ssw0rd
MYSQL_USER=sentinel-kit_user
MYSQL_PASSWORD=sentinel-kit_passwd
MYSQL_DATABASE=sentinel-kit_db
GF_SECURITY_ADMIN_USER=sentinel-kit_grafana_admin
GF_SECURITY_ADMIN_PASSWORD=sentinel-kit_grafana_password
SFTP_USER=sentinel-kit_sftp_user
SFTP_PASSWORD=sentinel-kit_sftp_passwd
ELASTICSEARCH_CLUSTER_NAME=sentinel-kit-elasticsearch-cluster
ELASTICSEARCH_PASSWORD=sentinelkit_elastic_passwd
```

## 🛑 Stopping and Cleaning the Stack

To only stop the stack, without removing existing data, just do the following command:
```bash
docker compose down
```

To stop __and remove the containers, networks, and volumes__ created by Docker Compose:

```bash
docker-compose down -v
```

If you want to erase all user data, and start from a fresh and clean installation, there is a `clean-user-data` sh or powershell (depending on your OS) to help you erasing all personal data. Then, you can rebuild the whole stack with: 

```bash
docker-compose up --build --force-recreate
```