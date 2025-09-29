<?php
/**
 * Contracts AJAX Operations - Real Estate Management System
 * Handle AJAX requests for contracts CRUD operations
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

        case 'updateStatus':
            if ($method !== 'POST') {
                throw new Exception('Método no permitido');
            }
            $response = handleUpdateStatus($pdo, $_POST);
            break;

        case 'checkExpiring':
            $response = handleCheckExpiring($pdo, $_GET);
            break;

        default:
            throw new Exception('Acción no válida');
    }

} catch (Exception $e) {
    error_log("Contracts AJAX Error: " . $e->getMessage());
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
 * Handle contract creation
 */
function handleCreate($pdo, $data) {
    try {
        // Validate required fields
        $required = ['tipo_contrato', 'fecha_inicio', 'valor_contrato', 'estado', 'id_inmueble', 'id_cliente'];
        $errors = [];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[] = "El campo {$field} es obligatorio";
            }
        }

        // Validate contract type
        if (!empty($data['tipo_contrato']) && !in_array($data['tipo_contrato'], ['Venta', 'Arriendo'])) {
            $errors[] = "Tipo de contrato inválido";
        }

        // Validate dates
        if ($data['tipo_contrato'] === 'Arriendo' && empty($data['fecha_fin'])) {
            $errors[] = "La fecha de fin es obligatoria para contratos de arriendo";
        }

        if (!empty($data['fecha_inicio']) && !empty($data['fecha_fin']) && $data['fecha_fin'] <= $data['fecha_inicio']) {
            $errors[] = "La fecha de fin debe ser posterior a la fecha de inicio";
        }

        // Validate amounts
        if (!empty($data['valor_contrato']) && $data['valor_contrato'] <= 0) {
            $errors[] = "El valor del contrato debe ser mayor a 0";
        }

        // Validate status
        if (!empty($data['estado']) && !array_key_exists($data['estado'], CONTRACT_STATUS)) {
            $errors[] = "Estado del contrato inválido";
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $errors
            ];
        }

        // Check if property exists
        $stmt = $pdo->prepare("SELECT estado FROM inmueble WHERE id_inmueble = ?");
        $stmt->execute([$data['id_inmueble']]);
        $propertyStatus = $stmt->fetchColumn();

        if (!$propertyStatus) {
            return [
                'success' => false,
                'message' => 'El inmueble seleccionado no existe',
                'errors' => ['id_inmueble' => 'Inmueble no encontrado']
            ];
        }

        // Check if client exists
        $stmt = $pdo->prepare("SELECT id_cliente FROM cliente WHERE id_cliente = ?");
        $stmt->execute([$data['id_cliente']]);
        if (!$stmt->fetchColumn()) {
            return [
                'success' => false,
                'message' => 'El cliente seleccionado no existe',
                'errors' => ['id_cliente' => 'Cliente no encontrado']
            ];
        }

        // Check if agent exists (if provided)
        if (!empty($data['id_agente'])) {
            $stmt = $pdo->prepare("SELECT activo FROM agente WHERE id_agente = ?");
            $stmt->execute([$data['id_agente']]);
            $agentActive = $stmt->fetchColumn();

            if ($agentActive === false) {
                return [
                    'success' => false,
                    'message' => 'El agente seleccionado no existe',
                    'errors' => ['id_agente' => 'Agente no encontrado']
                ];
            }
        }

        // Insert new contract
        $stmt = $pdo->prepare("
            INSERT INTO contrato (tipo_contrato, fecha_inicio, fecha_fin, valor_contrato, estado,
                                 observaciones, archivo_contrato, id_inmueble, id_cliente, id_agente)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $result = $stmt->execute([
            $data['tipo_contrato'],
            $data['fecha_inicio'],
            !empty($data['fecha_fin']) ? $data['fecha_fin'] : null,
            $data['valor_contrato'],
            $data['estado'],
            trim($data['observaciones'] ?? ''),
            trim($data['archivo_contrato'] ?? ''),
            $data['id_inmueble'],
            $data['id_cliente'],
            !empty($data['id_agente']) ? $data['id_agente'] : null
        ]);

        if ($result) {
            $contractId = $pdo->lastInsertId();

            // Get the created contract data
            $stmt = $pdo->prepare("SELECT * FROM contrato WHERE id_contrato = ?");
            $stmt->execute([$contractId]);
            $contract = $stmt->fetch();

            return [
                'success' => true,
                'message' => 'Contrato creado exitosamente',
                'data' => [
                    'id' => $contractId,
                    'contract' => $contract,
                    'display_id' => 'CON' . str_pad($contractId, 3, '0', STR_PAD_LEFT)
                ]
            ];
        } else {
            throw new Exception('Error al crear el contrato');
        }

    } catch (PDOException $e) {
        error_log("Database error in handleCreate: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error de base de datos al crear el contrato',
            'errors' => []
        ];
    }
}

