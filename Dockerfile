FROM php:7.4.33-apache

COPY . /var/www/html/

COPY docker-php.conf /etc/apache2/conf-available/docker-php.conf

RUN docker-php-ext-install mysqli pdo_mysql

RUN a2enmod rewrite