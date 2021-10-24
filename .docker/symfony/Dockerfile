FROM php:7.4-fpm AS php

RUN apt-get update -y --fix-missing && apt-get install -y \
    chromium \
    chromium-l10n \
    git \
    imagemagick \
    libfreetype6-dev \
    libgmp-dev \
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
RUN pecl install apcu imagick memcached zip
RUN docker-php-ext-install exif gd gmp opcache pdo pdo_mysql sockets zip pcntl intl
RUN docker-php-ext-enable apcu imagick memcached

# Increase memory limit
RUN echo 'memory_limit = 4G' >> /usr/local/etc/php/conf.d/docker-php-memlimit.ini;

COPY /.docker/symfony/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

# Générate DKIM keys
RUN openssl genrsa -out keys/private.pem 1024
RUN openssl rsa -in keys/private.pem -outform PEM -pubout -out keys/public.pem
