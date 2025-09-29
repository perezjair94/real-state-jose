<?php
/**
 * Rentals AJAX Operations - Real Estate Management System
 * Handle AJAX requests for rentals CRUD operations
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

        case 'registerPayment':
            if ($method !== 'POST') {
                throw new Exception('Método no permitido');
            }
            $response = handleRegisterPayment($pdo, $_POST);
            break;

        default:
            throw new Exception('Acción no válida');
    }

} catch (Exception $e) {
    error_log("Rentals AJAX Error: " . $e->getMessage());
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
 * Handle rental creation
 */
function handleCreate($pdo, $data) {
    try {
        // Validate required fields
        $required = ['fecha_inicio', 'fecha_fin', 'canon_mensual', 'estado', 'id_inmueble', 'id_cliente'];
        $errors = [];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[] = "El campo {$field} es obligatorio";
            }
        }

        // Validate dates
        if (!empty($data['fecha_inicio']) && !empty($data['fecha_fin']) && $data['fecha_fin'] <= $data['fecha_inicio']) {
            $errors[] = "La fecha de fin debe ser posterior a la fecha de inicio";
        }

        // Validate amounts
        if (!empty($data['canon_mensual']) && $data['canon_mensual'] <= 0) {
            $errors[] = "El canon mensual debe ser mayor a 0";
        }

        if (!empty($data['deposito']) && $data['deposito'] < 0) {
            $errors[] = "El depósito no puede ser negativo";
        }

        // Validate status
        if (!empty($data['estado']) && !array_key_exists($data['estado'], RENTAL_STATUS)) {
            $errors[] = "Estado del arriendo inválido";
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

        // Insert new rental
        $stmt = $pdo->prepare("
            INSERT INTO arriendo (fecha_inicio, fecha_fin, canon_mensual, deposito, estado,
                                 observaciones, id_inmueble, id_cliente, id_agente)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $result = $stmt->execute([
            $data['fecha_inicio'],
            $data['fecha_fin'],
            $data['canon_mensual'],
            !empty($data['deposito']) ? $data['deposito'] : null,
            $data['estado'],
            trim($data['observaciones'] ?? ''),
            $data['id_inmueble'],
            $data['id_cliente'],
            !empty($data['id_agente']) ? $data['id_agente'] : null
        ]);

        if ($result) {
            $rentalId = $pdo->lastInsertId();

            // Get the created rental data
            $stmt = $pdo->prepare("SELECT * FROM arriendo WHERE id_arriendo = ?");
            $stmt->execute([$rentalId]);
            $rental = $stmt->fetch();

            return [
                'success' => true,
                'message' => 'Arriendo creado exitosamente',
                'data' => [
                    'id' => $rentalId,
                    'rental' => $rental,
                    'display_id' => 'ARR' . str_pad($rentalId, 3, '0', STR_PAD_LEFT)
                ]
            ];
        } else {
            throw new Exception('Error al crear el arriendo');
        }

    } catch (PDOException $e) {
        error_log("Database error in handleCreate: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error de base de datos al crear el arriendo',
            'errors' => []
        ];
    }
}

/**
 * Handle rental update
 */
