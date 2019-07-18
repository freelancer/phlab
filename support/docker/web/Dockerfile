FROM php:7-fpm-stretch

RUN \
  pecl channel-update pecl.php.net && \
  pecl install apcu && \
  docker-php-ext-enable apcu && \
  pecl clear-cache
RUN \
  apt-get update && \
  apt-get install --no-install-recommends --yes libfreetype6-dev libjpeg62-turbo-dev libpng-dev && \
  docker-php-ext-configure gd --with-freetype-dir=/usr/include --with-jpeg-dir=/usr/include && \
  docker-php-ext-install -j$(nproc) gd && \
  rm --force --recursive /var/lib/apt/lists/*
RUN docker-php-ext-install -j$(nproc) mysqli
RUN docker-php-ext-install -j$(nproc) opcache

RUN \
  apt-get update && \
  apt-get install --no-install-recommends --yes imagemagick python-pygments
