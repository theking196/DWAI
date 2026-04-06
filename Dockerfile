FROM ubuntu:22.04

# Prevent interactive prompts
ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=UTC

# Install PHP, NGINX, and dependencies
RUN apt-get update && apt-get install -y \
    php8.1-fpm \
    php8.1-mbstring \
    php8.1-xml \
    php8.1-curl \
    php8.1-zip \
    php8.1-pgsql \
    php8.1-gd \
    php8.1-intl \
    php8.1-bcmath \
    nginx \
    git \
    curl \
    unzip \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# Get Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
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

# Install PHP dependencies
RUN composer install --ignore-platform-reqs --no-scripts --no-dev

# Copy nginx config
RUN echo 'server { listen 8080 default_server; root /var/www/public; index index.php; server_name _; location / { try_files $uri $uri/ /index.php?$query_string; } location ~ \.php$ { include fastcgi_params; fastcgi_pass 127.0.0.1:9000; fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; } }' > /etc/nginx/sites-available/default

# Expose port 8080
EXPOSE 8080

# Start services
CMD service php8.1-fpm start && nginx -g 'daemon off;'
