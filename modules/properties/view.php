<?php
/**
 * View Property Details - Real Estate Management System
 * Display comprehensive property information
 * Educational PHP/MySQL Project
 */

// Prevent direct access
if (!defined('APP_ACCESS')) {
    die('Direct access not permitted');
}

// Get property ID from URL
$propertyId = (int)($_GET['id'] ?? 0);

if ($propertyId <= 0) {
    redirectWithMessage('?module=properties', 'ID de propiedad inválido', 'error');
}

$property = null;
$relatedData = [];

try {
    $db = new Database();
    $pdo = $db->getConnection();

    // Get property details
    $sql = "SELECT * FROM inmueble WHERE id_inmueble = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$propertyId]);
    $property = $stmt->fetch();

    if (!$property) {
        redirectWithMessage('?module=properties', 'Propiedad no encontrada', 'error');
    }

    // Get related sales
    $salesSql = "SELECT v.*, c.nombre, c.apellido FROM venta v
                 JOIN cliente c ON v.id_cliente = c.id_cliente
                 WHERE v.id_inmueble = ?
                 ORDER BY v.fecha_venta DESC";
    $salesStmt = $pdo->prepare($salesSql);
    $salesStmt->execute([$propertyId]);
    $relatedData['sales'] = $salesStmt->fetchAll();

    // Get related contracts
    $contractsSql = "SELECT co.*, c.nombre, c.apellido FROM contrato co
                     JOIN cliente c ON co.id_cliente = c.id_cliente
                     WHERE co.id_inmueble = ?
                     ORDER BY co.fecha_inicio DESC";
    $contractsStmt = $pdo->prepare($contractsSql);
    $contractsStmt->execute([$propertyId]);
    $relatedData['contracts'] = $contractsStmt->fetchAll();

    // Get related rentals
    $rentalsSql = "SELECT a.*, c.nombre, c.apellido FROM arriendo a
                   JOIN cliente c ON a.id_cliente = c.id_cliente
                   WHERE a.id_inmueble = ?
                   ORDER BY a.fecha_inicio DESC";
    $rentalsStmt = $pdo->prepare($rentalsSql);
    $rentalsStmt->execute([$propertyId]);
    $relatedData['rentals'] = $rentalsStmt->fetchAll();

    // Get related visits
    $visitsSql = "SELECT vi.*, c.nombre as cliente_nombre, c.apellido as cliente_apellido,
                         a.nombre as agente_nombre
                  FROM visita vi
                  JOIN cliente c ON vi.id_cliente = c.id_cliente
                  LEFT JOIN agente a ON vi.id_agente = a.id_agente
                  WHERE vi.id_inmueble = ?
                  ORDER BY vi.fecha_visita DESC, vi.hora_visita DESC";
    $visitsStmt = $pdo->prepare($visitsSql);
    $visitsStmt->execute([$propertyId]);
    $relatedData['visits'] = $visitsStmt->fetchAll();

} catch (PDOException $e) {
    error_log("Error fetching property details: " . $e->getMessage());
    redirectWithMessage('?module=properties', 'Error al cargar los detalles de la propiedad', 'error');
}

// Parse photos from JSON
$photos = [];
if (!empty($property['fotos'])) {
    $photos = json_decode($property['fotos'], true) ?: [];
}

$formattedId = generateFormattedId('INM', $property['id_inmueble']);
?>

<div class="module-header">
    <h2>Detalles de la Propiedad <span class="property-id"><?= $formattedId ?></span></h2>
    <p class="module-description">
        Información completa y historial de transacciones de la propiedad.
    </p>
</div>

<!-- Action Buttons -->
<div class="action-buttons">
    <a href="?module=properties" class="btn btn-secondary">
        ← Volver a la Lista
    </a>
    <a href="?module=properties&action=edit&id=<?= $property['id_inmueble'] ?>" class="btn btn-primary">
        ✏️ Editar Propiedad
    </a>
    <?php if ($property['estado'] === 'Disponible'): ?>
        <button type="button" class="btn btn-warning" onclick="changeStatus('<?= $property['id_inmueble'] ?>')">
            🔄 Cambiar Estado
        </button>
    <?php endif; ?>
    <button type="button" class="btn btn-info" onclick="printProperty()">
        🖨️ Imprimir Ficha
    </button>
    <button type="button" class="btn btn-success" onclick="shareProperty()">
        📤 Compartir
    </button>
