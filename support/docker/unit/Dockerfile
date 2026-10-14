# Production runs Phabricator on PHP 7.2 (see `php::globals::php_version` in
# puppet's `phabricator_*` role hieradata).
FROM php:7.2-cli-buster

# Buster is end-of-life, so its packages now only live on archive.debian.org,
# where they no longer change (hence no pinned versions).
# hadolint ignore=DL3008
RUN set -eux; \
  echo 'deb http://archive.debian.org/debian buster main' \
    > /etc/apt/sources.list; \
  echo 'deb http://archive.debian.org/debian-security buster/updates main' \
    >> /etc/apt/sources.list; \
  apt-get update; \
  apt-get install --no-install-recommends --yes git unzip; \
  rm --force --recursive /var/lib/apt/lists/*

RUN docker-php-ext-install -j"$(nproc)" mysqli pcntl

# Production pins Composer 2.2.7 (see `php::composer::version` in puppet).
COPY --from=composer:2.2.7 /usr/bin/composer /usr/local/bin/composer

# The working copy is bind mounted and owned by whoever is on the host, which
# `git` would otherwise refuse to read.
RUN git config --system --add safe.directory '*'
