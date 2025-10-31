<?php
/**
 * Application Constants - Real Estate Management System
 * Central configuration for the application
 * Educational PHP/MySQL Project
 */

// Prevent double-definition of constants
if (defined('APP_INITIALIZED')) {
    return;
}

// Application Information
if (!defined('APP_NAME')) define('APP_NAME', 'Inmuebles del Sinú');
if (!defined('APP_VERSION')) define('APP_VERSION', '1.0.0');
if (!defined('APP_DESCRIPTION')) define('APP_DESCRIPTION', 'Sistema educativo para gestión de propiedades inmobiliarias');
if (!defined('APP_AUTHOR')) define('APP_AUTHOR', 'Proyecto Educativo PHP/MySQL');

// Environment Configuration
if (!defined('ENVIRONMENT')) define('ENVIRONMENT', 'development'); // development, production
if (!defined('DEBUG_MODE')) define('DEBUG_MODE', true); // Set to false in production

// Security Configuration
if (!defined('SESSION_TIMEOUT')) define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
if (!defined('MAX_LOGIN_ATTEMPTS')) define('MAX_LOGIN_ATTEMPTS', 5);
if (!defined('PASSWORD_MIN_LENGTH')) define('PASSWORD_MIN_LENGTH', 8);
if (!defined('CSRF_TOKEN_EXPIRY')) define('CSRF_TOKEN_EXPIRY', 1800); // 30 minutes

// File Upload Configuration
if (!defined('UPLOAD_MAX_SIZE')) define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB in bytes
if (!defined('UPLOAD_PATH_PROPERTIES')) define('UPLOAD_PATH_PROPERTIES', 'assets/uploads/properties/');
if (!defined('UPLOAD_PATH_CONTRACTS')) define('UPLOAD_PATH_CONTRACTS', 'assets/uploads/contracts/');

// Allowed file types for uploads
if (!defined('ALLOWED_IMAGE_TYPES')) define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
if (!defined('ALLOWED_DOCUMENT_TYPES')) define('ALLOWED_DOCUMENT_TYPES', ['pdf', 'doc', 'docx', 'txt']);

// Database Configuration
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'real_estate_db');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

// Application URLs (adjust for your environment)
// Auto-detect BASE_URL or use default
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptPath = dirname($_SERVER['SCRIPT_NAME']);

    // Clean up the path - if we're in /real-state-jose/config/constants.php,
    // the path would be /real-state-jose/config, so we need to go up one level
    if (basename($scriptPath) === 'config') {
        $scriptPath = dirname($scriptPath);
    }

    // Ensure script path ends with /
    if (empty($scriptPath) || $scriptPath === '/') {
        $scriptPath = '/';
    } else {
        $scriptPath = rtrim($scriptPath, '/') . '/';
    }

    $baseUrl = $protocol . $host . $scriptPath;
    define('BASE_URL', $baseUrl);
    define('ASSETS_URL', BASE_URL . 'assets/');
    define('UPLOADS_URL', BASE_URL . 'assets/uploads/');
}

// Pagination Settings
if (!defined('RECORDS_PER_PAGE')) define('RECORDS_PER_PAGE', 10);
if (!defined('MAX_PAGINATION_LINKS')) define('MAX_PAGINATION_LINKS', 10);

// Module Configuration
if (!defined('AVAILABLE_MODULES')) define('AVAILABLE_MODULES', [
    'properties' => 'Inmuebles',
    'clients' => 'Clientes',
    'agents' => 'Agentes',
    'sales' => 'Ventas',
    'contracts' => 'Contratos',
    'rentals' => 'Arriendos',
    'visits' => 'Visitas'
]);

// Menu Structure with Submenus
if (!defined('MENU_STRUCTURE')) define('MENU_STRUCTURE', [
    [
        'key' => 'properties',
        'label' => 'Inmuebles',
        'icon' => '🏠',
        'submenu' => [
            [
                'key' => 'properties',
                'label' => 'Gestión de Inmuebles',
                'icon' => '📋'
            ],
            [
                'key' => 'sales',
                'label' => 'Ventas',
                'icon' => '💰'
            ],
            [
                'key' => 'rentals',
                'label' => 'Arriendos',
                'icon' => '🔑'
            ]
        ]
    ],
    [
        'key' => 'clients',
        'label' => 'Clientes',
        'icon' => '👥'
    ],
    [
        'key' => 'agents',
        'label' => 'Agentes',
        'icon' => '👔'
    ],
    [
        'key' => 'contracts',
        'label' => 'Contratos',
        'icon' => '📄'
    ],
    [
        'key' => 'visits',
        'label' => 'Visitas',
        'icon' => '📅'
    ]
]);

