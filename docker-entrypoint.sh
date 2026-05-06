#!/bin/bash
set -e

# Esperar a que MySQL esté disponible
if [ -n "$DB_HOST" ] && [ "$DB_HOST" != "localhost" ]; then
    echo "Esperando a MySQL en $DB_HOST..."
    until mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -e "SELECT 1" > /dev/null 2>&1; do
        echo "MySQL no está disponible aún - esperando..."
        sleep 2
    done
    echo "MySQL está listo!"
fi

# Si existe el archivo SQL de schema, importarlo (solo si las tablas no existen)
if [ -f /var/www/html/proyecto2.sql ]; then
    echo "Verificando base de datos..."
    # No importamos automáticamente para evitar sobrescribir datos en producción
fi

exec "$@"
