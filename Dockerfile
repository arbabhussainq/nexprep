FROM php:8.2-apache

RUN rm -f /etc/apache2/mods-enabled/mpm_*.conf \
           /etc/apache2/mods-enabled/mpm_*.load && \
    a2enmod mpm_prefork && \
    a2enmod rewrite && \
    docker-php-ext-install pdo pdo_mysql mysqli

COPY . /var/www/html/

EXPOSE 80