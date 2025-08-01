FROM php:8.2-apache

ARG user
ARG uid

RUN useradd -G www-data,root -u $uid -d /home/$user $user

COPY dev/php.ini-production /usr/local/etc/php/php.ini-production
COPY dev/php.ini-development /usr/local/etc/php/php.ini-development
COPY dev/php.ini /usr/local/etc/php/php.ini

RUN apt-get update \
 && apt-get install -y git zlib1g-dev libicu-dev libmagickwand-dev libmcrypt-dev libzip-dev zip libonig-dev libjpeg-dev \
 && docker-php-ext-configure intl \
 && docker-php-ext-configure gd --enable-gd --with-freetype --with-jpeg --with-webp \
 && docker-php-ext-install -j$(nproc) zip pdo pdo_mysql mbstring iconv calendar intl sockets gd \
 && pecl install imagick mcrypt \
 && docker-php-ext-enable imagick gd \
    && a2enmod rewrite \
    && sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && curl -sS https://getcomposer.org/installer \
          | php -- --install-dir=/usr/local/bin --filename=composer --version=2.1.6 \
    && composer self-update \
    && chown -R www-data:www-data /var/www/html

USER $user

WORKDIR /var/www/html
