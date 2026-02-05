FROM serversideup/php:8.5-frankenphp

USER root

RUN install-php-extensions intl bcmath

RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get update \
    && apt-get install -y nodejs \
    && npm install -g npm \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

RUN npm init -y && npm install puppeteer

USER www-data
WORKDIR /var/www/html
