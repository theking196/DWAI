# DWAI Studio - Application Structure

## Overview
DWAI Studio is organized following Laravel best practices with clear separation of concerns.

## Directory Structure

```
app/
├── Exceptions/          # Custom exception handlers
├── Helpers/            # Helper functions (auto-loaded)
├── Http/
│   ├── Controllers/
│   │   ├── Api/       # API controllers (AIController, ReferenceController)
│   │   ├── Web/       # Web controllers (Dashboard, Project, Session)
│   │   ├── Controller.php       # Base controller
│   │   ├── Api/ApiController.php  # Base API controller
│   │   └── Web/WebController.php  # Base web controller
│   ├── Requests/       # Form request validation classes
│   └── Resources/     # API resource transformers
├── Jobs/               # Queue jobs (GenerateAIOutput)
├── Models/             # Eloquent models
├── Policies/           # Authorization policies
├── Providers/         # Service providers
└── Services/           # Business logic
    └── AI/            # AI service layer
        ├── AIProvider.php
        ├── MockAIProvider.php
        └── AIService.php
```

## Controllers

### Web Controllers (`app/Http/Controllers/Web/`)
- Handle HTML responses
- Use Blade views
- Extend `WebController`

### API Controllers (`app/Http/Controllers/Api/`)
- Return JSON responses
- Extend `ApiController`
- Have helper methods: `success()`, `error()`, `paginate()`

## Services (`app/Services/`)
- Business logic layer
- AI services go in `Services/AI/`
- Services are auto-discovered by Laravel

## Jobs (`app/Jobs/`)
- Queue jobs for async processing
- Use `Dispatchable`, `Queueable`, `SerializesModels`

## Models (`app/Models/`)
- Eloquent models
- Include relationships
- Use traits: `HasFactory`, `SoftDeletes`

## Policies (`app/Policies/`)
- Authorization logic
- Registered in `AuthServiceProvider`

## Requests (`app/Http/Requests/`)
- Form request validation
- Extends `FormRequest`

## Helpers (`app/Helpers/`)
- Global helper functions
- Auto-loaded via `composer.json`

## Views (`resources/views/`)
- `components/` - Reusable blade components
- `layouts/` - Master layouts
- `pages/` - Page views
- `projects/` - Project-related views
- `sessions/` - Session-related views
- `emails/` - Email templates

## Routes

- `routes/web.php` - Web routes
- `routes/api.php` - API routes (local only)
- `routes/console.php` - Artisan commands

## Configuration

All config files in `config/`:
- `app.php` - Application config
- `database.php` - Database config
- `filesystems.php` - Storage config
- `queue.php` - Queue config
- `session.php` - Session config
- `cache.php` - Cache config

## Adding New Features

### 1. Create Model
```php
app/Models/FeatureName.php
```

### 2. Create Controller
```php
app/Http/Controllers/Api/FeatureController.php  # API
app/Http/Controllers/Web/FeatureController.php  # Web
```

### 3. Add Routes
```php
routes/web.php  # or routes/api.php
```

### 4. Create Views (if web)
```php
resources/views/feature/index.blade.php
```

### 5. Create Service (if complex logic)
```php
app/Services/FeatureService.php
```
