<?php
/**
 * Database Configuration - Real Estate Management System
 * Secure PDO connection following 2024 security best practices
 * Educational PHP/MySQL Project
 */

class Database {
    // Database configuration - modify these values for your environment
    private $host = '127.0.0.1';
    private $db_name = 'real_estate_db';
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';
    private $pdo;

    /**
     * Get secure PDO database connection
     * Implements 2024 security best practices:
     * - UTF-8 character set (utf8mb4) for full Unicode support
     * - Prepared statement emulation disabled for security
     * - Exception mode for proper error handling
     * - Associative array fetch mode by default
     *
     * @return PDO Database connection instance
     * @throws Exception If connection fails
     */
    public function getConnection() {
        // Return existing connection if already established
        if ($this->pdo !== null) {
            return $this->pdo;
        }

        try {
            // Build DSN (Data Source Name) with charset
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";

            // PDO options for security and functionality
            $options = [
                // SECURITY: Disable prepared statement emulation to prevent SQL injection
                PDO::ATTR_EMULATE_PREPARES => false,

                // ERROR HANDLING: Use exceptions for better error management
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

                // FETCH MODE: Default to associative arrays for cleaner code
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

                // CHARACTER SET: Ensure proper UTF-8 handling
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}",

                // CONNECTION: Persistent connections for better performance
                PDO::ATTR_PERSISTENT => false, // Set to true for production if needed

                // TIMEOUT: Set connection timeout (seconds)
                PDO::ATTR_TIMEOUT => 30
            ];

            // Create PDO connection
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);

            // Additional security settings
            $this->pdo->exec("SET sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");

        } catch(PDOException $e) {
            // Log the actual error for developers (never show to users)
            error_log("Database connection error: " . $e->getMessage());
            error_log("Error occurred at: " . date('Y-m-d H:i:s'));

            // Throw a generic error message for security
            throw new Exception("Database connection failed. Please contact the administrator.");
        }

        return $this->pdo;
    }

    /**
     * Test database connection
     * Utility method for setup and debugging
     *
     * @return bool True if connection successful
     */
    public function testConnection() {
        try {
            $pdo = $this->getConnection();
            $stmt = $pdo->query("SELECT 1");
            return $stmt !== false;
        } catch (Exception $e) {
            error_log("Database test failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Close database connection
     * Explicitly close the connection when done
     */
    public function closeConnection() {
        $this->pdo = null;
    }

    /**
     * Get database configuration info (for setup/debugging)
     * Returns safe information without sensitive data
     *
     * @return array Configuration information
     */
    public function getConnectionInfo() {
        return [
            'host' => $this->host,
            'database' => $this->db_name,
            'charset' => $this->charset,
            'connected' => ($this->pdo !== null)
        ];
    }

    /**
     * Execute a query with error handling
     * Helper method for common database operations
     *
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters for prepared statement
     * @return PDOStatement|false Statement object or false on failure
     */
    public function execute($sql, $params = []) {
        try {
            $pdo = $this->getConnection();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query execution error: " . $e->getMessage());
            error_log("Query: " . $sql);
            error_log("Params: " . json_encode($params));
            return false;
        }
    }

    /**
     * Get last inserted ID
     * Utility method for getting auto-increment IDs
     *
     * @return string Last insert ID
     */
    public function getLastInsertId() {
        try {
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error getting last insert ID: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Begin database transaction
     * For operations that require atomicity
     *
     * @return bool True on success
     */
    public function beginTransaction() {
        try {
            return $this->pdo->beginTransaction();
        } catch (PDOException $e) {
            error_log("Error starting transaction: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Commit database transaction
     *
     * @return bool True on success
     */
    public function commit() {
        try {
            return $this->pdo->commit();
        } catch (PDOException $e) {
            error_log("Error committing transaction: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Rollback database transaction
     *
     * @return bool True on success
     */
    public function rollback() {
        try {
            return $this->pdo->rollback();
        } catch (PDOException $e) {
            error_log("Error rolling back transaction: " . $e->getMessage());
            return false;
        }
    }
}

// Example usage (for educational purposes):
/*
try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Example prepared statement
    $stmt = $pdo->prepare("SELECT * FROM cliente WHERE id_cliente = ?");
    $stmt->execute([1]);
    $client = $stmt->fetch();

    echo "Connection successful!";
} catch (Exception $e) {
    echo "Connection failed: " . $e->getMessage();
}
*/
?>