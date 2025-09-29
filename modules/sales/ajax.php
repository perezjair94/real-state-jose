<?php
/**
 * Sales AJAX Operations - Real Estate Management System
 * Handle AJAX requests for sales CRUD operations
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

        default:
            throw new Exception('Acción no válida');
    }

} catch (Exception $e) {
    error_log("Sales AJAX Error: " . $e->getMessage());
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
 * Handle sale creation
 */
function handleCreate($pdo, $data) {
    try {
        // Validate required fields
        $required = ['fecha_venta', 'valor', 'id_inmueble', 'id_cliente'];
        $errors = [];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[] = "El campo {$field} es obligatorio";
            }
        }

        // Validate amounts
        if (!empty($data['valor']) && $data['valor'] <= 0) {
            $errors[] = "El valor de la venta debe ser mayor a 0";
        }

        if (!empty($data['comision']) && $data['comision'] < 0) {
            $errors[] = "La comisión no puede ser negativa";
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $errors
            ];
        }

        // Check if property exists and is available
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

        if ($propertyStatus === 'Vendido') {
            return [
                'success' => false,
                'message' => 'El inmueble ya ha sido vendido',
                'errors' => ['id_inmueble' => 'Inmueble no disponible']
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

        // Begin transaction
        $pdo->beginTransaction();

        try {
            // Insert new sale
            $stmt = $pdo->prepare("
                INSERT INTO venta (fecha_venta, valor, comision, observaciones, id_inmueble, id_cliente, id_agente)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $result = $stmt->execute([
                $data['fecha_venta'],
                $data['valor'],
                !empty($data['comision']) ? $data['comision'] : null,
                trim($data['observaciones'] ?? ''),
                $data['id_inmueble'],
                $data['id_cliente'],
                !empty($data['id_agente']) ? $data['id_agente'] : null
            ]);

            if ($result) {
                $saleId = $pdo->lastInsertId();

                // Update property status to Vendido (this should be done by trigger, but adding as backup)
                $stmt = $pdo->prepare("UPDATE inmueble SET estado = 'Vendido', updated_at = CURRENT_TIMESTAMP WHERE id_inmueble = ?");
                $stmt->execute([$data['id_inmueble']]);

                // Commit transaction
                $pdo->commit();

                // Get the created sale data
                $stmt = $pdo->prepare("SELECT * FROM venta WHERE id_venta = ?");
                $stmt->execute([$saleId]);
                $sale = $stmt->fetch();

                return [
                    'success' => true,
                    'message' => 'Venta creada exitosamente',
                    'data' => [
                        'id' => $saleId,
                        'sale' => $sale,
                        'display_id' => 'VEN' . str_pad($saleId, 3, '0', STR_PAD_LEFT)
                    ]
                ];
            } else {
                throw new Exception('Error al crear la venta');
            }

        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }

    } catch (PDOException $e) {
        error_log("Database error in handleCreate: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error de base de datos al crear la venta',
            'errors' => []
        ];
    }
}

/**
 * Handle sale update
 */
function handleUpdate($pdo, $data) {
    try {
        $saleId = (int)($data['id'] ?? 0);

        if ($saleId <= 0) {
            throw new Exception('ID de venta inválido');
        }

        // Check if sale exists
        $stmt = $pdo->prepare("SELECT * FROM venta WHERE id_venta = ?");
        $stmt->execute([$saleId]);
        $existingSale = $stmt->fetch();

        if (!$existingSale) {
            throw new Exception('Venta no encontrada');
        }

        // Validate required fields
        $required = ['fecha_venta', 'valor', 'id_inmueble', 'id_cliente'];
        $errors = [];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[] = "El campo {$field} es obligatorio";
            }
        }

        // Validate amounts
        if (!empty($data['valor']) && $data['valor'] <= 0) {
            $errors[] = "El valor de la venta debe ser mayor a 0";
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $errors
            ];
        }

        // Update sale
        $stmt = $pdo->prepare("
            UPDATE venta
            SET fecha_venta = ?, valor = ?, comision = ?, observaciones = ?,
                id_inmueble = ?, id_cliente = ?, id_agente = ?
            WHERE id_venta = ?
        ");

        $result = $stmt->execute([
            $data['fecha_venta'],
            $data['valor'],
            !empty($data['comision']) ? $data['comision'] : null,
            trim($data['observaciones'] ?? ''),
            $data['id_inmueble'],
            $data['id_cliente'],
            !empty($data['id_agente']) ? $data['id_agente'] : null,
            $saleId
        ]);

        if ($result) {
            // Get updated sale data
            $stmt = $pdo->prepare("SELECT * FROM venta WHERE id_venta = ?");
            $stmt->execute([$saleId]);
            $sale = $stmt->fetch();

            return [
                'success' => true,
                'message' => 'Venta actualizada exitosamente',
                'data' => [
                    'id' => $saleId,
                    'sale' => $sale
                ]
            ];
        } else {
            throw new Exception('Error al actualizar la venta');
        }

    } catch (PDOException $e) {
        error_log("Database error in handleUpdate: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error de base de datos al actualizar la venta',
            'errors' => []
        ];
    }
}

