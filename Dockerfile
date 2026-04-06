FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pgsql mbstring exif pcntl bcmath gd

# Get Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy entire app
COPY . .

# Create required directories
RUN mkdir -p bootstrap/cache \
    && mkdir -p storage/framework/cache \
    && mkdir -p storage/framework/sessions \
    && mkdir -p storage/framework/views \
    && mkdir -p storage/logs \
    && chmod 777 bootstrap/cache \
    && chmod -R 777 storage

# Set permissions
RUN chown -R www-data:www-data /var/www

# Install dependencies
RUN composer install --ignore-platform-reqs --no-scripts --no-dev

# Expose port
EXPOSE 9000

# Start PHP-FPM
CMD ["php-fpm"]