</div>

<!-- Property Information Cards -->
<div class="card-grid">
    <!-- Basic Information -->
    <div class="card property-info">
        <h3>Información Básica</h3>

        <div class="info-grid">
            <div class="info-item">
                <strong>ID:</strong>
                <span class="property-id"><?= $formattedId ?></span>
            </div>

            <div class="info-item">
                <strong>Tipo:</strong>
                <span class="property-type"><?= htmlspecialchars($property['tipo_inmueble']) ?></span>
            </div>

            <div class="info-item">
                <strong>Estado:</strong>
                <span class="status <?= strtolower($property['estado']) ?>">
                    <?= htmlspecialchars($property['estado']) ?>
                </span>
            </div>

            <div class="info-item">
                <strong>Precio:</strong>
                <span class="price"><?= formatCurrency($property['precio']) ?></span>
            </div>

            <div class="info-item full-width">
                <strong>Dirección:</strong>
                <span><?= htmlspecialchars($property['direccion']) ?></span>
            </div>

            <div class="info-item">
                <strong>Ciudad:</strong>
                <span><?= htmlspecialchars($property['ciudad']) ?></span>
            </div>

            <div class="info-item">
                <strong>Registro:</strong>
                <span><?= formatDate($property['created_at']) ?></span>
            </div>
        </div>
    </div>

    <!-- Property Characteristics -->
    <div class="card property-details">
        <h3>Características</h3>

        <div class="characteristics-grid">
            <?php if ($property['area_construida']): ?>
                <div class="characteristic">
                    <div class="char-icon">🏠</div>
                    <div class="char-info">
                        <strong><?= number_format($property['area_construida'], 1) ?> m²</strong>
                        <span>Área Construida</span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($property['area_lote']): ?>
                <div class="characteristic">
                    <div class="char-icon">🌿</div>
                    <div class="char-info">
                        <strong><?= number_format($property['area_lote'], 1) ?> m²</strong>
                        <span>Área del Lote</span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($property['habitaciones']): ?>
                <div class="characteristic">
                    <div class="char-icon">🛏️</div>
                    <div class="char-info">
                        <strong><?= $property['habitaciones'] ?></strong>
                        <span>Habitaciones</span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($property['banos']): ?>
                <div class="characteristic">
                    <div class="char-icon">🚿</div>
                    <div class="char-info">
                        <strong><?= $property['banos'] ?></strong>
                        <span>Baños</span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($property['garaje']): ?>
                <div class="characteristic">
                    <div class="char-icon">🚗</div>
                    <div class="char-info">
                        <strong>Sí</strong>
                        <span>Garaje</span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Description -->
<?php if (!empty($property['descripcion'])): ?>
    <div class="card">
        <h3>Descripción</h3>
        <p class="property-description"><?= nl2br(htmlspecialchars($property['descripcion'])) ?></p>
    </div>
<?php endif; ?>

