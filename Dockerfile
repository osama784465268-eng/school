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

EXPOSE 8080

CMD ["sh", "-c", "PORT=${PORT:-8080} && sed -i \"s/Listen [0-9]*/Listen $PORT/\" /etc/apache2/ports.conf && sed -i \"s/<VirtualHost \\*:[0-9]*>/<VirtualHost \\*:$PORT>/\" /etc/apache2/sites-available/000-default.conf && apache2-foreground"]
