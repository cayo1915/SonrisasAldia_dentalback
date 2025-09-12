FROM richarvey/nginx-php-fpm:1.7.2

# Copiar todos los archivos
COPY . /var/www/html

# Cambiar al directorio de trabajo
WORKDIR /var/www/html

# Eliminar composer.lock si existe para evitar problemas
RUN if [ -f composer.lock ]; then rm composer.lock; fi

# Crear composer.json si no existe
RUN if [ ! -f composer.json ]; then echo '{"name":"laravel/laravel","require":{"php":">=8.1"}}' > composer.json; fi

# Instalar dependencias sin archivo lock
RUN composer install --optimize-autoloader --no-dev --ignore-platform-reqs

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Generar clave de aplicación
RUN php artisan key:generate --force

# Exponer puerto
EXPOSE 80

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=80"]