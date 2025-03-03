# Restarters Development Environment

This document describes how to set up and use the development environment for the Restarters project.

## Prerequisites

- [Docker](https://docs.docker.com/get-docker/)
- [Docker Compose](https://docs.docker.com/compose/install/)

No other dependencies are required on your host machine!

## Getting Started

1. Clone the repository:
   ```bash
   git clone https://github.com/TheRestartProject/restarters.net.git
   cd restarters.net
   ```

2. Start the development environment:
   ```bash
   ./dev.sh up
   ```

3. Run the setup script:
   ```bash
   ./dev.sh setup
   ```

4. Access the application:
   - Web application: http://localhost:8001
   - phpMyAdmin: http://localhost:8002
   - MailHog (for email testing): http://localhost:8025

## Default Admin User

- Email: jane@bloggs.net
- Password: passw0rd

## Development Workflow

The `dev.sh` script provides several commands to help with development:

```bash
# Start the development environment
./dev.sh up

# Stop the development environment
./dev.sh down

# Restart the development environment
./dev.sh restart

# Open a bash shell in the app container
./dev.sh bash

# Run an Artisan command
./dev.sh artisan migrate

# Run a Composer command
./dev.sh composer require package/name

# Run an NPM command
./dev.sh npm run dev

# Run PHPUnit tests
./dev.sh test

# Show logs from the app container
./dev.sh logs

# Run the initial setup script
./dev.sh setup

# Rebuild containers from scratch
./dev.sh rebuild

# Show help
./dev.sh help
```

## Cross-Platform Compatibility

The development environment is designed to work across different platforms:

- **Linux (x86_64/AMD64)**: Works out of the box
- **macOS (Intel)**: Works out of the box
- **macOS (Apple Silicon M1/M2/M3)**: Automatically detects ARM64 architecture and uses compatible images
- **Windows with WSL2**: Works through WSL2 with Docker Desktop

The `dev.sh` script automatically detects your system architecture and configures the environment accordingly. No manual adjustments are needed when switching between different machines.

## File Permissions

The development environment is configured to use your host user's UID and GID inside the container. This ensures that files created by the container have the correct permissions on your host machine, regardless of whether you're using Linux, macOS, or Windows with WSL.

## Customizing the Environment

You can customize the environment by editing the following files:

- `docker-compose.dev.yml`: Docker Compose configuration
- `Dockerfile.dev`: PHP application container configuration
- `docker/php/php.ini`: PHP configuration
- `docker/nginx/restarters.conf`: Nginx configuration

## Troubleshooting

### File Permission Issues

If you encounter file permission issues, you can try the following:

1. Stop the containers:
   ```bash
   ./dev.sh down
   ```

2. Fix permissions on the host:
   ```bash
   sudo chown -R $(id -u):$(id -g) .
   ```

3. Restart the containers:
   ```bash
   ./dev.sh up
   ```

### Architecture-Related Issues

If you encounter issues related to container architecture:

1. Rebuild the containers from scratch:
   ```bash
   ./dev.sh rebuild
   ```

2. If problems persist, check Docker's platform support:
   ```bash
   docker version
   docker info
   ```

### Container Issues

If you need to rebuild the containers:

```bash
./dev.sh rebuild
```

## Additional Information

For more information about the Restarters project, please refer to the main [README.md](README.md) file. 