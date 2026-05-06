<?php
require_once "admin_session.php";
require_once "conexion.php";

$mensaje = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $texto = trim($_POST['texto']);
    $opcion_a = trim($_POST['opcion_a']);
    $opcion_b = trim($_POST['opcion_b']);
    $opcion_c = trim($_POST['opcion_c']);
    $correcta = trim($_POST['correcta']);
    $tema = trim($_POST['tema']);

    if (empty($texto) || empty($opcion_a) || empty($opcion_b) || empty($opcion_c) || empty($correcta) || empty($tema)) {
        $error = "Todos los campos son obligatorios.";
    } else {
        $sql = "INSERT INTO preguntas (texto, opcion_a, opcion_b, opcion_c, correcta, tema, fuente) VALUES (?, ?, ?, ?, ?, ?, 'Admin')";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ssssss", $texto, $opcion_a, $opcion_b, $opcion_c, $correcta, $tema);
            
            if ($stmt->execute()) {
                $mensaje = "¡Pregunta añadida con éxito a la base de datos!";
            } else {
                $error = "Error al guardar la pregunta: " . $conn->error;
            }
            $stmt->close();
        } else {
            $error = "Error al preparar la consulta: " . $conn->error;
        }
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Añadir Pregunta - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="indexadmin.php"><i class="fa-solid fa-user-shield"></i> Panel de Admin</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="indexadmin.php">Ver Usuarios</a></li>
                    <li class="nav-item"><a class="nav-link active" href="añadir_preguntas.php">Añadir Pregunta</a></li>
                </ul>
                <ul class="navbar-nav"><li class="nav-item"><a class="nav-link" href="logout.php">Cerrar Sesión (Admin)</a></li></ul>
            </div>
        </div>
    </nav>

    <main class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <h1 class="mb-4">Añadir Nueva Pregunta</h1>
                
                <?php if ($mensaje): ?>
                    <div class="alert alert-success"><?php echo $mensaje; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <form action="añadir_preguntas.php" method="post">
                            <div class="mb-3">
                                <label for="texto" class="form-label">Texto de la Pregunta:</label>
                                <textarea name="texto" id="texto" class="form-control" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="opcion_a" class="form-label">Opción A:</label>
                                <input type="text" name="opcion_a" id="opcion_a" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="opcion_b" class="form-label">Opción B:</label>
                                <input type="text" name="opcion_b" id="opcion_b" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="opcion_c" class="form-label">Opción C:</label>
                                <input type="text" name="opcion_c" id="opcion_c" class="form-control" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="correcta" class="form-label">Respuesta Correcta:</label>
                                    <select name="correcta" id="correcta" class="form-select" required>
                                        <option value="">Selecciona...</option>
                                        <option value="A">Opción A</option>
                                        <option value="B">Opción B</option>
                                        <option value="C">Opción C</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="tema" class="form-label">Tema:</label>
                                    <input type="text" name="tema" id="tema" class="form-control" placeholder="Ej: Señales de Prioridad" required>
                                </div>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fa-solid fa-plus-circle"></i> Guardar Pregunta
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