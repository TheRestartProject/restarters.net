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

2. Run the setup script:
   ```bash
   ./dev.sh setup
   ```

   This will:
   - Set up the necessary files and directories
   - Build and start the Docker containers
   - Automatically run the initialization process

3. Monitor the initialization progress:
   ```bash
   ./dev.sh logs
   ```

4. Once initialization is complete, access the application:
   - Web application: http://localhost:8001
   - phpMyAdmin: http://localhost:8002
   - MailHog (for email testing): http://localhost:8025

## Setup Options

The setup command supports several options:

```bash
./dev.sh setup [options]
```

Options:
- `--prod`: Setup production environment (default is development)
- `--rebuild`: Rebuild containers from scratch
- `--force`: Force initialization even if already initialized

Examples:
```bash
# Setup development environment (default)
./dev.sh setup

# Setup production environment
./dev.sh setup --prod

# Rebuild development environment from scratch
./dev.sh setup --rebuild

# Force reinitialization of development environment
./dev.sh setup --force
```

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

# Check initialization status
./dev.sh status

# Rebuild containers from scratch but only in Dev mode
./dev.sh rebuild

# Run troubleshooting steps
./dev.sh troubleshoot

# Show help
./dev.sh help
```

## Automatic Initialization

The development environment is configured to automatically run the initialization process when the containers start. This includes:

1. Setting up the database connection
2. Installing Composer dependencies
3. Running database migrations
4. Installing NPM dependencies
5. Creating the admin user

You can monitor the progress of the initialization with `./dev.sh logs` and check its status with `./dev.sh status`.

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
- `docker/startup.sh`: Initialization script

## Troubleshooting

### Checking Initialization Status

If you're unsure whether the initialization process has completed:

```bash
./dev.sh status
```

### Viewing Logs

To see what's happening during initialization:

```bash
./dev.sh logs
```

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

### Running Diagnostics

If you're experiencing issues, run the troubleshooting command:

```bash
./dev.sh troubleshoot
```

This will run a series of diagnostic checks and display the results.

## Additional Information

For more information about the Restarters project, please refer to the main [README.md](README.md) file.

## Docker Development Environment

### Webpack Dev Server

The Docker development environment automatically runs the webpack dev server (`npm run watch`) in the background when the container starts. This allows for hot reloading of frontend assets while the PHP-FPM server is running.

If you need to restart the webpack dev server, you can do so by running:

```bash
docker exec -it restarters-app bash -c "cd /var/www && npm run watch"
``` 