/**
 * Handle contract update
 */
function handleUpdate($pdo, $data) {
    try {
        $contractId = (int)($data['id'] ?? 0);

        if ($contractId <= 0) {
            throw new Exception('ID de contrato inválido');
        }

        // Check if contract exists
        $stmt = $pdo->prepare("SELECT * FROM contrato WHERE id_contrato = ?");
        $stmt->execute([$contractId]);
        $existingContract = $stmt->fetch();

        if (!$existingContract) {
            throw new Exception('Contrato no encontrado');
        }

        // Validate required fields
        $required = ['tipo_contrato', 'fecha_inicio', 'valor_contrato', 'estado', 'id_inmueble', 'id_cliente'];
        $errors = [];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[] = "El campo {$field} es obligatorio";
            }
        }

        // Validate dates
        if ($data['tipo_contrato'] === 'Arriendo' && empty($data['fecha_fin'])) {
            $errors[] = "La fecha de fin es obligatoria para contratos de arriendo";
        }

        if (!empty($data['fecha_inicio']) && !empty($data['fecha_fin']) && $data['fecha_fin'] <= $data['fecha_inicio']) {
            $errors[] = "La fecha de fin debe ser posterior a la fecha de inicio";
        }

        // Validate amounts
        if (!empty($data['valor_contrato']) && $data['valor_contrato'] <= 0) {
            $errors[] = "El valor del contrato debe ser mayor a 0";
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $errors
            ];
        }

        // Update contract
        $stmt = $pdo->prepare("
            UPDATE contrato
            SET tipo_contrato = ?, fecha_inicio = ?, fecha_fin = ?, valor_contrato = ?,
                estado = ?, observaciones = ?, archivo_contrato = ?,
                id_inmueble = ?, id_cliente = ?, id_agente = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id_contrato = ?
        ");

        $result = $stmt->execute([
            $data['tipo_contrato'],
            $data['fecha_inicio'],
            !empty($data['fecha_fin']) ? $data['fecha_fin'] : null,
            $data['valor_contrato'],
            $data['estado'],
            trim($data['observaciones'] ?? ''),
            trim($data['archivo_contrato'] ?? ''),
            $data['id_inmueble'],
            $data['id_cliente'],
            !empty($data['id_agente']) ? $data['id_agente'] : null,
            $contractId
        ]);

        if ($result) {
            // Get updated contract data
            $stmt = $pdo->prepare("SELECT * FROM contrato WHERE id_contrato = ?");
            $stmt->execute([$contractId]);
            $contract = $stmt->fetch();

            return [
                'success' => true,
                'message' => 'Contrato actualizado exitosamente',
                'data' => [
                    'id' => $contractId,
                    'contract' => $contract
                ]
            ];
        } else {
            throw new Exception('Error al actualizar el contrato');
        }

    } catch (PDOException $e) {
        error_log("Database error in handleUpdate: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error de base de datos al actualizar el contrato',
            'errors' => []
        ];
    }
}

