<?php
session_start();
require_once "conexion.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("location: perfil.php");
    exit;
}

$id_test = $_GET['id'];
$id_usuario = $_SESSION['id_usuario'];
$nombre_usuario = $_SESSION['nombre'];

$sql_test = "SELECT fecha, puntuacion, (30 - puntuacion) AS total_fallos 
             FROM test 
             WHERE id_test = ? AND id_usuario = ?";
$stmt_test = $conn->prepare($sql_test);
$stmt_test->bind_param("ii", $id_test, $id_usuario);
$stmt_test->execute();
$test_info = $stmt_test->get_result()->fetch_assoc();
$stmt_test->close();

if (!$test_info) {
    header("location: perfil.php");
    exit;
}

$sql_preguntas = "SELECT 
                        p.texto, p.opcion_a, p.opcion_b, p.opcion_c, 
                        p.correcta AS letra_correcta,
                        r.respuesta_usuario, 
                        r.correcta AS fue_correcta
                    FROM respuestas_test r
                    JOIN preguntas p ON r.id_pregunta = p.id_pregunta
                    WHERE r.id_test = ?";

$stmt_preguntas = $conn->prepare($sql_preguntas);
$stmt_preguntas->bind_param("i", $id_test);
$stmt_preguntas->execute();
$resultados_examen = $stmt_preguntas->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_preguntas->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revisión del Test #<?php echo $id_test; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .respuesta-correcta {
            background-color: #d1e7dd; 
            border: 2px solid #0f5132;
        }
        .respuesta-incorrecta {
            background-color: #f8d7da; 
            border: 2px solid #842029;
            text-decoration: line-through;
        }
        .icono-respuesta {
            font-size: 1.2rem;
            margin-left: 10px;
        }
    </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php"><i class="fa-solid fa-car-side"></i> MDTest</a>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="index.php">Inicio</a></li>
                    <li class="nav-item"><a class="nav-link" href="test.php">Hacer Test</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fa-solid fa-user-circle"></i> ¡Hola, <?php echo htmlspecialchars($_SESSION['nombre']); ?>!
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item active" href="perfil.php">Ver Progreso</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Cerrar Sesión</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                
                <a href="perfil.php" class="btn btn-outline-secondary mb-3">
                    <i class="fa-solid fa-arrow-left"></i> Volver a mi Perfil
                </a>
                
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h1 class="mb-3">Revisión del Test #<?php echo $id_test; ?></h1>
                        <p class="text-muted">Realizado el: <?php echo date("d/m/Y \a \l\a\s H:i", strtotime($test_info['fecha'])); ?> hs</p>
                        <hr>
                        <div class="row text-center">
                            <div class="col">
                                <span class="text-success fw-bold fs-4"><?php echo $test_info['puntuacion']; ?></span>
                                <br><small>Aciertos</small>
                            </div>
                            <div class="col">
                                <span class="text-danger fw-bold fs-4"><?php echo $test_info['total_fallos']; ?></span>
                                <br><small>Fallos</small>
                            </div>
                            <div class="col">
                                <?php if ($test_info['total_fallos'] <= 3): ?>
                                    <span class="badge bg-success p-3">APROBADO</span>
                                <?php else: ?>
                                    <span class="badge bg-danger p-3">SUSPENSO</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php foreach ($resultados_examen as $index => $pregunta): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <strong>Pregunta <?php echo ($index + 1); ?></strong>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title mb-3"><?php echo htmlspecialchars($pregunta['texto']); ?></h5>
                            
                            <ul class="list-group">
                                <?php
                                    $respuesta_usuario = $pregunta['respuesta_usuario'];
                                    $letra_correcta = $pregunta['letra_correcta'];
                                    
                                    $clase_a = 'list-group-item';
                                    if ($letra_correcta == 'A') {
                                        $clase_a .= ' respuesta-correcta';
                                    } elseif ($respuesta_usuario == 'A') {
                                        $clase_a .= ' respuesta-incorrecta';
                                    }
                                    
                                    $clase_b = 'list-group-item';
                                    if ($letra_correcta == 'B') {
                                        $clase_b .= ' respuesta-correcta';
                                    } elseif ($respuesta_usuario == 'B') {
                                        $clase_b .= ' respuesta-incorrecta';
                                    }
                                    
                                    $clase_c = 'list-group-item';
                                    if ($letra_correcta == 'C') {
                                        $clase_c .= ' respuesta-correcta';
                                    } elseif ($respuesta_usuario == 'C') {
                                        $clase_c .= ' respuesta-incorrecta';
                                    }
                                ?>

                                <li class="<?php echo $clase_a; ?>">
                                    A) <?php echo htmlspecialchars($pregunta['opcion_a']); ?>
                                    
                                    <?php if ($letra_correcta == 'A'): ?>
                                        <span class="float-end icono-respuesta"><i class="fa-solid fa-check text-success"></i> (Correcta)</span>
                                    <?php elseif ($respuesta_usuario == 'A'): ?>
                                        <span class="float-end icono-respuesta"><i class="fa-solid fa-xmark text-danger"></i> (Tu respuesta)</span>
                                    <?php endif; ?>
                                </li>
                                
                                <li class="<?php echo $clase_b; ?>">
                                    B) <?php echo htmlspecialchars($pregunta['opcion_b']); ?>
                                    
                                    <?php if ($letra_correcta == 'B'): ?>
                                        <span class="float-end icono-respuesta"><i class="fa-solid fa-check text-success"></i> (Correcta)</span>
                                    <?php elseif ($respuesta_usuario == 'B'): ?>
                                        <span class="float-end icono-respuesta"><i class="fa-solid fa-xmark text-danger"></i> (Tu respuesta)</span>
                                    <?php endif; ?>
                                </li>
                                
                                <li class="<?php echo $clase_c; ?>">
                                    C) <?php echo htmlspecialchars($pregunta['opcion_c']); ?>
                                    
                                    <?php if ($letra_correcta == 'C'): ?>
                                        <span class="float-end icono-respuesta"><i class="fa-solid fa-check text-success"></i> (Correcta)</span>
                                    <?php elseif ($respuesta_usuario == 'C'): ?>
                                        <span class="float-end icono-respuesta"><i class="fa-solid fa-xmark text-danger"></i> (Tu respuesta)</span>
                                    <?php endif; ?>
                                </li>
                            </ul>
                            
                            <?php if ($pregunta['fue_correcta'] == 0 && $respuesta_usuario === null): ?>
                                <div class="alert alert-warning mt-3">
                                    No respondiste a esta pregunta. La respuesta correcta era la <strong><?php echo $letra_correcta; ?></strong>.
                                </div>
                            <?php endif; ?>
                            
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <a href="perfil.php" class="btn btn-outline-secondary mb-5">
                    <i class="fa-solid fa-arrow-left"></i> Volver a mi Perfil
                </a>

            </div>
        </div>
    </main>

    <footer class="bg-dark text-white text-center p-4 mt-5">
        <div class="container">
            <p class="mb-0">&copy; 2025 Autoescuela IA. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>