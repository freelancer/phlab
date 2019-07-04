FROM php:7-cli

RUN docker-php-ext-install -j$(nproc) mysqli
RUN docker-php-ext-install -j$(nproc) pcntl

ADD https://raw.githubusercontent.com/vishnubob/wait-for-it/master/wait-for-it.sh /usr/local/bin/wait-for-it

# The `ADD` command doesn't have a `--chmod` flag (see https://github.com/moby/moby/issues/34819).
RUN chmod +x /usr/local/bin/wait-for-it

ENTRYPOINT ["/usr/local/bin/wait-for-it", "--strict", "database:3306", "--"]
