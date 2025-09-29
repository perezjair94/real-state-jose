<?php
/**
 * Visit Detail View - Real Estate Management System
 * Display detailed information about a specific property visit
 * Educational PHP/MySQL Project
 */

// Prevent direct access
if (!defined('APP_ACCESS')) {
    die('Direct access not permitted');
}

$error = '';
$visit = null;
$visitId = (int)($_GET['id'] ?? 0);

// Validate visit ID and load data
if ($visitId <= 0) {
    $error = "ID de visita inválido";
} else {
    try {
        $db = new Database();
        $pdo = $db->getConnection();

        // Load visit data with all related information
        $stmt = $pdo->prepare("
            SELECT v.*,
                   i.tipo_inmueble, i.direccion, i.ciudad, i.precio as precio_inmueble,
                   i.area_construida, i.habitaciones, i.banos, i.estado as estado_inmueble,
                   cl.nombre as cliente_nombre, cl.apellido as cliente_apellido,
                   cl.correo as cliente_correo, cl.telefono as cliente_telefono,
                   cl.tipo_cliente, cl.tipo_documento, cl.nro_documento,
                   a.nombre as agente_nombre, a.correo as agente_correo, a.telefono as agente_telefono
            FROM visita v
            LEFT JOIN inmueble i ON v.id_inmueble = i.id_inmueble
            LEFT JOIN cliente cl ON v.id_cliente = cl.id_cliente
            LEFT JOIN agente a ON v.id_agente = a.id_agente
            WHERE v.id_visita = ?
        ");
        $stmt->execute([$visitId]);
        $visit = $stmt->fetch();

        if (!$visit) {
            $error = "Visita no encontrada";
        }

    } catch (PDOException $e) {
        error_log("Error loading visit details: " . $e->getMessage());
        $error = "Error al cargar los datos de la visita";
    }
}

// Check if visit is today
$isToday = false;
$isPast = false;
$isFuture = false;
if ($visit) {
    $visitDate = new DateTime($visit['fecha_visita']);
    $today = new DateTime();
    $today->setTime(0, 0, 0);

    $isToday = $visitDate->format('Y-m-d') === $today->format('Y-m-d');
    $isPast = $visitDate < $today;
    $isFuture = $visitDate > $today;
}
?>

<div class="module-header">
    <h2>Detalles de la Visita</h2>
    <p class="module-description">
        Información completa de la visita programada.
    </p>
</div>

<!-- Navigation Breadcrumb -->
<div class="breadcrumb">
    <a href="?module=visits">Visitas</a> >
    <?php if ($visit): ?>
        VIS<?= str_pad($visit['id_visita'], 3, '0', STR_PAD_LEFT) ?> - <?= formatDate($visit['fecha_visita']) ?>
    <?php else: ?>
        Visita no encontrada
    <?php endif; ?>
</div>

<!-- Action Buttons -->
<div class="action-buttons">
    <a href="?module=visits" class="btn btn-secondary">
        ← Volver a Lista
    </a>
    <?php if ($visit): ?>
        <?php if ($visit['estado'] === 'Programada'): ?>
            <a href="?module=visits&action=edit&id=<?= $visitId ?>" class="btn btn-primary">
                Editar Visita
            </a>
            <button type="button" class="btn btn-success" onclick="confirmVisit(<?= $visitId ?>)">
                Marcar como Realizada
            </button>
            <button type="button" class="btn btn-warning" onclick="rescheduleVisit(<?= $visitId ?>)">
                Reprogramar
            </button>
            <button type="button" class="btn btn-danger" onclick="cancelVisit(<?= $visitId ?>)">
                Cancelar Visita
            </button>
        <?php endif; ?>
        <button type="button" class="btn btn-info" onclick="exportVisitData(<?= $visitId ?>)">
            Exportar Datos
        </button>
        <button type="button" class="btn btn-outline" onclick="printVisit(<?= $visitId ?>)">
            Imprimir
        </button>
        <button type="button" class="btn btn-danger" onclick="confirmDelete(<?= $visitId ?>)">
            Eliminar
        </button>
    <?php endif; ?>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <?= htmlspecialchars($error) ?>
    </div>
    <div class="card">
        <div class="no-results">
            <h3>Visita No Encontrada</h3>
            <p>La visita solicitada no existe o ha sido eliminada.</p>
            <a href="?module=visits" class="btn btn-primary">Volver a Lista de Visitas</a>
        </div>
    </div>
<?php else: ?>

    <!-- Visit Header Card -->
    <div class="card visit-header status-<?= strtolower($visit['estado']) ?>">
        <div class="visit-info">
            <div class="visit-main">
                <h2>
                    Visita VIS<?= str_pad($visit['id_visita'], 3, '0', STR_PAD_LEFT) ?>
                    <?php if ($isToday): ?>
                        <span class="today-badge">HOY</span>
                    <?php endif; ?>
                </h2>
                <div class="visit-datetime-display">
                    📅 <?= formatDate($visit['fecha_visita']) ?>
                    <br>
                    🕐 <?= date('g:i A', strtotime($visit['hora_visita'])) ?>
                </div>
                <div class="visit-status-display">
                    <span class="status-badge status-<?= strtolower($visit['estado']) ?>">
                        <?= htmlspecialchars($visit['estado']) ?>
                    </span>
                </div>
            </div>
            <div class="visit-meta">
                <?php if ($visit['calificacion']): ?>
                    <div class="meta-item">
                        <strong>Nivel de Interés:</strong>
                        <span class="interest-badge interest-<?= strtolower(str_replace(' ', '-', $visit['calificacion'])) ?>">
                            <?= htmlspecialchars($visit['calificacion']) ?>
                        </span>
                    </div>
                <?php endif; ?>
                <div class="meta-item">
                    <strong>Registrado:</strong> <?= formatDate($visit['created_at']) ?>
                </div>
                <?php if ($isPast && $visit['estado'] === 'Programada'): ?>
                    <div class="meta-item warning">
                        ⚠️ <strong>Visita vencida</strong>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Details Grid -->
    <div class="details-grid">
        <!-- Property Information -->
        <div class="card detail-card">
            <h3>Inmueble a Visitar</h3>
            <div class="detail-group">
                <div class="detail-item">
                    <label>ID Inmueble:</label>
                    <value>
                        <a href="?module=properties&action=view&id=<?= $visit['id_inmueble'] ?>">
                            INM<?= str_pad($visit['id_inmueble'], 3, '0', STR_PAD_LEFT) ?>
                        </a>
                    </value>
                </div>
                <div class="detail-item">
                    <label>Tipo:</label>
                    <value><?= htmlspecialchars($visit['tipo_inmueble']) ?></value>
                </div>
                <div class="detail-item">
                    <label>Dirección:</label>
                    <value><?= htmlspecialchars($visit['direccion']) ?></value>
                </div>
                <div class="detail-item">
                    <label>Ciudad:</label>
                    <value><?= htmlspecialchars($visit['ciudad']) ?></value>
                </div>
                <?php if ($visit['area_construida']): ?>
                    <div class="detail-item">
                        <label>Área Construida:</label>
                        <value><?= number_format($visit['area_construida'], 2) ?> m²</value>
                    </div>
                <?php endif; ?>
                <?php if ($visit['habitaciones']): ?>
                    <div class="detail-item">
                        <label>Habitaciones:</label>
                        <value><?= $visit['habitaciones'] ?></value>
                    </div>
                <?php endif; ?>
                <?php if ($visit['banos']): ?>
                    <div class="detail-item">
                        <label>Baños:</label>
                        <value><?= $visit['banos'] ?></value>
                    </div>
                <?php endif; ?>
                <div class="detail-item">
                    <label>Precio:</label>
                    <value class="amount"><?= formatCurrency($visit['precio_inmueble']) ?></value>
                </div>
                <div class="detail-item">
                    <label>Estado del Inmueble:</label>
                    <value>
                        <span class="status-badge status-<?= strtolower($visit['estado_inmueble']) ?>">
                            <?= htmlspecialchars($visit['estado_inmueble']) ?>
                        </span>
                    </value>
                </div>
            </div>
        </div>

        <!-- Client Information -->
        <div class="card detail-card">
            <h3>Cliente Interesado</h3>
            <div class="detail-group">
                <div class="detail-item">
                    <label>ID Cliente:</label>
                    <value>
                        <a href="?module=clients&action=view&id=<?= $visit['id_cliente'] ?>">
                            CLI<?= str_pad($visit['id_cliente'], 3, '0', STR_PAD_LEFT) ?>
                        </a>
                    </value>
                </div>
                <div class="detail-item">
                    <label>Nombre Completo:</label>
                    <value><?= htmlspecialchars($visit['cliente_nombre'] . ' ' . $visit['cliente_apellido']) ?></value>
                </div>
                <div class="detail-item">
                    <label>Tipo de Cliente:</label>
                    <value>
                        <span class="client-type <?= strtolower(str_replace(' ', '-', $visit['tipo_cliente'])) ?>">
                            <?= htmlspecialchars($visit['tipo_cliente']) ?>
                        </span>
                    </value>
                </div>
                <div class="detail-item">
                    <label>Documento:</label>
                    <value><?= htmlspecialchars($visit['tipo_documento'] . ' ' . $visit['nro_documento']) ?></value>
                </div>
                <div class="detail-item">
                    <label>Email:</label>
                    <value>
                        <a href="mailto:<?= htmlspecialchars($visit['cliente_correo']) ?>" class="email-link">
                            <?= htmlspecialchars($visit['cliente_correo']) ?>
                        </a>
                    </value>
                </div>
                <div class="detail-item">
                    <label>Teléfono:</label>
                    <value>
                        <a href="tel:<?= htmlspecialchars($visit['cliente_telefono']) ?>" class="phone-link">
                            <?= htmlspecialchars($visit['cliente_telefono']) ?>
                        </a>
                    </value>
                </div>
            </div>
        </div>

        <!-- Agent Information -->
        <div class="card detail-card">
            <h3>Agente Responsable</h3>
            <div class="detail-group">
                <div class="detail-item">
                    <label>ID Agente:</label>
                    <value>
                        <a href="?module=agents&action=view&id=<?= $visit['id_agente'] ?>">
                            AGE<?= str_pad($visit['id_agente'], 3, '0', STR_PAD_LEFT) ?>
                        </a>
                    </value>
                </div>
                <div class="detail-item">
                    <label>Nombre:</label>
                    <value><?= htmlspecialchars($visit['agente_nombre']) ?></value>
                </div>
                <div class="detail-item">
                    <label>Email:</label>
                    <value>
                        <a href="mailto:<?= htmlspecialchars($visit['agente_correo']) ?>" class="email-link">
                            <?= htmlspecialchars($visit['agente_correo']) ?>
                        </a>
                    </value>
                </div>
                <div class="detail-item">
                    <label>Teléfono:</label>
                    <value>
                        <a href="tel:<?= htmlspecialchars($visit['agente_telefono']) ?>" class="phone-link">
                            <?= htmlspecialchars($visit['agente_telefono']) ?>
                        </a>
                    </value>
                </div>
            </div>
        </div>

        <!-- Visit Summary -->
        <div class="card detail-card summary-card">
            <h3>Resumen de la Visita</h3>
            <div class="visit-summary">
                <div class="summary-row">
                    <span class="label">Estado:</span>
                    <span class="value">
                        <span class="status-badge status-<?= strtolower($visit['estado']) ?>">
                            <?= htmlspecialchars($visit['estado']) ?>
                        </span>
                    </span>
                </div>
                <div class="summary-row">
                    <span class="label">Fecha:</span>
                    <span class="value"><?= formatDate($visit['fecha_visita']) ?></span>
                </div>
                <div class="summary-row">
                    <span class="label">Hora:</span>
                    <span class="value"><?= date('g:i A', strtotime($visit['hora_visita'])) ?></span>
                </div>
                <?php if ($visit['calificacion']): ?>
                    <div class="summary-row highlight">
                        <span class="label">Nivel de Interés:</span>
                        <span class="value">
                            <span class="interest-badge interest-<?= strtolower(str_replace(' ', '-', $visit['calificacion'])) ?>">
                                <?= htmlspecialchars($visit['calificacion']) ?>
                            </span>
                        </span>
                    </div>
                <?php endif; ?>
                <?php if ($isToday): ?>
                    <div class="summary-row today-alert">
                        <span class="label">📅 ¡La visita es HOY!</span>
                        <span class="value"><?= date('g:i A', strtotime($visit['hora_visita'])) ?></span>
                    </div>
                <?php elseif ($isFuture): ?>
                    <div class="summary-row">
                        <span class="label">Días restantes:</span>
                        <span class="value"><?= $visitDate->diff($today)->days ?> días</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Observations -->
    <?php if ($visit['observaciones']): ?>
        <div class="card">
            <h3>Observaciones</h3>
            <div class="observations-content">
                <?= nl2br(htmlspecialchars($visit['observaciones'])) ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Visit Timeline -->
    <div class="card">
        <h3>Línea de Tiempo</h3>
        <div class="timeline">
            <div class="timeline-item">
                <div class="timeline-marker"></div>
                <div class="timeline-content">
                    <div class="timeline-date"><?= formatDate($visit['created_at']) ?></div>
                    <div class="timeline-title">Visita Programada</div>
                    <div class="timeline-description">La visita fue registrada en el sistema</div>
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-marker <?= $visit['estado'] === 'Realizada' ? 'active' : ($visit['estado'] === 'Programada' ? 'pending' : '') ?>"></div>
                <div class="timeline-content">
                    <div class="timeline-date"><?= formatDate($visit['fecha_visita']) ?> - <?= $visit['hora_visita'] ?></div>
                    <div class="timeline-title">
                        <?php if ($visit['estado'] === 'Realizada'): ?>
                            Visita Realizada
                        <?php elseif ($visit['estado'] === 'Cancelada'): ?>
                            Visita Cancelada
                        <?php else: ?>
                            Fecha Programada
                        <?php endif; ?>
                    </div>
                    <div class="timeline-description">
                        <?php if ($visit['estado'] === 'Realizada'): ?>
                            La visita fue completada exitosamente
                        <?php elseif ($visit['estado'] === 'Cancelada'): ?>
                            La visita fue cancelada
                        <?php else: ?>
                            Visita programada con <?= htmlspecialchars($visit['cliente_nombre']) ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php if ($visit['estado'] === 'Realizada' && $visit['calificacion']): ?>
                <div class="timeline-item">
                    <div class="timeline-marker completed"></div>
                    <div class="timeline-content">
                        <div class="timeline-date"><?= formatDate($visit['updated_at'] ?? $visit['created_at']) ?></div>
                        <div class="timeline-title">Evaluación Registrada</div>
                        <div class="timeline-description">
                            Nivel de interés del cliente: <?= htmlspecialchars($visit['calificacion']) ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php endif; ?>

<!-- JavaScript for Enhanced Functionality -->
<script>
/**
 * Visit detail view functionality
 */

function confirmDelete(visitId) {
    if (confirm('¿Está seguro de que desea eliminar esta visita?\n\nEsta acción no se puede deshacer.')) {
        deleteVisit(visitId);
    }
}

async function deleteVisit(visitId) {
    try {
        const response = await Ajax.visits.delete(visitId);

        if (response.success) {
            App.showSuccessMessage('Visita eliminada correctamente');
            window.location.href = '?module=visits';
        } else {
            throw new Error(response.error || 'Error desconocido');
        }
    } catch (error) {
        console.error('Error:', error);
        App.showErrorMessage('Error al eliminar la visita: ' + error.message);
    }
}

function confirmVisit(visitId) {
    if (confirm('¿Marcar esta visita como realizada?')) {
        updateVisitStatus(visitId, 'Realizada');
    }
}

function cancelVisit(visitId) {
    if (confirm('¿Está seguro de que desea cancelar esta visita?')) {
        updateVisitStatus(visitId, 'Cancelada');
    }
}

function rescheduleVisit(visitId) {
    window.location.href = `?module=visits&action=edit&id=${visitId}`;
}

async function updateVisitStatus(visitId, newStatus) {
    try {
        const response = await Ajax.visits.updateStatus(visitId, newStatus);

        if (response.success) {
            App.showSuccessMessage('Estado actualizado correctamente');
            location.reload();
        } else {
            throw new Error(response.error || 'Error desconocido');
        }
    } catch (error) {
        console.error('Error:', error);
        App.showErrorMessage('Error al actualizar el estado: ' + error.message);
    }
}

function exportVisitData(visitId) {
    const exportOptions = `
        <div class="export-options">
            <h4>Seleccione el formato de exportación:</h4>
            <div class="export-buttons">
                <button type="button" class="btn btn-primary" onclick="exportToFormat('pdf', ${visitId})">
                    📄 PDF - Resumen de Visita
                </button>
                <button type="button" class="btn btn-primary" onclick="exportToFormat('excel', ${visitId})">
                    📊 Excel - Datos Completos
                </button>
            </div>
        </div>
    `;

    const footer = `
        <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
    `;

    App.openModal(exportOptions, 'Exportar Datos de la Visita', footer);
}

function exportToFormat(format, visitId) {
    const params = new URLSearchParams({
        module: 'visits',
        action: 'export',
        id: visitId,
        format: format
    });

    window.open('?' + params.toString(), '_blank');
    App.closeModal();
}

function printVisit(visitId) {
    window.open(`?module=visits&action=print&id=${visitId}`, '_blank');
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    // Add click handlers for email and phone links
    document.querySelectorAll('.email-link, .phone-link').forEach(link => {
        link.addEventListener('click', function(e) {
            console.log('Contact link clicked:', this.href);
        });
    });
});
</script>

