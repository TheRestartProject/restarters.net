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

# Main command handling
case "$1" in
    up)
        echo "Starting Restarters development environment..."
        export UID=$(id -u)
        export USER=$(id -un)
        docker-compose -f docker-compose.dev.yml up -d
        echo "Containers started. You can access the application at http://localhost:8001"
        ;;
    down)
        echo "Stopping Restarters development environment..."
        docker-compose -f docker-compose.dev.yml down
        ;;
    restart)
        echo "Restarting Restarters development environment..."
        docker-compose -f docker-compose.dev.yml down
        export UID=$(id -u)
        export USER=$(id -un)
        docker-compose -f docker-compose.dev.yml up -d
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
    help|*)
        show_help
        ;;
esac 