function handleUpdate($pdo, $data) {
    try {
        $rentalId = (int)($data['id'] ?? 0);

        if ($rentalId <= 0) {
            throw new Exception('ID de arriendo inválido');
        }

        // Check if rental exists
        $stmt = $pdo->prepare("SELECT * FROM arriendo WHERE id_arriendo = ?");
        $stmt->execute([$rentalId]);
        $existingRental = $stmt->fetch();

        if (!$existingRental) {
            throw new Exception('Arriendo no encontrado');
        }

        // Validate required fields
        $required = ['fecha_inicio', 'fecha_fin', 'canon_mensual', 'estado', 'id_inmueble', 'id_cliente'];
        $errors = [];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[] = "El campo {$field} es obligatorio";
            }
        }

        // Validate dates
        if (!empty($data['fecha_inicio']) && !empty($data['fecha_fin']) && $data['fecha_fin'] <= $data['fecha_inicio']) {
            $errors[] = "La fecha de fin debe ser posterior a la fecha de inicio";
        }

        // Validate amounts
        if (!empty($data['canon_mensual']) && $data['canon_mensual'] <= 0) {
            $errors[] = "El canon mensual debe ser mayor a 0";
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $errors
            ];
        }

        // Update rental
        $stmt = $pdo->prepare("
            UPDATE arriendo
            SET fecha_inicio = ?, fecha_fin = ?, canon_mensual = ?, deposito = ?,
                estado = ?, observaciones = ?, id_inmueble = ?, id_cliente = ?, id_agente = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id_arriendo = ?
        ");

        $result = $stmt->execute([
            $data['fecha_inicio'],
            $data['fecha_fin'],
            $data['canon_mensual'],
            !empty($data['deposito']) ? $data['deposito'] : null,
            $data['estado'],
            trim($data['observaciones'] ?? ''),
            $data['id_inmueble'],
            $data['id_cliente'],
            !empty($data['id_agente']) ? $data['id_agente'] : null,
            $rentalId
        ]);

        if ($result) {
            // Get updated rental data
            $stmt = $pdo->prepare("SELECT * FROM arriendo WHERE id_arriendo = ?");
            $stmt->execute([$rentalId]);
            $rental = $stmt->fetch();

            return [
                'success' => true,
                'message' => 'Arriendo actualizado exitosamente',
                'data' => [
                    'id' => $rentalId,
                    'rental' => $rental
                ]
            ];
        } else {
            throw new Exception('Error al actualizar el arriendo');
        }

    } catch (PDOException $e) {
        error_log("Database error in handleUpdate: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error de base de datos al actualizar el arriendo',
            'errors' => []
        ];
    }
}

/**
 * Handle rental deletion
 */
function handleDelete($pdo, $data) {
    try {
        $rentalId = (int)($data['id'] ?? 0);

        if ($rentalId <= 0) {
            throw new Exception('ID de arriendo inválido');
        }

        // Check if rental exists
        $stmt = $pdo->prepare("SELECT * FROM arriendo WHERE id_arriendo = ?");
        $stmt->execute([$rentalId]);
        $rental = $stmt->fetch();

        if (!$rental) {
            throw new Exception('Arriendo no encontrado');
        }

        // Check if rental can be deleted (only Terminado or before activation)
        if (!in_array($rental['estado'], ['Terminado', 'Vencido'])) {
            return [
                'success' => false,
                'message' => "No se puede eliminar un arriendo en estado {$rental['estado']}. Primero debe terminarlo.",
                'data' => [
                    'current_status' => $rental['estado'],
                    'can_delete' => false
                ]
            ];
        }

        // Delete rental
        $stmt = $pdo->prepare("DELETE FROM arriendo WHERE id_arriendo = ?");
        $result = $stmt->execute([$rentalId]);

        if ($result) {
            return [
                'success' => true,
                'message' => 'Arriendo eliminado exitosamente',
                'data' => [
                    'deleted_rental' => $rental
                ]
            ];
        } else {
            throw new Exception('Error al eliminar el arriendo');
        }

    } catch (PDOException $e) {
        error_log("Database error in handleDelete: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error de base de datos al eliminar el arriendo',
            'errors' => []
        ];
    }
}

/**
 * Handle getting rental data
 */
