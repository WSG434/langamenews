FROM composer:latest AS composer

FROM php:8.5-fpm-alpine

RUN apk add --no-cache \
    linux-headers \
    autoconf \
    g++ \
    make \
    rabbitmq-c-dev \
    icu-dev \
    libzip-dev \
    libxml2-dev \
    oniguruma-dev \
    && docker-php-ext-install \
        pdo_mysql \
        intl \
        zip \
        xml \
        mbstring \
        opcache \
    && pecl install amqp xdebug \
    && docker-php-ext-enable amqp xdebug \
    && apk del autoconf g++ make linux-headers

COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN echo '[xdebug]' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo 'xdebug.mode=develop,debug' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo 'xdebug.client_host=host.docker.internal' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo 'xdebug.start_with_request=yes' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
