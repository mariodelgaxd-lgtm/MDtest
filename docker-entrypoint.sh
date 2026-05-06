#!/bin/bash
set -e

echo "========================================"
echo "Iniciando MDTest..."
echo "========================================"

# ARREGLAR MPM: Desactivar mpm_event en tiempo de ejecución
# (Railway podría estar usando imagen en caché)
echo "Configurando Apache MPM..."
a2dismod mpm_event mpm_worker 2>/dev/null || true
a2enmod mpm_prefork rewrite 2>/dev/null || true

# Verificar que solo hay un MPM activo
echo "MPMs activos:"
ls -la /etc/apache2/mods-enabled/mpm_* 2>/dev/null || echo "Ninguno encontrado"

# Configurar Apache para usar el puerto correcto (Railway asigna PORT)
if [ -n "$PORT" ]; then
    echo "Configurando puerto $PORT para Railway..."
    sed -i "s/Listen 80/Listen $PORT/g" /etc/apache2/ports.conf
    sed -i "s/:80/:$PORT/g" /etc/apache2/sites-available/000-default.conf
fi

# Verificar estructura de archivos
echo "Verificando archivos..."
if [ -f /var/www/html/index.php ]; then
    echo "✓ Archivos del proyecto encontrados"
else
    echo "✗ ERROR: No se encontraron archivos del proyecto"
    exit 1
fi

# Verificar configuración de Apache antes de iniciar
echo "Verificando configuración de Apache..."
apache2ctl configtest || {
    echo "✗ ERROR en configuración de Apache"
    echo "Intentando corregir..."
    # Último recurso: forzar solo mpm_prefork
    rm -f /etc/apache2/mods-enabled/mpm_*
    ln -s /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/
    ln -s /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/
    apache2ctl configtest
}

# Verificar conexión a base de datos (NO bloqueante)
if [ -n "$DB_HOST" ] && [ "$DB_HOST" != "localhost" ]; then
    echo "Verificando conexión a base de datos en $DB_HOST..."
    if mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -e "SELECT 1" "$DB_NAME" > /dev/null 2>&1; then
        echo "✓ Base de datos conectada!"
    else
        echo "⚠ ADVERTENCIA: No se pudo conectar a la base de datos"
        echo "Host: $DB_HOST, User: $DB_USER, DB: $DB_NAME"
        echo "La aplicación iniciará pero puede no funcionar correctamente hasta que la BD esté lista"
    fi
else
    echo "Modo local detectado (sin DB_HOST externo)"
fi

# Iniciar Apache inmediatamente
echo "========================================"
echo "MDTest listo! Iniciando Apache..."
echo "========================================"
exec "$@"