/**
 * Handle sale deletion
 */
function handleDelete($pdo, $data) {
    try {
        $saleId = (int)($data['id'] ?? 0);

        if ($saleId <= 0) {
            throw new Exception('ID de venta inválido');
        }

        // Check if sale exists
        $stmt = $pdo->prepare("SELECT * FROM venta WHERE id_venta = ?");
        $stmt->execute([$saleId]);
        $sale = $stmt->fetch();

        if (!$sale) {
            throw new Exception('Venta no encontrada');
        }

        // Check for related contracts
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM contrato WHERE id_inmueble = ? AND id_cliente = ? AND tipo_contrato = 'Venta'");
        $stmt->execute([$sale['id_inmueble'], $sale['id_cliente']]);
        $contractsCount = $stmt->fetchColumn();

        if ($contractsCount > 0) {
            return [
                'success' => false,
                'message' => "No se puede eliminar la venta porque tiene {$contractsCount} contrato(s) relacionado(s)",
                'data' => [
                    'dependencies' => ["{$contractsCount} contrato(s)"],
                    'can_force_delete' => false
                ]
            ];
        }

        // Begin transaction
        $pdo->beginTransaction();

        try {
            // Delete sale
            $stmt = $pdo->prepare("DELETE FROM venta WHERE id_venta = ?");
            $result = $stmt->execute([$saleId]);

            if ($result) {
                // Restore property status to Disponible
                $stmt = $pdo->prepare("UPDATE inmueble SET estado = 'Disponible', updated_at = CURRENT_TIMESTAMP WHERE id_inmueble = ?");
                $stmt->execute([$sale['id_inmueble']]);

                $pdo->commit();

                return [
                    'success' => true,
                    'message' => 'Venta eliminada exitosamente. El inmueble vuelve a estar disponible.',
                    'data' => [
                        'deleted_sale' => $sale
                    ]
                ];
            } else {
                throw new Exception('Error al eliminar la venta');
            }

        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }

    } catch (PDOException $e) {
        error_log("Database error in handleDelete: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error de base de datos al eliminar la venta',
            'errors' => []
        ];
    }
}

/**
 * Handle getting sale data
 */
