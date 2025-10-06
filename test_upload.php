<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Prueba de Subida de Fotos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        .result {
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
    </style>
</head>
<body>
    <h1>Prueba de Sistema de Subida de Fotos</h1>

    <?php
    define('APP_ACCESS', true);
    require_once 'config/constants.php';

    echo "<div class='result info'>";
    echo "<h3>Configuración del Sistema:</h3>";
    echo "<ul>";
    echo "<li><strong>Directorio de uploads:</strong> " . UPLOAD_PATH_PROPERTIES . "</li>";
    echo "<li><strong>Existe el directorio:</strong> " . (is_dir(UPLOAD_PATH_PROPERTIES) ? '✓ Sí' : '✗ No') . "</li>";
    echo "<li><strong>Es escribible:</strong> " . (is_writable(UPLOAD_PATH_PROPERTIES) ? '✓ Sí' : '✗ No') . "</li>";
    echo "<li><strong>Permisos:</strong> " . substr(sprintf('%o', fileperms(UPLOAD_PATH_PROPERTIES)), -4) . "</li>";
    echo "<li><strong>Tipos de archivo permitidos:</strong> " . implode(', ', ALLOWED_IMAGE_TYPES) . "</li>";
    echo "<li><strong>Tamaño máximo:</strong> " . number_format(UPLOAD_MAX_SIZE / 1024 / 1024, 2) . " MB</li>";
    echo "</ul>";
    echo "</div>";

    // Test database connection and check photos
    try {
        require_once 'config/database.php';
        $db = new Database();
        $pdo = $db->getConnection();

        $stmt = $pdo->query("SELECT id_inmueble, direccion, fotos FROM inmueble LIMIT 5");
        $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<div class='result success'>";
        echo "<h3>✓ Conexión a Base de Datos Exitosa</h3>";
        echo "<p>Últimas 5 propiedades y sus fotos:</p>";
        echo "<table border='1' cellpadding='5' style='width:100%; border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Dirección</th><th>Fotos</th></tr>";

        foreach ($properties as $prop) {
            $fotos = json_decode($prop['fotos'] ?? '[]', true);
            $fotosInfo = empty($fotos) ? '<em>Sin fotos</em>' : implode(', ', $fotos);

            echo "<tr>";
            echo "<td>" . $prop['id_inmueble'] . "</td>";
            echo "<td>" . htmlspecialchars($prop['direccion']) . "</td>";
            echo "<td>" . $fotosInfo . "</td>";
            echo "</tr>";
        }

        echo "</table>";
        echo "</div>";

    } catch (Exception $e) {
        echo "<div class='result error'>";
        echo "<h3>✗ Error de Base de Datos</h3>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
    }

    // Check for uploaded files
    $uploadedFiles = glob(UPLOAD_PATH_PROPERTIES . '*');
    echo "<div class='result info'>";
    echo "<h3>Archivos en el Directorio de Uploads:</h3>";
    if (empty($uploadedFiles)) {
        echo "<p><em>No hay archivos subidos aún.</em></p>";
    } else {
        echo "<ul>";
        foreach ($uploadedFiles as $file) {
            $filename = basename($file);
            $size = filesize($file);
            echo "<li>" . htmlspecialchars($filename) . " (" . number_format($size / 1024, 2) . " KB)</li>";
        }
        echo "</ul>";
    }
    echo "</div>";

    // PHP upload settings
    echo "<div class='result info'>";
    echo "<h3>Configuración PHP de Uploads:</h3>";
    echo "<ul>";
    echo "<li><strong>upload_max_filesize:</strong> " . ini_get('upload_max_filesize') . "</li>";
    echo "<li><strong>post_max_size:</strong> " . ini_get('post_max_size') . "</li>";
    echo "<li><strong>max_file_uploads:</strong> " . ini_get('max_file_uploads') . "</li>";
    echo "<li><strong>file_uploads:</strong> " . (ini_get('file_uploads') ? 'Habilitado' : 'Deshabilitado') . "</li>";
    echo "</ul>";
    echo "</div>";
    ?>

    <div class="result info">
        <h3>Instrucciones para Probar:</h3>
        <ol>
            <li>Ve a <a href="index.php?module=properties&action=create">Crear Nueva Propiedad</a></li>
            <li>Llena el formulario y selecciona al menos una foto</li>
            <li>Envía el formulario</li>
            <li>Regresa a esta página para verificar si la foto se subió</li>
            <li>Revisa <a href="index.php?module=properties">Lista de Propiedades</a> para ver si aparece la foto</li>
        </ol>
    </div>

</body>
</html>