/**
 * Handle contract deletion
 */
function handleDelete($pdo, $data) {
    try {
        $contractId = (int)($data['id'] ?? 0);

        if ($contractId <= 0) {
            throw new Exception('ID de contrato inválido');
        }

        // Check if contract exists
        $stmt = $pdo->prepare("SELECT * FROM contrato WHERE id_contrato = ?");
        $stmt->execute([$contractId]);
        $contract = $stmt->fetch();

        if (!$contract) {
            throw new Exception('Contrato no encontrado');
        }

        // Check if contract can be deleted (only Borrador or Cancelado)
        if (!in_array($contract['estado'], ['Borrador', 'Cancelado'])) {
            return [
                'success' => false,
                'message' => "No se puede eliminar un contrato en estado {$contract['estado']}. Cancélelo primero.",
                'data' => [
                    'current_status' => $contract['estado'],
                    'can_delete' => false
                ]
            ];
        }

        // Delete contract
        $stmt = $pdo->prepare("DELETE FROM contrato WHERE id_contrato = ?");
        $result = $stmt->execute([$contractId]);

        if ($result) {
            return [
                'success' => true,
                'message' => 'Contrato eliminado exitosamente',
                'data' => [
                    'deleted_contract' => $contract
                ]
            ];
        } else {
            throw new Exception('Error al eliminar el contrato');
        }

    } catch (PDOException $e) {
        error_log("Database error in handleDelete: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error de base de datos al eliminar el contrato',
            'errors' => []
        ];
    }
}

/**
 * Handle getting contract data
 */