// Default Module
if (!defined('DEFAULT_MODULE')) define('DEFAULT_MODULE', 'properties');

// Available Actions per Module
if (!defined('MODULE_ACTIONS')) define('MODULE_ACTIONS', [
    'list' => 'Listar',
    'create' => 'Crear',
    'edit' => 'Editar',
    'view' => 'Ver',
    'delete' => 'Eliminar',
    'ajax' => 'AJAX'
]);

// Property Types
if (!defined('PROPERTY_TYPES')) define('PROPERTY_TYPES', [
    'Casa' => 'Casa',
    'Apartamento' => 'Apartamento',
    'Local' => 'Local Comercial',
    'Oficina' => 'Oficina',
    'Lote' => 'Lote'
]);

// Property Status
if (!defined('PROPERTY_STATUS')) define('PROPERTY_STATUS', [
    'Disponible' => 'Disponible',
    'Arrendado' => 'Arrendado',
    'Vendido' => 'Vendido'
]);

// Client Types
if (!defined('CLIENT_TYPES')) define('CLIENT_TYPES', [
    'Comprador' => 'Comprador',
    'Vendedor' => 'Vendedor',
    'Arrendatario' => 'Arrendatario',
    'Arrendador' => 'Arrendador'
]);

// Document Types
if (!defined('DOCUMENT_TYPES')) define('DOCUMENT_TYPES', [
    'CC' => 'Cédula de Ciudadanía',
    'CE' => 'Cédula de Extranjería',
    'PP' => 'Pasaporte',
    'NIT' => 'NIT'
]);

// Contract Types
if (!defined('CONTRACT_TYPES')) define('CONTRACT_TYPES', [
    'Venta' => 'Venta',
    'Arriendo' => 'Arriendo'
]);

// Contract Status
if (!defined('CONTRACT_STATUS')) define('CONTRACT_STATUS', [
    'Borrador' => 'Borrador',
    'Activo' => 'Activo',
    'Finalizado' => 'Finalizado',
    'Cancelado' => 'Cancelado'
]);

// Rental Status
if (!defined('RENTAL_STATUS')) define('RENTAL_STATUS', [
    'Activo' => 'Activo',
    'Vencido' => 'Vencido',
    'Terminado' => 'Terminado',
    'Moroso' => 'Moroso'
]);

// Visit Status
if (!defined('VISIT_STATUS')) define('VISIT_STATUS', [
    'Programada' => 'Programada',
    'Realizada' => 'Realizada',
    'Cancelada' => 'Cancelada',
    'Reprogramada' => 'Reprogramada'
]);

// Interest Levels for Visits
if (!defined('INTEREST_LEVELS')) define('INTEREST_LEVELS', [
    'Muy Interesado' => 'Muy Interesado',
    'Interesado' => 'Interesado',
    'Poco Interesado' => 'Poco Interesado',
    'No Interesado' => 'No Interesado'
]);

// Colombian Cities (most common)
if (!defined('CITIES')) define('CITIES', [
    'Bogotá' => 'Bogotá D.C.',
    'Medellín' => 'Medellín',
    'Cali' => 'Cali',
    'Barranquilla' => 'Barranquilla',
    'Cartagena' => 'Cartagena',
    'Bucaramanga' => 'Bucaramanga',
    'Pereira' => 'Pereira',
    'Manizales' => 'Manizales',
    'Ibagué' => 'Ibagué',
    'Neiva' => 'Neiva',
    'Villavicencio' => 'Villavicencio',
    'Montería' => 'Montería'
]);

// Error Messages
if (!defined('ERROR_MESSAGES')) define('ERROR_MESSAGES', [
    'required' => 'Este campo es obligatorio',
    'email' => 'Ingrese un email válido',
    'numeric' => 'Este campo debe ser numérico',
    'min_length' => 'Mínimo {min} caracteres',
    'max_length' => 'Máximo {max} caracteres',
    'unique' => 'Este valor ya existe',
    'date' => 'Ingrese una fecha válida',
    'phone' => 'Ingrese un teléfono válido',
    'document' => 'Número de documento inválido'
]);

