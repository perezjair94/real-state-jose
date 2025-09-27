<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    echo "<h2>✅ Conexión exitosa a MySQL</h2>";

    // Mostrar información de la conexión
    $info = $database->getConnectionInfo();
    echo "<p><strong>Host:</strong> {$info['host']}</p>";
    echo "<p><strong>Base de datos:</strong> {$info['database']}</p>";
    echo "<p><strong>Charset:</strong> {$info['charset']}</p>";

    // Probar una consulta simple
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM cliente");
    $result = $stmt->fetch();
    echo "<p><strong>Total de clientes:</strong> {$result['total']}</p>";

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM inmueble");
    $result = $stmt->fetch();
    echo "<p><strong>Total de inmuebles:</strong> {$result['total']}</p>";

    echo "<p style='color: green;'>✅ Base de datos configurada correctamente</p>";

} catch (Exception $e) {
    echo "<h2>❌ Error de conexión</h2>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>