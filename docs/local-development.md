# Local Development Setup Guide

This guide will help you set up a local development environment for the Restarters.net project using Docker.

- [Local Development Setup Guide](#local-development-setup-guide)
  - [Prerequisites](#prerequisites)
  - [Setup Steps](#setup-steps)
    - [1. Clone the Repository](#1-clone-the-repository)
    - [2. Environment Configuration](#2-environment-configuration)
    - [3. Starting the Development Environment](#3-starting-the-development-environment)
      - [Using Task (Recommended)](#using-task-recommended)
      - [Using Docker Compose Directly](#using-docker-compose-directly)
    - [4. Initial Setup](#4-initial-setup)
    - [5. Accessing the Application](#5-accessing-the-application)
  - [Common Tasks](#common-tasks)
    - [Running Commands in the Container](#running-commands-in-the-container)
      - [Using Task](#using-task)
      - [Using Docker Directly](#using-docker-directly)
    - [Stopping the Environment](#stopping-the-environment)
      - [Using Task](#using-task-1)
      - [Using Docker Compose](#using-docker-compose)
    - [Rebuilding Containers](#rebuilding-containers)
      - [Using Task](#using-task-2)
      - [Using Docker Compose](#using-docker-compose-1)
****

## Prerequisites

Before you begin, ensure you have the following installed:

- [Docker](https://docs.docker.com/get-docker/) (newer versions already include Docker Compose)
  - [Docker Compose](https://docs.docker.com/compose/install/) (only needed for older Docker versions)
- [Task](https://taskfile.dev/installation/) (optional but recommended)

> [!NOTE]
> The `Taskfile` will automatically detect your Docker version and use the appropriate command (`docker-compose` or `docker compose`).

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
- Set `APP_URL` to `http://localhost:8001` (matching the port in docker-compose.yml)
- The `APP_KEY` will be auto-generated during setup

### 3. Starting the Development Environment

The development environment includes several optional services that can be enabled through profiles:

- **Core**: Restarters web application and MySQL database
- **Development**: Adds phpMyAdmin and Mailhog
- **Discourse**: Adds Discourse forum with its required services (PostgreSQL, Redis, Sidekiq) 

#### Using Task (Recommended)

The project includes a Taskfile that provides convenient commands for managing Docker:

```bash
# Start the basic environment (app + database)
task docker:up

# Start with development tools (PHPMyAdmin, Mailhog)
task docker:up-dev

# Start with Discourse integration
task docker:up-discourse

# Start all services
task docker:up-all
```

#### Using Docker Compose Directly

If you don't have Task installed:

```bash
# Start the basic environment
docker compose up -d

# Start with development tools
docker compose --profile dev up -d

# Start with Discourse
docker compose --profile discourse up -d

# Start all services
docker compose --profile '*' up -d
```

### 4. Initial Setup

The Docker container will automatically:

1. Install Composer dependencies
2. Run database migrations
3. Install npm packages
4. Generate application key
5. Create an admin user

You can monitor the progress by checking the container logs:

```bash
# Using Task
task docker:logs

# Or using Docker
docker logs -f restarters
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

### Running Commands in the Container

> [!IMPORTANT]
> Depending on the Docker version you have installed you may need to substitute the  `docker compose` with `docker-compose`

#### Using Task

```bash
# Open a shell in the container
task docker:shell

# Run a bash command
task docker:run:bash -- [command]

# Run an artisan command
task docker:run:artisan -- [command]
```

#### Using Docker Directly

```bash
# Open a shell
docker exec -it restarters bash

# Run a bash command
docker exec -it restarters bash -c "[command]"

# Run an artisan command
docker exec -it restarters php artisan [command]
```

### Stopping the Environment

#### Using Task

```bash
# Stop basic environment
task docker:down

# Stop with development tools
task docker:down-dev

# Stop all containers
task docker:down-all
```

#### Using Docker Compose

```bash
# Stop basic environment
docker compose down

# Stop with specific set of containers
docker compose --profile dev down

# Stop all containers
docker compose --profile '*'
```

### Rebuilding Containers

If you need to completely rebuild. This will remove all containers, volumes, networks, and images.

#### Using Task
```bash
# Using Task
task docker:rebuild

# Using specific profile
task docker:rebuild-dev
```

#### Using Docker Compose
```bash
# Using Docker Compose
docker compose down -v --rmi all
docker compose up -d

# Using specific profile
docker compose --profile dev down -v --rmi all
docker compose --profile dev up -d

# Using all profiles
docker compose --profile '*' down -v --rmi all
docker compose --profile '*' up -d
```
