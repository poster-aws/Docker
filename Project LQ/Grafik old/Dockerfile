FROM php:8.1-fpm

# Установка расширений для работы с MySQL
RUN apt-get update && \
    apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev

# Установим дополнительные библиотеки для работы с MySQL

# Конфигурируем расширение GD с поддержкой FreeType и JPEG
RUN docker-php-ext-configure gd --with-freetype --with-jpeg

# Устанавливаем расширение GD
RUN docker-php-ext-install gd

# Устанавливаем расширение MySQLi
RUN docker-php-ext-install mysqli