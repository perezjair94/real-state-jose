<?php
/**
 * Properties AJAX Handler - Real Estate Management System
 * Handles AJAX requests for property operations
 * Educational PHP/MySQL Project
 */

// Prevent direct access
if (!defined('APP_ACCESS')) {
    die('Direct access not permitted');
}

// Handle JSON requests
$jsonData = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_SERVER['CONTENT_TYPE']) &&
    strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $jsonInput = file_get_contents('php://input');
    $jsonData = json_decode($jsonInput, true);
}

// Ensure this is an AJAX request
if (!isset($_POST['ajax']) && !$jsonData) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

// Get the requested action
$action = $jsonData['action'] ?? $_POST['action'] ?? '';

// Response array
$response = ['success' => false, 'error' => '', 'data' => null];

try {
    $db = new Database();
    $pdo = $db->getConnection();

    switch ($action) {
        case 'delete':
            handleDeleteProperty($pdo, $response);
            break;

        case 'update_status':
            handleUpdateStatus($pdo, $response);
            break;

        case 'get_property':
            handleGetProperty($pdo, $response);
            break;

        case 'search':
            handleSearchProperties($pdo, $response);
            break;

        case 'upload_photo':
            handlePhotoUpload($response);
            break;

        case 'delete_photo':
            handleDeletePhoto($pdo, $response);
            break;

        case 'submitInterest':
            handleSubmitInterest($pdo, $response, $jsonData);
            break;

        default:
            $response['error'] = 'Acción no válida';
            break;
    }

} catch (PDOException $e) {
    error_log("AJAX Database error: " . $e->getMessage());
    $response['error'] = 'Error de base de datos';
} catch (Exception $e) {
    error_log("AJAX General error: " . $e->getMessage());
    $response['error'] = $e->getMessage();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;

/**
 * Handle property deletion
 */
function handleDeleteProperty($pdo, &$response) {
    $propertyId = (int)($_POST['id'] ?? 0);

    // Debug logging
    error_log("Delete Property Request - ID: {$propertyId}");
    error_log("POST data: " . print_r($_POST, true));

    if ($propertyId <= 0) {
        $response['error'] = 'ID de propiedad inválido';
        error_log("Error: Invalid property ID - {$propertyId}");
        return;
    }

    // Check if property exists and can be deleted
    $checkSql = "SELECT id_inmueble, estado, fotos FROM inmueble WHERE id_inmueble = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$propertyId]);
    $property = $checkStmt->fetch();

    if (!$property) {
        $response['error'] = 'Propiedad no encontrada';
        error_log("Error: Property not found - ID: {$propertyId}");
        return;
    }

    error_log("Property found - ID: {$propertyId}, Estado: {$property['estado']}");

    // Check if property is referenced in other tables
    $referenceChecks = [
        'venta' => 'No se puede eliminar: la propiedad tiene ventas registradas',
        'contrato' => 'No se puede eliminar: la propiedad tiene contratos activos',
        'arriendo' => 'No se puede eliminar: la propiedad tiene arriendos activos',
        'visita' => 'No se puede eliminar: la propiedad tiene visitas programadas'
    ];

    foreach ($referenceChecks as $table => $message) {
        $refSql = "SELECT COUNT(*) FROM {$table} WHERE id_inmueble = ?";
        $refStmt = $pdo->prepare($refSql);
        $refStmt->execute([$propertyId]);
        $count = $refStmt->fetchColumn();

        if ($count > 0) {
            $response['error'] = $message;
            error_log("Error: Cannot delete - {$count} records found in table {$table}");
            return;
        }
    }

    error_log("No references found, proceeding with deletion");

    // Begin transaction
    $pdo->beginTransaction();

    try {
        // Delete associated photos from filesystem
        if (!empty($property['fotos'])) {
            $photos = json_decode($property['fotos'], true);
            if (is_array($photos)) {
                foreach ($photos as $photo) {
                    // Only delete custom uploaded photos, not default images
                    if (strpos($photo, 'img/') !== 0 && strpos($photo, 'casa') === false) {
                        $photoPath = UPLOAD_PATH_PROPERTIES . $photo;
                        if (file_exists($photoPath)) {
                            if (unlink($photoPath)) {
                                error_log("Deleted photo: {$photoPath}");
                            } else {
                                error_log("Failed to delete photo: {$photoPath}");
                            }
                        }
                    }
                }
            }
        }

        // Delete property from database
        $deleteSql = "DELETE FROM inmueble WHERE id_inmueble = ?";
        $deleteStmt = $pdo->prepare($deleteSql);
        $result = $deleteStmt->execute([$propertyId]);

        if ($result) {
            $rowsAffected = $deleteStmt->rowCount();
            error_log("Delete executed - Rows affected: {$rowsAffected}");

            if ($rowsAffected > 0) {
                $pdo->commit();
                $response['success'] = true;
                $response['data'] = [
                    'id' => $propertyId,
                    'message' => 'Propiedad eliminada correctamente'
                ];

                error_log("Success: Property {$propertyId} deleted successfully");
                logMessage("Property {$propertyId} deleted successfully", 'INFO');
            } else {
                throw new Exception('No se pudo eliminar la propiedad - ninguna fila afectada');
            }
        } else {
            throw new Exception('Error al ejecutar la eliminación');
        }

    } catch (Exception $e) {
        $pdo->rollback();
        error_log("Error during deletion: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Handle property status update
 */
function handleUpdateStatus($pdo, &$response) {
    $propertyId = (int)($_POST['id'] ?? 0);
    $newStatus = sanitizeInput($_POST['status'] ?? '');

    // Debug logging
    error_log("Update Status Request - ID: {$propertyId}, Status: {$newStatus}");
    error_log("POST data: " . print_r($_POST, true));

    if ($propertyId <= 0) {
        $response['error'] = 'ID de propiedad inválido';
        error_log("Error: Invalid property ID - {$propertyId}");
        return;
    }

    $validStatuses = array_keys(PROPERTY_STATUS);
    if (!in_array($newStatus, $validStatuses)) {
        $response['error'] = 'Estado inválido: ' . $newStatus;
        error_log("Error: Invalid status - {$newStatus}. Valid: " . implode(', ', $validStatuses));
        return;
    }

    // First, verify the property exists
    $checkSql = "SELECT estado FROM inmueble WHERE id_inmueble = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$propertyId]);
    $currentProperty = $checkStmt->fetch();

    if (!$currentProperty) {
        $response['error'] = 'Propiedad no encontrada';
        error_log("Error: Property not found - ID: {$propertyId}");
        return;
    }

    // Check if status is actually changing
    if ($currentProperty['estado'] === $newStatus) {
        $response['success'] = true;
        $response['data'] = [
            'id' => $propertyId,
            'status' => $newStatus,
            'message' => 'El estado ya está configurado como ' . $newStatus
        ];
        error_log("Info: Status unchanged - Property {$propertyId} already has status {$newStatus}");
        return;
    }

    // Update property status
    $updateSql = "UPDATE inmueble SET estado = ?, updated_at = CURRENT_TIMESTAMP WHERE id_inmueble = ?";
    $updateStmt = $pdo->prepare($updateSql);
    $result = $updateStmt->execute([$newStatus, $propertyId]);

    if ($result) {
        $response['success'] = true;
        $response['data'] = [
            'id' => $propertyId,
            'status' => $newStatus,
            'message' => 'Estado actualizado correctamente'
        ];

        error_log("Success: Property {$propertyId} status updated from {$currentProperty['estado']} to {$newStatus}");
        logMessage("Property {$propertyId} status updated from {$currentProperty['estado']} to {$newStatus}", 'INFO');
    } else {
        $response['error'] = 'No se pudo actualizar el estado';
        error_log("Error: Update failed - Database error");
    }
}

/**
 * Handle get single property data
 */
function handleGetProperty($pdo, &$response) {
    $propertyId = (int)($_POST['id'] ?? 0);

    if ($propertyId <= 0) {
        $response['error'] = 'ID de propiedad inválido';
        return;
    }

    $sql = "SELECT * FROM inmueble WHERE id_inmueble = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$propertyId]);
    $property = $stmt->fetch();

    if ($property) {
        // Format data for response
        $property['precio_formatted'] = formatCurrency($property['precio']);
        $property['created_at_formatted'] = formatDate($property['created_at']);
        $property['fotos_array'] = !empty($property['fotos']) ? json_decode($property['fotos'], true) : [];

        $response['success'] = true;
        $response['data'] = $property;
    } else {
        $response['error'] = 'Propiedad no encontrada';
    }
}

/**
 * Handle property search
 */
function handleSearchProperties($pdo, &$response) {
    $searchTerm = sanitizeInput($_POST['search'] ?? '');
    $limit = min(20, (int)($_POST['limit'] ?? 10)); // Limit results for performance

    if (strlen($searchTerm) < 2) {
        $response['error'] = 'Mínimo 2 caracteres para buscar';
        return;
    }

    $sql = "SELECT id_inmueble, tipo_inmueble, direccion, ciudad, precio, estado
            FROM inmueble
            WHERE direccion LIKE ? OR descripcion LIKE ? OR ciudad LIKE ?
            ORDER BY created_at DESC
            LIMIT ?";

    $searchWildcard = "%{$searchTerm}%";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$searchWildcard, $searchWildcard, $searchWildcard, $limit]);
    $properties = $stmt->fetchAll();

    // Format results
    $formattedProperties = [];
    foreach ($properties as $property) {
        $formattedProperties[] = [
            'id' => $property['id_inmueble'],
            'formatted_id' => generateFormattedId('INM', $property['id_inmueble']),
            'type' => $property['tipo_inmueble'],
            'address' => $property['direccion'],
            'city' => $property['ciudad'],
            'price' => formatCurrency($property['precio']),
            'status' => $property['estado']
        ];
    }

    $response['success'] = true;
    $response['data'] = [
        'properties' => $formattedProperties,
        'count' => count($formattedProperties),
        'search_term' => $searchTerm
    ];
}

/**
 * Handle photo upload
 */
function handlePhotoUpload(&$response) {
    $propertyId = (int)($_POST['property_id'] ?? 0);

    if ($propertyId <= 0) {
        $response['error'] = 'ID de propiedad inválido';
        return;
    }

    if (!isset($_FILES['photo']) || empty($_FILES['photo']['tmp_name'])) {
        $response['error'] = 'No se recibió ningún archivo';
        return;
    }

    $file = $_FILES['photo'];
    $validation = validateUploadedFile($file, ALLOWED_IMAGE_TYPES);

    if (!$validation['valid']) {
        $response['error'] = $validation['error'];
        return;
    }

    try {
        $db = new Database();
        $pdo = $db->getConnection();

        // Generate unique filename
        $newFilename = generateUniqueFilename($file['name']);
        $uploadPath = UPLOAD_PATH_PROPERTIES . $newFilename;

        // Create upload directory if it doesn't exist
        if (!is_dir(UPLOAD_PATH_PROPERTIES)) {
            mkdir(UPLOAD_PATH_PROPERTIES, 0755, true);
        }

        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            // Update property photos in database
            $getSql = "SELECT fotos FROM inmueble WHERE id_inmueble = ?";
            $getStmt = $pdo->prepare($getSql);
            $getStmt->execute([$propertyId]);
            $currentPhotos = $getStmt->fetchColumn();

            $photos = [];
            if (!empty($currentPhotos)) {
                $photos = json_decode($currentPhotos, true) ?: [];
            }

            $photos[] = $newFilename;

            $updateSql = "UPDATE inmueble SET fotos = ?, updated_at = CURRENT_TIMESTAMP WHERE id_inmueble = ?";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([json_encode($photos), $propertyId]);

            $response['success'] = true;
            $response['data'] = [
                'filename' => $newFilename,
                'url' => UPLOADS_URL . 'properties/' . $newFilename,
                'message' => 'Foto subida correctamente'
            ];

            logMessage("Photo uploaded for property {$propertyId}: {$newFilename}", 'INFO');
        } else {
            $response['error'] = 'Error al guardar el archivo';
        }

    } catch (Exception $e) {
        error_log("Photo upload error: " . $e->getMessage());
        $response['error'] = 'Error al procesar la imagen';
    }
}

/**
 * Handle photo deletion
 */
function handleDeletePhoto($pdo, &$response) {
    $propertyId = (int)($_POST['property_id'] ?? 0);
    $filename = sanitizeInput($_POST['filename'] ?? '');

    if ($propertyId <= 0 || empty($filename)) {
        $response['error'] = 'Parámetros inválidos';
        return;
    }

    try {
        // Get current photos
        $getSql = "SELECT fotos FROM inmueble WHERE id_inmueble = ?";
        $getStmt = $pdo->prepare($getSql);
        $getStmt->execute([$propertyId]);
        $currentPhotos = $getStmt->fetchColumn();

        if (empty($currentPhotos)) {
            $response['error'] = 'No hay fotos para eliminar';
            return;
        }

        $photos = json_decode($currentPhotos, true) ?: [];
        $photoIndex = array_search($filename, $photos);

        if ($photoIndex === false) {
            $response['error'] = 'Foto no encontrada';
            return;
        }

        // Remove photo from array
        unset($photos[$photoIndex]);
        $photos = array_values($photos); // Re-index array

        // Update database
        $updateSql = "UPDATE inmueble SET fotos = ?, updated_at = CURRENT_TIMESTAMP WHERE id_inmueble = ?";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([json_encode($photos), $propertyId]);

        // Delete physical file
        $photoPath = UPLOAD_PATH_PROPERTIES . $filename;
        if (file_exists($photoPath)) {
            unlink($photoPath);
        }

        $response['success'] = true;
        $response['data'] = [
            'filename' => $filename,
            'message' => 'Foto eliminada correctamente'
        ];

        logMessage("Photo deleted from property {$propertyId}: {$filename}", 'INFO');

    } catch (Exception $e) {
        error_log("Photo deletion error: " . $e->getMessage());
        $response['error'] = 'Error al eliminar la foto';
    }
}

/**
 * Handle property interest submission (purchase or rental request)
 */
function handleSubmitInterest($pdo, &$response, $jsonData) {
    // Get data from JSON request
    $data = $jsonData['data'] ?? [];

    // Validate required fields
    $requiredFields = ['nombre', 'apellido', 'correo', 'telefono', 'id_inmueble', 'tipo_interes'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            $response['error'] = "El campo '{$field}' es requerido";
            error_log("Interest submission error: Missing field '{$field}'");
            return;
        }
    }

    // Sanitize inputs
    $nombre = sanitizeInput($data['nombre']);
    $apellido = sanitizeInput($data['apellido']);
    $correo = sanitizeInput($data['correo']);
    $telefono = sanitizeInput($data['telefono']);
    $idInmueble = (int)$data['id_inmueble'];
    $tipoInteres = sanitizeInput($data['tipo_interes']);
    $fechaPreferida = !empty($data['fecha_preferida']) ? sanitizeInput($data['fecha_preferida']) : null;
    $horaPreferida = !empty($data['hora_preferida']) ? sanitizeInput($data['hora_preferida']) : null;
    $mensaje = !empty($data['mensaje']) ? sanitizeInput($data['mensaje']) : '';

    // Validate email format
    if (!validateEmail($correo)) {
        $response['error'] = 'Formato de correo electrónico inválido';
        return;
    }

    // Validate property ID
    if ($idInmueble <= 0) {
        $response['error'] = 'ID de propiedad inválido';
        return;
    }

    // Validate tipo_interes
    if (!in_array($tipoInteres, ['compra', 'arriendo'])) {
        $response['error'] = 'Tipo de interés inválido';
        return;
    }

    try {
        // Check if property exists
        $checkPropertySql = "SELECT id_inmueble, direccion, estado FROM inmueble WHERE id_inmueble = ?";
        $checkPropertyStmt = $pdo->prepare($checkPropertySql);
        $checkPropertyStmt->execute([$idInmueble]);
        $property = $checkPropertyStmt->fetch();

        if (!$property) {
            $response['error'] = 'Propiedad no encontrada';
            return;
        }

        if ($property['estado'] !== 'Disponible') {
            $response['error'] = 'Esta propiedad ya no está disponible';
            return;
        }

        // Check if client exists by email
        $checkClientSql = "SELECT id_cliente, nombre, apellido FROM cliente WHERE correo = ?";
        $checkClientStmt = $pdo->prepare($checkClientSql);
        $checkClientStmt->execute([$correo]);
        $existingClient = $checkClientStmt->fetch();

        $idCliente = null;

        if ($existingClient) {
            // Use existing client
            $idCliente = $existingClient['id_cliente'];
            error_log("Using existing client ID: {$idCliente}");
        } else {
            // Create new client record
            $tipoClienteMap = [
                'compra' => 'Comprador',
                'arriendo' => 'Arrendatario'
            ];
            $tipoCliente = $tipoClienteMap[$tipoInteres];

            // Generate a unique document number (temporary for leads)
            $nroDocumento = 'LEAD-' . strtoupper(substr(md5($correo . time()), 0, 10));

            $insertClientSql = "INSERT INTO cliente (nombre, apellido, tipo_documento, nro_documento, correo, tipo_cliente)
                                VALUES (?, ?, 'CC', ?, ?, ?)";
            $insertClientStmt = $pdo->prepare($insertClientSql);
            $insertClientStmt->execute([$nombre, $apellido, $nroDocumento, $correo, $tipoCliente]);
            $idCliente = $pdo->lastInsertId();
            error_log("Created new client ID: {$idCliente}");
        }

        // Get a default agent or leave null (admin will assign later)
        $getAgentSql = "SELECT id_agente FROM agente WHERE activo = TRUE ORDER BY RAND() LIMIT 1";
        $getAgentStmt = $pdo->prepare($getAgentSql);
        $getAgentStmt->execute();
        $agent = $getAgentStmt->fetch();
        $idAgente = $agent ? $agent['id_agente'] : null;

        // Create visit record with interest information
        $visitEstado = 'Programada';
        $visitFecha = $fechaPreferida ?: date('Y-m-d', strtotime('+3 days')); // Default: 3 days from now
        $visitHora = $horaPreferida ?: '10:00:00'; // Default: 10 AM

        $tipoInteresTexto = $tipoInteres === 'compra' ? 'Compra' : 'Arriendo';
        $observaciones = "SOLICITUD DE {$tipoInteresTexto}\n";
        $observaciones .= "Teléfono: {$telefono}\n";
        if (!empty($mensaje)) {
            $observaciones .= "Mensaje: {$mensaje}\n";
        }
        $observaciones .= "\nGenerado automáticamente desde la web.";

        $insertVisitSql = "INSERT INTO visita (fecha_visita, hora_visita, estado, observaciones, id_inmueble, id_cliente, id_agente)
                           VALUES (?, ?, ?, ?, ?, ?, ?)";
        $insertVisitStmt = $pdo->prepare($insertVisitSql);
        $insertVisitStmt->execute([
            $visitFecha,
            $visitHora,
            $visitEstado,
            $observaciones,
            $idInmueble,
            $idCliente,
            $idAgente
        ]);

        $visitId = $pdo->lastInsertId();

        $response['success'] = true;
        $response['data'] = [
            'visita_id' => $visitId,
            'cliente_id' => $idCliente,
            'message' => '¡Solicitud registrada exitosamente! Nos pondremos en contacto contigo pronto.',
            'fecha_visita' => $visitFecha,
            'hora_visita' => $visitHora
        ];

        logMessage("Interest submission: Client {$idCliente} interested in {$tipoInteresTexto} property {$idInmueble}", 'INFO');

    } catch (PDOException $e) {
        error_log("Database error in handleSubmitInterest: " . $e->getMessage());
        $response['error'] = 'Error al procesar la solicitud. Por favor intenta nuevamente.';
    } catch (Exception $e) {
        error_log("Error in handleSubmitInterest: " . $e->getMessage());
        $response['error'] = 'Error al procesar la solicitud: ' . $e->getMessage();
    }
}

/**
 * Educational helper function for AJAX response formatting
 * Demonstrates how to structure API responses consistently
 */
function formatAjaxResponse($success, $data = null, $error = '', $meta = []) {
    return [
        'success' => $success,
        'data' => $data,
        'error' => $error,
        'meta' => array_merge([
            'timestamp' => date('Y-m-d H:i:s'),
            'module' => 'properties'
        ], $meta)
    ];
}
?>