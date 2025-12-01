param(
    [ValidateSet("run", "stop", "build", "clean-data", "console", "logs", "status", "help")]
    [string]$Command = "help",
    [string]$ServiceName = "",
    [switch]$Follow,
    [switch]$Help
)

$SuccessColor = "Green"
$ErrorColor = "Red" 
$WarningColor = "Yellow"
$InfoColor = "Cyan"
$HeaderColor = "Magenta"
$GrayColor = "Gray"

function Show-Help {
    Write-Host ""
    Write-Host "=============================================" -ForegroundColor $HeaderColor
    Write-Host "       Sentinel-Kit Management Script        " -ForegroundColor $HeaderColor
    Write-Host "=============================================" -ForegroundColor $HeaderColor
    Write-Host ""
    Write-Host "USAGE:" -ForegroundColor $InfoColor
    Write-Host "  .\launcher.ps1 <command>" -ForegroundColor White
    Write-Host ""
    Write-Host "COMMANDS:" -ForegroundColor $InfoColor
    Write-Host "  run         Start the Docker stack" -ForegroundColor White
    Write-Host "  stop        Stop the running Docker stack" -ForegroundColor White  
    Write-Host "  build       Build and start the Docker stack" -ForegroundColor White
    Write-Host "  clean-data  Clean all user data and stop containers" -ForegroundColor White
    Write-Host "  console     Access Sentinel-Kit console" -ForegroundColor White
    Write-Host "  logs        Show Docker container logs" -ForegroundColor White
    Write-Host "  status      Show container status" -ForegroundColor White
    Write-Host "  help        Show this help message" -ForegroundColor White
    Write-Host ""
    Write-Host "OPTIONS:" -ForegroundColor $InfoColor
    Write-Host "  -Follow     Follow log output (for logs command)" -ForegroundColor White
    Write-Host ""
}

function Test-DockerCompose {
    Write-Host "Checking for 'docker compose' presence..." -ForegroundColor $WarningColor
    
    $TestResult = Invoke-Expression "docker compose version" -ErrorAction SilentlyContinue
    if ($LASTEXITCODE -eq 0 -and $TestResult) {
        Write-Host "'docker compose' is available." -ForegroundColor $SuccessColor
        return $true
    } else {
        Write-Host "Docker Compose not available." -ForegroundColor $ErrorColor
        return $false
    }
}

function Get-RunningContainers {
    $result = Invoke-Expression "docker compose ps -q" -ErrorAction SilentlyContinue
    if ($LASTEXITCODE -eq 0 -and $result) {
        return $result
    }
    return $null
}

if ($Help) {
    Show-Help
    exit 0
}

