<?php
/**
 * AJAX Endpoints - Gestión de Usuarios
 * Operaciones asíncronas: activar/desactivar, desbloquear, eliminar
 */

define('APP_ACCESS', true);

require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Set JSON response header
header('Content-Type: application/json');

initSession();

// Check admin role
if (!hasRole('admin')) {
    echo json_encode([
        'success' => false,
        'message' => 'Acceso denegado'
    ]);
    exit;
}

// Get action from POST
$action = $_POST['action'] ?? '';
$idUsuario = isset($_POST['id']) ? (int)$_POST['id'] : null;

if (!$idUsuario) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de usuario no válido'
    ]);
    exit;
}

try {
    $db = new Database();
    $pdo = $db->getConnection();

    switch ($action) {
        case 'toggle':
            // Activate or deactivate user
            $activo = isset($_POST['activo']) ? (int)$_POST['activo'] : 0;

            // Prevent admin from deactivating themselves
            $currentUser = getCurrentUser();
            if ($idUsuario == $currentUser['id'] && $activo == 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'No puedes desactivar tu propia cuenta'
                ]);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE usuarios SET activo = ? WHERE id_usuario = ?");
            $stmt->execute([$activo, $idUsuario]);

            echo json_encode([
                'success' => true,
                'message' => $activo ? 'Usuario activado exitosamente' : 'Usuario desactivado exitosamente'
            ]);
            break;

        case 'unlock':
            // Unlock blocked user
            $stmt = $pdo->prepare("
                UPDATE usuarios
                SET intentos_login = 0,
                    bloqueado_hasta = NULL
                WHERE id_usuario = ?
            ");
            $stmt->execute([$idUsuario]);

            echo json_encode([
                'success' => true,
                'message' => 'Usuario desbloqueado exitosamente'
            ]);
            break;

        case 'delete':
            // Delete user
            // Prevent admin from deleting themselves
            $currentUser = getCurrentUser();
            if ($idUsuario == $currentUser['id']) {
                echo json_encode([
                    'success' => false,
                    'message' => 'No puedes eliminar tu propia cuenta'
                ]);
                exit;
            }

            // Check if user exists
            $stmt = $pdo->prepare("SELECT username, rol FROM usuarios WHERE id_usuario = ?");
            $stmt->execute([$idUsuario]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$usuario) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ]);
                exit;
            }

            // Delete user
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
            $stmt->execute([$idUsuario]);

            echo json_encode([
                'success' => true,
                'message' => "Usuario '{$usuario['username']}' eliminado exitosamente"
            ]);
            break;

        case 'reset_password':
            // Reset password (optional feature)
            $newPassword = $_POST['new_password'] ?? '';

            if (empty($newPassword) || strlen($newPassword) < 6) {
                echo json_encode([
                    'success' => false,
                    'message' => 'La contraseña debe tener al menos 6 caracteres'
                ]);
                exit;
            }

            $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);

            $stmt = $pdo->prepare("UPDATE usuarios SET password_hash = ? WHERE id_usuario = ?");
            $stmt->execute([$passwordHash, $idUsuario]);

            echo json_encode([
                'success' => true,
                'message' => 'Contraseña actualizada exitosamente'
            ]);
            break;

        default:
            echo json_encode([
                'success' => false,
                'message' => 'Acción no válida'
            ]);
            break;
    }

} catch (PDOException $e) {
    error_log("AJAX Error: " . $e->getMessage());

    // Check for foreign key constraint errors
    if ($e->getCode() == '23000') {
        echo json_encode([
            'success' => false,
            'message' => 'No se puede eliminar el usuario porque tiene registros asociados en el sistema'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al procesar la solicitud'
        ]);
    }
}
