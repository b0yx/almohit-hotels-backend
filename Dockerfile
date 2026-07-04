# ==============================================================================
# Almohit Hotels Backend - Multi-stage Production Dockerfile
# ==============================================================================

# ------------------------------------------------------------------------------
# Stage 1: Vendor Dependencies Builder
# ------------------------------------------------------------------------------
FROM php:8.4-fpm-alpine AS builder

WORKDIR /var/www/html

# Install system dependencies and build packages
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    postgresql-dev \
    oniguruma-dev \
    linux-headers \
    $PHPIZE_DEPS

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        bcmath \
        intl \
        zip \
        gd \
        mbstring \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy composer files and install production dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy application source code
COPY . .

# Dump autoload files
RUN composer dump-autoload --no-dev --optimize

# ------------------------------------------------------------------------------
# Stage 2: Production Runtime
# ------------------------------------------------------------------------------
FROM php:8.4-fpm-alpine AS runner

WORKDIR /var/www/html

# Install runtime dependencies only
RUN apk add --no-cache \
    curl \
    netcat-openbsd \
    libpng \
    libjpeg-turbo \
    freetype \
    libzip \
    icu-libs \
    libpq \
    oniguruma

# Copy compiled PHP extensions and configs from builder stage
COPY --from=builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=builder /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/

# Copy custom PHP & OPcache configurations
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/laravel.ini /usr/local/etc/php/conf.d/laravel.ini

# Copy application files from builder stage
COPY --from=builder --chown=www-data:www-data /var/www/html /var/www/html

# Set directory permissions for Laravel
RUN mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod +x /var/www/html/docker/entrypoint.sh

# Expose PHP-FPM port
EXPOSE 9000

# Set healthcheck for PHP-FPM container
HEALTHCHECK --interval=10s --timeout=5s --start-period=15s --retries=3 \
    CMD nc -z 127.0.0.1 9000 || exit 1

# Set entrypoint and default command
ENTRYPOINT ["/var/www/html/docker/entrypoint.sh"]
CMD ["php-fpm"]
