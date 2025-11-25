FROM php:7.4-fpm-bullseye

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
