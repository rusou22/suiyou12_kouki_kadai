FROM php:8.4-fpm-alpine AS php

RUN docker-php-ext-install pdo_mysql



RUN install -o www-data -g www-data -d /var/www/upload/image/



RUN apk add --no-cache autoconf build-base \
    && yes '' | pecl install redis \
    && docker-php-ext-enable redis



COPY ./php.ini ${PHP_INI_DIR}/php.ini
