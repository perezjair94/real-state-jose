<?php
/**
 * Client AJAX Operations - Real Estate Management System
 * Handle AJAX requests for client CRUD operations
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

        default:
            throw new Exception('Acción no válida');
    }

} catch (Exception $e) {
    error_log("Client AJAX Error: " . $e->getMessage());
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
 * Handle client creation
 */
function handleCreate($pdo, $data) {
    try {
        // Validate required fields
        $required = ['nombre', 'apellido', 'tipo_documento', 'nro_documento', 'correo', 'tipo_cliente'];
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

        // Validate document type
        if (!empty($data['tipo_documento']) && !array_key_exists($data['tipo_documento'], DOCUMENT_TYPES)) {
            $errors[] = "Tipo de documento inválido";
        }

        // Validate client type
        if (!empty($data['tipo_cliente']) && !array_key_exists($data['tipo_cliente'], CLIENT_TYPES)) {
            $errors[] = "Tipo de cliente inválido";
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $errors
            ];
        }

        // Check for duplicate document number
        $stmt = $pdo->prepare("SELECT id_cliente FROM cliente WHERE nro_documento = ?");
        $stmt->execute([$data['nro_documento']]);
        if ($stmt->fetchColumn()) {
            return [
                'success' => false,
                'message' => 'Ya existe un cliente con este número de documento',
                'errors' => ['nro_documento' => 'Número de documento duplicado']
            ];
        }

        // Check for duplicate email
        $stmt = $pdo->prepare("SELECT id_cliente FROM cliente WHERE correo = ?");
        $stmt->execute([$data['correo']]);
        if ($stmt->fetchColumn()) {
            return [
                'success' => false,
                'message' => 'Ya existe un cliente con este email',
                'errors' => ['correo' => 'Email duplicado']
            ];
        }

        // Insert new client
        $stmt = $pdo->prepare("
            INSERT INTO cliente (nombre, apellido, tipo_documento, nro_documento, correo, direccion, tipo_cliente)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $result = $stmt->execute([
            trim($data['nombre']),
            trim($data['apellido']),
            $data['tipo_documento'],
            trim($data['nro_documento']),
            trim($data['correo']),
            trim($data['direccion'] ?? ''),
            $data['tipo_cliente']
        ]);

        if ($result) {
            $clientId = $pdo->lastInsertId();

            // Get the created client data
            $stmt = $pdo->prepare("SELECT * FROM cliente WHERE id_cliente = ?");
            $stmt->execute([$clientId]);
            $client = $stmt->fetch();

            return [
                'success' => true,
                'message' => 'Cliente creado exitosamente',
                'data' => [
                    'id' => $clientId,
                    'client' => $client,
                    'display_id' => 'CLI' . str_pad($clientId, 3, '0', STR_PAD_LEFT)
                ]
            ];
        } else {
            throw new Exception('Error al crear el cliente');
        }

    } catch (PDOException $e) {
        error_log("Database error in handleCreate: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error de base de datos al crear el cliente',
            'errors' => []
        ];
    }
}

/**
 * Handle client update
 */
