#!/usr/bin/env php
<?php
/**
 * Migration Runner - Real Estate Management System
 * Utility script to apply database migrations
 *
 * Usage:
 *   php run_migration.php <migration_file>
 *   php run_migration.php all
 *
 * Examples:
 *   php run_migration.php 000_initial_schema.sql
 *   php run_migration.php all
 */

// Check if running from command line
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

require_once __DIR__ . '/config/database.php';

// Colors for terminal output
class Colors {
    public static $GREEN = "\033[0;32m";
    public static $RED = "\033[0;31m";
    public static $YELLOW = "\033[1;33m";
    public static $BLUE = "\033[0;34m";
    public static $NC = "\033[0m"; // No Color
}

function printSuccess($message) {
    echo Colors::$GREEN . "✓ " . $message . Colors::$NC . "\n";
}

function printError($message) {
    echo Colors::$RED . "✗ " . $message . Colors::$NC . "\n";
}

function printWarning($message) {
    echo Colors::$YELLOW . "⚠ " . $message . Colors::$NC . "\n";
}

function printInfo($message) {
    echo Colors::$BLUE . "ℹ " . $message . Colors::$NC . "\n";
}

function getMigrationFiles() {
    $migrationsDir = __DIR__ . '/database/migrations';
    $files = glob($migrationsDir . '/*.sql');

    // Filter and sort migration files
    $migrations = [];
    foreach ($files as $file) {
        $basename = basename($file);
        // Only include numbered migration files (e.g., 000_*.sql, 001_*.sql)
        if (preg_match('/^\d{3}_.*\.sql$/', $basename)) {
            $migrations[] = $basename;
        }
    }

    sort($migrations);
    return $migrations;
}

function runMigration($pdo, $migrationFile) {
    $filePath = __DIR__ . '/database/migrations/' . $migrationFile;

    if (!file_exists($filePath)) {
        printError("Migration file not found: $migrationFile");
        return false;
    }

    printInfo("Running migration: $migrationFile");

    // Read the SQL file
    $sql = file_get_contents($filePath);

    if ($sql === false) {
        printError("Could not read migration file: $migrationFile");
        return false;
    }

    try {
        // Split SQL by delimiter to handle triggers
        $statements = [];
        $currentStatement = '';
        $inDelimiter = false;

        foreach (explode("\n", $sql) as $line) {
            $trimmedLine = trim($line);

            // Skip empty lines and comments
            if (empty($trimmedLine) || substr($trimmedLine, 0, 2) === '--') {
                continue;
            }

            // Check for DELIMITER command
            if (stripos($trimmedLine, 'DELIMITER') === 0) {
                if (!$inDelimiter) {
                    $inDelimiter = true;
                } else {
                    $inDelimiter = false;
                    if (!empty($currentStatement)) {
                        $statements[] = trim($currentStatement);
                        $currentStatement = '';
                    }
                }
                continue;
            }

            $currentStatement .= $line . "\n";

            // If not in delimiter block and line ends with semicolon, it's a complete statement
            if (!$inDelimiter && substr($trimmedLine, -1) === ';') {
                $statements[] = trim($currentStatement);
                $currentStatement = '';
            }
        }

        // Add any remaining statement
        if (!empty(trim($currentStatement))) {
            $statements[] = trim($currentStatement);
        }

        // Execute each statement
        foreach ($statements as $statement) {
            if (empty($statement)) continue;

            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                // Ignore "already exists" errors for idempotent migrations
                if (strpos($e->getMessage(), 'already exists') === false) {
                    throw $e;
                }
            }
        }

        printSuccess("Migration completed: $migrationFile");
        return true;

    } catch (PDOException $e) {
        printError("Migration failed: " . $e->getMessage());
        return false;
    }
}

// ============================================================================
// MAIN SCRIPT
// ============================================================================

echo "\n";
echo "========================================\n";
echo "  Real Estate DB Migration Runner\n";
echo "========================================\n\n";

// Check command line arguments
if ($argc < 2) {
    printError("Missing argument: migration file name");
    echo "\nUsage:\n";
    echo "  php run_migration.php <migration_file>\n";
    echo "  php run_migration.php all\n\n";
    echo "Available migrations:\n";
    foreach (getMigrationFiles() as $file) {
        echo "  - $file\n";
    }
    exit(1);
}

$migrationArg = $argv[1];

try {
    // Connect to database
    $database = new Database();
    $pdo = $database->getConnection();

    printSuccess("Connected to database: real_estate_db");
    echo "\n";

    // Run migrations
    if ($migrationArg === 'all') {
        printInfo("Running all migrations in order...");
        echo "\n";

        $migrations = getMigrationFiles();
        if (empty($migrations)) {
            printWarning("No migration files found.");
            exit(0);
        }

        $successCount = 0;
        $failCount = 0;

        foreach ($migrations as $migration) {
            if (runMigration($pdo, $migration)) {
                $successCount++;
            } else {
                $failCount++;
            }
            echo "\n";
        }

        echo "========================================\n";
        printInfo("Migrations completed: $successCount successful, $failCount failed");
        echo "========================================\n\n";

    } else {
        // Run specific migration
        $migrationFile = $migrationArg;

        // Add .sql extension if not provided
        if (substr($migrationFile, -4) !== '.sql') {
            $migrationFile .= '.sql';
        }

        if (runMigration($pdo, $migrationFile)) {
            echo "\n";
            printSuccess("Migration successful!");
        } else {
            echo "\n";
            printError("Migration failed!");
            exit(1);
        }
    }

} catch (Exception $e) {
    printError("Database connection failed: " . $e->getMessage());
    exit(1);
}

echo "\n";
?>
