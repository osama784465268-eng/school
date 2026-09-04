FROM php:8.2-apache

# Use modern ENV key=value syntax
ENV PORT=80

# Fix AH00534: Purge duplicate MPM modules and force mpm_prefork ONLY
RUN rm -rf /etc/apache2/mods-enabled/mpm_* \
    && ln -s /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/ \
    && ln -s /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/ \
    && a2enmod rewrite

# Install required PHP extensions for PDO and MySQL
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Copy application files to Apache root
COPY . /var/www/html/

# Set permissions and working directory
WORKDIR /var/www/html
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["sh", "-c", "echo \"export PORT=\\${PORT:-80}\" >> /etc/apache2/envvars && sed -i \"s/Listen .*/Listen \\${PORT}/\" /etc/apache2/ports.conf && sed -i \"s/<VirtualHost \\*:.*/<VirtualHost \\*:\\${PORT}>/\" /etc/apache2/sites-available/000-default.conf && apache2-foreground"]
