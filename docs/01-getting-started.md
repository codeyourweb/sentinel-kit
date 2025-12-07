# 🚀 Getting Started with Sentinel Kit

This guide walks you through the initial setup of Sentinel Kit, from deployment to first access.

---

## Prerequisites

Before starting, ensure your system meets the following requirements:

### System Requirements
- **Operating System**: Windows 10/11, Linux, or macOS
- **Memory**: Minimum 8 GB RAM (16 GB recommended)
- **Storage**: At least 20 GB free disk space
- **Network**: Internet access for initial container downloads

### Required Software
- **Docker Desktop** or **Docker Engine** with Docker Compose
- **PowerShell** (Windows) or **Bash** (Linux/macOS)
- **Git** for cloning the repository

---

## Step 1: Download and Setup

### Clone the Repository
```bash
git clone https://github.com/codeyourweb/sentinel-kit.git
cd sentinel-kit
```

### 🛠️ Environment Configuration

Most of the parameters for this stack are defined in the **`.env`** file located at the root directory. These settings are loaded **before the stack starts**. If the stack is already running, you must **restart it** for any changes to take effect.

---

### ⚙️ Services and Profiles

The following configuration block defines the active services using Docker Compose profiles and sets the Elasticsearch cluster mode.

```bash
COMPOSE_PROFILES=sftp,es-secondary-node,phpmyadmin,kibana,internal-monitoring
ELASTICSEARCH_CLUSTER_MODE=multi-node
``` 
### Profile Customization

You can remove certain services if you don't need them. Simply remove the corresponding profile from the `COMPOSE_PROFILES` list:

* Remove internal-monitoring to disable access to Grafana features.
* Remove phpmyadmin to disable direct access to the MySQL database.
* Remove sftp if no external file upload functionality is required.

### Elasticsearch Configuration

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

### Configure DNS Resolution (Local Development)

Use the integrated console command as __Administrator__:
```bash
./launcher.sh local-dns-install
```

Or, if you want to do it manually, add these entries to your system's hosts file:

**Windows**: Edit `C:\Windows\System32\drivers\etc\hosts`
**Linux/macOS**: Edit `/etc/hosts`

 Add to your hosts file :
```
127.0.0.1   sentinel-kit.local
127.0.0.1   backend.sentinel-kit.local
127.0.0.1   phpmyadmin.sentinel-kit.local
127.0.0.1   kibana.sentinel-kit.local
127.0.0.1   grafana.sentinel-kit.local

```

---

## Step 2: Launch the Platform

### Using the Launcher (Recommended)

**Windows PowerShell:**
```powershell
./launcher.ps1 start
```

**Linux/macOS:**
```bash
./launcher.sh start
```

The launcher will:
- Check system prerequisites
- Build and start all required services
- Display service startup progress
- Provide access information once ready

### Manual Docker Compose (Alternative)
```bash
docker-compose up -d
```

**Note**: First startup takes 5-10 minutes as services initialize and dependencies download.

---

## Step 3: Verify Installation

### Check Service Status
```powershell
# Using launcher
./launcher status

# Using Docker directly  
docker-compose ps
```

All services should show as "Up" or "running":
- `sentinel-kit-app-frontend`
- `sentinel-kit-app-backend`
- `sentinel-kit-app-db-elasticsearch-es01`
- `sentinel-kit-app-db-elasticsearch-es02` (only if you run a elastic cluster)
- `sentinel-kit-app-utils-kibana`
- `sentinel-kit-app-utils-grafana`
- `sentinel-kit-app-db-mysql`
- `sentinel-kit-app-server-fluentbit`
- `sentinel-kit-app-server-caddy`

### Access the Platform
Once services are running:

1. **Open your browser** and navigate to: `https://sentinel-kit.local`
2. **Accept the SSL certificate** (self-signed for local development)
3. **You should see the Sentinel Kit login page**

---

## Step 4: Create First Admin User

### Using the Backend Console

1. **Access the backend container:**
   Recommanded way - with the integrated console app
   ```bash
   ./launcher console
   ```

   or with a docker exec command:

   ```bash
   docker exec -it sentinel-kit-app-backend bash
   ```

2. **Run the user creation command:**
   ```bash
   php bin/console app:users:create
   ```

   Follow the instructions to create your first user
---

## Step 5: First Login

### Access the Dashboard

1. **Navigate to**: `https://sentinel-kit.local`
2. **Enter your credentials**:
   - Username: `admin` (or your chosen username)
   - Password: Your secure password
3. **Complete 2FA setup**
4. **Access the main dashboard**

### Dashboard Overview
Upon first login, you'll see:
- **Service Status Panel**: Shows health of all platform components
- **Recent Alerts**: Currently empty (no rules or data sources configured yet)
- **Data Sources**: Shows ingestion status and volume
- **Quick Actions**: Access to rule management and configuration
---

## Users secrets reset
Password resetting could be done
```bash
backend:/var/www/html# php bin/console app:users:renew-password demo@example.com MyNewPa$$w0rd

 [OK] User password reset successful.                                                                                   
```                                                                                                                        

And you can also reset user OTP:
```bash
backend:/var/www/html# php bin/console app:users:renew-otp demo@example.com

 [OK] User OTP reset successful.                                                                                      
```

## Next Steps

Now that your platform is running:

1. **[Configure Data Ingestion](02-data-ingestion.md)** - Set up log sources and collection
2. **[Create Detection Rules](04-sigma-rules.md)** - Build your first Sigma rules
3. **[Monitor Platform Health](06-monitoring-health.md)** - Learn about system monitoring

---

## Troubleshooting

### Common Issues

**Services won't start:**
- Check available memory (need 8GB+ free)
- Verify Docker Desktop is running
- Check port conflicts (80, 443, 9200)

**Can't access web interface:**
- Verify hosts file configuration
- Check if Caddy service is running

**Elasticsearch fails to start:**
- Increase Docker memory allocation to 4GB+
- Check disk space (needs 10GB+ available)
- Wait longer - Elasticsearch startup can take 5+ minutes

### Getting Help
- View service logs: `./launcher logs`
- Report issues on GitHub with system information
---

*Next: [Data Ingestion Setup →](02-data-ingestion.md)*