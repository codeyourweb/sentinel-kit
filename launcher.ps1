param(
    [Parameter(Position=0)]
    [ValidateSet("run", "stop", "build", "clean-data", "console", "help")]
    [string]$Command = "help",
    
    [switch]$Help
)

$SuccessColor = "Green"
$ErrorColor = "Red" 
$WarningColor = "Yellow"
$InfoColor = "Cyan"
$HeaderColor = "Magenta"

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
    Write-Host "  run         Start the Docker stack (using existing images)" -ForegroundColor White
    Write-Host "  stop        Stop the running Docker stack" -ForegroundColor White  
    Write-Host "  build       Build and start the Docker stack (rebuild images)" -ForegroundColor White
    Write-Host "  clean-data  Clean all user data and stop containers" -ForegroundColor White
    Write-Host "  console     Access Sentinel-Kit console in backend container" -ForegroundColor White
    Write-Host "  help        Show this help message" -ForegroundColor White
    Write-Host ""
    Write-Host "OPTIONS:" -ForegroundColor $InfoColor
    Write-Host "  -Help       Show this help message" -ForegroundColor White
    Write-Host ""
    Write-Host "EXAMPLES:" -ForegroundColor $InfoColor
    Write-Host "  .\launcher.ps1 run         # Start the stack" -ForegroundColor Gray
    Write-Host "  .\launcher.ps1 build       # Build and start" -ForegroundColor Gray
    Write-Host "  .\launcher.ps1 stop        # Stop the stack" -ForegroundColor Gray
    Write-Host "  .\launcher.ps1 clean-data  # Clean all data" -ForegroundColor Gray
    Write-Host "  .\launcher.ps1 console     # Access Sentinel-Kit console" -ForegroundColor Gray
    Write-Host ""
}

