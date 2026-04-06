#!/bin/bash

# DWAI Studio - Local Setup Script
# Run this on your local machine to prepare for deployment

echo "🚀 DWAI Studio Local Setup"

# 1. Check if .env exists
if [ ! -f .env ]; then
    echo "📄 Creating .env from example..."
    cp .env.example .env
else
    echo "✅ .env already exists"
fi

# 2. Generate APP_KEY
echo "🔑 Generating APP_KEY..."
KEY=$(php -r "echo 'base64:' . base64_encode(random_bytes(32));")
sed -i "s/APP_KEY=.*/APP_KEY=$KEY/" .env

# 3. Update database for SQLite (local dev) or PostgreSQL (production)
echo "🗄️ Setting up database..."

# 4. Create SQLite database if using SQLite
if grep -q "DB_CONNECTION=sqlite" .env 2>/dev/null; then
    touch database/database.sqlite
    echo "✅ SQLite database created"
fi

# 5. Display the APP_KEY for Railway
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📋 APP_KEY (copy this for Railway):"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
grep "APP_KEY" .env
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

echo ""
echo "✅ Setup complete!"
echo ""
echo "Next steps:"
echo "1. Commit .env: git add .env && git commit -m 'Add env file'"
echo "2. Push: git push origin main"
echo "3. Deploy to Railway"
