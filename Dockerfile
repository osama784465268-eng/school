FROM php:8.2-apache

# Default PORT environment variable if not injected by Railway
ENV PORT=80

# Purge duplicate MPM modules and enable prefork + rewrite
RUN rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf \
    && a2enmod mpm_prefork rewrite

# Configure Apache to dynamically bind to Railway's ${PORT}
RUN sed -i 's/80/${PORT}/g' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

# Install required PHP extensions for PDO and MySQL
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Copy application files to Apache root
COPY . /var/www/html/

# Set permissions and working directory
WORKDIR /var/www/html
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
