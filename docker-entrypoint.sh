#!/bin/bash
set -e

# Railway supplies dynamic PORT environment variable (default 8080 if not set)
PORT="${PORT:-8080}"

# Configure Apache to listen on $PORT
sed -i "s/Listen [0-9]*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost \*:${PORT}>/" /etc/apache2/sites-available/000-default.conf

exec "$@"
