FROM richarvey/nginx-php-fpm:1.7.2

# Copiar todos los archivos
COPY . /var/www/html

# Cambiar al directorio de trabajo
WORKDIR /var/www/html

# Instalar Composer 2 (sobrescribiendo el Composer 1 de la imagen base)
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer --2

# Instalar dependencias (usando composer.json original de tu proyecto)
RUN composer install --optimize-autoloader --no-dev --ignore-platform-reqs

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Generar clave de aplicación
RUN php artisan key:generate --force

# Exponer puerto
EXPOSE 80

# Ejecutar Laravel usando artisan serve
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=80"]
