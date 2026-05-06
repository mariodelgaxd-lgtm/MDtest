<?php
require_once "admin_session.php";
require_once "conexion.php";

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("location: indexadmin.php");
    exit;
}

$id_a_borrar = $_GET['id'];
$id_admin_actual = $_SESSION['id_usuario'];

if ($id_a_borrar == $id_admin_actual) {
    header("location: indexadmin.php");
    exit;
}

$sql = "DELETE FROM usuarios WHERE id_usuario = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $id_a_borrar);
    $stmt->execute();
    $stmt->close();
}
$conn->close();

header("location: indexadmin.php");
exit;
?>