<?php
/**
 * Sale Detail View - Real Estate Management System
 * Display detailed information about a specific sale
 * Educational PHP/MySQL Project
 */

// Prevent direct access
if (!defined('APP_ACCESS')) {
    die('Direct access not permitted');
}

$error = '';
$sale = null;
$saleId = (int)($_GET['id'] ?? 0);

// Additional data for detailed view
$propertyDetails = null;
$clientDetails = null;
$agentDetails = null;
$relatedContract = null;

// Validate sale ID and load data
if ($saleId <= 0) {
    $error = "ID de venta inválido";
} else {
    try {
        $db = new Database();
        $pdo = $db->getConnection();

        // Load sale data with all related information
        $stmt = $pdo->prepare("
            SELECT v.*,
                   i.tipo_inmueble, i.direccion, i.ciudad, i.precio as precio_inmueble,
                   i.area_construida, i.habitaciones, i.banos, i.estado as estado_inmueble,
                   c.nombre as cliente_nombre, c.apellido as cliente_apellido,
                   c.correo as cliente_correo, c.tipo_cliente, c.tipo_documento, c.nro_documento,
                   a.nombre as agente_nombre, a.correo as agente_correo, a.telefono as agente_telefono
            FROM venta v
            LEFT JOIN inmueble i ON v.id_inmueble = i.id_inmueble
            LEFT JOIN cliente c ON v.id_cliente = c.id_cliente
            LEFT JOIN agente a ON v.id_agente = a.id_agente
            WHERE v.id_venta = ?
        ");
        $stmt->execute([$saleId]);
        $sale = $stmt->fetch();

        if (!$sale) {
            $error = "Venta no encontrada";
        } else {
            // Get related contract if exists
            $stmt = $pdo->prepare("
                SELECT * FROM contrato
                WHERE id_inmueble = ? AND id_cliente = ? AND tipo_contrato = 'Venta'
                ORDER BY fecha_inicio DESC
                LIMIT 1
            ");
            $stmt->execute([$sale['id_inmueble'], $sale['id_cliente']]);
            $relatedContract = $stmt->fetch();
        }

    } catch (PDOException $e) {
        error_log("Error loading sale details: " . $e->getMessage());
        $error = "Error al cargar los datos de la venta";
    }
}
?>

<div class="module-header">
    <h2>Detalles de la Venta</h2>
    <p class="module-description">
        Información completa de la transacción de venta.
    </p>
</div>

<!-- Navigation Breadcrumb -->
<div class="breadcrumb">
    <a href="?module=sales">Ventas</a> >
    <?php if ($sale): ?>
        VEN<?= str_pad($sale['id_venta'], 3, '0', STR_PAD_LEFT) ?> - <?= formatCurrency($sale['valor']) ?>
    <?php else: ?>
        Venta no encontrada
    <?php endif; ?>
</div>

<!-- Action Buttons -->
<div class="action-buttons">
    <a href="?module=sales" class="btn btn-secondary">
        ← Volver a Lista
    </a>
    <?php if ($sale): ?>
        <a href="?module=sales&action=edit&id=<?= $saleId ?>" class="btn btn-primary">
            Editar Venta
        </a>
        <button type="button" class="btn btn-info" onclick="exportSaleData(<?= $saleId ?>)">
            Exportar Datos
        </button>
        <button type="button" class="btn btn-success" onclick="printSaleReceipt(<?= $saleId ?>)">
            Imprimir Recibo
        </button>
        <button type="button" class="btn btn-danger" onclick="confirmDelete(<?= $saleId ?>)">
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
            <h3>Venta No Encontrada</h3>
            <p>La venta solicitada no existe o ha sido eliminada.</p>
            <a href="?module=sales" class="btn btn-primary">Volver a Lista de Ventas</a>
        </div>
    </div>
<?php else: ?>

    <!-- Sale Header Card -->
    <div class="card sale-header">
        <div class="sale-info">
            <div class="sale-main">
                <h2>
                    Venta VEN<?= str_pad($sale['id_venta'], 3, '0', STR_PAD_LEFT) ?>
                </h2>
                <div class="sale-amount-display">
                    <?= formatCurrency($sale['valor']) ?>
                </div>
            </div>
            <div class="sale-meta">
                <div class="meta-item">
                    <strong>Fecha de Venta:</strong> <?= formatDate($sale['fecha_venta']) ?>
                </div>
                <div class="meta-item">
                    <strong>Registrado:</strong> <?= formatDate($sale['created_at']) ?>
                </div>
                <?php if ($sale['comision']): ?>
                    <div class="meta-item">
                        <strong>Comisión:</strong> <?= formatCurrency($sale['comision']) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Details Grid -->
    <div class="details-grid">
        <!-- Property Information -->
        <div class="card detail-card">
            <h3>Inmueble Vendido</h3>
            <div class="detail-group">
                <div class="detail-item">
                    <label>ID Inmueble:</label>
                    <value>
                        <a href="?module=properties&action=view&id=<?= $sale['id_inmueble'] ?>">
                            <span class="property-id">INM<?= str_pad($sale['id_inmueble'], 3, '0', STR_PAD_LEFT) ?></span>
                        </a>
                    </value>
                </div>
                <div class="detail-item">
                    <label>Tipo:</label>
                    <value><?= htmlspecialchars($sale['tipo_inmueble']) ?></value>
                </div>
                <div class="detail-item">
                    <label>Dirección:</label>
                    <value><?= htmlspecialchars($sale['direccion']) ?></value>
                </div>
                <div class="detail-item">
                    <label>Ciudad:</label>
                    <value><?= htmlspecialchars($sale['ciudad']) ?></value>
                </div>
                <?php if ($sale['area_construida']): ?>
                    <div class="detail-item">
                        <label>Área Construida:</label>
                        <value><?= number_format($sale['area_construida'], 2) ?> m²</value>
                    </div>
                <?php endif; ?>
                <?php if ($sale['habitaciones']): ?>
                    <div class="detail-item">
                        <label>Habitaciones:</label>
                        <value><?= $sale['habitaciones'] ?></value>
                    </div>
                <?php endif; ?>
                <div class="detail-item">
                    <label>Precio Original:</label>
                    <value><?= formatCurrency($sale['precio_inmueble']) ?></value>
                </div>
                <div class="detail-item">
                    <label>Estado Actual:</label>
                    <value>
                        <span class="status-badge status-<?= strtolower($sale['estado_inmueble']) ?>">
                            <?= htmlspecialchars($sale['estado_inmueble']) ?>
                        </span>
                    </value>
                </div>
            </div>
        </div>

        <!-- Client Information -->
        <div class="card detail-card">
            <h3>Cliente Comprador</h3>
            <div class="detail-group">
                <div class="detail-item">
                    <label>ID Cliente:</label>
                    <value>
                        <a href="?module=clients&action=view&id=<?= $sale['id_cliente'] ?>">
                            CLI<?= str_pad($sale['id_cliente'], 3, '0', STR_PAD_LEFT) ?>
                        </a>
                    </value>
                </div>
                <div class="detail-item">
                    <label>Nombre Completo:</label>
                    <value><?= htmlspecialchars($sale['cliente_nombre'] . ' ' . $sale['cliente_apellido']) ?></value>
                </div>
                <div class="detail-item">
                    <label>Tipo de Cliente:</label>
                    <value>
                        <span class="client-type <?= strtolower(str_replace(' ', '-', $sale['tipo_cliente'])) ?>">
                            <?= htmlspecialchars($sale['tipo_cliente']) ?>
                        </span>
                    </value>
                </div>
                <div class="detail-item">
                    <label>Documento:</label>
                    <value><?= htmlspecialchars($sale['tipo_documento'] . ' ' . $sale['nro_documento']) ?></value>
                </div>
                <div class="detail-item">
                    <label>Email:</label>
                    <value>
                        <a href="mailto:<?= htmlspecialchars($sale['cliente_correo']) ?>" class="email-link">
                            <?= htmlspecialchars($sale['cliente_correo']) ?>
                        </a>
                    </value>
                </div>
            </div>
        </div>

        <!-- Agent Information -->
        <div class="card detail-card">
            <h3>Agente Responsable</h3>
            <?php if ($sale['id_agente']): ?>
                <div class="detail-group">
                    <div class="detail-item">
                        <label>ID Agente:</label>
                        <value>
                            <a href="?module=agents&action=view&id=<?= $sale['id_agente'] ?>">
                                AGE<?= str_pad($sale['id_agente'], 3, '0', STR_PAD_LEFT) ?>
                            </a>
                        </value>
                    </div>
                    <div class="detail-item">
                        <label>Nombre:</label>
                        <value><?= htmlspecialchars($sale['agente_nombre']) ?></value>
                    </div>
                    <div class="detail-item">
                        <label>Email:</label>
                        <value>
                            <a href="mailto:<?= htmlspecialchars($sale['agente_correo']) ?>" class="email-link">
                                <?= htmlspecialchars($sale['agente_correo']) ?>
                            </a>
                        </value>
                    </div>
                    <div class="detail-item">
                        <label>Teléfono:</label>
                        <value><?= htmlspecialchars($sale['agente_telefono']) ?></value>
                    </div>
                    <?php if ($sale['comision']): ?>
                        <div class="detail-item">
                            <label>Comisión Pagada:</label>
                            <value class="amount"><?= formatCurrency($sale['comision']) ?></value>
                        </div>
                        <div class="detail-item">
                            <label>% sobre venta:</label>
                            <value><?= number_format(($sale['comision'] / $sale['valor']) * 100, 2) ?>%</value>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p class="text-muted">No se asignó agente a esta venta</p>
            <?php endif; ?>
        </div>

        <!-- Sale Summary -->
        <div class="card detail-card summary-card">
            <h3>Resumen Financiero</h3>
            <div class="financial-summary">
                <div class="summary-row">
                    <span class="label">Precio del Inmueble:</span>
                    <span class="value"><?= formatCurrency($sale['precio_inmueble']) ?></span>
                </div>
                <div class="summary-row">
                    <span class="label">Valor de Venta:</span>
                    <span class="value"><?= formatCurrency($sale['valor']) ?></span>
                </div>
                <?php if ($sale['valor'] != $sale['precio_inmueble']): ?>
                    <div class="summary-row <?= $sale['valor'] > $sale['precio_inmueble'] ? 'positive' : 'negative' ?>">
                        <span class="label">Diferencia:</span>
                        <span class="value">
                            <?= formatCurrency(abs($sale['valor'] - $sale['precio_inmueble'])) ?>
                            (<?= $sale['valor'] > $sale['precio_inmueble'] ? '+' : '-' ?><?= number_format(abs((($sale['valor'] - $sale['precio_inmueble']) / $sale['precio_inmueble']) * 100), 2) ?>%)
                        </span>
                    </div>
                <?php endif; ?>
                <?php if ($sale['comision']): ?>
                    <div class="summary-row highlight">
                        <span class="label">Comisión del Agente:</span>
                        <span class="value"><?= formatCurrency($sale['comision']) ?></span>
                    </div>
                    <div class="summary-row total">
                        <span class="label">Neto para el Vendedor:</span>
                        <span class="value"><?= formatCurrency($sale['valor'] - $sale['comision']) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Observations -->
    <?php if ($sale['observaciones']): ?>
        <div class="card">
            <h3>Observaciones</h3>
            <div class="observations-content">
                <?= nl2br(htmlspecialchars($sale['observaciones'])) ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Related Contract -->
    <?php if ($relatedContract): ?>
        <div class="card">
            <h3>Contrato Relacionado</h3>
            <div class="related-contract">
                <div class="contract-info">
                    <strong>CON<?= str_pad($relatedContract['id_contrato'], 3, '0', STR_PAD_LEFT) ?></strong>
                    -
                    Estado: <span class="status-badge status-<?= strtolower($relatedContract['estado']) ?>">
                        <?= htmlspecialchars($relatedContract['estado']) ?>
                    </span>
                    -
                    Valor: <?= formatCurrency($relatedContract['valor_contrato']) ?>
                </div>
                <a href="?module=contracts&action=view&id=<?= $relatedContract['id_contrato'] ?>" class="btn btn-sm btn-info">
                    Ver Contrato
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
                    <div class="timeline-date"><?= formatDate($sale['created_at']) ?></div>
                    <div class="timeline-title">Venta Registrada</div>
                    <div class="timeline-description">La venta fue registrada en el sistema</div>
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-marker active"></div>
                <div class="timeline-content">
                    <div class="timeline-date"><?= formatDate($sale['fecha_venta']) ?></div>
                    <div class="timeline-title">Venta Completada</div>
                    <div class="timeline-description">
                        Se completó la transacción por un valor de <?= formatCurrency($sale['valor']) ?>
                    </div>
                </div>
            </div>
            <?php if ($relatedContract): ?>
                <div class="timeline-item">
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <div class="timeline-date"><?= formatDate($relatedContract['fecha_inicio']) ?></div>
                        <div class="timeline-title">Contrato Firmado</div>
                        <div class="timeline-description">Se firmó el contrato de venta</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php endif; ?>

<!-- JavaScript for Enhanced Functionality -->
<script>
/**
 * Sale detail view functionality
 */

function confirmDelete(saleId) {
    if (confirm('¿Está seguro de que desea eliminar esta venta?\n\nEsta acción no se puede deshacer y puede afectar registros relacionados.')) {
        deleteSale(saleId);
    }
}

async function deleteSale(saleId) {
    try {
        const response = await Ajax.sales.delete(saleId);

        if (response.success) {
            App.showSuccessMessage('Venta eliminada correctamente');
            window.location.href = '?module=sales';
        } else {
            throw new Error(response.error || 'Error desconocido');
        }
    } catch (error) {
        console.error('Error:', error);
        App.showErrorMessage('Error al eliminar la venta: ' + error.message);
    }
}

function exportSaleData(saleId) {
    const exportOptions = `
        <div class="export-options">
            <h4>Seleccione el formato de exportación:</h4>
            <div class="export-buttons">
                <button type="button" class="btn btn-primary" onclick="exportToFormat('pdf', ${saleId})">
                    📄 PDF - Recibo de Venta
                </button>
                <button type="button" class="btn btn-primary" onclick="exportToFormat('excel', ${saleId})">
                    📊 Excel - Datos Completos
                </button>
                <button type="button" class="btn btn-primary" onclick="exportToFormat('invoice', ${saleId})">
                    🧾 Factura de Venta
                </button>
            </div>
        </div>
    `;

    const footer = `
        <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
    `;

    App.openModal(exportOptions, 'Exportar Datos de la Venta', footer);
}

function exportToFormat(format, saleId) {
    const params = new URLSearchParams({
        module: 'sales',
        action: 'export',
        id: saleId,
        format: format
    });

    window.open('?' + params.toString(), '_blank');
    App.closeModal();
}

function printSaleReceipt(saleId) {
    window.open(`?module=sales&action=print&id=${saleId}`, '_blank');
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
/* Additional styles specific to sale detail view */
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

.sale-header {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    margin-bottom: var(--spacing-lg);
}

.sale-info {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: var(--spacing-lg);
}

.sale-main h2 {
    color: white;
    margin-bottom: var(--spacing-sm);
}

.sale-amount-display {
    font-size: 2.5rem;
    font-weight: 700;
    background: rgba(255, 255, 255, 0.2);
    padding: 12px 24px;
    border-radius: var(--border-radius);
    display: inline-block;
}

.sale-meta {
    text-align: right;
    opacity: 0.9;
}

.meta-item {
    margin-bottom: var(--spacing-xs);
    font-size: var(--font-size-sm);
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

.status-vendido { background: #f8d7da; color: #721c24; }
.status-disponible { background: #d4edda; color: #155724; }
.status-arrendado { background: #fff3cd; color: #856404; }
.status-activo { background: #d4edda; color: #155724; }
.status-finalizado { background: #e2e3e5; color: #383d41; }

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

.observations-content {
    padding: var(--spacing-md);
    background: var(--bg-secondary);
    border-left: 4px solid var(--secondary-color);
    border-radius: var(--border-radius);
    line-height: 1.6;
}

.related-contract {
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

/* Responsive adjustments */
@media (max-width: 768px) {
    .details-grid {
        grid-template-columns: 1fr;
    }

    .sale-info {
        flex-direction: column;
        gap: var(--spacing-md);
    }

    .sale-meta {
        text-align: left;
    }

    .sale-amount-display {
        font-size: 1.8rem;
    }

    .export-buttons {
        flex-direction: column;
    }

    .related-contract {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>