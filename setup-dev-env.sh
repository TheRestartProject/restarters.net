#!/bin/bash

# Make sure scripts are executable
chmod +x dev.sh
chmod +x docker/startup.sh

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "Docker is not installed. Please install Docker first."
    echo "Visit https://docs.docker.com/get-docker/ for installation instructions."
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker-compose &> /dev/null && ! command -v docker compose &> /dev/null; then
    echo "Docker Compose is not installed. Please install Docker Compose first."
    echo "Visit https://docs.docker.com/compose/install/ for installation instructions."
    exit 1
fi

echo "Setting up Restarters development environment..."

# Create necessary directories
mkdir -p docker/php docker/nginx

# Check if .env file exists, if not copy from .env.example
if [ ! -f .env ]; then
    if [ -f .env.example ]; then
        cp .env.example .env
        echo "Created .env file from .env.example"
    else
        echo "Warning: .env.example file not found. You'll need to create a .env file manually."
    fi
fi

# Start the development environment
./dev.sh rebuild

# Wait for containers to be ready
echo "Waiting for containers to be ready..."
sleep 10

# Run the setup script
./dev.sh setup

echo ""
echo "Setup complete! You can now access the application at http://localhost:8001"
echo "Admin user: jane@bloggs.net / passw0rd"
echo ""
echo "If you encounter any issues, run './dev.sh troubleshoot' to diagnose problems."
echo "For more information, see the DEVELOPMENT.md file." 