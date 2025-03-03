#!/bin/bash

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "Docker is not running. Please start Docker and try again."
    exit 1
fi

# Function to display help
show_help() {
    echo "Restarters Development Helper"
    echo ""
    echo "Usage: ./dev.sh [command]"
    echo ""
    echo "Commands:"
    echo "  up                Start the development environment"
    echo "  down              Stop the development environment"
    echo "  restart           Restart the development environment"
    echo "  bash              Open a bash shell in the app container"
    echo "  artisan [cmd]     Run an Artisan command"
    echo "  composer [cmd]    Run a Composer command"
    echo "  npm [cmd]         Run an NPM command"
    echo "  test              Run PHPUnit tests"
    echo "  logs              Show logs from the app container"
    echo "  setup             Run the initial setup script"
    echo "  rebuild           Rebuild containers from scratch"
    echo "  troubleshoot      Run troubleshooting steps"
    echo "  help              Show this help message"
    echo ""
}

# Check if the containers are running
check_running() {
    if ! docker ps | grep -q restarters-app; then
        echo "Restarters containers are not running. Use './dev.sh up' to start them."
        exit 1
    fi
}

# Set environment variables for Docker
set_env_vars() {
    # Get current user ID and username
    export UID=$(id -u)
    export USER=$(id -un)
    
    # Detect architecture and set appropriate image for MailHog
    ARCH=$(uname -m)
    if [ "$ARCH" = "arm64" ] || [ "$ARCH" = "aarch64" ]; then
        echo "Detected ARM64 architecture"
        export MAILHOG_IMAGE="jcalonso/mailhog:latest"
    else
        echo "Detected x86_64/AMD64 architecture"
        export MAILHOG_IMAGE="mailhog/mailhog"
    fi
}

# Function to determine which docker compose command to use
docker_compose_cmd() {
    if command -v docker-compose &> /dev/null; then
        docker-compose "$@"
    else
        docker compose "$@"
    fi
}

# Main command handling
case "$1" in
    up)
        echo "Starting Restarters development environment..."
        set_env_vars
        docker_compose_cmd -f docker-compose.dev.yml up -d
        echo "Containers started. You can access the application at http://localhost:8001"
        ;;
    down)
        echo "Stopping Restarters development environment..."
        docker_compose_cmd -f docker-compose.dev.yml down
        ;;
    restart)
        echo "Restarting Restarters development environment..."
        docker_compose_cmd -f docker-compose.dev.yml down
        set_env_vars
        docker_compose_cmd -f docker-compose.dev.yml up -d
        echo "Containers restarted. You can access the application at http://localhost:8001"
        ;;
    bash)
        check_running
        docker exec -it restarters-app bash
        ;;
    artisan)
        check_running
        shift
        docker exec -it restarters-app php artisan "$@"
        ;;
    composer)
        check_running
        shift
        docker exec -it restarters-app composer "$@"
        ;;
    npm)
        check_running
        shift
        docker exec -it restarters-app npm "$@"
        ;;
    test)
        check_running
        docker exec -it restarters-app php vendor/bin/phpunit
        ;;
    logs)
        docker logs --follow restarters-app
        ;;
    setup)
        check_running
        docker exec -it restarters-app bash /var/www/docker/startup.sh
        ;;
    rebuild)
        echo "Rebuilding containers from scratch..."
        docker_compose_cmd -f docker-compose.dev.yml down -v --rmi all
        set_env_vars
        docker_compose_cmd -f docker-compose.dev.yml build --no-cache
        docker_compose_cmd -f docker-compose.dev.yml up -d
        echo "Containers rebuilt and started. You can access the application at http://localhost:8001"
        ;;
    troubleshoot)
        echo "Running troubleshooting steps..."
        
        # Step 1: Check Docker and Docker Compose versions
        echo "=== Docker Version ==="
        docker --version
        echo ""
        
        # Step 2: Check running containers
        echo "=== Running Containers ==="
        docker ps
        echo ""
        
        # Step 3: Check container logs
        echo "=== Container Logs (last 20 lines) ==="
        docker logs --tail 20 restarters-app
        echo ""
        
        # Step 4: Check database connection
        echo "=== Database Connection Test ==="
        docker exec restarters-app bash -c "php -r \"try { new PDO('mysql:host=restarters_db;dbname=restarters_db_test', 'restarters', 's3cr3t'); echo 'Connection successful!\n'; } catch (PDOException \$e) { echo 'Connection failed: ' . \$e->getMessage() . \n'; }\""
        echo ""
        
        # Step 5: Check PHP extensions
        echo "=== PHP Extensions ==="
        docker exec restarters-app php -m
        echo ""
        
        # Step 6: Check file permissions
        echo "=== File Permissions ==="
        docker exec restarters-app ls -la /var/www
        echo ""
        
        echo "Troubleshooting complete. If issues persist, try './dev.sh rebuild' to rebuild the containers."
        ;;
    help|*)
        show_help
        ;;
esac 