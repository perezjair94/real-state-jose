<?php
/**
 * Fix UTF-8 Double Encoding in Database
 * This script corrects data that was double-encoded as UTF-8
 */

define('APP_ACCESS', true);
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='utf-8'><title>UTF-8 Encoding Fix</title>";
echo "<style>body { font-family: Arial; padding: 20px; } .success { color: green; } .error { color: red; } pre { background: #f5f5f5; padding: 10px; }</style>";
echo "</head><body>";

echo "<h1>Corrección de Codificación UTF-8</h1>";
echo "<hr>";

try {
    $db = new Database();
    $pdo = $db->getConnection();

    // Start transaction
    $pdo->beginTransaction();

    echo "<h2>Corrigiendo tabla 'inmueble'...</h2>";

    // Get all records
    $stmt = $pdo->query("SELECT id_inmueble, tipo_inmueble, direccion, ciudad, descripcion FROM inmueble");
    $inmuebles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $fixed_count = 0;

    foreach ($inmuebles as $inmueble) {
        $needs_fix = false;
        $fixed_data = [];

        // Check each text field for double encoding
        $fields = ['tipo_inmueble', 'direccion', 'ciudad', 'descripcion'];

        foreach ($fields as $field) {
            $value = $inmueble[$field];

            // Try to fix double encoding
            // When UTF-8 is encoded twice, we need to decode once
            $fixed_value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');

            // Check if it contains mojibake patterns
            if (strpos($value, 'Ã') !== false ||
                strpos($value, 'Ã©') !== false ||
                strpos($value, 'Ã­') !== false ||
                strpos($value, 'Ã³') !== false ||
                strpos($value, 'Ãº') !== false ||
                strpos($value, 'Ã±') !== false) {

                // Fix double encoding by treating as ISO-8859-1 and converting to UTF-8
                $fixed_value = utf8_encode(utf8_decode($value));
                $needs_fix = true;
                $fixed_data[$field] = $fixed_value;

                echo "<div class='success'>";
                echo "ID {$inmueble['id_inmueble']} - {$field}:<br>";
                echo "Antes: {$value}<br>";
                echo "Después: {$fixed_value}<br>";
                echo "</div>";
            }
        }

        // Update if needed
        if ($needs_fix) {
            $update_parts = [];
            $update_values = [];

            foreach ($fixed_data as $field => $value) {
                $update_parts[] = "{$field} = ?";
                $update_values[] = $value;
            }

            $update_values[] = $inmueble['id_inmueble'];

            $sql = "UPDATE inmueble SET " . implode(', ', $update_parts) . " WHERE id_inmueble = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($update_values);

            $fixed_count++;
        }
    }

    echo "<hr>";
    echo "<h2>Corrigiendo tabla 'cliente'...</h2>";

    // Get all clients
    $stmt = $pdo->query("SELECT id_cliente, nombre, apellido, correo, direccion FROM cliente");
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($clientes as $cliente) {
        $needs_fix = false;
        $fixed_data = [];

        $fields = ['nombre', 'apellido', 'correo', 'direccion'];

        foreach ($fields as $field) {
            $value = $cliente[$field];

            if (strpos($value, 'Ã') !== false) {
                $fixed_value = utf8_encode(utf8_decode($value));
                $needs_fix = true;
                $fixed_data[$field] = $fixed_value;

                echo "<div class='success'>";
                echo "Cliente ID {$cliente['id_cliente']} - {$field}:<br>";
                echo "Antes: {$value}<br>";
                echo "Después: {$fixed_value}<br>";
                echo "</div>";
            }
        }

        if ($needs_fix) {
            $update_parts = [];
            $update_values = [];

            foreach ($fixed_data as $field => $value) {
                $update_parts[] = "{$field} = ?";
                $update_values[] = $value;
            }

            $update_values[] = $cliente['id_cliente'];

            $sql = "UPDATE cliente SET " . implode(', ', $update_parts) . " WHERE id_cliente = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($update_values);

            $fixed_count++;
        }
    }

    // Commit transaction
    $pdo->commit();

    echo "<hr>";
    echo "<h2 class='success'>✓ Corrección completada</h2>";
    echo "<p>Total de registros corregidos: {$fixed_count}</p>";

    echo "<hr>";
    echo "<h2>Verificación de resultados:</h2>";

    // Show some corrected data
    $stmt = $pdo->query("SELECT id_inmueble, ciudad FROM inmueble WHERE ciudad LIKE '%Med%' LIMIT 3");
    $test = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<pre>";
    print_r($test);
    echo "</pre>";

    echo "<p><a href='index.php?module=properties'>Ir a lista de propiedades</a></p>";
    echo "<p><a href='test_price_encoding.php'>Ver test de codificación</a></p>";

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo "<h2 class='error'>Error</h2>";
    echo "<p class='error'>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "</body></html>";
?>
