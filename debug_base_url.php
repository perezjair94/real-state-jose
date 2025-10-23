<?php
/**
 * Debug BASE_URL - Temporary file to verify URL configuration
 */

define('APP_ACCESS', true);
require_once 'config/constants.php';
require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug BASE_URL</title>
    <style>
        body { font-family: monospace; padding: 20px; }
        .info { background: #f0f0f0; padding: 10px; margin: 10px 0; border-left: 4px solid #00de55; }
        .test-image { max-width: 200px; border: 2px solid #ccc; margin: 10px 0; }
        h2 { color: #0a1931; }
    </style>
</head>
<body>
    <h1>Debug de Rutas de Imágenes</h1>

    <h2>Configuración BASE_URL</h2>
    <div class="info">
        <strong>BASE_URL:</strong> <?= BASE_URL ?><br>
        <strong>ASSETS_URL:</strong> <?= ASSETS_URL ?><br>
        <strong>UPLOADS_URL:</strong> <?= UPLOADS_URL ?><br>
        <strong>SCRIPT_NAME:</strong> <?= $_SERVER['SCRIPT_NAME'] ?><br>
        <strong>HTTP_HOST:</strong> <?= $_SERVER['HTTP_HOST'] ?><br>
    </div>

    <h2>Rutas de Imágenes</h2>
    <div class="info">
        <strong>Ruta default img:</strong> <?= BASE_URL ?>img/casa1.jpeg<br>
        <strong>Ruta uploads:</strong> <?= BASE_URL ?>assets/uploads/properties/casa2_68fa9a194259e.jpg<br>
    </div>

    <h2>Test de Carga de Imágenes</h2>

    <h3>Imagen Default (img/)</h3>
    <img src="<?= BASE_URL ?>img/casa1.jpeg" alt="Casa 1" class="test-image" onerror="this.style.border='2px solid red'; this.alt='ERROR: No se pudo cargar'">
    <p>Ruta: <code><?= BASE_URL ?>img/casa1.jpeg</code></p>

    <h3>Imagen Uploaded (assets/uploads/properties/)</h3>
    <img src="<?= BASE_URL ?>assets/uploads/properties/casa2_68fa9a194259e.jpg" alt="Casa 2" class="test-image" onerror="this.style.border='2px solid red'; this.alt='ERROR: No se pudo cargar'">
    <p>Ruta: <code><?= BASE_URL ?>assets/uploads/properties/casa2_68fa9a194259e.jpg</code></p>

    <h2>Propiedades en Base de Datos</h2>
    <?php
    try {
        $db = new Database();
        $pdo = $db->getConnection();
        $stmt = $pdo->query("SELECT id_inmueble, tipo_inmueble, direccion, fotos FROM inmueble LIMIT 5");
        $properties = $stmt->fetchAll();

        foreach ($properties as $prop) {
            echo "<div class='info'>";
            echo "<strong>ID {$prop['id_inmueble']}:</strong> {$prop['tipo_inmueble']} - {$prop['direccion']}<br>";
            echo "<strong>Fotos:</strong> " . ($prop['fotos'] ?: 'NULL') . "<br>";

            if (!empty($prop['fotos']) && $prop['fotos'] !== 'null') {
                $fotos = json_decode($prop['fotos'], true);
                if (is_array($fotos) && !empty($fotos)) {
                    echo "<strong>Fotos decodificadas:</strong><br>";
                    foreach ($fotos as $foto) {
                        $ruta = BASE_URL . 'assets/uploads/properties/' . htmlspecialchars($foto);
                        echo "- <code>{$ruta}</code><br>";
                        echo "<img src='{$ruta}' alt='Foto' class='test-image' onerror=\"this.style.border='2px solid red'; this.alt='ERROR';\"><br>";
                    }
                }
            }
            echo "</div>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    }
    ?>

    <p><a href="index.php">← Volver al inicio</a></p>
</body>
</html>
