<?php
session_start();

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("location: index.php");
    exit;
}

require_once "conexion.php";

$nombre = $email = $password = "";
$error = "";

function validarPassword($password) {
    if (strlen($password) < 8) {
        return "La contraseña debe tener al menos 8 caracteres.";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return "La contraseña debe tener al menos una mayúscula.";
    }
    if (!preg_match('/[a-z]/', $password)) {
        return "La contraseña debe tener al menos una minúscula.";
    }
    if (!preg_match('/[0-9]/', $password)) {
        return "La contraseña debe tener al menos un número.";
    }
    return "";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty(trim($_POST["nombre"]))) {
        $error = "Por favor, ingresa un nombre.";
    } else {
        $nombre = trim($_POST["nombre"]);
    }
    
    if (empty(trim($_POST["email"]))) {
        $error = "Por favor, ingresa un email.";
    } else {
        $email = trim($_POST["email"]);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "El email no es válido.";
        } else {
            $sql = "SELECT id_usuario FROM usuarios WHERE email = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("s", $email);
                if ($stmt->execute()) {
                    $stmt->store_result();
                    if ($stmt->num_rows == 1) {
                        $error = "Este email ya está registrado.";
                    }
                } else {
                    $error = "Ups! Algo salió mal.";
                }
                $stmt->close();
            }
        }
    }

    if (empty(trim($_POST["password"]))) {
        $error = "Por favor, ingresa una contraseña.";
    } else {
        $password = trim($_POST["password"]);
        $error_password = validarPassword($password);
        if (!empty($error_password)) {
            $error = $error_password;
        }
    }

    if ($_POST["password"] !== $_POST["password_confirm"]) {
        $error = "Las contraseñas no coinciden.";
    }

    if (empty($error)) {
        $password_hasheada = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sss", $nombre, $email, $password_hasheada);
            
            if ($stmt->execute()) {
                header("location: login.php");
            } else {
                $error = "Algo salió mal al crear la cuenta.";
            }
            $stmt->close();
        }
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - MDTest</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        .auth-bg {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-primary);
            position: relative;
            overflow: hidden;
            padding: 2rem 0;
        }
        .auth-bg::before {
            content: '';
            position: absolute;
            inset: 0;
            background: 
                radial-gradient(ellipse at 20% 50%, rgba(99, 102, 241, 0.1) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 50%, rgba(139, 92, 246, 0.08) 0%, transparent 50%);
        }
        .auth-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-xl);
            padding: 3rem;
            width: 100%;
            max-width: 420px;
            position: relative;
            z-index: 1;
        }
    </style>
</head>
<body>
    <div class="auth-bg">
        <div id="particles" style="position:absolute;inset:0;overflow:hidden;pointer-events:none;"></div>
        
        <div class="auth-card animate-scale-in">
            <div class="text-center mb-4">
                <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width:64px;height:64px;background:var(--accent-gradient);">
                    <i class="fa-solid fa-user-plus text-white fa-2x"></i>
                </div>
                
                <h2 class="fw-bold">Crear Cuenta</h2>
                <p class="text-secondary">Únete a MDTest y empieza a aprender</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-custom mb-4" style="border-color:var(--danger);">
                    <i class="fa-solid fa-circle-exclamation me-2" style="color:var(--danger);"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form action="register.php" method="post">
                <div class="mb-3">
                    <label class="form-label">Nombre</label>
                    <div class="position-relative">
                        <i class="fa-solid fa-user position-absolute" style="left:1rem;top:50%;transform:translateY(-50%);color:var(--text-muted);"></i>
                        <input type="text" name="nombre" class="form-control-custom" style="padding-left:2.5rem;" value="<?php echo htmlspecialchars($nombre); ?>" placeholder="Tu nombre" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <div class="position-relative">
                        <i class="fa-solid fa-envelope position-absolute" style="left:1rem;top:50%;transform:translateY(-50%);color:var(--text-muted);"></i>
                        <input type="email" name="email" class="form-control-custom" style="padding-left:2.5rem;" value="<?php echo htmlspecialchars($email); ?>" placeholder="tu@email.com" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Contraseña</label>
                    <div class="position-relative">
                        <i class="fa-solid fa-lock position-absolute" style="left:1rem;top:50%;transform:translateY(-50%);color:var(--text-muted);"></i>
                        <input type="password" name="password" class="form-control-custom" style="padding-left:2.5rem;" placeholder="••••••••" required>
                    </div>
                    <div class="form-text mt-2" style="color:var(--text-muted);font-size:0.8rem;">
                        <i class="fa-solid fa-info-circle me-1"></i> Mínimo 8 caracteres, 1 mayúscula, 1 minúscula y 1 número
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Confirmar Contraseña</label>
                    <div class="position-relative">
                        <i class="fa-solid fa-lock position-absolute" style="left:1rem;top:50%;transform:translateY(-50%);color:var(--text-muted);"></i>
                        <input type="password" name="password_confirm" class="form-control-custom" style="padding-left:2.5rem;" placeholder="••••••••" required>
                    </div>
                </div>
                
                <button type="submit" class="btn-primary-custom w-100 justify-content-center">
                    <i class="fa-solid fa-user-plus"></i> Crear Cuenta
                </button>
            </form>
            
            <hr class="my-4" style="border-color:var(--border-color);">
            
            <p class="text-center text-secondary mb-0">
                ¿Ya tienes cuenta? <a href="login.php" style="color:var(--accent-primary);text-decoration:none;font-weight:600;">Inicia sesión</a>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/animations.js"></script>
</body>
</html>
