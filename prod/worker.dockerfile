FROM php:8.2

RUN apt-get update \
 && apt-get install -y cron git zlib1g-dev libicu-dev libmagickwand-dev libmcrypt-dev libzip-dev zip libonig-dev \
 && docker-php-ext-configure intl \
 && docker-php-ext-install -j$(nproc) zip pdo pdo_mysql mbstring iconv calendar intl sockets gd \
 && pecl install imagick mcrypt \
 && docker-php-ext-enable imagick gd

COPY prod/php.ini /usr/local/etc/php/php.ini-production
COPY prod/php.ini /usr/local/etc/php/php.ini-development
COPY prod/php.ini /usr/local/etc/php/php.ini

WORKDIR /var/www/html

CMD ["php", "/var/www/html/artisan", "queue:work"]
