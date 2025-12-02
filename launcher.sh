#!/bin/bash

# Colors for output
SUCCESS_COLOR="\033[32m"  # Green
ERROR_COLOR="\033[31m"    # Red
WARNING_COLOR="\033[33m"  # Yellow
INFO_COLOR="\033[36m"     # Cyan
HEADER_COLOR="\033[35m"   # Magenta
GRAY_COLOR="\033[90m"     # Gray
WHITE_COLOR="\033[37m"    # White
RESET_COLOR="\033[0m"     # Reset

show_help() {
    echo ""
    echo -e "${HEADER_COLOR}=============================================${RESET_COLOR}"
    echo -e "${HEADER_COLOR}       Sentinel-Kit Management Script        ${RESET_COLOR}"
    echo -e "${HEADER_COLOR}=============================================${RESET_COLOR}"
    echo ""
    echo -e "${INFO_COLOR}USAGE:${RESET_COLOR}"
    echo -e "  ${WHITE_COLOR}./launcher.sh <command>${RESET_COLOR}"
    echo ""
    echo -e "${INFO_COLOR}COMMANDS:${RESET_COLOR}"
    echo -e "  ${WHITE_COLOR}start       Start the Docker stack (using existing images)${RESET_COLOR}"
    echo -e "  ${WHITE_COLOR}stop        Stop the running Docker stack${RESET_COLOR}"
    echo -e "  ${WHITE_COLOR}build       Build and start the Docker stack (rebuild images)${RESET_COLOR}"
    echo -e "  ${WHITE_COLOR}clean-data  Clean all user data and stop containers${RESET_COLOR}"
    echo -e "  ${WHITE_COLOR}console     Access Sentinel-Kit console in backend container${RESET_COLOR}"
    echo -e "  ${WHITE_COLOR}logs        Show Docker container logs${RESET_COLOR}"
    echo -e "  ${WHITE_COLOR}status      Show container status information${RESET_COLOR}"
    echo -e "  ${WHITE_COLOR}help        Show this help message${RESET_COLOR}"
    echo ""
    echo -e "${INFO_COLOR}OPTIONS:${RESET_COLOR}"
    echo -e "  ${WHITE_COLOR}-h, --help  Show this help message${RESET_COLOR}"
    echo -e "  ${WHITE_COLOR}-f          Follow log output (for logs command)${RESET_COLOR}"
    echo ""
    echo -e "${INFO_COLOR}EXAMPLES:${RESET_COLOR}"
    echo -e "  ${GRAY_COLOR}./launcher.sh start       # Start the stack${RESET_COLOR}"
    echo -e "  ${GRAY_COLOR}./launcher.sh build       # Build and start${RESET_COLOR}"
    echo -e "  ${GRAY_COLOR}./launcher.sh stop        # Stop the stack${RESET_COLOR}"
    echo -e "  ${GRAY_COLOR}./launcher.sh clean-data  # Clean all data${RESET_COLOR}"
    echo -e "  ${GRAY_COLOR}./launcher.sh console     # Access Sentinel-Kit console${RESET_COLOR}"
    echo -e "  ${GRAY_COLOR}./launcher.sh logs        # Show all container logs${RESET_COLOR}"
    echo -e "  ${GRAY_COLOR}./launcher.sh logs -f     # Follow all container logs${RESET_COLOR}"
    echo -e "  ${GRAY_COLOR}./launcher.sh logs backend # Show backend container logs${RESET_COLOR}"
    echo -e "  ${GRAY_COLOR}./launcher.sh status      # Show container status${RESET_COLOR}"
    echo ""
}

test_docker_compose() {
    echo -e "${WARNING_COLOR}Checking for 'docker compose' presence...${RESET_COLOR}"
    local test_result
    test_result=$(docker compose version 2>/dev/null)
    
    if [ $? -eq 0 ] && [ -n "$test_result" ]; then
        local version_line=$(echo "$test_result" | head -n 1)
        echo -e "${SUCCESS_COLOR}'docker compose' is available. Found version: $version_line${RESET_COLOR}"
        return 0
    else
        echo -e "${ERROR_COLOR}--------------------------------------------------------${RESET_COLOR}"
        echo -e "${ERROR_COLOR}FAILURE: The 'docker compose' command is not available.${RESET_COLOR}"
        echo -e "${ERROR_COLOR}Please ensure Docker is installed and 'docker compose' is in your PATH.${RESET_COLOR}"
        echo -e "${ERROR_COLOR}Check command exit code: $?${RESET_COLOR}"
        echo -e "${ERROR_COLOR}--------------------------------------------------------${RESET_COLOR}"
        return 1
    fi
}

