<?php
/**
 * Agents AJAX Handler - Real Estate Management System
 * Handle AJAX requests for agent operations
 * Educational PHP/MySQL Project
 */

// Prevent direct access
if (!defined('APP_ACCESS')) {
    die('Direct access not permitted');
}

// Set JSON response header
header('Content-Type: application/json');

$response = ['success' => false, 'error' => ''];

try {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'toggle_status':
            $agentId = (int)($_POST['id'] ?? 0);
            $newStatus = (int)($_POST['status'] ?? 0);

            if ($agentId <= 0) {
                throw new Exception('ID de agente inválido');
            }

            $db = new Database();
            $pdo = $db->getConnection();

            // Update agent status
            $sql = "UPDATE agente SET activo = ?, updated_at = CURRENT_TIMESTAMP WHERE id_agente = ?";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$newStatus, $agentId]);

            if ($result && $stmt->rowCount() > 0) {
                $response['success'] = true;
                $response['message'] = 'Estado del agente actualizado correctamente';

                // Log the change
                if (LOG_ENABLED) {
                    logMessage("Agent {$agentId} status changed to " . ($newStatus ? 'active' : 'inactive'), 'INFO');
                }
            } else {
                throw new Exception('No se pudo actualizar el estado del agente');
            }
            break;

        case 'delete':
            $agentId = (int)($_POST['id'] ?? 0);

            if ($agentId <= 0) {
                throw new Exception('ID de agente inválido');
            }

            $db = new Database();
            $pdo = $db->getConnection();

            // Check if agent has associated records
            $checkSql = "SELECT COUNT(*) FROM venta WHERE id_agente = ?
                        UNION ALL
                        SELECT COUNT(*) FROM contrato WHERE id_agente = ?
                        UNION ALL
                        SELECT COUNT(*) FROM arriendo WHERE id_agente = ?
                        UNION ALL
                        SELECT COUNT(*) FROM visita WHERE id_agente = ?";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([$agentId, $agentId, $agentId, $agentId]);
            $counts = $checkStmt->fetchAll(PDO::FETCH_COLUMN);

            $totalRecords = array_sum($counts);

            if ($totalRecords > 0) {
                // Don't delete, just deactivate
                $sql = "UPDATE agente SET activo = 0, updated_at = CURRENT_TIMESTAMP WHERE id_agente = ?";
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([$agentId]);

                $response['success'] = true;
                $response['message'] = 'El agente ha sido desactivado (tiene registros asociados)';
            } else {
                // Safe to delete
                $sql = "DELETE FROM agente WHERE id_agente = ?";
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([$agentId]);

                if ($result && $stmt->rowCount() > 0) {
                    $response['success'] = true;
                    $response['message'] = 'Agente eliminado correctamente';
                } else {
                    throw new Exception('No se pudo eliminar el agente');
                }
            }
            break;

        default:
            throw new Exception('Acción no válida');
    }

} catch (PDOException $e) {
    error_log("Database error in agents AJAX: " . $e->getMessage());
    $response['error'] = 'Error de base de datos. Intente nuevamente.';
} catch (Exception $e) {
    error_log("Error in agents AJAX: " . $e->getMessage());
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
?>