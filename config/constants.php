<?php
/**
 * Application Constants - Real Estate Management System
 * Central configuration for the application
 * Educational PHP/MySQL Project
 */

// Prevent direct access
if (!defined('APP_ACCESS')) {
    define('APP_ACCESS', true);
}

// Application Information
define('APP_NAME', 'Sistema de Gestión Inmobiliaria');
define('APP_VERSION', '1.0.0');
define('APP_DESCRIPTION', 'Sistema educativo para gestión de propiedades inmobiliarias');
define('APP_AUTHOR', 'Proyecto Educativo PHP/MySQL');

// Environment Configuration
define('ENVIRONMENT', 'development'); // development, production
define('DEBUG_MODE', true); // Set to false in production

// Security Configuration
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('MAX_LOGIN_ATTEMPTS', 5);
define('PASSWORD_MIN_LENGTH', 8);
define('CSRF_TOKEN_EXPIRY', 1800); // 30 minutes

// File Upload Configuration
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB in bytes
define('UPLOAD_PATH_PROPERTIES', 'assets/uploads/properties/');
define('UPLOAD_PATH_CONTRACTS', 'assets/uploads/contracts/');

// Allowed file types for uploads
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_DOCUMENT_TYPES', ['pdf', 'doc', 'docx', 'txt']);

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'real_estate_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application URLs (adjust for your environment)
define('BASE_URL', 'http://localhost/real-estate-system/');
define('ASSETS_URL', BASE_URL . 'assets/');
define('UPLOADS_URL', BASE_URL . 'assets/uploads/');

// Pagination Settings
define('RECORDS_PER_PAGE', 10);
define('MAX_PAGINATION_LINKS', 10);

// Module Configuration
define('AVAILABLE_MODULES', [
    'properties' => 'Inmuebles',
    'clients' => 'Clientes',
    'agents' => 'Agentes',
    'sales' => 'Ventas',
    'contracts' => 'Contratos',
    'rentals' => 'Arriendos',
    'visits' => 'Visitas'
]);

// Menu Structure with Submenus
define('MENU_STRUCTURE', [
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
define('DEFAULT_MODULE', 'properties');

// Available Actions per Module
define('MODULE_ACTIONS', [
    'list' => 'Listar',
    'create' => 'Crear',
    'edit' => 'Editar',
    'view' => 'Ver',
    'delete' => 'Eliminar'
]);

// Property Types
define('PROPERTY_TYPES', [
    'Casa' => 'Casa',
    'Apartamento' => 'Apartamento',
    'Local' => 'Local Comercial',
    'Oficina' => 'Oficina',
    'Lote' => 'Lote'
]);

// Property Status
define('PROPERTY_STATUS', [
    'Disponible' => 'Disponible',
    'Vendido' => 'Vendido',
    'Arrendado' => 'Arrendado',
    'Reservado' => 'Reservado'
]);

// Client Types
define('CLIENT_TYPES', [
    'Comprador' => 'Comprador',
    'Vendedor' => 'Vendedor',
    'Arrendatario' => 'Arrendatario',
    'Arrendador' => 'Arrendador'
]);

// Document Types
define('DOCUMENT_TYPES', [
    'CC' => 'Cédula de Ciudadanía',
    'CE' => 'Cédula de Extranjería',
    'PP' => 'Pasaporte',
    'NIT' => 'NIT'
]);

// Contract Types
define('CONTRACT_TYPES', [
    'Venta' => 'Venta',
    'Arriendo' => 'Arriendo'
]);

// Contract Status
define('CONTRACT_STATUS', [
    'Borrador' => 'Borrador',
    'Activo' => 'Activo',
    'Finalizado' => 'Finalizado',
    'Cancelado' => 'Cancelado'
]);

// Rental Status
define('RENTAL_STATUS', [
    'Activo' => 'Activo',
    'Vencido' => 'Vencido',
    'Terminado' => 'Terminado',
    'Moroso' => 'Moroso'
]);

// Visit Status
define('VISIT_STATUS', [
    'Programada' => 'Programada',
    'Realizada' => 'Realizada',
    'Cancelada' => 'Cancelada',
    'Reprogramada' => 'Reprogramada'
]);

// Interest Levels for Visits
define('INTEREST_LEVELS', [
    'Muy Interesado' => 'Muy Interesado',
    'Interesado' => 'Interesado',
    'Poco Interesado' => 'Poco Interesado',
    'No Interesado' => 'No Interesado'
]);

// Colombian Cities (most common)
define('CITIES', [
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
define('ERROR_MESSAGES', [
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
define('SUCCESS_MESSAGES', [
    'created' => 'Registro creado exitosamente',
    'updated' => 'Registro actualizado exitosamente',
    'deleted' => 'Registro eliminado exitosamente',
    'uploaded' => 'Archivo subido exitosamente'
]);

// Date and Time Formats
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'd/m/Y');
define('DISPLAY_DATETIME_FORMAT', 'd/m/Y H:i');

// Currency Configuration
define('CURRENCY_SYMBOL', '$');
define('CURRENCY_CODE', 'COP');
define('CURRENCY_DECIMALS', 0);

// Logging Configuration
define('LOG_ENABLED', true);
define('LOG_PATH', 'logs/');
define('LOG_MAX_SIZE', 10 * 1024 * 1024); // 10MB

// Cache Configuration
define('CACHE_ENABLED', false); // Simple file cache
define('CACHE_PATH', 'cache/');
define('CACHE_EXPIRY', 3600); // 1 hour

// Email Configuration (for future enhancements)
define('MAIL_ENABLED', false);
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', '');
define('MAIL_PASSWORD', '');
define('MAIL_FROM_EMAIL', 'noreply@inmobiliaria.com');
define('MAIL_FROM_NAME', 'Sistema Inmobiliario');

// Educational Comments Configuration
define('SHOW_EDUCATIONAL_COMMENTS', true); // Display helpful comments in forms

// Development Tools
if (ENVIRONMENT === 'development') {
    // Show all errors in development
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);

    // Development-specific constants
    define('SHOW_DEBUG_INFO', true);
    define('LOG_QUERIES', true);
} else {
    // Production settings
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);

    define('SHOW_DEBUG_INFO', false);
    define('LOG_QUERIES', false);
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

// Application initialization flag
define('APP_INITIALIZED', true);
?>