<style>
/* Additional styles specific to visit detail view */
.breadcrumb {
    margin-bottom: var(--spacing-md);
    font-size: var(--font-size-sm);
    color: var(--text-secondary);
}

.breadcrumb a {
    color: var(--primary-color);
    text-decoration: none;
}

.breadcrumb a:hover {
    text-decoration: underline;
}

.visit-header {
    color: white;
    margin-bottom: var(--spacing-lg);
}

.visit-header.status-programada {
    background: linear-gradient(135deg, #2196f3, #1976d2);
}

.visit-header.status-realizada {
    background: linear-gradient(135deg, #28a745, #20c997);
}

.visit-header.status-cancelada {
    background: linear-gradient(135deg, #dc3545, #c82333);
}

.visit-header.status-reprogramada {
    background: linear-gradient(135deg, #ff9800, #f57c00);
}

.visit-info {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: var(--spacing-lg);
}

.visit-main h2 {
    color: white;
    margin-bottom: var(--spacing-sm);
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
}

.today-badge {
    background: #ffc107;
    color: #000;
    padding: 4px 12px;
    border-radius: var(--border-radius);
    font-size: var(--font-size-sm);
    font-weight: 700;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.visit-datetime-display {
    font-size: 1.5rem;
    font-weight: 600;
    background: rgba(255, 255, 255, 0.2);
    padding: 12px 24px;
    border-radius: var(--border-radius);
    display: inline-block;
    margin: var(--spacing-sm) 0;
}

.visit-status-display {
    margin-top: var(--spacing-sm);
}

.visit-meta {
    text-align: right;
    opacity: 0.9;
}

.meta-item {
    margin-bottom: var(--spacing-xs);
    font-size: var(--font-size-sm);
}

.meta-item.warning {
    color: #fff3cd;
    font-weight: 600;
    font-size: var(--font-size-md);
}

.interest-badge {
    padding: 4px 12px;
    border-radius: var(--border-radius);
    font-size: var(--font-size-sm);
    font-weight: 600;
    text-transform: uppercase;
}

.interest-muy-alto { background: #4caf50; color: white; }
.interest-alto { background: #8bc34a; color: white; }
.interest-medio { background: #ff9800; color: white; }
.interest-bajo { background: #ff5722; color: white; }
.interest-sin-interés { background: #9e9e9e; color: white; }

.details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: var(--spacing-lg);
    margin-bottom: var(--spacing-xl);
}

.detail-card h3 {
    color: var(--primary-color);
    border-bottom: 2px solid var(--border-color);
    padding-bottom: var(--spacing-sm);
    margin-bottom: var(--spacing-md);
}

.detail-group {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-md);
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.detail-item label {
    font-weight: 600;
    color: var(--text-secondary);
    font-size: var(--font-size-sm);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.detail-item value {
    font-size: var(--font-size-md);
    color: var(--text-primary);
}

.amount {
    font-family: monospace;
    font-weight: 600;
    color: #28a745;
    font-size: var(--font-size-lg);
}

.email-link, .phone-link {
    color: var(--secondary-color);
    text-decoration: none;
}

.email-link:hover, .phone-link:hover {
    text-decoration: underline;
}

.status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: var(--border-radius);
    font-size: var(--font-size-xs);
    font-weight: 600;
    text-transform: uppercase;
}

.status-programada { background: #2196f3; color: white; }
.status-realizada { background: #28a745; color: white; }
.status-cancelada { background: #dc3545; color: white; }
.status-reprogramada { background: #ff9800; color: white; }
.status-vendido { background: #f8d7da; color: #721c24; }
.status-disponible { background: #d4edda; color: #155724; }
.status-arrendado { background: #fff3cd; color: #856404; }

.client-type {
    display: inline-block;
    padding: 4px 8px;
    border-radius: var(--border-radius);
    font-size: var(--font-size-xs);
    font-weight: 600;
    text-transform: uppercase;
}

.client-type.comprador { background: #d4edda; color: #155724; }
.client-type.vendedor { background: #d1ecf1; color: #0c5460; }

.summary-card {
    background: var(--bg-secondary);
}

.visit-summary {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-sm);
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: var(--spacing-sm);
    border-bottom: 1px solid var(--border-color);
}

.summary-row.highlight {
    background: #fff3cd;
    font-weight: 600;
}

.summary-row.today-alert {
    background: #ffc107;
    color: #000;
    font-weight: 700;
    font-size: var(--font-size-lg);
    animation: pulse 2s infinite;
}

.observations-content {
    padding: var(--spacing-md);
    background: var(--bg-secondary);
    border-left: 4px solid var(--secondary-color);
    border-radius: var(--border-radius);
    line-height: 1.6;
}

.timeline {
    padding: var(--spacing-md);
}

.timeline-item {
    display: flex;
    gap: var(--spacing-md);
    margin-bottom: var(--spacing-lg);
    position: relative;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: 10px;
    top: 30px;
    bottom: -20px;
    width: 2px;
    background: var(--border-color);
}

.timeline-marker {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: var(--border-color);
    flex-shrink: 0;
    margin-top: 4px;
}

.timeline-marker.active {
    background: var(--primary-color);
    box-shadow: 0 0 0 4px rgba(44, 123, 229, 0.2);
}

.timeline-marker.pending {
    background: #ff9800;
    box-shadow: 0 0 0 4px rgba(255, 152, 0, 0.2);
}

.timeline-marker.completed {
    background: #28a745;
    box-shadow: 0 0 0 4px rgba(40, 167, 69, 0.2);
}

.timeline-content {
    flex: 1;
}

.timeline-date {
    font-size: var(--font-size-sm);
    color: var(--text-secondary);
    margin-bottom: 4px;
}

.timeline-title {
    font-weight: 600;
    margin-bottom: 4px;
}

.timeline-description {
    font-size: var(--font-size-sm);
    color: var(--text-secondary);
}

.export-options {
    padding: var(--spacing-md);
}

.export-buttons {
    display: flex;
    gap: var(--spacing-sm);
    margin-top: var(--spacing-md);
    flex-wrap: wrap;
}

.text-muted {
    color: var(--text-secondary);
    font-style: italic;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .details-grid {
        grid-template-columns: 1fr;
    }

    .visit-info {
        flex-direction: column;
        gap: var(--spacing-md);
    }

    .visit-meta {
        text-align: left;
    }

    .visit-datetime-display {
        font-size: 1.2rem;
    }

    .export-buttons {
        flex-direction: column;
    }
}
</style>