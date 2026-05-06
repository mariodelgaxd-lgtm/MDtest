<?php
require_once "admin_session.php";
require_once "conexion.php";

$mensaje = "";
$error = "";
$id_usuario = 0;
$nombre = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_usuario = $_POST['id_usuario'];
    $nombre = trim($_POST['nombre']);
    
    if (empty($nombre)) {
        $error = "El nombre no puede estar vacio.";
    } else {
        $sql = "UPDATE usuarios SET nombre = ? WHERE id_usuario = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("si", $nombre, $id_usuario);
            if ($stmt->execute()) {
                $mensaje = "¡Usuario actualizado correctamente!";
            } else {
                $error = "Error al actualizar el usuario: " . $conn->error;
            }
            $stmt->close();
        }
    }
} else {
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        header("location: indexadmin.php");
        exit;
    }
    $id_usuario = $_GET['id'];
}

$sql_select = "SELECT nombre FROM usuarios WHERE id_usuario = ?";
if ($stmt_select = $conn->prepare($sql_select)) {
    $stmt_select->bind_param("i", $id_usuario);
    $stmt_select->execute();
    $resultado = $stmt_select->get_result();
    if ($resultado->num_rows == 1) {
        $usuario = $resultado->fetch_assoc();
        if ($_SERVER["REQUEST_METHOD"] != "POST") {
             $nombre = $usuario['nombre'];
        }
    } else {
        $error = "No se encontró al usuario.";
    }
    $stmt_select->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuario - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="style.css"> </head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="indexadmin.php"><i class="fa-solid fa-user-shield"></i> Panel de Admin</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="indexadmin.php">Ver Usuarios</a></li>
                    <li class="nav-item"><a class="nav-link" href="añadir_preguntas.php">Añadir Pregunta</a></li>
                </ul>
                <ul class="navbar-nav"><li class="nav-item"><a class="nav-link" href="logout.php">Cerrar Sesión (Admin)</a></li></ul>
            </div>
        </div>
    </nav>

    <main class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                
                <h1 class="mb-4">Editar Usuario</h1>
                
                <?php if ($mensaje): ?>
                    <div class="alert alert-success"><?php echo $mensaje; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <form action="editar_usuario.php" method="post" onsubmit="return confirm('¿Estás seguro de que quieres guardar estos cambios?');">
                            
                            <input type="hidden" name="id_usuario" value="<?php echo $id_usuario; ?>">
                            
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre del Usuario:</label>
                                <input type="text" name="nombre" id="nombre" class="form-control" value="<?php echo htmlspecialchars($nombre); ?>" required>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-between">
                                <a href="indexadmin.php" class="btn btn-outline-secondary">
                                    <i class="fa-solid fa-arrow-left"></i> Cancelar y Volver
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa-solid fa-save"></i> Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>