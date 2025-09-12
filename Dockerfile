FROM richarvey/nginx-php-fpm:1.7.2

# Crear directorio de trabajo
WORKDIR /var/www/html

# Copiar composer.json si existe, crear uno básico si no
COPY composer.json . 2>/dev/null || echo '{"name":"laravel-app", "require": {"php": "^8.1"}}' > composer.json

# Eliminar composer.lock si existe y crear uno válido o instalar dependencias sin lock
RUN if [ -f composer.lock ]; then rm composer.lock; fi

# Copiar el resto de la aplicación
COPY . .

# Instalar dependencias PHP (sin usar composer.lock)
RUN composer install --optimize-autoloader --no-dev --no-scripts

# Generar composer.lock después de la instalación
RUN composer dump-autoload

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Generar clave de aplicación
RUN php artisan key:generate --force

# Ejecutar scripts post-install (después de configurar permisos)
RUN composer run-script post-autoload-dump

# Exponer puerto
EXPOSE 80

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=80"]