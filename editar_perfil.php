<?php
session_start();
require_once "conexion.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

$id_usuario = $_SESSION['id_usuario'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password_actual = $_POST['password_actual'] ?? '';
    $password_nueva = $_POST['password_nueva'] ?? '';
    $password_confirmar = $_POST['password_confirmar'] ?? '';

    $cambiar_password = !empty($password_nueva);

    if (empty($nombre) || empty($email)) {
        $error = "Nombre y email son obligatorios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "El formato del email no es válido.";
    } elseif ($cambiar_password && $password_nueva !== $password_confirmar) {
        $error = "Las contraseñas nuevas no coinciden.";
    } elseif ($cambiar_password && strlen($password_nueva) < 8) {
        $error = "La contraseña nueva debe tener al menos 8 caracteres.";
    } elseif ($cambiar_password && !preg_match('/[A-Z]/', $password_nueva)) {
        $error = "La contraseña debe contener al menos una mayúscula.";
    } elseif ($cambiar_password && !preg_match('/[a-z]/', $password_nueva)) {
        $error = "La contraseña debe contener al menos una minúscula.";
    } elseif ($cambiar_password && !preg_match('/[0-9]/', $password_nueva)) {
        $error = "La contraseña debe contener al menos un número.";
    } else {
        $sql_check = "SELECT id_usuario, password, email FROM usuarios WHERE id_usuario = ?";
        $stmt = $conn->prepare($sql_check);
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($cambiar_password && !password_verify($password_actual, $user['password'])) {
            $error = "La contraseña actual es incorrecta.";
        } else {
            $sql_email = "SELECT id_usuario FROM usuarios WHERE email = ? AND id_usuario != ?";
            $stmt_email = $conn->prepare($sql_email);
            $stmt_email->bind_param("si", $email, $id_usuario);
            $stmt_email->execute();
            if ($stmt_email->get_result()->num_rows > 0) {
                $error = "Este email ya está en uso por otro usuario.";
            } else {
                $stmt_email->close();

                if ($cambiar_password) {
                    $hash = password_hash($password_nueva, PASSWORD_DEFAULT);
                    $sql_update = "UPDATE usuarios SET nombre = ?, email = ?, password = ? WHERE id_usuario = ?";
                    $stmt_up = $conn->prepare($sql_update);
                    $stmt_up->bind_param("sssi", $nombre, $email, $hash, $id_usuario);
                } else {
                    $sql_update = "UPDATE usuarios SET nombre = ?, email = ? WHERE id_usuario = ?";
                    $stmt_up = $conn->prepare($sql_update);
                    $stmt_up->bind_param("ssi", $nombre, $email, $id_usuario);
                }

                if ($stmt_up->execute()) {
                    $_SESSION['nombre'] = $nombre;
                    $_SESSION['email'] = $email;
                    $success = "Perfil actualizado correctamente.";
                } else {
                    $error = "Error al actualizar el perfil.";
                }
                $stmt_up->close();
            }
        }
    }
}

$sql = "SELECT nombre, email FROM usuarios WHERE id_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil - MDTest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>

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

    <main class="pt-5 mt-5">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    
                    <div class="text-center mb-5 reveal">
                        <div class="user-avatar user-avatar-lg mx-auto mb-3"><?php echo strtoupper(mb_substr($_SESSION['nombre'], 0, 1)); ?></div>
                        <h1 class="display-5 fw-bold">Editar <span class="gradient-text">Perfil</span></h1>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger mb-4 reveal" style="background:rgba(239,68,68,0.1);border:1px solid var(--danger);color:var(--danger);">
                            <i class="fa-solid fa-circle-exclamation me-2"></i><?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success mb-4 reveal" style="background:rgba(16,185,129,0.1);border:1px solid var(--success);color:var(--success);">
                            <i class="fa-solid fa-circle-check me-2"></i><?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <div class="glass-card p-4 reveal">
                        <form method="POST" action="">
                            <div class="mb-4">
                                <label class="form-label" style="color:var(--text-secondary);font-weight:500;">Nombre</label>
                                <input type="text" name="nombre" class="form-control-custom" value="<?php echo htmlspecialchars($user_data['nombre']); ?>" required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label" style="color:var(--text-secondary);font-weight:500;">Email</label>
                                <input type="email" name="email" class="form-control-custom" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                                <small class="text-secondary">Asegúrate de que tu email es correcto.</small>
                            </div>

                            <hr style="border-color:var(--border-color);margin:2rem 0;">

                            <h5 class="mb-3" style="color:var(--text-primary);"><i class="fa-solid fa-lock me-2"></i>Cambiar Contraseña</h5>
                            <p class="small text-secondary mb-3">Deja en blanco si no quieres cambiarla.</p>

                            <div class="mb-3">
                                <label class="form-label" style="color:var(--text-secondary);font-size:0.9rem;">Contraseña Actual</label>
                                <input type="password" name="password_actual" class="form-control-custom">
                            </div>

                            <div class="mb-3">
                                <label class="form-label" style="color:var(--text-secondary);font-size:0.9rem;">Nueva Contraseña</label>
                                <input type="password" name="password_nueva" class="form-control-custom" id="password_nueva">
                                <small class="text-secondary">Mínimo 8 caracteres, 1 mayúscula, 1 minúscula, 1 número.</small>
                            </div>

                            <div class="mb-4">
                                <label class="form-label" style="color:var(--text-secondary);font-size:0.9rem;">Confirmar Nueva Contraseña</label>
                                <input type="password" name="password_confirmar" class="form-control-custom">
                            </div>

                            <div class="d-flex gap-3">
                                <button type="submit" class="btn-primary-custom w-100 justify-content-center">
                                    <i class="fa-solid fa-floppy-disk"></i> Guardar Cambios
                                </button>
                                <a href="perfil.php" class="btn-secondary-custom w-100 justify-content-center">
                                    <i class="fa-solid fa-xmark"></i> Cancelar
                                </a>
                            </div>
                        </form>
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
</body>
</html>