// Success Messages
if (!defined('SUCCESS_MESSAGES')) define('SUCCESS_MESSAGES', [
    'created' => 'Registro creado exitosamente',
    'updated' => 'Registro actualizado exitosamente',
    'deleted' => 'Registro eliminado exitosamente',
    'uploaded' => 'Archivo subido exitosamente'
]);

// Date and Time Formats
if (!defined('DATE_FORMAT')) define('DATE_FORMAT', 'Y-m-d');
if (!defined('DATETIME_FORMAT')) define('DATETIME_FORMAT', 'Y-m-d H:i:s');
if (!defined('DISPLAY_DATE_FORMAT')) define('DISPLAY_DATE_FORMAT', 'd/m/Y');
if (!defined('DISPLAY_DATETIME_FORMAT')) define('DISPLAY_DATETIME_FORMAT', 'd/m/Y H:i');

// Currency Configuration
if (!defined('CURRENCY_SYMBOL')) define('CURRENCY_SYMBOL', '$');
if (!defined('CURRENCY_CODE')) define('CURRENCY_CODE', 'COP');
if (!defined('CURRENCY_DECIMALS')) define('CURRENCY_DECIMALS', 0);

// Logging Configuration
if (!defined('LOG_ENABLED')) define('LOG_ENABLED', true);
if (!defined('LOG_PATH')) define('LOG_PATH', 'logs/');
if (!defined('LOG_MAX_SIZE')) define('LOG_MAX_SIZE', 10 * 1024 * 1024); // 10MB

// Cache Configuration
if (!defined('CACHE_ENABLED')) define('CACHE_ENABLED', false); // Simple file cache
if (!defined('CACHE_PATH')) define('CACHE_PATH', 'cache/');
if (!defined('CACHE_EXPIRY')) define('CACHE_EXPIRY', 3600); // 1 hour

// Email Configuration (for future enhancements)
if (!defined('MAIL_ENABLED')) define('MAIL_ENABLED', false);
if (!defined('MAIL_HOST')) define('MAIL_HOST', 'smtp.gmail.com');
if (!defined('MAIL_PORT')) define('MAIL_PORT', 587);
if (!defined('MAIL_USERNAME')) define('MAIL_USERNAME', '');
if (!defined('MAIL_PASSWORD')) define('MAIL_PASSWORD', '');
if (!defined('MAIL_FROM_EMAIL')) define('MAIL_FROM_EMAIL', 'noreply@inmobiliaria.com');
if (!defined('MAIL_FROM_NAME')) define('MAIL_FROM_NAME', 'Sistema Inmobiliario');

// Educational Comments Configuration
if (!defined('SHOW_EDUCATIONAL_COMMENTS')) define('SHOW_EDUCATIONAL_COMMENTS', true); // Display helpful comments in forms

// Development Tools
if (ENVIRONMENT === 'development') {
    // Show all errors in development
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);

    // Development-specific constants
    if (!defined('SHOW_DEBUG_INFO')) define('SHOW_DEBUG_INFO', true);
    if (!defined('LOG_QUERIES')) define('LOG_QUERIES', true);
} else {
    // Production settings
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);

    if (!defined('SHOW_DEBUG_INFO')) define('SHOW_DEBUG_INFO', false);
    if (!defined('LOG_QUERIES')) define('LOG_QUERIES', false);
}

// Helper function to get constant arrays (since PHP constants can't hold arrays in older versions)
if (!function_exists('getConstantArray')) {
    function getConstantArray($constantName) {
        switch ($constantName) {
            case 'AVAILABLE_MODULES':
                return AVAILABLE_MODULES;
            case 'PROPERTY_TYPES':
                return PROPERTY_TYPES;
            case 'CLIENT_TYPES':
                return CLIENT_TYPES;
            case 'DOCUMENT_TYPES':
                return DOCUMENT_TYPES;
            case 'CITIES':
                return CITIES;
            default:
                return [];
        }
    }
}

// Application initialization flag - marks end of constants file
define('APP_INITIALIZED', true);
?>
