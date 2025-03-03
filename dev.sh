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

# Function to determine which docker compose command to use
docker_compose_cmd() {
    if command -v docker-compose &> /dev/null; then
        docker-compose "$@"
    else
        docker compose "$@"
    fi
}

# Set user ID and group ID for container
set_user_ids() {
    # Default to 1000 if id command fails
    USER_ID=$(id -u 2>/dev/null || echo 1000)
    GROUP_ID=$(id -g 2>/dev/null || echo 1000)
    USER_NAME=$(id -un 2>/dev/null || echo developer)
    
    export DOCKER_UID=$USER_ID
    export DOCKER_GID=$GROUP_ID
    export DOCKER_USER=$USER_NAME
}

# Main command handling
case "$1" in
    up)
        echo "Starting Restarters development environment..."
        set_user_ids
        
        # Run docker compose and check if it succeeded
        if docker_compose_cmd -f docker-compose.dev.yml up -d; then
            echo "Containers started successfully."
            echo "You can access the application at http://localhost:8001"
            echo "Run './dev.sh setup' to initialize the application."
        else
            echo "Failed to start containers. Check the error messages above."
            exit 1
        fi
        ;;
    down)
        echo "Stopping Restarters development environment..."
        if docker_compose_cmd -f docker-compose.dev.yml down; then
            echo "Containers stopped successfully."
        else
            echo "Failed to stop containers. Check the error messages above."
            exit 1
        fi
        ;;
    restart)
        echo "Restarting Restarters development environment..."
        set_user_ids
        
        if docker_compose_cmd -f docker-compose.dev.yml down; then
            if docker_compose_cmd -f docker-compose.dev.yml up -d; then
                echo "Containers restarted successfully."
                echo "You can access the application at http://localhost:8001"
            else
                echo "Failed to start containers. Check the error messages above."
                exit 1
            fi
        else
            echo "Failed to stop containers. Check the error messages above."
            exit 1
        fi
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
        if docker exec -it restarters-app bash /var/www/docker/startup.sh; then
            echo "Setup completed successfully."
        else
            echo "Setup failed. Check the error messages above."
            exit 1
        fi
        ;;
    help|*)
        show_help
        ;;
esac 