ARG PHP_VERSION=8.4
FROM php:${PHP_VERSION}-cli-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        libicu-dev \
        libonig-dev \
        libxml2-dev \
        libzip-dev \
        unzip \
        zip \
    && docker-php-ext-install \
        intl \
        mbstring \
        pdo_mysql \
        sysvshm \
        xml \
        zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app

RUN mkdir -p /tmp/composer-cache \
    && chmod 1777 /tmp/composer-cache

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_CACHE_DIR=/tmp/composer-cache

CMD ["bash"]
