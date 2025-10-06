<?php
/**
 * Fix UTF-8 Encoding Issues in Database
 * Converts incorrectly stored UTF-8 data
 */

define('APP_ACCESS', true);
require_once 'config/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();

    echo "<h2>Corrigiendo Codificación UTF-8</h2>\n";

    // Verify current charset
    $stmt = $pdo->query("SHOW VARIABLES LIKE 'character_set%'");
    echo "<h3>Configuración actual de charset:</h3><pre>\n";
    while ($row = $stmt->fetch()) {
        echo $row['Variable_name'] . " = " . $row['Value'] . "\n";
    }
    echo "</pre>\n";

    // Fix encoding for inmueble table
    echo "<h3>Corrigiendo tabla inmueble...</h3>\n";

    $stmt = $pdo->query("SELECT id_inmueble, ciudad, direccion FROM inmueble");
    $properties = $stmt->fetchAll();

    $fixed = 0;
    foreach ($properties as $prop) {
        // Check if needs fixing (contains Ã characters indicating double-encoding)
        if (strpos($prop['ciudad'], 'Ã') !== false || strpos($prop['direccion'], 'Ã') !== false) {
            // Fix double-encoded UTF-8: decode once to get proper UTF-8
            $ciudadFixed = utf8_decode($prop['ciudad']);
            $direccionFixed = utf8_decode($prop['direccion']);

            $updateStmt = $pdo->prepare("UPDATE inmueble SET ciudad = ?, direccion = ? WHERE id_inmueble = ?");
            $updateStmt->execute([$ciudadFixed, $direccionFixed, $prop['id_inmueble']]);

            echo "✓ Fila {$prop['id_inmueble']}: '{$prop['ciudad']}' → '{$ciudadFixed}'<br>\n";
            $fixed++;
        }
    }

    echo "<p><strong>Total corregido: {$fixed} registros</strong></p>\n";

    // Fix encoding for cliente table
    echo "<h3>Corrigiendo tabla cliente...</h3>\n";

    $stmt = $pdo->query("SELECT id_cliente, nombre, apellido, direccion FROM cliente");
    $clients = $stmt->fetchAll();

    $fixed = 0;
    foreach ($clients as $client) {
        $needsUpdate = false;
        $nombreFixed = $client['nombre'];
        $apellidoFixed = $client['apellido'];
        $direccionFixed = $client['direccion'];

        if (strpos($client['nombre'], 'Ã') !== false) {
            $nombreFixed = utf8_decode($client['nombre']);
            $needsUpdate = true;
        }
        if (strpos($client['apellido'], 'Ã') !== false) {
            $apellidoFixed = utf8_decode($client['apellido']);
            $needsUpdate = true;
        }
        if ($client['direccion'] && strpos($client['direccion'], 'Ã') !== false) {
            $direccionFixed = utf8_decode($client['direccion']);
            $needsUpdate = true;
        }

        if ($needsUpdate) {
            $updateStmt = $pdo->prepare("UPDATE cliente SET nombre = ?, apellido = ?, direccion = ? WHERE id_cliente = ?");
            $updateStmt->execute([$nombreFixed, $apellidoFixed, $direccionFixed, $client['id_cliente']]);

            echo "✓ Cliente {$client['id_cliente']} corregido<br>\n";
            $fixed++;
        }
    }

    echo "<p><strong>Total corregido: {$fixed} clientes</strong></p>\n";

    echo "<hr><h3>✅ Proceso completado</h3>\n";
    echo "<p><a href='test_upload.php'>Ver resultados en test_upload.php</a></p>\n";

} catch (Exception $e) {
    echo "<h3>❌ Error:</h3>\n";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>
