# Restarters.net Technical Documentation

This document provides technical details about the Restarters.net platform architecture, technologies, and testing.

## Architecture

### Backend
- **Framework**: Laravel 10 with PHP 8+
- **Database**: MySQL 8.0
- **Caching**: Redis for performance optimization
- **Queue**: Background job processing for integrations
- **ORM**: Laravel Eloquent

### Frontend
- **JavaScript Framework**: Vue.js 2 components
- **Templates**: Blade templates (gradual migration to full SPA - see [Migration Strategy](docs/migration-to-vue.md))
- **State Management**: Vuex stores
- **Asset Bundling**: Vite for modern build pipeline
- **UI Framework**: Bootstrap 4

### Development & Build
- **Package Manager**: Composer (PHP), npm (JavaScript)
- **Asset Compilation**: Vite
- **Code Style**: PHP CS Fixer
- **Testing**: PHPUnit, Jest, Playwright

## Key Technologies

### PHP Libraries
- **Laravel Eloquent ORM** - Database interactions and relationships
- **Laravel Auditing** - Change tracking and audit trails
- **Intervention Image** - Image processing and optimization
- **GuzzleHTTP** - HTTP client for API integrations
- **Laravel Sanctum** - API authentication (planned)

### JavaScript Libraries
- **Vue.js 2** - Component-based UI development
- **Vuex** - State management
- **Vuelidate** - Form validation
- **Bootstrap Vue** - Vue-compatible Bootstrap components
- **Axios** - HTTP client for API calls

### External Service Integrations
- **Discourse API** - Forum integration (Restarters Talk)
- **MediaWiki API** - Wiki integration (Restarters Wiki)
- **WordPress XML-RPC** - Content publishing
- **Geocoding Service** - Location lookup
- **Drip** - Email marketing
- **Zapier Webhooks** - Workflow automation

### Documentation & Standards
- **OpenAPI/Swagger** - API documentation (available at `/api/documentation`)
- **RESTful API** - v2 API follows REST principles
- **JSON** - Standard data interchange format

## Performance Features

### Caching Strategy
- **Database Query Caching** - Reduces database load for frequently accessed data
- **API Response Caching** - Homepage data cached with 12-hour TTL
- **Notification Caching** - Discourse notifications cached with 60-second TTL
- **Redis** - In-memory data store for cache

### Optimization Techniques
- **Image Optimization** - Automatic resizing and compression
- **Lazy Loading** - Components loaded on demand
- **Pagination** - Large datasets split across multiple pages
- **Code Splitting** - JavaScript bundles split for faster initial load
- **Database Indexing** - Optimized queries with proper indexes

### Background Processing
- **Queue System** - Laravel queues for long-running tasks
- **Job Processing** - Discourse syncs, WordPress publishing, email sending
- **Event-Driven Architecture** - Laravel events and listeners

## Testing Coverage

The application has comprehensive test coverage across multiple levels:

### PHPUnit Tests (95 test files)

**Feature Tests** - End-to-end testing of major features:
- User registration and authentication
- Group management (create, edit, delete, join, invite)
- Event management (create, edit, delete, RSVP, moderation)
- Device recording and repair tracking
- API endpoints (v1 and v2)
- Network coordination
- Statistics calculation and impact metrics
- External integrations (Discourse, WordPress, Wiki)
- Email notifications
- Admin functionality
- Role and permission system

**Unit Tests** - Component-level testing:
- Model relationships and scopes
- Business logic in services
- Helper functions
- Calculation algorithms (CO2, waste prevented)

**Test Organization**:
- `/tests/Feature/` - Feature tests organized by domain
- `/tests/Unit/` - Unit tests for isolated components
- `/tests/Integration/` - Playwright E2E tests

### Playwright Tests (4 E2E tests)

End-to-end browser testing:
- Landing page functionality
- Device recording workflow
- Group creation and management
- Event creation and management

**Configuration**:
- Browser: Chromium (Firefox and Safari support commented out)
- Workers: 1 (to avoid CSRF issues)
- Screenshots and videos on failure
- Trace capture for debugging

### Test Commands

