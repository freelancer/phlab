FROM php:7-cli

RUN docker-php-ext-install -j$(nproc) mysqli
RUN docker-php-ext-install -j$(nproc) pcntl

WORKDIR /usr/local/src