get_running_containers() {
    docker compose ps -q 2>/dev/null
}

convert_shell_scripts_to_lf() {
    echo -e "${INFO_COLOR}Converting shell scripts to Unix line endings (LF)...${RESET_COLOR}"
    
    local converted_count=0
    
    while IFS= read -r -d '' file; do
        if [ -f "$file" ]; then
            if grep -q $'\r' "$file" 2>/dev/null; then
                if sed -i 's/\r$//' "$file" 2>/dev/null; then
                    echo -e "  ${GRAY_COLOR}Converted: $file${RESET_COLOR}"
                    converted_count=$((converted_count + 1))
                else
                    echo -e "  ${WARNING_COLOR}Warning: Failed to convert $file${RESET_COLOR}"
                fi
            fi
        fi
    done < <(find . -name "*.sh" -type f -print0 2>/dev/null)
    
    if [ $converted_count -gt 0 ]; then
        echo -e "${SUCCESS_COLOR}Converted $converted_count shell script(s) to Unix line endings.${RESET_COLOR}"
    else
        echo -e "${SUCCESS_COLOR}No shell scripts needed line ending conversion.${RESET_COLOR}"
    fi
}

wait_for_healthy_services() {
    echo -e "${INFO_COLOR}Waiting for backend and frontend to be healthy...${RESET_COLOR}"
    
    local max_attempts=60
    local attempt=0
    local backend_healthy=false
    local frontend_healthy=false
    
    while [ $attempt -lt $max_attempts ]; do
        attempt=$((attempt + 1))
        
        if [ "$backend_healthy" = false ]; then
            local backend_health=$(docker compose ps --format json sentinel-kit-app-backend 2>/dev/null)
            if [ $? -eq 0 ] && [ -n "$backend_health" ]; then
                local health_status=$(echo "$backend_health" | grep -o '"Health":"[^"]*"' | cut -d'"' -f4)
                if [ "$health_status" = "healthy" ]; then
                    echo -e "  ${SUCCESS_COLOR}Backend is healthy!${RESET_COLOR}"
                    backend_healthy=true
                fi
            fi
        fi
        
        if [ "$frontend_healthy" = false ]; then
            local frontend_health=$(docker compose ps --format json sentinel-kit-app-frontend 2>/dev/null)
            if [ $? -eq 0 ] && [ -n "$frontend_health" ]; then
                local health_status=$(echo "$frontend_health" | grep -o '"Health":"[^"]*"' | cut -d'"' -f4)
                if [ "$health_status" = "healthy" ]; then
                    echo -e "  ${SUCCESS_COLOR}Frontend is healthy!${RESET_COLOR}"
                    frontend_healthy=true
                fi
            fi
        fi
        
        if [ "$backend_healthy" = true ] && [ "$frontend_healthy" = true ]; then
            echo -e "${SUCCESS_COLOR}All critical services are healthy and ready!${RESET_COLOR}"
            return 0
        fi
        
        if [ $((attempt % 10)) -eq 0 ]; then
            echo -e "  ${WARNING_COLOR}Still waiting... (attempt $attempt/$max_attempts)${RESET_COLOR}"
        fi
        
        sleep 10
    done
    
    echo -e "${ERROR_COLOR}Timeout: Services did not become healthy within the expected time.${RESET_COLOR}"
    return 1
}

