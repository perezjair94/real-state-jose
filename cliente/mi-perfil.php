<?php
/**
 * Mi Perfil - Cliente
 * Información del usuario y cliente vinculado
 */

define('APP_ACCESS', true);

require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

initSession();
requireRole('cliente');

$user = getCurrentUser();
$success = '';
$error = '';

// Cargar información del cliente si está vinculado
$clienteInfo = null;
if ($user['id_cliente']) {
    try {
        $db = new Database();
        $pdo = $db->getConnection();

        $stmt = $pdo->prepare("SELECT * FROM cliente WHERE id_cliente = ?");
        $stmt->execute([$user['id_cliente']]);
        $clienteInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Error loading client info: " . $e->getMessage());
    }
}

// Manejar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_password'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de seguridad inválido';
    } else {
        $password_actual = $_POST['password_actual'] ?? '';
        $password_nueva = $_POST['password_nueva'] ?? '';
        $password_confirmar = $_POST['password_confirmar'] ?? '';

        if (empty($password_actual) || empty($password_nueva) || empty($password_confirmar)) {
            $error = 'Todos los campos son obligatorios';
        } elseif ($password_nueva !== $password_confirmar) {
            $error = 'Las contraseñas nuevas no coinciden';
        } elseif (strlen($password_nueva) < PASSWORD_MIN_LENGTH) {
            $error = 'La contraseña debe tener al menos ' . PASSWORD_MIN_LENGTH . ' caracteres';
        } else {
            try {
                $db = new Database();
                $pdo = $db->getConnection();

                // Verificar contraseña actual
                $stmt = $pdo->prepare("SELECT password_hash FROM usuarios WHERE id_usuario = ?");
                $stmt->execute([$user['id']]);
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!password_verify($password_actual, $usuario['password_hash'])) {
                    $error = 'La contraseña actual es incorrecta';
                } else {
                    // Actualizar contraseña
                    $nuevo_hash = password_hash($password_nueva, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE usuarios SET password_hash = ?, updated_at = NOW() WHERE id_usuario = ?");
                    $stmt->execute([$nuevo_hash, $user['id']]);

                    $success = 'Contraseña actualizada exitosamente';
                }

            } catch (PDOException $e) {
                error_log("Error updating password: " . $e->getMessage());
                $error = 'Error al actualizar la contraseña';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Oswald', sans-serif;
            background: #f5f6fa;
        }

        .header {
            background: linear-gradient(135deg, #0a1931 0%, #1e3a5f 100%);
            color: white;
            padding: 20px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 28px;
        }

        .btn-back {
            background: #00de55;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
        }

        .profile-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #0a1931 0%, #00de55 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            margin: 0 auto 20px;
            color: white;
        }

        .profile-name {
            text-align: center;
            font-size: 24px;
            font-weight: 700;
            color: #0a1931;
            margin-bottom: 5px;
        }

        .profile-role {
            text-align: center;
            color: #666;
            margin-bottom: 20px;
        }

        .profile-badge {
            background: #00de55;
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: #0a1931;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #00de55;
        }

        .info-grid {
            display: grid;
            gap: 20px;
        }

        .info-item {
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 15px;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #0a1931;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .info-value {
            color: #666;
            font-size: 16px;
        }

        .form-group {
            margin-bottom: 20px;
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
        }

        .form-group input:focus {
            outline: none;
            border-color: #00de55;
        }

        .btn-primary {
            background: #00de55;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-family: 'Oswald', sans-serif;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-primary:hover {
            background: #00aa41;
        }

        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 30px;
        }

        .stat-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #00de55;
        }

        .stat-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }

            .stats-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>👤 Mi Perfil</h1>
            <a href="dashboard.php" class="btn-back">← Volver al Dashboard</a>
        </div>
    </div>

    <div class="container">
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="profile-grid">
            <!-- Columna izquierda: Avatar y info básica -->
            <div>
                <div class="profile-card">
                    <div class="profile-avatar">
                        <?= mb_substr($user['nombre_completo'], 0, 1) ?>
                    </div>
                    <h2 class="profile-name"><?= htmlspecialchars($user['nombre_completo']) ?></h2>
                    <div class="profile-role">
                        <span class="profile-badge">Cliente</span>
                    </div>

                    <div class="info-grid" style="margin-top: 30px;">
                        <div class="info-item">
                            <div class="info-label">Usuario</div>
                            <div class="info-value"><?= htmlspecialchars($user['username']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Email</div>
                            <div class="info-value"><?= htmlspecialchars($user['email']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Rol</div>
                            <div class="info-value">Cliente (Acceso limitado)</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna derecha: Información detallada -->
            <div>
                <!-- Información del cliente vinculado -->
                <?php if ($clienteInfo): ?>
                <div class="profile-card" style="margin-bottom: 30px;">
                    <h3 class="section-title">📋 Información Personal</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Nombre Completo</div>
                            <div class="info-value">
                                <?= htmlspecialchars($clienteInfo['nombre']) ?>
                                <?= htmlspecialchars($clienteInfo['apellido']) ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Tipo de Documento</div>
                            <div class="info-value">
                                <?= DOCUMENT_TYPES[$clienteInfo['tipo_documento']] ?? $clienteInfo['tipo_documento'] ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Número de Documento</div>
                            <div class="info-value"><?= htmlspecialchars($clienteInfo['nro_documento']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Correo Electrónico</div>
                            <div class="info-value"><?= htmlspecialchars($clienteInfo['correo']) ?></div>
                        </div>
                        <?php if ($clienteInfo['direccion']): ?>
                        <div class="info-item">
                            <div class="info-label">Dirección</div>
                            <div class="info-value"><?= htmlspecialchars($clienteInfo['direccion']) ?></div>
                        </div>
                        <?php endif; ?>
                        <div class="info-item">
                            <div class="info-label">Tipo de Cliente</div>
                            <div class="info-value"><?= htmlspecialchars($clienteInfo['tipo_cliente']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Cliente desde</div>
                            <div class="info-value"><?= formatDate($clienteInfo['created_at']) ?></div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="profile-card" style="margin-bottom: 30px;">
                    <div class="alert alert-warning">
                        ⚠️ Tu cuenta de usuario no está vinculada a un perfil de cliente.
                        Contacta al administrador para vincular tu cuenta.
                    </div>
                </div>
                <?php endif; ?>

                <!-- Cambiar contraseña -->
                <div class="profile-card">
                    <h3 class="section-title">🔐 Cambiar Contraseña</h3>
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                        <div class="form-group">
                            <label for="password_actual">Contraseña Actual</label>
                            <input type="password" id="password_actual" name="password_actual" required>
                        </div>

                        <div class="form-group">
                            <label for="password_nueva">Nueva Contraseña</label>
                            <input type="password" id="password_nueva" name="password_nueva" required
                                   minlength="<?= PASSWORD_MIN_LENGTH ?>">
                            <small style="color: #666; font-size: 12px;">
                                Mínimo <?= PASSWORD_MIN_LENGTH ?> caracteres
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="password_confirmar">Confirmar Nueva Contraseña</label>
                            <input type="password" id="password_confirmar" name="password_confirmar" required
                                   minlength="<?= PASSWORD_MIN_LENGTH ?>">
                        </div>

                        <button type="submit" name="cambiar_password" class="btn-primary">
                            Actualizar Contraseña
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
