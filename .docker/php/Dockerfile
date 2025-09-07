FROM php:8.4-fpm-alpine

RUN set -eux; \
    apk add --no-cache \
        bash git unzip curl \
        icu-dev libzip-dev oniguruma-dev \
        postgresql-dev \
        $PHPIZE_DEPS; \
    docker-php-ext-configure intl; \
    docker-php-ext-install -j"$(nproc)" intl bcmath pdo pdo_pgsql pcntl zip; \
    pecl install redis; \
    docker-php-ext-enable redis; \
    apk del $PHPIZE_DEPS; \
    rm -rf /tmp/pear

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
ENV COMPOSER_ALLOW_SUPERUSER=1