<!-- Photo Gallery -->
<?php if (!empty($photos)): ?>
    <div class="card">
        <h3>Galería de Fotos (<?= count($photos) ?>)</h3>

        <div class="photo-gallery">
            <?php foreach ($photos as $index => $photo): ?>
                <?php
                // Determine the correct path for the image
                // Check if it's a custom uploaded photo or a default image
                if (strpos($photo, 'img/') === 0 || strpos($photo, 'casa') !== false) {
                    // Default image from img/ folder
                    $photoSrc = (strpos($photo, 'img/') === 0) ? BASE_URL . $photo : BASE_URL . 'img/' . $photo;
                } else {
                    // Custom uploaded photo
                    $photoSrc = BASE_URL . 'assets/uploads/properties/' . $photo;
                }
                ?>
                <div class="photo-item" data-photo-index="<?= $index ?>">
                    <img
                        src="<?= htmlspecialchars($photoSrc) ?>"
                        alt="Foto de la propiedad <?= $index + 1 ?>"
                        onclick="openPhotoModal(<?= $index ?>)"
                        loading="lazy"
                        onerror="this.src='<?= BASE_URL ?>img/casa1.jpeg'"
                    >
                    <div class="photo-overlay">
                        <span class="photo-number"><?= $index + 1 ?></span>
                        <button type="button"
                                class="photo-action"
                                onclick="downloadPhoto('<?= htmlspecialchars($photoSrc) ?>')"
                                title="Descargar foto">
                            📥
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Related Information Tabs -->
<div class="card">
    <h3>Historial y Relaciones</h3>

    <div class="tabs">
        <div class="tab-buttons">
            <button type="button" class="tab-button active" data-tab="visits">
                Visitas (<?= count($relatedData['visits']) ?>)
            </button>
            <button type="button" class="tab-button" data-tab="contracts">
                Contratos (<?= count($relatedData['contracts']) ?>)
            </button>
            <button type="button" class="tab-button" data-tab="sales">
                Ventas (<?= count($relatedData['sales']) ?>)
            </button>
            <button type="button" class="tab-button" data-tab="rentals">
                Arriendos (<?= count($relatedData['rentals']) ?>)
            </button>
        </div>

        <!-- Visits Tab -->
        <div class="tab-content active" id="tab-visits">
            <?php if (!empty($relatedData['visits'])): ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Cliente</th>
                                <th>Agente</th>
                                <th>Estado</th>
                                <th>Calificación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($relatedData['visits'] as $visit): ?>
                                <tr>
                                    <td><?= formatDate($visit['fecha_visita']) ?></td>
                                    <td><?= date('H:i', strtotime($visit['hora_visita'])) ?></td>
                                    <td>
                                        <a href="?module=clients&action=view&id=<?= $visit['id_cliente'] ?>">
                                            <?= htmlspecialchars($visit['cliente_nombre'] . ' ' . $visit['cliente_apellido']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if ($visit['agente_nombre']): ?>
                                            <a href="?module=agents&action=view&id=<?= $visit['id_agente'] ?>">
                                                <?= htmlspecialchars($visit['agente_nombre']) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">Sin asignar</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status <?= strtolower($visit['estado']) ?>">
                                            <?= htmlspecialchars($visit['estado']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($visit['calificacion']): ?>
                                            <span class="interest-level"><?= htmlspecialchars($visit['calificacion']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Pendiente</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="no-data">No hay visitas registradas para esta propiedad.</p>
            <?php endif; ?>
        </div>

        <!-- Contracts Tab -->
        <div class="tab-content" id="tab-contracts">
            <?php if (!empty($relatedData['contracts'])): ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tipo</th>
                                <th>Cliente</th>
                                <th>Fecha Inicio</th>
                                <th>Fecha Fin</th>
                                <th>Valor</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($relatedData['contracts'] as $contract): ?>
                                <tr>
                                    <td>
                                        <a href="?module=contracts&action=view&id=<?= $contract['id_contrato'] ?>">
                                            CON<?= str_pad($contract['id_contrato'], 3, '0', STR_PAD_LEFT) ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($contract['tipo_contrato']) ?></td>
                                    <td>
                                        <a href="?module=clients&action=view&id=<?= $contract['id_cliente'] ?>">
                                            <?= htmlspecialchars($contract['nombre'] . ' ' . $contract['apellido']) ?>
                                        </a>
                                    </td>
                                    <td><?= formatDate($contract['fecha_inicio']) ?></td>
                                    <td><?= $contract['fecha_fin'] ? formatDate($contract['fecha_fin']) : 'N/A' ?></td>
                                    <td><?= formatCurrency($contract['valor_contrato'] ?? 0) ?></td>
                                    <td>
                                        <span class="status <?= strtolower($contract['estado']) ?>">
                                            <?= htmlspecialchars($contract['estado']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="no-data">No hay contratos registrados para esta propiedad.</p>
            <?php endif; ?>
        </div>

        <!-- Sales Tab -->
        <div class="tab-content" id="tab-sales">
            <?php if (!empty($relatedData['sales'])): ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Cliente</th>
                                <th>Valor</th>
                                <th>Comisión</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($relatedData['sales'] as $sale): ?>
                                <tr>
                                    <td>
                                        <a href="?module=sales&action=view&id=<?= $sale['id_venta'] ?>">
                                            VEN<?= str_pad($sale['id_venta'], 3, '0', STR_PAD_LEFT) ?>
                                        </a>
                                    </td>
                                    <td><?= formatDate($sale['fecha_venta']) ?></td>
                                    <td>
                                        <a href="?module=clients&action=view&id=<?= $sale['id_cliente'] ?>">
                                            <?= htmlspecialchars($sale['nombre'] . ' ' . $sale['apellido']) ?>
                                        </a>
                                    </td>
                                    <td><?= formatCurrency($sale['valor']) ?></td>
                                    <td><?= $sale['comision'] ? formatCurrency($sale['comision']) : 'N/A' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="no-data">No hay ventas registradas para esta propiedad.</p>
            <?php endif; ?>
        </div>

        <!-- Rentals Tab -->
        <div class="tab-content" id="tab-rentals">
            <?php if (!empty($relatedData['rentals'])): ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Fecha Inicio</th>
                                <th>Fecha Fin</th>
                                <th>Canon Mensual</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($relatedData['rentals'] as $rental): ?>
                                <tr>
                                    <td>
                                        <a href="?module=rentals&action=view&id=<?= $rental['id_arriendo'] ?>">
                                            ARR<?= str_pad($rental['id_arriendo'], 3, '0', STR_PAD_LEFT) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="?module=clients&action=view&id=<?= $rental['id_cliente'] ?>">
                                            <?= htmlspecialchars($rental['nombre'] . ' ' . $rental['apellido']) ?>
                                        </a>
                                    </td>
                                    <td><?= formatDate($rental['fecha_inicio']) ?></td>
                                    <td><?= formatDate($rental['fecha_fin']) ?></td>
                                    <td><?= formatCurrency($rental['canon_mensual']) ?></td>
                                    <td>
                                        <span class="status <?= strtolower($rental['estado']) ?>">
                                            <?= htmlspecialchars($rental['estado']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="no-data">No hay arriendos registrados para esta propiedad.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- JavaScript for Enhanced Functionality -->
<script>
/**
 * Property view functionality
 */

// Tab switching
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabId = this.dataset.tab;

            // Remove active class from all buttons and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));

            // Add active class to clicked button and corresponding content
            this.classList.add('active');
            document.getElementById('tab-' + tabId).classList.add('active');
        });
    });
});

