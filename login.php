<?php
session_start();

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("location: index.php");
    exit;
}

require_once "conexion.php";

$email = $password = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty(trim($_POST["email"]))) {
        $error = "Ingresa tu email.";
    } else {
        $email = trim($_POST["email"]);
    }
    
    if (empty(trim($_POST["password"]))) {
        $error = "Ingresa tu contraseña.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    if (empty($error)) {
        $sql = "SELECT id_usuario, nombre, email, password FROM usuarios WHERE email = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $email);
            
            if ($stmt->execute()) {
                $stmt->store_result();
                
                if ($stmt->num_rows == 1) {
                    $stmt->bind_result($id, $nombre, $email_db, $hashed_password);
                    
                    if ($stmt->fetch()) {
                        if (password_verify($password, $hashed_password)) {
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id_usuario"] = $id;
                            $_SESSION["nombre"] = $nombre;
                            $_SESSION["email"] = $email_db;
                            
                            header("location: index.php");
                            exit;
                        } else {
                            $error = "La contraseña no es correcta.";
                        }
                    }
                } else {
                    $error = "No se encontró ninguna cuenta con ese email.";
                }
            } else {
                $error = "Ups! Algo salió mal.";
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
    <title>Iniciar Sesión - MDTest</title>
    
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
                    <i class="fa-solid fa-car-side text-white fa-2x"></i>
                </div>
                
                <h2 class="fw-bold">Bienvenido de vuelta</h2>
                <p class="text-secondary">Inicia sesión para continuar</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-custom mb-4" style="border-color:var(--danger);">
                    <i class="fa-solid fa-circle-exclamation me-2" style="color:var(--danger);"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form action="login.php" method="post">
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <div class="position-relative">
                        <i class="fa-solid fa-envelope position-absolute" style="left:1rem;top:50%;transform:translateY(-50%);color:var(--text-muted);"></i>
                        <input type="email" name="email" class="form-control-custom" style="padding-left:2.5rem;" value="<?php echo htmlspecialchars($email); ?>" placeholder="tu@email.com" required>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Contraseña</label>
                    <div class="position-relative">
                        <i class="fa-solid fa-lock position-absolute" style="left:1rem;top:50%;transform:translateY(-50%);color:var(--text-muted);"></i>
                        <input type="password" name="password" class="form-control-custom" style="padding-left:2.5rem;" placeholder="••••••••" required>
                    </div>
                </div>
                
                <button type="submit" class="btn-primary-custom w-100 justify-content-center">
                    <i class="fa-solid fa-right-to-bracket"></i> Iniciar Sesión
                </button>
            </form>
            
            <hr class="my-4" style="border-color:var(--border-color);">
            
            <p class="text-center text-secondary mb-0">
                ¿No tienes cuenta? <a href="register.php" style="color:var(--accent-primary);text-decoration:none;font-weight:600;">Regístrate</a>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/animations.js"></script>
</body>
</html>
