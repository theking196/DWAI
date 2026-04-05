# DWAI Studio - Laravel Version

Converted HTML template to Laravel with Vite.

## Setup

```bash
# Install dependencies
npm install

# Start dev server
npm run dev

# Or with PHP server
php artisan serve
```

## Structure

```
dwai-studio-laravel/
├── app/Http/Controllers/   # Laravel controllers
├── resources/
│   ├── css/app.css       # Main styles
│   ├── js/app.js        # Main scripts
│   └── views/
│       ├── layouts/app.blade.php   # Main layout
│       └── pages/
│           └── dashboard.blade.php
├── routes/web.php        # Routes
├── vite.config.js       # Vite config
└── package.json
```

## Preserved from Original

- ✅ Layout structure (header, sidebar, main, footer)
- ✅ Design tokens (colors, spacing, shadows)
- ✅ Component styles (buttons, forms, cards, modals, badges)
- ✅ Responsive breakpoints (mobile, tablet, desktop)
- ✅ Theme toggle (dark/low-contrast)
- ✅ Collapsible panels

## Asset Loading

Uses Vite for HMR:
```blade
@vite(['resources/css/app.css'])
@vite(['resources/js/app.js'])
```

## Controllers

Project-based routing with fallback to localStorage for demo data.

## Next Steps

1. Connect real database in `.env`
2. Create migrations
3. Wire API endpoints
4. Replace mockServer with real backend