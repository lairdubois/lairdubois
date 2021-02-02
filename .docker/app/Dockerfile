FROM php:7.3-fpm AS php

RUN apt-get update -y && apt-get install -y \
    git \
    imagemagick \
    libgmp-dev \
    libmemcached-dev \
    libmagickwand-dev --no-install-recommends \
    libzip-dev \
    memcached

# Get composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php -r "if (hash_file('sha384', 'composer-setup.php') === '756890a4488ce9024fc62c56153228907f1545c228516cbf63f885e036d37e9a59d27d63f46af1d4d07ee0f76181c7d3') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
    && php composer-setup.php \
    && php -r "unlink('composer-setup.php');" \
    && mv composer.phar /usr/local/bin/composer

RUN pecl install apcu imagick memcached zip
RUN docker-php-ext-install exif gmp opcache pdo pdo_mysql sockets zip
RUN docker-php-ext-enable apcu imagick memcached

# Increase memory limit
RUN echo 'memory_limit = 4G' >> /usr/local/etc/php/conf.d/docker-php-memlimit.ini;

COPY --from=node:10 /usr/local/bin/node /usr/bin/node
RUN apt-get install -y npm && npm install -g less