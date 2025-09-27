<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug de Conexión MySQL</h2>";

// 1. Verificar extensiones PHP
echo "<h3>1. Extensiones PHP</h3>";
if (extension_loaded('pdo')) {
    echo "✅ PDO está disponible<br>";
} else {
    echo "❌ PDO NO está disponible<br>";
}

if (extension_loaded('pdo_mysql')) {
    echo "✅ PDO MySQL está disponible<br>";
} else {
    echo "❌ PDO MySQL NO está disponible<br>";
}

// 2. Probar conexión básica
echo "<h3>2. Prueba de Conexión Básica</h3>";
try {
    $host = '127.0.0.1';
    $port = '3306';
    $dbname = 'real_estate_db';
    $username = 'root';
    $password = '';

    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    echo "DSN: $dsn<br>";

    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "✅ Conexión PDO exitosa<br>";

    // Probar consulta simple
    $stmt = $pdo->query("SELECT DATABASE() as db_name");
    $result = $stmt->fetch();
    echo "Base de datos actual: " . $result['db_name'] . "<br>";

    $stmt = $pdo->query("SELECT USER() as user_name");
    $result = $stmt->fetch();
    echo "Usuario actual: " . $result['user_name'] . "<br>";

    $stmt = $pdo->query("SELECT NOW() as tiempo_actual");
    $result = $stmt->fetch();
    echo "Tiempo actual: " . $result['tiempo_actual'] . "<br>";

    // Verificar si las tablas existen
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tablas en la base de datos: " . implode(', ', $tables) . "<br>";

} catch (PDOException $e) {
    echo "❌ Error PDO: " . $e->getMessage() . "<br>";
    echo "Código de error: " . $e->getCode() . "<br>";
}

// 3. Test de conectividad de red
echo "<h3>3. Test de Red</h3>";
$connection = @fsockopen('127.0.0.1', 3306, $errno, $errstr, 5);
if ($connection) {
    echo "✅ Puerto 3306 accesible en 127.0.0.1<br>";
    fclose($connection);
} else {
    echo "❌ Puerto 3306 NO accesible: $errstr ($errno)<br>";
}

// 4. Información del sistema
echo "<h3>4. Información del Sistema</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Sistema: " . php_uname() . "<br>";
echo "Usuario web: " . get_current_user() . "<br>";

// 5. Probar con la clase Database
echo "<h3>5. Prueba con Clase Database</h3>";
try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    echo "✅ Clase Database funciona correctamente<br>";

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM cliente");
    $result = $stmt->fetch();
    echo "Total de clientes: " . $result['total'] . "<br>";

} catch (Exception $e) {
    echo "❌ Error con clase Database: " . $e->getMessage() . "<br>";
}
?>