FROM php:7-fpm

RUN docker-php-ext-install -j$(nproc) mysqli

WORKDIR /usr/local/src
