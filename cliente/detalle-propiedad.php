<?php
/**
 * Detalle de Propiedad - Cliente
 * Vista de solo lectura para que clientes vean detalles de propiedades
 */

define('APP_ACCESS', true);

require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

initSession();
requireRole('cliente');

$user = getCurrentUser();

// Get property ID from URL
$propertyId = (int)($_GET['id'] ?? 0);

if ($propertyId <= 0) {
    header('Location: propiedades.php');
    exit;
}

$property = null;

try {
    $db = new Database();
    $pdo = $db->getConnection();

    // Get property details (only available properties for clients)
    $sql = "SELECT * FROM inmueble WHERE id_inmueble = ? AND estado = 'Disponible'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$propertyId]);
    $property = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$property) {
        header('Location: propiedades.php');
        exit;
    }

} catch (PDOException $e) {
    error_log("Error fetching property details: " . $e->getMessage());
    header('Location: propiedades.php');
    exit;
}

// Parse photos from JSON
$fotos = null;
if (!empty($property['fotos']) && $property['fotos'] !== 'null') {
    $fotos = json_decode($property['fotos'], true);
}

// Determine images to display
$imagesToShow = [];
if (is_array($fotos) && !empty($fotos)) {
    foreach ($fotos as $foto) {
        if (!empty($foto)) {
            $imagesToShow[] = '../assets/uploads/properties/' . htmlspecialchars($foto);
        }
    }
}

