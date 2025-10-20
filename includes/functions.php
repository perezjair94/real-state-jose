<?php
/**
 * Utility Functions and Classes - Real Estate Management System
 * Security, validation, and helper functions
 * Educational PHP/MySQL Project - 2024 Best Practices
 */

// Prevent direct access
if (!defined('APP_ACCESS')) {
    die('Direct access not permitted');
}

/**
 * Server-Side Validation Class
 * Implements comprehensive validation with 2024 security standards
 */
class Validator {
    private $errors = [];
    private $data = [];

    /**
     * Validate data against rules
     *
     * @param array $data Input data to validate
     * @param array $rules Validation rules
     * @return bool True if validation passes
     */
    public function validate($data, $rules) {
        $this->data = $data;
        $this->errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? '';
            $this->validateField($field, $value, $fieldRules);
        }

        return empty($this->errors);
    }

    /**
     * Validate individual field
     *
     * @param string $field Field name
     * @param mixed $value Field value
     * @param array $rules Validation rules for this field
     */
    private function validateField($field, $value, $rules) {
        foreach ($rules as $rule) {
            if (strpos($rule, ':') !== false) {
                list($ruleName, $parameter) = explode(':', $rule, 2);
            } else {
                $ruleName = $rule;
                $parameter = null;
            }

            switch ($ruleName) {
                case 'required':
                    if (empty(trim($value))) {
                        $this->addError($field, ERROR_MESSAGES['required']);
                    }
                    break;

                case 'email':
                    if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $this->addError($field, ERROR_MESSAGES['email']);
                    }
                    break;

                case 'numeric':
                    if (!empty($value) && !is_numeric($value)) {
                        $this->addError($field, ERROR_MESSAGES['numeric']);
                    }
                    break;

                case 'integer':
                    if (!empty($value) && !filter_var($value, FILTER_VALIDATE_INT)) {
                        $this->addError($field, ERROR_MESSAGES['numeric']);
                    }
                    break;

                case 'decimal':
                    if (!empty($value) && !is_numeric($value)) {
                        $this->addError($field, ERROR_MESSAGES['numeric']);
                    }
                    break;

                case 'min_length':
                    if (strlen($value) < $parameter) {
                        $message = str_replace('{min}', $parameter, ERROR_MESSAGES['min_length']);
                        $this->addError($field, $message);
                    }
                    break;

                case 'max_length':
                    if (strlen($value) > $parameter) {
                        $message = str_replace('{max}', $parameter, ERROR_MESSAGES['max_length']);
                        $this->addError($field, $message);
                    }
                    break;

                case 'unique':
                    if ($this->checkUnique($field, $value, $parameter)) {
                        $this->addError($field, ERROR_MESSAGES['unique']);
                    }
                    break;

                case 'date':
                    if (!empty($value) && !$this->isValidDate($value)) {
                        $this->addError($field, ERROR_MESSAGES['date']);
                    }
                    break;

                case 'phone':
                    if (!empty($value) && !$this->isValidPhone($value)) {
                        $this->addError($field, ERROR_MESSAGES['phone']);
                    }
                    break;

                case 'document':
                    if (!empty($value) && !$this->isValidDocument($value)) {
                        $this->addError($field, ERROR_MESSAGES['document']);
                    }
                    break;

                case 'min_value':
                    if (is_numeric($value) && $value < $parameter) {
                        $this->addError($field, "El valor mínimo es {$parameter}");
                    }
                    break;

                case 'max_value':
                    if (is_numeric($value) && $value > $parameter) {
                        $this->addError($field, "El valor máximo es {$parameter}");
                    }
                    break;
            }
        }
    }

    /**
     * Add validation error
     *
     * @param string $field Field name
     * @param string $message Error message
     */
    private function addError($field, $message) {
        $this->errors[$field] = $message;
    }

    /**
     * Get all validation errors
     *
     * @return array All errors
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Get error for specific field
     *
     * @param string $field Field name
     * @return string Error message or empty string
     */
    public function getError($field) {
        return $this->errors[$field] ?? '';
    }

    /**
     * Check if value is unique in database
     *
     * @param string $field Field name
     * @param mixed $value Value to check
     * @param string $table Table name
     * @return bool True if value exists (not unique)
     */
    private function checkUnique($field, $value, $table) {
        try {
            $db = new Database();
            $pdo = $db->getConnection();

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE {$field} = ?");
            $stmt->execute([$value]);

            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("Unique validation error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate date format
     *
     * @param string $date Date string
     * @return bool True if valid date
     */
    private function isValidDate($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Validate phone number (Colombian format)
     *
     * @param string $phone Phone number
     * @return bool True if valid phone
     */
    private function isValidPhone($phone) {
        // Remove spaces and dashes
        $phone = preg_replace('/[\s\-]/', '', $phone);

        // Colombian phone patterns: 10 digits for mobile, 7 for landline
        return preg_match('/^([0-9]{7}|[0-9]{10})$/', $phone);
    }

    /**
     * Validate document number
     *
     * @param string $document Document number
     * @return bool True if valid document
     */
    private function isValidDocument($document) {
        // Basic validation: at least 5 characters, alphanumeric
        return preg_match('/^[A-Za-z0-9]{5,20}$/', $document);
    }
}

/**
 * Security Helper Functions
 */

/**
 * Sanitize input data to prevent XSS attacks
 *
 * @param mixed $data Input data (string or array)
 * @return mixed Sanitized data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }

    // Remove whitespace, strip tags, convert special characters
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF token for form security
 *
 * @return string CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token']) ||
        !isset($_SESSION['csrf_token_time']) ||
        (time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_EXPIRY) {

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }

    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 *
 * @param string $token Token to verify
 * @return bool True if valid
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) &&
           hash_equals($_SESSION['csrf_token'], $token) &&
           (time() - $_SESSION['csrf_token_time']) <= CSRF_TOKEN_EXPIRY;
}

/**
 * Hash password securely
 *
 * @param string $password Plain text password
 * @return string Hashed password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password against hash
 *
 * @param string $password Plain text password
 * @param string $hash Stored password hash
 * @return bool True if password matches
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * User Interface Helper Functions
 */

/**
 * Redirect with flash message
 *
 * @param string $url Redirect URL
 * @param string $message Flash message
 * @param string $type Message type (success, error, warning, info)
 */
function redirectWithMessage($url, $message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    header("Location: {$url}");
    exit;
}

/**
 * Display flash message and clear it
 *
 * @return string HTML for flash message
 */
function displayFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = htmlspecialchars($_SESSION['flash_message']);
        $type = $_SESSION['flash_type'] ?? 'info';

        // Clear the message
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);

        return "<div class='alert alert-{$type} alert-dismissible' role='alert'>
                    <button type='button' class='close' onclick='this.parentElement.style.display=\"none\"'>&times;</button>
                    {$message}
                </div>";
    }

    return '';
}

