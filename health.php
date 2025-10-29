<?php
/**
 * Health Check Endpoint for Docker
 * Used by Docker HEALTHCHECK to verify the application is running
 */

header('Content-Type: application/json');

try {
    // Test database connection
    require_once 'config/database.php';
    $database = new Database();

    if (!$database->testConnection()) {
        http_response_code(503);
        echo json_encode(['status' => 'unhealthy', 'message' => 'Database connection failed']);
        exit(1);
    }

    // Database is healthy
    http_response_code(200);
    echo json_encode([
        'status' => 'healthy',
        'message' => 'Application is running',
        'timestamp' => date('c')
    ]);

} catch (Exception $e) {
    http_response_code(503);
    echo json_encode([
        'status' => 'unhealthy',
        'message' => 'Service unavailable',
        'error' => $e->getMessage()
    ]);
    exit(1);
}
?>
