FROM php:7.4-apache

RUN docker-php-ext-install mysqli
RUN apt-get update && apt-get install -y libzip-dev zip && docker-php-ext-install zip
COPY src/ /var/www/html/
