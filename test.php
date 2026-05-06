<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tests - MDTest</title>
    
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
                    <li class="nav-item"><a class="nav-link-custom active" href="test.php">Tests</a></li>
                    <li class="nav-item"><a class="nav-link-custom" href="repaso_rapido.php">Repaso</a></li>
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
                    require_once "conexion.php";
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

    <!-- Main Content -->
    <main class="pt-5 mt-5">
        <div class="container py-5">
            
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    
                    <!-- Header -->
                    <div class="text-center mb-5 reveal">
                        <h1 class="display-5 fw-bold mb-3">
                            <span class="gradient-text">Tests</span> de Práctica
                        </h1>
                        <p class="text-secondary">30 preguntas adaptadas a tu nivel de conocimiento</p>
                    </div>

                    <!-- Test Container -->
                    <div id="test-container">
                        <!-- Loading -->
                        <div id="loading" class="text-center py-5">
                            <div class="spinner-border" role="status" style="width:3rem;height:3rem;color:var(--accent-primary);">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <p class="mt-3 text-secondary">Preparando tu test personalizado...</p>
                        </div>

                        <!-- Test Form -->
                        <form id="test-form" class="d-none">
                            <div id="questions-container"></div>

                            <!-- Navigation -->
                            <div class="d-flex justify-content-between align-items-center mt-4">
                                <button type="button" id="btn-prev" class="btn-secondary-custom" disabled>
                                    <i class="fa-solid fa-chevron-left"></i> Anterior
                                </button>
                                
                                <div id="progress-indicator" class="d-flex gap-2"></div>
                                
                                <button type="button" id="btn-next" class="btn-primary-custom">
                                    Siguiente <i class="fa-solid fa-chevron-right"></i>
                                </button>
                                
                                <button type="submit" id="btn-submit" class="btn-primary-custom d-none">
                                    <i class="fa-solid fa-flag-checkered"></i> Finalizar
                                </button>
                            </div>
                        </form>

                        <!-- Results -->
                        <div id="results-container" class="d-none"></div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal 30 días de racha -->
    <div class="modal fade modal-30days" id="modal30dias" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h4 class="modal-title fw-bold" style="color:var(--warning);">
                        <i class="fa-solid fa-fire me-2"></i>¡30 Días de Racha!
                    </h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter:invert(1);"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="fa-solid fa-fire fa-4x mb-3" style="color:var(--warning);"></i>
                    <h3 class="fw-bold mb-3">¡Eres imparable!</h3>
                    <p class="text-secondary">Has mantenido una racha de 30 días aprobando tests. Esto demuestra que tienes un nivel de preparación equivalente al examen oficial de la DGT.</p>
                    <div class="alert alert-custom mt-3" style="background:rgba(245,158,11,0.1);border-color:var(--warning);">
                        <i class="fa-solid fa-stopwatch me-2"></i>
                        <strong>Simulacro Final Desbloqueado</strong><br>
                        <small>30 preguntas aleatorias · 30 minutos · Sin repaso espaciado</small>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 justify-content-center">
                    <a href="simulacro.php" class="btn-primary-custom" style="background:linear-gradient(135deg,#f59e0b,#ef4444);">
                        <i class="fa-solid fa-stopwatch"></i> Hacer Simulacro
                    </a>
                    <button type="button" class="btn-secondary-custom" data-bs-dismiss="modal">Más tarde</button>
                </div>
            </div>
        </div>
    </div>

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
        // Test functionality
        let currentQuestion = 0;
        let questions = [];
        let answers = {};
        let startTime = Date.now();

        async function loadTest() {
            try {
                const response = await fetch('obtener_test.php');
                questions = await response.json();
                
                if (questions.error) {
                    document.getElementById('loading').innerHTML = `
                        <div class="alert alert-custom text-center">
                            <i class="fa-solid fa-circle-info fa-2x mb-2"></i>
                            <p>${questions.error}</p>
                        </div>
                    `;
                    return;
                }
                
                document.getElementById('loading').classList.add('d-none');
                document.getElementById('test-form').classList.remove('d-none');
                
                renderQuestions();
                updateNavigation();
                
            } catch (error) {
                document.getElementById('loading').innerHTML = `
                    <div class="alert alert-custom text-center text-danger">
                        <i class="fa-solid fa-circle-exclamation fa-2x mb-2"></i>
                        <p>Error al cargar el test. Inténtalo de nuevo.</p>
                    </div>
                `;
            }
        }

        function renderQuestions() {
            const container = document.getElementById('questions-container');
            container.innerHTML = questions.map((q, i) => `
                <div class="question-card ${i === 0 ? '' : 'd-none'}" data-question="${i}">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <span class="badge-custom badge-primary">Pregunta ${i + 1} de ${questions.length}</span>
                    </div>
                    
                    <h4 class="mb-4">${q.pregunta}</h4>
                    
                    <div class="opciones-lista">
                        ${q.opciones.map((op, j) => `
                            <label class="option-label">
                                <input type="radio" name="q${i}" value="${j}" 
                                    ${answers[i] === j ? 'checked' : ''}
                                    onchange="selectAnswer(${i}, ${j})">
                                <span class="fw-medium" style="color:var(--text-primary);">${String.fromCharCode(65 + j)}) ${op}</span>
                            </label>
                        `).join('')}
                    </div>
                </div>
            `).join('');
        }

        function selectAnswer(question, answer) {
            answers[question] = answer;
            updateNavigation();
            
            // Auto-advance after selection
            if (question < questions.length - 1) {
                setTimeout(() => {
                    document.getElementById('btn-next').click();
                }, 500);
            }
        }

        function showQuestion(index) {
            document.querySelectorAll('.question-card').forEach((card, i) => {
                card.classList.toggle('d-none', i !== index);
            });
            currentQuestion = index;
            updateNavigation();
        }

        function updateNavigation() {
            document.getElementById('btn-prev').disabled = currentQuestion === 0;
            
            const isLast = currentQuestion === questions.length - 1;
            document.getElementById('btn-next').classList.toggle('d-none', isLast);
            document.getElementById('btn-submit').classList.toggle('d-none', !isLast);
            
            // Update progress dots
            const progressContainer = document.getElementById('progress-indicator');
            progressContainer.innerHTML = questions.map((_, i) => `
                <div class="progress-dot ${i === currentQuestion ? 'active' : ''} ${answers[i] !== undefined ? 'answered' : ''}"
                    onclick="showQuestion(${i})"></div>
            `).join('');
        }

        document.getElementById('btn-prev').addEventListener('click', () => {
            if (currentQuestion > 0) showQuestion(currentQuestion - 1);
        });

        document.getElementById('btn-next').addEventListener('click', () => {
            if (currentQuestion < questions.length - 1) showQuestion(currentQuestion + 1);
        });

        document.getElementById('test-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const tiempoSegundos = Math.floor((Date.now() - startTime) / 1000);
            let correctas = 0;
            const resultados = questions.map((q, i) => {
                const userAnswer = answers[i];
                const isCorrect = userAnswer === q.respuesta_correcta_index;
                if (isCorrect) correctas++;
                
                return {
                    id_pregunta: q.id_pregunta,
                    respuesta_usuario: String.fromCharCode(65 + userAnswer),
                    correcta: isCorrect
                };
            });
            
            try {
                const response = await fetch('guardar_test.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        puntuacion: correctas,
                        resultados: resultados,
                        tiempo_segundos: tiempoSegundos
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showResults(data, correctas, questions.length - correctas);
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });

        function showResults(data, correctas, fallos) {
            const container = document.getElementById('results-container');
            const porcentaje = Math.round((correctas / questions.length) * 100);
            const aprobado = fallos <= 3;
            
            container.innerHTML = `
                <div class="glass-card p-5 text-center animate-scale-in">
                    <div class="mb-4">
                        ${aprobado 
                            ? '<i class="fa-solid fa-circle-check fa-5x" style="color:var(--success);"></i>'
                            : '<i class="fa-solid fa-circle-xmark fa-5x" style="color:var(--danger);"></i>'
                        }
                    </div>
                    
                    <h2 class="display-4 fw-bold mb-2" style="color:${aprobado ? 'var(--success)' : 'var(--danger)'}">
                        ${aprobado ? '¡APROBADO!' : 'SUSPENSO'}
                    </h2>
                    
                    <p class="lead text-secondary mb-4">${correctas} aciertos de ${questions.length} (${porcentaje}%)</p>
                    
                    <div class="row justify-content-center mb-4">
                        <div class="col-4">
                            <div class="stat-value" style="font-size:2rem;color:var(--success);">${correctas}</div>
                            <div class="stat-label">Aciertos</div>
                        </div>
                        <div class="col-4">
                            <div class="stat-value" style="font-size:2rem;color:var(--danger);">${fallos}</div>
                            <div class="stat-label">Fallos</div>
                        </div>
                        <div class="col-4">
                            <div class="stat-value" style="font-size:2rem;color:var(--accent-primary);">${data.nueva_racha}</div>
                            <div class="stat-label">Racha</div>
                        </div>
                    </div>
                    
                    ${data.alerta_30_dias ? `
                        <div class="alert mb-4" style="background:linear-gradient(135deg,rgba(245,158,11,0.15),rgba(239,68,68,0.15));border:2px solid var(--warning);border-radius:var(--radius-lg);padding:1.5rem;">
                            <i class="fa-solid fa-fire fa-2x mb-2" style="color:var(--warning);"></i>
                            <h5 class="fw-bold mb-2" style="color:var(--warning);">¡30 Días de Racha!</h5>
                            <p class="text-secondary mb-3">Has desbloqueado el Simulacro Final. ¡Demuestra que estás listo para la DGT!</p>
                            <a href="simulacro.php" class="btn-primary-custom" style="background:linear-gradient(135deg,#f59e0b,#ef4444);">
                                <i class="fa-solid fa-stopwatch"></i> Ir al Simulacro
                            </a>
                        </div>
                    ` : ''}
                    
                    ${data.logros_nuevos && data.logros_nuevos.length > 0 ? `
                        <div class="alert alert-custom mb-4">
                            <h5><i class="fa-solid fa-trophy me-2" style="color:var(--warning);"></i>Logros Desbloqueados</h5>
                            ${data.logros_nuevos.map(l => `<span class="badge-custom badge-success me-2">${l}</span>
                            `).join('')}
                        </div>
                    ` : ''}
                    
                    <div class="d-flex gap-3 justify-content-center">
                        <a href="test.php" class="btn-primary-custom">
                            <i class="fa-solid fa-rotate-right"></i> Otro Test
                        </a>
                        <a href="perfil.php" class="btn-secondary-custom">
                            <i class="fa-solid fa-chart-line"></i> Ver Progreso
                        </a>
                    </div>
                </div>
            `;
            
            document.getElementById('test-form').classList.add('d-none');
            container.classList.remove('d-none');
            
            // Mostrar modal de 30 días si aplica
            if (data.alerta_30_dias) {
                const modal = new bootstrap.Modal(document.getElementById('modal30dias'));
                modal.show();
            }
        }

        // Load test on page load
        loadTest();
    </script>
</body>
</html>
