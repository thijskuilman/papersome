FROM serversideup/php:8.5-frankenphp

USER root

# PHP extensions
RUN install-php-extensions intl bcmath

# Install Node.js (Browsershot)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get update \
    && apt-get install -y nodejs \
    && npm install -g npm \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

USER www-data

WORKDIR /var/www/html
