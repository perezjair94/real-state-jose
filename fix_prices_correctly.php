<?php
/**
 * Fix Property Prices - Correct the decimal issue
 * This script properly handles decimal prices
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

    echo "Starting price correction...\n\n";

    foreach ($properties as $property) {
        $originalPrice = $property['precio'];

        // Convert to float first to handle decimals
        $floatPrice = floatval($originalPrice);

        // If the price is absurdly large (more than 10 billion), divide by 100
        if ($floatPrice > 10000000000) {
            $cleanPrice = intval($floatPrice / 100);
            echo "ID {$property['id_inmueble']}: '{$originalPrice}' -> '{$cleanPrice}' (divided by 100)\n";

            $updateStmt = $pdo->prepare("UPDATE inmueble SET precio = ? WHERE id_inmueble = ?");
            if ($updateStmt->execute([$cleanPrice, $property['id_inmueble']])) {
                $updatedCount++;
            } else {
                $errors[] = "Error updating property ID {$property['id_inmueble']}";
            }
        } else {
            // For small prices (test data), just convert to int
            $cleanPrice = intval($floatPrice);
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

    echo "\nPrice correction completed successfully!\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
