# Base FrankenPHP image
FROM serversideup/php:8.5-frankenphp

# Switch to root to install extensions
USER root

# Install intl (required by Laravel) and bcmath
RUN install-php-extensions intl bcmath

# Install Node.js + npm (needed for Browsershot)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get update \
    && apt-get install -y nodejs \
    && npm install -g npm \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* \

# Drop back to unprivileged user
USER www-data

# Set working directory
WORKDIR /var/www/html
