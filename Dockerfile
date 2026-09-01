FROM composer:2 AS dependencies

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader

FROM php:8.4-apache-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    libcurl4-openssl-dev \
    libzip-dev \
    && docker-php-ext-install pdo_mysql curl zip \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/* \
    && mkdir -p /var/lib/pqr/uploads \
    && chown -R www-data:www-data /var/lib/pqr

COPY docker/php.ini /usr/local/etc/php/conf.d/production.ini
COPY . /var/www/html/
COPY --from=dependencies /app/vendor /var/www/html/vendor

RUN chown -R root:root /var/www/html \
    && chown -R www-data:www-data /var/lib/pqr

EXPOSE 80
