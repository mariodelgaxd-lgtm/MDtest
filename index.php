<?php
session_start();

$racha_actual = 0;
$nombre_usuario = "Invitado";
$usuario_logueado = (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true);

if ($usuario_logueado) {
    require_once "conexion.php";
    $id_usuario = $_SESSION['id_usuario'];
    $nombre_usuario = $_SESSION['nombre'];
    
    $sql_get_racha = "SELECT racha FROM usuarios WHERE id_usuario = ?";
    if ($stmt = $conn->prepare($sql_get_racha)) {
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $resultado_racha = $stmt->get_result()->fetch_assoc();
        if ($resultado_racha) {
            $racha_actual = $resultado_racha['racha'];
        }
        $stmt->close();
    }
    
    $sql_stats = "SELECT COUNT(*) as total_tests, SUM(CASE WHEN (30 - puntuacion) <= 3 THEN 1 ELSE 0 END) as aprobados FROM test WHERE id_usuario = ?";
    if ($stmt = $conn->prepare($sql_stats)) {
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stats = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
    
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MDTest - Autoescuela Inteligente</title>
    
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
                    <li class="nav-item"><a class="nav-link-custom active" href="index.php">Inicio</a></li>
                    <li class="nav-item"><a class="nav-link-custom" href="test.php">Tests</a></li>
                    <li class="nav-item"><a class="nav-link-custom" href="repaso_rapido.php">Repaso</a></li>
                    <li class="nav-item"><a class="nav-link-custom" href="perfil.php">Progreso</a></li>
                    <li class="nav-item"><a class="nav-link-custom" href="leaderboard.php">Ranking</a></li>
                </ul>
                
                <div class="d-flex align-items-center gap-3">
                    <!-- Theme Toggle -->
                    <div class="theme-toggle" id="theme-toggle" title="Cambiar tema">
                        <div class="theme-toggle-slider">
                            <i class="fa-solid fa-moon"></i>
                        </div>
                    </div>
                    
                    <?php if ($usuario_logueado): ?>
                        <?php if ($racha_actual > 0): ?>
                            <span class="streak-fire streak-fire-<?php echo min($racha_actual, 50); ?> d-none d-md-inline">
                                <i class="fa-solid fa-fire"></i> <?php echo $racha_actual; ?>
                            </span>
                        <?php endif; ?>
                        <div class="dropdown">
                            <a href="#" class="user-avatar dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" style="text-decoration:none;">
                                <?php echo strtoupper(mb_substr($nombre_usuario, 0, 1)); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="perfil.php"><i class="fa-solid fa-chart-line"></i> Mi Progreso</a></li>
                                <li><a class="dropdown-item" href="editar_perfil.php"><i class="fa-solid fa-user-pen"></i> Editar Perfil</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn-primary-custom">
                            <i class="fa-solid fa-user"></i> Entrar
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-bg"></div>
        <div id="particles" style="position:absolute;inset:0;overflow:hidden;pointer-events:none;"></div>
        
        <div class="container hero-content">
            <div class="row align-items-center min-vh-100 pt-5">
                <div class="col-lg-6">
                    <div class="animate-fade-in">
                        <div class="badge-custom badge-primary mb-4 animate-fade-in delay-200">
                            <i class="fa-solid fa-sparkles"></i> Inteligencia Artificial
                        </div>
                        
                        <h1 class="hero-title">
                            Aprende a conducir con 
                            <span class="gradient-text">MDTest</span>
                        </h1>
                        
                        <p class="hero-subtitle">
                            La plataforma inteligente que adapta los tests a tus necesidades. 
                            Aprende más rápido con repaso espaciado y gamificación.
                        </p>
                        
                        <div class="d-flex gap-3 flex-wrap animate-fade-in delay-400">
                            <a href="test.php" class="btn-primary-custom">
                                <i class="fa-solid fa-play"></i> Comenzar Test
                            </a>
                            <a href="#features" class="btn-secondary-custom">
                                <i class="fa-solid fa-circle-info"></i> Saber más
                            </a>
                        </div>
                        
                        <?php if ($usuario_logueado): ?>
                            <div class="mt-5 d-flex gap-4 animate-fade-in delay-600">
                                <div>
                                    <div class="stat-value" style="font-size:2rem;"><?php echo $racha_actual; ?></div>
                                    <div class="stat-label">Racha Actual</div>
                                </div>
                                <div class="vr" style="background:var(--border-color);"></div>
                                <div>
                                    <div class="stat-value" style="font-size:2rem;"><?php echo $stats['total_tests'] ?? 0; ?></div>
                                    <div class="stat-label">Tests Completados</div>
                                </div>
                                <div class="vr" style="background:var(--border-color);"></div>
                                <div>
                                    <div class="stat-value" style="font-size:2rem;"><?php echo $stats['aprobados'] ?? 0; ?></div>
                                    <div class="stat-label">Aprobados</div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-lg-6 d-none d-lg-block">
                    <div class="position-relative animate-fade-in-right delay-300">
                        <!-- Decorative circles -->
                        <div class="position-absolute" style="width:400px;height:400px;border-radius:50%;background:var(--accent-gradient);opacity:0.1;top:50%;left:50%;transform:translate(-50%,-50%);"></div>
                        <div class="position-absolute" style="width:300px;height:300px;border-radius:50%;border:2px dashed var(--accent-primary);opacity:0.3;top:50%;left:50%;transform:translate(-50%,-50%);animation:spin 20s linear infinite;"></div>
                        
                        <!-- Main illustration -->
                        <div class="glass-card p-5 position-relative animate-float">
                            <div class="text-center">
                                <i class="fa-solid fa-steering-wheel fa-8x gradient-text"></i>
                                <div class="mt-4">
                                    <div class="progress-custom mb-3">
                                        <div class="progress-custom-fill" style="width:75%"></div>
                                    </div>
                                    <div class="d-flex justify-content-between small">
                                        <span class="text-secondary">Progreso</span>
                                        <span class="fw-bold" style="color:var(--accent-primary);">75%</span>
                                    </div>
                                </div>
                                
                                <div class="mt-4 d-flex justify-content-center gap-3">
                                    <div class="badge-custom badge-success">
                                        <i class="fa-solid fa-check"></i> 24/30
                                    </div>
                                    <div class="badge-custom badge-warning">
                                        <i class="fa-solid fa-fire"></i> Racha 15
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5">
        <div class="container py-5">
            <div class="section-header reveal">
                <h2><span class="gradient-text">Características</span> Inteligentes</h2>
                <p>Todo lo que necesitas para aprobar tu examen de conducir</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4 reveal">
                    <div class="feature-card text-center">
                        <div class="feature-icon mx-auto">
                            <i class="fa-solid fa-brain"></i>
                        </div>
                        <h3 class="h4 mb-3">Repaso Espaciado</h3>
                        <p class="text-secondary">La IA calcula cuándo debes repasar cada pregunta para maximizar tu retención.</p>
                    </div>
                </div>
                
                <div class="col-md-4 reveal delay-200">
                    <div class="feature-card text-center">
                        <div class="feature-icon mx-auto" style="background:linear-gradient(135deg,#f59e0b,#ef4444);">
                            <i class="fa-solid fa-fire"></i>
                        </div>
                        <h3 class="h4 mb-3">Gamificación</h3>
                        <p class="text-secondary">Mantén tu racha, desbloquea logros y compite en el ranking global.</p>
                    </div>
                </div>
                
                <div class="col-md-4 reveal delay-400">
                    <div class="feature-card text-center">
                        <div class="feature-icon mx-auto" style="background:linear-gradient(135deg,#10b981,#3b82f6);">
                            <i class="fa-solid fa-chart-line"></i>
                        </div>
                        <h3 class="h4 mb-3">Análisis Detallado</h3>
                        <p class="text-secondary">Visualiza tu progreso con gráficos y descubre tus temas débiles.</p>
                    </div>
                </div>
                
                <div class="col-md-4 reveal">
                    <div class="feature-card text-center">
                        <div class="feature-icon mx-auto" style="background:linear-gradient(135deg,#ec4899,#8b5cf6);">
                            <i class="fa-solid fa-book-open"></i>
                        </div>
                        <h3 class="h4 mb-3">Temario Visual</h3>
                        <p class="text-secondary">Estudia cada tema con contenido visual y ejemplos prácticos.</p>
                    </div>
                </div>
                
                <div class="col-md-4 reveal delay-200">
                    <div class="feature-card text-center">
                        <div class="feature-icon mx-auto" style="background:linear-gradient(135deg,#06b6d4,#3b82f6);">
                            <i class="fa-solid fa-rotate-left"></i>
                        </div>
                        <h3 class="h4 mb-3">Repaso Rápido</h3>
                        <p class="text-secondary">Practica solo con tus preguntas falladas para reforzar tus puntos débiles.</p>
                    </div>
                </div>
                
                <div class="col-md-4 reveal delay-400">
                    <div class="feature-card text-center">
                        <div class="feature-icon mx-auto" style="background:linear-gradient(135deg,#f97316,#ef4444);">
                            <i class="fa-solid fa-stopwatch"></i>
                        </div>
                        <h3 class="h4 mb-3">Simulacro Final</h3>
                        <p class="text-secondary">Desbloquea el examen final cuando alcances 30 días de racha.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-5">
        <div class="container">
            <div class="glass-card p-5 text-center reveal">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <i class="fa-solid fa-rocket fa-4x gradient-text mb-4 animate-float"></i>
                        
                        <h2 class="display-5 fw-bold mb-4">¿Listo para empezar?</h2>
                        
                        <p class="lead text-secondary mb-4">
                            Únete a miles de estudiantes que ya están preparando su examen con MDTest.
                        </p>
                        
                        <div class="d-flex gap-3 justify-content-center flex-wrap">
                            <?php if ($usuario_logueado): ?>
                                <a href="test.php" class="btn-primary-custom">
                                    <i class="fa-solid fa-play"></i> Hacer un Test
                                </a>
                                <a href="perfil.php" class="btn-secondary-custom">
                                    <i class="fa-solid fa-chart-line"></i> Ver Progreso
                                </a>
                            <?php else: ?>
                                <a href="register.php" class="btn-primary-custom">
                                    <i class="fa-solid fa-user-plus"></i> Crear Cuenta
                                </a>
                                <a href="login.php" class="btn-secondary-custom">
                                    <i class="fa-solid fa-right-to-bracket"></i> Iniciar Sesión
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer-custom">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:36px;height:36px;background:var(--accent-gradient);">
                            <i class="fa-solid fa-car-side text-white" style="font-size:0.9rem;"></i>
                        </div>
                        <span class="fw-bold fs-5" style="color:var(--text-primary);">MDTest</span>
                    </div>
                    
                    <p class="text-secondary small mb-0">© 2025 MDTest. Todos los derechos reservados.</p>
                </div>
                
                <div class="col-md-6 text-md-end">
                    <div class="d-flex gap-3 justify-content-md-end">
                        <a href="#" class="text-secondary hover:text-primary transition-colors">
                            <i class="fa-brands fa-twitter fa-lg"></i>
                        </a>
                        
                        <a href="#" class="text-secondary hover:text-primary transition-colors">
                            <i class="fa-brands fa-instagram fa-lg"></i>
                        </a>
                        
                        <a href="#" class="text-secondary hover:text-primary transition-colors">
                            <i class="fa-brands fa-github fa-lg"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/animations.js"></script>
</body>
</html>