// Photo modal functionality
function openPhotoModal(index) {
    const photos = <?= json_encode($photos) ?>;
    if (!photos[index]) return;

    // Helper function to get the correct photo path
    function getPhotoPath(photo) {
        const baseUrl = '<?= BASE_URL ?>';
        if (photo.indexOf('img/') === 0 || photo.indexOf('casa') !== -1) {
            return photo.indexOf('img/') === 0 ? baseUrl + photo : baseUrl + 'img/' + photo;
        } else {
            return baseUrl + 'assets/uploads/properties/' + photo;
        }
    }

    const modal = document.createElement('div');
    modal.className = 'photo-modal';
    modal.innerHTML = `
        <div class="photo-modal-content">
            <button class="photo-modal-close" onclick="this.closest('.photo-modal').remove()">&times;</button>
            <img src="${getPhotoPath(photos[index])}" alt="Foto de la propiedad" onerror="this.src='<?= BASE_URL ?>img/casa1.jpeg'">
            <div class="photo-modal-nav">
                <button onclick="changePhoto(-1)" ${index === 0 ? 'disabled' : ''}>‹ Anterior</button>
                <span>${index + 1} de ${photos.length}</span>
                <button onclick="changePhoto(1)" ${index === photos.length - 1 ? 'disabled' : ''}>Siguiente ›</button>
            </div>
        </div>
    `;

    modal.currentIndex = index;
    document.body.appendChild(modal);

    // Close on click outside
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.remove();
        }
    });
}

