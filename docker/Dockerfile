# syntax=docker/dockerfile:1.7

# --- Base PHP-FPM image (Debian) ---
FROM php:8.4-fpm AS php-base

ENV DEBIAN_FRONTEND=noninteractive \
    PATH="/var/www/html/vendor/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin" \
    COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html

# Install system dependencies, PHP extensions, and Node.js
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates git curl unzip libzip-dev libpng-dev libjpeg62-turbo-dev \
        libfreetype6-dev libonig-dev libicu-dev libxml2-dev libxslt1.1 libxslt1-dev \
        libpq-dev libssl3 supervisor fonts-liberation libasound2 libatk1.0-0 libatk-bridge2.0-0 \
        libcups2 libdrm2 libgbm1 libgtk-3-0 libnss3 libx11-6 libx11-xcb1 libxcomposite1 \
        libxdamage1 libxext6 libxfixes3 libxrandr2 libxshmfence1 xdg-utils wget \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql intl zip bcmath \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && npm install -g pnpm@9 \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# --- Build vendor dependencies ---
FROM php-base AS vendor
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-progress --no-interaction --optimize-autoloader --no-scripts

# --- Build frontend assets and Puppeteer ---
FROM php-base AS frontend
WORKDIR /var/www/html

COPY package.json package-lock.json ./
COPY vite.config.js ./
COPY resources ./resources
COPY --from=vendor /var/www/html/vendor /var/www/html/vendor

RUN npm ci --no-audit --no-fund \
    && if [ -d resources ] && [ -f vite.config.js ]; then npm run build; else echo "No frontend sources found; skipping asset build"; fi \
    && npm prune --omit=dev --no-audit --no-fund \
    && npx puppeteer browsers install chrome \
    && npx puppeteer --version

# --- Final runtime image ---
FROM php-base AS app
WORKDIR /var/www/html

# Copy application code
COPY . /var/www/html

# Copy built vendors and node_modules (for Puppeteer / Browsershot) and public assets
COPY --from=vendor /var/www/html/vendor /var/www/html/vendor
COPY --from=frontend /var/www/html/node_modules /var/www/html/node_modules
COPY --from=frontend /var/www/html/public/build /var/www/html/public/build

# PHP production config
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && sed -ri 's/^;?opcache.enable=.*/opcache.enable=1/' "$PHP_INI_DIR/php.ini" \
    && sed -ri 's/^;?opcache.validate_timestamps=.*/opcache.validate_timestamps=0/' "$PHP_INI_DIR/php.ini"

# Prepare Puppeteer cache for runtime user and install Chrome there
ENV PUPPETEER_CACHE_DIR=/var/www/.cache/puppeteer
RUN mkdir -p /var/www/.cache/puppeteer \
    && chown -R www-data:www-data /var/www/.cache

# Ensure correct permissions
RUN chown -R www-data:www-data storage bootstrap/cache \
    && find storage -type d -exec chmod 775 {} \; \
    && find storage -type f -exec chmod 664 {} \; \
    && chmod -R 775 bootstrap/cache

# Prepare PsySH config dir for artisan tinker (must be created as root before switching user)
RUN mkdir -p /var/www/.config/psysh \
    && chown -R www-data:www-data /var/www/.config

# Healthcheck
HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 CMD php -v || exit 1

# Laravel entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

USER www-data
RUN npx puppeteer browsers install chrome \
    && npx puppeteer --version
ENV APP_ENV=production \
    BROWSERSHOT_NO_SANDBOX=true

ENTRYPOINT ["/usr/local/bin/entrypoint"]
CMD ["php-fpm"]

# --- Static web server image with built assets (Nginx) ---
FROM nginx:1.27-alpine AS web
WORKDIR /var/www/html
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY public /var/www/html/public
COPY --from=frontend /var/www/html/public/build /var/www/html/public/build
RUN mkdir -p /var/www/html/storage/app/public \
    && ln -s /var/www/html/storage/app/public /var/www/html/public/storage || true
