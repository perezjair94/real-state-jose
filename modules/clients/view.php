<?php
/**
 * Client Detail View - Real Estate Management System
 * Display detailed information about a specific client
 * Educational PHP/MySQL Project
 */

// Prevent direct access
if (!defined('APP_ACCESS')) {
    die('Direct access not permitted');
}

$error = '';
$client = null;
$clientId = (int)($_GET['id'] ?? 0);

// Additional data for detailed view
$relatedData = [
    'sales' => [],
    'contracts' => [],
    'rentals' => [],
    'visits' => []
];

// Validate client ID and load data
if ($clientId <= 0) {
    $error = "ID de cliente inválido";
} else {
    try {
        $db = new Database();
        $pdo = $db->getConnection();

        // Load client data
        $stmt = $pdo->prepare("SELECT * FROM cliente WHERE id_cliente = ?");
        $stmt->execute([$clientId]);
        $client = $stmt->fetch();

        if (!$client) {
            $error = "Cliente no encontrado";
        } else {
            // Load related sales
            $stmt = $pdo->prepare("
                SELECT v.*, i.direccion as propiedad_direccion, i.tipo_inmueble, a.nombre as agente_nombre
                FROM venta v
                LEFT JOIN inmueble i ON v.id_inmueble = i.id_inmueble
                LEFT JOIN agente a ON v.id_agente = a.id_agente
                WHERE v.id_cliente = ?
                ORDER BY v.fecha_venta DESC
            ");
            $stmt->execute([$clientId]);
            $relatedData['sales'] = $stmt->fetchAll();

            // Load related contracts
            $stmt = $pdo->prepare("
                SELECT c.*, i.direccion as propiedad_direccion, i.tipo_inmueble, a.nombre as agente_nombre
                FROM contrato c
                LEFT JOIN inmueble i ON c.id_inmueble = i.id_inmueble
                LEFT JOIN agente a ON c.id_agente = a.id_agente
                WHERE c.id_cliente = ?
                ORDER BY c.fecha_inicio DESC
            ");
            $stmt->execute([$clientId]);
            $relatedData['contracts'] = $stmt->fetchAll();

            // Load related rentals
            $stmt = $pdo->prepare("
                SELECT a.*, i.direccion as propiedad_direccion, i.tipo_inmueble, ag.nombre as agente_nombre
                FROM arriendo a
                LEFT JOIN inmueble i ON a.id_inmueble = i.id_inmueble
                LEFT JOIN agente ag ON a.id_agente = ag.id_agente
                WHERE a.id_cliente = ?
                ORDER BY a.fecha_inicio DESC
            ");
            $stmt->execute([$clientId]);
            $relatedData['rentals'] = $stmt->fetchAll();

            // Load related visits
            $stmt = $pdo->prepare("
                SELECT v.*, i.direccion as propiedad_direccion, i.tipo_inmueble, a.nombre as agente_nombre
                FROM visita v
                LEFT JOIN inmueble i ON v.id_inmueble = i.id_inmueble
                LEFT JOIN agente a ON v.id_agente = a.id_agente
                WHERE v.id_cliente = ?
                ORDER BY v.fecha_visita DESC, v.hora_visita DESC
            ");
            $stmt->execute([$clientId]);
            $relatedData['visits'] = $stmt->fetchAll();
        }

    } catch (PDOException $e) {
        error_log("Error loading client details: " . $e->getMessage());
        $error = "Error al cargar los datos del cliente";
    }
}
?>

<div class="module-header">
    <h2>Detalles del Cliente</h2>
    <p class="module-description">
        Información completa y transacciones relacionadas con el cliente.
    </p>
</div>

<!-- Navigation Breadcrumb -->
<div class="breadcrumb">
    <a href="?module=clients">Clientes</a> >
    <?php if ($client): ?>
        CLI<?= str_pad($client['id_cliente'], 3, '0', STR_PAD_LEFT) ?> - <?= htmlspecialchars($client['nombre'] . ' ' . $client['apellido']) ?>
    <?php else: ?>
        Cliente no encontrado
    <?php endif; ?>
</div>

<!-- Action Buttons -->
<div class="action-buttons">
    <a href="?module=clients" class="btn btn-secondary">
        ← Volver a Lista
    </a>
    <?php if ($client): ?>
        <a href="?module=clients&action=edit&id=<?= $clientId ?>" class="btn btn-primary">
            Editar Cliente
        </a>
        <button type="button" class="btn btn-info" onclick="exportClientData(<?= $clientId ?>)">
            Exportar Datos
        </button>
        <button type="button" class="btn btn-warning" onclick="showContactModal(<?= $clientId ?>)">
            Contactar
        </button>
        <button type="button" class="btn btn-danger" onclick="confirmDelete(<?= $clientId ?>)">
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
            <h3>Cliente No Encontrado</h3>
            <p>El cliente solicitado no existe o ha sido eliminado.</p>
            <a href="?module=clients" class="btn btn-primary">Volver a Lista de Clientes</a>
        </div>
    </div>
<?php else: ?>

    <!-- Client Header Card -->
    <div class="card client-header">
        <div class="client-info">
            <div class="client-main">
                <h2>
                    <?= htmlspecialchars($client['nombre'] . ' ' . $client['apellido']) ?>
                    <span class="client-id">CLI<?= str_pad($client['id_cliente'], 3, '0', STR_PAD_LEFT) ?></span>
                </h2>
                <div class="client-type-badge">
                    <span class="client-type <?= strtolower(str_replace(' ', '-', $client['tipo_cliente'])) ?>">
                        <?= htmlspecialchars($client['tipo_cliente']) ?>
                    </span>
                </div>
            </div>
            <div class="client-meta">
                <div class="meta-item">
                    <strong>Registrado:</strong> <?= formatDate($client['created_at']) ?>
                </div>
                <?php if ($client['updated_at'] !== $client['created_at']): ?>
                    <div class="meta-item">
                        <strong>Actualizado:</strong> <?= formatDate($client['updated_at']) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Client Details Grid -->
    <div class="details-grid">
        <!-- Personal Information -->
        <div class="card detail-card">
            <h3>Información Personal</h3>
            <div class="detail-group">
                <div class="detail-item">
                    <label>Nombre Completo:</label>
                    <value><?= htmlspecialchars($client['nombre'] . ' ' . $client['apellido']) ?></value>
                </div>
                <div class="detail-item">
                    <label>Tipo de Cliente:</label>
                    <value>
                        <span class="client-type <?= strtolower(str_replace(' ', '-', $client['tipo_cliente'])) ?>">
                            <?= htmlspecialchars($client['tipo_cliente']) ?>
                        </span>
                    </value>
                </div>
            </div>
        </div>

        <!-- Identification -->
        <div class="card detail-card">
            <h3>Identificación</h3>
            <div class="detail-group">
                <div class="detail-item">
                    <label>Tipo de Documento:</label>
                    <value><?= htmlspecialchars(DOCUMENT_TYPES[$client['tipo_documento']] ?? $client['tipo_documento']) ?></value>
                </div>
                <div class="detail-item">
                    <label>Número de Documento:</label>
                    <value class="document-number"><?= htmlspecialchars($client['nro_documento']) ?></value>
                </div>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="card detail-card">
            <h3>Información de Contacto</h3>
            <div class="detail-group">
                <div class="detail-item">
                    <label>Email:</label>
                    <value>
                        <a href="mailto:<?= htmlspecialchars($client['correo']) ?>" class="email-link">
                            <?= htmlspecialchars($client['correo']) ?>
                        </a>
                    </value>
                </div>
                <div class="detail-item">
                    <label>Dirección:</label>
                    <value class="address">
                        <?= $client['direccion'] ? htmlspecialchars($client['direccion']) : '<span class="text-muted">No especificada</span>' ?>
                    </value>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="card detail-card stats-card">
            <h3>Resumen de Actividad</h3>
            <div class="stats-grid-small">
                <div class="stat-item">
                    <div class="stat-number"><?= count($relatedData['sales']) ?></div>
                    <div class="stat-label">Ventas</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= count($relatedData['contracts']) ?></div>
                    <div class="stat-label">Contratos</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= count($relatedData['rentals']) ?></div>
                    <div class="stat-label">Arriendos</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= count($relatedData['visits']) ?></div>
                    <div class="stat-label">Visitas</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Information Tabs -->
    <div class="related-info">
        <div class="info-tabs">
            <button class="tab-button active" onclick="showTab('sales')">
                Ventas (<?= count($relatedData['sales']) ?>)
            </button>
            <button class="tab-button" onclick="showTab('contracts')">
                Contratos (<?= count($relatedData['contracts']) ?>)
            </button>
            <button class="tab-button" onclick="showTab('rentals')">
                Arriendos (<?= count($relatedData['rentals']) ?>)
            </button>
            <button class="tab-button" onclick="showTab('visits')">
                Visitas (<?= count($relatedData['visits']) ?>)
            </button>
        </div>

        <!-- Sales Tab -->
        <div id="sales-tab" class="tab-content active">
            <div class="card">
                <h4>Ventas Realizadas</h4>
                <?php if (!empty($relatedData['sales'])): ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID Venta</th>
                                    <th>Fecha</th>
                                    <th>Propiedad</th>
                                    <th>Valor</th>
                                    <th>Comisión</th>
                                    <th>Agente</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($relatedData['sales'] as $sale): ?>
                                    <tr>
                                        <td><strong>VEN<?= str_pad($sale['id_venta'], 3, '0', STR_PAD_LEFT) ?></strong></td>
                                        <td><?= formatDate($sale['fecha_venta']) ?></td>
                                        <td>
                                            <div class="property-info">
                                                <strong><?= htmlspecialchars($sale['tipo_inmueble']) ?></strong>
                                                <small><?= htmlspecialchars($sale['propiedad_direccion']) ?></small>
                                            </div>
                                        </td>
                                        <td class="amount"><?= formatCurrency($sale['valor']) ?></td>
                                        <td class="amount"><?= $sale['comision'] ? formatCurrency($sale['comision']) : '-' ?></td>
                                        <td><?= htmlspecialchars($sale['agente_nombre'] ?: 'Sin agente') ?></td>
                                        <td>
                                            <a href="?module=sales&action=view&id=<?= $sale['id_venta'] ?>" class="btn btn-sm btn-info">Ver</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No hay ventas registradas para este cliente.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Contracts Tab -->
        <div id="contracts-tab" class="tab-content">
            <div class="card">
                <h4>Contratos</h4>
                <?php if (!empty($relatedData['contracts'])): ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID Contrato</th>
                                    <th>Tipo</th>
                                    <th>Propiedad</th>
                                    <th>Valor</th>
                                    <th>Estado</th>
                                    <th>Vigencia</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($relatedData['contracts'] as $contract): ?>
                                    <tr>
                                        <td><strong>CON<?= str_pad($contract['id_contrato'], 3, '0', STR_PAD_LEFT) ?></strong></td>
                                        <td><?= htmlspecialchars($contract['tipo_contrato']) ?></td>
                                        <td>
                                            <div class="property-info">
                                                <strong><?= htmlspecialchars($contract['tipo_inmueble']) ?></strong>
                                                <small><?= htmlspecialchars($contract['propiedad_direccion']) ?></small>
                                            </div>
                                        </td>
                                        <td class="amount"><?= formatCurrency($contract['valor_contrato']) ?></td>
                                        <td>
                                            <span class="status-badge status-<?= strtolower($contract['estado']) ?>">
                                                <?= htmlspecialchars($contract['estado']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?= formatDate($contract['fecha_inicio']) ?>
                                            <?php if ($contract['fecha_fin']): ?>
                                                - <?= formatDate($contract['fecha_fin']) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="?module=contracts&action=view&id=<?= $contract['id_contrato'] ?>" class="btn btn-sm btn-info">Ver</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No hay contratos registrados para este cliente.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Rentals Tab -->
        <div id="rentals-tab" class="tab-content">
            <div class="card">
                <h4>Arriendos</h4>
                <?php if (!empty($relatedData['rentals'])): ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID Arriendo</th>
                                    <th>Propiedad</th>
                                    <th>Canon Mensual</th>
                                    <th>Estado</th>
                                    <th>Vigencia</th>
                                    <th>Agente</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($relatedData['rentals'] as $rental): ?>
                                    <tr>
                                        <td><strong>ARR<?= str_pad($rental['id_arriendo'], 3, '0', STR_PAD_LEFT) ?></strong></td>
                                        <td>
                                            <div class="property-info">
                                                <strong><?= htmlspecialchars($rental['tipo_inmueble']) ?></strong>
                                                <small><?= htmlspecialchars($rental['propiedad_direccion']) ?></small>
                                            </div>
                                        </td>
                                        <td class="amount"><?= formatCurrency($rental['canon_mensual']) ?></td>
                                        <td>
                                            <span class="status-badge status-<?= strtolower($rental['estado']) ?>">
                                                <?= htmlspecialchars($rental['estado']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?= formatDate($rental['fecha_inicio']) ?> - <?= formatDate($rental['fecha_fin']) ?>
                                        </td>
                                        <td><?= htmlspecialchars($rental['agente_nombre'] ?: 'Sin agente') ?></td>
                                        <td>
                                            <a href="?module=rentals&action=view&id=<?= $rental['id_arriendo'] ?>" class="btn btn-sm btn-info">Ver</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No hay arriendos registrados para este cliente.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Visits Tab -->
        <div id="visits-tab" class="tab-content">
            <div class="card">
                <h4>Visitas a Propiedades</h4>
                <?php if (!empty($relatedData['visits'])): ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID Visita</th>
                                    <th>Fecha/Hora</th>
                                    <th>Propiedad</th>
                                    <th>Estado</th>
                                    <th>Calificación</th>
                                    <th>Agente</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($relatedData['visits'] as $visit): ?>
                                    <tr>
                                        <td><strong>VIS<?= str_pad($visit['id_visita'], 3, '0', STR_PAD_LEFT) ?></strong></td>
                                        <td>
                                            <?= formatDate($visit['fecha_visita']) ?><br>
                                            <small><?= date('H:i', strtotime($visit['hora_visita'])) ?></small>
                                        </td>
                                        <td>
                                            <div class="property-info">
                                                <strong><?= htmlspecialchars($visit['tipo_inmueble']) ?></strong>
                                                <small><?= htmlspecialchars($visit['propiedad_direccion']) ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?= strtolower($visit['estado']) ?>">
                                                <?= htmlspecialchars($visit['estado']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($visit['calificacion']): ?>
                                                <span class="interest-level <?= strtolower(str_replace(' ', '-', $visit['calificacion'])) ?>">
                                                    <?= htmlspecialchars($visit['calificacion']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">Sin calificar</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($visit['agente_nombre']) ?></td>
                                        <td>
                                            <a href="?module=visits&action=view&id=<?= $visit['id_visita'] ?>" class="btn btn-sm btn-info">Ver</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No hay visitas registradas para este cliente.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php endif; ?>

<!-- JavaScript for Enhanced Functionality -->
<script>
/**
 * Client detail view functionality
 */

// Tab functionality
function showTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });

    // Remove active class from all buttons
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active');
    });

    // Show selected tab
    document.getElementById(tabName + '-tab').classList.add('active');
    event.target.classList.add('active');
}

function confirmDelete(clientId) {
    if (confirm('¿Está seguro de que desea eliminar este cliente?\n\nEsta acción eliminará también todas las transacciones relacionadas y no se puede deshacer.')) {
        deleteClient(clientId);
    }
}

async function deleteClient(clientId) {
    try {
        const response = await Ajax.clients.delete(clientId);

        if (response.success) {
            App.showSuccessMessage('Cliente eliminado correctamente');
            window.location.href = '?module=clients';
        } else {
            throw new Error(response.error || 'Error desconocido');
        }
    } catch (error) {
        console.error('Error:', error);
        App.showErrorMessage('Error al eliminar el cliente: ' + error.message);
    }
}

function exportClientData(clientId) {
    const exportOptions = `
        <div class="export-options">
            <h4>Seleccione el formato de exportación:</h4>
            <div class="export-buttons">
                <button type="button" class="btn btn-primary" onclick="exportToFormat('pdf', ${clientId})">
                    📄 PDF Completo
                </button>
                <button type="button" class="btn btn-primary" onclick="exportToFormat('excel', ${clientId})">
                    📊 Excel con Datos
                </button>
                <button type="button" class="btn btn-primary" onclick="exportToFormat('summary', ${clientId})">
                    📋 Resumen de Actividad
                </button>
            </div>
            <div class="export-options-detail">
                <label>
                    <input type="checkbox" id="includeTransactions" checked>
                    Incluir transacciones relacionadas
                </label>
                <label>
                    <input type="checkbox" id="includeVisits" checked>
                    Incluir historial de visitas
                </label>
            </div>
        </div>
    `;

    const footer = `
        <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
    `;

    App.openModal(exportOptions, 'Exportar Datos del Cliente', footer);
}

function exportToFormat(format, clientId) {
    const includeTransactions = document.getElementById('includeTransactions').checked;
    const includeVisits = document.getElementById('includeVisits').checked;

    const params = new URLSearchParams({
        module: 'clients',
        action: 'export',
        id: clientId,
        format: format,
        transactions: includeTransactions ? '1' : '0',
        visits: includeVisits ? '1' : '0'
    });

    window.open('?' + params.toString(), '_blank');
    App.closeModal();
}

function showContactModal(clientId) {
    const contactOptions = `
        <div class="contact-options">
            <h4>Opciones de Contacto</h4>
            <div class="contact-methods">
                <button type="button" class="btn btn-primary" onclick="sendEmail(${clientId})">
                    📧 Enviar Email
                </button>
                <button type="button" class="btn btn-info" onclick="scheduleCall(${clientId})">
                    📞 Programar Llamada
                </button>
                <button type="button" class="btn btn-success" onclick="scheduleVisit(${clientId})">
                    🏠 Programar Visita
                </button>
            </div>
            <div class="contact-templates">
                <h5>Plantillas Rápidas:</h5>
                <select id="templateSelect" class="form-control">
                    <option value="">Seleccione una plantilla</option>
                    <option value="followup">Seguimiento de interés</option>
                    <option value="newproperties">Nuevas propiedades disponibles</option>
                    <option value="appointment">Confirmación de cita</option>
                    <option value="thankyou">Agradecimiento por visita</option>
                </select>
            </div>
        </div>
    `;

    const footer = `
        <button type="button" class="btn btn-secondary" data-modal-close>Cerrar</button>
    `;

    App.openModal(contactOptions, 'Contactar Cliente', footer);
}

function sendEmail(clientId) {
    const template = document.getElementById('templateSelect').value;

    // TODO: Implement email functionality
    App.showInfoMessage('Funcionalidad de email pendiente de implementación');
    App.closeModal();
}

function scheduleCall(clientId) {
    // TODO: Implement call scheduling
    App.showInfoMessage('Funcionalidad de programación de llamadas pendiente de implementación');
    App.closeModal();
}

function scheduleVisit(clientId) {
    // Redirect to visit creation with client pre-selected
    window.location.href = `?module=visits&action=create&client_id=${clientId}`;
    App.closeModal();
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    // Add click handlers for email links
    document.querySelectorAll('.email-link').forEach(link => {
        link.addEventListener('click', function(e) {
            // Track email interactions
            console.log('Email clicked:', this.href);
        });
    });

    // Add copy functionality for document number
    const documentNumber = document.querySelector('.document-number');
    if (documentNumber) {
        documentNumber.style.cursor = 'pointer';
        documentNumber.title = 'Click para copiar';
        documentNumber.addEventListener('click', function() {
            navigator.clipboard.writeText(this.textContent).then(() => {
                App.showSuccessMessage('Número de documento copiado al portapapeles');
            });
        });
    }
});
</script>

<style>
/* Additional styles specific to client detail view */
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

.client-header {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    margin-bottom: var(--spacing-lg);
}

.client-info {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: var(--spacing-lg);
}

.client-main h2 {
    color: white;
    margin-bottom: var(--spacing-sm);
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    flex-wrap: wrap;
}

.client-id {
    font-size: var(--font-size-md);
    background: rgba(255, 255, 255, 0.2);
    padding: 4px 8px;
    border-radius: var(--border-radius);
    font-weight: 500;
}

.client-type-badge .client-type {
    background: rgba(255, 255, 255, 0.9);
    color: var(--primary-color);
    padding: 6px 12px;
    border-radius: var(--border-radius);
    font-weight: 600;
    text-transform: uppercase;
    font-size: var(--font-size-sm);
}

.client-meta {
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

.document-number {
    font-family: monospace;
    font-weight: 600;
    background: var(--bg-secondary);
    padding: 4px 8px;
    border-radius: var(--border-radius);
    display: inline-block;
}

.email-link {
    color: var(--secondary-color);
    text-decoration: none;
}

.email-link:hover {
    text-decoration: underline;
}

.address {
    line-height: 1.5;
}

.stats-card {
    background: var(--bg-secondary);
}

.stats-grid-small {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--spacing-md);
}

.stat-item {
    text-align: center;
    padding: var(--spacing-sm);
    background: white;
    border-radius: var(--border-radius);
    border: 1px solid var(--border-color);
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary-color);
    margin-bottom: var(--spacing-xs);
}

.stat-label {
    font-size: var(--font-size-xs);
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.related-info {
    margin-top: var(--spacing-xl);
}

.info-tabs {
    display: flex;
    gap: 2px;
    margin-bottom: var(--spacing-md);
    border-bottom: 2px solid var(--border-color);
    flex-wrap: wrap;
}

.tab-button {
    padding: var(--spacing-sm) var(--spacing-md);
    border: none;
    background: var(--bg-secondary);
    color: var(--text-secondary);
    cursor: pointer;
    border-radius: var(--border-radius) var(--border-radius) 0 0;
    transition: all 0.2s ease;
    font-size: var(--font-size-sm);
    font-weight: 500;
}

.tab-button:hover {
    background: var(--primary-color);
    color: white;
}

.tab-button.active {
    background: var(--primary-color);
    color: white;
    font-weight: 600;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.property-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.property-info small {
    color: var(--text-secondary);
    font-size: var(--font-size-xs);
}

.amount {
    font-family: monospace;
    font-weight: 600;
    text-align: right;
}

.status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: var(--border-radius);
    font-size: var(--font-size-xs);
    font-weight: 600;
    text-transform: uppercase;
}

.status-activo { background: #d4edda; color: #155724; }
.status-borrador { background: #fff3cd; color: #856404; }
.status-finalizado { background: #d1ecf1; color: #0c5460; }
.status-cancelado { background: #f8d7da; color: #721c24; }
.status-programada { background: #cce5ff; color: #004085; }
.status-realizada { background: #d4edda; color: #155724; }
.status-cancelada { background: #f8d7da; color: #721c24; }
.status-vencido { background: #f8d7da; color: #721c24; }
.status-terminado { background: #e2e3e5; color: #383d41; }
.status-moroso { background: #f5c6cb; color: #721c24; }

.interest-level {
    display: inline-block;
    padding: 2px 6px;
    border-radius: var(--border-radius);
    font-size: var(--font-size-xs);
    font-weight: 500;
}

.interest-level.muy-interesado { background: #d4edda; color: #155724; }
.interest-level.interesado { background: #cce5ff; color: #004085; }
.interest-level.poco-interesado { background: #fff3cd; color: #856404; }
.interest-level.no-interesado { background: #f8d7da; color: #721c24; }

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
.client-type.arrendatario { background: #fff3cd; color: #856404; }
.client-type.arrendador { background: #f8d7da; color: #721c24; }

.export-options, .contact-options {
    padding: var(--spacing-md);
}

.export-buttons, .contact-methods {
    display: flex;
    gap: var(--spacing-sm);
    margin-bottom: var(--spacing-md);
    flex-wrap: wrap;
}

.export-options-detail {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-xs);
    padding-top: var(--spacing-md);
    border-top: 1px solid var(--border-color);
}

.contact-templates {
    margin-top: var(--spacing-md);
    padding-top: var(--spacing-md);
    border-top: 1px solid var(--border-color);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .details-grid {
        grid-template-columns: 1fr;
    }

    .client-info {
        flex-direction: column;
        gap: var(--spacing-md);
    }

    .client-meta {
        text-align: left;
    }

    .info-tabs {
        flex-direction: column;
    }

    .tab-button {
        border-radius: var(--border-radius);
    }

    .stats-grid-small {
        grid-template-columns: repeat(4, 1fr);
    }

    .export-buttons, .contact-methods {
        flex-direction: column;
    }
}

@media (max-width: 480px) {
    .stats-grid-small {
        grid-template-columns: repeat(2, 1fr);
    }

    .table-container {
        overflow-x: auto;
    }

    .table th,
    .table td {
        min-width: 100px;
        padding: 6px;
        font-size: var(--font-size-xs);
    }
}
</style>