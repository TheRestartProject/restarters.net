# Restarters Development Environment

This document describes how to set up and use the development environment for the Restarters project.

## Prerequisites

- [Docker](https://docs.docker.com/get-docker/)
- [Docker Compose](https://docs.docker.com/compose/install/) (included with Docker Desktop)

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

# Show help
./dev.sh help
```

## File Permissions

The development environment is configured to use your host user's UID and GID inside the container. This ensures that files created by the container have the correct permissions on your host machine, regardless of whether you're using Linux, macOS, or Windows with WSL.

The script automatically detects your user ID and group ID and passes them to the Docker containers, so you shouldn't encounter permission issues.

## Compatibility

The development environment works with both older versions of Docker that use `docker-compose` as a separate command and newer versions that use `docker compose` as a subcommand. The `dev.sh` script automatically detects which command to use.

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

### Container Issues

If you need to rebuild the containers:

```bash
# For newer Docker versions
docker compose -f docker-compose.dev.yml down --rmi all

# For older Docker versions
docker-compose -f docker-compose.dev.yml down --rmi all

# Then start again
./dev.sh up
```

### Error Messages

If you encounter errors when running any of the commands, the `dev.sh` script will display an error message and exit with a non-zero status code. This makes it easier to identify and fix issues.

## Additional Information

For more information about the Restarters project, please refer to the main [README.md](README.md) file. 