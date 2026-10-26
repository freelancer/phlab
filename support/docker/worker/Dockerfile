FROM php:7.4-cli-bullseye

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

RUN \
  apt-get update && \
  apt-get install --no-install-recommends --yes git procps && \
  rm --force --recursive /var/lib/apt/lists/*

RUN docker-php-ext-install -j$(nproc) mysqli
RUN docker-php-ext-install -j$(nproc) pcntl
