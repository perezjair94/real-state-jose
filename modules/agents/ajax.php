<?php
/**
 * Agent AJAX Operations - Real Estate Management System
 * Handle AJAX requests for agent CRUD operations
 * Educational PHP/MySQL Project
 */

// Prevent direct access
if (!defined('APP_ACCESS')) {
    die('Direct access not permitted');
}

// Set JSON response header
header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'data' => null,
    'errors' => []
];

try {
    // Get request data
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];

    // Database connection
    $db = new Database();
    $pdo = $db->getConnection();

    switch ($action) {
        case 'create':
            if ($method !== 'POST') {
                throw new Exception('Método no permitido');
            }
            $response = handleCreate($pdo, $_POST);
            break;

        case 'update':
            if ($method !== 'POST') {
                throw new Exception('Método no permitido');
            }
            $response = handleUpdate($pdo, $_POST);
            break;

        case 'delete':
            if ($method !== 'POST') {
                throw new Exception('Método no permitido');
            }
            $response = handleDelete($pdo, $_POST);
            break;

        case 'get':
            $response = handleGet($pdo, $_GET);
            break;

        case 'search':
            $response = handleSearch($pdo, $_GET);
            break;

        case 'validate':
            $response = handleValidation($pdo, $_POST);
            break;

        case 'toggle_status':
            if ($method !== 'POST') {
                throw new Exception('Método no permitido');
            }
            $response = handleToggleStatus($pdo, $_POST);
            break;

        case 'export':
            $response = handleExport($pdo, $_GET);
            break;

        case 'bulk_action':
            if ($method !== 'POST') {
                throw new Exception('Método no permitido');
            }
            $response = handleBulkAction($pdo, $_POST);
            break;

        case 'statistics':
            $response = handleStatistics($pdo, $_GET);
            break;

        default:
            throw new Exception('Acción no válida');
    }

} catch (Exception $e) {
    error_log("Agent AJAX Error: " . $e->getMessage());
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
        'data' => null,
        'errors' => []
    ];
}

// Send JSON response
echo json_encode($response);
exit;

/**
 * Handle agent creation
 */
function handleCreate($pdo, $data) {
    try {
        // Validate required fields
        $required = ['nombre', 'correo', 'telefono'];
        $errors = [];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[] = "El campo {$field} es obligatorio";
            }
        }

        // Validate email format
        if (!empty($data['correo']) && !filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Formato de email inválido";
        }

        // Validate phone format
        if (!empty($data['telefono']) && !preg_match('/^[0-9\-\+\(\)\s]{7,20}$/', $data['telefono'])) {
            $errors[] = "Formato de teléfono inválido";
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $errors
            ];
        }

        // Check for duplicate email
        $stmt = $pdo->prepare("SELECT id_agente FROM agente WHERE correo = ?");
        $stmt->execute([$data['correo']]);
        if ($stmt->fetchColumn()) {
            return [
                'success' => false,
                'message' => 'Ya existe un agente con este email',
                'errors' => ['correo' => 'Email duplicado']
            ];
        }

        // Insert new agent
        $stmt = $pdo->prepare("
            INSERT INTO agente (nombre, correo, telefono, asesor, activo)
            VALUES (?, ?, ?, ?, ?)
        ");

        $result = $stmt->execute([
            trim($data['nombre']),
            trim($data['correo']),
            trim($data['telefono']),
            trim($data['asesor'] ?? ''),
            (int)($data['activo'] ?? 1)
        ]);

        if ($result) {
            $agentId = $pdo->lastInsertId();

            // Get the created agent data
            $stmt = $pdo->prepare("SELECT * FROM agente WHERE id_agente = ?");
            $stmt->execute([$agentId]);
            $agent = $stmt->fetch();

            return [
                'success' => true,
                'message' => 'Agente creado exitosamente',
                'data' => [
                    'id' => $agentId,
                    'agent' => $agent,
                    'display_id' => 'AGE' . str_pad($agentId, 3, '0', STR_PAD_LEFT)
                ]
            ];
        } else {
            throw new Exception('Error al crear el agente');
        }

    } catch (PDOException $e) {
        error_log("Database error in handleCreate: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error de base de datos al crear el agente',
            'errors' => []
        ];
    }
}

/**
 * Handle agent update
 */
