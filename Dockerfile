FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    nginx

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

# Create nginx config
RUN echo 'server { listen 8080; root /var/www/public; index index.php; location / { try_files $uri $uri/ /index.php?$query_string; } location ~ \.php$ { fastcgi_pass 127.0.0.1:9000; fastcgi_index index.php; fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; include fastcgi_params; } }' > /etc/nginx/sites-available/default

# Install dependencies
RUN composer install --ignore-platform-reqs --no-scripts --no-dev

# Expose port 8080
EXPOSE 8080

# Start nginx and php-fpm
CMD service php8.2-fpm start && nginx -g 'daemon off;'
