FROM php:8.4.1-fpm-alpine3.21 AS base-platform

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN install-php-extensions apcu
RUN install-php-extensions redis

WORKDIR /app

## ==============================================
FROM base-platform AS dev
## ==============================================
ENV APP_ENV="dev"
ENV APP_DEBUG=1
COPY docker/php/php.dev.ini $PHP_INI_DIR/conf.d/30-php.ini
