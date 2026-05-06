<?php
require_once "conexion.php";

$sql = file_get_contents("mejoras_db.sql");

// Dividir en sentencias individuales
$sentencias = array_filter(array_map('trim', explode(';', $sql)));

$errores = [];
$exitos = 0;

foreach ($sentencias as $sentencia) {
    if (empty($sentencia)) continue;
    
    if ($conn->query($sentencia)) {
        $exitos++;
    } else {
        $errores[] = "Error en: " . substr($sentencia, 0, 50) . "... - " . $conn->error;
    }
}

$conn->close();

echo "Resultado de la actualización de base de datos:\n";
echo "Exitos: $exitos\n";
echo "Errores: " . count($errores) . "\n";

if (!empty($errores)) {
    echo "\nDetalles de errores:\n";
    foreach ($errores as $error) {
        echo "- $error\n";
    }
}
?>