function changePhoto(direction) {
    const modal = document.querySelector('.photo-modal');
    if (!modal) return;

    const photos = <?= json_encode($photos) ?>;
    const newIndex = modal.currentIndex + direction;

    // Helper function to get the correct photo path
    function getPhotoPath(photo) {
        const baseUrl = '<?= BASE_URL ?>';
        if (photo.indexOf('img/') === 0 || photo.indexOf('casa') !== -1) {
            return photo.indexOf('img/') === 0 ? baseUrl + photo : baseUrl + 'img/' + photo;
        } else {
            return baseUrl + 'assets/uploads/properties/' + photo;
        }
    }

    if (newIndex >= 0 && newIndex < photos.length) {
        modal.currentIndex = newIndex;
        const img = modal.querySelector('img');
        const nav = modal.querySelector('.photo-modal-nav span');
        const prevBtn = modal.querySelector('.photo-modal-nav button:first-of-type');
        const nextBtn = modal.querySelector('.photo-modal-nav button:last-of-type');

        img.src = getPhotoPath(photos[newIndex]);
        nav.textContent = `${newIndex + 1} de ${photos.length}`;

        prevBtn.disabled = newIndex === 0;
        nextBtn.disabled = newIndex === photos.length - 1;
    }
}

function downloadPhoto(filename) {
    const link = document.createElement('a');
    link.href = filename; // filename is already the full path
    link.download = filename.split('/').pop();
    link.click();
}

function changeStatus(propertyId) {
    const currentStatus = '<?= $property['estado'] ?>';
    const statusOptions = <?= json_encode(array_keys(PROPERTY_STATUS)) ?>;

    let optionsHtml = '';
    statusOptions.forEach(status => {
        if (status !== currentStatus) {
            optionsHtml += `<option value="${status}">${status}</option>`;
        }
    });

    const content = `
        <div class="status-change-form">
            <p>Estado actual: <strong>${currentStatus}</strong></p>
            <div class="form-group">
                <label for="new-status">Nuevo estado:</label>
                <select id="new-status" class="form-control">
                    <option value="">Seleccione...</option>
                    ${optionsHtml}
                </select>
            </div>
        </div>
    `;

    const footer = `
        <button type="button" class="btn btn-primary" onclick="confirmStatusChange(${propertyId})">Cambiar Estado</button>
        <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
    `;

    App.openModal(content, 'Cambiar Estado de la Propiedad', footer);
}

async function confirmStatusChange(propertyId) {
    const newStatus = document.getElementById('new-status').value;
    if (!newStatus) {
        alert('Seleccione un nuevo estado');
        return;
    }

    try {
        console.log('Actualizando estado de propiedad:', propertyId, 'a:', newStatus);
        const response = await Ajax.properties.updateStatus(propertyId, newStatus);
        console.log('Respuesta del servidor:', response);

        if (response.success) {
            App.showSuccessMessage('Estado actualizado correctamente');
            window.location.reload();
        } else {
            throw new Error(response.error || 'Error desconocido');
        }
    } catch (error) {
        console.error('Error al actualizar estado:', error);
        App.showErrorMessage('Error al actualizar el estado: ' + error.message);
    } finally {
        App.closeModal();
    }
}

function printProperty() {
    window.print();
}

function shareProperty() {
    const url = window.location.href;
    const title = 'Propiedad <?= $formattedId ?> - <?= htmlspecialchars($property['direccion']) ?>';

    if (navigator.share) {
        navigator.share({
            title: title,
            url: url
        });
    } else {
        // Fallback: copy to clipboard
        navigator.clipboard.writeText(url).then(() => {
            App.showSuccessMessage('Enlace copiado al portapapeles');
        });
    }
}
</script>

