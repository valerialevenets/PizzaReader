FROM serversideup/php:8.2-unit
#COPY . /var/www/html

WORKDIR /var/www/html

RUN composer install --no-scripts --no-dev

# Copy the conf files into the docker container
COPY prod/php.ini /usr/local/etc/php/
COPY prod/.env /var/www/html
