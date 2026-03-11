FROM php:8.1-fpm

RUN apt-get update && \
    apt-get install -y libpng-dev libjpeg-dev libfreetype6-dev

RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install gd mysqli
