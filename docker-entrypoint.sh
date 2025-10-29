#!/bin/bash
set -e

echo "Waiting for MySQL to be ready..."
while ! nc -z ${MYSQL_HOST:-database} 3306 2>/dev/null; do
  sleep 1
done

echo "MySQL is ready!"

# Create required directories
mkdir -p logs
mkdir -p assets/uploads
mkdir -p cache

# Set proper permissions
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html

echo "Starting Apache..."
exec apache2-foreground
