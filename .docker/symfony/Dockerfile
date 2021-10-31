FROM php:7.4-fpm AS php

RUN apt-get update -y --fix-missing && apt-get install -y \
    chromium \
    chromium-l10n \
    git \
    imagemagick \
    libfreetype6-dev \
    libgmp-dev \
    librabbitmq-dev \
    libmemcached-dev \
    libmagickwand-dev --no-install-recommends \
    libpng-dev \
    libzip-dev \
    memcached \
    unzip

# Get composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN pecl install apcu imagick memcached zip amqp
RUN docker-php-ext-install exif gd gmp opcache pdo pdo_mysql sockets zip pcntl intl sysvsem
RUN docker-php-ext-enable apcu imagick memcached amqp

# Increase memory limit
RUN echo 'memory_limit = 4G' >> /usr/local/etc/php/conf.d/docker-php-memlimit.ini;

COPY /.docker/symfony/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

# Générate DKIM keys
RUN mkdir keys
RUN openssl genrsa -out keys/private.pem 1024
RUN openssl rsa -in keys/private.pem -outform PEM -pubout -out keys/public.pem
