<?php
/**
 * Generador de Hash de Contraseñas
 * Utilidad para crear hashes seguros para nuevos usuarios
 * IMPORTANTE: Eliminar este archivo en producción
 */

// Solo permitir en desarrollo
if (!defined('ENVIRONMENT') || ENVIRONMENT !== 'development') {
    define('ENVIRONMENT', 'development'); // Asegurarse que funcione en desarrollo
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generador de Hash de Contraseñas</title>
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

        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }

        h1 {
            color: #0a1931;
            font-size: 32px;
            margin-bottom: 10px;
        }

        .warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #0a1931;
            font-size: 14px;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-family: 'Oswald', sans-serif;
            font-size: 15px;
        }

        input:focus {
            outline: none;
            border-color: #00de55;
        }

        .btn {
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
        }

        .btn:hover {
            background: #00aa41;
        }

        .result {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin-top: 20px;
            word-break: break-all;
        }

        .result h3 {
            color: #0a1931;
            font-size: 18px;
            margin-bottom: 10px;
        }

        .hash-output {
            background: #e9ecef;
            padding: 15px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 13px;
            color: #212529;
            margin: 10px 0;
        }

        .sql-example {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
        }

        .sql-example h4 {
            color: #0c5460;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .sql-example pre {
            background: white;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
            font-size: 12px;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #0a1931;
            text-decoration: none;
            font-weight: 600;
        }

        .back-link:hover {
            color: #00de55;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Generador de Hash</h1>
        <p style="color: #666; margin-bottom: 20px;">Genera hashes seguros para contraseñas de nuevos usuarios</p>

        <div class="warning">
            <strong>⚠️ ADVERTENCIA:</strong> Este archivo es solo para desarrollo.
            Elimínalo antes de poner el sistema en producción.
        </div>

        <form method="POST" action="">
            <div class="form-group">
                <label for="password">Contraseña a Hashear</label>
                <input
                    type="text"
                    id="password"
                    name="password"
                    placeholder="Ingrese la contraseña"
                    required
                >
                <small style="color: #666; font-size: 12px;">
                    Mínimo 8 caracteres recomendado
                </small>
            </div>

            <button type="submit" class="btn">Generar Hash</button>
        </form>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['password'])) {
            $password = $_POST['password'];
            $hash = password_hash($password, PASSWORD_DEFAULT);
            ?>

            <div class="result">
                <h3>✅ Hash Generado</h3>

                <p><strong>Contraseña original:</strong></p>
                <div class="hash-output"><?= htmlspecialchars($password) ?></div>

                <p><strong>Hash (copiar esto a la base de datos):</strong></p>
                <div class="hash-output"><?= htmlspecialchars($hash) ?></div>

                <div class="sql-example">
                    <h4>📝 Ejemplo SQL para crear usuario administrador:</h4>
                    <pre>INSERT INTO usuarios (username, password_hash, email, nombre_completo, rol, activo)
VALUES (
    'nuevo_admin',
    '<?= htmlspecialchars($hash) ?>',
    'admin@example.com',
    'Nombre del Admin',
    'admin',
    TRUE
);</pre>
                </div>

                <div class="sql-example">
                    <h4>📝 Ejemplo SQL para crear usuario cliente:</h4>
                    <pre>INSERT INTO usuarios (username, password_hash, email, nombre_completo, rol, id_cliente, activo)
VALUES (
    'nuevo_cliente',
    '<?= htmlspecialchars($hash) ?>',
    'cliente@example.com',
    'Nombre del Cliente',
    'cliente',
    1,  -- ID del cliente en la tabla 'cliente'
    TRUE
);</pre>
                </div>
            </div>

        <?php } ?>

        <a href="login.php" class="back-link">← Volver al Login</a>
    </div>
</body>
</html>
