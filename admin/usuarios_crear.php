<?php
/**
 * Crear Usuario - Inmuebles del Sinú
 * Formulario para agregar nuevos usuarios al sistema
 */

define('APP_ACCESS', true);

require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

initSession();
requireRole('admin');

$user = getCurrentUser();

// Get list of clients for cliente role linking
try {
    $db = new Database();
    $pdo = $db->getConnection();

    $stmt = $pdo->query("
        SELECT c.id_cliente, c.nombre, c.apellido, c.correo
        FROM cliente c
        LEFT JOIN usuarios u ON c.id_cliente = u.id_cliente
        WHERE u.id_usuario IS NULL
        ORDER BY c.nombre, c.apellido
    ");
    $clientesSinUsuario = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error fetching clients: " . $e->getMessage());
    $clientesSinUsuario = [];
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        redirectWithMessage('usuarios.php', 'Token de seguridad inválido', 'error');
    }

    // Collect and validate input
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $nombreCompleto = trim($_POST['nombre_completo'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    $rol = $_POST['rol'] ?? '';
    $idCliente = $_POST['id_cliente'] ?? null;
    $activo = isset($_POST['activo']) ? 1 : 0;

    $errors = [];

    // Validation
    if (empty($username)) {
        $errors[] = "El nombre de usuario es obligatorio";
    } elseif (strlen($username) < 3) {
        $errors[] = "El nombre de usuario debe tener al menos 3 caracteres";
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Debes proporcionar un email válido";
    }

    if (empty($nombreCompleto)) {
        $errors[] = "El nombre completo es obligatorio";
    }

    if (empty($password)) {
        $errors[] = "La contraseña es obligatoria";
    } elseif (strlen($password) < 6) {
        $errors[] = "La contraseña debe tener al menos 6 caracteres";
    }

    if ($password !== $passwordConfirm) {
        $errors[] = "Las contraseñas no coinciden";
    }

    if (!in_array($rol, ['admin', 'cliente'])) {
        $errors[] = "Debes seleccionar un rol válido";
    }

    if ($rol === 'cliente' && $idCliente) {
        $idCliente = (int)$idCliente;
    } else {
        $idCliente = null;
    }

    // Check if username already exists
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $errors[] = "El nombre de usuario ya está en uso";
            }
        } catch (PDOException $e) {
            $errors[] = "Error al verificar el usuario";
            error_log("Username check error: " . $e->getMessage());
        }
    }

    // Check if email already exists
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = "El email ya está registrado";
            }
        } catch (PDOException $e) {
            $errors[] = "Error al verificar el email";
            error_log("Email check error: " . $e->getMessage());
        }
    }

    // If no errors, create user
    if (empty($errors)) {
        try {
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);

            $sql = "INSERT INTO usuarios (username, password_hash, email, nombre_completo, rol, id_cliente, activo)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $username,
                $passwordHash,
                $email,
                $nombreCompleto,
                $rol,
                $idCliente,
                $activo
            ]);

            redirectWithMessage('usuarios.php', 'Usuario creado exitosamente', 'success');

        } catch (PDOException $e) {
            $errors[] = "Error al crear el usuario: " . $e->getMessage();
            error_log("User creation error: " . $e->getMessage());
        }
    }

    // Store errors in session to display
    if (!empty($errors)) {
        $_SESSION['flash_message'] = implode('<br>', $errors);
        $_SESSION['flash_type'] = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Usuario - <?= APP_NAME ?></title>
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
            background: #f5f6fa;
        }

        /* Header */
        .admin-header {
            background: #0a1931;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .admin-header h1 {
            font-size: 24px;
            font-weight: 700;
        }

        .btn-back {
            background: #666;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.3s;
        }

        .btn-back:hover {
            background: #555;
        }

        /* Container */
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px;
        }

        /* Form Card */
        .form-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .form-card h2 {
            color: #0a1931;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .form-card .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }

        /* Form */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #0a1931;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group label .required {
            color: #e94545;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: 'Oswald', sans-serif;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #00de55;
        }

        .form-group small {
            display: block;
            color: #666;
            margin-top: 5px;
            font-size: 12px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
            cursor: pointer;
        }

        .checkbox-group label {
            margin-bottom: 0;
            cursor: pointer;
        }

        /* Password Strength */
        .password-strength {
            height: 4px;
            background: #ddd;
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: width 0.3s, background 0.3s;
        }

        .strength-weak { width: 33%; background: #e94545; }
        .strength-medium { width: 66%; background: #ff9800; }
        .strength-strong { width: 100%; background: #00de55; }

        /* Form Actions */
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-family: 'Oswald', sans-serif;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: #00de55;
            color: white;
        }

        .btn-primary:hover {
            background: #00aa41;
        }

        .btn-secondary {
            background: #666;
            color: white;
        }

        .btn-secondary:hover {
            background: #555;
        }

        /* Messages */
        .message {
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .message-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Section Headers */
        .section-header {
            color: #0a1931;
            font-size: 18px;
            font-weight: 600;
            margin: 25px 0 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #00de55;
        }

        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .info-box p {
            color: #0d47a1;
            font-size: 14px;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1>🔐 Crear Nuevo Usuario</h1>
        <div class="user-info">
            <a href="usuarios.php" class="btn-back">← Volver a Usuarios</a>
        </div>
    </div>

    <div class="container">
        <?php displayFlashMessage(); ?>

        <div class="form-card">
            <h2>Nuevo Usuario</h2>
            <p class="subtitle">Completa los datos para crear un nuevo usuario en el sistema</p>

            <form method="POST" action="" id="createUserForm">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                <div class="section-header">Información de Acceso</div>

                <div class="form-group">
                    <label for="username">Nombre de Usuario <span class="required">*</span></label>
                    <input type="text"
                           id="username"
                           name="username"
                           required
                           minlength="3"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           placeholder="Ej: jose.perez">
                    <small>Mínimo 3 caracteres, sin espacios</small>
                </div>

                <div class="form-group">
                    <label for="email">Email <span class="required">*</span></label>
                    <input type="email"
                           id="email"
                           name="email"
                           required
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           placeholder="usuario@ejemplo.com">
                </div>

                <div class="form-group">
                    <label for="password">Contraseña <span class="required">*</span></label>
                    <input type="password"
                           id="password"
                           name="password"
                           required
                           minlength="6"
                           placeholder="Mínimo 6 caracteres">
                    <div class="password-strength">
                        <div class="password-strength-bar" id="strengthBar"></div>
                    </div>
                    <small id="strengthText">Ingresa una contraseña</small>
                </div>

                <div class="form-group">
                    <label for="password_confirm">Confirmar Contraseña <span class="required">*</span></label>
                    <input type="password"
                           id="password_confirm"
                           name="password_confirm"
                           required
                           minlength="6"
                           placeholder="Repite la contraseña">
                </div>

                <div class="section-header">Información Personal</div>

                <div class="form-group">
                    <label for="nombre_completo">Nombre Completo <span class="required">*</span></label>
                    <input type="text"
                           id="nombre_completo"
                           name="nombre_completo"
                           required
                           value="<?= htmlspecialchars($_POST['nombre_completo'] ?? '') ?>"
                           placeholder="Ej: José Pérez González">
                </div>

                <div class="section-header">Permisos y Roles</div>

                <div class="form-group">
                    <label for="rol">Rol del Usuario <span class="required">*</span></label>
                    <select id="rol" name="rol" required>
                        <option value="">-- Selecciona un rol --</option>
                        <option value="admin" <?= ($_POST['rol'] ?? '') === 'admin' ? 'selected' : '' ?>>
                            Administrador (Acceso completo)
                        </option>
                        <option value="cliente" <?= ($_POST['rol'] ?? '') === 'cliente' ? 'selected' : '' ?>>
                            Cliente (Solo ver propiedades)
                        </option>
                    </select>
                </div>

                <div class="form-group" id="clienteLinkGroup" style="display: none;">
                    <label for="id_cliente">Vincular con Cliente Existente</label>
                    <select id="id_cliente" name="id_cliente">
                        <option value="">-- No vincular (usar trigger automático) --</option>
                        <?php foreach ($clientesSinUsuario as $cliente): ?>
                            <option value="<?= $cliente['id_cliente'] ?>">
                                <?= htmlspecialchars($cliente['nombre'] . ' ' . $cliente['apellido']) ?>
                                (<?= htmlspecialchars($cliente['correo']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small>Deja en blanco para vinculación automática por email (trigger)</small>
                </div>

                <?php if (count($clientesSinUsuario) === 0): ?>
                    <div id="noClientesInfo" style="display: none;">
                        <div class="info-box">
                            <p>ℹ️ No hay clientes disponibles para vincular (todos ya tienen usuario). El sistema vinculará automáticamente por email si existe coincidencia.</p>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox"
                               id="activo"
                               name="activo"
                               <?= isset($_POST['activo']) || !isset($_POST['username']) ? 'checked' : '' ?>>
                        <label for="activo">Usuario activo (puede iniciar sesión)</label>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Crear Usuario</button>
                    <a href="usuarios.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Show/hide cliente linking based on role
        const rolSelect = document.getElementById('rol');
        const clienteLinkGroup = document.getElementById('clienteLinkGroup');
        const noClientesInfo = document.getElementById('noClientesInfo');

        rolSelect.addEventListener('change', function() {
            if (this.value === 'cliente') {
                clienteLinkGroup.style.display = 'block';
                if (noClientesInfo) {
                    noClientesInfo.style.display = 'block';
                }
            } else {
                clienteLinkGroup.style.display = 'none';
                if (noClientesInfo) {
                    noClientesInfo.style.display = 'none';
                }
            }
        });

        // Trigger on page load if cliente is selected
        if (rolSelect.value === 'cliente') {
            clienteLinkGroup.style.display = 'block';
            if (noClientesInfo) {
                noClientesInfo.style.display = 'block';
            }
        }

        // Password strength indicator
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            let text = '';
            let className = '';

            if (password.length === 0) {
                text = 'Ingresa una contraseña';
            } else if (password.length < 6) {
                strength = 1;
                text = 'Muy débil - Mínimo 6 caracteres';
                className = 'strength-weak';
            } else {
                strength = 1;
                if (password.length >= 8) strength++;
                if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
                if (/[0-9]/.test(password)) strength++;
                if (/[^a-zA-Z0-9]/.test(password)) strength++;

                if (strength <= 2) {
                    text = 'Débil - Agrega mayúsculas, números o símbolos';
                    className = 'strength-weak';
                } else if (strength <= 3) {
                    text = 'Media - Considera agregar más variedad';
                    className = 'strength-medium';
                } else {
                    text = 'Fuerte - Buena contraseña';
                    className = 'strength-strong';
                }
            }

            strengthBar.className = 'password-strength-bar ' + className;
            strengthText.textContent = text;
        });

        // Password confirmation validation
        const confirmInput = document.getElementById('password_confirm');

        confirmInput.addEventListener('input', function() {
            if (this.value && this.value !== passwordInput.value) {
                this.setCustomValidity('Las contraseñas no coinciden');
            } else {
                this.setCustomValidity('');
            }
        });

        passwordInput.addEventListener('input', function() {
            if (confirmInput.value && confirmInput.value !== this.value) {
                confirmInput.setCustomValidity('Las contraseñas no coinciden');
            } else {
                confirmInput.setCustomValidity('');
            }
        });

        // Form validation before submit
        document.getElementById('createUserForm').addEventListener('submit', function(e) {
            const password = passwordInput.value;
            const confirm = confirmInput.value;

            if (password !== confirm) {
                e.preventDefault();
                alert('Las contraseñas no coinciden');
                confirmInput.focus();
                return false;
            }
        });
    </script>
</body>
</html>