// If no custom photos, use default images
if (empty($imagesToShow)) {
    $imagesToShow = ['../img/casa1.jpeg', '../img/casa2.jpg', '../img/casa3.jpeg'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Propiedad - <?= APP_NAME ?></title>
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
            transition: background 0.3s;
        }

        .btn-back:hover {
            background: #00aa41;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px;
        }

        .property-detail {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .image-gallery {
            position: relative;
            width: 100%;
            height: 500px;
            overflow: hidden;
            background: #e0e0e0;
        }

        .image-gallery img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .gallery-controls {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
        }

        .gallery-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255,255,255,0.5);
            cursor: pointer;
            transition: background 0.3s;
        }

        .gallery-dot.active {
            background: white;
        }

        .gallery-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0,0,0,0.5);
            color: white;
            border: none;
            padding: 15px 20px;
            font-size: 24px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .gallery-nav:hover {
            background: rgba(0,0,0,0.7);
        }

        .gallery-nav.prev {
            left: 10px;
        }

        .gallery-nav.next {
            right: 10px;
        }

        .property-info {
            padding: 40px;
        }

        .property-header {
            margin-bottom: 30px;
        }

        .property-type-badge {
            display: inline-block;
            background: #00de55;
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .property-title {
            font-size: 36px;
            color: #0a1931;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .property-location {
            font-size: 18px;
            color: #666;
            margin-bottom: 20px;
        }

        .property-price {
            font-size: 42px;
            color: #00de55;
            font-weight: 700;
            margin-bottom: 30px;
        }

        .property-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
            padding: 30px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .feature-icon {
            font-size: 28px;
        }

        .feature-content {
            display: flex;
            flex-direction: column;
        }

        .feature-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            font-weight: 600;
        }

        .feature-value {
            font-size: 20px;
            color: #0a1931;
            font-weight: 700;
        }

        .property-description {
            margin-bottom: 40px;
        }

        .property-description h3 {
            font-size: 24px;
            color: #0a1931;
            margin-bottom: 15px;
        }

        .property-description p {
            font-size: 16px;
            line-height: 1.8;
            color: #555;
        }

        .property-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .detail-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #00de55;
        }

        .detail-box h4 {
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .detail-box p {
            font-size: 18px;
            color: #0a1931;
            font-weight: 600;
        }

        .contact-section {
            background: linear-gradient(135deg, #0a1931 0%, #1e3a5f 100%);
            color: white;
            padding: 40px;
            border-radius: 12px;
            text-align: center;
        }

        .contact-section h3 {
            font-size: 28px;
            margin-bottom: 15px;
        }

        .contact-section p {
            font-size: 16px;
            margin-bottom: 25px;
            opacity: 0.9;
        }

        .btn-contact {
            display: inline-block;
            background: #00de55;
            color: white;
            padding: 15px 40px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 18px;
            transition: background 0.3s;
        }

        .btn-contact:hover {
            background: #00aa41;
        }

        @media (max-width: 768px) {
            .image-gallery {
                height: 300px;
            }

            .property-info {
                padding: 20px;
            }

            .property-title {
                font-size: 28px;
            }

            .property-price {
                font-size: 32px;
            }

            .property-features {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>🏠 Detalle de Propiedad</h1>
            <a href="propiedades.php" class="btn-back">← Volver a Propiedades</a>
        </div>
    </div>

    <div class="container">
        <div class="property-detail">
            <!-- Image Gallery -->
            <div class="image-gallery" id="imageGallery">
                <img id="galleryImage" src="<?= $imagesToShow[0] ?>" alt="Imagen de propiedad" onerror="this.src='../img/casa1.jpeg'">

                <?php if (count($imagesToShow) > 1): ?>
                    <button class="gallery-nav prev" onclick="changeImage(-1)">‹</button>
                    <button class="gallery-nav next" onclick="changeImage(1)">›</button>

                    <div class="gallery-controls">
                        <?php foreach ($imagesToShow as $index => $img): ?>
                            <div class="gallery-dot <?= $index === 0 ? 'active' : '' ?>" onclick="showImage(<?= $index ?>)"></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Property Information -->
            <div class="property-info">
                <div class="property-header">
                    <span class="property-type-badge"><?= htmlspecialchars($property['tipo_inmueble']) ?></span>
                    <h1 class="property-title"><?= htmlspecialchars($property['tipo_inmueble']) ?> en <?= htmlspecialchars($property['ciudad']) ?></h1>
                    <p class="property-location">📍 <?= htmlspecialchars($property['direccion']) ?></p>
                    <div class="property-price"><?= formatCurrency($property['precio']) ?></div>
                </div>

                <!-- Main Features -->
                <div class="property-features">
                    <?php if (!empty($property['habitaciones'])): ?>
                        <div class="feature-item">
                            <div class="feature-icon">🛏️</div>
                            <div class="feature-content">
                                <span class="feature-label">Habitaciones</span>
                                <span class="feature-value"><?= $property['habitaciones'] ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($property['banos'])): ?>
                        <div class="feature-item">
                            <div class="feature-icon">🚿</div>
                            <div class="feature-content">
                                <span class="feature-label">Baños</span>
                                <span class="feature-value"><?= $property['banos'] ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($property['area_construida'])): ?>
                        <div class="feature-item">
                            <div class="feature-icon">📐</div>
                            <div class="feature-content">
                                <span class="feature-label">Área Construida</span>
                                <span class="feature-value"><?= number_format($property['area_construida'], 0) ?> m²</span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($property['area_lote'])): ?>
                        <div class="feature-item">
                            <div class="feature-icon">🏞️</div>
                            <div class="feature-content">
                                <span class="feature-label">Área del Lote</span>
                                <span class="feature-value"><?= number_format($property['area_lote'], 0) ?> m²</span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($property['garaje'])): ?>
                        <div class="feature-item">
                            <div class="feature-icon">🚗</div>
                            <div class="feature-content">
                                <span class="feature-label">Garaje</span>
                                <span class="feature-value">Sí</span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($property['piscina'])): ?>
                        <div class="feature-item">
                            <div class="feature-icon">🏊</div>
                            <div class="feature-content">
                                <span class="feature-label">Piscina</span>
                                <span class="feature-value">Sí</span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Description -->
                <?php if (!empty($property['descripcion'])): ?>
                    <div class="property-description">
                        <h3>Descripción</h3>
                        <p><?= nl2br(htmlspecialchars($property['descripcion'])) ?></p>
                    </div>
                <?php endif; ?>

                <!-- Additional Details -->
                <div class="property-details-grid">
                    <div class="detail-box">
                        <h4>Estado</h4>
                        <p><?= htmlspecialchars($property['estado']) ?></p>
                    </div>

                    <div class="detail-box">
                        <h4>Ciudad</h4>
                        <p><?= htmlspecialchars($property['ciudad']) ?></p>
                    </div>

                    <div class="detail-box">
                        <h4>Tipo de Inmueble</h4>
                        <p><?= htmlspecialchars($property['tipo_inmueble']) ?></p>
                    </div>

                    <?php if (!empty($property['ano_construccion'])): ?>
                        <div class="detail-box">
                            <h4>Año de Construcción</h4>
                            <p><?= htmlspecialchars($property['ano_construccion']) ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Contact Section -->
                <div class="contact-section">
                    <h3>¿Interesado en esta propiedad?</h3>
                    <p>Contáctanos para más información o para agendar una visita</p>
                    <a href="dashboard.php" class="btn-contact">Volver al Dashboard</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Image Gallery
        const images = <?= json_encode($imagesToShow) ?>;
        let currentImageIndex = 0;

        function showImage(index) {
            currentImageIndex = index;
            const galleryImage = document.getElementById('galleryImage');
            galleryImage.src = images[index];

            // Update dots
            const dots = document.querySelectorAll('.gallery-dot');
            dots.forEach((dot, i) => {
                dot.classList.toggle('active', i === index);
            });
        }

        function changeImage(direction) {
            currentImageIndex += direction;

            // Loop around
            if (currentImageIndex < 0) {
                currentImageIndex = images.length - 1;
            } else if (currentImageIndex >= images.length) {
                currentImageIndex = 0;
            }

            showImage(currentImageIndex);
        }

        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowLeft') {
                changeImage(-1);
            } else if (e.key === 'ArrowRight') {
                changeImage(1);
            }
        });
    </script>
</body>
</html>