function handleUpdate($pdo, $data) {
    try {
        $clientId = (int)($data['id'] ?? 0);

        if ($clientId <= 0) {
            throw new Exception('ID de cliente inválido');
        }

        // Check if client exists
        $stmt = $pdo->prepare("SELECT id_cliente FROM cliente WHERE id_cliente = ?");
        $stmt->execute([$clientId]);
        if (!$stmt->fetchColumn()) {
            throw new Exception('Cliente no encontrado');
        }

        // Validate required fields
        $required = ['nombre', 'apellido', 'tipo_documento', 'nro_documento', 'correo', 'tipo_cliente'];
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

        // Check for duplicate document number (excluding current client)
        $stmt = $pdo->prepare("SELECT id_cliente FROM cliente WHERE nro_documento = ? AND id_cliente != ?");
        $stmt->execute([$data['nro_documento'], $clientId]);
        if ($stmt->fetchColumn()) {
            return [
                'success' => false,
                'message' => 'Ya existe otro cliente con este número de documento',
                'errors' => ['nro_documento' => 'Número de documento duplicado']
            ];
        }

        // Check for duplicate email (excluding current client)
        $stmt = $pdo->prepare("SELECT id_cliente FROM cliente WHERE correo = ? AND id_cliente != ?");
        $stmt->execute([$data['correo'], $clientId]);
        if ($stmt->fetchColumn()) {
            return [
                'success' => false,
                'message' => 'Ya existe otro cliente con este email',
                'errors' => ['correo' => 'Email duplicado']
            ];
        }

        // Update client
        $stmt = $pdo->prepare("
            UPDATE cliente
            SET nombre = ?, apellido = ?, tipo_documento = ?, nro_documento = ?,
                correo = ?, direccion = ?, tipo_cliente = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id_cliente = ?
        ");

        $result = $stmt->execute([
            trim($data['nombre']),
            trim($data['apellido']),
            $data['tipo_documento'],
            trim($data['nro_documento']),
            trim($data['correo']),
            trim($data['direccion'] ?? ''),
            $data['tipo_cliente'],
            $clientId
        ]);

        if ($result) {
            // Get updated client data
            $stmt = $pdo->prepare("SELECT * FROM cliente WHERE id_cliente = ?");
            $stmt->execute([$clientId]);
            $client = $stmt->fetch();

            return [
                'success' => true,
                'message' => 'Cliente actualizado exitosamente',
                'data' => [
                    'id' => $clientId,
                    'client' => $client
                ]
            ];
        } else {
            throw new Exception('Error al actualizar el cliente');
        }

    } catch (PDOException $e) {
        error_log("Database error in handleUpdate: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error de base de datos al actualizar el cliente',
            'errors' => []
        ];
    }
}

/**
 * Handle client deletion
 */
function handleDelete($pdo, $data) {
    try {
        $clientId = (int)($data['id'] ?? 0);

        if ($clientId <= 0) {
            throw new Exception('ID de cliente inválido');
        }

        // Check if client exists
        $stmt = $pdo->prepare("SELECT * FROM cliente WHERE id_cliente = ?");
        $stmt->execute([$clientId]);
        $client = $stmt->fetch();

        if (!$client) {
            throw new Exception('Cliente no encontrado');
        }

        // Check for related records that would prevent deletion
        $dependencies = [];

        // Check sales
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM venta WHERE id_cliente = ?");
        $stmt->execute([$clientId]);
        $salesCount = $stmt->fetchColumn();
        if ($salesCount > 0) {
            $dependencies[] = "{$salesCount} venta(s)";
        }

        // Check contracts
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM contrato WHERE id_cliente = ?");
        $stmt->execute([$clientId]);
        $contractsCount = $stmt->fetchColumn();
        if ($contractsCount > 0) {
            $dependencies[] = "{$contractsCount} contrato(s)";
        }

        // Check rentals
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM arriendo WHERE id_cliente = ?");
        $stmt->execute([$clientId]);
        $rentalsCount = $stmt->fetchColumn();
        if ($rentalsCount > 0) {
            $dependencies[] = "{$rentalsCount} arriendo(s)";
        }

        // Check visits
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM visita WHERE id_cliente = ?");
        $stmt->execute([$clientId]);
        $visitsCount = $stmt->fetchColumn();
        if ($visitsCount > 0) {
            $dependencies[] = "{$visitsCount} visita(s)";
        }

        if (!empty($dependencies)) {
            return [
                'success' => false,
                'message' => 'No se puede eliminar el cliente porque tiene transacciones relacionadas: ' . implode(', ', $dependencies),
                'data' => [
                    'dependencies' => $dependencies,
                    'can_force_delete' => false // Set to true if you want to allow force deletion
                ]
            ];
        }

        // Delete client
        $stmt = $pdo->prepare("DELETE FROM cliente WHERE id_cliente = ?");
        $result = $stmt->execute([$clientId]);

        if ($result) {
            return [
                'success' => true,
                'message' => 'Cliente eliminado exitosamente',
                'data' => [
                    'deleted_client' => $client
                ]
            ];
        } else {
            throw new Exception('Error al eliminar el cliente');
        }

    } catch (PDOException $e) {
        error_log("Database error in handleDelete: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error de base de datos al eliminar el cliente',
            'errors' => []
        ];
    }
}

/**
 * Handle getting client data
 */
function handleGet($pdo, $data) {
    try {
        $clientId = (int)($data['id'] ?? 0);

        if ($clientId <= 0) {
            throw new Exception('ID de cliente inválido');
        }

        $stmt = $pdo->prepare("SELECT * FROM cliente WHERE id_cliente = ?");
        $stmt->execute([$clientId]);
        $client = $stmt->fetch();

        if (!$client) {
            throw new Exception('Cliente no encontrado');
        }

        // Get related data if requested
        $includeRelated = $data['include_related'] ?? false;
        $relatedData = [];

        if ($includeRelated) {
            // Get sales count
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM venta WHERE id_cliente = ?");
            $stmt->execute([$clientId]);
            $relatedData['sales_count'] = $stmt->fetchColumn();

            // Get contracts count
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM contrato WHERE id_cliente = ?");
            $stmt->execute([$clientId]);
            $relatedData['contracts_count'] = $stmt->fetchColumn();

            // Get rentals count
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM arriendo WHERE id_cliente = ?");
            $stmt->execute([$clientId]);
            $relatedData['rentals_count'] = $stmt->fetchColumn();

            // Get visits count
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM visita WHERE id_cliente = ?");
            $stmt->execute([$clientId]);
            $relatedData['visits_count'] = $stmt->fetchColumn();
        }

        return [
            'success' => true,
            'message' => 'Cliente encontrado',
            'data' => [
                'client' => $client,
                'display_id' => 'CLI' . str_pad($client['id_cliente'], 3, '0', STR_PAD_LEFT),
                'related' => $relatedData
            ]
        ];

    } catch (PDOException $e) {
        error_log("Database error in handleGet: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error de base de datos al obtener el cliente',
            'errors' => []
        ];
    }
}

/**
 * Handle client search
 */
function handleSearch($pdo, $data) {
    try {
        $searchTerm = trim($data['term'] ?? '');
        $limit = min((int)($data['limit'] ?? 10), 50); // Max 50 results
        $type = $data['type'] ?? '';

        $whereConditions = [];
        $params = [];

        if (!empty($searchTerm)) {
            $whereConditions[] = "(nombre LIKE ? OR apellido LIKE ? OR correo LIKE ? OR nro_documento LIKE ?)";
            $searchWildcard = "%{$searchTerm}%";
            $params = array_fill(0, 4, $searchWildcard);
        }

        if (!empty($type) && array_key_exists($type, CLIENT_TYPES)) {
            $whereConditions[] = "tipo_cliente = ?";
            $params[] = $type;
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        $sql = "SELECT id_cliente, nombre, apellido, tipo_cliente, correo, nro_documento, tipo_documento
                FROM cliente
                {$whereClause}
                ORDER BY nombre, apellido
                LIMIT ?";

        $params[] = $limit;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $clients = $stmt->fetchAll();

        // Format results for frontend consumption
        $results = array_map(function($client) {
            return [
                'id' => $client['id_cliente'],
                'display_id' => 'CLI' . str_pad($client['id_cliente'], 3, '0', STR_PAD_LEFT),
                'name' => $client['nombre'] . ' ' . $client['apellido'],
                'email' => $client['correo'],
                'document' => $client['tipo_documento'] . ' ' . $client['nro_documento'],
                'type' => $client['tipo_cliente'],
                'label' => $client['nombre'] . ' ' . $client['apellido'] . ' (' . $client['correo'] . ')'
            ];
        }, $clients);

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
            case 'nro_documento':
                if (!empty($value)) {
                    $sql = "SELECT id_cliente FROM cliente WHERE nro_documento = ?";
                    $params = [$value];

                    if ($excludeId > 0) {
                        $sql .= " AND id_cliente != ?";
                        $params[] = $excludeId;
                    }

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);

                    if ($stmt->fetchColumn()) {
                        $response['valid'] = false;
                        $response['message'] = 'Este número de documento ya está registrado';
                    }
                }
                break;

            case 'correo':
                if (!empty($value)) {
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $response['valid'] = false;
                        $response['message'] = 'Formato de email inválido';
                    } else {
                        $sql = "SELECT id_cliente FROM cliente WHERE correo = ?";
                        $params = [$value];

                        if ($excludeId > 0) {
                            $sql .= " AND id_cliente != ?";
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
        $clientId = (int)($data['id'] ?? 0);

        if ($clientId > 0) {
            // Export single client
            $stmt = $pdo->prepare("SELECT * FROM cliente WHERE id_cliente = ?");
            $stmt->execute([$clientId]);
            $client = $stmt->fetch();

            if (!$client) {
                throw new Exception('Cliente no encontrado');
            }

            $exportData = [$client];
        } else {
            // Export all clients (with filters if provided)
            $whereConditions = [];
            $params = [];

            if (!empty($data['type'])) {
                $whereConditions[] = "tipo_cliente = ?";
                $params[] = $data['type'];
            }

            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

            $stmt = $pdo->prepare("SELECT * FROM cliente {$whereClause} ORDER BY nombre, apellido");
            $stmt->execute($params);
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
        $clientIds = $data['client_ids'] ?? [];

        if (empty($clientIds) || !is_array($clientIds)) {
            throw new Exception('No se seleccionaron clientes');
        }

        $clientIds = array_map('intval', $clientIds);
        $clientIds = array_filter($clientIds, function($id) { return $id > 0; });

        if (empty($clientIds)) {
            throw new Exception('IDs de cliente inválidos');
        }

        $results = [];

        switch ($action) {
            case 'delete':
                foreach ($clientIds as $clientId) {
                    $deleteResult = handleDelete($pdo, ['id' => $clientId]);
                    $results[] = [
                        'id' => $clientId,
                        'success' => $deleteResult['success'],
                        'message' => $deleteResult['message']
                    ];
                }
                break;

            case 'export':
                $placeholders = str_repeat('?,', count($clientIds) - 1) . '?';
                $stmt = $pdo->prepare("SELECT * FROM cliente WHERE id_cliente IN ({$placeholders}) ORDER BY nombre, apellido");
                $stmt->execute($clientIds);
                $clients = $stmt->fetchAll();

                return [
                    'success' => true,
                    'message' => 'Clientes exportados exitosamente',
                    'data' => [
                        'clients' => $clients,
                        'count' => count($clients)
                    ]
                ];

            default:
                throw new Exception('Acción en lote no válida');
        }

        $successCount = count(array_filter($results, function($r) { return $r['success']; }));
        $totalCount = count($results);

        return [
            'success' => $successCount > 0,
            'message' => "Procesados {$successCount} de {$totalCount} clientes",
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
?>