.PHONY: deploy setup migrate install

# DWAI Studio Makefile

# Generate APP_KEY and prepare for deployment
deploy:
	@echo "🚀 Preparing for Railway deployment..."
	@chmod +x deploy.sh
	@./deploy.sh

# Local setup
setup:
	@chmod +x setup.sh
	@./setup.sh

# Install dependencies
install:
	@composer install
	@cp .env.example .env
	@php artisan key:generate

# Run migrations
migrate:
	@php artisan migrate

# Run seeder
seed:
	@php artisan db:seed

# Start local server
serve:
	@php artisan serve

# Clear all caches
cache-clear:
	@php artisan config:clear
	@php artisan cache:clear
	@php artisan route:clear
	@php artisan view:clear

# Build for production
production:
	@composer install --no-dev --optimize-autoloader
	@php artisan config:cache
	@php artisan route:cache
	@php artisan view:cache
