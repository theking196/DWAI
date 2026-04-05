# Quick Reference - Commands

## Development
```bash
# Start dev server
php artisan serve

# Hot reload assets
npm run dev
```

## Database
```bash
# Migrate
php artisan migrate

# Seed
php artisan db:seed

# Fresh + seed
php artisan migrate:fresh --seed

# Show status
php artisan migrate:status
```

## Queue
```bash
# Start worker
php artisan queue:work

# Restart worker
php artisan queue:restart
```

## Cache & Clear
```bash
php artisan cache:clear
php artisan view:clear
php artisan config:clear
php artisan route:clear
```

## Routes
```bash
# List routes
php artisan route:list
```

## Tinker (Database Playground)
```bash
php artisan tinker
```

## Key URLs
- Dashboard: `/dashboard`
- Projects: `/projects`
- Sessions: `/sessions`
