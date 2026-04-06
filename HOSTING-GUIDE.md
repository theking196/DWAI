# Free Laravel Hosting Platforms

## Top Recommendations

| Platform | Free Tier | Database | Notes |
|----------|-----------|----------|-------|
| **Render** | ✅ Yes | PostgreSQL | Best for Laravel, Docker support |
| **Fly.io** | ✅ 3 VMs | PostgreSQL | Global edge deployment |
| **Railway** | ✅ $5 credit/mo | PostgreSQL | Easiest setup |
| **PocketHost** | ✅ Yes | SQLite | Quickest deployment |
| **Cyclic** | ✅ Yes | No DB | Serverless |
| **StormKit** | ✅ Yes | PostgreSQL | Git integration |

## Platform Details

### 1. Render.com ⭐
- **URL**: render.com
- **Free**: Web service + PostgreSQL
- **Specs**: 750 hours/month, 512MB RAM
- **Pros**: Docker support, managed PostgreSQL, easy scaling
- **Cons**: Sleeps after 15 min inactivity (free tier)

### 2. Fly.io
- **URL**: fly.io
- **Free**: 3 shared VMs, 160GB bandwidth
- **Pros**: Edge deployment, Postgres included
- **Cons**: More complex setup

### 3. Railway
- **URL**: railway.app
- **Free**: $5 credit/month
- **Pros**: One-click Laravel, easy UI
- **Cons**: Credit-based (not truly free)

### 4. PocketHost
- **URL**: pocethost.io
- **Free**: Yes, with limits
- **Pros**: Instant deployment, SQLite
- **Cons**: Limited resources

### 5. Cyclic
- **URL**: cyclic.sh
- **Free**: Yes
- **Pros**: Serverless, auto-deploy from GitHub
- **Cons**: No persistent database (use external)

### 6. StormKit
- **URL**: stormkit.io
- **Free**: Yes
- **Pros**: EU/US deployment, Git integration
- **Cons**: Smaller free tier

## Alternative: Vercel-style (Edge)
- **Vercel** - Can host PHP via edge functions (limited)
- **Netlify** - Same, use _functions folder

## Self-Hosted Options (Free VPS)
- **Oracle Cloud** - Always free: 2 VMs, ARM
- **Google Cloud** - $300 credit, 90 days
- **AWS** - Free tier for 1 year

---

## Quick Deploy Comparison

| Platform | Difficulty | Speed | Best For |
|----------|------------|-------|----------|
| PocketHost | ⭐ Easy | ⚡ Fast | Quick tests |
| Railway | ⭐ Easy | ⚡ Fast | Prototyping |
| Render | ⭐⭐ Medium | ⚡ Fast | Production |
| Fly.io | ⭐⭐⭐ Hard | 🐢 Slow | Scale |

---

## Recommended: Render

1. Fork repo → GitHub
2. render.com → New Web Service
3. Connect GitHub
4. Add PostgreSQL
5. Set env vars
6. Deploy!

See `render.yaml` in repo for one-click config.
