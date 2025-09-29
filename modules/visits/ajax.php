<?php
/**
 * Visits AJAX Operations - Real Estate Management System
 * Handle AJAX requests for visits CRUD operations
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

        case 'getToday':
            $response = handleGetToday($pdo, $_GET);
            break;

        case 'getUpcoming':
            $response = handleGetUpcoming($pdo, $_GET);
            break;

        default:
            throw new Exception('Acción no válida');
    }

} catch (Exception $e) {
    error_log("Visits AJAX Error: " . $e->getMessage());
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
 * Handle visit creation
 */
function handleCreate($pdo, $data) {
    try {
        // Validate required fields
        $required = ['fecha_visita', 'hora_visita', 'estado', 'id_inmueble', 'id_cliente', 'id_agente'];
        $errors = [];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[] = "El campo {$field} es obligatorio";
            }
        }

        // Validate date is not in the past
        if (!empty($data['fecha_visita']) && $data['fecha_visita'] < date('Y-m-d')) {
            $errors[] = "La fecha de visita no puede ser anterior a hoy";
        }

        // Validate business hours
        if (!empty($data['hora_visita'])) {
            $hour = (int)substr($data['hora_visita'], 0, 2);
            if ($hour < 8 || $hour > 18) {
                $errors[] = "Las visitas solo se pueden programar entre 8:00 AM y 6:00 PM";
            }
        }

        // Validate status
        if (!empty($data['estado']) && !array_key_exists($data['estado'], VISIT_STATUS)) {
            $errors[] = "Estado de visita inválido";
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

        // Check if agent exists and is active
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

        if (!$agentActive) {
            return [
                'success' => false,
                'message' => 'El agente seleccionado no está activo',
                'errors' => ['id_agente' => 'Agente inactivo']
            ];
        }

        // Insert new visit
        $stmt = $pdo->prepare("
            INSERT INTO visita (fecha_visita, hora_visita, estado, calificacion,
                               observaciones, id_inmueble, id_cliente, id_agente)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $result = $stmt->execute([
            $data['fecha_visita'],
            $data['hora_visita'],
            $data['estado'],
            trim($data['calificacion'] ?? ''),
            trim($data['observaciones'] ?? ''),
            $data['id_inmueble'],
            $data['id_cliente'],
            $data['id_agente']
        ]);

        if ($result) {
            $visitId = $pdo->lastInsertId();

            // Get the created visit data
            $stmt = $pdo->prepare("SELECT * FROM visita WHERE id_visita = ?");
            $stmt->execute([$visitId]);
            $visit = $stmt->fetch();

            return [
                'success' => true,
                'message' => 'Visita creada exitosamente',
                'data' => [
                    'id' => $visitId,
                    'visit' => $visit,
                    'display_id' => 'VIS' . str_pad($visitId, 3, '0', STR_PAD_LEFT)
                ]
            ];
        } else {
            throw new Exception('Error al crear la visita');
        }

    } catch (PDOException $e) {
        error_log("Database error in handleCreate: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error de base de datos al crear la visita',
            'errors' => []
        ];
    }
}

/**
 * Handle visit update
 */
function handleUpdate($pdo, $data) {
    try {
        $visitId = (int)($data['id'] ?? 0);

        if ($visitId <= 0) {
            throw new Exception('ID de visita inválido');
        }

        // Check if visit exists
        $stmt = $pdo->prepare("SELECT * FROM visita WHERE id_visita = ?");
        $stmt->execute([$visitId]);
        $existingVisit = $stmt->fetch();

        if (!$existingVisit) {
            throw new Exception('Visita no encontrada');
        }

        // Validate required fields
        $required = ['fecha_visita', 'hora_visita', 'estado', 'id_inmueble', 'id_cliente', 'id_agente'];
        $errors = [];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[] = "El campo {$field} es obligatorio";
            }
        }

        // Validate business hours
        if (!empty($data['hora_visita'])) {
            $hour = (int)substr($data['hora_visita'], 0, 2);
            if ($hour < 8 || $hour > 18) {
                $errors[] = "Las visitas solo se pueden programar entre 8:00 AM y 6:00 PM";
            }
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $errors
            ];
        }

        // Update visit
        $stmt = $pdo->prepare("
            UPDATE visita
            SET fecha_visita = ?, hora_visita = ?, estado = ?, calificacion = ?,
                observaciones = ?, id_inmueble = ?, id_cliente = ?, id_agente = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id_visita = ?
        ");

        $result = $stmt->execute([
            $data['fecha_visita'],
            $data['hora_visita'],
            $data['estado'],
            trim($data['calificacion'] ?? ''),
            trim($data['observaciones'] ?? ''),
            $data['id_inmueble'],
            $data['id_cliente'],
            $data['id_agente'],
            $visitId
        ]);

        if ($result) {
            // Get updated visit data
            $stmt = $pdo->prepare("SELECT * FROM visita WHERE id_visita = ?");
            $stmt->execute([$visitId]);
            $visit = $stmt->fetch();

            return [
                'success' => true,
                'message' => 'Visita actualizada exitosamente',
                'data' => [
                    'id' => $visitId,
                    'visit' => $visit
                ]
            ];
        } else {
            throw new Exception('Error al actualizar la visita');
        }

    } catch (PDOException $e) {
        error_log("Database error in handleUpdate: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error de base de datos al actualizar la visita',
            'errors' => []
        ];
    }
}