```bash
# PHPUnit tests
./vendor/bin/phpunit                          # Run all tests
./vendor/bin/phpunit --filter TestName       # Run specific test

# Playwright tests
npm test                                       # Run Playwright tests

# JavaScript unit tests
npm run jest                                   # Run Jest tests
```

## Database Design

### Core Tables
- **users** - User accounts and profiles
- **groups** - Community repair groups
- **events** (parties) - Repair events
- **devices** - Repaired items with impact data
- **networks** - Regional networks
- **categories** - Device categories with LCA data

### Key Features
- **Soft Deletes** - Enabled for users, events, groups
- **Audit Trail** - All changes tracked via Laravel Auditing
- **Timestamps** - Created/updated timestamps on all tables
- **JSON Columns** - Flexible network_data storage
- **Pivot Tables** - Many-to-many relationships (user-groups, group-networks)
- **Foreign Keys** - Referential integrity maintained

### Timezone Handling
- **UTC Storage** - Events stored as UTC timestamps
- **Timezone Awareness** - Groups and events have timezone fields
- **Virtual Columns** - Event date/time calculated from UTC + timezone
- **Network Inheritance** - Groups inherit timezone from networks if not set

## Security

### Authentication & Authorization
- **CSRF Protection** - Laravel middleware on all forms
- **Password Hashing** - Bcrypt with configurable rounds
- **Session Management** - Secure session handling
- **Role-Based Access Control** - 5 user roles with granular permissions
- **Authorization Policies** - Eloquent policies for resource access
- **API Token Authentication** - Secure API access via tokens

### Data Protection
- **Soft Deletes** - Data preservation for compliance
- **User Anonymization** - GDPR-compliant deletion
- **Email Verification** - Required for registration
- **Consent Tracking** - GDPR consent records (cookies, data usage)
- **Audit Trail** - Complete change history

### API Security
- **Rate Limiting** - Throttling on API endpoints
- **Input Validation** - Laravel validation on all inputs
- **SQL Injection Prevention** - Eloquent ORM prevents SQL injection
- **XSS Prevention** - Blade template escaping
- **Hash-Based Links** - Secure invitation and calendar links

## Deployment

### Production Requirements
- PHP 8.0+
- MySQL 8.0+
- Redis (recommended for caching)
- Composer
- Node.js 16+ & npm

### Environment Configuration
- `.env` file for environment-specific settings
- Database credentials
- External API keys (Discourse, WordPress, Drip, Geocoding)
- Mail server configuration
- Cache driver configuration (Redis recommended)

### Build Process
```bash
composer install --no-dev           # Install PHP dependencies
npm install                         # Install JavaScript dependencies
npm run build                       # Build production assets
php artisan migrate                 # Run database migrations
php artisan config:cache            # Cache configuration
php artisan route:cache             # Cache routes
php artisan view:cache              # Cache Blade templates
```

### CI/CD
- **CircleCI** - Continuous integration
- **Automated Testing** - PHPUnit runs on every commit
- **Code Coverage** - Coveralls integration
- **Branch Protection** - Develop and production branches protected

## Summary Statistics

- **Total Routes**: 150+ endpoints (web + API)
- **Core Entities**: 6 main models (Users, Groups, Events, Devices, Networks, Categories)
- **Controllers**: 35+ (including API v1 and v2)
- **User Roles**: 5 distinct roles with granular permissions
- **External Integrations**: 6+ external services
- **Supported Languages**: 3 language variants (EN, FR, FR-BE)
- **API Versions**: Legacy v1 + RESTful v2
- **Test Coverage**: 95 PHPUnit tests + 4 Playwright E2E tests
- **Database Tables**: 30+ tables
- **Vue Components**: 40+ reusable components
- **Vuex Store Modules**: Multiple domain-specific stores

## Related Documentation

- **[Migration to Vue Strategy](docs/migration-to-vue.md)** - Frontend architecture evolution plan
- **[Features Documentation](Features.md)** - Complete feature list and user capabilities
- **[Local Development Setup](docs/local-development.md)** - Getting started guide for developers
- **[CLAUDE.md](CLAUDE.md)** - AI assistant guidelines and project overview
- **[API Documentation](https://restarters.net/api/documentation)** - OpenAPI/Swagger interactive docs
- **[GitHub Wiki](https://github.com/TheRestartProject/restarters.net/wiki)** - Additional developer resources
