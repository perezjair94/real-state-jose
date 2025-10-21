<?php
/**
 * Test script to verify inmueble table and execute the query
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    echo "Database connection successful!\n\n";

    // Check if inmueble table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'inmueble'");
    $tableExists = $stmt->rowCount() > 0;

    if ($tableExists) {
        echo "✓ Table 'inmueble' exists\n\n";

        // Execute the query from the error
        $query = "
            SELECT 'Total properties' AS Description, COUNT(*) AS Count FROM inmueble
            UNION ALL
            SELECT 'Disponible', COUNT(*) FROM inmueble WHERE estado = 'Disponible'
            UNION ALL
            SELECT 'Vendido', COUNT(*) FROM inmueble WHERE estado = 'Vendido'
            UNION ALL
            SELECT 'Arrendado', COUNT(*) FROM inmueble WHERE estado = 'Arrendado'
        ";

        $stmt = $pdo->query($query);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "Query Results:\n";
        echo "==============\n";
        foreach ($results as $row) {
            printf("%-20s: %d\n", $row['Description'], $row['Count']);
        }

    } else {
        echo "✗ Table 'inmueble' does NOT exist\n";
        echo "You need to import the schema.sql file:\n";
        echo "mysql -u root -p real_estate_db < database/schema.sql\n";
    }

    // Show all tables in database
    echo "\n\nTables in database:\n";
    echo "==================\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo "- $table\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    error_log($e->getMessage());
}
?>