function handleGet($pdo, $data) {
    try {
        $saleId = (int)($data['id'] ?? 0);

        if ($saleId <= 0) {
            throw new Exception('ID de venta inválido');
        }

        $stmt = $pdo->prepare("
            SELECT v.*,
                   i.tipo_inmueble, i.direccion, i.ciudad,
                   c.nombre as cliente_nombre, c.apellido as cliente_apellido,
                   a.nombre as agente_nombre
            FROM venta v
            LEFT JOIN inmueble i ON v.id_inmueble = i.id_inmueble
            LEFT JOIN cliente c ON v.id_cliente = c.id_cliente
            LEFT JOIN agente a ON v.id_agente = a.id_agente
            WHERE v.id_venta = ?
        ");
        $stmt->execute([$saleId]);
        $sale = $stmt->fetch();

        if (!$sale) {
            throw new Exception('Venta no encontrada');
        }

        return [
            'success' => true,
            'message' => 'Venta encontrada',
            'data' => [
                'sale' => $sale,
                'display_id' => 'VEN' . str_pad($sale['id_venta'], 3, '0', STR_PAD_LEFT)
            ]
        ];

    } catch (PDOException $e) {
        error_log("Database error in handleGet: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error de base de datos al obtener la venta',
            'errors' => []
        ];
    }
}

/**
 * Handle sale search
 */
function handleSearch($pdo, $data) {
    try {
        $searchTerm = trim($data['term'] ?? '');
        $limit = min((int)($data['limit'] ?? 10), 50);
        $dateFrom = $data['date_from'] ?? '';
        $dateTo = $data['date_to'] ?? '';
        $minValue = $data['min_value'] ?? '';
        $maxValue = $data['max_value'] ?? '';

        $whereConditions = [];
        $params = [];

        if (!empty($searchTerm)) {
            $whereConditions[] = "(i.direccion LIKE ? OR i.ciudad LIKE ? OR c.nombre LIKE ? OR c.apellido LIKE ?)";
            $searchWildcard = "%{$searchTerm}%";
            $params = array_merge($params, array_fill(0, 4, $searchWildcard));
        }

        if (!empty($dateFrom)) {
            $whereConditions[] = "v.fecha_venta >= ?";
            $params[] = $dateFrom;
        }

        if (!empty($dateTo)) {
            $whereConditions[] = "v.fecha_venta <= ?";
            $params[] = $dateTo;
        }

        if (!empty($minValue) && is_numeric($minValue)) {
            $whereConditions[] = "v.valor >= ?";
            $params[] = $minValue;
        }

        if (!empty($maxValue) && is_numeric($maxValue)) {
            $whereConditions[] = "v.valor <= ?";
            $params[] = $maxValue;
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        $sql = "SELECT v.id_venta, v.fecha_venta, v.valor, v.comision,
                       i.tipo_inmueble, i.direccion, i.ciudad,
                       c.nombre as cliente_nombre, c.apellido as cliente_apellido,
                       a.nombre as agente_nombre
                FROM venta v
                LEFT JOIN inmueble i ON v.id_inmueble = i.id_inmueble
                LEFT JOIN cliente c ON v.id_cliente = c.id_cliente
                LEFT JOIN agente a ON v.id_agente = a.id_agente
                {$whereClause}
                ORDER BY v.fecha_venta DESC
                LIMIT ?";

        $params[] = $limit;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $sales = $stmt->fetchAll();

        // Format results
        $results = array_map(function($sale) {
            return [
                'id' => $sale['id_venta'],
                'display_id' => 'VEN' . str_pad($sale['id_venta'], 3, '0', STR_PAD_LEFT),
                'date' => $sale['fecha_venta'],
                'value' => $sale['valor'],
                'commission' => $sale['comision'],
                'property' => $sale['tipo_inmueble'] . ' - ' . $sale['direccion'] . ', ' . $sale['ciudad'],
                'client' => $sale['cliente_nombre'] . ' ' . $sale['cliente_apellido'],
                'agent' => $sale['agente_nombre'] ?: 'Sin agente',
                'label' => 'VEN' . str_pad($sale['id_venta'], 3, '0', STR_PAD_LEFT') . ' - ' .
                          $sale['tipo_inmueble'] . ' - ' . number_format($sale['valor'], 0, ',', '.')
            ];
        }, $sales);

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
            case 'valor':
                if (!empty($value) && (!is_numeric($value) || $value <= 0)) {
                    $response['valid'] = false;
                    $response['message'] = 'El valor debe ser un número mayor a 0';
                }
                break;

            case 'comision':
                if (!empty($value) && (!is_numeric($value) || $value < 0)) {
                    $response['valid'] = false;
                    $response['message'] = 'La comisión debe ser un número mayor o igual a 0';
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
                    } elseif ($status === 'Vendido') {
                        $response['valid'] = false;
                        $response['message'] = 'El inmueble ya ha sido vendido';
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
        $saleId = (int)($data['id'] ?? 0);
        $dateFrom = $data['date_from'] ?? '';
        $dateTo = $data['date_to'] ?? '';

        $whereConditions = [];
        $params = [];

        if ($saleId > 0) {
            // Export single sale
            $whereConditions[] = "v.id_venta = ?";
            $params[] = $saleId;
        }

        if (!empty($dateFrom)) {
            $whereConditions[] = "v.fecha_venta >= ?";
            $params[] = $dateFrom;
        }

        if (!empty($dateTo)) {
            $whereConditions[] = "v.fecha_venta <= ?";
            $params[] = $dateTo;
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        $stmt = $pdo->prepare("
            SELECT v.*,
                   i.tipo_inmueble, i.direccion, i.ciudad,
                   c.nombre as cliente_nombre, c.apellido as cliente_apellido,
                   a.nombre as agente_nombre
            FROM venta v
            LEFT JOIN inmueble i ON v.id_inmueble = i.id_inmueble
            LEFT JOIN cliente c ON v.id_cliente = c.id_cliente
            LEFT JOIN agente a ON v.id_agente = a.id_agente
            {$whereClause}
            ORDER BY v.fecha_venta DESC
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
        $saleIds = $data['sale_ids'] ?? [];

        if (empty($saleIds) || !is_array($saleIds)) {
            throw new Exception('No se seleccionaron ventas');
        }

        $saleIds = array_map('intval', $saleIds);
        $saleIds = array_filter($saleIds, function($id) { return $id > 0; });

        if (empty($saleIds)) {
            throw new Exception('IDs de venta inválidos');
        }

        $results = [];

        switch ($action) {
            case 'delete':
                foreach ($saleIds as $saleId) {
                    $deleteResult = handleDelete($pdo, ['id' => $saleId]);
                    $results[] = [
                        'id' => $saleId,
                        'success' => $deleteResult['success'],
                        'message' => $deleteResult['message']
                    ];
                }
                break;

            case 'export':
                $placeholders = str_repeat('?,', count($saleIds) - 1) . '?';
                $stmt = $pdo->prepare("
                    SELECT v.*,
                           i.tipo_inmueble, i.direccion,
                           c.nombre as cliente_nombre, c.apellido as cliente_apellido
                    FROM venta v
                    LEFT JOIN inmueble i ON v.id_inmueble = i.id_inmueble
                    LEFT JOIN cliente c ON v.id_cliente = c.id_cliente
                    WHERE v.id_venta IN ({$placeholders})
                    ORDER BY v.fecha_venta DESC
                ");
                $stmt->execute($saleIds);
                $sales = $stmt->fetchAll();

                return [
                    'success' => true,
                    'message' => 'Ventas exportadas exitosamente',
                    'data' => [
                        'sales' => $sales,
                        'count' => count($sales)
                    ]
                ];

            default:
                throw new Exception('Acción en lote no válida');
        }

        $successCount = count(array_filter($results, function($r) { return $r['success']; }));
        $totalCount = count($results);

        return [
            'success' => $successCount > 0,
            'message' => "Procesadas {$successCount} de {$totalCount} ventas",
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
 * Handle sales statistics
 */
function handleStatistics($pdo, $data) {
    try {
        $period = $data['period'] ?? 'all'; // all, year, month, week
        $year = $data['year'] ?? date('Y');
        $month = $data['month'] ?? date('m');

        $stats = [];

        // Total sales
        $stmt = $pdo->prepare("SELECT COUNT(*) as count, COALESCE(SUM(valor), 0) as total_value, COALESCE(SUM(comision), 0) as total_commission FROM venta");
        $stmt->execute();
        $stats['total'] = $stmt->fetch();

        // Sales by period
        switch ($period) {
            case 'year':
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as count, COALESCE(SUM(valor), 0) as total_value
                    FROM venta
                    WHERE YEAR(fecha_venta) = ?
                ");
                $stmt->execute([$year]);
                $stats['period'] = $stmt->fetch();
                break;

            case 'month':
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as count, COALESCE(SUM(valor), 0) as total_value
                    FROM venta
                    WHERE YEAR(fecha_venta) = ? AND MONTH(fecha_venta) = ?
                ");
                $stmt->execute([$year, $month]);
                $stats['period'] = $stmt->fetch();
                break;
        }

        // Top agents by sales
        $stmt = $pdo->prepare("
            SELECT a.id_agente, a.nombre, COUNT(v.id_venta) as sales_count,
                   COALESCE(SUM(v.valor), 0) as total_value,
                   COALESCE(SUM(v.comision), 0) as total_commission
            FROM agente a
            LEFT JOIN venta v ON a.id_agente = v.id_agente
            WHERE a.activo = 1
            GROUP BY a.id_agente
            ORDER BY total_value DESC
            LIMIT 5
        ");
        $stmt->execute();
        $stats['top_agents'] = $stmt->fetchAll();

        // Sales by property type
        $stmt = $pdo->prepare("
            SELECT i.tipo_inmueble, COUNT(v.id_venta) as count, COALESCE(SUM(v.valor), 0) as total_value
            FROM venta v
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
?>