start_sentinel_kit() {
    echo ""
    echo -e "${HEADER_COLOR}========== STARTING SENTINELKIT ==========${RESET_COLOR}"
    
    if ! test_docker_compose; then
        exit 1
    fi
    
    convert_shell_scripts_to_lf
    
    echo "--------------------------------------------------------"
    local running_containers
    running_containers=$(get_running_containers)
    
    if [ -n "$running_containers" ]; then
        local container_count=$(echo "$running_containers" | wc -l)
        echo -e "${INFO_COLOR}--------------------------------------------------------${RESET_COLOR}"
        echo -e "${INFO_COLOR}INFORMATION: The Docker stack is already running.${RESET_COLOR}"
        echo -e "${INFO_COLOR}Number of active containers: $container_count${RESET_COLOR}"
        echo -e "${INFO_COLOR}Checking health status...${RESET_COLOR}"
        echo -e "${INFO_COLOR}--------------------------------------------------------${RESET_COLOR}"
        if wait_for_healthy_services; then
            echo -e "${SUCCESS_COLOR}Success! The Docker stack is running and healthy.${RESET_COLOR}"
        else
            echo -e "${WARNING_COLOR}Warning: Some services may not be fully healthy yet.${RESET_COLOR}"
        fi
        return
    fi
    
    echo -e "${WARNING_COLOR}No active containers found. Starting the Docker stack using existing images...${RESET_COLOR}"
    
    if docker compose up -d; then
        echo "--------------------------------------------------------"
        if wait_for_healthy_services; then
            echo -e "${SUCCESS_COLOR}Success! The Docker stack has been launched and is healthy.${RESET_COLOR}"
        else
            echo -e "${WARNING_COLOR}Warning: Docker stack started but some services are not healthy.${RESET_COLOR}"
        fi
        echo -e "${SUCCESS_COLOR}Containers are running in detached mode.${RESET_COLOR}"
    else
        echo -e "${ERROR_COLOR}Internal error while running 'docker compose up'. Exit Code: $?${RESET_COLOR}"
        echo -e "${ERROR_COLOR}Please check your docker-compose.yml file and logs for details.${RESET_COLOR}"
    fi
}

stop_sentinel_kit() {
    echo ""
    echo -e "${HEADER_COLOR}========== STOPPING SENTINELKIT ==========${RESET_COLOR}"
    
    if ! test_docker_compose; then
        exit 1
    fi
    
    echo -e "${WARNING_COLOR}Checking current status of the Docker stack...${RESET_COLOR}"
    local running_containers
    running_containers=$(get_running_containers)
    
    if [ -n "$running_containers" ]; then
        local container_count=$(echo "$running_containers" | wc -l)
        echo -e "${INFO_COLOR}$container_count running Docker container(s) found. Attempting to stop...${RESET_COLOR}"
        
        if docker compose down; then
            echo "--------------------------------------------------------"
            echo -e "${SUCCESS_COLOR}Success! The Docker stack has been stopped.${RESET_COLOR}"
            echo -e "${SUCCESS_COLOR}All containers, networks, and default volumes have been cleaned up.${RESET_COLOR}"
        else
            echo -e "${ERROR_COLOR}Internal error while running 'docker compose down'. Exit Code: $?${RESET_COLOR}"
            echo -e "${ERROR_COLOR}Please check Docker logs for more details.${RESET_COLOR}"
        fi
    else
        echo -e "${INFO_COLOR}--------------------------------------------------------${RESET_COLOR}"
        echo -e "${INFO_COLOR}INFORMATION: No Docker stack is currently running.${RESET_COLOR}"
        echo -e "${INFO_COLOR}No 'docker compose down' action was executed.${RESET_COLOR}"
        echo -e "${INFO_COLOR}--------------------------------------------------------${RESET_COLOR}"
    fi
}

build_sentinel_kit() {
    echo ""
    echo -e "${HEADER_COLOR}========== BUILDING SENTINELKIT ==========${RESET_COLOR}"
    
    if ! test_docker_compose; then
        exit 1
    fi
    
    convert_shell_scripts_to_lf
    
    echo "--------------------------------------------------------"
    local running_containers
    running_containers=$(get_running_containers)
    
    if [ -n "$running_containers" ]; then
        local container_count=$(echo "$running_containers" | wc -l)
        echo -e "${ERROR_COLOR}--------------------------------------------------------${RESET_COLOR}"
        echo -e "${ERROR_COLOR}STOP REQUIRED: The Docker stack is already running.${RESET_COLOR}"
        echo -e "${ERROR_COLOR}Number of active containers: $container_count${RESET_COLOR}"
        echo -e "${ERROR_COLOR}Please stop the stack first using: ./launcher.sh stop${RESET_COLOR}"
        echo -e "${ERROR_COLOR}--------------------------------------------------------${RESET_COLOR}"
        return
    fi
    
    echo -e "${WARNING_COLOR}No active containers found. Starting and rebuilding the Docker stack...${RESET_COLOR}"
    
    if docker compose up -d --build --force-recreate; then
        echo "--------------------------------------------------------"
        if wait_for_healthy_services; then
            echo -e "${SUCCESS_COLOR}Success! The Docker stack has been rebuilt, started and is healthy.${RESET_COLOR}"
        else
            echo -e "${WARNING_COLOR}Warning: Docker stack rebuilt and started but some services are not healthy.${RESET_COLOR}"
        fi
        echo -e "${SUCCESS_COLOR}Containers are running in detached mode.${RESET_COLOR}"
    else
        echo -e "${ERROR_COLOR}Internal error while running 'docker compose up'. Exit Code: $?${RESET_COLOR}"
        echo -e "${ERROR_COLOR}Please check your docker-compose.yml file and logs for details.${RESET_COLOR}"
    fi
}

