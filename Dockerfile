FROM php:8.2-apache

# Purge any duplicate MPM modules and enable prefork exclusively
RUN rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf \
    && a2enmod mpm_prefork rewrite

# Install required PHP extensions for PDO and MySQL
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Copy application files to Apache root
COPY . /var/www/html/

# Set permissions and working directory
WORKDIR /var/www/html
RUN chown -R www-data:www-data /var/www/html

# Verify Apache configuration syntax
RUN apache2ctl configtest

EXPOSE 80

CMD ["apache2-foreground"]
