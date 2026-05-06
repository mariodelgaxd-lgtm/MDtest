<?php
session_start();
require_once "conexion.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

$sql_ranking = "SELECT u.id_usuario, u.nombre, u.racha, u.tests_completados, 
                n.nombre_nivel, COUNT(lu.id_logro) as total_logros
                FROM usuarios u
                LEFT JOIN niveles n ON u.id_nivel = n.id_nivel
                LEFT JOIN logros_usuario lu ON u.id_usuario = lu.id_usuario
                GROUP BY u.id_usuario
                ORDER BY u.racha DESC, u.tests_completados DESC
                LIMIT 20";
$ranking = $conn->query($sql_ranking)->fetch_all(MYSQLI_ASSOC);

$id_usuario = $_SESSION['id_usuario'];
$sql_pos = "SELECT COUNT(*) + 1 as pos FROM usuarios WHERE racha > (SELECT racha FROM usuarios WHERE id_usuario = ?)";
$stmt = $conn->prepare($sql_pos);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$posicion = $stmt->get_result()->fetch_assoc()['pos'];
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ranking - MDTest</title>
    
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
                    <li class="nav-item"><a class="nav-link-custom" href="repaso_rapido.php">Repaso</a></li>
                    <li class="nav-item"><a class="nav-link-custom" href="perfil.php">Progreso</a></li>
                    <li class="nav-item"><a class="nav-link-custom active" href="leaderboard.php">Ranking</a></li>
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
            
            <!-- Header -->
            <div class="text-center mb-5 reveal">
                <i class="fa-solid fa-trophy fa-4x mb-3" style="color:var(--warning);"></i>
                <h1 class="display-5 fw-bold">Ranking de <span class="gradient-text">Conductores</span></h1>
                <p class="text-secondary">Los mejores estudiantes de la autoescuela</p>
                <div class="badge-custom badge-primary mt-3">Tu posición: #<?php echo $posicion; ?></div>
            </div>

            <!-- Podium -->
            <?php if (count($ranking) >= 3): ?>
            <div class="row g-4 mb-5 justify-content-center">
                <!-- 2nd -->
                <div class="col-md-4 col-lg-3 reveal delay-100">
                    <div class="glass-card p-4 text-center h-100">
                        <div class="rank-badge rank-2 mx-auto mb-3">2</div>
                        <div class="user-avatar user-avatar-lg mx-auto mb-3"><?php echo strtoupper(mb_substr($ranking[1]['nombre'], 0, 1)); ?></div>
                        <h4 class="fw-bold"><?php echo htmlspecialchars($ranking[1]['nombre']); ?></h4>
                        <p class="text-secondary small"><?php echo $ranking[1]['nombre_nivel']; ?></p>
                        <div class="badge-custom badge-warning"><i class="fa-solid fa-fire"></i> <?php echo $ranking[1]['racha']; ?> días</div>
                    </div>
                </div>
                
                <!-- 1st -->
                <div class="col-md-4 col-lg-3 reveal">
                    <div class="glass-card p-4 text-center h-100" style="border:2px solid rgba(255,215,0,0.3);">
                        <div class="rank-badge rank-1 mx-auto mb-3" style="width:48px;height:48px;font-size:1.5rem;">1</div>
                        <div class="user-avatar user-avatar-lg mx-auto mb-3 animate-float"><?php echo strtoupper(mb_substr($ranking[0]['nombre'], 0, 1)); ?></div>
                        <h3 class="fw-bold"><?php echo htmlspecialchars($ranking[0]['nombre']); ?></h3>
                        <p class="text-secondary small"><?php echo $ranking[0]['nombre_nivel']; ?></p>
                        <div class="badge-custom badge-warning"><i class="fa-solid fa-fire"></i> <?php echo $ranking[0]['racha']; ?> días</div>
                        <p class="mt-2 small text-secondary"><?php echo $ranking[0]['tests_completados']; ?> tests</p>
                    </div>
                </div>
                
                <!-- 3rd -->
                <div class="col-md-4 col-lg-3 reveal delay-200">
                    <div class="glass-card p-4 text-center h-100">
                        <div class="rank-badge rank-3 mx-auto mb-3">3</div>
                        <div class="user-avatar user-avatar-lg mx-auto mb-3"><?php echo strtoupper(mb_substr($ranking[2]['nombre'], 0, 1)); ?></div>
                        <h4 class="fw-bold"><?php echo htmlspecialchars($ranking[2]['nombre']); ?></h4>
                        <p class="text-secondary small"><?php echo $ranking[2]['nombre_nivel']; ?></p>
                        <div class="badge-custom badge-warning"><i class="fa-solid fa-fire"></i> <?php echo $ranking[2]['racha']; ?> días</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Table -->
            <div class="glass-card overflow-hidden reveal">
                <div class="table-responsive">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>Pos</th>
                                <th>Conductor</th>
                                <th>Nivel</th>
                                <th>Racha</th>
                                <th>Tests</th>
                                <th>Logros</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ranking as $index => $user): 
                                $es_actual = ($user['id_usuario'] == $id_usuario);
                            ?>
                            <tr style="<?php echo $es_actual ? 'background:rgba(99,102,241,0.08);' : ''; ?>">
                                <td>
                                    <span class="fw-bold <?php echo $index < 3 ? 'text-warning' : 'text-secondary'; ?>">#<?php echo $index + 1; ?></span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="user-avatar" style="width:32px;height:32px;font-size:0.8rem;"><?php echo strtoupper(mb_substr($user['nombre'], 0, 1)); ?></div>
                                        <span class="fw-bold"><?php echo htmlspecialchars($user['nombre']); ?></span>
                                        <?php if ($es_actual): ?>
                                            <span class="badge-custom badge-primary">Tú</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge-custom badge-primary"><?php echo $user['nombre_nivel']; ?></span>
                                </td>
                                <td><span class="text-warning"><i class="fa-solid fa-fire"></i> <?php echo $user['racha']; ?></span></td>
                                <td class="fw-bold"><?php echo $user['tests_completados']; ?></td>
                                <td>
                                    <span class="badge-custom badge-success"><i class="fa-solid fa-medal"></i> <?php echo $user['total_logros']; ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
<?php $conn->close(); ?>
</body>
</html>
