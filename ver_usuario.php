<?php
require_once "admin_session.php"; 
require_once "conexion.php"; 

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("location: indexadmin.php");
    exit;
}
$id_usuario = $_GET['id']; 

$sql_usuario = "SELECT nombre, email, racha FROM usuarios WHERE id_usuario = ?";
$stmt_usuario = $conn->prepare($sql_usuario);
$stmt_usuario->bind_param("i", $id_usuario);
$stmt_usuario->execute();
$usuario = $stmt_usuario->get_result()->fetch_assoc();
$stmt_usuario->close();

if (!$usuario) { header("location: indexadmin.php"); exit; }
$nombre_usuario = $usuario['nombre'];
$racha_actual = $usuario['racha'];

$sql_stats = "SELECT COUNT(*) as total_tests, SUM(CASE WHEN (30 - puntuacion) <= 3 THEN 1 ELSE 0 END) as tests_aprobados, SUM(CASE WHEN (30 - puntuacion) > 3 THEN 1 ELSE 0 END) as tests_suspensos FROM test WHERE id_usuario = ?";
$stmt_stats = $conn->prepare($sql_stats);
$stmt_stats->bind_param("i", $id_usuario);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();
$stmt_stats->close();

$sql_fallos_tema = "SELECT p.tema, COUNT(fu.id_fallo) as total_fallos FROM fallos_usuario fu JOIN preguntas p ON fu.id_pregunta = p.id_pregunta WHERE fu.id_usuario = ? GROUP BY p.tema ORDER BY total_fallos DESC LIMIT 3";
$stmt_fallos = $conn->prepare($sql_fallos_tema);
$stmt_fallos->bind_param("i", $id_usuario);
$stmt_fallos->execute();
$temas_fallados = $stmt_fallos->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_fallos->close();

$sql_historial = "SELECT id_test, fecha, puntuacion, (30 - puntuacion) AS total_fallos FROM test WHERE id_usuario = ? ORDER BY fecha DESC LIMIT 10";
$stmt_historial = $conn->prepare($sql_historial);
$stmt_historial->bind_param("i", $id_usuario);
$stmt_historial->execute();
$historial_tests = $stmt_historial->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_historial->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Perfil de <?php echo htmlspecialchars($nombre_usuario); ?></title>
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
                    <li class="nav-item"><a class="nav-link" href="añadir_preguntas.php">Añadir Pregunta</a></li>
                </ul>
                <ul class="navbar-nav"><li class="nav-item"><a class="nav-link" href="logout.php">Cerrar Sesión (Admin)</a></li></ul>
            </div>
        </div>
    </nav>

    <main class="container mt-5">
        <h1 class="mb-4">Perfil de Progreso: <?php echo htmlspecialchars($nombre_usuario); ?></h1>
        <p class="lead text-muted"><?php echo htmlspecialchars($usuario['email']); ?></p>
        <a href="indexadmin.php" class="btn btn-outline-secondary mb-3"><i class="fa-solid fa-arrow-left"></i> Volver a la lista</a>

        <div class="row">
             <div class="col-md-4 mb-4">
                <div class="card text-center shadow-sm h-100"><div class="card-body">
                    <i class="fa-solid fa-fire fa-4x <?php echo ($racha_actual > 0) ? 'racha-fuego' : 'text-muted'; ?>"></i>
                    <h5 class="card-title mt-3">Racha Actual</h5>
                    <p class="display-4 fw-bold"><?php echo $racha_actual; ?></p>
                </div></div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card text-center shadow-sm h-100"><div class="card-body">
                    <i class="fa-solid fa-check-double fa-4x text-success"></i>
                    <h5 class="card-title mt-3">Tests Aprobados</h5>
                    <p class="display-4 fw-bold"><?php echo $stats['tests_aprobados']; ?></p>
                </div></div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card text-center shadow-sm h-100"><div class="card-body">
                    <i class="fa-solid fa-xmark fa-4x text-danger"></i>
                    <h5 class="card-title mt-3">Tests Suspensos</h5>
                    <p class="display-4 fw-bold"><?php echo $stats['tests_suspensos']; ?></p>
                </div></div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-7" id="temas">
                <h3>Temas Más Fallados</h3>
                <div class="card">
                    <ul class="list-group list-group-flush">
                        <?php if (count($temas_fallados) > 0): ?>
                            <?php foreach ($temas_fallados as $tema): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?php echo htmlspecialchars($tema['tema']); ?>
                                    <span class="badge bg-danger rounded-pill"><?php echo $tema['total_fallos']; ?> fallos</span>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="list-group-item">Este usuario no tiene fallos registrados.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            <div class="col-md-5">
                <h3>Historial Reciente</h3>
                <div class="list-group">
                    <?php if (count($historial_tests) > 0): ?>
                        <?php foreach ($historial_tests as $test): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <h5 class="mb-1">Test #<?php echo $test['id_test']; ?></h5>
                                    <small><?php echo date("d/m/Y H:i", strtotime($test['fecha'])); ?> hs</small>
                                </div>
                                <span class="badge <?php echo ($test['total_fallos'] <= 3) ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo $test['total_fallos']; ?> fallos
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">Este usuario no ha completado ningún test.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>