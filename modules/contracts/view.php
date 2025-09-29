<?php
/**
 * Contract Detail View - Real Estate Management System
 * Display detailed information about a specific contract
 * Educational PHP/MySQL Project
 */

// Prevent direct access
if (!defined('APP_ACCESS')) {
    die('Direct access not permitted');
}

$error = '';
$contract = null;
$contractId = (int)($_GET['id'] ?? 0);

// Additional data for detailed view
$propertyDetails = null;
$clientDetails = null;
$agentDetails = null;
$relatedSale = null;

// Validate contract ID and load data
if ($contractId <= 0) {
    $error = "ID de contrato inválido";
} else {
    try {
        $db = new Database();
        $pdo = $db->getConnection();

        // Load contract data with all related information
        $stmt = $pdo->prepare("
            SELECT c.*,
                   i.tipo_inmueble, i.direccion, i.ciudad, i.precio as precio_inmueble,
                   i.area_construida, i.habitaciones, i.banos, i.estado as estado_inmueble,
                   cl.nombre as cliente_nombre, cl.apellido as cliente_apellido,
                   cl.correo as cliente_correo, cl.tipo_cliente, cl.tipo_documento, cl.nro_documento,
                   a.nombre as agente_nombre, a.correo as agente_correo, a.telefono as agente_telefono
            FROM contrato c
            LEFT JOIN inmueble i ON c.id_inmueble = i.id_inmueble
            LEFT JOIN cliente cl ON c.id_cliente = cl.id_cliente
            LEFT JOIN agente a ON c.id_agente = a.id_agente
            WHERE c.id_contrato = ?
        ");
        $stmt->execute([$contractId]);
        $contract = $stmt->fetch();

        if (!$contract) {
            $error = "Contrato no encontrado";
        } else {
            // Get related sale if exists (for Venta contracts)
            if ($contract['tipo_contrato'] === 'Venta') {
                $stmt = $pdo->prepare("
                    SELECT * FROM venta
                    WHERE id_inmueble = ? AND id_cliente = ?
                    ORDER BY fecha_venta DESC
                    LIMIT 1
                ");
                $stmt->execute([$contract['id_inmueble'], $contract['id_cliente']]);
                $relatedSale = $stmt->fetch();
            }
        }

    } catch (PDOException $e) {
        error_log("Error loading contract details: " . $e->getMessage());
        $error = "Error al cargar los datos del contrato";
    }
}

// Calculate contract duration if applicable
$durationInfo = '';
if ($contract && $contract['fecha_inicio'] && $contract['fecha_fin']) {
    $inicio = new DateTime($contract['fecha_inicio']);
    $fin = new DateTime($contract['fecha_fin']);
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

// Calculate days remaining (for active Arriendo contracts)
$daysRemaining = null;
$isExpiringSoon = false;
if ($contract && $contract['fecha_fin'] && $contract['estado'] === 'Activo') {
    $today = new DateTime();
    $finDate = new DateTime($contract['fecha_fin']);
    if ($finDate > $today) {
        $daysRemaining = $today->diff($finDate)->days;
        $isExpiringSoon = $daysRemaining <= 30;
    }
}
?>

<div class="module-header">
    <h2>Detalles del Contrato</h2>
    <p class="module-description">
        Información completa del contrato de <?= $contract ? strtolower($contract['tipo_contrato']) : 'N/A' ?>.
    </p>
</div>

<!-- Navigation Breadcrumb -->
<div class="breadcrumb">
    <a href="?module=contracts">Contratos</a> >
    <?php if ($contract): ?>
        CON<?= str_pad($contract['id_contrato'], 3, '0', STR_PAD_LEFT) ?> - <?= htmlspecialchars($contract['tipo_contrato']) ?>
    <?php else: ?>
        Contrato no encontrado
    <?php endif; ?>
</div>

<!-- Action Buttons -->
<div class="action-buttons">
    <a href="?module=contracts" class="btn btn-secondary">
        ← Volver a Lista
    </a>
    <?php if ($contract): ?>
        <a href="?module=contracts&action=edit&id=<?= $contractId ?>" class="btn btn-primary">
            Editar Contrato
        </a>
        <button type="button" class="btn btn-info" onclick="exportContractData(<?= $contractId ?>)">
            Exportar Datos
        </button>
        <button type="button" class="btn btn-success" onclick="printContract(<?= $contractId ?>)">
            Imprimir Contrato
        </button>
        <?php if ($contract['estado'] !== 'Finalizado' && $contract['estado'] !== 'Cancelado'): ?>
            <button type="button" class="btn btn-warning" onclick="confirmStatusChange(<?= $contractId ?>, '<?= $contract['estado'] ?>')">
                Cambiar Estado
            </button>
        <?php endif; ?>
        <button type="button" class="btn btn-danger" onclick="confirmDelete(<?= $contractId ?>)">
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
            <h3>Contrato No Encontrado</h3>
            <p>El contrato solicitado no existe o ha sido eliminado.</p>
            <a href="?module=contracts" class="btn btn-primary">Volver a Lista de Contratos</a>
        </div>
    </div>
<?php else: ?>

    <!-- Contract Header Card -->
    <div class="card contract-header status-<?= strtolower($contract['estado']) ?>">
        <div class="contract-info">
            <div class="contract-main">
                <h2>
                    Contrato CON<?= str_pad($contract['id_contrato'], 3, '0', STR_PAD_LEFT) ?>
                    <span class="contract-type-badge">
                        <?= htmlspecialchars($contract['tipo_contrato']) ?>
                    </span>
                </h2>
                <div class="contract-amount-display">
                    <?= formatCurrency($contract['valor_contrato']) ?>
                    <?php if ($contract['tipo_contrato'] === 'Arriendo'): ?>
                        <small>/mes</small>
                    <?php endif; ?>
                </div>
                <div class="contract-status-display">
                    <span class="status-badge status-<?= strtolower($contract['estado']) ?>">
                        <?= htmlspecialchars($contract['estado']) ?>
                    </span>
                </div>
            </div>
            <div class="contract-meta">
                <div class="meta-item">
                    <strong>Fecha de Inicio:</strong> <?= formatDate($contract['fecha_inicio']) ?>
                </div>
                <?php if ($contract['fecha_fin']): ?>
                    <div class="meta-item">
                        <strong>Fecha de Fin:</strong> <?= formatDate($contract['fecha_fin']) ?>
                    </div>
                <?php endif; ?>
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
                <div class="meta-item">
                    <strong>Registrado:</strong> <?= formatDate($contract['created_at']) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Details Grid -->
    <div class="details-grid">
        <!-- Property Information -->
        <div class="card detail-card">
            <h3>Inmueble</h3>
            <div class="detail-group">
                <div class="detail-item">
                    <label>ID Inmueble:</label>
                    <value>
                        <a href="?module=properties&action=view&id=<?= $contract['id_inmueble'] ?>">
                            INM<?= str_pad($contract['id_inmueble'], 3, '0', STR_PAD_LEFT) ?>
                        </a>
                    </value>
                </div>
                <div class="detail-item">
                    <label>Tipo:</label>
                    <value><?= htmlspecialchars($contract['tipo_inmueble']) ?></value>
                </div>
                <div class="detail-item">
                    <label>Dirección:</label>
                    <value><?= htmlspecialchars($contract['direccion']) ?></value>
                </div>
                <div class="detail-item">
                    <label>Ciudad:</label>
                    <value><?= htmlspecialchars($contract['ciudad']) ?></value>
                </div>
                <?php if ($contract['area_construida']): ?>
                    <div class="detail-item">
                        <label>Área Construida:</label>
                        <value><?= number_format($contract['area_construida'], 2) ?> m²</value>
                    </div>
                <?php endif; ?>
                <?php if ($contract['habitaciones']): ?>
                    <div class="detail-item">
                        <label>Habitaciones:</label>
                        <value><?= $contract['habitaciones'] ?></value>
                    </div>
                <?php endif; ?>
                <?php if ($contract['banos']): ?>
                    <div class="detail-item">
                        <label>Baños:</label>
                        <value><?= $contract['banos'] ?></value>
                    </div>
                <?php endif; ?>
                <div class="detail-item">
                    <label>Precio del Inmueble:</label>
                    <value><?= formatCurrency($contract['precio_inmueble']) ?></value>
                </div>
                <div class="detail-item">
                    <label>Estado Actual:</label>
                    <value>
                        <span class="status-badge status-<?= strtolower($contract['estado_inmueble']) ?>">
                            <?= htmlspecialchars($contract['estado_inmueble']) ?>
                        </span>
                    </value>
                </div>
            </div>
        </div>

        <!-- Client Information -->
        <div class="card detail-card">
            <h3>Cliente</h3>
            <div class="detail-group">
                <div class="detail-item">
                    <label>ID Cliente:</label>
                    <value>
                        <a href="?module=clients&action=view&id=<?= $contract['id_cliente'] ?>">
                            CLI<?= str_pad($contract['id_cliente'], 3, '0', STR_PAD_LEFT) ?>
                        </a>
                    </value>
                </div>
                <div class="detail-item">
                    <label>Nombre Completo:</label>
                    <value><?= htmlspecialchars($contract['cliente_nombre'] . ' ' . $contract['cliente_apellido']) ?></value>
                </div>
                <div class="detail-item">
                    <label>Tipo de Cliente:</label>
                    <value>
                        <span class="client-type <?= strtolower(str_replace(' ', '-', $contract['tipo_cliente'])) ?>">
                            <?= htmlspecialchars($contract['tipo_cliente']) ?>
                        </span>
                    </value>
                </div>
                <div class="detail-item">
                    <label>Documento:</label>
                    <value><?= htmlspecialchars($contract['tipo_documento'] . ' ' . $contract['nro_documento']) ?></value>
                </div>
                <div class="detail-item">
                    <label>Email:</label>
                    <value>
                        <a href="mailto:<?= htmlspecialchars($contract['cliente_correo']) ?>" class="email-link">
                            <?= htmlspecialchars($contract['cliente_correo']) ?>
                        </a>
                    </value>
                </div>
            </div>
        </div>

        <!-- Agent Information -->
        <div class="card detail-card">
            <h3>Agente Responsable</h3>
            <?php if ($contract['id_agente']): ?>
                <div class="detail-group">
                    <div class="detail-item">
                        <label>ID Agente:</label>
                        <value>
                            <a href="?module=agents&action=view&id=<?= $contract['id_agente'] ?>">
                                AGE<?= str_pad($contract['id_agente'], 3, '0', STR_PAD_LEFT) ?>
                            </a>
                        </value>
                    </div>
                    <div class="detail-item">
                        <label>Nombre:</label>
                        <value><?= htmlspecialchars($contract['agente_nombre']) ?></value>
                    </div>
                    <div class="detail-item">
                        <label>Email:</label>
                        <value>
                            <a href="mailto:<?= htmlspecialchars($contract['agente_correo']) ?>" class="email-link">
                                <?= htmlspecialchars($contract['agente_correo']) ?>
                            </a>
                        </value>
                    </div>
                    <div class="detail-item">
                        <label>Teléfono:</label>
                        <value><?= htmlspecialchars($contract['agente_telefono']) ?></value>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-muted">No se asignó agente a este contrato</p>
            <?php endif; ?>
        </div>

        <!-- Contract Summary -->
        <div class="card detail-card summary-card">
            <h3>Resumen del Contrato</h3>
            <div class="financial-summary">
                <div class="summary-row">
                    <span class="label">Tipo de Contrato:</span>
                    <span class="value contract-type-badge"><?= htmlspecialchars($contract['tipo_contrato']) ?></span>
                </div>
                <div class="summary-row">
                    <span class="label">Estado:</span>
                    <span class="value">
                        <span class="status-badge status-<?= strtolower($contract['estado']) ?>">
                            <?= htmlspecialchars($contract['estado']) ?>
                        </span>
                    </span>
                </div>
                <div class="summary-row highlight">
                    <span class="label">Valor del Contrato:</span>
                    <span class="value amount">
                        <?= formatCurrency($contract['valor_contrato']) ?>
                        <?php if ($contract['tipo_contrato'] === 'Arriendo'): ?>
                            <small>/mes</small>
                        <?php endif; ?>
                    </span>
                </div>
                <?php if ($contract['valor_contrato'] != $contract['precio_inmueble']): ?>
                    <div class="summary-row">
                        <span class="label">Precio del Inmueble:</span>
                        <span class="value"><?= formatCurrency($contract['precio_inmueble']) ?></span>
                    </div>
                    <div class="summary-row <?= $contract['valor_contrato'] > $contract['precio_inmueble'] ? 'positive' : 'negative' ?>">
                        <span class="label">Diferencia:</span>
                        <span class="value">
                            <?= formatCurrency(abs($contract['valor_contrato'] - $contract['precio_inmueble'])) ?>
                            (<?= $contract['valor_contrato'] > $contract['precio_inmueble'] ? '+' : '-' ?><?= number_format(abs((($contract['valor_contrato'] - $contract['precio_inmueble']) / $contract['precio_inmueble']) * 100), 2) ?>%)
                        </span>
                    </div>
                <?php endif; ?>
                <?php if ($contract['tipo_contrato'] === 'Arriendo' && $contract['fecha_inicio'] && $contract['fecha_fin']): ?>
                    <?php
                    $inicio = new DateTime($contract['fecha_inicio']);
                    $fin = new DateTime($contract['fecha_fin']);
                    $months = $inicio->diff($fin)->m + ($inicio->diff($fin)->y * 12);
                    if ($months > 0):
                    ?>
                        <div class="summary-row total">
                            <span class="label">Valor Total Estimado (<?= $months ?> meses):</span>
                            <span class="value"><?= formatCurrency($contract['valor_contrato'] * $months) ?></span>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Observations -->
    <?php if ($contract['observaciones']): ?>
        <div class="card">
            <h3>Observaciones</h3>
            <div class="observations-content">
                <?= nl2br(htmlspecialchars($contract['observaciones'])) ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Contract Document -->
    <?php if ($contract['archivo_contrato']): ?>
        <div class="card">
            <h3>Documento del Contrato</h3>
            <div class="document-info">
                <div class="document-icon">📄</div>
                <div class="document-details">
                    <strong><?= htmlspecialchars($contract['archivo_contrato']) ?></strong>
                    <p>Archivo adjunto al contrato</p>
                </div>
                <button type="button" class="btn btn-primary" onclick="downloadDocument('<?= htmlspecialchars($contract['archivo_contrato']) ?>')">
                    Descargar
                </button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Related Sale -->
    <?php if ($relatedSale): ?>
        <div class="card">
            <h3>Venta Relacionada</h3>
            <div class="related-info">
                <div class="related-content">
                    <strong>VEN<?= str_pad($relatedSale['id_venta'], 3, '0', STR_PAD_LEFT) ?></strong>
                    -
                    Fecha: <?= formatDate($relatedSale['fecha_venta']) ?>
                    -
                    Valor: <?= formatCurrency($relatedSale['valor']) ?>
                    <?php if ($relatedSale['comision']): ?>
                        -
                        Comisión: <?= formatCurrency($relatedSale['comision']) ?>
                    <?php endif; ?>
                </div>
                <a href="?module=sales&action=view&id=<?= $relatedSale['id_venta'] ?>" class="btn btn-sm btn-info">
                    Ver Venta
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Timeline -->
    <div class="card">
        <h3>Línea de Tiempo</h3>
        <div class="timeline">
            <div class="timeline-item">
                <div class="timeline-marker"></div>
                <div class="timeline-content">
                    <div class="timeline-date"><?= formatDate($contract['created_at']) ?></div>
                    <div class="timeline-title">Contrato Registrado</div>
                    <div class="timeline-description">El contrato fue registrado en el sistema</div>
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-marker <?= $contract['estado'] === 'Activo' ? 'active' : '' ?>"></div>
                <div class="timeline-content">
                    <div class="timeline-date"><?= formatDate($contract['fecha_inicio']) ?></div>
                    <div class="timeline-title">Inicio del Contrato</div>
                    <div class="timeline-description">
                        Contrato de <?= strtolower($contract['tipo_contrato']) ?> por <?= formatCurrency($contract['valor_contrato']) ?>
                    </div>
                </div>
            </div>
            <?php if ($contract['fecha_fin']): ?>
                <div class="timeline-item">
                    <div class="timeline-marker <?= $contract['estado'] === 'Finalizado' ? 'completed' : '' ?>"></div>
                    <div class="timeline-content">
                        <div class="timeline-date"><?= formatDate($contract['fecha_fin']) ?></div>
                        <div class="timeline-title">
                            <?= $contract['estado'] === 'Finalizado' ? 'Contrato Finalizado' : 'Fecha de Finalización' ?>
                        </div>
                        <div class="timeline-description">
                            <?= $contract['estado'] === 'Finalizado' ? 'El contrato ha sido completado' : 'Fecha programada para finalización' ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($relatedSale): ?>
                <div class="timeline-item">
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <div class="timeline-date"><?= formatDate($relatedSale['fecha_venta']) ?></div>
                        <div class="timeline-title">Venta Completada</div>
                        <div class="timeline-description">Se completó la venta por <?= formatCurrency($relatedSale['valor']) ?></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php endif; ?>

<!-- JavaScript for Enhanced Functionality -->
<script>
/**
 * Contract detail view functionality
 */

function confirmDelete(contractId) {
    if (confirm('¿Está seguro de que desea eliminar este contrato?\n\nEsta acción no se puede deshacer y puede afectar registros relacionados.')) {
        deleteContract(contractId);
    }
}

async function deleteContract(contractId) {
    try {
        const response = await Ajax.contracts.delete(contractId);

        if (response.success) {
            App.showSuccessMessage('Contrato eliminado correctamente');
            window.location.href = '?module=contracts';
        } else {
            throw new Error(response.error || 'Error desconocido');
        }
    } catch (error) {
        console.error('Error:', error);
        App.showErrorMessage('Error al eliminar el contrato: ' + error.message);
    }
}

function confirmStatusChange(contractId, currentStatus) {
    const statusOptions = {
        'Borrador': ['Activo', 'Cancelado'],
        'Activo': ['Finalizado', 'Cancelado'],
        'Finalizado': [],
        'Cancelado': []
    };

    const availableStatuses = statusOptions[currentStatus] || [];

    if (availableStatuses.length === 0) {
        alert('No hay cambios de estado disponibles para un contrato ' + currentStatus);
        return;
    }

    const modalContent = `
        <div class="status-change-modal">
            <h4>Cambiar Estado del Contrato</h4>
            <p>Estado actual: <strong>${currentStatus}</strong></p>
            <div class="status-options">
                ${availableStatuses.map(status => `
                    <button type="button" class="btn btn-primary status-option"
                            onclick="changeContractStatus(${contractId}, '${status}')">
                        ${status}
                    </button>
                `).join('')}
            </div>
        </div>
    `;

    const footer = `
        <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
    `;

    App.openModal(modalContent, 'Cambiar Estado del Contrato', footer);
}

async function changeContractStatus(contractId, newStatus) {
    try {
        const response = await Ajax.contracts.updateStatus(contractId, newStatus);

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

function exportContractData(contractId) {
    const exportOptions = `
        <div class="export-options">
            <h4>Seleccione el formato de exportación:</h4>
            <div class="export-buttons">
                <button type="button" class="btn btn-primary" onclick="exportToFormat('pdf', ${contractId})">
                    📄 PDF - Contrato Completo
                </button>
                <button type="button" class="btn btn-primary" onclick="exportToFormat('excel', ${contractId})">
                    📊 Excel - Datos Completos
                </button>
                <button type="button" class="btn btn-primary" onclick="exportToFormat('summary', ${contractId})">
                    📋 Resumen del Contrato
                </button>
            </div>
        </div>
    `;

    const footer = `
        <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
    `;

    App.openModal(exportOptions, 'Exportar Datos del Contrato', footer);
}

function exportToFormat(format, contractId) {
    const params = new URLSearchParams({
        module: 'contracts',
        action: 'export',
        id: contractId,
        format: format
    });

    window.open('?' + params.toString(), '_blank');
    App.closeModal();
}

function printContract(contractId) {
    window.open(`?module=contracts&action=print&id=${contractId}`, '_blank');
}

function downloadDocument(fileName) {
    window.open(`uploads/contracts/${fileName}`, '_blank');
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
/* Additional styles specific to contract detail view */
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

.contract-header {
    color: white;
    margin-bottom: var(--spacing-lg);
}

.contract-header.status-borrador {
    background: linear-gradient(135deg, #6c757d, #5a6268);
}

.contract-header.status-activo {
    background: linear-gradient(135deg, #28a745, #20c997);
}

.contract-header.status-finalizado {
    background: linear-gradient(135deg, #17a2b8, #138496);
}

.contract-header.status-cancelado {
    background: linear-gradient(135deg, #dc3545, #c82333);
}

.contract-info {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: var(--spacing-lg);
}

.contract-main h2 {
    color: white;
    margin-bottom: var(--spacing-sm);
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    flex-wrap: wrap;
}

.contract-type-badge {
    font-size: var(--font-size-md);
    background: rgba(255, 255, 255, 0.2);
    padding: 6px 12px;
    border-radius: var(--border-radius);
}

.contract-amount-display {
    font-size: 2.5rem;
    font-weight: 700;
    background: rgba(255, 255, 255, 0.2);
    padding: 12px 24px;
    border-radius: var(--border-radius);
    display: inline-block;
    margin-bottom: var(--spacing-sm);
}

.contract-amount-display small {
    font-size: 1rem;
}

.contract-status-display {
    margin-top: var(--spacing-sm);
}

.contract-meta {
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

.status-borrador { background: #6c757d; color: white; }
.status-activo { background: #28a745; color: white; }
.status-finalizado { background: #17a2b8; color: white; }
.status-cancelado { background: #dc3545; color: white; }
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

.summary-row.positive .value {
    color: #28a745;
}

.summary-row.negative .value {
    color: #dc3545;
}

.contract-type-badge {
    background: var(--bg-secondary);
    color: var(--text-primary);
    padding: 4px 8px;
    border-radius: var(--border-radius);
    font-size: var(--font-size-sm);
}

.observations-content {
    padding: var(--spacing-md);
    background: var(--bg-secondary);
    border-left: 4px solid var(--secondary-color);
    border-radius: var(--border-radius);
    line-height: 1.6;
}

.document-info {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    padding: var(--spacing-md);
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
}

.document-icon {
    font-size: 3rem;
}

.document-details {
    flex: 1;
}

.document-details strong {
    display: block;
    margin-bottom: 4px;
}

.document-details p {
    color: var(--text-secondary);
    font-size: var(--font-size-sm);
    margin: 0;
}

.related-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--spacing-md);
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
    flex-wrap: wrap;
    gap: var(--spacing-sm);
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

/* Responsive adjustments */
@media (max-width: 768px) {
    .details-grid {
        grid-template-columns: 1fr;
    }

    .contract-info {
        flex-direction: column;
        gap: var(--spacing-md);
    }

    .contract-meta {
        text-align: left;
    }

    .contract-amount-display {
        font-size: 1.8rem;
    }

    .export-buttons {
        flex-direction: column;
    }

    .related-info {
        flex-direction: column;
        align-items: flex-start;
    }

    .document-info {
        flex-direction: column;
        text-align: center;
    }
}
</style>