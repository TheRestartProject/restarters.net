# Local Development Setup Guide

This guide will help you set up a local development environment for the Restarters.net project using Docker.

## Quick Start

1. **Install prerequisites**: [Docker](https://docs.docker.com/get-docker/) and [Task](https://taskfile.dev/installation/)
2. **Clone repository**: `git clone https://github.com/TheRestartProject/restarters.net.git`
3. **Setup environment**: `cp .env.docker .env`
4. **Start services**: `task docker:up-core` (or `task docker:up-all` for full environment)
5. **Wait for setup**: `task docker:wait-for-services-core`
6. **Access application**: http://localhost:8001

<details>
<summary>Prerequisites and Windows Setup</summary>

## Prerequisites

Before you begin, ensure you have the following installed:

- [Docker](https://docs.docker.com/get-docker/) (newer versions already include Docker Compose)
  - [Docker Compose](https://docs.docker.com/compose/install/) (only needed for older Docker versions)
- [Task](https://taskfile.dev/installation/)

### Windows

If you're developing on Windows, then for acceptable performance you must:
* Make sure you have WSL2 installed.  Running Docker under native windows via Docker Desktop is unusably slow.
* Check out the code to a WSL path (e.g. `/home/user/restarters.net`).  Do **not** use a path under `/mnt`.
* Run the commands below from inside the WSL container.
* You **must** run the `file-sync.sh` job to ensure changes are sync'd to the Docker containers.

</details>

<details>
<summary>Environment Profiles</summary>

## Environment Profiles

The development environment includes several optional components:

- **Core**: Restarters web application and MySQL database
- **Debug Tools**: Adds phpMyAdmin and Mailhog for debugging and testing
- **Discourse**: Adds Discourse forum with its required services (PostgreSQL, Redis, Sidekiq)
- **All**: Starts all containers (Core, Debug Tools, Discourse)

```bash
# Start the core environment (app + database only)
task docker:up-core

# Start with debug tools (phpMyAdmin, Mailhog)
task docker:up-debug

# Start with Discourse integration
task docker:up-discourse

# Start all services
task docker:up-all
```

</details>

<details>
<summary>Detailed Setup Steps</summary>

### 1. Clone the Repository

```bash
git clone https://github.com/TheRestartProject/restarters.net.git
cd restarters.net
```

### 2. Environment Configuration

Copy the example environment file:

```bash
cp .env.docker .env
```

You may need to adjust the following settings in your `.env` file:
- The `APP_KEY` will be auto-generated during setup
> [!NOTE]
> This is only required if the default port, `8001`, is not available.
- Set `APP_URL` to `http://localhost:PORT
  - You will need to change the port in the `docker-compose.yml` file to match the port you are using.

### 3. Wait for Services to be Ready

After starting services, you can wait for them to be fully ready and responding:

```bash
# Wait for core services to be ready
task docker:wait-for-services-core

# Wait for debug services to be ready
task docker:wait-for-services-debug

# Wait for Discourse services to be ready
task docker:wait-for-services-discourse

# Wait for all services to be ready
task docker:wait-for-services-all
```

The wait commands will check that services are listening on their expected ports and return proper responses.

### 4. Initial Setup

The core application container will automatically:

1. Install Composer dependencies
2. Run database migrations
3. Install npm packages
4. Generate application key
5. Create an admin user

You can monitor the progress by checking the container logs:

```bash
task docker:logs
```

</details>

<details>
<summary>Access Information</summary>

## Access Information

Once setup is complete:

- **Main application**: http://localhost:8001
  - Admin: `jane@bloggs.net`
  - Password: `passw0rd`
- **PHPMyAdmin**: http://localhost:8002 (debug profile only)
  - Host: `restarters_db`
  - User: `root`
  - Password: `s3cr3t`
- **Mailhog**: http://localhost:8026 (debug profile only)
- **Discourse**: http://localhost:8003 (discourse profile only)
  -  User: `someuser`
  -  Password: `mustbetencharacters`

</details>

<details>
<summary>Testing</summary>

## Testing

The project includes unified task commands that ensure consistent test execution between local development and CI environments:

```bash
# Run PHPUnit tests (includes coverage and CI integration)
task docker:test:phpunit

# Run Jest JavaScript tests
task docker:test:jest

# Run Playwright end-to-end tests
task docker:test:playwright

# Run Playwright autocomplete tests (requires special setup data)
task docker:test:playwright-autocomplete
```

### Prerequisites for Testing

Before running tests, you need to configure the Google API key for geocoding functionality:

1. **Set Google API Key**: Add a valid Google Maps API key to your `.env` file:
   ```bash
   GOOGLE_API_CONSOLE_KEY=your_actual_google_api_key_here
   ```

2. **API Key Requirements**: The key must have the following APIs enabled:
   - Geocoding API
   - Maps JavaScript API (for location validation)

> [!WARNING]
> Without a valid `GOOGLE_API_CONSOLE_KEY`, tests that create groups or events will fail with location validation errors.

> [!NOTE]
> The PHPUnit task will automatically upload coverage to Coveralls if the `COVERALLS_REPO_TOKEN` environment variable is set.

</details>

<details>
<summary>Container Management Commands</summary>

### Running Commands in the Core Application Container

```bash
# Open a shell in the container
task docker:shell

# Run a bash command
task docker:run:bash -- [command]

# Run an artisan command
task docker:run:artisan -- [command]
```

### Checking Service Health

```bash
# View container logs if services aren't starting properly
task docker:logs
```

### Stopping the Environment

```bash
# Stop the core services
task docker:down-core

# Stop with the debug tools
task docker:down-debug

# Stop with Discourse
task docker:down-discourse

# Stop all containers
task docker:down-all
```

### Rebuilding Containers

If you need to completely rebuild. This will remove all containers, volumes, networks, and images.

```bash
# Rebuild the core services
task docker:rebuild-core

# Rebuild with the debug tools
task docker:rebuild-debug

# Rebuild with Discourse
task docker:rebuild-discourse

# Rebuild all services
task docker:rebuild-all
```

</details>

<details>
<summary>Troubleshooting</summary>

### Common Issues

**Services not starting properly**
- Check logs: `task docker:logs`
- Ensure ports aren't in use by other applications
- Try rebuilding: `task docker:rebuild-core`

**Permission errors on Windows**
- Add user to Docker group: `sudo usermod -aG docker $USER`
- Restart terminal/Docker Desktop

**Database connection errors**
- Wait for services: `task docker:wait-for-services-core`
- Check if MySQL container is running: `docker ps`

**Frontend assets not building**
- Check if npm install completed in logs
- Manually run: `task docker:run:bash -- npm install`

### Performance Issues

**Slow Docker on Windows**
Follow the [Docker performance optimization guide](https://medium.com/@suyashsingh.stem/increase-docker-performance-on-windows-by-20x-6d2318256b9a) for significant improvements.

**Build timeouts**
- Increase Docker memory allocation in Docker Desktop settings
- Use `task docker:up-core` instead of `task docker:up-all` for faster startup

</details>
