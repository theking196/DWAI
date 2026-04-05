# DWAI Studio

A private local AI development environment for creating cinematic content.

## ⚠️ Private Use Only

This application is designed for **local/offline use only**. Do not expose to the internet.

## Requirements

- PHP 8.2+
- Composer
- Node.js 18+
- npm

## Quick Start

### 1. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install
```

### 2. Environment Setup

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 3. Database Setup

```bash
# Create SQLite database (if not exists)
touch database/database.sqlite

# Run migrations
php artisan migrate
```

### 4. Seed Demo Data (Optional)

```bash
# Seed sample data (Project: Boy Wonder)
php artisan db:seed
```

### 5. Build Assets

```bash
# Development (with hot reload)
npm run dev

# Production build
npm run build
```

### 6. Start Application

```bash
# Start local server
php artisan serve
```

Visit: `http://localhost:8000`

## Queue Worker (For Async AI Generation)

If using async AI generation:

```bash
# Start queue worker (runs in background)
php artisan queue:work

# Or with supervisor for production (local only)
# See: https://laravel.com/docs/queue/supervisor
```

## Features

### AI Generation
- Text generation (GPT-style)
- Image generation (DALL-E style)
- Reference-based image generation
- Queue-based async processing
- Status tracking: pending → processing → completed/failed

### Projects
- Create/edit projects
- Visual style images
- Canon entries
- Reference image library with primary selection

### Sessions
- Multiple session types (brainstorm, script, storyboard, edit)
- AI output history
- Short-term memory

### Data Management
- SQLite database (local file)
- File uploads to local storage
- No external dependencies

## Project Structure

```
app/
├── Http/Controllers/
│   ├── Api/        # JSON APIs
│   └── Web/        # HTML pages
├── Models/         # Database models
├── Services/AI/   # AI providers
├── Jobs/          # Queue jobs
└── Helpers/       # Helper functions
```

See `docs/STRUCTURE.md` for detailed architecture.

## Troubleshooting

### Database Issues
```bash
# Reset database
php artisan migrate:fresh --seed
```

### Clear Cache
```bash
php artisan cache:clear
php artisan view:clear
php artisan config:clear
```

### Queue Issues
```bash
# Restart queue worker
php artisan queue:restart
php artisan queue:work
```

## Security Notes

- `APP_ENV=local` - Keep this setting
- No internet-facing routes by default
- SQLite database stored locally
- File uploads in local storage only

## License

MIT - Private Use Only