<style>
/* Property view specific styles */
.property-info .info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--spacing-sm);
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.info-item.full-width {
    grid-column: 1 / -1;
}

.property-id {
    font-family: monospace;
    font-weight: bold;
    color: var(--primary-color);
    font-size: 1.1em;
}

.characteristics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: var(--spacing-md);
}

.characteristic {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    padding: var(--spacing-sm);
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
}

.char-icon {
    font-size: 1.5em;
    width: 40px;
    text-align: center;
}

.char-info strong {
    display: block;
    color: var(--primary-color);
    font-size: 1.1em;
}

.char-info span {
    font-size: 0.9em;
    color: var(--text-secondary);
}

.property-description {
    line-height: 1.6;
    color: var(--text-primary);
}

.photo-gallery {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: var(--spacing-sm);
    margin-top: var(--spacing-md);
}

.photo-item {
    position: relative;
    aspect-ratio: 4/3;
    border-radius: var(--border-radius);
    overflow: hidden;
    cursor: pointer;
    transition: transform var(--transition-fast);
}

.photo-item:hover {
    transform: scale(1.02);
}

.photo-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.photo-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.3);
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: var(--spacing-xs);
    opacity: 0;
    transition: opacity var(--transition-fast);
}

.photo-item:hover .photo-overlay {
    opacity: 1;
}

.photo-number {
    background: rgba(0,0,0,0.7);
    color: white;
    padding: 4px 8px;
    border-radius: var(--border-radius);
    font-size: 0.8em;
}

.photo-action {
    background: rgba(255,255,255,0.9);
    border: none;
    padding: 4px 8px;
    border-radius: var(--border-radius);
    cursor: pointer;
    font-size: 0.9em;
}

.photo-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.9);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.photo-modal-content {
    position: relative;
    max-width: 90vw;
    max-height: 90vh;
    text-align: center;
}

.photo-modal-content img {
    max-width: 100%;
    max-height: 80vh;
    object-fit: contain;
}

.photo-modal-close {
    position: absolute;
    top: -40px;
    right: 0;
    background: none;
    border: none;
    color: white;
    font-size: 2em;
    cursor: pointer;
}

.photo-modal-nav {
    margin-top: var(--spacing-md);
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: white;
}

.photo-modal-nav button {
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    padding: var(--spacing-sm) var(--spacing-md);
    border-radius: var(--border-radius);
    cursor: pointer;
}

.photo-modal-nav button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.tabs {
    margin-top: var(--spacing-lg);
}

.tab-buttons {
    display: flex;
    gap: 4px;
    border-bottom: 1px solid var(--border-color);
    margin-bottom: var(--spacing-lg);
    flex-wrap: wrap;
}

.tab-button {
    padding: var(--spacing-sm) var(--spacing-md);
    background: none;
    border: none;
    border-bottom: 2px solid transparent;
    cursor: pointer;
    font-size: var(--font-size-sm);
    transition: all var(--transition-fast);
}

.tab-button.active {
    border-bottom-color: var(--secondary-color);
    color: var(--secondary-color);
    font-weight: 600;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.no-data {
    text-align: center;
    color: var(--text-secondary);
    font-style: italic;
    padding: var(--spacing-xl);
}

.interest-level {
    padding: 2px 6px;
    border-radius: var(--border-radius);
    font-size: 0.8em;
    font-weight: 500;
}

.status-change-form {
    padding: var(--spacing-md);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .characteristics-grid {
        grid-template-columns: 1fr;
    }

    .photo-gallery {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    }

    .tab-buttons {
        flex-direction: column;
    }

    .tab-button {
        text-align: left;
        width: 100%;
    }
}

/* Print styles */
@media print {
    .action-buttons,
    .photo-overlay,
    .tab-buttons {
        display: none !important;
    }

    .tab-content {
        display: block !important;
    }
}
</style>