FROM php:8.2

# Add crontab file in the cron directory
COPY dev/crontab /etc/cron.d/pizzareader

RUN apt-get update \
 && apt-get install -y cron git zlib1g-dev libicu-dev libmagickwand-dev libmcrypt-dev libzip-dev zip libonig-dev \
 && docker-php-ext-configure intl \
 && docker-php-ext-install -j$(nproc) zip pdo pdo_mysql mbstring iconv calendar intl sockets gd \
 && pecl install imagick mcrypt \
 && docker-php-ext-enable imagick gd \
 && chmod 0600 /etc/cron.d/pizzareader \
 && curl -sS https://getcomposer.org/installer \
           | php -- --install-dir=/usr/local/bin --filename=composer --version=2.1.6 \
     && composer self-update

COPY dev/php.ini /usr/local/etc/php/php.ini-production
COPY dev/php.ini /usr/local/etc/php/php.ini-development
COPY dev/php.ini /usr/local/etc/php/php.ini

WORKDIR /var/www/html

CMD ["cron", "-f", "-L", "8"]
