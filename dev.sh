#!/bin/bash

# Source the utility functions if available
source "docker/bash_utils.sh"

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    log_error "Docker is not running. Please start Docker and try again."
    exit 1
fi

# Function to display help
show_help() {
    echo "Restarters Development Helper"
    echo ""
    echo "Usage: ./dev.sh [command]"
    echo ""
    echo "Commands:"
    echo "  setup [options]    Set up the environment (see setup options below)"
    echo "  up                 Start the development environment"
    echo "  down               Stop the development environment"
    echo "  restart            Restart the development environment"
    echo "  bash               Open a bash shell in the app container"
    echo "  artisan [cmd]      Run an Artisan command"
    echo "  composer [cmd]     Run a Composer command"
    echo "  npm [cmd]          Run an NPM command"
    echo "  test               Run PHPUnit tests"
    echo "  logs               Show logs from the app container"
    echo "  rebuild            Rebuild containers from scratch"
    echo "  troubleshoot       Run troubleshooting steps"
    echo "  status             Check the status of the initialization"
    echo "  help               Show this help message"
    echo ""
    echo "Setup options:"
    echo "  --dev              Setup development environment (default)"
    echo "  --prod             Setup production environment"
    echo "  --rebuild          Rebuild containers from scratch"
    echo "  --force            Force initialization even if already initialized"
    echo ""
    echo "Examples:"
    echo "  ./dev.sh setup                  # Setup development environment"
    echo "  ./dev.sh setup --prod           # Setup production environment"
    echo "  ./dev.sh setup --rebuild        # Rebuild development environment"
    echo "  ./dev.sh setup --force          # Force reinitialization"
    echo ""
}

# Check if the containers are running
check_running() {
    if ! docker ps | grep -q restarters-app; then
        log_warn "Restarters containers are not running. Use './dev.sh up' to start them."
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
        log_info "Detected ARM64 architecture"
        export MAILHOG_IMAGE="jcalonso/mailhog:latest"
    else
        log_info "Detected x86_64/AMD64 architecture"
        export MAILHOG_IMAGE="mailhog/mailhog:latest"
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

# Setup function to handle environment setup
setup() {
    # Default values
    ENV="dev"
    REBUILD=false
    FORCE_INIT=false

    # Parse command line arguments
    while [[ $# -gt 0 ]]; do
        case $1 in
            --dev)
                ENV="dev"
                shift
                ;;
            --prod)
                ENV="prod"
                shift
                ;;
            --rebuild)
                REBUILD=true
                shift
                ;;
            --force)
                FORCE_INIT=true
                shift
                ;;
            *)
                log_error "Unknown option: $1"
                show_help
                exit 1
                ;;
        esac
    done

    # Check if Docker is installed
    if ! command -v docker &> /dev/null; then
        log_error "Docker is not installed. Please install Docker first."
        log_info "Visit https://docs.docker.com/get-docker/ for installation instructions."
        exit 1
    fi

    # Check if Docker Compose is installed
    if ! command -v docker-compose &> /dev/null && ! command -v docker compose &> /dev/null; then
        log_error "Docker Compose is not installed. Please install Docker Compose first."
        log_info "Visit https://docs.docker.com/compose/install/ for installation instructions."
        exit 1
    fi

    # Make scripts executable
    log_section "Preparing scripts"
    chmod +x docker/startup.sh docker/entrypoint.sh docker/run-services.sh docker/bash_utils.sh

    # Create necessary directories
    mkdir -p docker/php docker/nginx

    # Check if .env file exists, if not copy from .env.example
    if [ ! -f .env ]; then
        if [ -f .env.example ]; then
            cp .env.example .env
            log_info "Created .env file from .env.example"
        else
            log_warn "Warning: .env.example file not found. You'll need to create a .env file manually."
        fi
    fi

    # Set environment variables for Docker
    set_env_vars
    export FORCE_INIT=$FORCE_INIT

    log_section "Setting up Restarters ${ENV} environment"

    # Stop any running containers
    if [ "$REBUILD" = true ]; then
        log_info "Stopping and removing existing containers..."
        if [ "$ENV" = "dev" ]; then
            docker_compose_cmd -f docker-compose.dev.yml down -v
        else
            docker_compose_cmd down -v
        fi
    fi

    # Start the containers
    if [ "$ENV" = "dev" ]; then
        log_info "Starting development environment..."
        docker_compose_cmd -f docker-compose.dev.yml up -d
        
        log_info "Containers are starting up and initialization is running automatically."
        log_info "This process may take several minutes to complete."
        log_info ""
        log_info "You can monitor the progress with:"
        log_info "  ./dev.sh logs"
        log_info ""
        log_info "Once initialization is complete, you can access the application at http://localhost:8001"
        log_info "Admin user: jane@bloggs.net / passw0rd"
    else
        log_info "Starting production environment..."
        docker_compose_cmd up -d
        
        log_info "Containers are starting up and initialization is running automatically."
        log_info "This process may take several minutes to complete."
        log_info ""
        log_info "You can monitor the progress with:"
        log_info "  docker-compose logs -f restarters"
        log_info ""
        log_info "Once initialization is complete, you can access the application at http://localhost:8001"
        log_info "Admin user: jane@bloggs.net / passw0rd"
    fi

    log_section "Setup complete"
    log_info "If you encounter any issues, check the container logs for details."
    log_info "For more information, see the DEVELOPMENT.md file."
}

