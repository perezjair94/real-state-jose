<?php
/**
 * Test script to verify image paths are correct in Docker environment
 */

define('APP_ACCESS', true);

require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

initSession();
requireLogin();
requireRole('admin');

echo '<!DOCTYPE html>
<html>
<head>
    <title>Test Docker Images</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .test-section { margin-bottom: 30px; border: 1px solid #ddd; padding: 15px; border-radius: 4px; }
        .test-section h2 { margin-top: 0; color: #0a1931; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { background: #e8f4f8; padding: 10px; border-radius: 4px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #f0f0f0; font-weight: bold; }
        img { max-width: 200px; height: auto; border: 1px solid #ddd; margin: 10px 0; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        .url-test { background: #fff3cd; padding: 10px; border-radius: 4px; margin: 10px 0; word-break: break-all; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test de Imágenes en Docker</h1>

        <!-- Test 1: URL Configuration -->
        <div class="test-section">
            <h2>1. Configuración de URLs</h2>';

echo 'BASE_URL: <code>' . BASE_URL . '</code><br>';
echo 'UPLOADS_URL: <code>' . UPLOADS_URL . '</code><br>';
echo 'UPLOAD_PATH_PROPERTIES: <code>' . UPLOAD_PATH_PROPERTIES . '</code><br>';

// Determine if running in Docker
$inDocker = file_exists('/.dockerenv');
echo '<div class="info">' . ($inDocker ? '<span class="success">✓ Ejecutándose en Docker</span>' : '<span class="error">✗ No en Docker</span>') . '</div>';

echo '        </div>';

        // Test 2: File System Check
        echo '        <div class="test-section">
            <h2>2. Verificación del Sistema de Archivos</h2>';

$uploadDir = UPLOAD_PATH_PROPERTIES;
$dirExists = is_dir($uploadDir);
$dirWritable = is_writable($uploadDir);

echo '<table>';
echo '<tr><th>Propiedad</th><th>Estado</th></tr>';
echo '<tr><td>Directorio existe</td><td>' . ($dirExists ? '<span class="success">✓ Sí</span>' : '<span class="error">✗ No</span>') . '</td></tr>';
echo '<tr><td>Directorio escribible</td><td>' . ($dirWritable ? '<span class="success">✓ Sí</span>' : '<span class="error">✗ No</span>') . '</td></tr>';
echo '</table>';

        // Test 3: Database Images
        echo '        <div class="test-section">
            <h2>3. Imágenes en la Base de Datos</h2>';

try {
    $db = new Database();
    $pdo = $db->getConnection();

    $stmt = $pdo->query("SELECT id_inmueble, fotos FROM inmueble WHERE fotos IS NOT NULL AND fotos != '[]' AND fotos != 'null' LIMIT 1");
    $property = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($property) {
        echo '<p>Propiedad encontrada: <strong>ID ' . $property['id_inmueble'] . '</strong></p>';

        $fotos = json_decode($property['fotos'], true);

        echo '<table>';
        echo '<tr><th>#</th><th>Nombre de Archivo</th><th>URL Construida</th><th>¿Existe?</th><th>Vista Previa</th></tr>';

        foreach ($fotos as $index => $foto) {
            $imageUrl = UPLOADS_URL . 'properties/' . $foto;
            $imagePath = UPLOAD_PATH_PROPERTIES . $foto;
            $fileExists = file_exists($imagePath);

            echo '<tr>';
            echo '<td>' . ($index + 1) . '</td>';
            echo '<td><code>' . htmlspecialchars($foto) . '</code></td>';
            echo '<td><code style="font-size: 11px;">' . htmlspecialchars($imageUrl) . '</code></td>';
            echo '<td>' . ($fileExists ? '<span class="success">✓ Existe</span>' : '<span class="error">✗ No existe</span>') . '</td>';
            echo '<td>';

            if ($fileExists) {
                echo '<img src="' . htmlspecialchars($imageUrl) . '" alt="Image" onerror="this.alt=\'Error cargando imagen\'; this.style.border=\'2px solid red\';">';
            } else {
                echo '<span class="error">Archivo no encontrado</span>';
            }

            echo '</td>';
            echo '</tr>';
        }

        echo '</table>';
    } else {
        echo '<p style="color: orange;">No hay propiedades con imágenes en la base de datos.</p>';
    }
} catch (Exception $e) {
    echo '<p style="color: red;">Error en base de datos: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

        echo '        </div>';

        // Test 4: Server Information
        echo '        <div class="test-section">
            <h2>4. Información del Servidor</h2>';

echo '<table>';
echo '<tr><th>Parámetro</th><th>Valor</th></tr>';
echo '<tr><td>HTTP_HOST</td><td><code>' . htmlspecialchars($_SERVER['HTTP_HOST']) . '</code></td></tr>';
echo '<tr><td>SCRIPT_NAME</td><td><code>' . htmlspecialchars($_SERVER['SCRIPT_NAME']) . '</code></td></tr>';
echo '<tr><td>PHP_VERSION</td><td><code>' . phpversion() . '</code></td></tr>';
echo '<tr><td>Server API</td><td><code>' . php_sapi_name() . '</code></td></tr>';
echo '</table>';

        echo '        </div>';

        // Test 5: Quick Test Link
        echo '        <div class="test-section">
            <h2>5. Acciones</h2>';

echo '<p><a href="' . BASE_URL . 'index.php?module=properties" style="display: inline-block; background: #00de55; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">← Volver a Propiedades</a></p>';

        echo '        </div>
    </div>
</body>
</html>';
?>
