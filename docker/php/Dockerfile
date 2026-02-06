FROM serversideup/php:8.5-frankenphp

USER root

RUN install-php-extensions \
    intl \
    bcmath \
    pdo_mysql \
    opcache \
    redis

WORKDIR /var/www/html

USER www-data
