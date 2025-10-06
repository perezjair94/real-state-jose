<?php
/**
 * Rental Detail View - Real Estate Management System
 * Display detailed information about a specific rental agreement
 * Educational PHP/MySQL Project
 */

// Prevent direct access
if (!defined('APP_ACCESS')) {
    die('Direct access not permitted');
}

$error = '';
$rental = null;
$rentalId = (int)($_GET['id'] ?? 0);

// Validate rental ID and load data
if ($rentalId <= 0) {
    $error = "ID de arriendo inválido";
} else {
    try {
        $db = new Database();
        $pdo = $db->getConnection();

        // Load rental data with all related information
        $stmt = $pdo->prepare("
            SELECT a.*,
                   i.tipo_inmueble, i.direccion, i.ciudad, i.precio as precio_inmueble,
                   i.area_construida, i.habitaciones, i.banos, i.estado as estado_inmueble,
                   cl.nombre as cliente_nombre, cl.apellido as cliente_apellido,
                   cl.correo as cliente_correo, cl.tipo_cliente, cl.tipo_documento, cl.nro_documento,
                   ag.nombre as agente_nombre, ag.correo as agente_correo, ag.telefono as agente_telefono
            FROM arriendo a
            LEFT JOIN inmueble i ON a.id_inmueble = i.id_inmueble
            LEFT JOIN cliente cl ON a.id_cliente = cl.id_cliente
            LEFT JOIN agente ag ON a.id_agente = ag.id_agente
            WHERE a.id_arriendo = ?
        ");
        $stmt->execute([$rentalId]);
        $rental = $stmt->fetch();

        if (!$rental) {
            $error = "Arriendo no encontrado";
        }

    } catch (PDOException $e) {
        error_log("Error loading rental details: " . $e->getMessage());
        $error = "Error al cargar los datos del arriendo";
    }
}

// Calculate rental duration
$durationInfo = '';
if ($rental && $rental['fecha_inicio'] && $rental['fecha_fin']) {
    $inicio = new DateTime($rental['fecha_inicio']);
    $fin = new DateTime($rental['fecha_fin']);
    $diff = $inicio->diff($fin);
    $diffDays = $diff->days;
    $months = floor($diffDays / 30);
    $days = $diffDays % 30;

    $durationInfo = $diffDays . ' días';
    if ($months > 0) {
        $durationInfo .= ' (' . $months . ($months === 1 ? ' mes' : ' meses');
        if ($days > 0) {
            $durationInfo .= ' y ' . $days . ($days === 1 ? ' día' : ' días');
        }
        $durationInfo .= ')';
    }
}

// Calculate days remaining
$daysRemaining = null;
$isExpiringSoon = false;
if ($rental && $rental['fecha_fin'] && $rental['estado'] === 'Activo') {
    $today = new DateTime();
    $finDate = new DateTime($rental['fecha_fin']);
    if ($finDate > $today) {
        $daysRemaining = $today->diff($finDate)->days;
        $isExpiringSoon = $daysRemaining <= 30;
    }
}

// Calculate total contract value
$totalValue = 0;
if ($rental && $rental['fecha_inicio'] && $rental['fecha_fin']) {
    $inicio = new DateTime($rental['fecha_inicio']);
    $fin = new DateTime($rental['fecha_fin']);
    $months = $inicio->diff($fin)->m + ($inicio->diff($fin)->y * 12);
    $totalValue = $rental['canon_mensual'] * $months;
}
?>

<div class="module-header">
    <h2>Detalles del Arriendo</h2>
    <p class="module-description">
        Información completa del contrato de arrendamiento.
    </p>
</div>

<!-- Navigation Breadcrumb -->
<div class="breadcrumb">
    <a href="?module=rentals">Arriendos</a> >
    <?php if ($rental): ?>
        ARR<?= str_pad($rental['id_arriendo'], 3, '0', STR_PAD_LEFT) ?> - <?= formatCurrency($rental['canon_mensual']) ?>/mes
    <?php else: ?>
        Arriendo no encontrado
    <?php endif; ?>
</div>

<!-- Action Buttons -->
<div class="action-buttons">
    <a href="?module=rentals" class="btn btn-secondary">
        ← Volver a Lista
    </a>
    <?php if ($rental): ?>
        <a href="?module=rentals&action=edit&id=<?= $rentalId ?>" class="btn btn-primary">
            Editar Arriendo
        </a>
        <button type="button" class="btn btn-success" onclick="registerPayment(<?= $rentalId ?>)">
            Registrar Pago
        </button>
        <button type="button" class="btn btn-info" onclick="exportRentalData(<?= $rentalId ?>)">
            Exportar Datos
        </button>
        <button type="button" class="btn btn-warning" onclick="printContract(<?= $rentalId ?>)">
            Imprimir Contrato
        </button>
        <?php if ($rental['estado'] !== 'Terminado'): ?>
            <button type="button" class="btn btn-outline" onclick="confirmStatusChange(<?= $rentalId ?>, '<?= $rental['estado'] ?>')">
                Cambiar Estado
            </button>
        <?php endif; ?>
        <button type="button" class="btn btn-danger" onclick="confirmDelete(<?= $rentalId ?>)">
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
            <h3>Arriendo No Encontrado</h3>
            <p>El arriendo solicitado no existe o ha sido eliminado.</p>
            <a href="?module=rentals" class="btn btn-primary">Volver a Lista de Arriendos</a>
        </div>
    </div>
<?php else: ?>

    <!-- Rental Header Card -->
    <div class="card rental-header status-<?= strtolower($rental['estado']) ?>">
        <div class="rental-info">
            <div class="rental-main">
                <h2>
                    Arriendo ARR<?= str_pad($rental['id_arriendo'], 3, '0', STR_PAD_LEFT) ?>
                </h2>
                <div class="rental-amount-display">
                    <?= formatCurrency($rental['canon_mensual']) ?>
                    <small>/mes</small>
                </div>
                <div class="rental-status-display">
                    <span class="status-badge status-<?= strtolower($rental['estado']) ?>">
                        <?= htmlspecialchars($rental['estado']) ?>
                    </span>
                </div>
            </div>
            <div class="rental-meta">
                <div class="meta-item">
                    <strong>Fecha de Inicio:</strong> <?= formatDate($rental['fecha_inicio']) ?>
                </div>
                <div class="meta-item">
                    <strong>Fecha de Fin:</strong> <?= formatDate($rental['fecha_fin']) ?>
                </div>
                <?php if ($durationInfo): ?>
                    <div class="meta-item">
                        <strong>Duración:</strong> <?= $durationInfo ?>
                    </div>
                <?php endif; ?>
                <?php if ($daysRemaining !== null): ?>
                    <div class="meta-item <?= $isExpiringSoon ? 'warning' : '' ?>">
                        <strong>Días restantes:</strong> <?= $daysRemaining ?>
                        <?php if ($isExpiringSoon): ?>
                            ⚠️ <em>¡Próximo a vencer!</em>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php if ($rental['deposito']): ?>
                    <div class="meta-item">
                        <strong>Depósito:</strong> <?= formatCurrency($rental['deposito']) ?>
                    </div>
                <?php endif; ?>
                <div class="meta-item">
                    <strong>Registrado:</strong> <?= formatDate($rental['created_at']) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Details Grid -->
    <div class="details-grid">
        <!-- Property Information -->
        <div class="card detail-card">
            <h3>Inmueble Arrendado</h3>
            <div class="detail-group">
                <div class="detail-item">
                    <label>ID Inmueble:</label>
                    <value>
                        <a href="?module=properties&action=view&id=<?= $rental['id_inmueble'] ?>">
                            <span class="property-id">INM<?= str_pad($rental['id_inmueble'], 3, '0', STR_PAD_LEFT) ?></span>
                        </a>
                    </value>
                </div>
                <div class="detail-item">
                    <label>Tipo:</label>
                    <value><?= htmlspecialchars($rental['tipo_inmueble']) ?></value>
                </div>
                <div class="detail-item">
                    <label>Dirección:</label>
                    <value><?= htmlspecialchars($rental['direccion']) ?></value>
                </div>
                <div class="detail-item">
                    <label>Ciudad:</label>
                    <value><?= htmlspecialchars($rental['ciudad']) ?></value>
                </div>
                <?php if ($rental['area_construida']): ?>
                    <div class="detail-item">
                        <label>Área Construida:</label>
                        <value><?= number_format($rental['area_construida'], 2) ?> m²</value>
                    </div>
                <?php endif; ?>
                <?php if ($rental['habitaciones']): ?>
                    <div class="detail-item">
                        <label>Habitaciones:</label>
                        <value><?= $rental['habitaciones'] ?></value>
                    </div>
                <?php endif; ?>
                <?php if ($rental['banos']): ?>
                    <div class="detail-item">
                        <label>Baños:</label>
                        <value><?= $rental['banos'] ?></value>
                    </div>
                <?php endif; ?>
                <div class="detail-item">
                    <label>Precio del Inmueble:</label>
                    <value><?= formatCurrency($rental['precio_inmueble']) ?></value>
                </div>
                <div class="detail-item">
                    <label>Estado Actual:</label>
                    <value>
                        <span class="status-badge status-<?= strtolower($rental['estado_inmueble']) ?>">
                            <?= htmlspecialchars($rental['estado_inmueble']) ?>
                        </span>
                    </value>
                </div>
            </div>
        </div>

        <!-- Tenant Information -->
        <div class="card detail-card">
            <h3>Arrendatario</h3>
            <div class="detail-group">
                <div class="detail-item">
                    <label>ID Cliente:</label>
                    <value>
                        <a href="?module=clients&action=view&id=<?= $rental['id_cliente'] ?>">
                            CLI<?= str_pad($rental['id_cliente'], 3, '0', STR_PAD_LEFT) ?>
                        </a>
                    </value>
                </div>
                <div class="detail-item">
                    <label>Nombre Completo:</label>
                    <value><?= htmlspecialchars($rental['cliente_nombre'] . ' ' . $rental['cliente_apellido']) ?></value>
                </div>
                <div class="detail-item">
                    <label>Tipo de Cliente:</label>
                    <value>
                        <span class="client-type <?= strtolower(str_replace(' ', '-', $rental['tipo_cliente'])) ?>">
                            <?= htmlspecialchars($rental['tipo_cliente']) ?>
                        </span>
                    </value>
                </div>
                <div class="detail-item">
                    <label>Documento:</label>
                    <value><?= htmlspecialchars($rental['tipo_documento'] . ' ' . $rental['nro_documento']) ?></value>
                </div>
                <div class="detail-item">
                    <label>Email:</label>
                    <value>
                        <a href="mailto:<?= htmlspecialchars($rental['cliente_correo']) ?>" class="email-link">
                            <?= htmlspecialchars($rental['cliente_correo']) ?>
                        </a>
                    </value>
                </div>
            </div>
        </div>

        <!-- Agent Information -->
        <div class="card detail-card">
            <h3>Agente Responsable</h3>
            <?php if ($rental['id_agente']): ?>
                <div class="detail-group">
                    <div class="detail-item">
                        <label>ID Agente:</label>
                        <value>
                            <a href="?module=agents&action=view&id=<?= $rental['id_agente'] ?>">
                                AGE<?= str_pad($rental['id_agente'], 3, '0', STR_PAD_LEFT) ?>
                            </a>
                        </value>
                    </div>
                    <div class="detail-item">
                        <label>Nombre:</label>
                        <value><?= htmlspecialchars($rental['agente_nombre']) ?></value>
                    </div>
                    <div class="detail-item">
                        <label>Email:</label>
                        <value>
                            <a href="mailto:<?= htmlspecialchars($rental['agente_correo']) ?>" class="email-link">
                                <?= htmlspecialchars($rental['agente_correo']) ?>
                            </a>
                        </value>
                    </div>
                    <div class="detail-item">
                        <label>Teléfono:</label>
                        <value><?= htmlspecialchars($rental['agente_telefono']) ?></value>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-muted">No se asignó agente a este arriendo</p>
            <?php endif; ?>
        </div>

        <!-- Financial Summary -->
        <div class="card detail-card summary-card">
            <h3>Resumen Financiero</h3>
            <div class="financial-summary">
                <div class="summary-row">
                    <span class="label">Canon Mensual:</span>
                    <span class="value amount"><?= formatCurrency($rental['canon_mensual']) ?></span>
                </div>
                <?php if ($rental['deposito']): ?>
                    <div class="summary-row">
                        <span class="label">Depósito de Garantía:</span>
                        <span class="value"><?= formatCurrency($rental['deposito']) ?></span>
                    </div>
                <?php endif; ?>
                <div class="summary-row">
                    <span class="label">Estado:</span>
                    <span class="value">
                        <span class="status-badge status-<?= strtolower($rental['estado']) ?>">
                            <?= htmlspecialchars($rental['estado']) ?>
                        </span>
                    </span>
                </div>
                <?php if ($totalValue > 0): ?>
                    <div class="summary-row highlight">
                        <span class="label">Duración del Contrato:</span>
                        <span class="value"><?= $durationInfo ?></span>
                    </div>
                    <div class="summary-row total">
                        <span class="label">Valor Total del Contrato:</span>
                        <span class="value"><?= formatCurrency($totalValue) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Observations -->
    <?php if ($rental['observaciones']): ?>
        <div class="card">
            <h3>Observaciones</h3>
            <div class="observations-content">
                <?= nl2br(htmlspecialchars($rental['observaciones'])) ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Payment History -->
    <div class="card">
        <h3>Historial de Pagos</h3>
        <div class="payment-placeholder">
            <p class="text-muted">📋 El historial de pagos se mostrará aquí en futuras versiones.</p>
            <p class="text-muted">Esta funcionalidad permitirá registrar y visualizar todos los pagos realizados por el arrendatario.</p>
            <button type="button" class="btn btn-primary" onclick="registerPayment(<?= $rentalId ?>)">
                Registrar Primer Pago
            </button>
        </div>
    </div>

    <!-- Timeline -->
    <div class="card">
        <h3>Línea de Tiempo</h3>
        <div class="timeline">
            <div class="timeline-item">
                <div class="timeline-marker"></div>
                <div class="timeline-content">
                    <div class="timeline-date"><?= formatDate($rental['created_at']) ?></div>
                    <div class="timeline-title">Arriendo Registrado</div>
                    <div class="timeline-description">El contrato de arrendamiento fue registrado en el sistema</div>
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-marker <?= $rental['estado'] === 'Activo' ? 'active' : '' ?>"></div>
                <div class="timeline-content">
                    <div class="timeline-date"><?= formatDate($rental['fecha_inicio']) ?></div>
                    <div class="timeline-title">Inicio del Arriendo</div>
                    <div class="timeline-description">
                        Canon mensual de <?= formatCurrency($rental['canon_mensual']) ?>
                    </div>
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-marker <?= $rental['estado'] === 'Terminado' ? 'completed' : '' ?>"></div>
                <div class="timeline-content">
                    <div class="timeline-date"><?= formatDate($rental['fecha_fin']) ?></div>
                    <div class="timeline-title">
                        <?= $rental['estado'] === 'Terminado' ? 'Arriendo Finalizado' : 'Fecha de Finalización' ?>
                    </div>
                    <div class="timeline-description">
                        <?= $rental['estado'] === 'Terminado' ? 'El contrato de arrendamiento ha finalizado' : 'Fecha programada para finalización del contrato' ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php endif; ?>

<!-- JavaScript for Enhanced Functionality -->
<script>
/**
 * Rental detail view functionality
 */

function confirmDelete(rentalId) {
    if (confirm('¿Está seguro de que desea eliminar este arriendo?\n\nEsta acción no se puede deshacer y puede afectar registros relacionados.')) {
        deleteRental(rentalId);
    }
}

async function deleteRental(rentalId) {
    try {
        const response = await Ajax.rentals.delete(rentalId);

        if (response.success) {
            App.showSuccessMessage('Arriendo eliminado correctamente');
            window.location.href = '?module=rentals';
        } else {
            throw new Error(response.error || 'Error desconocido');
        }
    } catch (error) {
        console.error('Error:', error);
        App.showErrorMessage('Error al eliminar el arriendo: ' + error.message);
    }
}

function confirmStatusChange(rentalId, currentStatus) {
    const statusOptions = {
        'Activo': ['Terminado', 'Moroso'],
        'Vencido': ['Terminado', 'Activo'],
        'Moroso': ['Activo', 'Terminado'],
        'Terminado': []
    };

    const availableStatuses = statusOptions[currentStatus] || [];

    if (availableStatuses.length === 0) {
        alert('No hay cambios de estado disponibles para un arriendo ' + currentStatus);
        return;
    }

    const modalContent = `
        <div class="status-change-modal">
            <h4>Cambiar Estado del Arriendo</h4>
            <p>Estado actual: <strong>${currentStatus}</strong></p>
            <div class="status-options">
                ${availableStatuses.map(status => `
                    <button type="button" class="btn btn-primary status-option"
                            onclick="changeRentalStatus(${rentalId}, '${status}')">
                        ${status}
                    </button>
                `).join('')}
            </div>
        </div>
    `;

    const footer = `
        <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
    `;

    App.openModal(modalContent, 'Cambiar Estado del Arriendo', footer);
}

async function changeRentalStatus(rentalId, newStatus) {
    try {
        const response = await Ajax.rentals.updateStatus(rentalId, newStatus);

        if (response.success) {
            App.showSuccessMessage('Estado actualizado correctamente');
            App.closeModal();
            location.reload();
        } else {
            throw new Error(response.error || 'Error desconocido');
        }
    } catch (error) {
        console.error('Error:', error);
        App.showErrorMessage('Error al cambiar el estado: ' + error.message);
    }
}

function registerPayment(rentalId) {
    const modalContent = `
        <div class="payment-form">
            <h4>Registrar Pago de Arriendo</h4>
            <p class="text-muted">Esta funcionalidad estará disponible en futuras versiones.</p>
            <div class="form-group">
                <label>Monto del Pago:</label>
                <input type="number" class="form-control" placeholder="Ingrese el monto" />
            </div>
            <div class="form-group">
                <label>Fecha de Pago:</label>
                <input type="date" class="form-control" />
            </div>
            <div class="form-group">
                <label>Método de Pago:</label>
                <select class="form-control">
                    <option>Efectivo</option>
                    <option>Transferencia</option>
                    <option>Cheque</option>
                </select>
            </div>
        </div>
    `;

    const footer = `
        <button type="button" class="btn btn-primary" onclick="alert('Función en desarrollo')">Guardar Pago</button>
        <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
    `;

    App.openModal(modalContent, 'Registrar Pago', footer);
}

function exportRentalData(rentalId) {
    const exportOptions = `
        <div class="export-options">
            <h4>Seleccione el formato de exportación:</h4>
            <div class="export-buttons">
                <button type="button" class="btn btn-primary" onclick="exportToFormat('pdf', ${rentalId})">
                    📄 PDF - Contrato Completo
                </button>
                <button type="button" class="btn btn-primary" onclick="exportToFormat('excel', ${rentalId})">
                    📊 Excel - Datos Completos
                </button>
                <button type="button" class="btn btn-primary" onclick="exportToFormat('receipt', ${rentalId})">
                    🧾 Recibo de Arriendo
                </button>
            </div>
        </div>
    `;

    const footer = `
        <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
    `;

    App.openModal(exportOptions, 'Exportar Datos del Arriendo', footer);
}

function exportToFormat(format, rentalId) {
    const params = new URLSearchParams({
        module: 'rentals',
        action: 'export',
        id: rentalId,
        format: format
    });

    window.open('?' + params.toString(), '_blank');
    App.closeModal();
}

function printContract(rentalId) {
    window.open(`?module=rentals&action=print&id=${rentalId}`, '_blank');
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    // Add click handlers for email links
    document.querySelectorAll('.email-link').forEach(link => {
        link.addEventListener('click', function(e) {
            console.log('Email clicked:', this.href);
        });
    });
});
</script>