start_sentinel_kit_console() {
    echo ""
    echo -e "${HEADER_COLOR}========== SENTINEL-KIT CONSOLE ACCESS ==========${RESET_COLOR}"
    
    if ! test_docker_compose; then
        exit 1
    fi
    
    local backend_container
    backend_container=$(docker compose ps -q sentinel-kit-app-backend 2>/dev/null)
    
    if [ -z "$backend_container" ]; then
        echo -e "${ERROR_COLOR}The backend container (sentinel-kit-app-backend) is not running.${RESET_COLOR}"
        echo -e "${INFO_COLOR}Please start the stack first using: ./launcher.sh start${RESET_COLOR}"
        return
    fi
    
    echo -e "${SUCCESS_COLOR}Backend container found. Starting interactive console...${RESET_COLOR}"
    echo ""
    echo -e "${INFO_COLOR}=========================================${RESET_COLOR}"
    echo -e "${INFO_COLOR} Sentinel-Kit Console - Interactive Mode ${RESET_COLOR}"
    echo -e "${INFO_COLOR}=========================================${RESET_COLOR}"
    echo -e "${WARNING_COLOR}Type your console commands${RESET_COLOR}"
    echo -e "${WARNING_COLOR}Examples:${RESET_COLOR}"
    echo ""
    echo -e "  ${GRAY_COLOR}app                     # List all sentinel-kit application commands${RESET_COLOR}"
    echo -e "  ${GRAY_COLOR}cache:clear             # Force backend cache clear${RESET_COLOR}"
    echo -e "  ${GRAY_COLOR}help                    # List all built-in commands${RESET_COLOR}"
    echo -e "${WARNING_COLOR}Type 'exit' to return to bash${RESET_COLOR}"
    echo ""
    
    while true; do
        echo -n "sentinel-kit> "
        read -r command_line
        
        if [ "$command_line" = "exit" ] || [ "$command_line" = "quit" ]; then
            echo -e "${INFO_COLOR}Exiting Sentinel-Kit console...${RESET_COLOR}"
            break
        fi
        
        if [ -z "$command_line" ] || [ "$command_line" = " " ]; then
            continue
        fi
        
        echo ""
        echo -e "${INFO_COLOR}Executing: php bin/console $command_line${RESET_COLOR}"
        echo -e "${GRAY_COLOR}----------------------------------------${RESET_COLOR}"
        
        if eval "docker compose exec sentinel-kit-app-backend php bin/console $command_line"; then
            if [ $? -ne 0 ]; then
                echo -e "${WARNING_COLOR}Command completed with exit code: $?${RESET_COLOR}"
            fi
        else
            echo -e "${ERROR_COLOR}Error executing command${RESET_COLOR}"
        fi
        
        echo ""
    done
}

