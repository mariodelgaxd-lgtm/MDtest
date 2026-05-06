# Usar imagen oficial de PHP con Apache
FROM php:8.2-apache

# Instalar extensiones necesarias para MySQL y utilidades
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd mysqli pdo pdo_mysql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# ARREGLAR MPM: Desactivar mpm_event y activar mpm_prefork (requerido por PHP)
RUN a2dismod mpm_event mpm_worker 2>/dev/null || true
RUN a2enmod mpm_prefork rewrite

# Copiar configuración de Apache
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# Copiar todo el proyecto
COPY . /var/www/html/

# Crear directorio para imágenes y dar permisos
RUN mkdir -p /var/www/html/imagenes_preguntas \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Puerto expuesto
EXPOSE 80

# Script de inicio
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