<style>
/* Additional styles specific to rental detail view */
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

.rental-header {
    color: white;
    margin-bottom: var(--spacing-lg);
}

.rental-header.status-activo {
    background: linear-gradient(135deg, #28a745, #20c997);
}

.rental-header.status-vencido {
    background: linear-gradient(135deg, #dc3545, #c82333);
}

.rental-header.status-terminado {
    background: linear-gradient(135deg, #6c757d, #5a6268);
}

.rental-header.status-moroso {
    background: linear-gradient(135deg, #ff9800, #f57c00);
}

.rental-info {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: var(--spacing-lg);
}

.rental-main h2 {
    color: white;
    margin-bottom: var(--spacing-sm);
}

.rental-amount-display {
    font-size: 2.5rem;
    font-weight: 700;
    background: rgba(255, 255, 255, 0.2);
    padding: 12px 24px;
    border-radius: var(--border-radius);
    display: inline-block;
    margin-bottom: var(--spacing-sm);
}

.rental-amount-display small {
    font-size: 1rem;
}

.rental-status-display {
    margin-top: var(--spacing-sm);
}

.rental-meta {
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
}

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
}

.email-link {
    color: var(--secondary-color);
    text-decoration: none;
}

.email-link:hover {
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

.status-activo { background: #28a745; color: white; }
.status-vencido { background: #dc3545; color: white; }
.status-terminado { background: #6c757d; color: white; }
.status-moroso { background: #ff9800; color: white; }
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

.financial-summary {
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

.summary-row.total {
    background: var(--primary-color);
    color: white;
    font-weight: 700;
    font-size: var(--font-size-lg);
}

.observations-content {
    padding: var(--spacing-md);
    background: var(--bg-secondary);
    border-left: 4px solid var(--secondary-color);
    border-radius: var(--border-radius);
    line-height: 1.6;
}

.payment-placeholder {
    text-align: center;
    padding: var(--spacing-xl);
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
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

.status-change-modal {
    padding: var(--spacing-md);
}

.status-options {
    display: flex;
    gap: var(--spacing-sm);
    margin-top: var(--spacing-md);
    flex-wrap: wrap;
}

.status-option {
    flex: 1;
    min-width: 150px;
}

.text-muted {
    color: var(--text-secondary);
    font-style: italic;
}

.payment-form {
    padding: var(--spacing-md);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .details-grid {
        grid-template-columns: 1fr;
    }

    .rental-info {
        flex-direction: column;
        gap: var(--spacing-md);
    }

    .rental-meta {
        text-align: left;
    }

    .rental-amount-display {
        font-size: 1.8rem;
    }

    .export-buttons {
        flex-direction: column;
    }
}
</style>