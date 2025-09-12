FROM richarvey/nginx-php-fpm:1.7.2

# Copiar todos los archivos directamente
COPY . /var/www/html

# Cambiar al directorio de trabajo
WORKDIR /var/www/html

# Crear archivos composer si no existen
RUN if [ ! -f composer.json ]; then echo '{"name":"laravel-app"}' > composer.json; fi
RUN if [ ! -f composer.lock ]; then touch composer.lock; fi

# Instalar dependencias PHP
RUN composer install --optimize-autoloader --no-dev

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Generar clave de aplicación
RUN php artisan key:generate --force

# Exponer puerto
EXPOSE 80

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=80"]