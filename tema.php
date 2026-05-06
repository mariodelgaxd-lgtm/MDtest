<?php
session_start();
require_once "conexion.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

if (!isset($_GET['tema']) || empty($_GET['tema'])) {
    header("location: perfil.php");
    exit;
}

$nombre_tema_url = urldecode($_GET['tema']);

$sql = "SELECT nombre_tema, resumen_ia, contenido_completo FROM temario WHERE nombre_tema = ?";
$tema_encontrado = null;

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("s", $nombre_tema_url);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $tema_encontrado = $result->fetch_assoc();
    }
    $stmt->close();
}
$conn->close();

if ($tema_encontrado === null) {
    header("location: perfil.php");
    exit;
}

// Iconos por tema
$iconos_temas = [
    'Señales de Prioridad' => 'fa-signs-post',
    'Velocidad y Distancias' => 'fa-gauge-high',
    'Maniobras (Adelantamientos, Giros)' => 'fa-arrows-left-right',
    'Normas de Circulación' => 'fa-road',
    'Seguridad Vial (Cinturón, Casco)' => 'fa-shield-halved',
    'Mecánica y Mantenimiento' => 'fa-wrench',
    'Documentación y Accidentes' => 'fa-file-contract',
    'Alumbrado y Señalización' => 'fa-lightbulb',
    'Usuarios Vulnerables (Peatones, Ciclistas)' => 'fa-person-walking',
    'Factores de Riesgo (Fatiga, Sueño)' => 'fa-brain',
    'Señales de Agentes y Semáforos' => 'fa-traffic-light',
    'Señales Verticales (Peligro, Prohibición)' => 'fa-triangle-exclamation',
    'Marcas Viales (Líneas en el suelo)' => 'fa-road-barrier'
];

$icono = $iconos_temas[$tema_encontrado['nombre_tema']] ?? 'fa-book';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tema_encontrado['nombre_tema']); ?> - MDTest</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        .tema-hero {
            background: var(--accent-gradient);
            padding: 4rem 0;
            position: relative;
            overflow: hidden;
        }
        .tema-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        .tema-content-wrapper {
            position: relative;
        }
        .tema-sidebar {
            position: sticky;
            top: 100px;
        }
        .tema-nav-item {
            padding: 0.75rem 1rem;
            border-radius: var(--radius-md);
            color: var(--text-secondary);
            text-decoration: none;
            display: block;
            transition: all 0.2s ease;
            margin-bottom: 0.25rem;
        }
        .tema-nav-item:hover {
            background: var(--bg-hover);
            color: var(--accent-primary);
        }
        .tema-nav-item.active {
            background: var(--accent-gradient);
            color: white;
        }
    </style>
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
                    <a href="perfil.php" class="user-avatar"><?php echo strtoupper(mb_substr($_SESSION['nombre'], 0, 1)); ?></a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="tema-hero mt-5">
        <div class="container position-relative">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:64px;height:64px;background:rgba(255,255,255,0.2);">
                            <i class="fa-solid <?php echo $icono; ?> fa-2x text-white"></i>
                        </div>
                        <div>
                            <span class="badge" style="background:rgba(255,255,255,0.2);color:white;">Temario</span>
                        </div>
                    </div>
                    
                    <h1 class="display-4 fw-bold text-white mb-3"><?php echo htmlspecialchars($tema_encontrado['nombre_tema']); ?></h1>
                    
                    <p class="lead text-white mb-0" style="opacity:0.9;"><?php echo htmlspecialchars($tema_encontrado['resumen_ia']); ?></p>
                </div>
            </div>
        </div>
    </section>

    <!-- Content -->
    <main class="py-5">
        <div class="container">
            <div class="row">
                <!-- Sidebar Navigation -->
                <div class="col-lg-3 mb-4">
                    <div class="tema-sidebar">
                        <div class="glass-card p-3">
                            <h5 class="mb-3"><i class="fa-solid fa-list me-2"></i>Contenido</h5>
                            
                            <nav id="tema-nav"></nav>
                            
                            <hr style="border-color:var(--border-color);">
                            
                            <a href="perfil.php" class="tema-nav-item">
                                <i class="fa-solid fa-arrow-left me-2"></i>Volver al Perfil
                            </a>
                            
                            <a href="test.php" class="tema-nav-item active">
                                <i class="fa-solid fa-play me-2"></i>Hacer Test
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Main Content -->
                <div class="col-lg-9">
                    <div class="glass-card p-4 p-md-5">
                        <div class="temario-contenido">
                            <?php echo $tema_encontrado['contenido_completo']; ?>
                        </div>
                        
                        <hr class="my-5" style="border-color:var(--border-color);">
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="perfil.php" class="btn-secondary-custom">
                                <i class="fa-solid fa-arrow-left"></i> Volver
                            </a>
                            
                            <a href="test.php" class="btn-primary-custom">
                                <i class="fa-solid fa-play"></i> Practicar este Tema
                            </a>
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
        // Generar navegación automática desde los headers
        document.addEventListener('DOMContentLoaded', () => {
            const contenido = document.querySelector('.temario-contenido');
            const nav = document.getElementById('tema-nav');
            const headers = contenido.querySelectorAll('h2, h3');
            
            headers.forEach((header, index) => {
                const id = `section-${index}`;
                header.id = id;
                
                const link = document.createElement('a');
                link.href = `#${id}`;
                link.className = 'tema-nav-item';
                link.innerHTML = `<i class="fa-solid fa-chevron-right me-2" style="font-size:0.7rem;"></i>${header.textContent}`;
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    document.getElementById(id).scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
                
                nav.appendChild(link);
            });
            
            // Active state on scroll
            window.addEventListener('scroll', () => {
                const scrollPos = window.scrollY + 150;
                
                headers.forEach((header, index) => {
                    const section = document.getElementById(`section-${index}`);
                    const link = nav.children[index];
                    
                    if (section.offsetTop <= scrollPos && section.offsetTop + section.offsetHeight > scrollPos) {
                        link.classList.add('active');
                    } else {
                        link.classList.remove('active');
                    }
                });
            });
        });
    </script>
</body>
</html>
