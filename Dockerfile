FROM richarvey/nginx-php-fpm:1.7.2

# Copy composer files
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Copy application files
COPY . /var/www/html

WORKDIR /var/www/html

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Create a link to the storage folder
RUN php artisan storage:link

# Expose port
EXPOSE 80

# Start the application
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=80"]