/**
 * Handle visit deletion
 */
function handleDelete($pdo, $data) {
    try {
        $visitId = (int)($data['id'] ?? 0);

        if ($visitId <= 0) {
            throw new Exception('ID de visita inválido');
        }

        // Check if visit exists
        $stmt = $pdo->prepare("SELECT * FROM visita WHERE id_visita = ?");
        $stmt->execute([$visitId]);
        $visit = $stmt->fetch();

        if (!$visit) {
            throw new Exception('Visita no encontrada');
        }

        // Delete visit
        $stmt = $pdo->prepare("DELETE FROM visita WHERE id_visita = ?");
        $result = $stmt->execute([$visitId]);

        if ($result) {
            return [
                'success' => true,
                'message' => 'Visita eliminada exitosamente',
                'data' => [
                    'deleted_visit' => $visit
                ]
            ];
        } else {
            throw new Exception('Error al eliminar la visita');
        }

    } catch (PDOException $e) {
        error_log("Database error in handleDelete: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error de base de datos al eliminar la visita',
            'errors' => []
        ];
    }
}

/**
 * Handle getting visit data
 */
function handleGet($pdo, $data) {
    try {
        $visitId = (int)($data['id'] ?? 0);

        if ($visitId <= 0) {
            throw new Exception('ID de visita inválido');
        }

        $stmt = $pdo->prepare("
            SELECT v.*,
                   i.tipo_inmueble, i.direccion, i.ciudad,
                   cl.nombre as cliente_nombre, cl.apellido as cliente_apellido,
                   a.nombre as agente_nombre
            FROM visita v
            LEFT JOIN inmueble i ON v.id_inmueble = i.id_inmueble
            LEFT JOIN cliente cl ON v.id_cliente = cl.id_cliente
            LEFT JOIN agente a ON v.id_agente = a.id_agente
            WHERE v.id_visita = ?
        ");
        $stmt->execute([$visitId]);
        $visit = $stmt->fetch();

        if (!$visit) {
            throw new Exception('Visita no encontrada');
        }

        return [
            'success' => true,
            'message' => 'Visita encontrada',
            'data' => [
                'visit' => $visit,
                'display_id' => 'VIS' . str_pad($visit['id_visita'], 3, '0', STR_PAD_LEFT)
            ]
        ];

    } catch (PDOException $e) {
        error_log("Database error in handleGet: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error de base de datos al obtener la visita',
            'errors' => []
        ];
    }
}

/**
 * Handle visit search
 */
function handleSearch($pdo, $data) {
    try {
        $searchTerm = trim($data['term'] ?? '');
        $limit = min((int)($data['limit'] ?? 10), 50);
        $estado = $data['estado'] ?? '';
        $agenteId = (int)($data['agente_id'] ?? 0);
        $dateFrom = $data['date_from'] ?? '';
        $dateTo = $data['date_to'] ?? '';

        $whereConditions = [];
        $params = [];

        if (!empty($searchTerm)) {
            $whereConditions[] = "(i.direccion LIKE ? OR i.ciudad LIKE ? OR cl.nombre LIKE ? OR cl.apellido LIKE ? OR v.observaciones LIKE ?)";
            $searchWildcard = "%{$searchTerm}%";
            $params = array_merge($params, array_fill(0, 5, $searchWildcard));
        }

        if (!empty($estado)) {
            $whereConditions[] = "v.estado = ?";
            $params[] = $estado;
        }

        if ($agenteId > 0) {
            $whereConditions[] = "v.id_agente = ?";
            $params[] = $agenteId;
        }

        if (!empty($dateFrom)) {
            $whereConditions[] = "v.fecha_visita >= ?";
            $params[] = $dateFrom;
        }

        if (!empty($dateTo)) {
            $whereConditions[] = "v.fecha_visita <= ?";
            $params[] = $dateTo;
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        $sql = "SELECT v.id_visita, v.fecha_visita, v.hora_visita, v.estado, v.calificacion,
                       i.tipo_inmueble, i.direccion, i.ciudad,
                       cl.nombre as cliente_nombre, cl.apellido as cliente_apellido,
                       a.nombre as agente_nombre
                FROM visita v
                LEFT JOIN inmueble i ON v.id_inmueble = i.id_inmueble
                LEFT JOIN cliente cl ON v.id_cliente = cl.id_cliente
                LEFT JOIN agente a ON v.id_agente = a.id_agente
                {$whereClause}
                ORDER BY v.fecha_visita ASC, v.hora_visita ASC
                LIMIT ?";

        $params[] = $limit;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $visits = $stmt->fetchAll();

        // Format results
        $results = array_map(function($visit) {
            return [
                'id' => $visit['id_visita'],
                'display_id' => 'VIS' . str_pad($visit['id_visita'], 3, '0', STR_PAD_LEFT),
                'fecha' => $visit['fecha_visita'],
                'hora' => $visit['hora_visita'],
                'estado' => $visit['estado'],
                'calificacion' => $visit['calificacion'],
                'property' => $visit['tipo_inmueble'] . ' - ' . $visit['direccion'] . ', ' . $visit['ciudad'],
                'client' => $visit['cliente_nombre'] . ' ' . $visit['cliente_apellido'],
                'agent' => $visit['agente_nombre'],
                'label' => 'VIS' . str_pad($visit['id_visita'], 3, '0', STR_PAD_LEFT) . ' - ' .
                          $visit['fecha_visita'] . ' - ' . $visit['tipo_inmueble']
            ];
        }, $visits);

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
            case 'fecha_visita':
                if (!empty($value) && $value < date('Y-m-d')) {
                    $response['valid'] = false;
                    $response['message'] = 'La fecha de visita no puede ser anterior a hoy';
                }
                break;

            case 'hora_visita':
                if (!empty($value)) {
                    $hour = (int)substr($value, 0, 2);
                    if ($hour < 8 || $hour > 18) {
                        $response['valid'] = false;
                        $response['message'] = 'Las visitas solo se pueden programar entre 8:00 AM y 6:00 PM';
                    }
                }
                break;

            case 'agent_availability':
                $agenteId = (int)($data['agente_id'] ?? 0);
                $fecha = $data['fecha'] ?? '';
                $hora = $data['hora'] ?? '';
                $excludeId = (int)($data['exclude_id'] ?? 0);

                if ($agenteId > 0 && !empty($fecha) && !empty($hora)) {
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) FROM visita
                        WHERE id_agente = ?
                        AND fecha_visita = ?
                        AND hora_visita = ?
                        AND estado IN ('Programada', 'Reprogramada')
                        AND id_visita != ?
                    ");
                    $stmt->execute([$agenteId, $fecha, $hora, $excludeId]);
                    $conflicts = $stmt->fetchColumn();

                    if ($conflicts > 0) {
                        $response['valid'] = false;
                        $response['message'] = 'El agente ya tiene una visita programada para esa fecha y hora';
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
        $visitId = (int)($data['id'] ?? 0);
        $estado = $data['estado'] ?? '';
        $dateFrom = $data['date_from'] ?? '';
        $dateTo = $data['date_to'] ?? '';

        $whereConditions = [];
        $params = [];

        if ($visitId > 0) {
            // Export single visit
            $whereConditions[] = "v.id_visita = ?";
            $params[] = $visitId;
        }

        if (!empty($estado)) {
            $whereConditions[] = "v.estado = ?";
            $params[] = $estado;
        }

        if (!empty($dateFrom)) {
            $whereConditions[] = "v.fecha_visita >= ?";
            $params[] = $dateFrom;
        }

        if (!empty($dateTo)) {
            $whereConditions[] = "v.fecha_visita <= ?";
            $params[] = $dateTo;
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        $stmt = $pdo->prepare("
            SELECT v.*,
                   i.tipo_inmueble, i.direccion, i.ciudad,
                   cl.nombre as cliente_nombre, cl.apellido as cliente_apellido,
                   a.nombre as agente_nombre
            FROM visita v
            LEFT JOIN inmueble i ON v.id_inmueble = i.id_inmueble
            LEFT JOIN cliente cl ON v.id_cliente = cl.id_cliente
            LEFT JOIN agente a ON v.id_agente = a.id_agente
            {$whereClause}
            ORDER BY v.fecha_visita ASC, v.hora_visita ASC
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
        $visitIds = $data['visit_ids'] ?? [];

        if (empty($visitIds) || !is_array($visitIds)) {
            throw new Exception('No se seleccionaron visitas');
        }

        $visitIds = array_map('intval', $visitIds);
        $visitIds = array_filter($visitIds, function($id) { return $id > 0; });

        if (empty($visitIds)) {
            throw new Exception('IDs de visita inválidos');
        }

        $results = [];

        switch ($action) {
            case 'delete':
                foreach ($visitIds as $visitId) {
                    $deleteResult = handleDelete($pdo, ['id' => $visitId]);
                    $results[] = [
                        'id' => $visitId,
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

                foreach ($visitIds as $visitId) {
                    $updateResult = handleUpdateStatus($pdo, ['id' => $visitId, 'estado' => $newStatus]);
                    $results[] = [
                        'id' => $visitId,
                        'success' => $updateResult['success'],
                        'message' => $updateResult['message']
                    ];
                }
                break;

            case 'export':
                $placeholders = str_repeat('?,', count($visitIds) - 1) . '?';
                $stmt = $pdo->prepare("
                    SELECT v.*,
                           i.tipo_inmueble, i.direccion,
                           cl.nombre as cliente_nombre, cl.apellido as cliente_apellido
                    FROM visita v
                    LEFT JOIN inmueble i ON v.id_inmueble = i.id_inmueble
                    LEFT JOIN cliente cl ON v.id_cliente = cl.id_cliente
                    WHERE v.id_visita IN ({$placeholders})
                    ORDER BY v.fecha_visita ASC
                ");
                $stmt->execute($visitIds);
                $visits = $stmt->fetchAll();

                return [
                    'success' => true,
                    'message' => 'Visitas exportadas exitosamente',
                    'data' => [
                        'visits' => $visits,
                        'count' => count($visits)
                    ]
                ];

            default:
                throw new Exception('Acción en lote no válida');
        }

        $successCount = count(array_filter($results, function($r) { return $r['success']; }));
        $totalCount = count($results);

        return [
            'success' => $successCount > 0,
            'message' => "Procesadas {$successCount} de {$totalCount} visitas",
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
 * Handle visit statistics
 */
function handleStatistics($pdo, $data) {
    try {
        $stats = [];

        // Total visits by status
        $stmt = $pdo->prepare("
            SELECT estado, COUNT(*) as count
            FROM visita
            GROUP BY estado
        ");
        $stmt->execute();
        $stats['by_status'] = $stmt->fetchAll();

        // Visits today
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM visita WHERE fecha_visita = CURDATE()");
        $stmt->execute();
        $stats['today'] = $stmt->fetchColumn();

        // Visits this week
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM visita
            WHERE YEARWEEK(fecha_visita, 1) = YEARWEEK(CURDATE(), 1)
        ");
        $stmt->execute();
        $stats['this_week'] = $stmt->fetchColumn();

        // Upcoming visits (next 7 days)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM visita
            WHERE fecha_visita BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            AND estado IN ('Programada', 'Reprogramada')
        ");
        $stmt->execute();
        $stats['upcoming'] = $stmt->fetchColumn();

        // Visits by interest level
        $stmt = $pdo->prepare("
            SELECT calificacion, COUNT(*) as count
            FROM visita
            WHERE calificacion IS NOT NULL AND calificacion != ''
            GROUP BY calificacion
            ORDER BY count DESC
        ");
        $stmt->execute();
        $stats['by_interest'] = $stmt->fetchAll();

        // Top agents by visits
        $stmt = $pdo->prepare("
            SELECT a.id_agente, a.nombre, COUNT(v.id_visita) as visit_count
            FROM agente a
            LEFT JOIN visita v ON a.id_agente = v.id_agente
            WHERE a.activo = 1
            GROUP BY a.id_agente
            ORDER BY visit_count DESC
            LIMIT 5
        ");
        $stmt->execute();
        $stats['top_agents'] = $stmt->fetchAll();

        // Visits by property type
        $stmt = $pdo->prepare("
            SELECT i.tipo_inmueble, COUNT(v.id_visita) as count
            FROM visita v
            JOIN inmueble i ON v.id_inmueble = i.id_inmueble
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
 * Handle visit status update
 */
function handleUpdateStatus($pdo, $data) {
    try {
        $visitId = (int)($data['id'] ?? 0);
        $newStatus = $data['estado'] ?? '';

        if ($visitId <= 0) {
            throw new Exception('ID de visita inválido');
        }

        if (empty($newStatus) || !array_key_exists($newStatus, VISIT_STATUS)) {
            throw new Exception('Estado inválido');
        }

        // Check if visit exists
        $stmt = $pdo->prepare("SELECT estado FROM visita WHERE id_visita = ?");
        $stmt->execute([$visitId]);
        $currentStatus = $stmt->fetchColumn();

        if ($currentStatus === false) {
            throw new Exception('Visita no encontrada');
        }

        // Update status
        $stmt = $pdo->prepare("UPDATE visita SET estado = ?, updated_at = CURRENT_TIMESTAMP WHERE id_visita = ?");
        $result = $stmt->execute([$newStatus, $visitId]);

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
 * Handle getting today's visits
 */
function handleGetToday($pdo, $data) {
    try {
        $stmt = $pdo->prepare("
            SELECT v.id_visita, v.fecha_visita, v.hora_visita, v.estado,
                   i.tipo_inmueble, i.direccion,
                   cl.nombre as cliente_nombre, cl.apellido as cliente_apellido,
                   a.nombre as agente_nombre
            FROM visita v
            JOIN inmueble i ON v.id_inmueble = i.id_inmueble
            JOIN cliente cl ON v.id_cliente = cl.id_cliente
            JOIN agente a ON v.id_agente = a.id_agente
            WHERE v.fecha_visita = CURDATE()
            ORDER BY v.hora_visita ASC
        ");
        $stmt->execute();
        $todayVisits = $stmt->fetchAll();

        return [
            'success' => true,
            'message' => 'Visitas de hoy obtenidas',
            'data' => [
                'visits' => $todayVisits,
                'count' => count($todayVisits)
            ]
        ];

    } catch (PDOException $e) {
        error_log("Database error in handleGetToday: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error de base de datos al obtener visitas de hoy',
            'errors' => []
        ];
    }
}

/**
 * Handle getting upcoming visits
 */
function handleGetUpcoming($pdo, $data) {
    try {
        $days = min((int)($data['days'] ?? 7), 30); // Max 30 days

        $stmt = $pdo->prepare("
            SELECT v.id_visita, v.fecha_visita, v.hora_visita, v.estado,
                   i.direccion, i.ciudad,
                   cl.nombre as cliente_nombre, cl.apellido as cliente_apellido,
                   a.nombre as agente_nombre,
                   DATEDIFF(v.fecha_visita, CURDATE()) as days_until
            FROM visita v
            JOIN inmueble i ON v.id_inmueble = i.id_inmueble
            JOIN cliente cl ON v.id_cliente = cl.id_cliente
            JOIN agente a ON v.id_agente = a.id_agente
            WHERE v.fecha_visita BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
            AND v.estado IN ('Programada', 'Reprogramada')
            ORDER BY v.fecha_visita ASC, v.hora_visita ASC
        ");
        $stmt->execute([$days]);
        $upcomingVisits = $stmt->fetchAll();

        return [
            'success' => true,
            'message' => 'Visitas próximas obtenidas',
            'data' => [
                'visits' => $upcomingVisits,
                'count' => count($upcomingVisits),
                'days' => $days
            ]
        ];

    } catch (PDOException $e) {
        error_log("Database error in handleGetUpcoming: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error de base de datos al obtener visitas próximas',
            'errors' => []
        ];
    }
}
?>