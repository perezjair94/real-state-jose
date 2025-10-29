<?php
/**
 * Database Diagnostic Tool
 * Helps identify database connection issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<html><head><title>Database Diagnostics</title>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; }</style></head><body>";

echo "<h1>🔍 Diagnóstico de Base de Datos</h1>";

// 1. Check PHP Extensions
echo "<h2>1. Extensiones PHP Requeridas</h2>";
$extensions = ['pdo', 'pdo_mysql'];
foreach ($extensions as $ext) {
    $status = extension_loaded($ext) ? '✅' : '❌';
    echo "<p>$status " . strtoupper($ext) . "</p>";
}

// 2. Check environment variables
echo "<h2>2. Variables de Entorno</h2>";
$env_vars = [
    'MYSQL_HOST' => getenv('MYSQL_HOST'),
    'MYSQL_DATABASE' => getenv('MYSQL_DATABASE'),
    'MYSQL_USER' => getenv('MYSQL_USER'),
    'MYSQL_PASSWORD' => getenv('MYSQL_PASSWORD') ? '***' : 'vacío',
];

foreach ($env_vars as $key => $value) {
    echo "<p><strong>$key:</strong> " . ($value ?: 'No definido') . "</p>";
}

// 3. Check actual configuration that will be used
echo "<h2>3. Configuración que se Utilizará</h2>";
$host = getenv('MYSQL_HOST') ?: '127.0.0.1';
$db_name = getenv('MYSQL_DATABASE') ?: 'real_estate_db';
$username = getenv('MYSQL_USER') ?: 'root';
$password = getenv('MYSQL_PASSWORD') ?: '';

echo "<p><strong>Host:</strong> $host</p>";
echo "<p><strong>Base de datos:</strong> $db_name</p>";
echo "<p><strong>Usuario:</strong> $username</p>";
echo "<p><strong>Contraseña:</strong> " . ($password ? '***' : 'vacía') . "</p>";

// 4. Try basic connection
echo "<h2>4. Prueba de Conexión Básica</h2>";
try {
    $dsn = "mysql:host=$host;dbname=$db_name;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5
    ]);
    echo "<p>✅ <strong>Conexión exitosa!</strong></p>";

    // Show database info
    $stmt = $pdo->query("SELECT DATABASE() as db_name, VERSION() as mysql_version");
    $info = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>Base de datos:</strong> " . $info['db_name'] . "</p>";
    echo "<p><strong>MySQL Version:</strong> " . $info['mysql_version'] . "</p>";

    // List tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p><strong>Tablas encontradas:</strong> " . count($tables) . "</p>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";

} catch (PDOException $e) {
    echo "<p>❌ <strong>Error de conexión:</strong></p>";
    echo "<p><code>" . htmlspecialchars($e->getMessage()) . "</code></p>";
    echo "<p><strong>Código de error:</strong> " . $e->getCode() . "</p>";

    echo "<h3>Posibles soluciones:</h3>";
    echo "<ul>";
    echo "<li>Verifica que MySQL está corriendo</li>";
    echo "<li>Verifica el host (localhost, 127.0.0.1, o el nombre del contenedor de Docker)</li>";
    echo "<li>Verifica el nombre de la base de datos</li>";
    echo "<li>Verifica las credenciales (usuario y contraseña)</li>";
    echo "<li>Verifica los permisos del usuario MySQL</li>";
    echo "</ul>";
}

// 5. Network test
echo "<h2>5. Prueba de Conectividad de Red</h2>";
$timeout = 5;
$connection = @fsockopen($host, 3306, $errno, $errstr, $timeout);
if ($connection) {
    echo "<p>✅ Puerto 3306 es accesible en $host</p>";
    fclose($connection);
} else {
    echo "<p>❌ No se puede acceder al puerto 3306 en $host</p>";
    echo "<p><strong>Error:</strong> $errstr ($errno)</p>";
}

// 6. Configuration files
echo "<h2>6. Archivos de Configuración</h2>";
if (file_exists('config/constants.php')) {
    echo "<p>✅ config/constants.php existe</p>";
} else {
    echo "<p>❌ config/constants.php NO existe</p>";
}

if (file_exists('config/database.php')) {
    echo "<p>✅ config/database.php existe</p>";
} else {
    echo "<p>❌ config/database.php NO existe</p>";
}

echo "</body></html>";
?>