function handleGet($pdo, $data) {
    try {
        $contractId = (int)($data['id'] ?? 0);

        if ($contractId <= 0) {
            throw new Exception('ID de contrato inválido');
        }

        $stmt = $pdo->prepare("
            SELECT c.*,
                   i.tipo_inmueble, i.direccion, i.ciudad,
                   cl.nombre as cliente_nombre, cl.apellido as cliente_apellido,
                   a.nombre as agente_nombre
            FROM contrato c
            LEFT JOIN inmueble i ON c.id_inmueble = i.id_inmueble
            LEFT JOIN cliente cl ON c.id_cliente = cl.id_cliente
            LEFT JOIN agente a ON c.id_agente = a.id_agente
            WHERE c.id_contrato = ?
        ");
        $stmt->execute([$contractId]);
        $contract = $stmt->fetch();

        if (!$contract) {
            throw new Exception('Contrato no encontrado');
        }

        return [
            'success' => true,
            'message' => 'Contrato encontrado',
            'data' => [
                'contract' => $contract,
                'display_id' => 'CON' . str_pad($contract['id_contrato'], 3, '0', STR_PAD_LEFT)
            ]
        ];

    } catch (PDOException $e) {
        error_log("Database error in handleGet: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error de base de datos al obtener el contrato',
            'errors' => []
        ];
    }
}

/**
 * Handle contract search
 */
function handleSearch($pdo, $data) {
    try {
        $searchTerm = trim($data['term'] ?? '');
        $limit = min((int)($data['limit'] ?? 10), 50);
        $tipo = $data['tipo'] ?? '';
        $estado = $data['estado'] ?? '';
        $dateFrom = $data['date_from'] ?? '';
        $dateTo = $data['date_to'] ?? '';

        $whereConditions = [];
        $params = [];

        if (!empty($searchTerm)) {
            $whereConditions[] = "(i.direccion LIKE ? OR i.ciudad LIKE ? OR cl.nombre LIKE ? OR cl.apellido LIKE ? OR c.observaciones LIKE ?)";
            $searchWildcard = "%{$searchTerm}%";
            $params = array_merge($params, array_fill(0, 5, $searchWildcard));
        }

        if (!empty($tipo) && in_array($tipo, ['Venta', 'Arriendo'])) {
            $whereConditions[] = "c.tipo_contrato = ?";
            $params[] = $tipo;
        }

        if (!empty($estado)) {
            $whereConditions[] = "c.estado = ?";
            $params[] = $estado;
        }

        if (!empty($dateFrom)) {
            $whereConditions[] = "c.fecha_inicio >= ?";
            $params[] = $dateFrom;
        }

        if (!empty($dateTo)) {
            $whereConditions[] = "c.fecha_inicio <= ?";
            $params[] = $dateTo;
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        $sql = "SELECT c.id_contrato, c.tipo_contrato, c.fecha_inicio, c.fecha_fin,
                       c.valor_contrato, c.estado,
                       i.tipo_inmueble, i.direccion, i.ciudad,
                       cl.nombre as cliente_nombre, cl.apellido as cliente_apellido,
                       a.nombre as agente_nombre
                FROM contrato c
                LEFT JOIN inmueble i ON c.id_inmueble = i.id_inmueble
                LEFT JOIN cliente cl ON c.id_cliente = cl.id_cliente
                LEFT JOIN agente a ON c.id_agente = a.id_agente
                {$whereClause}
                ORDER BY c.fecha_inicio DESC
                LIMIT ?";

        $params[] = $limit;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $contracts = $stmt->fetchAll();

        // Format results
        $results = array_map(function($contract) {
            return [
                'id' => $contract['id_contrato'],
                'display_id' => 'CON' . str_pad($contract['id_contrato'], 3, '0', STR_PAD_LEFT),
                'tipo' => $contract['tipo_contrato'],
                'fecha_inicio' => $contract['fecha_inicio'],
                'fecha_fin' => $contract['fecha_fin'],
                'valor' => $contract['valor_contrato'],
                'estado' => $contract['estado'],
                'property' => $contract['tipo_inmueble'] . ' - ' . $contract['direccion'] . ', ' . $contract['ciudad'],
                'client' => $contract['cliente_nombre'] . ' ' . $contract['cliente_apellido'],
                'agent' => $contract['agente_nombre'] ?: 'Sin agente',
                'label' => 'CON' . str_pad($contract['id_contrato'], 3, '0', STR_PAD_LEFT) . ' - ' .
                          $contract['tipo_contrato'] . ' - ' . $contract['tipo_inmueble']
            ];
        }, $contracts);

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

        $response = [
            'success' => true,
            'valid' => true,
            'message' => 'Campo válido'
        ];

        switch ($field) {
            case 'valor_contrato':
                if (!empty($value) && (!is_numeric($value) || $value <= 0)) {
                    $response['valid'] = false;
                    $response['message'] = 'El valor debe ser un número mayor a 0';
                }
                break;

            case 'fecha_fin':
                $fechaInicio = $data['fecha_inicio'] ?? '';
                if (!empty($value) && !empty($fechaInicio) && $value <= $fechaInicio) {
                    $response['valid'] = false;
                    $response['message'] = 'La fecha de fin debe ser posterior a la fecha de inicio';
                }
                break;

            case 'property_available':
                $propertyId = (int)$value;
                if ($propertyId > 0) {
                    $stmt = $pdo->prepare("SELECT estado FROM inmueble WHERE id_inmueble = ?");
                    $stmt->execute([$propertyId]);
                    $status = $stmt->fetchColumn();

                    if (!$status) {
                        $response['valid'] = false;
                        $response['message'] = 'El inmueble no existe';
                    }
                }
                break;

            case 'contract_conflict':
                $propertyId = (int)($data['id_inmueble'] ?? 0);
                $fechaInicio = $data['fecha_inicio'] ?? '';
                $fechaFin = $data['fecha_fin'] ?? '';
                $excludeId = (int)($data['exclude_id'] ?? 0);

                if ($propertyId > 0 && !empty($fechaInicio)) {
                    $sql = "SELECT COUNT(*) FROM contrato
                            WHERE id_inmueble = ?
                            AND estado = 'Activo'
                            AND id_contrato != ?";
                    $params = [$propertyId, $excludeId];

                    if (!empty($fechaFin)) {
                        $sql .= " AND ((fecha_inicio BETWEEN ? AND ?) OR (fecha_fin BETWEEN ? AND ?)
                                  OR (fecha_inicio <= ? AND fecha_fin >= ?))";
                        $params = array_merge($params, [$fechaInicio, $fechaFin, $fechaInicio, $fechaFin, $fechaInicio, $fechaFin]);
                    } else {
                        $sql .= " AND fecha_inicio = ?";
                        $params[] = $fechaInicio;
                    }

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $conflicts = $stmt->fetchColumn();

                    if ($conflicts > 0) {
                        $response['valid'] = false;
                        $response['message'] = 'Ya existe un contrato activo para este inmueble en las fechas especificadas';
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
 * Handle data export
 */
function handleExport($pdo, $data) {
    try {
        $format = $data['format'] ?? 'json';
        $contractId = (int)($data['id'] ?? 0);
        $tipo = $data['tipo'] ?? '';
        $estado = $data['estado'] ?? '';
        $dateFrom = $data['date_from'] ?? '';
        $dateTo = $data['date_to'] ?? '';

        $whereConditions = [];
        $params = [];

        if ($contractId > 0) {
            // Export single contract
            $whereConditions[] = "c.id_contrato = ?";
            $params[] = $contractId;
        }

        if (!empty($tipo)) {
            $whereConditions[] = "c.tipo_contrato = ?";
            $params[] = $tipo;
        }

        if (!empty($estado)) {
            $whereConditions[] = "c.estado = ?";
            $params[] = $estado;
        }

        if (!empty($dateFrom)) {
            $whereConditions[] = "c.fecha_inicio >= ?";
            $params[] = $dateFrom;
        }

        if (!empty($dateTo)) {
            $whereConditions[] = "c.fecha_inicio <= ?";
            $params[] = $dateTo;
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        $stmt = $pdo->prepare("
            SELECT c.*,
                   i.tipo_inmueble, i.direccion, i.ciudad,
                   cl.nombre as cliente_nombre, cl.apellido as cliente_apellido,
                   a.nombre as agente_nombre
            FROM contrato c
            LEFT JOIN inmueble i ON c.id_inmueble = i.id_inmueble
            LEFT JOIN cliente cl ON c.id_cliente = cl.id_cliente
            LEFT JOIN agente a ON c.id_agente = a.id_agente
            {$whereClause}
            ORDER BY c.fecha_inicio DESC
        ");
        $stmt->execute($params);
        $exportData = $stmt->fetchAll();

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
        $contractIds = $data['contract_ids'] ?? [];

        if (empty($contractIds) || !is_array($contractIds)) {
            throw new Exception('No se seleccionaron contratos');
        }

        $contractIds = array_map('intval', $contractIds);
        $contractIds = array_filter($contractIds, function($id) { return $id > 0; });

        if (empty($contractIds)) {
            throw new Exception('IDs de contrato inválidos');
        }

        $results = [];

        switch ($action) {
            case 'delete':
                foreach ($contractIds as $contractId) {
                    $deleteResult = handleDelete($pdo, ['id' => $contractId]);
                    $results[] = [
                        'id' => $contractId,
                        'success' => $deleteResult['success'],
                        'message' => $deleteResult['message']
                    ];
                }
                break;

            case 'updateStatus':
                $newStatus = $data['new_status'] ?? '';
                if (empty($newStatus)) {
                    throw new Exception('Estado nuevo no especificado');
                }

                foreach ($contractIds as $contractId) {
                    $updateResult = handleUpdateStatus($pdo, ['id' => $contractId, 'estado' => $newStatus]);
                    $results[] = [
                        'id' => $contractId,
                        'success' => $updateResult['success'],
                        'message' => $updateResult['message']
                    ];
                }
                break;

            case 'export':
                $placeholders = str_repeat('?,', count($contractIds) - 1) . '?';
                $stmt = $pdo->prepare("
                    SELECT c.*,
                           i.tipo_inmueble, i.direccion,
                           cl.nombre as cliente_nombre, cl.apellido as cliente_apellido
                    FROM contrato c
                    LEFT JOIN inmueble i ON c.id_inmueble = i.id_inmueble
                    LEFT JOIN cliente cl ON c.id_cliente = cl.id_cliente
                    WHERE c.id_contrato IN ({$placeholders})
                    ORDER BY c.fecha_inicio DESC
                ");
                $stmt->execute($contractIds);
                $contracts = $stmt->fetchAll();

                return [
                    'success' => true,
                    'message' => 'Contratos exportados exitosamente',
                    'data' => [
                        'contracts' => $contracts,
                        'count' => count($contracts)
                    ]
                ];

            default:
                throw new Exception('Acción en lote no válida');
        }

        $successCount = count(array_filter($results, function($r) { return $r['success']; }));
        $totalCount = count($results);

        return [
            'success' => $successCount > 0,
            'message' => "Procesados {$successCount} de {$totalCount} contratos",
            'data' => [
                'results' => $results,
                'success_count' => $successCount,
                'total_count' => $totalCount
            ]
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage(),
            'errors' => []
        ];
    }
}

/**
 * Handle contract statistics
 */
function handleStatistics($pdo, $data) {
    try {
        $period = $data['period'] ?? 'all'; // all, year, month
        $year = $data['year'] ?? date('Y');
        $month = $data['month'] ?? date('m');

        $stats = [];

        // Total contracts by type
        $stmt = $pdo->prepare("
            SELECT tipo_contrato, COUNT(*) as count, COALESCE(SUM(valor_contrato), 0) as total_value
            FROM contrato
            GROUP BY tipo_contrato
        ");
        $stmt->execute();
        $stats['by_type'] = $stmt->fetchAll();

        // Contracts by status
        $stmt = $pdo->prepare("
            SELECT estado, COUNT(*) as count
            FROM contrato
            GROUP BY estado
            ORDER BY count DESC
        ");
        $stmt->execute();
        $stats['by_status'] = $stmt->fetchAll();

        // Active contracts count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM contrato WHERE estado = 'Activo'");
        $stmt->execute();
        $stats['active_count'] = $stmt->fetchColumn();

        // Expiring contracts (within 30 days)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM contrato
            WHERE estado = 'Activo'
            AND fecha_fin IS NOT NULL
            AND fecha_fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ");
        $stmt->execute();
        $stats['expiring_soon'] = $stmt->fetchColumn();

        // Average contract value by type
        $stmt = $pdo->prepare("
            SELECT tipo_contrato, AVG(valor_contrato) as avg_value,
                   MIN(valor_contrato) as min_value, MAX(valor_contrato) as max_value
            FROM contrato
            GROUP BY tipo_contrato
        ");
        $stmt->execute();
        $stats['value_stats'] = $stmt->fetchAll();

        // Top agents by contracts
        $stmt = $pdo->prepare("
            SELECT a.id_agente, a.nombre, COUNT(c.id_contrato) as contract_count,
                   COALESCE(SUM(c.valor_contrato), 0) as total_value
            FROM agente a
            LEFT JOIN contrato c ON a.id_agente = c.id_agente
            WHERE a.activo = 1
            GROUP BY a.id_agente
            ORDER BY contract_count DESC
            LIMIT 5
        ");
        $stmt->execute();
        $stats['top_agents'] = $stmt->fetchAll();

        // Contracts by property type
        $stmt = $pdo->prepare("
            SELECT i.tipo_inmueble, COUNT(c.id_contrato) as count
            FROM contrato c
            JOIN inmueble i ON c.id_inmueble = i.id_inmueble
            GROUP BY i.tipo_inmueble
            ORDER BY count DESC
        ");
        $stmt->execute();
        $stats['by_property_type'] = $stmt->fetchAll();

        return [
            'success' => true,
            'message' => 'Estadísticas obtenidas',
            'data' => $stats
        ];

    } catch (PDOException $e) {
        error_log("Database error in handleStatistics: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error de base de datos al obtener estadísticas',
            'errors' => []
        ];
    }
}

/**
 * Handle contract status update
 */
function handleUpdateStatus($pdo, $data) {
    try {
        $contractId = (int)($data['id'] ?? 0);
        $newStatus = $data['estado'] ?? '';

        if ($contractId <= 0) {
            throw new Exception('ID de contrato inválido');
        }

        if (empty($newStatus) || !array_key_exists($newStatus, CONTRACT_STATUS)) {
            throw new Exception('Estado inválido');
        }

        // Check if contract exists
        $stmt = $pdo->prepare("SELECT estado FROM contrato WHERE id_contrato = ?");
        $stmt->execute([$contractId]);
        $currentStatus = $stmt->fetchColumn();

        if ($currentStatus === false) {
            throw new Exception('Contrato no encontrado');
        }

        // Validate status transition
        $validTransitions = [
            'Borrador' => ['Activo', 'Cancelado'],
            'Activo' => ['Finalizado', 'Cancelado'],
            'Finalizado' => [],
            'Cancelado' => []
        ];

        if (!in_array($newStatus, $validTransitions[$currentStatus] ?? [])) {
            return [
                'success' => false,
                'message' => "No se puede cambiar el estado de {$currentStatus} a {$newStatus}",
                'data' => [
                    'current_status' => $currentStatus,
                    'requested_status' => $newStatus,
                    'valid_transitions' => $validTransitions[$currentStatus] ?? []
                ]
            ];
        }

        // Update status
        $stmt = $pdo->prepare("UPDATE contrato SET estado = ?, updated_at = CURRENT_TIMESTAMP WHERE id_contrato = ?");
        $result = $stmt->execute([$newStatus, $contractId]);

        if ($result) {
            return [
                'success' => true,
                'message' => "Estado actualizado de {$currentStatus} a {$newStatus}",
                'data' => [
                    'previous_status' => $currentStatus,
                    'new_status' => $newStatus
                ]
            ];
        } else {
            throw new Exception('Error al actualizar el estado');
        }

    } catch (PDOException $e) {
        error_log("Database error in handleUpdateStatus: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error de base de datos al actualizar el estado',
            'errors' => []
        ];
    }
}

/**
 * Handle checking expiring contracts
 */
function handleCheckExpiring($pdo, $data) {
    try {
        $days = min((int)($data['days'] ?? 30), 90); // Max 90 days

        $stmt = $pdo->prepare("
            SELECT c.id_contrato, c.tipo_contrato, c.fecha_fin, c.valor_contrato,
                   i.direccion, i.ciudad,
                   cl.nombre as cliente_nombre, cl.apellido as cliente_apellido,
                   DATEDIFF(c.fecha_fin, CURDATE()) as days_remaining
            FROM contrato c
            JOIN inmueble i ON c.id_inmueble = i.id_inmueble
            JOIN cliente cl ON c.id_cliente = cl.id_cliente
            WHERE c.estado = 'Activo'
            AND c.fecha_fin IS NOT NULL
            AND c.fecha_fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
            ORDER BY c.fecha_fin ASC
        ");
        $stmt->execute([$days]);
        $expiringContracts = $stmt->fetchAll();

        return [
            'success' => true,
            'message' => 'Contratos próximos a vencer obtenidos',
            'data' => [
                'contracts' => $expiringContracts,
                'count' => count($expiringContracts),
                'days' => $days
            ]
        ];

    } catch (PDOException $e) {
        error_log("Database error in handleCheckExpiring: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error de base de datos al verificar contratos',
            'errors' => []
        ];
    }
}
?>