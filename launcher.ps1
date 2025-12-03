param(
    [ValidateSet("start", "stop", "build", "clean-data", "console", "logs", "status", "help")]
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
    Write-Host "  start       Start the Docker stack" -ForegroundColor White
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

function Convert-ShellScriptsToLF {
    Write-Host "Converting shell scripts to Unix line endings (LF)..." -ForegroundColor $InfoColor
    
    $shFiles = Get-ChildItem -Path "." -Recurse -Name "*.sh" -File
    $convertedCount = 0
    
    foreach ($file in $shFiles) {
        try {
            $content = Get-Content -Path $file -Raw
            if ($content -and $content.Contains("`r`n")) {
                $content = $content -replace "`r`n", "`n"
                [System.IO.File]::WriteAllText((Resolve-Path $file).Path, $content)
                Write-Host "  Converted: $file" -ForegroundColor $GrayColor
                $convertedCount++
            }
        }
        catch {
            Write-Host "  Warning: Failed to convert $file - $($_.Exception.Message)" -ForegroundColor $WarningColor
        }
    }
    
    if ($convertedCount -gt 0) {
        Write-Host "Converted $convertedCount shell script(s) to Unix line endings." -ForegroundColor $SuccessColor
    } else {
        Write-Host "No shell scripts needed line ending conversion." -ForegroundColor $SuccessColor
    }
}

function Wait-ForHealthyServices {
    Write-Host "Waiting for backend and frontend to be healthy..." -ForegroundColor $InfoColor
    
    $maxAttempts = 60
    $attempt = 0
    $backendHealthy = $false
    $frontendHealthy = $false
    
    while ($attempt -lt $maxAttempts) {
        $attempt++
        
        if (-not $backendHealthy) {
            $backendHealth = Invoke-Expression "docker compose ps --format json sentinel-kit-app-backend" -ErrorAction SilentlyContinue
            if ($LASTEXITCODE -eq 0 -and $backendHealth) {
                $healthStatus = ($backendHealth | ConvertFrom-Json).Health
                if ($healthStatus -eq "healthy") {
                    Write-Host "  Backend is healthy!" -ForegroundColor $SuccessColor
                    $backendHealthy = $true
                }
            }
        }
        
        if (-not $frontendHealthy) {
            $frontendHealth = Invoke-Expression "docker compose ps --format json sentinel-kit-app-frontend" -ErrorAction SilentlyContinue
            if ($LASTEXITCODE -eq 0 -and $frontendHealth) {
                $healthStatus = ($frontendHealth | ConvertFrom-Json).Health
                if ($healthStatus -eq "healthy") {
                    Write-Host "  Frontend is healthy!" -ForegroundColor $SuccessColor
                    $frontendHealthy = $true
                }
            }
        }
        
        if ($backendHealthy -and $frontendHealthy) {
            Write-Host "All critical services are healthy and ready!" -ForegroundColor $SuccessColor
            return $true
        }
        
        if ($attempt % 10 -eq 0) {
            Write-Host "  Still waiting... (attempt $attempt/$maxAttempts)" -ForegroundColor $WarningColor
        }
        
        Start-Sleep -Seconds 10
    }
    
    Write-Host "Timeout: Services did not become healthy within the expected time." -ForegroundColor $ErrorColor
    return $false
}

if ($Help) {
    Show-Help
    exit 0
}

switch ($Command.ToLower()) {
    "help" { 
        Show-Help 
    }
    "start" { 
        Write-Host ""
        Write-Host "========== STARTING SENTINELKIT ==========" -ForegroundColor $HeaderColor
        
        if (-not (Test-DockerCompose)) {
            exit 1
        }
        
        Convert-ShellScriptsToLF
        
        $RunningContainers = Get-RunningContainers
        
        if ($RunningContainers) {
            Write-Host "Docker stack is already running." -ForegroundColor $InfoColor
            if (Wait-ForHealthyServices) {
                Write-Host "Success! The Docker stack is running and healthy." -ForegroundColor $SuccessColor
            } else {
                Write-Host "Warning: Some services may not be fully healthy yet." -ForegroundColor $WarningColor
            }
        } else {
            Write-Host "Starting the Docker stack..." -ForegroundColor $InfoColor
            Invoke-Expression "docker compose up -d"
            
            if ($LASTEXITCODE -eq 0) {
                if (Wait-ForHealthyServices) {
                    Write-Host "Success! The Docker stack has been launched and is healthy." -ForegroundColor $SuccessColor
                } else {
                    Write-Host "Warning: Docker stack started but some services are not healthy." -ForegroundColor $WarningColor
                }
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
        
        Convert-ShellScriptsToLF
        
        Write-Host "Building and starting the Docker stack..." -ForegroundColor $InfoColor
        Invoke-Expression "docker compose up -d --build --force-recreate"
        
        if ($LASTEXITCODE -eq 0) {
            if (Wait-ForHealthyServices) {
                Write-Host "Success! The Docker stack has been built, started and is healthy." -ForegroundColor $SuccessColor
            } else {
                Write-Host "Warning: Docker stack built and started but some services are not healthy." -ForegroundColor $WarningColor
            }
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
            Write-Host "Please start the stack first using: .\launcher.ps1 start" -ForegroundColor $WarningColor
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
            'mysql' = 'sentinel-kit-db-mysql'
            'elasticsearch' = 'sentinel-kit-db-elasticsearch-es01'
            'kibana' = 'sentinel-kit-utils-kibana'
            'scanner' = 'sentinel-kit-server-rules-scanner'
            'forwarder' = 'sentinel-kit-server-fluentbit'
            'caddy' = 'sentinel-kit-server-caddy'
            'grafana' = 'sentinel-kit-utils-grafana'
            'prometheus' = 'sentinel-kit-utils-prometheus'
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
                "./sentinel-kit_server_frontend/.production_build_complete",
                "./sentinel-kit_server_frontend/.source_hash",
                "./sentinel-kit_server_frontend/node_modules",
                "./sentinel-kit_server_frontend/package-lock.json",
                "./sentinel-kit_server_frontend/dist",
                "./sentinel-kit_server_backend/.cache_ready",
                "./sentinel-kit_server_backend/.composer_hash",
                "./sentinel-kit_server_backend/.source_hash",
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