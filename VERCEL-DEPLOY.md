# Vercel Deployment Guide (Full Steps)

## Prerequisite
Make sure your GitHub repo has these files:
- `vercel.json` ✅ (added)
- `public/index.php` ✅ (added)
- `public/.htaccess` ✅ (added)
- `bootstrap/app.php` ✅ (exists)
- `vendor/` (will be built on Vercel)

---

## Step 1: Push Updates to GitHub

The files have been added. Just push:
```bash
git add .
git commit -m "Add Vercel deployment files"
git push origin main
```

---

## Step 2: Deploy on Vercel

### 2.1 Go to Vercel
1. Open **vercel.com**
2. Sign up with **GitHub**
3. Click **"Add New..."** → **"Project"**

### 2.2 Import Your Repo
1. Find `theking196/DWAI` (or your fork)
2. Click **"Import"**

### 2.3 Configure
1. **Framework Preset**: Leave as "Other" (Vercel detects PHP)
2. **Build Command**: Leave empty (uses vercel.json)
3. **Output Directory**: Leave empty (uses vercel.json)

### 2.4 Environment Variables
Click **"Environment Variables"** and add:

| Name | Value |
|------|-------|
| `APP_ENV` | `production` |
| `APP_KEY` | `base64:dH9kQvzPJVLxR2eFmK8N4pW6cY1bA0sE5hQ9rT3uI4=` |
| `APP_DEBUG` | `false` |
| `LOG_CHANNEL` | `stderr` |

### 2.5 Deploy
1. Click **"Deploy"**
2. Wait 2-3 minutes for build
3. Get your URL: `https://your-project.vercel.app`

---

## Step 3: Add Database (Optional)

Vercel doesn't have free PostgreSQL. Use **Neon.tech**:

### 3.1 Create Neon Database
1. Go to **neon.tech**
2. Sign up with GitHub
3. Create **New Project** → Name it `dwai`
4. Copy connection string (starts with `postgres://`)

### 3.2 Add DB Variables in Vercel
Add these to your Vercel environment variables:

| Name | Value |
|------|-------|
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST` | `<from neon connection string>` |
| `DB_PORT` | `5432` |
| `DB_DATABASE` | `<from neon>` |
| `DB_USERNAME` | `<from neon>` |
| `DB_PASSWORD` | `<from neon>` |

### 3.3 Trigger Redeploy
1. Go to Vercel dashboard
2. Click **"Deployments"** → Latest deployment
3. Click **"Redeploy"**

---

## Troubleshooting

### Error: "No PHP Runtime"
Make sure `vercel.json` is in root and contains the PHP builder.

### Error: "vendor/autoload.php not found"
Vercel's PHP builder should run `composer install`. If not, add to vercel.json:
```json
"installCommand": "composer install --no-dev --optimize-autoloader"
```

### Error: "Route not found"
This is normal - Laravel routes need to be configured. For now, use direct URLs:
- `/dashboard`
- `/projects`
- `/sessions/create`

---

## Done! 🎉

Your app will be live at: `https://your-app-name.vercel.app`

The free tier includes:
- 100GB bandwidth/month
- Custom domains
- SSL certificates