function handleUpdate($pdo, $data) {
    try {
        $agentId = (int)($data['id'] ?? 0);

        if ($agentId <= 0) {
            throw new Exception('ID de agente inválido');
        }

        // Check if agent exists
        $stmt = $pdo->prepare("SELECT id_agente FROM agente WHERE id_agente = ?");
        $stmt->execute([$agentId]);
        if (!$stmt->fetchColumn()) {
            throw new Exception('Agente no encontrado');
        }

        // Validate required fields
        $required = ['nombre', 'correo', 'telefono'];
        $errors = [];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[] = "El campo {$field} es obligatorio";
            }
        }

        // Validate email format
        if (!empty($data['correo']) && !filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Formato de email inválido";
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $errors
            ];
        }

        // Check for duplicate email (excluding current agent)
        $stmt = $pdo->prepare("SELECT id_agente FROM agente WHERE correo = ? AND id_agente != ?");
        $stmt->execute([$data['correo'], $agentId]);
        if ($stmt->fetchColumn()) {
            return [
                'success' => false,
                'message' => 'Ya existe otro agente con este email',
                'errors' => ['correo' => 'Email duplicado']
            ];
        }

        // Update agent
        $stmt = $pdo->prepare("
            UPDATE agente
            SET nombre = ?, correo = ?, telefono = ?, asesor = ?, activo = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id_agente = ?
        ");

        $result = $stmt->execute([
            trim($data['nombre']),
            trim($data['correo']),
            trim($data['telefono']),
            trim($data['asesor'] ?? ''),
            (int)($data['activo'] ?? 1),
            $agentId
        ]);

        if ($result) {
            // Get updated agent data
            $stmt = $pdo->prepare("SELECT * FROM agente WHERE id_agente = ?");
            $stmt->execute([$agentId]);
            $agent = $stmt->fetch();

            return [
                'success' => true,
                'message' => 'Agente actualizado exitosamente',
                'data' => [
                    'id' => $agentId,
                    'agent' => $agent
                ]
            ];
        } else {
            throw new Exception('Error al actualizar el agente');
        }

    } catch (PDOException $e) {
        error_log("Database error in handleUpdate: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error de base de datos al actualizar el agente',
            'errors' => []
        ];
    }
}

/**
 * Handle agent deletion
 */
function handleDelete($pdo, $data) {
    try {
        $agentId = (int)($data['id'] ?? 0);

        if ($agentId <= 0) {
            throw new Exception('ID de agente inválido');
        }

        // Check if agent exists
        $stmt = $pdo->prepare("SELECT * FROM agente WHERE id_agente = ?");
        $stmt->execute([$agentId]);
        $agent = $stmt->fetch();

        if (!$agent) {
            throw new Exception('Agente no encontrado');
        }

        // Check for related records
        $dependencies = [];

        // Check sales
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM venta WHERE id_agente = ?");
        $stmt->execute([$agentId]);
        $salesCount = $stmt->fetchColumn();
        if ($salesCount > 0) {
            $dependencies[] = "{$salesCount} venta(s)";
        }

        // Check contracts
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM contrato WHERE id_agente = ?");
        $stmt->execute([$agentId]);
        $contractsCount = $stmt->fetchColumn();
        if ($contractsCount > 0) {
            $dependencies[] = "{$contractsCount} contrato(s)";
        }

        // Check rentals
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM arriendo WHERE id_agente = ?");
        $stmt->execute([$agentId]);
        $rentalsCount = $stmt->fetchColumn();
        if ($rentalsCount > 0) {
            $dependencies[] = "{$rentalsCount} arriendo(s)";
        }

        // Check visits
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM visita WHERE id_agente = ?");
        $stmt->execute([$agentId]);
        $visitsCount = $stmt->fetchColumn();
        if ($visitsCount > 0) {
            $dependencies[] = "{$visitsCount} visita(s)";
        }

        if (!empty($dependencies)) {
            // Don't delete, just deactivate
            $stmt = $pdo->prepare("UPDATE agente SET activo = 0, updated_at = CURRENT_TIMESTAMP WHERE id_agente = ?");
            $result = $stmt->execute([$agentId]);

            return [
                'success' => true,
                'message' => 'El agente ha sido desactivado debido a que tiene transacciones relacionadas: ' . implode(', ', $dependencies),
                'data' => [
                    'deactivated' => true,
                    'dependencies' => $dependencies
                ]
            ];
        }

        // Safe to delete
        $stmt = $pdo->prepare("DELETE FROM agente WHERE id_agente = ?");
        $result = $stmt->execute([$agentId]);

        if ($result) {
            return [
                'success' => true,
                'message' => 'Agente eliminado exitosamente',
                'data' => [
                    'deleted_agent' => $agent
                ]
            ];
        } else {
            throw new Exception('Error al eliminar el agente');
        }

    } catch (PDOException $e) {
        error_log("Database error in handleDelete: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error de base de datos al eliminar el agente',
            'errors' => []
        ];
    }
}

