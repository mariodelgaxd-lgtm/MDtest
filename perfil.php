<?php
session_start();
require_once "conexion.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

$id_usuario = $_SESSION['id_usuario'];
$nombre_usuario = $_SESSION['nombre'];

// Datos del usuario
$sql_user = "SELECT u.*, n.nombre_nivel, n.descripcion as nivel_desc 
             FROM usuarios u 
             LEFT JOIN niveles n ON u.id_nivel = n.id_nivel 
             WHERE u.id_usuario = ?";
$stmt = $conn->prepare($sql_user);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Stats
$sql_stats = "SELECT 
                COUNT(*) as total_tests,
                SUM(CASE WHEN (30 - puntuacion) <= 3 THEN 1 ELSE 0 END) as tests_aprobados,
                SUM(CASE WHEN (30 - puntuacion) > 3 THEN 1 ELSE 0 END) as tests_suspensos,
                AVG(puntuacion) as media_puntuacion
             FROM test 
             WHERE id_usuario = ?";
$stmt = $conn->prepare($sql_stats);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Evolución
$sql_evolucion = "SELECT fecha, puntuacion, (30 - puntuacion) as fallos 
                  FROM test WHERE id_usuario = ? ORDER BY fecha DESC LIMIT 10";
$stmt = $conn->prepare($sql_evolucion);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$evolucion = array_reverse($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
$stmt->close();

// Temas fallados
$sql_temas = "SELECT t.nombre_tema, COUNT(fu.id_fallo) as total_fallos
              FROM fallos_usuario fu 
              JOIN preguntas p ON fu.id_pregunta = p.id_pregunta 
              LEFT JOIN temario t ON p.id_tema = t.id_tema
              WHERE fu.id_usuario = ? GROUP BY p.id_tema ORDER BY total_fallos DESC LIMIT 5";
$stmt = $conn->prepare($sql_temas);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$temas_fallados = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Logros
$sql_logros = "SELECT l.*, lu.fecha_conseguido 
               FROM logros l 
               JOIN logros_usuario lu ON l.id_logro = lu.id_logro 
               WHERE lu.id_usuario = ? ORDER BY lu.fecha_conseguido DESC";
$stmt = $conn->prepare($sql_logros);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$logros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();

// Preparar datos para charts
$labels_evolucion = [];
$data_aciertos = [];
$data_fallos = [];
foreach ($evolucion as $test) {
    $labels_evolucion[] = date('d/m', strtotime($test['fecha']));
    $data_aciertos[] = (int)$test['puntuacion'];
    $data_fallos[] = (int)$test['fallos'];
}

$labels_temas = [];
$data_temas = [];
foreach ($temas_fallados as $tema) {
    $labels_temas[] = $tema['nombre_tema'];
    $data_temas[] = (int)$tema['total_fallos'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Progreso - MDTest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <li class="nav-item"><a class="nav-link-custom" href="repaso_rapido.php">Repaso</a></li>
                    <li class="nav-item"><a class="nav-link-custom active" href="perfil.php">Progreso</a></li>
                    <li class="nav-item"><a class="nav-link-custom" href="leaderboard.php">Ranking</a></li>
                </ul>
                
                <div class="d-flex align-items-center gap-3">
                    <div class="theme-toggle" id="theme-toggle" title="Cambiar tema">
                        <div class="theme-toggle-slider">
                            <i class="fa-solid fa-moon"></i>
                        </div>
                    </div>
                    <?php if ($user_data['racha'] > 0): ?>
                        <span class="streak-fire streak-fire-<?php echo min($user_data['racha'], 50); ?> d-none d-md-inline">
                            <i class="fa-solid fa-fire"></i> <?php echo $user_data['racha']; ?>
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
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="pt-5 mt-5">
        <div class="container py-5">
            
            <!-- Header -->
            <div class="row align-items-center mb-5 reveal">
                <div class="col-md-auto">
                    <div class="user-avatar user-avatar-lg animate-scale-in"><?php echo strtoupper(mb_substr($nombre_usuario, 0, 1)); ?></div>
                </div>
                
                <div class="col">
                    <h1 class="display-5 fw-bold mb-2"><?php echo htmlspecialchars($nombre_usuario); ?></h1>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <span class="badge-custom badge-primary"><i class="fa-solid fa-trophy"></i> <?php echo $user_data['nombre_nivel']; ?></span>
                        <span class="text-secondary"><?php echo $user_data['tests_completados']; ?> tests completados</span>
                        <?php if ($user_data['racha'] > 0): ?>
                            <span class="badge-custom badge-warning"><i class="fa-solid fa-fire"></i> Racha <?php echo $user_data['racha']; ?> días</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-md-auto">
                    <div class="d-flex gap-2">
                        <a href="test.php" class="btn-primary-custom"><i class="fa-solid fa-play"></i> Nuevo Test</a>
                        <a href="repaso_rapido.php" class="btn-secondary-custom"><i class="fa-solid fa-rotate-left"></i> Repaso</a>
                    </div>
                </div>
            </div>

            <!-- Streak Progress -->
            <div class="row mb-5 reveal">
                <div class="col-12">
                    <div class="streak-progress-container">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-fire" style="color:var(--warning);font-size:1.2rem;"></i>
                                <span class="fw-bold">Progreso hacia el Simulacro Final</span>
                            </div>
                            <span class="badge-custom badge-warning">
                                <i class="fa-solid fa-fire"></i> <?php echo $user_data['racha']; ?> / 30 días
                            </span>
                        </div>
                        <div class="streak-progress-bar">
                            <div class="streak-progress-fill" style="width:<?php echo min(100, ($user_data['racha'] / 30) * 100); ?>%"></div>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <span class="streak-milestone <?php echo $user_data['racha'] >= 10 ? 'active' : ''; ?>">
                                <i class="fa-solid fa-fire<?php echo $user_data['racha'] >= 10 ? '' : '-extinguisher'; ?>"></i> 10 días
                            </span>
                            <span class="streak-milestone <?php echo $user_data['racha'] >= 20 ? 'active' : ''; ?>">
                                <i class="fa-solid fa-fire<?php echo $user_data['racha'] >= 20 ? '' : '-extinguisher'; ?>"></i> 20 días
                            </span>
                            <span class="streak-milestone <?php echo $user_data['racha'] >= 30 ? 'active' : ''; ?>">
                                <i class="fa-solid fa-crown"></i> 30 días (Simulacro)
                            </span>
                        </div>
                        <?php if ($user_data['racha'] >= 30): ?>
                            <div class="mt-3 text-center">
                                <a href="simulacro.php" class="btn-primary-custom" style="background:linear-gradient(135deg,#f59e0b,#ef4444);">
                                    <i class="fa-solid fa-stopwatch"></i> Hacer Simulacro Final
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="row g-4 mb-5">
                <div class="col-6 col-md-3 reveal">
                    <div class="stat-card">
                        <div class="stat-icon" style="background:rgba(99,102,241,0.15);color:var(--accent-primary);">
                            <i class="fa-solid fa-chart-line"></i>
                        </div>
                        <div class="stat-value"><?php echo $user_data['tests_completados']; ?></div>
                        <div class="stat-label">Tests Totales</div>
                    </div>
                </div>
                
                <div class="col-6 col-md-3 reveal delay-100">
                    <div class="stat-card">
                        <div class="stat-icon" style="background:rgba(16,185,129,0.15);color:var(--success);">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                        <div class="stat-value" style="background:linear-gradient(135deg,#10b981,#059669);-webkit-background-clip:text;background-clip:text;"><?php echo $stats['tests_aprobados'] ?? 0; ?></div>
                        <div class="stat-label">Aprobados</div>
                    </div>
                </div>
                
                <div class="col-6 col-md-3 reveal delay-200">
                    <div class="stat-card">
                        <div class="stat-icon" style="background:rgba(239,68,68,0.15);color:var(--danger);">
                            <i class="fa-solid fa-circle-xmark"></i>
                        </div>
                        <div class="stat-value" style="background:linear-gradient(135deg,#ef4444,#dc2626);-webkit-background-clip:text;background-clip:text;"><?php echo $stats['tests_suspensos'] ?? 0; ?></div>
                        <div class="stat-label">Suspensos</div>
                    </div>
                </div>
                
                <div class="col-6 col-md-3 reveal delay-300">
                    <div class="stat-card">
                        <div class="stat-icon" style="background:rgba(245,158,11,0.15);color:var(--warning);">
                            <i class="fa-solid fa-percent"></i>
                        </div>
                        <div class="stat-value" style="background:linear-gradient(135deg,#f59e0b,#d97706);-webkit-background-clip:text;background-clip:text;"><?php echo round($stats['media_puntuacion'] ?? 0); ?></div>
                        <div class="stat-label">Media Aciertos</div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="row g-4 mb-5">
                <div class="col-lg-8 reveal">
                    <div class="glass-card p-4">
                        <h3 class="h4 mb-4"><i class="fa-solid fa-chart-line me-2" style="color:var(--accent-primary);"></i>Evolución</h3>
                        <div class="chart-container">
                            <canvas id="chartEvolucion"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 reveal delay-200">
                    <div class="glass-card p-4">
                        <h3 class="h4 mb-4"><i class="fa-solid fa-chart-pie me-2" style="color:var(--accent-secondary);"></i>Fallos por Tema</h3>
                        <div class="chart-container">
                            <canvas id="chartTemas"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Logros -->
            <div class="mb-5">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="h4"><i class="fa-solid fa-medal me-2" style="color:var(--warning);"></i>Mis Logros</h3>
                    <span class="badge-custom badge-primary"><?php echo count($logros); ?> / 12</span>
                </div>
                
                <div class="row g-3">
                    <?php foreach ($logros as $logro): ?>
                    <div class="col-6 col-md-4 col-lg-3 reveal">
                        <div class="feature-card text-center">
                            <div class="feature-icon mx-auto" style="width:56px;height:56px;font-size:1.25rem;">
                                <i class="fa-solid fa-medal"></i>
                            </div>
                            <h5 class="mb-1"><?php echo htmlspecialchars($logro['nombre']); ?></h5>
                            <p class="small text-secondary mb-0"><?php echo htmlspecialchars($logro['descripcion']); ?></p>
                            <span class="badge-custom badge-success mt-2"><i class="fa-solid fa-check"></i> Conseguido</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($logros)): ?>
                    <div class="col-12 reveal">
                        <div class="glass-card p-5 text-center">
                            <i class="fa-solid fa-lock fa-3x text-secondary mb-3"></i>
                            <p class="text-secondary">Aún no has desbloqueado ningún logro. ¡Sigue practicando!</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Temas a Repasar -->
            <div class="mb-5">
                <h3 class="h4 mb-4"><i class="fa-solid fa-triangle-exclamation me-2" style="color:var(--danger);"></i>Temas a Repasar</h3>
                
                <div class="row g-3">
                    <?php foreach ($temas_fallados as $idx => $tema): 
                        $colors = ['danger', 'warning', 'info'];
                        $color = $colors[$idx] ?? 'secondary';
                        $max = $temas_fallados[0]['total_fallos'];
                        $pct = $max > 0 ? round(($tema['total_fallos'] / $max) * 100) : 0;
                    ?>
                    <div class="col-md-4 reveal delay-<?php echo $idx * 100; ?>">
                        <div class="glass-card p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h5 class="mb-0"><?php echo htmlspecialchars($tema['nombre_tema']); ?></h5>
                                <span class="badge-custom badge-<?php echo $color; ?>"><?php echo $tema['total_fallos']; ?> fallos</span>
                            </div>
                            
                            <div class="progress-custom mb-3">
                                <div class="progress-custom-fill" data-width="<?php echo $pct; ?>" style="width:0%"></div>
                            </div>
                            
                            <a href="tema.php?tema=<?php echo urlencode($tema['nombre_tema']); ?>" class="btn-secondary-custom w-100 justify-content-center">
                                <i class="fa-solid fa-book-open"></i> Estudiar
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($temas_fallados)): ?>
                    <div class="col-12">
                        <div class="glass-card p-5 text-center">
                            <i class="fa-solid fa-circle-check fa-3x text-success mb-3"></i>
                            <p class="text-secondary">¡Excelente! No tienes temas pendientes de repaso.</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Examen Oficial DGT -->
            <div class="mb-5 reveal">
                <h3 class="h4 mb-4"><i class="fa-solid fa-id-card me-2" style="color:var(--success);"></i>¿Listo para el Examen Oficial?</h3>
                
                <div class="exam-dgt-section">
                    <div class="row align-items-center mb-4">
                        <div class="col-lg-8">
                            <h4 class="fw-bold mb-2">Pasos para obtener tu carnet de conducir</h4>
                            <p class="text-secondary mb-0">Sigue estos pasos una vez apruebes el simulacro final de MDTest.</p>
                        </div>
                        <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                            <a href="https://sede.dgt.gob.es/es/permisos-de-conducir/cita-previa/" target="_blank" class="btn-primary-custom" style="background:linear-gradient(135deg,#10b981,#059669);">
                                <i class="fa-solid fa-calendar-check"></i> Pedir Cita DGT
                            </a>
                        </div>
                    </div>

                    <div class="exam-step reveal delay-100">
                        <div class="exam-step-number">1</div>
                        <div>
                            <h5 class="fw-bold mb-1">Solicitar Cita Previa</h5>
                            <p class="text-secondary mb-0">Accede a la web oficial de la DGT y solicita cita previa para el examen teórico. Elige la jefatura de tráfico más cercana a tu domicilio.</p>
                        </div>
                    </div>

                    <div class="exam-step reveal delay-200">
                        <div class="exam-step-number">2</div>
                        <div>
                            <h5 class="fw-bold mb-1">Realizar el Psicotécnico</h5>
                            <p class="text-secondary mb-0">Debes pasar un reconocimiento médico-psicotécnico en un centro autorizado. Lleva tu DNI y una foto tamaño carnet.</p>
                        </div>
                    </div>

                    <div class="exam-step reveal delay-300">
                        <div class="exam-step-number">3</div>
                        <div>
                            <h5 class="fw-bold mb-1">Pagar las Tasas del Examen</h5>
                            <p class="text-secondary mb-0">Abona la tasa correspondiente al examen teórico. Tu autoescuela suele gestionar esto, pero confirma que está pagado antes de la fecha.</p>
                        </div>
                    </div>

                    <div class="exam-step reveal delay-400">
                        <div class="exam-step-number">4</div>
                        <div>
                            <h5 class="fw-bold mb-1">Documentación Necesaria</h5>
                            <p class="text-secondary mb-0">DNI/NIE en vigor, justificante de pago de tasas, certificado médico-psicotécnico y foto tamaño carnet. Llévalo todo el día del examen.</p>
                        </div>
                    </div>

                    <div class="exam-step reveal delay-500">
                        <div class="exam-step-number">5</div>
                        <div>
                            <h5 class="fw-bold mb-1">El Día del Examen</h5>
                            <p class="text-secondary mb-0">Llega con 15 minutos de antelación. El examen consta de 30 preguntas y puedes fallar máximo 3. ¡Tienes 30 minutos, igual que en MDTest!</p>
                        </div>
                    </div>

                    <div class="mt-4 p-3" style="background:rgba(99,102,241,0.1);border-radius:var(--radius-md);border:1px solid rgba(99,102,241,0.3);">
                        <div class="d-flex align-items-center gap-3">
                            <i class="fa-solid fa-lightbulb" style="color:var(--accent-primary);font-size:1.5rem;"></i>
                            <div>
                                <p class="fw-bold mb-1" style="color:var(--accent-primary);">Consejo Pro</p>
                                <p class="text-secondary mb-0 small">Si has aprobado el simulacro final de MDTest (30 días de racha), tienes un nivel de preparación equivalente al del examen oficial. ¡Confía en ti!</p>
                            </div>
                        </div>
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

    <script>
        // Chart.js configuration
        const labelsEvolucion = <?php echo json_encode($labels_evolucion); ?>;
        const dataAciertos = <?php echo json_encode($data_aciertos); ?>;
        const dataFallos = <?php echo json_encode($data_fallos); ?>;
        const labelsTemas = <?php echo json_encode($labels_temas); ?>;
        const dataTemas = <?php echo json_encode($data_temas); ?>;

        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const textColor = isDark ? '#9ca3af' : '#475569';
        const gridColor = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';

        if (labelsEvolucion.length > 0) {
            new Chart(document.getElementById('chartEvolucion'), {
                type: 'line',
                data: {
                    labels: labelsEvolucion,
                    datasets: [{
                        label: 'Aciertos',
                        data: dataAciertos,
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                        fill: true,
                        tension: 0.4
                    }, {
                        label: 'Fallos',
                        data: dataFallos,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { labels: { color: textColor } }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 30,
                            ticks: { color: textColor },
                            grid: { color: gridColor }
                        },
                        x: {
                            ticks: { color: textColor },
                            grid: { color: gridColor }
                        }
                    }
                }
            });
        }

        if (labelsTemas.length > 0) {
            new Chart(document.getElementById('chartTemas'), {
                type: 'doughnut',
                data: {
                    labels: labelsTemas,
                    datasets: [{
                        data: dataTemas,
                        backgroundColor: ['#6366f1', '#8b5cf6', '#a78bfa', '#c4b5fd', '#ddd6fe'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { color: textColor, font: { size: 11 } }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>