function handleGet($pdo, $data) {
    try {
        $rentalId = (int)($data['id'] ?? 0);

        if ($rentalId <= 0) {
            throw new Exception('ID de arriendo inválido');
        }

        $stmt = $pdo->prepare("
            SELECT a.*,
                   i.tipo_inmueble, i.direccion, i.ciudad,
                   cl.nombre as cliente_nombre, cl.apellido as cliente_apellido,
                   ag.nombre as agente_nombre
            FROM arriendo a
            LEFT JOIN inmueble i ON a.id_inmueble = i.id_inmueble
            LEFT JOIN cliente cl ON a.id_cliente = cl.id_cliente
            LEFT JOIN agente ag ON a.id_agente = ag.id_agente
            WHERE a.id_arriendo = ?
        ");
        $stmt->execute([$rentalId]);
        $rental = $stmt->fetch();

        if (!$rental) {
            throw new Exception('Arriendo no encontrado');
        }

        return [
            'success' => true,
            'message' => 'Arriendo encontrado',
            'data' => [
                'rental' => $rental,
                'display_id' => 'ARR' . str_pad($rental['id_arriendo'], 3, '0', STR_PAD_LEFT)
            ]
        ];

    } catch (PDOException $e) {
        error_log("Database error in handleGet: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error de base de datos al obtener el arriendo',
            'errors' => []
        ];
    }
}

/**
 * Handle rental search
 */
function handleSearch($pdo, $data) {
    try {
        $searchTerm = trim($data['term'] ?? '');
        $limit = min((int)($data['limit'] ?? 10), 50);
        $estado = $data['estado'] ?? '';
        $dateFrom = $data['date_from'] ?? '';
        $dateTo = $data['date_to'] ?? '';
        $minCanon = $data['min_canon'] ?? '';
        $maxCanon = $data['max_canon'] ?? '';

        $whereConditions = [];
        $params = [];

        if (!empty($searchTerm)) {
            $whereConditions[] = "(i.direccion LIKE ? OR i.ciudad LIKE ? OR cl.nombre LIKE ? OR cl.apellido LIKE ? OR a.observaciones LIKE ?)";
            $searchWildcard = "%{$searchTerm}%";
            $params = array_merge($params, array_fill(0, 5, $searchWildcard));
        }

        if (!empty($estado)) {
            $whereConditions[] = "a.estado = ?";
            $params[] = $estado;
        }

        if (!empty($dateFrom)) {
            $whereConditions[] = "a.fecha_inicio >= ?";
            $params[] = $dateFrom;
        }

        if (!empty($dateTo)) {
            $whereConditions[] = "a.fecha_inicio <= ?";
            $params[] = $dateTo;
        }

        if (!empty($minCanon) && is_numeric($minCanon)) {
            $whereConditions[] = "a.canon_mensual >= ?";
            $params[] = $minCanon;
        }

        if (!empty($maxCanon) && is_numeric($maxCanon)) {
            $whereConditions[] = "a.canon_mensual <= ?";
            $params[] = $maxCanon;
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        $sql = "SELECT a.id_arriendo, a.fecha_inicio, a.fecha_fin, a.canon_mensual,
                       a.deposito, a.estado,
                       i.tipo_inmueble, i.direccion, i.ciudad,
                       cl.nombre as cliente_nombre, cl.apellido as cliente_apellido,
                       ag.nombre as agente_nombre
                FROM arriendo a
                LEFT JOIN inmueble i ON a.id_inmueble = i.id_inmueble
                LEFT JOIN cliente cl ON a.id_cliente = cl.id_cliente
                LEFT JOIN agente ag ON a.id_agente = ag.id_agente
                {$whereClause}
                ORDER BY a.fecha_inicio DESC
                LIMIT ?";

        $params[] = $limit;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rentals = $stmt->fetchAll();

        // Format results
        $results = array_map(function($rental) {
            return [
                'id' => $rental['id_arriendo'],
                'display_id' => 'ARR' . str_pad($rental['id_arriendo'], 3, '0', STR_PAD_LEFT),
                'fecha_inicio' => $rental['fecha_inicio'],
                'fecha_fin' => $rental['fecha_fin'],
                'canon' => $rental['canon_mensual'],
                'deposito' => $rental['deposito'],
                'estado' => $rental['estado'],
                'property' => $rental['tipo_inmueble'] . ' - ' . $rental['direccion'] . ', ' . $rental['ciudad'],
                'client' => $rental['cliente_nombre'] . ' ' . $rental['cliente_apellido'],
                'agent' => $rental['agente_nombre'] ?: 'Sin agente',
                'label' => 'ARR' . str_pad($rental['id_arriendo'], 3, '0', STR_PAD_LEFT) . ' - ' .
                          $rental['tipo_inmueble'] . ' - ' . number_format($rental['canon_mensual'], 0, ',', '.') . '/mes'
            ];
        }, $rentals);

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
            case 'canon_mensual':
                if (!empty($value) && (!is_numeric($value) || $value <= 0)) {
                    $response['valid'] = false;
                    $response['message'] = 'El canon mensual debe ser un número mayor a 0';
                }
                break;

            case 'deposito':
                if (!empty($value) && (!is_numeric($value) || $value < 0)) {
                    $response['valid'] = false;
                    $response['message'] = 'El depósito debe ser un número mayor o igual a 0';
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

            case 'rental_conflict':
                $propertyId = (int)($data['id_inmueble'] ?? 0);
                $fechaInicio = $data['fecha_inicio'] ?? '';
                $fechaFin = $data['fecha_fin'] ?? '';
                $excludeId = (int)($data['exclude_id'] ?? 0);

                if ($propertyId > 0 && !empty($fechaInicio) && !empty($fechaFin)) {
                    $sql = "SELECT COUNT(*) FROM arriendo
                            WHERE id_inmueble = ?
                            AND estado = 'Activo'
                            AND id_arriendo != ?
                            AND ((fecha_inicio BETWEEN ? AND ?) OR (fecha_fin BETWEEN ? AND ?)
                                  OR (fecha_inicio <= ? AND fecha_fin >= ?))";
                    $params = [$propertyId, $excludeId, $fechaInicio, $fechaFin, $fechaInicio, $fechaFin, $fechaInicio, $fechaFin];

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $conflicts = $stmt->fetchColumn();

                    if ($conflicts > 0) {
                        $response['valid'] = false;
                        $response['message'] = 'Ya existe un arriendo activo para este inmueble en las fechas especificadas';
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
        $rentalId = (int)($data['id'] ?? 0);
        $estado = $data['estado'] ?? '';
        $dateFrom = $data['date_from'] ?? '';
        $dateTo = $data['date_to'] ?? '';

        $whereConditions = [];
        $params = [];

        if ($rentalId > 0) {
            // Export single rental
            $whereConditions[] = "a.id_arriendo = ?";
            $params[] = $rentalId;
        }

        if (!empty($estado)) {
            $whereConditions[] = "a.estado = ?";
            $params[] = $estado;
        }

        if (!empty($dateFrom)) {
            $whereConditions[] = "a.fecha_inicio >= ?";
            $params[] = $dateFrom;
        }

        if (!empty($dateTo)) {
            $whereConditions[] = "a.fecha_inicio <= ?";
            $params[] = $dateTo;
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        $stmt = $pdo->prepare("
            SELECT a.*,
                   i.tipo_inmueble, i.direccion, i.ciudad,
                   cl.nombre as cliente_nombre, cl.apellido as cliente_apellido,
                   ag.nombre as agente_nombre
            FROM arriendo a
            LEFT JOIN inmueble i ON a.id_inmueble = i.id_inmueble
            LEFT JOIN cliente cl ON a.id_cliente = cl.id_cliente
            LEFT JOIN agente ag ON a.id_agente = ag.id_agente
            {$whereClause}
            ORDER BY a.fecha_inicio DESC
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
        $rentalIds = $data['rental_ids'] ?? [];

        if (empty($rentalIds) || !is_array($rentalIds)) {
            throw new Exception('No se seleccionaron arriendos');
        }

        $rentalIds = array_map('intval', $rentalIds);
        $rentalIds = array_filter($rentalIds, function($id) { return $id > 0; });

        if (empty($rentalIds)) {
            throw new Exception('IDs de arriendo inválidos');
        }

        $results = [];

        switch ($action) {
            case 'delete':
                foreach ($rentalIds as $rentalId) {
                    $deleteResult = handleDelete($pdo, ['id' => $rentalId]);
                    $results[] = [
                        'id' => $rentalId,
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

                foreach ($rentalIds as $rentalId) {
                    $updateResult = handleUpdateStatus($pdo, ['id' => $rentalId, 'estado' => $newStatus]);
                    $results[] = [
                        'id' => $rentalId,
                        'success' => $updateResult['success'],
                        'message' => $updateResult['message']
                    ];
                }
                break;

            case 'export':
                $placeholders = str_repeat('?,', count($rentalIds) - 1) . '?';
                $stmt = $pdo->prepare("
                    SELECT a.*,
                           i.tipo_inmueble, i.direccion,
                           cl.nombre as cliente_nombre, cl.apellido as cliente_apellido
                    FROM arriendo a
                    LEFT JOIN inmueble i ON a.id_inmueble = i.id_inmueble
                    LEFT JOIN cliente cl ON a.id_cliente = cl.id_cliente
                    WHERE a.id_arriendo IN ({$placeholders})
                    ORDER BY a.fecha_inicio DESC
                ");
                $stmt->execute($rentalIds);
                $rentals = $stmt->fetchAll();

                return [
                    'success' => true,
                    'message' => 'Arriendos exportados exitosamente',
                    'data' => [
                        'rentals' => $rentals,
                        'count' => count($rentals)
                    ]
                ];

            default:
                throw new Exception('Acción en lote no válida');
        }

        $successCount = count(array_filter($results, function($r) { return $r['success']; }));
        $totalCount = count($results);

        return [
            'success' => $successCount > 0,
            'message' => "Procesados {$successCount} de {$totalCount} arriendos",
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
 * Handle rental statistics
 */
function handleStatistics($pdo, $data) {
    try {
        $stats = [];

        // Total rentals by status
        $stmt = $pdo->prepare("
            SELECT estado, COUNT(*) as count, COALESCE(SUM(canon_mensual), 0) as total_monthly
            FROM arriendo
            GROUP BY estado
        ");
        $stmt->execute();
        $stats['by_status'] = $stmt->fetchAll();

        // Active rentals count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM arriendo WHERE estado = 'Activo'");
        $stmt->execute();
        $stats['active_count'] = $stmt->fetchColumn();

        // Total monthly income from active rentals
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(canon_mensual), 0) FROM arriendo WHERE estado = 'Activo'");
        $stmt->execute();
        $stats['monthly_income'] = $stmt->fetchColumn();

        // Expiring rentals (within 30 days)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM arriendo
            WHERE estado = 'Activo'
            AND fecha_fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ");
        $stmt->execute();
        $stats['expiring_soon'] = $stmt->fetchColumn();

        // Average rental values
        $stmt = $pdo->prepare("
            SELECT AVG(canon_mensual) as avg_canon,
                   MIN(canon_mensual) as min_canon, MAX(canon_mensual) as max_canon
            FROM arriendo
        ");
        $stmt->execute();
        $stats['value_stats'] = $stmt->fetch();

        // Top agents by rentals
        $stmt = $pdo->prepare("
            SELECT ag.id_agente, ag.nombre, COUNT(a.id_arriendo) as rental_count,
                   COALESCE(SUM(a.canon_mensual), 0) as total_monthly
            FROM agente ag
            LEFT JOIN arriendo a ON ag.id_agente = a.id_agente
            WHERE ag.activo = 1
            GROUP BY ag.id_agente
            ORDER BY rental_count DESC
            LIMIT 5
        ");
        $stmt->execute();
        $stats['top_agents'] = $stmt->fetchAll();

        // Rentals by property type
        $stmt = $pdo->prepare("
            SELECT i.tipo_inmueble, COUNT(a.id_arriendo) as count,
                   COALESCE(SUM(a.canon_mensual), 0) as total_monthly
            FROM arriendo a
            JOIN inmueble i ON a.id_inmueble = i.id_inmueble
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
 * Handle rental status update
 */
function handleUpdateStatus($pdo, $data) {
    try {
        $rentalId = (int)($data['id'] ?? 0);
        $newStatus = $data['estado'] ?? '';

        if ($rentalId <= 0) {
            throw new Exception('ID de arriendo inválido');
        }

        if (empty($newStatus) || !array_key_exists($newStatus, RENTAL_STATUS)) {
            throw new Exception('Estado inválido');
        }

        // Check if rental exists
        $stmt = $pdo->prepare("SELECT estado FROM arriendo WHERE id_arriendo = ?");
        $stmt->execute([$rentalId]);
        $currentStatus = $stmt->fetchColumn();

        if ($currentStatus === false) {
            throw new Exception('Arriendo no encontrado');
        }

        // Validate status transition
        $validTransitions = [
            'Activo' => ['Terminado', 'Moroso', 'Vencido'],
            'Vencido' => ['Terminado', 'Activo'],
            'Moroso' => ['Activo', 'Terminado'],
            'Terminado' => []
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
        $stmt = $pdo->prepare("UPDATE arriendo SET estado = ?, updated_at = CURRENT_TIMESTAMP WHERE id_arriendo = ?");
        $result = $stmt->execute([$newStatus, $rentalId]);

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
 * Handle checking expiring rentals
 */
function handleCheckExpiring($pdo, $data) {
    try {
        $days = min((int)($data['days'] ?? 30), 90); // Max 90 days

        $stmt = $pdo->prepare("
            SELECT a.id_arriendo, a.fecha_fin, a.canon_mensual,
                   i.direccion, i.ciudad,
                   cl.nombre as cliente_nombre, cl.apellido as cliente_apellido,
                   DATEDIFF(a.fecha_fin, CURDATE()) as days_remaining
            FROM arriendo a
            JOIN inmueble i ON a.id_inmueble = i.id_inmueble
            JOIN cliente cl ON a.id_cliente = cl.id_cliente
            WHERE a.estado = 'Activo'
            AND a.fecha_fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
            ORDER BY a.fecha_fin ASC
        ");
        $stmt->execute([$days]);
        $expiringRentals = $stmt->fetchAll();

        return [
            'success' => true,
            'message' => 'Arriendos próximos a vencer obtenidos',
            'data' => [
                'rentals' => $expiringRentals,
                'count' => count($expiringRentals),
                'days' => $days
            ]
        ];

    } catch (PDOException $e) {
        error_log("Database error in handleCheckExpiring: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error de base de datos al verificar arriendos',
            'errors' => []
        ];
    }
}

/**
 * Handle payment registration
 */
function handleRegisterPayment($pdo, $data) {
    try {
        $rentalId = (int)($data['id_arriendo'] ?? 0);
        $amount = (float)($data['monto'] ?? 0);
        $paymentDate = $data['fecha_pago'] ?? date('Y-m-d');
        $paymentMethod = $data['metodo_pago'] ?? 'Efectivo';
        $reference = trim($data['referencia'] ?? '');

        if ($rentalId <= 0) {
            throw new Exception('ID de arriendo inválido');
        }

        if ($amount <= 0) {
            throw new Exception('El monto del pago debe ser mayor a 0');
        }

        // Note: Payment table creation is pending - this is a placeholder
        // In a complete implementation, we would insert into a 'pago' table here

        return [
            'success' => true,
            'message' => 'Funcionalidad de pagos en desarrollo. Los pagos se registrarán en futuras versiones.',
            'data' => [
                'rental_id' => $rentalId,
                'amount' => $amount,
                'date' => $paymentDate,
                'method' => $paymentMethod,
                'reference' => $reference
            ]
        ];

    } catch (Exception $e) {
        error_log("Error in handleRegisterPayment: " . $e->getMessage());
        return [
            'success' => false,
            'message' => $e->getMessage(),
            'errors' => []
        ];
    }
}
?>