/**
 * Handle getting agent data
 */
function handleGet($pdo, $data) {
    try {
        $agentId = (int)($data['id'] ?? 0);

        if ($agentId <= 0) {
            throw new Exception('ID de agente inválido');
        }

        $stmt = $pdo->prepare("SELECT * FROM agente WHERE id_agente = ?");
        $stmt->execute([$agentId]);
        $agent = $stmt->fetch();

        if (!$agent) {
            throw new Exception('Agente no encontrado');
        }

        // Get related data if requested
        $includeRelated = $data['include_related'] ?? false;
        $relatedData = [];

        if ($includeRelated) {
            // Get sales count and commission
            $stmt = $pdo->prepare("SELECT COUNT(*) as count, COALESCE(SUM(comision), 0) as total_commission FROM venta WHERE id_agente = ?");
            $stmt->execute([$agentId]);
            $salesData = $stmt->fetch();
            $relatedData['sales_count'] = $salesData['count'];
            $relatedData['total_commission'] = $salesData['total_commission'];

            // Get contracts count
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM contrato WHERE id_agente = ?");
            $stmt->execute([$agentId]);
            $relatedData['contracts_count'] = $stmt->fetchColumn();

            // Get rentals count
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM arriendo WHERE id_agente = ?");
            $stmt->execute([$agentId]);
            $relatedData['rentals_count'] = $stmt->fetchColumn();

            // Get visits count
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM visita WHERE id_agente = ?");
            $stmt->execute([$agentId]);
            $relatedData['visits_count'] = $stmt->fetchColumn();
        }

        return [
            'success' => true,
            'message' => 'Agente encontrado',
            'data' => [
                'agent' => $agent,
                'display_id' => 'AGE' . str_pad($agent['id_agente'], 3, '0', STR_PAD_LEFT),
                'related' => $relatedData
            ]
        ];

    } catch (PDOException $e) {
        error_log("Database error in handleGet: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error de base de datos al obtener el agente',
            'errors' => []
        ];
    }
}

/**
 * Handle agent search
 */
function handleSearch($pdo, $data) {
    try {
        $searchTerm = trim($data['term'] ?? '');
        $limit = min((int)($data['limit'] ?? 10), 50);
        $activeOnly = $data['active_only'] ?? false;

        $whereConditions = [];
        $params = [];

        if (!empty($searchTerm)) {
            $whereConditions[] = "(nombre LIKE ? OR correo LIKE ? OR telefono LIKE ? OR asesor LIKE ?)";
            $searchWildcard = "%{$searchTerm}%";
            $params = array_fill(0, 4, $searchWildcard);
        }

        if ($activeOnly) {
            $whereConditions[] = "activo = 1";
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        $sql = "SELECT id_agente, nombre, correo, telefono, asesor, activo
                FROM agente
                {$whereClause}
                ORDER BY nombre
                LIMIT ?";

        $params[] = $limit;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $agents = $stmt->fetchAll();

        // Format results
        $results = array_map(function($agent) {
            return [
                'id' => $agent['id_agente'],
                'display_id' => 'AGE' . str_pad($agent['id_agente'], 3, '0', STR_PAD_LEFT),
                'name' => $agent['nombre'],
                'email' => $agent['correo'],
                'phone' => $agent['telefono'],
                'advisor' => $agent['asesor'],
                'active' => (bool)$agent['activo'],
                'label' => $agent['nombre'] . ' (' . $agent['correo'] . ')'
            ];
        }, $agents);

        return [
            'success' => true,
            'message' => 'Búsqueda completada',
            'data' => [
                'results' => $results,
                'total' => count($results),
                'search_term' => $searchTerm
            ]
        ];

    } catch (PDOException $e) {
        error_log("Database error in handleSearch: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error de base de datos en la búsqueda',
            'errors' => []
        ];
    }
}

/**
 * Handle field validation
 */
function handleValidation($pdo, $data) {
    try {
        $field = $data['field'] ?? '';
        $value = trim($data['value'] ?? '');
        $excludeId = (int)($data['exclude_id'] ?? 0);

        $response = [
            'success' => true,
            'valid' => true,
            'message' => 'Campo válido'
        ];

        switch ($field) {
            case 'correo':
                if (!empty($value)) {
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $response['valid'] = false;
                        $response['message'] = 'Formato de email inválido';
                    } else {
                        $sql = "SELECT id_agente FROM agente WHERE correo = ?";
                        $params = [$value];

                        if ($excludeId > 0) {
                            $sql .= " AND id_agente != ?";
                            $params[] = $excludeId;
                        }

                        $stmt = $pdo->prepare($sql);
                        $stmt->execute($params);

                        if ($stmt->fetchColumn()) {
                            $response['valid'] = false;
                            $response['message'] = 'Este email ya está registrado';
                        }
                    }
                }
                break;

            case 'telefono':
                if (!empty($value)) {
                    if (!preg_match('/^[0-9\-\+\(\)\s]{7,20}$/', $value)) {
                        $response['valid'] = false;
                        $response['message'] = 'Formato de teléfono inválido';
                    }
                }
                break;

            default:
                throw new Exception('Campo de validación no soportado');
        }

        return $response;

    } catch (PDOException $e) {
        error_log("Database error in handleValidation: " . $e->getMessage());
        return [
            'success' => false,
            'valid' => false,
            'message' => 'Error de base de datos en la validación'
        ];
    }
}

