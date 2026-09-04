FROM php:8.2-apache

# Install required PHP extensions for PDO and MySQL
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy application files to Apache root
COPY . /var/www/html/

# Set permissions and working directory
WORKDIR /var/www/html
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
