<?php
/**
 * Página de Login - Sistema de Gestión Inmobiliaria
 * Autenticación para Administradores y Clientes
 */

// Application access control
define('APP_ACCESS', true);

// Start session
session_start();

// If already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: cliente/dashboard.php');
    }
    exit;
}

// Load configuration
require_once 'config/constants.php';
require_once 'config/database.php';

// Error message
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Por favor ingrese usuario y contraseña';
    } else {
        try {
            $db = new Database();
            $pdo = $db->getConnection();

            // Get user by username
            $stmt = $pdo->prepare("
                SELECT
                    id_usuario,
                    username,
                    password_hash,
                    email,
                    nombre_completo,
                    rol,
                    id_cliente,
                    activo,
                    intentos_login,
                    bloqueado_hasta
                FROM usuarios
                WHERE username = ? OR email = ?
            ");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $error = 'Usuario o contraseña incorrectos';
            } elseif (!$user['activo']) {
                $error = 'Su cuenta está inactiva. Contacte al administrador';
            } elseif ($user['bloqueado_hasta'] && strtotime($user['bloqueado_hasta']) > time()) {
                $error = 'Cuenta bloqueada temporalmente por múltiples intentos fallidos';
            } elseif (password_verify($password, $user['password_hash'])) {
                // Login successful
                // Reset login attempts
                $stmt = $pdo->prepare("
                    UPDATE usuarios
                    SET intentos_login = 0,
                        bloqueado_hasta = NULL,
                        ultimo_acceso = NOW()
                    WHERE id_usuario = ?
                ");
                $stmt->execute([$user['id_usuario']]);

                // Set session variables
                $_SESSION['user_id'] = $user['id_usuario'];
                $_SESSION['user_role'] = $user['rol'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nombre_completo'] = $user['nombre_completo'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['id_cliente'] = $user['id_cliente'];
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = time();

                // Redirect based on role
                if ($user['rol'] === 'admin') {
                    header('Location: admin/dashboard.php');
                } else {
                    header('Location: cliente/dashboard.php');
                }
                exit;
            } else {
                // Wrong password - increment attempts
                $intentos = $user['intentos_login'] + 1;
                $bloqueado = null;

                // Block after 5 failed attempts for 15 minutes
                if ($intentos >= 5) {
                    $bloqueado = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                    $error = 'Cuenta bloqueada por 15 minutos debido a múltiples intentos fallidos';
                } else {
                    $error = 'Usuario o contraseña incorrectos';
                }

                $stmt = $pdo->prepare("
                    UPDATE usuarios
                    SET intentos_login = ?,
                        bloqueado_hasta = ?
                    WHERE id_usuario = ?
                ");
                $stmt->execute([$intentos, $bloqueado, $user['id_usuario']]);
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error = 'Error al procesar el login. Intente nuevamente';
        }
    }
}

// Check for logout message
if (isset($_GET['logout'])) {
    $success = 'Ha cerrado sesión exitosamente';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Oswald', sans-serif;
            background: linear-gradient(135deg, #0a1931 0%, #1e3a5f 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            max-width: 450px;
            width: 100%;
            overflow: hidden;
        }

        .login-header {
            background: #0a1931;
            color: white;
            padding: 30px;
            text-align: center;
        }

        .login-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .login-header .subtitle {
            font-size: 14px;
            color: #00de55;
            font-weight: 400;
        }

        .login-body {
            padding: 40px 30px;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #0a1931;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-family: 'Oswald', sans-serif;
            font-size: 15px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #00de55;
        }

        .form-group input::placeholder {
            color: #999;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: #00de55;
            color: white;
            border: none;
            border-radius: 6px;
            font-family: 'Oswald', sans-serif;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-login:hover {
            background: #00aa41;
        }

        .login-footer {
            margin-top: 25px;
            text-align: center;
            font-size: 13px;
            color: #666;
        }

        .login-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin-top: 20px;
        }

        .login-info h3 {
            font-size: 16px;
            color: #0a1931;
            margin-bottom: 10px;
        }

        .login-info ul {
            list-style: none;
            font-size: 13px;
            line-height: 1.8;
        }

        .login-info ul li {
            color: #555;
        }

        .login-info ul li strong {
            color: #00aa41;
        }

        .icon {
            font-size: 50px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="icon">🏠</div>
            <h1><?= APP_NAME ?></h1>
            <p class="subtitle">Ingrese sus credenciales para continuar</p>
        </div>

        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="username">Usuario o Email</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="Ingrese su usuario o email"
                        required
                        autofocus
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Ingrese su contraseña"
                        required
                    >
                </div>

                <button type="submit" class="btn-login">Iniciar Sesión</button>
            </form>

            <div class="login-footer">
                <p>¿Olvidó su contraseña? Contacte al administrador</p>
            </div>

            <?php if (ENVIRONMENT === 'development'): ?>
            <div class="login-info">
                <h3>🔑 Credenciales de Prueba</h3>
                <ul>
                    <li><strong>Administrador:</strong> admin / admin123</li>
                    <li><strong>Cliente:</strong> cliente1 / cliente123</li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
