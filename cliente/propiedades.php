<?php
/**
 * Explorar Propiedades - Cliente
 * Catálogo de propiedades disponibles para clientes
 */

define('APP_ACCESS', true);

require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

initSession();
requireRole('cliente');

$user = getCurrentUser();

// Filtros
$tipo = $_GET['tipo'] ?? '';
$ciudad = $_GET['ciudad'] ?? '';
$precio_max = $_GET['precio_max'] ?? '';

try {
    $db = new Database();
    $pdo = $db->getConnection();

    // Construir query con filtros
    $sql = "SELECT * FROM inmueble WHERE estado = 'Disponible'";
    $params = [];

    if ($tipo) {
        $sql .= " AND tipo_inmueble = ?";
        $params[] = $tipo;
    }

    if ($ciudad) {
        $sql .= " AND ciudad LIKE ?";
        $params[] = "%{$ciudad}%";
    }

    if ($precio_max) {
        $sql .= " AND precio <= ?";
        $params[] = $precio_max;
    }

    $sql .= " ORDER BY created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $propiedades = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener ciudades únicas para filtro
    $stmt = $pdo->query("SELECT DISTINCT ciudad FROM inmueble WHERE estado = 'Disponible' ORDER BY ciudad");
    $ciudades = $stmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    error_log("Error loading properties: " . $e->getMessage());
    $propiedades = [];
    $ciudades = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explorar Propiedades - <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Oswald', sans-serif;
            background: #f5f6fa;
        }

        .header {
            background: linear-gradient(135deg, #0a1931 0%, #1e3a5f 100%);
            color: white;
            padding: 20px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 28px;
        }

        .btn-back {
            background: #00de55;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px;
        }

        .filters {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .filters h2 {
            margin-bottom: 20px;
            color: #0a1931;
        }

        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            font-size: 14px;
            color: #0a1931;
        }

        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-family: 'Oswald', sans-serif;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #00de55;
        }

        .btn-filter {
            background: #00de55;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-family: 'Oswald', sans-serif;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-clear {
            background: #6c757d;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-family: 'Oswald', sans-serif;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-left: 10px;
        }

        .properties-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
        }

        .property-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .property-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }

        .property-image {
            width: 100%;
            height: 220px;
            background: #e0e0e0;
            overflow: hidden;
            position: relative;
        }

        .property-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .property-content {
            padding: 20px;
        }

        .property-type {
            background: #00de55;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            display: inline-block;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .property-title {
            font-size: 20px;
            font-weight: 700;
            color: #0a1931;
            margin-bottom: 5px;
        }

        .property-location {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .property-details {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 14px;
            color: #666;
        }

        .property-price {
            font-size: 24px;
            font-weight: 700;
            color: #00de55;
            margin-bottom: 15px;
        }

        .property-description {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .btn-view {
            display: block;
            text-align: center;
            background: #0a1931;
            color: white;
            padding: 12px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.3s;
        }

        .btn-view:hover {
            background: #1e3a5f;
        }

        .no-results {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
        }

        .no-results h3 {
            font-size: 24px;
            color: #0a1931;
            margin-bottom: 10px;
        }

        .results-count {
            margin-bottom: 20px;
            font-size: 16px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>🔍 Explorar Propiedades</h1>
            <a href="dashboard.php" class="btn-back">← Volver al Dashboard</a>
        </div>
    </div>

    <div class="container">
        <div class="filters">
            <h2>Filtrar Propiedades</h2>
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Tipo de Inmueble</label>
                        <select name="tipo">
                            <option value="">Todos los tipos</option>
                            <?php foreach (PROPERTY_TYPES as $key => $label): ?>
                                <option value="<?= $key ?>" <?= $tipo === $key ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Ciudad</label>
                        <select name="ciudad">
                            <option value="">Todas las ciudades</option>
                            <?php foreach ($ciudades as $c): ?>
                                <option value="<?= htmlspecialchars($c) ?>" <?= $ciudad === $c ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Precio Máximo</label>
                        <input type="number" name="precio_max" placeholder="Ej: 500000000"
                               value="<?= htmlspecialchars($precio_max) ?>">
                    </div>
                </div>

                <div>
                    <button type="submit" class="btn-filter">Aplicar Filtros</button>
                    <a href="propiedades.php" class="btn-clear" style="text-decoration: none; display: inline-block;">Limpiar Filtros</a>
                </div>
            </form>
        </div>

        <div class="results-count">
            <strong><?= count($propiedades) ?></strong> propiedades disponibles
        </div>

        <?php if (empty($propiedades)): ?>
            <div class="no-results">
                <h3>No se encontraron propiedades</h3>
                <p>Intenta ajustar los filtros de búsqueda</p>
            </div>
        <?php else: ?>
            <div class="properties-grid">
                <?php foreach ($propiedades as $prop): ?>
                    <div class="property-card">
                        <div class="property-image">
                            <?php
                            // Get property image from JSON or use default based on property rotation
                            $fotos = json_decode($prop['fotos'] ?? '[]', true);

                            if (!empty($fotos) && isset($fotos[0])) {
                                // Use uploaded photo
                                $imageSrc = '../assets/uploads/properties/' . htmlspecialchars($fotos[0]);
                            } else {
                                // Use default image based on property rotation
                                $defaultImages = ['../img/casa1.jpeg', '../img/casa2.jpg', '../img/casa3.jpeg'];
                                $imageIndex = $prop['id_inmueble'] % count($defaultImages);
                                $imageSrc = $defaultImages[$imageIndex];
                            }
                            ?>
                            <img src="<?= $imageSrc ?>"
                                 alt="<?= htmlspecialchars($prop['tipo_inmueble']) ?> en <?= htmlspecialchars($prop['ciudad']) ?>"
                                 onerror="this.src='../img/casa1.jpeg'">
                        </div>
                        <div class="property-content">
                            <span class="property-type"><?= htmlspecialchars($prop['tipo_inmueble']) ?></span>
                            <h3 class="property-title"><?= htmlspecialchars($prop['tipo_inmueble']) ?> en <?= htmlspecialchars($prop['ciudad']) ?></h3>
                            <p class="property-location">📍 <?= htmlspecialchars($prop['direccion']) ?></p>

                            <div class="property-details">
                                <?php if ($prop['habitaciones']): ?>
                                    <span>🛏️ <?= $prop['habitaciones'] ?> hab</span>
                                <?php endif; ?>
                                <?php if ($prop['banos']): ?>
                                    <span>🚿 <?= $prop['banos'] ?> baños</span>
                                <?php endif; ?>
                                <?php if ($prop['area_construida']): ?>
                                    <span>📐 <?= $prop['area_construida'] ?>m²</span>
                                <?php endif; ?>
                            </div>

                            <div class="property-price">
                                <?= formatCurrency($prop['precio']) ?>
                            </div>

                            <?php if ($prop['descripcion']): ?>
                                <p class="property-description"><?= htmlspecialchars($prop['descripcion']) ?></p>
                            <?php endif; ?>

                            <a href="../index.php?module=properties&action=view&id=<?= $prop['id_inmueble'] ?>" class="btn-view">
                                Ver Detalles
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
