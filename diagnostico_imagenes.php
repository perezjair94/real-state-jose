<?php
/**
 * Diagnostic script for image loading issues
 */

define('APP_ACCESS', true);

require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

initSession();
requireLogin();
requireRole('admin');

echo '<h1>Diagnóstico de Carga de Imágenes</h1>';
echo '<hr>';

// 1. Check BASE_URL
echo '<h2>1. Configuración de URLs</h2>';
echo '<pre>';
echo 'BASE_URL: ' . BASE_URL . PHP_EOL;
echo 'UPLOADS_URL: ' . UPLOADS_URL . PHP_EOL;
echo 'UPLOAD_PATH_PROPERTIES: ' . UPLOAD_PATH_PROPERTIES . PHP_EOL;
echo '</pre>';

// 2. Check directory existence and permissions
echo '<h2>2. Verificación de Directorios</h2>';
echo '<table border="1" cellpadding="10">';
echo '<tr><th>Directorio</th><th>Existe</th><th>Legible</th><th>Escribible</th></tr>';

$dirs = [
    UPLOAD_PATH_PROPERTIES,
    'assets/',
    'assets/uploads/',
];

foreach ($dirs as $dir) {
    $exists = is_dir($dir) ? 'Sí' : 'No';
    $readable = is_readable($dir) ? 'Sí' : 'No';
    $writable = is_writable($dir) ? 'Sí' : 'No';
    echo "<tr><td>$dir</td><td>$exists</td><td>$readable</td><td>$writable</td></tr>";
}
echo '</table>';

// 3. List uploaded files
echo '<h2>3. Archivos Subidos</h2>';
if (is_dir(UPLOAD_PATH_PROPERTIES)) {
    $files = array_diff(scandir(UPLOAD_PATH_PROPERTIES), ['.', '..']);
    if (!empty($files)) {
        echo '<ul>';
        foreach ($files as $file) {
            $filepath = UPLOAD_PATH_PROPERTIES . $file;
            $filesize = filesize($filepath);
            echo "<li>$file (" . formatBytes($filesize) . ")</li>";
        }
        echo '</ul>';
    } else {
        echo '<p>No hay archivos en el directorio de uploads</p>';
    }
}

// 4. Check database records
echo '<h2>4. Registros en Base de Datos</h2>';
try {
    $db = new Database();
    $pdo = $db->getConnection();

    $stmt = $pdo->query("SELECT id_inmueble, fotos FROM inmueble WHERE fotos IS NOT NULL AND fotos != '[]' AND fotos != 'null' LIMIT 5");
    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($properties)) {
        echo '<table border="1" cellpadding="10">';
        echo '<tr><th>ID Propiedad</th><th>Fotos (JSON)</th><th>URLs Construidas</th></tr>';

        foreach ($properties as $prop) {
            $fotos = json_decode($prop['fotos'], true);
            $urls = [];

            if (is_array($fotos)) {
                foreach ($fotos as $foto) {
                    if (!empty($foto)) {
                        $url = BASE_URL . 'assets/uploads/properties/' . htmlspecialchars($foto);
                        $urls[] = $url;
                    }
                }
            }

            echo '<tr>';
            echo '<td>' . $prop['id_inmueble'] . '</td>';
            echo '<td><small><pre>' . htmlspecialchars($prop['fotos']) . '</pre></small></td>';
            echo '<td>';
            foreach ($urls as $url) {
                echo '<div><code style="font-size: 11px; word-break: break-all;">' . $url . '</code></div>';
            }
            echo '</td>';
            echo '</tr>';
        }

        echo '</table>';
    } else {
        echo '<p>No hay propiedades con fotos en la base de datos</p>';
    }
} catch (PDOException $e) {
    echo '<p style="color: red;">Error en base de datos: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

// 5. Test actual file access
echo '<h2>5. Prueba de Acceso a Archivos</h2>';
if (is_dir(UPLOAD_PATH_PROPERTIES)) {
    $files = array_diff(scandir(UPLOAD_PATH_PROPERTIES), ['.', '..']);
    $testFile = reset($files);

    if ($testFile) {
        $filepath = UPLOAD_PATH_PROPERTIES . $testFile;
        $url = BASE_URL . 'assets/uploads/properties/' . $testFile;

        echo '<p>Archivo de prueba: <strong>' . $testFile . '</strong></p>';
        echo '<p>Ruta completa del servidor: <strong>' . realpath($filepath) . '</strong></p>';
        echo '<p>URL para navegador: <strong>' . $url . '</strong></p>';

        echo '<p style="margin-top: 20px;"><strong>Vista previa:</strong></p>';
        echo '<img src="' . $url . '" style="max-width: 300px; border: 1px solid #ccc;" alt="Test image" onerror="alert(\'No se puede cargar la imagen\');">';
    }
}

echo '<hr>';
echo '<a href="' . BASE_URL . 'index.php?module=properties" class="btn btn-primary">Volver a Propiedades</a>';
?>
