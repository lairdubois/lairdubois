FROM php:7.4-fpm AS php

RUN apt-get update -y --fix-missing && apt-get install -y \
    chromium \
    chromium-l10n \
    git \
    imagemagick \
    libgmp-dev \
    libmemcached-dev \
    libmagickwand-dev --no-install-recommends \
    libpng-dev \
    libzip-dev \
    memcached \
    unzip

# Get composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

#RUN docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ --with-png-dir=/usr/include/
RUN pecl install apcu imagick memcached zip
RUN docker-php-ext-install exif gd gmp opcache pdo pdo_mysql sockets zip pcntl intl
RUN docker-php-ext-enable apcu imagick memcached

# Increase memory limit
RUN echo 'memory_limit = 4G' >> /usr/local/etc/php/conf.d/docker-php-memlimit.ini;
