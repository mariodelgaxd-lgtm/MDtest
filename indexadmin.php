<?php
require_once "admin_session.php";
require_once "conexion.php";

$sql = "SELECT id_usuario, nombre, email, racha FROM usuarios ORDER BY fecha_registro DESC";
$resultado = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Administrador</title>
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
                    <li class="nav-item"><a class="nav-link active" href="indexadmin.php">Ver Usuarios</a></li>
                    <li class="nav-item"><a class="nav-link" href="anadir_pregunta.php">Añadir Pregunta</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="logout.php">Cerrar Sesión (Admin)</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container mt-5">
        <h1 class="mb-4">Gestión de Usuarios</h1>
        
        <table class="table table-striped table-hover shadow-sm">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Racha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resultado->num_rows > 0): ?>
                    <?php while($usuario = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $usuario['id_usuario']; ?></td>
                            <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                            <td><i class="fa-solid fa-fire <?php echo ($usuario['racha'] > 0) ? 'racha-fuego' : ''; ?>"></i> <?php echo $usuario['racha']; ?></td>
                            <td>
                                <a href="ver_usuario.php?id=<?php echo $usuario['id_usuario']; ?>" class="btn btn-primary btn-sm" title="Ver Estadísticas">
                                    <i class="fa-solid fa-chart-line"></i>
                                </a>
                                <a href="editar_usuario.php?id=<?php echo $usuario['id_usuario']; ?>" class="btn btn-warning btn-sm" title="Editar Usuario">
                                    <i class="fa-solid fa-pencil"></i>
                                </a>
                                <?php if ($_SESSION['id_usuario'] != $usuario['id_usuario']): ?>
                                    <a href="borrar_usuario.php?id=<?php echo $usuario['id_usuario']; ?>" 
                                       class="btn btn-danger btn-sm" 
                                       title="Borrar Usuario"
                                       onclick="return confirm('¿Estás seguro de que quieres borrar a este usuario? Esta acción es irreversible.');">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center">No hay usuarios registrados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>