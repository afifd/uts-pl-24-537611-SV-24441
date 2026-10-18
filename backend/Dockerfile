FROM docker.io/dunglas/frankenphp:1.13.0-php8.5-alpine

# Install PostgreSQL extension and Composer
RUN install-php-extensions \
    pdo_pgsql \
    pgsql \
    @composer

WORKDIR /app

# Copy dependency definitions first to leverage Docker layer caching
COPY composer.json composer.lock ./

# Install Composer dependencies without dev packages and without scripts yet
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy application source code
COPY . .

# Generate optimized autoload files
RUN composer dump-autoload --optimize --no-dev

# Ensure storage and bootstrap/cache permissions
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

ENV SERVER_NAME=":8000"
EXPOSE 8000