/**
 * Handle toggle agent status
 */
function handleToggleStatus($pdo, $data) {
    try {
        $agentId = (int)($data['id'] ?? 0);
        $newStatus = (int)($data['status'] ?? 0);

        if ($agentId <= 0) {
            throw new Exception('ID de agente inválido');
        }

        $stmt = $pdo->prepare("UPDATE agente SET activo = ?, updated_at = CURRENT_TIMESTAMP WHERE id_agente = ?");
        $result = $stmt->execute([$newStatus, $agentId]);

        if ($result && $stmt->rowCount() > 0) {
            return [
                'success' => true,
                'message' => 'Estado del agente actualizado correctamente',
                'data' => [
                    'id' => $agentId,
                    'new_status' => $newStatus
                ]
            ];
        } else {
            throw new Exception('No se pudo actualizar el estado del agente');
        }

    } catch (PDOException $e) {
        error_log("Database error in handleToggleStatus: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error de base de datos al cambiar el estado',
            'errors' => []
        ];
    }
}

/**
 * Handle data export
 */
function handleExport($pdo, $data) {
    try {
        $format = $data['format'] ?? 'json';
        $agentId = (int)($data['id'] ?? 0);
        $activeOnly = $data['active_only'] ?? false;

        if ($agentId > 0) {
            // Export single agent
            $stmt = $pdo->prepare("SELECT * FROM agente WHERE id_agente = ?");
            $stmt->execute([$agentId]);
            $agent = $stmt->fetch();

            if (!$agent) {
                throw new Exception('Agente no encontrado');
            }

            $exportData = [$agent];
        } else {
            // Export all agents
            $whereClause = $activeOnly ? 'WHERE activo = 1' : '';
            $stmt = $pdo->prepare("SELECT * FROM agente {$whereClause} ORDER BY nombre");
            $stmt->execute();
            $exportData = $stmt->fetchAll();
        }

        return [
            'success' => true,
            'message' => 'Datos exportados exitosamente',
            'data' => [
                'format' => $format,
                'records' => $exportData,
                'count' => count($exportData),
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ];

    } catch (PDOException $e) {
        error_log("Database error in handleExport: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error de base de datos en la exportación',
            'errors' => []
        ];
    }
}

/**
 * Handle bulk actions
 */
function handleBulkAction($pdo, $data) {
    try {
        $action = $data['bulk_action'] ?? '';
        $agentIds = $data['agent_ids'] ?? [];

        if (empty($agentIds) || !is_array($agentIds)) {
            throw new Exception('No se seleccionaron agentes');
        }

        $agentIds = array_map('intval', $agentIds);
        $agentIds = array_filter($agentIds, function($id) { return $id > 0; });

        if (empty($agentIds)) {
            throw new Exception('IDs de agente inválidos');
        }

        $results = [];

        switch ($action) {
            case 'activate':
                $placeholders = str_repeat('?,', count($agentIds) - 1) . '?';
                $stmt = $pdo->prepare("UPDATE agente SET activo = 1, updated_at = CURRENT_TIMESTAMP WHERE id_agente IN ({$placeholders})");
                $stmt->execute($agentIds);
                $affectedRows = $stmt->rowCount();

                return [
                    'success' => true,
                    'message' => "{$affectedRows} agente(s) activado(s) exitosamente",
                    'data' => ['affected_rows' => $affectedRows]
                ];

            case 'deactivate':
                $placeholders = str_repeat('?,', count($agentIds) - 1) . '?';
                $stmt = $pdo->prepare("UPDATE agente SET activo = 0, updated_at = CURRENT_TIMESTAMP WHERE id_agente IN ({$placeholders})");
                $stmt->execute($agentIds);
                $affectedRows = $stmt->rowCount();

                return [
                    'success' => true,
                    'message' => "{$affectedRows} agente(s) desactivado(s) exitosamente",
                    'data' => ['affected_rows' => $affectedRows]
                ];

            case 'delete':
                foreach ($agentIds as $agentId) {
                    $deleteResult = handleDelete($pdo, ['id' => $agentId]);
                    $results[] = [
                        'id' => $agentId,
                        'success' => $deleteResult['success'],
                        'message' => $deleteResult['message']
                    ];
                }
                break;

            case 'export':
                $placeholders = str_repeat('?,', count($agentIds) - 1) . '?';
                $stmt = $pdo->prepare("SELECT * FROM agente WHERE id_agente IN ({$placeholders}) ORDER BY nombre");
                $stmt->execute($agentIds);
                $agents = $stmt->fetchAll();

                return [
                    'success' => true,
                    'message' => 'Agentes exportados exitosamente',
                    'data' => [
                        'agents' => $agents,
                        'count' => count($agents)
                    ]
                ];

            default:
                throw new Exception('Acción en lote no válida');
        }

        if ($action === 'delete') {
            $successCount = count(array_filter($results, function($r) { return $r['success']; }));
            $totalCount = count($results);

            return [
                'success' => $successCount > 0,
                'message' => "Procesados {$successCount} de {$totalCount} agentes",
                'data' => [
                    'results' => $results,
                    'success_count' => $successCount,
                    'total_count' => $totalCount
                ]
            ];
        }

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage(),
            'errors' => []
        ];
    }
}

