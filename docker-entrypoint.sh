#!/bin/bash
set -e

echo "========================================"
echo "Iniciando MDTest..."
echo "========================================"

# Configurar Apache para usar el puerto correcto (Railway asigna PORT)
if [ -n "$PORT" ]; then
    echo "Configurando puerto $PORT para Railway..."
    sed -i "s/Listen 80/Listen $PORT/g" /etc/apache2/ports.conf
    sed -i "s/:80/:$PORT/g" /etc/apache2/sites-available/000-default.conf
fi

# Verificar conexión a base de datos
if [ -n "$DB_HOST" ] && [ "$DB_HOST" != "localhost" ]; then
    echo "Esperando conexión a base de datos en $DB_HOST..."
    max_tries=30
    count=0
    
    while [ $count -lt $max_tries ]; do
        if mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -e "SELECT 1" "$DB_NAME" > /dev/null 2>&1; then
            echo "✓ Base de datos conectada!"
            break
        fi
        
        count=$((count + 1))
        echo "Intento $count/$max_tries - esperando base de datos..."
        sleep 2
    done
    
    if [ $count -eq $max_tries ]; then
        echo "⚠ ADVERTENCIA: No se pudo conectar a la base de datos"
        echo "La aplicación iniciará pero puede no funcionar correctamente"
    fi
else
    echo "Modo local detectado (sin DB_HOST externo)"
fi

# Verificar estructura de archivos
echo "Verificando archivos..."
if [ -f /var/www/html/index.php ]; then
    echo "✓ Archivos del proyecto encontrados"
else
    echo "✗ ERROR: No se encontraron archivos del proyecto"
    exit 1
fi

# Iniciar Apache
echo "========================================"
echo "MDTest listo! Iniciando Apache..."
echo "========================================"
exec "$@"