/**
 * Format currency amount (Colombian format)
 * Example: $ 1.500.000 (punto para miles, sin decimales)
 *
 * @param float $amount Amount to format
 * @param bool $showCode Show currency code (COP)
 * @return string Formatted currency
 */
function formatCurrency($amount, $showCode = false) {
    // Colombian format: $ sign, dot for thousands, no decimals
    $formatted = CURRENCY_SYMBOL . ' ' . number_format($amount, CURRENCY_DECIMALS, ',', '.');

    if ($showCode) {
        $formatted .= ' ' . CURRENCY_CODE;
    }

    return $formatted;
}

/**
 * Format date for display
 *
 * @param string $date Date string
 * @param string $format Output format
 * @return string Formatted date
 */
function formatDate($date, $format = DISPLAY_DATE_FORMAT) {
    if (empty($date) || $date === '0000-00-00') {
        return '';
    }

    $dateObj = DateTime::createFromFormat('Y-m-d', $date);
    if ($dateObj) {
        return $dateObj->format($format);
    }

    return $date;
}

/**
 * Generate auto-increment ID with prefix
 *
 * @param string $prefix ID prefix (CLI, INM, AGE, etc.)
 * @param int $number Sequential number
 * @return string Formatted ID
 */
function generateFormattedId($prefix, $number) {
    return $prefix . str_pad($number, 3, '0', STR_PAD_LEFT);
}

/**
 * Database Helper Functions
 */

/**
 * Execute database query with error handling
 *
 * @param string $sql SQL query
 * @param array $params Parameters
 * @return PDOStatement|false
 */
function executeQuery($sql, $params = []) {
    try {
        $db = new Database();
        $pdo = $db->getConnection();

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    } catch (PDOException $e) {
        error_log("Query error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get single record from database
 *
 * @param string $table Table name
 * @param string $idField ID field name
 * @param mixed $idValue ID value
 * @return array|false Record or false if not found
 */
function getRecord($table, $idField, $idValue) {
    $sql = "SELECT * FROM {$table} WHERE {$idField} = ?";
    $stmt = executeQuery($sql, [$idValue]);

    if ($stmt) {
        return $stmt->fetch();
    }

    return false;
}

/**
 * Insert record into database
 *
 * @param string $table Table name
 * @param array $data Data to insert
 * @return int|false Insert ID or false on failure
 */
function insertRecord($table, $data) {
    $fields = array_keys($data);
    $placeholders = array_fill(0, count($fields), '?');

    $sql = "INSERT INTO {$table} (" . implode(', ', $fields) . ")
            VALUES (" . implode(', ', $placeholders) . ")";

    $stmt = executeQuery($sql, array_values($data));

    if ($stmt) {
        $db = new Database();
        return $db->getLastInsertId();
    }

    return false;
}

/**
 * Update record in database
 *
 * @param string $table Table name
 * @param array $data Data to update
 * @param string $idField ID field name
 * @param mixed $idValue ID value
 * @return bool Success status
 */
function updateRecord($table, $data, $idField, $idValue) {
    $fields = array_keys($data);
    $setClause = implode(' = ?, ', $fields) . ' = ?';

    $sql = "UPDATE {$table} SET {$setClause} WHERE {$idField} = ?";

    $params = array_values($data);
    $params[] = $idValue;

    $stmt = executeQuery($sql, $params);

    return $stmt !== false;
}

/**
 * Delete record from database
 *
 * @param string $table Table name
 * @param string $idField ID field name
 * @param mixed $idValue ID value
 * @return bool Success status
 */
function deleteRecord($table, $idField, $idValue) {
    $sql = "DELETE FROM {$table} WHERE {$idField} = ?";
    $stmt = executeQuery($sql, [$idValue]);

    return $stmt !== false;
}

/**
 * File Upload Helper Functions
 */

/**
 * Validate uploaded file
 *
 * @param array $file $_FILES array element
 * @param array $allowedTypes Allowed file extensions
 * @param int $maxSize Maximum file size in bytes
 * @return array Validation result with 'valid' and 'error' keys
 */
function validateUploadedFile($file, $allowedTypes, $maxSize = UPLOAD_MAX_SIZE) {
    $result = ['valid' => false, 'error' => ''];

    // Check if file was uploaded
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        $result['error'] = 'No se seleccionó ningún archivo';
        return $result;
    }

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $result['error'] = 'Error al subir el archivo';
        return $result;
    }

    // Check file size
    if ($file['size'] > $maxSize) {
        $result['error'] = 'El archivo es demasiado grande (máximo ' . formatBytes($maxSize) . ')';
        return $result;
    }

    // Check file type
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedTypes)) {
        $result['error'] = 'Tipo de archivo no permitido. Permitidos: ' . implode(', ', $allowedTypes);
        return $result;
    }

    $result['valid'] = true;
    return $result;
}

