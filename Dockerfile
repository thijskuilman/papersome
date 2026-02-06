# ---------- PHP base with required extensions ----------
FROM serversideup/php:8.5-frankenphp AS php-base

USER root

# Install required PHP extensions
RUN install-php-extensions \
    intl \
    bcmath \
    pdo_mysql \
    opcache \
    redis

WORKDIR /var/www/html

# ---------- Composer stage ----------
FROM php-base AS composer

COPY composer.json composer.lock ./

# Install PHP dependencies for production
RUN composer install \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

# ---------- Node build stage ----------
FROM node:20-alpine AS node-build

WORKDIR /app

ENV PUPPETEER_SKIP_DOWNLOAD=true

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build

# ---------- Runtime PHP image ----------
FROM php-base

# Install Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get update \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# Copy source code
COPY . .

# Copy Composer dependencies from Composer stage
COPY --from=composer /var/www/html/vendor vendor

# Copy built frontend assets from Node stage
COPY --from=node-build /app/public/build public/build

# Ensure Laravel can write to storage and cache
RUN mkdir -p storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Switch to non-root user
USER www-data
