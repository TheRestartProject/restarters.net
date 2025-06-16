# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Restarters.net is a suite of software for the repair community that brings together community repair enthusiasts and activists. It combines three core modules:

- **The Fixometer** - engine for organizing community repair events and recording their impact
- **Restarters Talk** - space for local and global discussion of community repair (powered by Discourse)
- **Restarters Wiki** - collectively produced knowledge base of advice and support (powered by MediaWiki)

This is a Laravel 9 application with PHP 8+ that integrates with external services including Discourse, MediaWiki, and WordPress.

## Development Commands

### Local Development Setup
```bash
# Using Docker (recommended for full development environment)
docker-compose up -d

# The application will be available at:
# - Restarters: http://www.example.com:8001
# - phpMyAdmin: http://www.example.com:8002  
# - Discourse: http://www.example.com:8003
# - Mailhog: http://localhost:8025

# Note: Add www.example.com to your hosts file pointing to your Docker host
```

### Common Development Commands
```bash
# Install PHP dependencies
composer install

# Install and build frontend assets
npm install
npm run dev          # Development build
npm run watch        # Watch for changes
npm run production   # Production build

# Laravel commands
php artisan migrate            # Run database migrations
php artisan migrate:fresh     # Fresh migration (drops all tables)
php artisan seed              # Run database seeders
php artisan tinker            # Laravel REPL
php artisan lang:js           # Generate JavaScript translation files
php artisan translations:check # Check translation completeness

# Generate application key (for new installs)
php artisan key:generate
```

### Testing
```bash
# Run PHP unit tests
./vendor/bin/phpunit

# Run specific test file
./vendor/bin/phpunit tests/Unit/ExampleTest.php

# Run JavaScript tests
npm run jest

# Run Playwright end-to-end tests
npm test
```

### Code Quality
```bash
# PHP CS Fixer (code style)
./tools/php-cs-fixer.phar fix

# Check code style without fixing
./tools/php-cs-fixer.phar fix --dry-run --diff
```

## Architecture Overview

### Core Domain Models
- **Device** (`app/Device.php`) - Represents items brought to repair events with repair status, categories, and impact calculations
- **Group** (`app/Group.php`) - Repair groups that organize events, with location, network affiliations, and member management
- **Party** (`app/Party.php`) - Repair events hosted by groups, with attendee management and statistics calculation
- **User** (`app/User.php`) - Platform users with roles (Admin, Host, Restarter, NetworkCoordinator)
- **Network** (`app/Network.php`) - Regional networks that groups can belong to
- **Category** (`app/Category.php`) - Device categories for classification and impact calculations

### Key Relationships
- Groups have many Events (Parties)
- Events have many Devices
- Users belong to Groups through UserGroups with roles
- Groups belong to Networks through group_network pivot table
- Devices belong to Categories and have repair status tracking

### External Integrations
- **Discourse API** - User management, group creation, SSO integration
- **MediaWiki API** - User account sync and authentication
- **WordPress XML-RPC** - Content publishing for events and groups
- **Geocoding** - Location services for groups and events
- **Drip** - Email marketing integration

### Database Design
- Uses Laravel migrations in `database/migrations/`
- Soft deletes enabled for key models (Events, Users)
- Audit trail via `owen-it/laravel-auditing` package
- JSON columns for flexible network_data storage

### Frontend Stack
- **Vue 2** components for interactive features
- **Laravel Mix** for asset compilation
- **Bootstrap 4** for styling
- **SCSS** for styles in `resources/sass/`
- Multiple build targets: main app, global styles, wiki styles

### Permission System
- Role-based permissions via custom `app/Role.php` and `app/Permissions.php`
- Middleware for authentication and authorization
- Network coordinators have regional permissions
- Group-level host permissions

### File Structure Patterns
- Controllers follow resource naming (e.g., `DeviceController`, `GroupController`)
- Events and Listeners in `app/Events/` and `app/Listeners/`
- Custom artisan commands in `app/Console/Commands/`
- API controllers separate from web controllers
- Blade templates in `resources/views/` with partials organization

### Testing Strategy
- PHPUnit for backend unit and feature tests
- Jest for JavaScript component testing
- Playwright for end-to-end testing
- Database factory patterns for test data

## Important Configuration
- Environment variables critical for external service integration
- Docker setup provides development databases and services
- Queue processing for background jobs (Discourse, WordPress sync)
- Caching used extensively for performance (categories, statistics)
- Timezone handling important for global event management

## Development Notes
- The codebase uses custom helper functions in `app/Helpers/`
- Translation files support multiple locales in `lang/` directory
- Image handling via intervention/image with custom sizing
- Custom validation rules in `app/Rules/`
- Event-driven architecture with model observers