show_logs() {
    local service_name="$1"
    local follow_logs="$2"
    
    echo ""
    echo -e "${HEADER_COLOR}========== DOCKER LOGS ==========${RESET_COLOR}"
    
    if ! test_docker_compose; then
        exit 1
    fi
    
    declare -A service_map=(
        ["backend"]="sentinel-kit-app-backend"
        ["frontend"]="sentinel-kit-app-frontend"
        ["mysql"]="sentinel-kit-db-mysql"
        ["elasticsearch"]="sentinel-kit-server-elasticsearch"
        ["kibana"]="sentinel-kit-server-kibana"
        ["fluentbit"]="sentinel-kit-server-fluentbit"
        ["caddy"]="sentinel-kit-server-caddy"
        ["grafana"]="sentinel-kit-server-grafana"
        ["prometheus"]="sentinel-kit-server-prometheus"
    )
    
    local actual_service_name="$service_name"
    if [ -n "$service_name" ] && [ -n "${service_map[$service_name]}" ]; then
        actual_service_name="${service_map[$service_name]}"
    fi
    
    if [ -n "$service_name" ]; then
        if [ "$follow_logs" = "true" ]; then
            echo -e "${INFO_COLOR}Following logs for service: $actual_service_name${RESET_COLOR}"
            echo -e "${WARNING_COLOR}Press Ctrl+C to stop...${RESET_COLOR}"
            echo ""
            docker compose logs -f "$actual_service_name"
        else
            echo -e "${INFO_COLOR}Showing logs for service: $actual_service_name${RESET_COLOR}"
            echo ""
            docker compose logs "$actual_service_name"
        fi
    else
        if [ "$follow_logs" = "true" ]; then
            echo -e "${INFO_COLOR}Following logs for all services${RESET_COLOR}"
            echo -e "${WARNING_COLOR}Press Ctrl+C to stop...${RESET_COLOR}"
            echo ""
            docker compose logs -f
        else
            echo -e "${INFO_COLOR}Showing logs for all services${RESET_COLOR}"
            echo ""
            docker compose logs
        fi
    fi
    
    if [ $? -ne 0 ]; then
        echo -e "${WARNING_COLOR}Command completed with exit code: $?${RESET_COLOR}"
    fi
}

show_status() {
    echo ""
    echo -e "${HEADER_COLOR}========== CONTAINER STATUS ==========${RESET_COLOR}"
    
    if ! test_docker_compose; then
        exit 1
    fi
    
    echo -e "${INFO_COLOR}Docker Compose Services Status:${RESET_COLOR}"
    echo ""
    
    if docker compose ps; then
        echo ""
        echo -e "${INFO_COLOR}Detailed Container Information:${RESET_COLOR}"
        echo ""
        
        local running_containers
        running_containers=$(docker compose ps -q 2>/dev/null)
        
        if [ -n "$running_containers" ]; then
            echo "$running_containers" | while read -r container_id; do
                local container_info
                container_info=$(docker inspect "$container_id" --format "{{.Name}} ({{.Config.Image}}) - Status: {{.State.Status}}" 2>/dev/null)
                if [ -n "$container_info" ]; then
                    echo -e "${SUCCESS_COLOR}$container_info${RESET_COLOR}"
                fi
            done
        else
            echo -e "${WARNING_COLOR}No containers are currently running${RESET_COLOR}"
        fi
    else
        echo -e "${ERROR_COLOR}Failed to get container status${RESET_COLOR}"
    fi
}

