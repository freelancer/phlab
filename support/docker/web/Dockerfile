FROM php:7.4-fpm-bullseye

# Bullseye is end-of-life, and its security updates are gone from
# deb.debian.org, so its packages now come from archive.debian.org. Its Release
# files have no Valid-Until, so apt needs no Check-Valid-Until override.
RUN set -eux; \
  echo 'deb http://archive.debian.org/debian bullseye main' \
    > /etc/apt/sources.list; \
  echo 'deb http://archive.debian.org/debian bullseye-updates main' \
    >> /etc/apt/sources.list; \
  echo 'deb http://archive.debian.org/debian-security bullseye-security main' \
    >> /etc/apt/sources.list

# APCu compatible with 7.4
RUN set -eux; \
  pecl channel-update pecl.php.net; \
  pecl install apcu; \
  docker-php-ext-enable apcu; \
  pecl clear-cache

# build deps for gd
RUN set -eux; \
  apt-get update; \
  apt-get install --no-install-recommends -y \
    libfreetype6-dev libjpeg62-turbo-dev libpng-dev; \
  # Note: flags changed in newer PHP: no "-dir" suffixes
  docker-php-ext-configure gd --with-freetype --with-jpeg; \
  docker-php-ext-install -j"$(nproc)" gd mysqli opcache; \
  rm -rf /var/lib/apt/lists/*

# tools (name change: python3-pygments)
RUN set -eux; \
  apt-get update; \
  apt-get install --no-install-recommends -y git imagemagick python3-pygments; \
  rm -rf /var/lib/apt/lists/*
