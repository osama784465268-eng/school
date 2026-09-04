FROM php:8.2-apache

# Install required PHP extensions for PDO and MySQL
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy application files to Apache root
COPY . /var/www/html/

# Copy docker-entrypoint script for dynamic Railway port binding
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Set permissions
RUN chown -R www-data:www-data /var/www/html

WORKDIR /var/www/html

EXPOSE 8080

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
