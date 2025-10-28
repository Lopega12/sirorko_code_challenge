FROM php:8.3-fpm
LABEL authors="Lorenzo Pérez"
RUN apt-get update \
&& apt-get install -y --no-install-recommends \
git unzip libzip-dev libicu-dev libonig-dev default-mysql-client \
acl file wget curl \
&& docker-php-ext-install intl opcache zip pdo pdo_mysql \
&& pecl install redis \
&& docker-php-ext-enable redis \
&& rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-overrides.ini
WORKDIR /var/www/html
# Opcional: Xdebug en dev
ARG WITH_XDEBUG=false
RUN if [ "$WITH_XDEBUG" = "true" ]; then \
pecl install xdebug \
&& docker-php-ext-enable xdebug \
&& echo "zend_extension=$(php -r 'echo ini_get(\"extension_dir\");')/xdebug.so" > /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
&& echo "xdebug.mode=debug,develop" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
&& echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
; fi
ENTRYPOINT ["php-fpm"]