function Test-DockerCompose {
    Write-Host "Checking for 'docker compose' presence..." -ForegroundColor $WarningColor
    $TestResult = docker compose version 2>$null
    
    if ($LASTEXITCODE -eq 0 -and $TestResult) {
        Write-Host "✅ 'docker compose' is available. Found version: $($TestResult.Trim().Split("`n")[0])" -ForegroundColor $SuccessColor
        return $true
    } else {
        Write-Host "--------------------------------------------------------" -ForegroundColor $ErrorColor
        Write-Host "🛑 FAILURE: The 'docker compose' command is not available." -ForegroundColor $ErrorColor
        Write-Host "Please ensure Docker Desktop is installed and 'docker compose' is in your PATH." -ForegroundColor $ErrorColor
        Write-Host "Check command exit code: $LASTEXITCODE" -ForegroundColor $ErrorColor
        Write-Host "--------------------------------------------------------" -ForegroundColor $ErrorColor
        return $false
    }
}

function Get-RunningContainers {
    return docker compose ps -q 2>$null
}

function Start-SentinelKit {
    Write-Host ""
    Write-Host "========== STARTING SENTINELKIT ==========" -ForegroundColor $HeaderColor
    
    if (-not (Test-DockerCompose)) {
        exit 1
    }
    
    Write-Host "--------------------------------------------------------"
    $RunningContainers = Get-RunningContainers
    
    if ($RunningContainers) {
        $ContainerCount = $RunningContainers.Count
        Write-Host "--------------------------------------------------------" -ForegroundColor $InfoColor
        Write-Host "ℹ️ INFORMATION: The Docker stack is already running." -ForegroundColor $InfoColor
        Write-Host "Number of active containers: $ContainerCount" -ForegroundColor $InfoColor
        Write-Host "No action taken. If you need to rebuild images, run: .\launcher.ps1 build" -ForegroundColor $InfoColor
        Write-Host "--------------------------------------------------------" -ForegroundColor $InfoColor
        return
    }
    
    Write-Host "No active containers found. Starting the Docker stack using existing images..." -ForegroundColor $WarningColor
    
    try {
        docker compose up -d
        
        if ($LASTEXITCODE -eq 0) {
            Write-Host "--------------------------------------------------------"
            Write-Host "🎉 Success! The Docker stack has been launched." -ForegroundColor $SuccessColor
            Write-Host "Containers are running in detached mode." -ForegroundColor $SuccessColor
        } else {
            Write-Host "❌ Internal error while running 'docker compose up'. Exit Code: $LASTEXITCODE" -ForegroundColor $ErrorColor
            Write-Host "Please check your docker-compose.yml file and logs for details. Did you forget to build first?" -ForegroundColor $ErrorColor
        }
    } catch {
        Write-Host "❌ An unexpected error occurred while calling Docker Compose." -ForegroundColor $ErrorColor
        Write-Host "Error Details: $($_.Exception.Message)" -ForegroundColor $ErrorColor
    }
}

function Stop-SentinelKit {
    Write-Host ""
    Write-Host "========== STOPPING SENTINELKIT ==========" -ForegroundColor $HeaderColor
    
    if (-not (Test-DockerCompose)) {
        exit 1
    }
    
    Write-Host "Checking current status of the Docker stack..." -ForegroundColor $WarningColor
    $RunningContainers = Get-RunningContainers
    
    if ($RunningContainers) {
        $ContainerCount = $RunningContainers.Count
        Write-Host "🔍 $ContainerCount running Docker container(s) found. Attempting to stop..." -ForegroundColor $InfoColor
        
        try {
            docker compose down
            
            if ($LASTEXITCODE -eq 0) {
                Write-Host "--------------------------------------------------------"
                Write-Host "🎉 Success! The Docker stack has been stopped (docker compose down)." -ForegroundColor $SuccessColor
                Write-Host "All containers, networks, and default volumes have been cleaned up." -ForegroundColor $SuccessColor
            } else {
                Write-Host "❌ Internal error while running 'docker compose down'. Exit Code: $LASTEXITCODE" -ForegroundColor $ErrorColor
                Write-Host "Please check Docker logs for more details." -ForegroundColor $ErrorColor
            }
        } catch {
            Write-Host "❌ An unexpected error occurred while calling Docker Compose down." -ForegroundColor $ErrorColor
            Write-Host "Error Details: $($_.Exception.Message)" -ForegroundColor $ErrorColor
        }
    } else {
        Write-Host "--------------------------------------------------------" -ForegroundColor $InfoColor
        Write-Host "ℹ️ INFORMATION: No Docker stack is currently running (according to docker compose ps -q)." -ForegroundColor $InfoColor
        Write-Host "No 'docker compose down' action was executed." -ForegroundColor $InfoColor
        Write-Host "--------------------------------------------------------" -ForegroundColor $InfoColor
    }
}

function Build-SentinelKit {
    Write-Host ""
    Write-Host "========== BUILDING SENTINELKIT ==========" -ForegroundColor $HeaderColor
    
    if (-not (Test-DockerCompose)) {
        exit 1
    }
    
    Write-Host "--------------------------------------------------------"
    $RunningContainers = Get-RunningContainers
    
    if ($RunningContainers) {
        $ContainerCount = $RunningContainers.Count
        Write-Host "--------------------------------------------------------" -ForegroundColor $ErrorColor
        Write-Host "🛑 STOP REQUIRED: The Docker stack is already running." -ForegroundColor $ErrorColor
        Write-Host "Number of active containers: $ContainerCount" -ForegroundColor $ErrorColor
        Write-Host "Please stop the stack first using: .\launcher.ps1 stop" -ForegroundColor $ErrorColor
        Write-Host "--------------------------------------------------------" -ForegroundColor $ErrorColor
        return
    }
    
    Write-Host "No active containers found. Starting and rebuilding the Docker stack..." -ForegroundColor $WarningColor
    
    try {
        docker compose up -d --build --force-recreate
        
        if ($LASTEXITCODE -eq 0) {
            Write-Host "--------------------------------------------------------"
            Write-Host "🎉 Success! The Docker stack has been rebuilt and started." -ForegroundColor $SuccessColor
            Write-Host "Containers are running in detached mode." -ForegroundColor $SuccessColor
        } else {
            Write-Host "❌ Internal error while running 'docker compose up'. Exit Code: $LASTEXITCODE" -ForegroundColor $ErrorColor
            Write-Host "Please check your docker-compose.yml file and logs for details." -ForegroundColor $ErrorColor
        }
    } catch {
        Write-Host "❌ An unexpected error occurred while calling Docker Compose." -ForegroundColor $ErrorColor
        Write-Host "Error Details: $($_.Exception.Message)" -ForegroundColor $ErrorColor
    }
}

function Start-SentinelKitConsole {
    Write-Host ""
    Write-Host "========== SENTINEL-KIT CONSOLE ACCESS ==========" -ForegroundColor $HeaderColor
    
    if (-not (Test-DockerCompose)) {
        exit 1
    }
    
    # Vérifier si le conteneur backend est en cours d'exécution
    $backendContainer = docker compose ps -q sentinel-kit-app-backend 2>$null
    
    if (-not $backendContainer) {
        Write-Host "❌ The backend container (sentinel-kit-app-backend) is not running." -ForegroundColor $ErrorColor
        Write-Host "Please start the stack first using: .\launcher.ps1 run" -ForegroundColor $InfoColor
        return
    }
    
    Write-Host "✅ Backend container found. Starting interactive console..." -ForegroundColor $SuccessColor
    Write-Host ""
    Write-Host "=========================================" -ForegroundColor $InfoColor
    Write-Host " Sentinel-Kit Console - Interactive Mode " -ForegroundColor $InfoColor
    Write-Host "=========================================" -ForegroundColor $InfoColor
    Write-Host "Type your console commands" -ForegroundColor $WarningColor
    Write-Host "Examples:" -ForegroundColor $WarningColor
    Write-Host 
    Write-Host "  app                     # List all sentinel-kit application commands" -ForegroundColor Gray
    Write-Host "  cache:clear             # Force backend cache clear" -ForegroundColor Gray
    Write-Host "  help                    # List all built-in commands" -ForegroundColor Gray
    Write-Host "Type 'exit' to return to PowerShell" -ForegroundColor $WarningColor
    Write-Host ""
    
    while ($true) {
        $command = Read-Host "sentinel-kit>"
        
        if ($command.ToLower() -eq "exit" -or $command.ToLower() -eq "quit") {
            Write-Host "Exiting Sentinel-Kit console..." -ForegroundColor $InfoColor
            break
        }
        
        if ([string]::IsNullOrWhiteSpace($command)) {
            continue
        }
        
        Write-Host ""
        Write-Host "Executing: php bin/console $command" -ForegroundColor $InfoColor
        Write-Host "----------------------------------------" -ForegroundColor Gray
        
        try {
            docker compose exec sentinel-kit-app-backend php bin/console $command
            
            if ($LASTEXITCODE -ne 0) {
                Write-Host "⚠️  Command completed with exit code: $LASTEXITCODE" -ForegroundColor $WarningColor
            }
        } catch {
            Write-Host "❌ Error executing command: $($_.Exception.Message)" -ForegroundColor $ErrorColor
        }
        
        Write-Host ""
    }
}

function Clear-SentinelKitData {
    Write-Host ""
    Write-Host "========== CLEANING SENTINELKIT DATA ==========" -ForegroundColor $HeaderColor
    Write-Host ""
    Write-Host "⚠️  WARNING: This will remove ALL user data and configurations!" -ForegroundColor $WarningColor
    Write-Host "This includes:" -ForegroundColor $WarningColor
    Write-Host "  - Frontend dependencies and builds" -ForegroundColor $WarningColor
    Write-Host "  - Backend cache and vendor files" -ForegroundColor $WarningColor
    Write-Host "  - Database migrations and JWT keys" -ForegroundColor $WarningColor
    Write-Host "  - All application data (logs, uploads, etc.)" -ForegroundColor $WarningColor
    Write-Host "  - Docker volumes and containers" -ForegroundColor $WarningColor
    Write-Host ""
    
    $confirmation = Read-Host "Are you sure you want to continue? Type 'yes' to confirm"
    
    if ($confirmation -ne "yes") {
        Write-Host "Operation cancelled." -ForegroundColor $InfoColor
        return
    }
    
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
        "./sentinel-kit_server_backend/migrations",
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
        try {
            if (Test-Path $item) {
                Remove-Item $item -Recurse -Force -ErrorAction Stop
                Write-Host "✅ Removed: $item" -ForegroundColor $SuccessColor
                $successCount++
            } else {
                Write-Host "⏭️  Skipped: $item (not found)" -ForegroundColor Gray
            }
        } catch {
            Write-Host "❌ Failed to remove: $item - $($_.Exception.Message)" -ForegroundColor $ErrorColor
            $errorCount++
        }
    }
    
    Write-Host ""
    Write-Host "Stopping Docker containers and removing volumes..." -ForegroundColor $WarningColor
    
    try {
        docker compose down -v
        
        if ($LASTEXITCODE -eq 0) {
            Write-Host "✅ Docker containers and volumes removed successfully." -ForegroundColor $SuccessColor
            $successCount++
        } else {
            Write-Host "❌ Error stopping Docker containers. Exit Code: $LASTEXITCODE" -ForegroundColor $ErrorColor
            $errorCount++
        }
    } catch {
        Write-Host "❌ Error during Docker cleanup: $($_.Exception.Message)" -ForegroundColor $ErrorColor
        $errorCount++
    }
    
    Write-Host ""
    Write-Host "--------------------------------------------------------"
    Write-Host "🧹 Cleanup Summary:" -ForegroundColor $HeaderColor
    Write-Host "✅ Successful operations: $successCount" -ForegroundColor $SuccessColor
    if ($errorCount -gt 0) {
        Write-Host "❌ Failed operations: $errorCount" -ForegroundColor $ErrorColor
    }
    Write-Host "--------------------------------------------------------"
    
    if ($errorCount -eq 0) {
        Write-Host "🎉 All user data has been successfully cleaned!" -ForegroundColor $SuccessColor
    } else {
        Write-Host "⚠️  Cleanup completed with some errors. Check the output above." -ForegroundColor $WarningColor
    }
}

if ($Help -or $Command -eq "help") {
    Show-Help
    exit 0
}

switch ($Command.ToLower()) {
    "run" {
        Start-SentinelKit
    }
    "stop" {
        Stop-SentinelKit
    }
    "build" {
        Build-SentinelKit
    }
    "clean-data" {
        Clear-SentinelKitData
    }
    "console" {
        Start-SentinelKitConsole
    }
    default {
        Write-Host "❌ Unknown command: $Command" -ForegroundColor $ErrorColor
        Write-Host "Use '.\launcher.ps1 help' to see available commands." -ForegroundColor $InfoColor
        exit 1
    }
}