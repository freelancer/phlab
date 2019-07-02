FROM php:7-fpm

RUN \
  pecl channel-update pecl.php.net && \
  pecl install apcu && \
  docker-php-ext-enable apcu

RUN docker-php-ext-install -j$(nproc) mysqli
RUN docker-php-ext-install -j$(nproc) opcache

WORKDIR /usr/local/src
