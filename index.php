<?php
/**
 * Main Application Entry Point - Real Estate Management System
 * Educational PHP/MySQL Project
 * Handles module routing and application initialization
 */

// Set UTF-8 encoding for proper character handling
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

// Application access control
define('APP_ACCESS', true);

// Start output buffering for clean output
ob_start();

// Load configuration and dependencies
require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Initialize session with security
initSession();

// Check if user is logged in - redirect to login if not
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Check user role and redirect to appropriate dashboard if accessing root
if (!isset($_GET['module']) && !isset($_GET['action'])) {
    if (hasRole('admin')) {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: cliente/dashboard.php');
    }
    exit;
}

// Get and validate current module and action
$module = $_GET['module'] ?? DEFAULT_MODULE;
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// Security: Validate module and action against allowed values
$allowedModules = array_keys(AVAILABLE_MODULES);
$allowedActions = array_keys(MODULE_ACTIONS);

if (!in_array($module, $allowedModules)) {
    $module = DEFAULT_MODULE;
}

if (!in_array($action, $allowedActions)) {
    $action = 'list';
}

// Authorization: Cliente role has limited access
// Clientes can only view properties (read-only)
if (hasRole('cliente')) {
    // Only allow properties module with list and view actions
    if ($module !== 'properties') {
        redirectWithMessage(
            'cliente/dashboard.php',
            'No tienes permisos para acceder a este módulo.',
            'error'
        );
    }

    // Only allow list and view actions
    if (!in_array($action, ['list', 'view'])) {
        redirectWithMessage(
            "?module={$module}&action=list",
            'No tienes permisos para realizar esta acción.',
            'error'
        );
    }
}

// Log access in development mode
if (ENVIRONMENT === 'development' && LOG_ENABLED) {
    logMessage("Module accessed: {$module}/{$action}" . ($id ? "/{$id}" : ""), 'INFO');
}

// Handle AJAX requests separately
if ($action === 'ajax' || (isset($_POST['ajax']) && $_POST['ajax'] === 'true')) {
    header('Content-Type: application/json');

    // Route to appropriate AJAX handler
    $ajaxFile = "modules/{$module}/ajax.php";
    if (file_exists($ajaxFile)) {
        require_once $ajaxFile;
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'AJAX handler not found']);
    }
    exit;
}

// Handle form submissions with CSRF protection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        redirectWithMessage(
            "?module={$module}&action={$action}",
            'Token de seguridad inválido. Intente nuevamente.',
            'error'
        );
    }
}

// Test database connection on first load (development mode)
if (ENVIRONMENT === 'development' && !isset($_SESSION['db_tested'])) {
    try {
        $db = new Database();
        if ($db->testConnection()) {
            $_SESSION['db_tested'] = true;
            if (DEBUG_MODE) {
                logMessage("Database connection tested successfully", 'INFO');
            }
        } else {
            throw new Exception("Database connection test failed");
        }
    } catch (Exception $e) {
        if (DEBUG_MODE) {
            error_log("Database connection error: " . $e->getMessage());
        }

        // Redirect to a setup page or show error
        if (file_exists('setup.php')) {
            header('Location: setup.php');
            exit;
        } else {
            die('
                <div style="max-width: 600px; margin: 50px auto; padding: 20px; border: 1px solid #e74c3c; border-radius: 8px; background: #f8d7da; color: #721c24; font-family: Arial, sans-serif;">
                    <h2>Error de Conexión a la Base de Datos</h2>
                    <p>No se pudo conectar a la base de datos. Por favor verifique:</p>
                    <ul>
                        <li>Que MySQL esté funcionando</li>
                        <li>Que la base de datos "real_estate_db" exista</li>
                        <li>Que las credenciales en config/database.php sean correctas</li>
                    </ul>
                    <p><strong>Para crear la base de datos:</strong></p>
                    <ol>
                        <li>Abra phpMyAdmin o su cliente MySQL</li>
                        <li>Ejecute el archivo <code>database/schema.sql</code></li>
                        <li>Opcionalmente ejecute <code>database/seed.sql</code> para datos de prueba</li>
                    </ol>
                </div>
            ');
        }
    }
}

// Determine the correct file path for the requested action
$moduleFile = null;
$possibleFiles = [
    "modules/{$module}/{$action}.php",
    "modules/{$module}/index.php",
    "modules/{$module}/list.php"
];

foreach ($possibleFiles as $file) {
    if (file_exists($file)) {
        $moduleFile = $file;
        break;
    }
}

// If no module file found, default to list or show error
if (!$moduleFile) {
    if ($action !== 'list') {
        // Redirect to list view if specific action not found
        redirectWithMessage(
            "?module={$module}",
            'La acción solicitada no está disponible.',
            'warning'
        );
    } else {
        // Show error if even list view doesn't exist
        $moduleFile = 'includes/error_404.php';
    }
}

// Include the header
require_once 'includes/header.php';

// Educational comment for students
if (SHOW_EDUCATIONAL_COMMENTS): ?>
<!-- Educational Note:
     This is where the dynamic content is loaded based on the URL parameters.
     The system uses a simple routing mechanism where:
     - ?module=properties loads modules/properties/list.php (default action)
     - ?module=properties&action=create loads modules/properties/create.php
     - ?module=clients&action=edit&id=1 loads modules/clients/edit.php with ID parameter
-->
<?php endif; ?>

<div class="main-content">
    <?php
    // Include the requested module file
    if (file_exists($moduleFile)) {
        require_once $moduleFile;
    } else {
        // Fallback error page
        echo '<div class="alert alert-danger">';
        echo '<h3>Página no encontrada</h3>';
        echo '<p>El módulo o acción solicitada no existe.</p>';
        echo '<p><a href="?module=' . DEFAULT_MODULE . '" class="btn btn-primary">Ir al inicio</a></p>';
        echo '</div>';
    }
    ?>
</div>

<?php
// Include the footer
require_once 'includes/footer.php';

// Clean up output buffer (only if it's active)
if (ob_get_level() > 0) {
    ob_end_flush();
}

// Educational timing information in development
if (ENVIRONMENT === 'development' && SHOW_DEBUG_INFO) {
    $executionTime = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
    $memoryUsage = memory_get_peak_usage(true);

    echo "<!-- Debug Info: Execution time: " . round($executionTime, 4) . "s, Memory: " . formatBytes($memoryUsage) . " -->";
}
?>