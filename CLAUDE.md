# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel 12 application with Filament 4 admin panel. The application uses:
- **PHP 8.3** with Laravel 12 framework
- **Filament v4** for admin interface (accessible at `/admin`)
- **MySQL** as the default database
- **Tailwind CSS v4** for styling
- **Livewire v3** (bundled with Filament)
- **Pest v4** for testing
- **Laravel Telescope** for debugging (dev only)
- **Laravel Debugbar** for debugging (dev only)
- **Laravel Boost** MCP server for enhanced development tools

## Development Commands

### Running the Application

```bash
# Start full development environment (server, queue, logs, vite)
composer run dev

# Start individual services
php artisan serve          # Development server only
npm run dev               # Vite dev server only
npm run build             # Build assets for production

# Start queue worker
php artisan queue:listen --tries=1

# View logs in real-time
php artisan pail --timeout=0
```

### Testing

```bash
# Run all tests
php artisan test
# or
composer test

# Run specific test file
php artisan test tests/Feature/ExampleTest.php

# Run tests matching a pattern
php artisan test --filter=testName

# Run Pest in parallel (if configured)
php artisan test --parallel
```

### Code Quality

```bash
# Format code with Laravel Pint (ALWAYS run before committing)
vendor/bin/pint

# Format only changed files
vendor/bin/pint --dirty

# Generate IDE helper files (runs automatically after composer update)
php artisan ide-helper:generate
php artisan ide-helper:meta
```

### Database

```bash
# Run migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback

# Fresh migration (drops all tables)
php artisan migrate:fresh

# Seed database
php artisan db:seed

# Fresh migration with seeding
php artisan migrate:fresh --seed
```

### Debugging Tools

```bash
# Access Laravel Telescope dashboard
# Visit: http://localhost/telescope (when TELESCOPE_ENABLED=true)

# Use Tinker REPL
php artisan tinker

# Clear various caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Filament Commands

```bash
# Create Filament resource
php artisan make:filament-resource ModelName --no-interaction

# Create Filament page
php artisan make:filament-page PageName --no-interaction

# Create Filament widget
php artisan make:filament-widget WidgetName --no-interaction

# Upgrade Filament (runs automatically after composer update)
php artisan filament:upgrade
```

## Architecture & Structure

### Laravel 12 Modern Structure

This project uses Laravel 12's streamlined structure:

- **No `app/Http/Middleware/`** - Middleware is registered in `bootstrap/app.php`
- **No `app/Console/Kernel.php`** - Console configuration in `bootstrap/app.php` or `routes/console.php`
- **Commands auto-discover** - Files in `app/Console/Commands/` are automatically registered
- **Service providers** - Application-specific providers are in `bootstrap/providers.php`

### Key Configuration Files

- `bootstrap/app.php` - Application bootstrap, middleware, and exception handling
- `bootstrap/providers.php` - Service provider registration
- `config/` - All configuration files (use `config()` helper, never `env()` outside config)
- `.env` - Environment variables (never commit this file)

### Filament Structure

The Filament admin panel is configured in:
- `app/Providers/Filament/AdminPanelProvider.php` - Main panel configuration
- `app/Filament/Resources/` - CRUD resources (currently empty, to be created)
- `app/Filament/Pages/` - Custom pages (currently empty, to be created)
- `app/Filament/Widgets/` - Dashboard widgets (currently empty, to be created)

The admin panel is accessible at `/admin` and uses the default Filament authentication.

### Database Configuration

- **Default**: MySQL database (configured in `.env` with DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD)
- **Testing**: In-memory SQLite (configured in `phpunit.xml`)
- **Sessions**: Stored in database (SESSION_DRIVER=database)
- **Cache**: Stored in database (CACHE_STORE=database)
- **Queue**: Stored in database (QUEUE_CONNECTION=database)

### Frontend Asset Pipeline

- **Vite** builds assets from `resources/css/app.css` and `resources/js/app.js`
- **Tailwind CSS v4** is configured via `@tailwindcss/vite` plugin
- **Important**: Use `@import "tailwindcss"` (not `@tailwind` directives)
- Run `npm run build` for production or `npm run dev` for development

### Testing Structure

- `tests/Feature/` - Feature tests (majority of tests should be here)
- `tests/Unit/` - Unit tests
- `tests/Pest.php` - Pest configuration and helpers
- `tests/TestCase.php` - Base test case class
- Tests use in-memory SQLite and have Telescope/Debugbar disabled

## Important Development Notes

### Environment Setup

First-time setup:
```bash
composer run setup
```

This will:
1. Install Composer dependencies
2. Copy `.env.example` to `.env`
3. Generate application key
4. Run migrations
5. Install npm dependencies
6. Build frontend assets

### Development Tools

**Debugbar** - Shows debug information at the bottom of pages when `APP_DEBUG=true`
- Toggle with `DEBUGBAR_ENABLED` environment variable

**Telescope** - Advanced debugging dashboard at `/telescope`
- Only enabled when `TELESCOPE_ENABLED=true`
- Configured to run in local environment only via `TelescopeServiceProvider`

**IDE Helper** - Provides autocomplete for Laravel facades and models
- Generated files: `_ide_helper.php`, `.phpstorm.meta.php` (gitignored)
- Auto-regenerates after `composer update`

### Laravel Boost MCP Server

This project uses Laravel Boost MCP server which provides specialized tools:
- `list-artisan-commands` - List available Artisan commands with parameters
- `get-absolute-url` - Get correct project URLs with scheme/domain/port
- `tinker` - Execute PHP code via Tinker
- `database-query` - Query database directly
- `browser-logs` - Read browser console logs/errors
- `search-docs` - Search version-specific Laravel ecosystem documentation

**Always use `search-docs` before implementing Laravel/Filament/Livewire features** to ensure version-correct approach.

### Code Style & Conventions

- **Always run `vendor/bin/pint --dirty` before committing**
- Use PHP 8 constructor property promotion
- Always use explicit return type declarations
- Use descriptive method/variable names
- Follow existing code conventions in the project
- Check sibling files for patterns before creating new ones

### Common Patterns

**Models**:
- Use `casts()` method instead of `$casts` property
- Include proper return types on relationship methods
- Use eager loading to prevent N+1 queries

**Validation**:
- Create Form Request classes for validation (not inline in controllers)
- Check existing Form Requests for array vs string rule format

**Configuration**:
- Use `config('key')` not `env('KEY')` outside config files
- Environment variables should only be accessed in config files

**Testing**:
- Write Pest tests (not PHPUnit)
- Use factories for model creation
- Most tests should be feature tests
- Run minimal tests with `--filter` during development

### Filament Best Practices

- Use Artisan commands to create Filament components (`make:filament-*`)
- Always pass `--no-interaction` to Artisan commands
- Components use static `make()` methods for initialization
- Use `relationship()` method on form components when possible
- Test Filament features with Livewire test helpers

## Git Workflow

This project uses Laravel Herd and is currently on branch `main`.

When committing:
1. Run `vendor/bin/pint --dirty` to format code
2. Run tests related to your changes
3. Stage files and commit with descriptive messages
4. Never commit `.env` file or IDE helper files (they're gitignored)

## Quick Reference

| Task | Command |
|------|---------|
| Start dev environment | `composer run dev` |
| Run tests | `php artisan test` |
| Format code | `vendor/bin/pint` |
| Create migration | `php artisan make:migration` |
| Create model | `php artisan make:model -mfs` |
| Create Filament resource | `php artisan make:filament-resource` |
| Access Telescope | Visit `/telescope` |
| Access Filament admin | Visit `/admin` |