/**
 * Format bytes to human readable format
 *
 * @param int $bytes Number of bytes
 * @return string Formatted size
 */
function formatBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    $bytes /= (1 << (10 * $pow));

    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Generate unique filename
 *
 * @param string $originalName Original filename
 * @return string Unique filename
 */
function generateUniqueFilename($originalName) {
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $filename = pathinfo($originalName, PATHINFO_FILENAME);
    $filename = preg_replace('/[^a-zA-Z0-9_-]/', '', $filename);

    return $filename . '_' . uniqid() . '.' . $extension;
}

/**
 * Debug and Development Functions
 */

/**
 * Debug output (only in development)
 *
 * @param mixed $data Data to debug
 * @param string $label Optional label
 */
function debug($data, $label = '') {
    if (ENVIRONMENT === 'development' && SHOW_DEBUG_INFO) {
        echo '<div style="background: #f0f0f0; border: 1px solid #ccc; padding: 10px; margin: 10px 0;">';
        if ($label) {
            echo '<strong>' . htmlspecialchars($label) . ':</strong><br>';
        }
        echo '<pre>' . htmlspecialchars(print_r($data, true)) . '</pre>';
        echo '</div>';
    }
}

/**
 * Log message to file
 *
 * @param string $message Message to log
 * @param string $level Log level (ERROR, WARNING, INFO)
 */
function logMessage($message, $level = 'INFO') {
    if (LOG_ENABLED) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;

        $logFile = LOG_PATH . 'app_' . date('Y-m-d') . '.log';

        // Create log directory if it doesn't exist
        if (!is_dir(LOG_PATH)) {
            mkdir(LOG_PATH, 0755, true);
        }

        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}

/**
 * Authentication and Authorization Functions
 */

/**
 * Check if user is logged in
 *
 * @return bool True if logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) &&
           isset($_SESSION['logged_in']) &&
           $_SESSION['logged_in'] === true;
}

/**
 * Check if user has specific role
 *
 * @param string $role Role to check (admin, cliente)
 * @return bool True if user has role
 */
function hasRole($role) {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

/**
 * Require login - redirect to login if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../login.php');
        exit;
    }
}

/**
 * Require specific role - redirect to appropriate dashboard if wrong role
 *
 * @param string $requiredRole Required role
 */
function requireRole($requiredRole) {
    requireLogin();

    if (!hasRole($requiredRole)) {
        // Redirect to appropriate dashboard based on current role
        if (hasRole('admin')) {
            header('Location: ../admin/dashboard.php');
        } else {
            header('Location: ../cliente/dashboard.php');
        }
        exit;
    }
}

/**
 * Get current user info
 *
 * @return array User information from session
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }

    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? '',
        'nombre_completo' => $_SESSION['nombre_completo'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'rol' => $_SESSION['user_role'] ?? '',
        'id_cliente' => $_SESSION['id_cliente'] ?? null
    ];
}

/**
 * Logout user
 */
function logout() {
    // Clear all session variables
    $_SESSION = [];

    // Destroy session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }

    // Destroy session
    session_destroy();

    // Redirect to login
    header('Location: ../login.php?logout=1');
    exit;
}

/**
 * Initialize application session
 */
function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();

        // Session security
        if (!isset($_SESSION['initiated'])) {
            session_regenerate_id(true);
            $_SESSION['initiated'] = true;
        }

        // Session timeout
        if (isset($_SESSION['last_activity']) &&
            (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
            session_destroy();
            session_start();
        }

        $_SESSION['last_activity'] = time();
    }
}

// Educational note: All functions use prepared statements and input sanitization
// to prevent SQL injection and XSS attacks, following 2024 security best practices
?>