clear_sentinel_kit_data() {
    echo ""
    echo -e "${HEADER_COLOR}========== CLEANING SENTINELKIT DATA ==========${RESET_COLOR}"
    echo ""
    echo -e "${WARNING_COLOR}WARNING: This will remove ALL user data and configurations!${RESET_COLOR}"
    echo -e "${WARNING_COLOR}This includes:${RESET_COLOR}"
    echo -e "${WARNING_COLOR}  - Frontend dependencies and builds${RESET_COLOR}"
    echo -e "${WARNING_COLOR}  - Backend cache and vendor files${RESET_COLOR}"
    echo -e "${WARNING_COLOR}  - Database migrations and JWT keys${RESET_COLOR}"
    echo -e "${WARNING_COLOR}  - All application data (logs, uploads, etc.)${RESET_COLOR}"
    echo -e "${WARNING_COLOR}  - Docker volumes and containers${RESET_COLOR}"
    echo ""
    
    echo -n "Are you sure you want to continue? Type 'yes' to confirm: "
    read -r confirmation
    
    if [ "$confirmation" != "yes" ]; then
        echo -e "${INFO_COLOR}Operation cancelled.${RESET_COLOR}"
        return
    fi
    
    echo ""
    echo -e "${WARNING_COLOR}Starting cleanup process...${RESET_COLOR}"
    
    items_to_remove=(
        "./sentinel-kit_server_frontend/.production_build_complete"
        "./sentinel-kit_server_frontend/.source_hash"
        "./sentinel-kit_server_frontend/node_modules"
        "./sentinel-kit_server_frontend/package-lock.json"
        "./sentinel-kit_server_frontend/dist"
        "./sentinel-kit_server_backend/.initial_setup_done"
        "./sentinel-kit_server_backend/.cache_ready"
        "./sentinel-kit_server_backend/.composer_hash"
        "./sentinel-kit_server_backend/.source_hash"
        "./sentinel-kit_server_backend/composer.lock"
        "./sentinel-kit_server_backend/symfony.lock"
        "./sentinel-kit_server_backend/var"
        "./sentinel-kit_server_backend/vendor"
        "./sentinel-kit_server_backend/migrations/*"
        "./sentinel-kit_server_backend/config/jwt/*.pem"
        "./config/elastalert_ruleset/*"
        "./config/caddy_server/certificates/*"
        "./data/caddy_logs/*"
        "./data/ftp_data/*"
        "./data/grafana/*"
        "./data/kibana/*"
        "./data/log_ingest_data/auditd/*"
        "./data/log_ingest_data/evtx/*"
        "./data/log_ingest_data/json/*"
        "./data/fluentbit_db/*"
        "./data/yara_triage_data/*"
    )
    
    local success_count=0
    local error_count=0
    
    for item in "${items_to_remove[@]}"; do
        if [ -e "$item" ] || [ -d "$item" ]; then
            if rm -rf "$item" 2>/dev/null; then
                echo -e "${SUCCESS_COLOR}Removed: $item${RESET_COLOR}"
                ((success_count++))
            else
                echo -e "${ERROR_COLOR}Failed to remove: $item${RESET_COLOR}"
                ((error_count++))
            fi
        else
            echo -e "${GRAY_COLOR}Skipped: $item (not found)${RESET_COLOR}"
        fi
    done
    
    echo ""
    echo -e "${WARNING_COLOR}Stopping Docker containers and removing volumes...${RESET_COLOR}"
    
    if docker compose down -v; then
        echo -e "${SUCCESS_COLOR}Docker containers and volumes removed successfully.${RESET_COLOR}"
        ((success_count++))
    else
        echo -e "${ERROR_COLOR}Error stopping Docker containers. Exit Code: $?${RESET_COLOR}"
        ((error_count++))
    fi
    
    echo ""
    echo "--------------------------------------------------------"
    echo -e "${HEADER_COLOR}Cleanup Summary:${RESET_COLOR}"
    echo -e "${SUCCESS_COLOR}Successful operations: $success_count${RESET_COLOR}"
    if [ $error_count -gt 0 ]; then
        echo -e "${ERROR_COLOR}Failed operations: $error_count${RESET_COLOR}"
    fi
    echo "--------------------------------------------------------"
    
    if [ $error_count -eq 0 ]; then
        echo -e "${SUCCESS_COLOR}All user data has been successfully cleaned!${RESET_COLOR}"
    else
        echo -e "${WARNING_COLOR}Cleanup completed with some errors. Check the output above.${RESET_COLOR}"
    fi
}

command=""
service_name=""
follow_logs="false"

while [ $# -gt 0 ]; do
    case "$1" in
        "start"|"stop"|"build"|"clean-data"|"console"|"logs"|"status"|"help")
            command="$1"
            shift
            ;;
        "-f"|"--follow")
            follow_logs="true"
            shift
            ;;
        "-h"|"--help")
            command="help"
            shift
            ;;
        "")
            command="help"
            break
            ;;
        *)
            if [ -z "$command" ]; then
                echo -e "${ERROR_COLOR}Unknown command: $1${RESET_COLOR}"
                echo -e "${INFO_COLOR}Use './launcher.sh help' to see available commands.${RESET_COLOR}"
                exit 1
            elif [ "$command" = "logs" ] && [ -z "$service_name" ]; then
                service_name="$1"
                shift
            else
                echo -e "${ERROR_COLOR}Unknown option: $1${RESET_COLOR}"
                echo -e "${INFO_COLOR}Use './launcher.sh help' to see available commands.${RESET_COLOR}"
                exit 1
            fi
            ;;
    esac
done

if [ -z "$command" ]; then
    command="help"
fi

case "$command" in
    "start")
        start_sentinel_kit
        ;;
    "stop")
        stop_sentinel_kit
        ;;
    "build")
        build_sentinel_kit
        ;;
    "clean-data")
        clear_sentinel_kit_data
        ;;
    "console")
        start_sentinel_kit_console
        ;;
    "logs")
        show_logs "$service_name" "$follow_logs"
        ;;
    "status")
        show_status
        ;;
    "help")
        show_help
        ;;
    *)
        echo -e "${ERROR_COLOR}Unknown command: $command${RESET_COLOR}"
        echo -e "${INFO_COLOR}Use './launcher.sh help' to see available commands.${RESET_COLOR}"
        exit 1
        ;;
esac