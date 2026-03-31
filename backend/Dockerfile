# =============================================================================
# Multi-stage Dockerfile for Symfony 6.4 LTS (PHP 8.3-FPM)
# =============================================================================

# ─── Base Stage ───────────────────────────────────────────────────────────────
FROM php:8.3-fpm-alpine AS base

# Install system dependencies
RUN apk add --no-cache \
    bash \
    curl \
    git \
    unzip \
    icu-dev \
    libpq-dev \
    libzip-dev \
    oniguruma-dev \
    linux-headers \
    $PHPIZE_DEPS

# Install PHP extensions
RUN docker-php-ext-install \
    intl \
    pdo \
    pdo_pgsql \
    pgsql \
    zip \
    opcache \
    bcmath \
    mbstring \
    sockets

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install APCu
RUN pecl install apcu && docker-php-ext-enable apcu

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ─── Development Stage ────────────────────────────────────────────────────────
FROM base AS dev

# Install Xdebug for development
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Development PHP config
RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

COPY docker/php/php-dev.ini "$PHP_INI_DIR/conf.d/99-custom.ini"

# Set proper permissions
RUN addgroup -g 1000 app && adduser -u 1000 -G app -s /bin/bash -D app
RUN chown -R app:app /var/www/html

USER app

EXPOSE 9000

CMD ["php-fpm"]

# ─── Production Build Stage ───────────────────────────────────────────────────
FROM base AS builder

WORKDIR /var/www/html

COPY composer.json composer.lock ./

# Install dependencies without dev packages
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --optimize-autoloader

COPY . .

RUN composer dump-autoload --classmap-authoritative --no-dev

# ─── Production Stage ─────────────────────────────────────────────────────────
FROM base AS prod

# Production PHP config
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php/php-prod.ini "$PHP_INI_DIR/conf.d/99-custom.ini"

WORKDIR /var/www/html

COPY --from=builder /var/www/html .

# Set permissions
RUN addgroup -g 1000 app && adduser -u 1000 -G app -s /bin/bash -D app
RUN chown -R app:app /var/www/html \
    && chmod -R 755 /var/www/html/public \
    && mkdir -p var/cache var/log \
    && chown -R app:app var/

USER app

EXPOSE 9000

CMD ["php-fpm"]
