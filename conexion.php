<?php
$servername = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';
$dbname = getenv('DB_NAME') ?: 'mdtest2';
$port = getenv('DB_PORT') ?: '3306';

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname, (int)$port);

// Verificar conexión
if ($conn->connect_error) {
    // En producción, loguear el error pero no mostrar detalles sensibles
    error_log("Error de conexión MySQL: " . $conn->connect_error);
    die("Error de conexión a la base de datos. Por favor, inténtalo más tarde.");
}

$conn->set_charset("utf8mb4");
?>