# Main command handling
case "$1" in
    setup)
        shift
        setup "$@"
        ;;
    up)
        log_info "Starting Restarters development environment..."
        set_env_vars
        docker_compose_cmd -f docker-compose.dev.yml up -d
        log_info "Containers started. Initialization is running automatically."
        log_info "You can monitor the progress with: ./dev.sh logs"
        log_info "Once initialization is complete, you can access the application at http://localhost:8001"
        ;;
    down)
        log_info "Stopping Restarters development environment..."
        docker_compose_cmd -f docker-compose.dev.yml down
        ;;
    restart)
        log_info "Restarting Restarters development environment..."
        docker_compose_cmd -f docker-compose.dev.yml down
        set_env_vars
        docker_compose_cmd -f docker-compose.dev.yml up -d
        log_info "Containers restarted. Initialization is running automatically."
        log_info "You can monitor the progress with: ./dev.sh logs"
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
    rebuild)
        log_info "Rebuilding containers from scratch..."
        docker_compose_cmd -f docker-compose.dev.yml down -v --rmi all
        set_env_vars
        docker_compose_cmd -f docker-compose.dev.yml build --no-cache
        docker_compose_cmd -f docker-compose.dev.yml up -d
        log_info "Containers rebuilt and started. Initialization is running automatically."
        log_info "You can monitor the progress with: ./dev.sh logs"
        ;;
    status)
        check_running
        log_info "Checking initialization status..."
        if docker exec restarters-app test -f /var/www/storage/framework/initialized; then
            log_info "✅ Initialization complete! The application is ready to use."
            log_info "You can access the application at http://localhost:8001"
            log_info "Admin user: jane@bloggs.net / passw0rd"
        else
            log_warn "⏳ Initialization is still in progress."
            log_info "You can monitor the progress with: ./dev.sh logs"
        fi
        ;;
    troubleshoot)
        log_section "Running troubleshooting steps..."
        
        # Step 1: Check Docker and Docker Compose versions
        log_info "=== Docker Version ==="
        docker --version
        echo ""
        
        # Step 2: Check running containers
        log_info "=== Running Containers ==="
        docker ps
        echo ""
        
        # Step 3: Check container logs
        log_info "=== Container Logs (last 20 lines) ==="
        docker logs --tail 20 restarters-app
        echo ""
        
        # Step 4: Check database connection
        log_info "=== Database Connection Test ==="
        docker exec restarters-app bash -c "php -r \"try { new PDO('mysql:host=restarters_db;dbname=restarters_db_test', 'restarters', 's3cr3t'); echo 'Connection successful!\n'; } catch (PDOException \$e) { echo 'Connection failed: ' . \$e->getMessage() . \n'; }\""
        echo ""
        
        # Step 5: Check PHP extensions
        log_info "=== PHP Extensions ==="
        docker exec restarters-app php -m
        echo ""
        
        # Step 6: Check file permissions
        log_info "=== File Permissions ==="
        docker exec restarters-app ls -la /var/www
        echo ""
        
        # Step 7: Check initialization status
        log_info "=== Initialization Status ==="
        if docker exec restarters-app test -f /var/www/storage/framework/initialized; then
            log_info "✅ Initialization complete!"
        else
            log_warn "⏳ Initialization is still in progress or has failed."
        fi
        echo ""
        
        log_info "Troubleshooting complete. If issues persist, try './dev.sh rebuild' to rebuild the containers."
        ;;
    help|"")
        show_help
        ;;
    *)
        log_error "Unknown command: $1"
        show_help
        exit 1
        ;;
esac 