# Laravel on Railway - Official Method
# Railway auto-detects Laravel and runs via php-fpm

FROM php:8.2-fpm-alpine

# Install dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    zip \
    unzip \
    postgresql-dev \
    nodejs \
    npm

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd

# Get Composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy app
COPY . .

# Create directories
RUN mkdir -p bootstrap/cache \
    && mkdir -p storage/framework/cache \
    && mkdir -p storage/framework/sessions \
    && mkdir -p storage/framework/views \
    && mkdir -p storage/logs \
    && chmod -R 775 bootstrap/cache storage \
    && chown -R www-data:www-data /var/www

# Install dependencies
RUN composer install --ignore-platform-reqs --no-scripts --no-dev

# Expose port 8080 (Railway handles the rest)
EXPOSE 8080

CMD ["php-fpm"]
