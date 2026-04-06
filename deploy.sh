#!/bin/bash

# DWAI Studio - Deploy Script
# Run this to prepare your app for Railway deployment

echo "🚀 DWAI Studio - Railway Deployment Prep"

# Get APP_KEY
KEY=$(php -r "echo 'base64:' . base64_encode(random_bytes(32));")

# Update .env file
if [ -f .env ]; then
    # Backup existing .env
    cp .env .env.backup
    
    # Update key values
    sed -i "s|APP_KEY=.*|APP_KEY=$KEY|" .env
    sed -i "s|APP_ENV=.*|APP_ENV=production|" .env
    sed -i "s|LOG_CHANNEL=.*|LOG_CHANNEL=stderr|" .env
    
    echo "✅ Updated .env with production settings"
else
    echo "❌ No .env found. Run: cp .env.example .env"
    exit 1
fi

# Add .env to git (if not already)
git add .env 2>/dev/null
git add railway.json 2>/dev/null
git add RAILWAY-DEPLOY.md 2>/dev/null
git add setup.sh 2>/dev/null

# Commit
echo "📦 Committing changes..."
git commit -m "Prepare for Railway deployment" 2>/dev/null

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅ Ready to deploy!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "📋 Your APP_KEY:"
grep "APP_KEY" .env
echo ""
echo "🚀 Next steps:"
echo "1. Push to GitHub: git push origin main"
echo "2. Go to railway.app"
echo "3. Create new project → Connect GitHub"
echo "4. Add PostgreSQL"
echo "5. Add these Railway Variables:"
echo "   - DB_CONNECTION=pgsql"
echo "   - DB_HOST=\${DB_HOST}"
echo "   - DB_PORT=5432"
echo "   - DB_DATABASE=\${DB_DATABASE}"
echo "   - DB_USERNAME=\${DB_USERNAME}"
echo "   - DB_PASSWORD=\${DB_PASSWORD}"
echo ""
echo "6. Deploy! 🎉"
