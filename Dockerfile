
FROM php:8.3-apache

# Install the MySQL driver for PHP
RUN docker-php-ext-install pdo_mysql

# Set the working directory
WORKDIR /var/www/html

# Copy our application files
COPY . /var/www/html/

# Apache listens on port 80
EXPOSE 80
