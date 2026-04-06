# Railway Deployment Guide

## Step 1: Prepare Your Repo

Your repo already has the files needed. Just make sure:

1. **.env file** exists (copy from .env.example if needed):
```bash
cp .env.example .env
```

2. **Generate APP_KEY** locally:
```bash
php artisan key:generate
```

3. **Commit** the .env file (without real API keys):
```bash
git add .env
git commit -m "Add env file"
git push origin main
```

---

## Step 2: Create Railway Project

1. Go to **railway.app**
2. Click **"Sign Up"** → Use GitHub
3. Click **"New Project"**
4. Select **"Empty Project"**

---

## Step 3: Add Database

1. In your Railway project dashboard
2. Click **"+ New"** → **"Database"** → **"Add PostgreSQL"**
3. Wait for it to provision (green status)
4. Click **"PostgreSQL"** → **"Connect"** → **"Environment Variables"**
5. Copy the `DATABASE_URL`

---

## Step 4: Add Laravel Service

1. Click **"+ New"** → **"GitHub Repo"**
2. Select your forked repo (`theking196/DWAI`)
3. Click **"Deploy Now"**

---

## Step 5: Configure Environment Variables

In Railway, go to your app service **"Variables"** tab:

Add these variables:

| Variable | Value |
|----------|-------|
| `APP_ENV` | `production` |
| `APP_KEY` | Generate locally with `php artisan key:generate`, copy the key |
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST` | From DATABASE_URL (after @) |
| `DB_PORT` | `5432` |
| `DB_DATABASE` | From DATABASE_URL (between / and ?) |
| `DB_USERNAME` | From DATABASE_URL (after //) |
| `DB_PASSWORD` | From DATABASE_URL (before @) |
| `LOG_CHANNEL` | `stderr` |

---

## Step 6: Configure Build & Start

In your app service settings:

| Setting | Value |
|---------|-------|
| **Build Command** | `composer install --no-dev --optimize-autoloader` |
| **Start Command** | `php artisan migrate --force && php-fpm` |

---

## Step 7: Deploy

1. Click **"Deploy"**
2. Watch logs for errors
3. If successful, click **"Generate Domain"** to get a free URL

---

## Troubleshooting

### Error: "No application detected"
Create a `railway.json`:
```json
{
  "$schema": "https://railway.app/railway.schema.json",
  "build": {
    "builder": "nixpacks",
    "buildCommand": "composer install --no-dev --optimize-autoloader"
  },
  "start": {
    "cmd": "php artisan migrate --force && php-fpm"
  }
}
```

### Error: Database connection
- Double-check all DB_* variables
- Make sure PostgreSQL is in same project

### Error: APP_KEY invalid
- Generate fresh key locally: `php artisan key:generate`
- Copy the full key (starts with `base64:`)

---

## Done! 🎉

Your app will be live at `https://your-app-name.up.railway.app`

The free tier gives:
- $5 credit/month
- 500 execution hours
- 1 GB storage
