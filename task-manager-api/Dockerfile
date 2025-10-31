FROM php:8.2-apache

ENV TZ=America/Sao_Paulo

RUN apt-get update && \
    apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    curl && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install pdo pdo_mysql gd && \
    a2enmod rewrite headers && \
    rm -rf /var/lib/apt/lists/*

COPY docker/php/php.ini /usr/local/etc/php/php.ini
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
