<?php
session_start();
require_once "conexion.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

$id_usuario = $_SESSION['id_usuario'];

$sql_count = "SELECT COUNT(*) as total FROM fallos_usuario WHERE id_usuario = ?";
$stmt = $conn->prepare($sql_count);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$total_fallos = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Repaso Rápido - MDTest</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom fixed-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:40px;height:40px;background:var(--accent-gradient);">
                    <i class="fa-solid fa-car-side text-white"></i>
                </div>
                <span class="fw-bold fs-4" style="color:var(--text-primary);">MDTest</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link-custom" href="index.php">Inicio</a></li>
                    <li class="nav-item"><a class="nav-link-custom" href="test.php">Tests</a></li>
                    <li class="nav-item"><a class="nav-link-custom active" href="repaso_rapido.php">Repaso</a></li>
                    <li class="nav-item"><a class="nav-link-custom" href="perfil.php">Progreso</a></li>
                    <li class="nav-item"><a class="nav-link-custom" href="leaderboard.php">Ranking</a></li>
                </ul>
                
                <div class="d-flex align-items-center gap-3">
                    <div class="theme-toggle" id="theme-toggle" title="Cambiar tema">
                        <div class="theme-toggle-slider">
                            <i class="fa-solid fa-moon"></i>
                        </div>
                    </div>
                    <?php
                    $racha_nav = 0;
                    $sql_nav = "SELECT racha FROM usuarios WHERE id_usuario = ?";
                    $stmt_nav = $conn->prepare($sql_nav);
                    $stmt_nav->bind_param("i", $_SESSION['id_usuario']);
                    $stmt_nav->execute();
                    $res_nav = $stmt_nav->get_result()->fetch_assoc();
                    if ($res_nav) $racha_nav = $res_nav['racha'];
                    $stmt_nav->close();
                    ?>
                    <?php if ($racha_nav > 0): ?>
                        <span class="streak-fire streak-fire-<?php echo min($racha_nav, 50); ?> d-none d-md-inline">
                            <i class="fa-solid fa-fire"></i> <?php echo $racha_nav; ?>
                        </span>
                    <?php endif; ?>
                    <div class="dropdown">
                        <a href="#" class="user-avatar dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" style="text-decoration:none;">
                            <?php echo strtoupper(mb_substr($_SESSION['nombre'], 0, 1)); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="perfil.php"><i class="fa-solid fa-chart-line"></i> Mi Progreso</a></li>
                            <li><a class="dropdown-item" href="editar_perfil.php"><i class="fa-solid fa-user-pen"></i> Editar Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="pt-5 mt-5">
        <div class="container py-5">
            
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    
                    <div class="text-center mb-5 reveal">
                        <i class="fa-solid fa-rotate-left fa-4x mb-3" style="color:var(--warning);"></i>
                        <h1 class="display-5 fw-bold">Repaso <span class="gradient-text">Rápido</span></h1>
                        <p class="text-secondary">Refuerza tus puntos débiles practicando solo con las preguntas que has fallado</p>
                    </div>

                    <div class="glass-card p-5 text-center reveal delay-200">
                        <?php if ($total_fallos > 0): ?>
                            
                            <div class="mb-4">
                                <div class="stat-value" style="font-size:4rem;"><?php echo $total_fallos; ?></div>
                                <div class="stat-label">preguntas pendientes de repaso</div>
                            </div>
                            
                            <div class="progress-custom mb-4 mx-auto" style="max-width:300px;">
                                <div class="progress-custom-fill" data-width="<?php echo min(100, $total_fallos * 2); ?>" style="width:0%"></div>
                            </div>
                            
                            <a href="test.php?modo=repaso" class="btn-primary-custom btn-lg">
                                <i class="fa-solid fa-play"></i> Comenzar Repaso Rápido
                            </a>
                            
                            <p class="mt-3 text-secondary small">Este modo no afecta tu racha de días</p>
                        
                        <?php else: ?>
                            
                            <div class="mb-4">
                                <i class="fa-solid fa-circle-check fa-5x" style="color:var(--success);"></i>
                            </div>
                            
                            <h3 class="fw-bold mb-3">¡Excelente!</h3>
                            
                            <p class="text-secondary mb-4">No tienes fallos pendientes de repaso. ¡Sigue así!</p>
                            
                            <a href="test.php" class="btn-primary-custom">
                                <i class="fa-solid fa-play"></i> Hacer Test Normal
                            </a>
                        
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer-custom">
        <div class="container">
            <div class="text-center">
                <p class="text-secondary mb-0">© 2025 MDTest. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/animations.js"></script>
<?php if (isset($conn)) $conn->close(); ?>
</body>
</html>
