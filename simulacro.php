<?php
session_start();
require_once "conexion.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

$id_usuario = $_SESSION['id_usuario'];
$sql_racha = "SELECT racha FROM usuarios WHERE id_usuario = ?";
$stmt_racha = $conn->prepare($sql_racha);
$stmt_racha->bind_param("i", $id_usuario);
$stmt_racha->execute();
$racha_actual = $stmt_racha->get_result()->fetch_assoc()['racha'];
$stmt_racha->close();
$conn->close();

if ($racha_actual < 30) {
    header("location: perfil.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simulacro de Examen Final - MDTest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        :root {
            --brand-bg: #0c1322;
            --brand-border: rgba(255,255,255,0.08);
            --brand-violet: #a78bfa;
        }
        body { background-color: var(--brand-bg); color: #dce2f7; font-family: 'Inter', sans-serif; }

        /* Temporizador flotante */
        #temporizador {
            position: fixed;
            top: 80px;
            right: 24px;
            z-index: 1050;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(25, 31, 47, 0.9);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(251,146,60,0.4);
            border-radius: 999px;
            padding: 0.5rem 1.2rem;
            font-size: 1.3rem;
            font-weight: 700;
            color: #fb923c;
            box-shadow: 0 4px 20px rgba(251,146,60,0.25);
            transition: all 0.4s ease;
        }
        #temporizador.urgente {
            border-color: rgba(239,68,68,0.6);
            color: #ef4444;
            box-shadow: 0 0 20px rgba(239,68,68,0.4);
            animation: pulse-timer 1s infinite;
        }
        @keyframes pulse-timer {
            0%, 100% { transform: scale(1); }
            50%       { transform: scale(1.04); }
        }

        /* Navbar */
        .nav-glass {
            background: rgba(12, 19, 34, 0.88);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--brand-border);
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg nav-glass sticky-top px-3 px-md-4">
    <div class="container-fluid">
        <a class="navbar-brand text-white fw-bold fs-5" href="index.php">
            <i class="fa-solid fa-car-side me-2" style="color:var(--brand-violet);"></i>MDTest
        </a>
        <div class="d-flex align-items-center gap-3 ms-auto">
            <span class="text-secondary small fw-semibold">Simulacro Final</span>
            <a href="perfil.php" class="btn btn-sm text-secondary" style="background:rgba(255,255,255,0.05);border:1px solid var(--brand-border);border-radius:8px;">
                <i class="fa-solid fa-xmark"></i> Salir
            </a>
        </div>
    </div>
</nav>

<!-- Temporizador flotante -->
<div id="temporizador">
    <i class="fa-solid fa-stopwatch" style="font-size:1rem;"></i>
    <span id="tiempo-display">30:00</span>
</div>

<main class="container-xl mt-5 pt-2 pb-5 mb-5">

    <!-- Cabecera -->
    <div class="text-center mb-5">
        <h1 class="display-6 fw-black" style="font-family:'Plus Jakarta Sans',sans-serif;">
            <span style="background:linear-gradient(to right,#a78bfa,#f472b6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">Simulacro de Examen Final</span>
        </h1>
        <p class="text-secondary">¡Demuestra lo que sabes! El test se enviará automáticamente cuando el tiempo llegue a 0.</p>
    </div>

    <!-- Spinner -->
    <div id="loading-spinner" class="text-center py-5 my-5">
        <div class="spinner-border text-primary" role="status" style="width:3rem;height:3rem;">
            <span class="visually-hidden">Cargando...</span>
        </div>
        <p class="mt-3 fs-5">Cargando 30 preguntas del simulacro...</p>
    </div>

    <!-- Formulario (se muestra tras cargar) -->
    <form id="test-form" class="d-none position-relative pb-5 mb-5">
        <div id="test-container"></div>
        <!-- Botones Anterior / Siguiente / Finalizar se inyectan aquí -->
    </form>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script type="module" src="script_simulacro.js?v=<?php echo time(); ?>"></script>

<!-- Temporizador (independiente del módulo) -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    let tiempoRestante = 1800;
    const display   = document.getElementById('tiempo-display');
    const timerBox  = document.getElementById('temporizador');
    const formEl    = document.getElementById('test-form');

    const intervalo = setInterval(() => {
        tiempoRestante--;
        const min = String(Math.floor(tiempoRestante / 60)).padStart(2, '0');
        const sec = String(tiempoRestante % 60).padStart(2, '0');
        display.textContent = `${min}:${sec}`;

        if (tiempoRestante <= 60) {
            timerBox.classList.add('urgente');
        }
        if (tiempoRestante <= 0) {
            clearInterval(intervalo);
            display.textContent = '¡TIEMPO!';
            alert('¡Se acabó el tiempo! El examen se corregirá automáticamente.');
            const btnSubmit = formEl.querySelector('button[type="submit"]');
            if (btnSubmit) btnSubmit.click();
        }
    }, 1000);
});
</script>
</body>
</html>