/**
 * Handle agent statistics
 */
function handleStatistics($pdo, $data) {
    try {
        $agentId = (int)($data['id'] ?? 0);

        if ($agentId > 0) {
            // Statistics for specific agent
            $stats = [];

            // Total sales and commission
            $stmt = $pdo->prepare("SELECT COUNT(*) as count, COALESCE(SUM(valor), 0) as total_value, COALESCE(SUM(comision), 0) as total_commission FROM venta WHERE id_agente = ?");
            $stmt->execute([$agentId]);
            $salesData = $stmt->fetch();
            $stats['sales'] = $salesData;

            // Active contracts
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM contrato WHERE id_agente = ? AND estado = 'Activo'");
            $stmt->execute([$agentId]);
            $stats['active_contracts'] = $stmt->fetchColumn();

            // Active rentals
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM arriendo WHERE id_agente = ? AND estado = 'Activo'");
            $stmt->execute([$agentId]);
            $stats['active_rentals'] = $stmt->fetchColumn();

            // Scheduled visits
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM visita WHERE id_agente = ? AND estado = 'Programada'");
            $stmt->execute([$agentId]);
            $stats['scheduled_visits'] = $stmt->fetchColumn();

            return [
                'success' => true,
                'message' => 'Estadísticas obtenidas',
                'data' => $stats
            ];
        } else {
            // Global statistics
            $stats = [];

            // Total agents
            $stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(activo = 1) as active, SUM(activo = 0) as inactive FROM agente");
            $stmt->execute();
            $stats['agents'] = $stmt->fetch();

            // Top performers
            $stmt = $pdo->prepare("
                SELECT a.id_agente, a.nombre, COUNT(v.id_venta) as sales_count, COALESCE(SUM(v.comision), 0) as total_commission
                FROM agente a
                LEFT JOIN venta v ON a.id_agente = v.id_agente
                WHERE a.activo = 1
                GROUP BY a.id_agente
                ORDER BY total_commission DESC
                LIMIT 5
            ");
            $stmt->execute();
            $stats['top_performers'] = $stmt->fetchAll();

            return [
                'success' => true,
                'message' => 'Estadísticas globales obtenidas',
                'data' => $stats
            ];
        }

    } catch (PDOException $e) {
        error_log("Database error in handleStatistics: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error de base de datos al obtener estadísticas',
            'errors' => []
        ];
    }
}
?>