switch ($Command.ToLower()) {
    "help" { 
        Show-Help 
    }
    "run" { 
        Write-Host ""
        Write-Host "========== STARTING SENTINELKIT ==========" -ForegroundColor $HeaderColor
        
        if (-not (Test-DockerCompose)) {
            exit 1
        }
        
        $RunningContainers = Get-RunningContainers
        
        if ($RunningContainers) {
            Write-Host "Docker stack is already running." -ForegroundColor $InfoColor
        } else {
            Write-Host "Starting the Docker stack..." -ForegroundColor $InfoColor
            Invoke-Expression "docker compose up -d"
            
            if ($LASTEXITCODE -eq 0) {
                Write-Host "Success! The Docker stack has been launched." -ForegroundColor $SuccessColor
            } else {
                Write-Host "Error starting containers." -ForegroundColor $ErrorColor
            }
        }
    }
    "stop" { 
        Write-Host ""
        Write-Host "========== STOPPING SENTINELKIT ==========" -ForegroundColor $HeaderColor
        
        if (-not (Test-DockerCompose)) {
            exit 1
        }
        
        $RunningContainers = Get-RunningContainers
        
        if ($RunningContainers) {
            Write-Host "Stopping the Docker stack..." -ForegroundColor $InfoColor
            Invoke-Expression "docker compose down"
            
            if ($LASTEXITCODE -eq 0) {
                Write-Host "Success! The Docker stack has been stopped." -ForegroundColor $SuccessColor
            } else {
                Write-Host "Error stopping containers." -ForegroundColor $ErrorColor
            }
        } else {
            Write-Host "No running containers found." -ForegroundColor $InfoColor
        }
    }
    "build" { 
        Write-Host ""
        Write-Host "========== BUILDING SENTINELKIT ==========" -ForegroundColor $HeaderColor
        
        if (-not (Test-DockerCompose)) {
            exit 1
        }
        
        Write-Host "Building and starting the Docker stack..." -ForegroundColor $InfoColor
        Invoke-Expression "docker compose up -d --build --force-recreate"
        
        if ($LASTEXITCODE -eq 0) {
            Write-Host "Success! The Docker stack has been built and started." -ForegroundColor $SuccessColor
        } else {
            Write-Host "Error building containers." -ForegroundColor $ErrorColor
        }
    }
    "console" { 
        Write-Host ""
        Write-Host "========== SENTINELKIT CONSOLE ==========" -ForegroundColor $HeaderColor
        
        if (-not (Test-DockerCompose)) {
            exit 1
        }
        
        $backendContainer = Invoke-Expression "docker compose ps -q sentinel-kit-app-backend" -ErrorAction SilentlyContinue
        
        if (-not $backendContainer) {
            Write-Host "Backend container is not running." -ForegroundColor $ErrorColor
            Write-Host "Please start the stack first using: .\launcher.ps1 run" -ForegroundColor $WarningColor
            exit 1
        }
        
        if ($ServiceName) {
            Write-Host "Executing: php bin/console $ServiceName" -ForegroundColor $InfoColor
            Invoke-Expression "docker compose exec sentinel-kit-app-backend php bin/console $ServiceName"
        } else {
            Write-Host "Backend container found. Starting interactive console..." -ForegroundColor $SuccessColor
            Write-Host ""
            Write-Host "=========================================" -ForegroundColor $InfoColor
            Write-Host " Sentinel-Kit Console - Interactive Mode " -ForegroundColor $InfoColor
            Write-Host "=========================================" -ForegroundColor $InfoColor
            Write-Host "Type your console commands" -ForegroundColor $WarningColor
            Write-Host "Examples:" -ForegroundColor $WarningColor
            Write-Host ""
            Write-Host "  app                     # List all sentinel-kit application commands" -ForegroundColor $GrayColor
            Write-Host "  cache:clear             # Force backend cache clear" -ForegroundColor $GrayColor
            Write-Host "  help                    # List all built-in commands" -ForegroundColor $GrayColor
            Write-Host "Type 'exit' to return to PowerShell" -ForegroundColor $WarningColor
            Write-Host ""
            
            while ($true) {
                Write-Host "sentinel-kit> " -NoNewline -ForegroundColor $InfoColor
                $commandLine = Read-Host
                
                if ($commandLine -eq "exit" -or $commandLine -eq "quit") {
                    Write-Host "Exiting Sentinel-Kit console..." -ForegroundColor $InfoColor
                    break
                }
                
                if ([string]::IsNullOrWhiteSpace($commandLine)) {
                    continue
                }
                
                Write-Host ""
                Write-Host "Executing: php bin/console $commandLine" -ForegroundColor $InfoColor
                Write-Host "----------------------------------------" -ForegroundColor $GrayColor
                
                $dockerCmd = "docker compose exec sentinel-kit-app-backend php bin/console $commandLine"
                try {
                    Invoke-Expression $dockerCmd
                    if ($LASTEXITCODE -ne 0) {
                        Write-Host "Command completed with exit code: $LASTEXITCODE" -ForegroundColor $WarningColor
                    }
                }
                catch {
                    Write-Host "Error executing command: $($_.Exception.Message)" -ForegroundColor $ErrorColor
                }
                
                Write-Host ""
            }
        }
    }
    "logs" { 
        Write-Host ""
        Write-Host "========== DOCKER LOGS ==========" -ForegroundColor $HeaderColor
        
        if (-not (Test-DockerCompose)) {
            exit 1
        }
        
        $serviceMap = @{
            'backend' = 'sentinel-kit-app-backend'
            'frontend' = 'sentinel-kit-app-frontend'
            'mysql' = 'sentinel-kit-mysql'
            'elasticsearch' = 'sentinel-kit-elasticsearch'
            'kibana' = 'sentinel-kit-kibana'
        }
        
        if ($ServiceName) {
            $actualServiceName = $serviceMap[$ServiceName.ToLower()]
            if (-not $actualServiceName) {
                $actualServiceName = $ServiceName
            }
            
            if ($Follow) {
                Write-Host "Following logs for service: $actualServiceName" -ForegroundColor $InfoColor
                Invoke-Expression "docker compose logs -f $actualServiceName"
            } else {
                Write-Host "Showing logs for service: $actualServiceName" -ForegroundColor $InfoColor
                Invoke-Expression "docker compose logs $actualServiceName"
            }
        } else {
            if ($Follow) {
                Write-Host "Following logs for all services" -ForegroundColor $InfoColor
                Invoke-Expression "docker compose logs -f"
            } else {
                Write-Host "Showing logs for all services" -ForegroundColor $InfoColor
                Invoke-Expression "docker compose logs"
            }
        }
    }
    "status" { 
        Write-Host ""
        Write-Host "========== DOCKER STATUS ==========" -ForegroundColor $HeaderColor
        
        if (-not (Test-DockerCompose)) {
            exit 1
        }
        
        Write-Host "Docker Compose Services Status:" -ForegroundColor $InfoColor
        Invoke-Expression "docker compose ps"
        
        if ($LASTEXITCODE -eq 0) {
            Write-Host ""
            Write-Host "Container Details:" -ForegroundColor $InfoColor
            $runningContainers = Invoke-Expression "docker compose ps -q" -ErrorAction SilentlyContinue
            
            if ($runningContainers) {
                foreach ($containerId in $runningContainers) {
                    if ($containerId.Trim()) {
                        $containerInfo = Invoke-Expression "docker inspect $containerId --format '{{.Name}} - Status: {{.State.Status}}'" -ErrorAction SilentlyContinue
                        if ($containerInfo) {
                            Write-Host "$containerInfo" -ForegroundColor $SuccessColor
                        }
                    }
                }
            } else {
                Write-Host "No containers are running" -ForegroundColor $WarningColor
            }
        }
    }
    "clean-data" { 
        Write-Host ""
        Write-Host "========== CLEANING USER DATA ==========" -ForegroundColor $HeaderColor
        Write-Host ""
        Write-Host "WARNING: This will permanently delete ALL user data!" -ForegroundColor $WarningColor
        Write-Host ""
        
        $confirmation = Read-Host "Type 'yes' to proceed"
        
        if ($confirmation.ToLower() -eq "yes") {
            Write-Host ""
            Write-Host "Starting cleanup process..." -ForegroundColor $WarningColor
            
            $itemsToRemove = @(
                "./sentinel-kit_server_frontend/node_modules",
                "./sentinel-kit_server_frontend/package-lock.json",
                "./sentinel-kit_server_frontend/dist",
                "./sentinel-kit_server_backend/.initial_setup_done",
                "./sentinel-kit_server_backend/composer.lock",
                "./sentinel-kit_server_backend/symfony.lock",
                "./sentinel-kit_server_backend/var",
                "./sentinel-kit_server_backend/vendor",
                "./sentinel-kit_server_backend/migrations/*",
                "./sentinel-kit_server_backend/config/jwt/*.pem",
                "./config/elastalert_ruleset/*",
                "./config/caddy_server/certificates/*",
                "./data/caddy_logs/*",
                "./data/ftp_data/*",
                "./data/grafana/*",
                "./data/kibana/*",
                "./data/log_ingest_data/auditd/*",
                "./data/log_ingest_data/evtx/*",
                "./data/log_ingest_data/json/*",
                "./data/fluentbit_db/*",
                "./data/yara_triage_data/*"
            )
            
            $successCount = 0
            $errorCount = 0
            
            foreach ($item in $itemsToRemove) {
                if ($item.Contains("*")) {
                    # Handle wildcard paths
                    $basePath = Split-Path $item -Parent
                    $pattern = Split-Path $item -Leaf
                    
                    if (Test-Path $basePath) {
                        try {
                            $files = Get-ChildItem -Path $basePath -Filter $pattern -ErrorAction SilentlyContinue
                            if ($files) {
                                Remove-Item -Path $files.FullName -Recurse -Force -ErrorAction Stop
                                Write-Host "Removed: $item" -ForegroundColor $SuccessColor
                                $successCount++
                            } else {
                                Write-Host "Skipped: $item (not found)" -ForegroundColor $GrayColor
                            }
                        } catch {
                            Write-Host "Failed to remove: $item" -ForegroundColor $ErrorColor
                            $errorCount++
                        }
                    } else {
                        Write-Host "Skipped: $item (path not found)" -ForegroundColor $GrayColor
                    }
                } else {
                    # Handle regular paths
                    if (Test-Path $item) {
                        try {
                            Remove-Item -Path $item -Recurse -Force -ErrorAction Stop
                            Write-Host "Removed: $item" -ForegroundColor $SuccessColor
                            $successCount++
                        } catch {
                            Write-Host "Failed to remove: $item" -ForegroundColor $ErrorColor
                            $errorCount++
                        }
                    } else {
                        Write-Host "Skipped: $item (not found)" -ForegroundColor $GrayColor
                    }
                }
            }
            
            Write-Host ""
            Write-Host "Stopping Docker containers and removing volumes..." -ForegroundColor $WarningColor
            
            if (Test-DockerCompose) {
                Invoke-Expression "docker compose down -v"
                
                if ($LASTEXITCODE -eq 0) {
                    Write-Host "Docker containers and volumes removed successfully." -ForegroundColor $SuccessColor
                    $successCount++
                } else {
                    Write-Host "Error stopping Docker containers. Exit Code: $LASTEXITCODE" -ForegroundColor $ErrorColor
                    $errorCount++
                }
            }
            
            Write-Host ""
            Write-Host "--------------------------------------------------------" -ForegroundColor $HeaderColor
            Write-Host "Cleanup Summary:" -ForegroundColor $HeaderColor
            Write-Host "Successful operations: $successCount" -ForegroundColor $SuccessColor
            if ($errorCount -gt 0) {
                Write-Host "Failed operations: $errorCount" -ForegroundColor $ErrorColor
            }
            Write-Host "--------------------------------------------------------" -ForegroundColor $HeaderColor
            
            if ($errorCount -eq 0) {
                Write-Host "All user data has been successfully cleaned!" -ForegroundColor $SuccessColor
            } else {
                Write-Host "Cleanup completed with some errors. Check the output above." -ForegroundColor $WarningColor
            }
        } else {
            Write-Host "Operation cancelled." -ForegroundColor $InfoColor
        }
    }
    default { 
        Write-Host "Unknown command: $Command" -ForegroundColor $ErrorColor
        Write-Host ""
        Show-Help 
    }
}