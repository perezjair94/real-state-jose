<?php
/**
 * Fix Property Prices - Remove formatting from database
 * This script cleans up prices that have been saved with formatting
 */

// Include configuration
require_once 'config/database.php';
require_once 'config/constants.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();

    // Get all properties
    $stmt = $pdo->query("SELECT id_inmueble, precio FROM inmueble");
    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $updatedCount = 0;
    $errors = [];

    echo "Starting price cleanup...\n\n";

    foreach ($properties as $property) {
        $originalPrice = $property['precio'];

        // Remove all non-numeric characters (dots, commas, spaces, etc.)
        $cleanPrice = preg_replace('/[^\d]/', '', $originalPrice);

        // Convert to integer
        $cleanPrice = intval($cleanPrice);

        // Only update if the price changed
        if ($originalPrice != $cleanPrice) {
            echo "ID {$property['id_inmueble']}: '{$originalPrice}' -> '{$cleanPrice}'\n";

            $updateStmt = $pdo->prepare("UPDATE inmueble SET precio = ? WHERE id_inmueble = ?");
            if ($updateStmt->execute([$cleanPrice, $property['id_inmueble']])) {
                $updatedCount++;
            } else {
                $errors[] = "Error updating property ID {$property['id_inmueble']}";
            }
        }
    }

    echo "\n--- Summary ---\n";
    echo "Total properties checked: " . count($properties) . "\n";
    echo "Prices updated: {$updatedCount}\n";

    if (!empty($errors)) {
        echo "\nErrors:\n";
        foreach ($errors as $error) {
            echo "  - {$error}\n";
        }
    } else {
        echo "No errors!\n";
    }

    echo "\nPrice cleanup completed successfully!\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
