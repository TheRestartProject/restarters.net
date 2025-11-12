# Local Development Setup Guide

This guide will help you set up a local development environment for the Restarters.net project using Docker.

- [Local Development Setup Guide](#local-development-setup-guide)
  - [Prerequisites](#prerequisites)
    - [Windows Users](#windows-users)
      - [Permission Issues on Windows](#permission-issues-on-windows)
  - [Setup Steps](#setup-steps)
    - [1. Clone the Repository](#1-clone-the-repository)
    - [2. Environment Configuration](#2-environment-configuration)
    - [3. Starting the Development Environment](#3-starting-the-development-environment)
    - [4. Initial Setup](#4-initial-setup)
    - [5. Accessing the Application](#5-accessing-the-application)
  - [Common Tasks](#common-tasks)
    - [Running Commands in the Core Application Container](#running-commands-in-the-core-application-container)
    - [Stopping the Environment](#stopping-the-environment)
    - [Rebuilding Containers](#rebuilding-containers)
****

## Prerequisites

Before you begin, ensure you have the following installed:

- [Docker](https://docs.docker.com/get-docker/) (newer versions already include Docker Compose)
  - [Docker Compose](https://docs.docker.com/compose/install/) (only needed for older Docker versions)
- [Task](https://taskfile.dev/installation/)

> [!NOTE]
> The `Taskfile` will automatically detect your Docker version and use the appropriate command (`docker-compose` or `docker compose`).

### Windows Users

If you're developing on Windows, then for acceptable performance you must:
* Make sure you have WSL2 installed.  Running Docker under native windows via Docker Desktop is unusably slow.
* Check out the code to a WSL path (e.g. `/home/user/restarters.net`).  Do **not** use a path under `/mnt`.
* Run the commands below from inside the WSL container.
* You **must** run the `file-sync.sh` job to ensure changes are sync'd to the Docker containers.

## Setup Steps

### 1. Clone the Repository

```bash
git clone https://github.com/TheRestartProject/restarters.net.git
cd restarters.net
```

### 2. Environment Configuration

Copy the example environment file:

```bash
cp .env.example .env
```

You may need to adjust the following settings in your `.env` file:
- The `APP_KEY` will be auto-generated during setup
> [!NOTE]
> This is only required if the default port, `8001`, is not available.
- Set `APP_URL` to `http://localhost:PORT
  - You will need to change the port in the `docker-compose.yml` file to match the port you are using.

### 3. Starting the Development Environment

The development environment includes several optional components that can be enabled:

- **Core**: Restarters web application and MySQL database
- **Debug Tools**: Adds phpMyAdmin and Mailhog for debugging and testing
- **Discourse**: Adds Discourse forum with its required services (PostgreSQL, Redis, Sidekiq)
- **All**: Starts all containers in the `docker-compose.yml` file (Core, Debug Tools, Discourse)

The project includes a Taskfile that provides convenient commands for managing Docker:

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

### 5. Accessing the Application

Once setup is complete:

- **Main application**: http://localhost:8001
  - Admin: `jane@bloggs.net`
  - Password: `passw0rd`
- **PHPMyAdmin**: http://localhost:8002
  - Host: `restarters_db`
  - User: `root`
  - Password: `s3cr3t`
- **Mailhog**: http://localhost:8025
- **Discourse**: http://localhost:8003
  -  User: `someuser`
  -  Password: `mustbetencharacters`

## Common Tasks

### Running Commands in the Core Application Container

```bash
# Open a shell in the container
task docker:shell

# Run a bash command
task docker:run:bash -- [command]

# Run an artisan command
task docker:run:artisan -- [command]
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
