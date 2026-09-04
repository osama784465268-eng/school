FROM php:8.2-apache

# Install MariaDB server and PHP extensions for internal database execution
RUN apt-get update && apt-get install -y mariadb-server mariadb-client \
    && docker-php-ext-install pdo pdo_mysql mysqli \
    && rm -rf /var/lib/apt/lists/*

# Fix Apache MPM conflict by forcing prefork exclusively
RUN rm -rf /etc/apache2/mods-enabled/mpm_* \
    && ln -s /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/ \
    && ln -s /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/ \
    && a2enmod rewrite

# Copy application code
COPY . /var/www/html/
WORKDIR /var/www/html
RUN chown -R www-data:www-data /var/www/html

EXPOSE 8080

CMD ["sh", "-c", "service mariadb start && mysql -u root -e 'CREATE DATABASE IF NOT EXISTS school_db;' && mysql -u root school_db < /var/www/html/database/school_db.sql 2>/dev/null || true && PORT=${PORT:-8080} && sed -i \"s/Listen [0-9]*/Listen $PORT/\" /etc/apache2/ports.conf && sed -i \"s/<VirtualHost \\*:[0-9]*>/<VirtualHost \\*:$PORT>/\" /etc/apache2/sites-available/000-default.conf && apache2-foreground"]
