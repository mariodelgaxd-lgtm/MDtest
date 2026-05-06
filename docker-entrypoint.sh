#!/bin/bash
set -e

echo "========================================"
echo "Iniciando MDTest..."
echo "========================================"

# Configurar Apache MPM correctamente
echo "Configurando Apache MPM..."
a2dismod mpm_event mpm_worker 2>/dev/null || true
a2enmod mpm_prefork rewrite 2>/dev/null || true

# Configurar puerto para Railway
if [ -n "$PORT" ]; then
    echo "Configurando puerto $PORT..."
    sed -i "s/Listen 80/Listen $PORT/g" /etc/apache2/ports.conf
    sed -i "s/:80/:$PORT/g" /etc/apache2/sites-available/000-default.conf
fi

# Verificar configuración de Apache
echo "Verificando configuración de Apache..."
if apache2ctl configtest 2>&1 | grep -q "Syntax OK"; then
    echo "✓ Configuración OK"
else
    echo "Corrigiendo configuración..."
    rm -f /etc/apache2/mods-enabled/mpm_*
    ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/
    ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/
fi

echo "========================================"
echo "Apache iniciado en puerto ${PORT:-80}"
echo "========================================"
exec "$@"
