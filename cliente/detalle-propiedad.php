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

        /* Interest Buttons */
        .btn-purchase,
        .btn-rent {
            padding: 18px 40px;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Oswald', sans-serif;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .btn-purchase {
            background: linear-gradient(135deg, #00aa41, #00de55);
            color: white;
        }

        .btn-purchase:hover {
            background: linear-gradient(135deg, #008f36, #00c04a);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 222, 85, 0.4);
        }

        .btn-rent {
            background: linear-gradient(135deg, #0a1931, #1e3a5f);
            color: white;
        }

        .btn-rent:hover {
            background: linear-gradient(135deg, #0d1f3c, #2b5190);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(10, 25, 49, 0.4);
        }

        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 50px rgba(0,0,0,0.3);
        }

        .modal-header {
            background: linear-gradient(135deg, #0a1931, #1e3a5f);
            color: white;
            padding: 25px 30px;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 24px;
        }

        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 28px;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-body {
            padding: 30px;
        }

        .property-summary {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-left: 4px solid #00de55;
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 8px;
        }

        .property-summary h4 {
            margin: 0 0 10px 0;
            color: #0a1931;
        }

        .property-summary p {
            margin: 5px 0;
            color: #555;
        }

        .interest-badge {
            display: inline-block;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-top: 5px;
        }

        .interest-badge.compra {
            background: linear-gradient(135deg, #00aa41, #00de55);
            color: white;
        }

        .interest-badge.arriendo {
            background: linear-gradient(135deg, #0a1931, #1e3a5f);
            color: white;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            margin-bottom: 5px;
            font-weight: 600;
            color: #0a1931;
        }

        .form-group label.required::after {
            content: ' *';
            color: #e94545;
        }

        .form-control {
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            font-family: 'Oswald', sans-serif;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #00de55;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }

        .modal-footer {
            padding: 20px 30px;
            background: #f8f9fa;
            border-radius: 0 0 12px 12px;
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }

        .btn-modal {
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Oswald', sans-serif;
            transition: all 0.3s;
        }

        .btn-modal-primary {
            background: #00de55;
            color: white;
        }

        .btn-modal-primary:hover {
            background: #00aa41;
        }

        .btn-modal-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-modal-secondary:hover {
            background: #5a6268;
        }

        .info-message {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin-top: 20px;
            border-radius: 6px;
            font-size: 14px;
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

                <!-- Interest Buttons -->
                <div class="interest-buttons" style="display: flex; gap: 20px; justify-content: center; margin-bottom: 30px;">
                    <button type="button" class="btn-purchase" onclick="showInterestForm('compra', <?= $property['id_inmueble'] ?>)">
                        🏠 Comprar esta Propiedad
                    </button>
                    <button type="button" class="btn-rent" onclick="showInterestForm('arriendo', <?= $property['id_inmueble'] ?>)">
                        🔑 Arrendar esta Propiedad
                    </button>
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

    <!-- Modal for Interest Form -->
    <div class="modal-overlay" id="interestModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Solicitud de Interés</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Content will be inserted by JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal btn-modal-primary" onclick="submitInterestForm()">
                    Enviar Solicitud
                </button>
                <button type="button" class="btn-modal btn-modal-secondary" onclick="closeModal()">
                    Cancelar
                </button>
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

        // Interest Form Modal Functions
        const currentUser = {
            nombre_completo: '<?= $user['nombre_completo'] ?? '' ?>',
            email: '<?= $user['email'] ?? '' ?>'
        };

        const property = {
            id: <?= $property['id_inmueble'] ?>,
            direccion: '<?= htmlspecialchars($property['direccion'], ENT_QUOTES) ?>',
            precio: '<?= formatCurrency($property['precio']) ?>'
        };

        function showInterestForm(tipoInteres, propertyId) {
            const tipoTexto = tipoInteres === 'compra' ? 'Comprar' : 'Arrendar';

            // Split nombre_completo into nombre and apellido
            const nombreParts = currentUser.nombre_completo.split(' ');
            const nombre = nombreParts[0] || '';
            const apellido = nombreParts.slice(1).join(' ') || '';

            const formHtml = `
                <div class="property-summary">
                    <h4>Propiedad de Interés</h4>
                    <p><strong>Dirección:</strong> ${property.direccion}</p>
                    <p><strong>Precio:</strong> ${property.precio}</p>
                    <p><strong>Tipo de Interés:</strong> <span class="interest-badge ${tipoInteres}">${tipoTexto}</span></p>
                </div>

                <form id="interest-form">
                    <input type="hidden" name="id_inmueble" value="${propertyId}">
                    <input type="hidden" name="tipo_interes" value="${tipoInteres}">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="nombre" class="required">Nombre</label>
                            <input type="text" id="nombre" name="nombre" class="form-control" required value="${nombre}">
                        </div>
                        <div class="form-group">
                            <label for="apellido" class="required">Apellido</label>
                            <input type="text" id="apellido" name="apellido" class="form-control" required value="${apellido}">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="correo" class="required">Correo Electrónico</label>
                            <input type="email" id="correo" name="correo" class="form-control" required value="${currentUser.email}">
                        </div>
                        <div class="form-group">
                            <label for="telefono" class="required">Teléfono</label>
                            <input type="tel" id="telefono" name="telefono" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="fecha_preferida">Fecha Preferida para Visita</label>
                            <input type="date" id="fecha_preferida" name="fecha_preferida" class="form-control" min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="form-group">
                            <label for="hora_preferida">Hora Preferida</label>
                            <input type="time" id="hora_preferida" name="hora_preferida" class="form-control">
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label for="mensaje">Mensaje Adicional</label>
                        <textarea id="mensaje" name="mensaje" class="form-control" rows="3" placeholder="Cuéntanos más sobre tu interés en esta propiedad..."></textarea>
                    </div>
                </form>
            `;

            document.getElementById('modalTitle').textContent = `Solicitud de ${tipoTexto}`;
            document.getElementById('modalBody').innerHTML = formHtml;
            document.getElementById('interestModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('interestModal').classList.remove('active');
        }

        async function submitInterestForm() {
            const form = document.getElementById('interest-form');
            const formData = new FormData(form);

            // Basic validation
            const nombre = formData.get('nombre');
            const apellido = formData.get('apellido');
            const correo = formData.get('correo');
            const telefono = formData.get('telefono');

            if (!nombre || !apellido || !correo || !telefono) {
                alert('Por favor complete todos los campos requeridos');
                return;
            }

            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(correo)) {
                alert('Por favor ingrese un correo electrónico válido');
                return;
            }

            try {
                // Convert FormData to JSON
                const data = {};
                formData.forEach((value, key) => {
                    data[key] = value;
                });

                const response = await fetch('../index.php?module=properties&action=ajax', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'submitInterest',
                        data: data
                    })
                });

                const result = await response.json();

                if (result.success) {
                    alert('¡Solicitud enviada exitosamente! Nos pondremos en contacto contigo pronto.');
                    closeModal();
                    // Optionally redirect to dashboard or show confirmation
                    setTimeout(() => {
                        window.location.href = 'mis-visitas.php';
                    }, 1000);
                } else {
                    throw new Error(result.error || 'Error al enviar la solicitud');
                }
            } catch (error) {
                console.error('Error submitting interest:', error);
                alert('Error al enviar la solicitud: ' + error.message);
            }
        }

        // Close modal when clicking outside
        document.getElementById('interestModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>
