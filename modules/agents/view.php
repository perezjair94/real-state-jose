<?php
/**
 * Agent Detail View - Real Estate Management System
 * Display detailed information about a specific agent
 * Educational PHP/MySQL Project
 */

// Prevent direct access
if (!defined('APP_ACCESS')) {
    die('Direct access not permitted');
}

$error = '';
$agent = null;
$agentId = (int)($_GET['id'] ?? 0);

// Additional data for detailed view
$relatedData = [
    'sales' => [],
    'contracts' => [],
    'rentals' => [],
    'visits' => [],
    'properties' => []
];

// Statistics
$stats = [
    'total_sales' => 0,
    'total_commission' => 0,
    'active_contracts' => 0,
    'scheduled_visits' => 0,
    'managed_properties' => 0
];

// Validate agent ID and load data
if ($agentId <= 0) {
    $error = "ID de agente inválido";
} else {
    try {
        $db = new Database();
        $pdo = $db->getConnection();

        // Load agent data
        $stmt = $pdo->prepare("SELECT * FROM agente WHERE id_agente = ?");
        $stmt->execute([$agentId]);
        $agent = $stmt->fetch();

        if (!$agent) {
            $error = "Agente no encontrado";
        } else {
            // Load related sales
            $stmt = $pdo->prepare("
                SELECT v.*, i.direccion as propiedad_direccion, i.tipo_inmueble,
                       c.nombre as cliente_nombre, c.apellido as cliente_apellido
                FROM venta v
                LEFT JOIN inmueble i ON v.id_inmueble = i.id_inmueble
                LEFT JOIN cliente c ON v.id_cliente = c.id_cliente
                WHERE v.id_agente = ?
                ORDER BY v.fecha_venta DESC
                LIMIT 10
            ");
            $stmt->execute([$agentId]);
            $relatedData['sales'] = $stmt->fetchAll();

            // Load related contracts
            $stmt = $pdo->prepare("
                SELECT c.*, i.direccion as propiedad_direccion, i.tipo_inmueble,
                       cl.nombre as cliente_nombre, cl.apellido as cliente_apellido
                FROM contrato c
                LEFT JOIN inmueble i ON c.id_inmueble = i.id_inmueble
                LEFT JOIN cliente cl ON c.id_cliente = cl.id_cliente
                WHERE c.id_agente = ?
                ORDER BY c.fecha_inicio DESC
                LIMIT 10
            ");
            $stmt->execute([$agentId]);
            $relatedData['contracts'] = $stmt->fetchAll();

            // Load related rentals
            $stmt = $pdo->prepare("
                SELECT a.*, i.direccion as propiedad_direccion, i.tipo_inmueble,
                       c.nombre as cliente_nombre, c.apellido as cliente_apellido
                FROM arriendo a
                LEFT JOIN inmueble i ON a.id_inmueble = i.id_inmueble
                LEFT JOIN cliente c ON a.id_cliente = c.id_cliente
                WHERE a.id_agente = ?
                ORDER BY a.fecha_inicio DESC
                LIMIT 10
            ");
            $stmt->execute([$agentId]);
            $relatedData['rentals'] = $stmt->fetchAll();

            // Load related visits
            $stmt = $pdo->prepare("
                SELECT v.*, i.direccion as propiedad_direccion, i.tipo_inmueble,
                       c.nombre as cliente_nombre, c.apellido as cliente_apellido
                FROM visita v
                LEFT JOIN inmueble i ON v.id_inmueble = i.id_inmueble
                LEFT JOIN cliente c ON c.id_cliente = v.id_cliente
                WHERE v.id_agente = ?
                ORDER BY v.fecha_visita DESC, v.hora_visita DESC
                LIMIT 10
            ");
            $stmt->execute([$agentId]);
            $relatedData['visits'] = $stmt->fetchAll();

            // Calculate statistics
            // Total sales count
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM venta WHERE id_agente = ?");
            $stmt->execute([$agentId]);
            $stats['total_sales'] = $stmt->fetchColumn();

            // Total commission
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(comision), 0) FROM venta WHERE id_agente = ? AND comision IS NOT NULL");
            $stmt->execute([$agentId]);
            $stats['total_commission'] = $stmt->fetchColumn();

            // Active contracts
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM contrato WHERE id_agente = ? AND estado = 'Activo'");
            $stmt->execute([$agentId]);
            $stats['active_contracts'] = $stmt->fetchColumn();

            // Scheduled visits
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM visita WHERE id_agente = ? AND estado = 'Programada' AND fecha_visita >= CURDATE()");
            $stmt->execute([$agentId]);
            $stats['scheduled_visits'] = $stmt->fetchColumn();
        }

    } catch (PDOException $e) {
        error_log("Error loading agent details: " . $e->getMessage());
        $error = "Error al cargar los datos del agente";
    }
}
?>

<div class="module-header">
    <h2>Detalles del Agente</h2>
    <p class="module-description">
        Información completa y rendimiento del agente inmobiliario.
    </p>
</div>

<!-- Navigation Breadcrumb -->
<div class="breadcrumb">
    <a href="?module=agents">Agentes</a> >
    <?php if ($agent): ?>
        AGE<?= str_pad($agent['id_agente'], 3, '0', STR_PAD_LEFT) ?> - <?= htmlspecialchars($agent['nombre']) ?>
    <?php else: ?>
        Agente no encontrado
    <?php endif; ?>
</div>

<!-- Action Buttons -->
<div class="action-buttons">
    <a href="?module=agents" class="btn btn-secondary">
        ← Volver a Lista
    </a>
    <?php if ($agent): ?>
        <a href="?module=agents&action=edit&id=<?= $agentId ?>" class="btn btn-primary">
            Editar Agente
        </a>
        <button type="button" class="btn btn-info" onclick="exportAgentData(<?= $agentId ?>)">
            Exportar Datos
        </button>
        <button type="button" class="btn btn-warning" onclick="showContactModal(<?= $agentId ?>)">
            Contactar
        </button>
        <button type="button" class="btn btn-danger" onclick="confirmDelete(<?= $agentId ?>)">
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
            <h3>Agente No Encontrado</h3>
            <p>El agente solicitado no existe o ha sido eliminado.</p>
            <a href="?module=agents" class="btn btn-primary">Volver a Lista de Agentes</a>
        </div>
    </div>
<?php else: ?>

    <!-- Agent Header Card -->
    <div class="card agent-header">
        <div class="agent-info">
            <div class="agent-main">
                <h2>
                    <?= htmlspecialchars($agent['nombre']) ?>
                    <span class="agent-id">AGE<?= str_pad($agent['id_agente'], 3, '0', STR_PAD_LEFT) ?></span>
                </h2>
                <div class="agent-status-badge">
                    <span class="agent-status <?= $agent['activo'] ? 'active' : 'inactive' ?>">
                        <?= $agent['activo'] ? 'Activo' : 'Inactivo' ?>
                    </span>
                </div>
                <?php if ($agent['asesor']): ?>
                    <div class="supervisor-info">
                        <small><strong>Supervisor:</strong> <?= htmlspecialchars($agent['asesor']) ?></small>
                    </div>
                <?php endif; ?>
            </div>
            <div class="agent-meta">
                <div class="meta-item">
                    <strong>Registrado:</strong> <?= formatDate($agent['created_at']) ?>
                </div>
                <?php if ($agent['updated_at'] !== $agent['created_at']): ?>
                    <div class="meta-item">
                        <strong>Actualizado:</strong> <?= formatDate($agent['updated_at']) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Agent Details Grid -->
    <div class="details-grid">
        <!-- Contact Information -->
        <div class="card detail-card">
            <h3>Información de Contacto</h3>
            <div class="detail-group">
                <div class="detail-item">
                    <label>Email:</label>
                    <value>
                        <a href="mailto:<?= htmlspecialchars($agent['correo']) ?>" class="email-link">
                            <?= htmlspecialchars($agent['correo']) ?>
                        </a>
                    </value>
                </div>
                <div class="detail-item">
                    <label>Teléfono:</label>
                    <value>
                        <a href="tel:<?= htmlspecialchars($agent['telefono']) ?>" class="phone-link">
                            <?= htmlspecialchars($agent['telefono']) ?>
                        </a>
                    </value>
                </div>
            </div>
        </div>

        <!-- Status Information -->
        <div class="card detail-card">
            <h3>Estado del Agente</h3>
            <div class="detail-group">
                <div class="detail-item">
                    <label>Estado Actual:</label>
                    <value>
                        <span class="agent-status <?= $agent['activo'] ? 'active' : 'inactive' ?>">
                            <?= $agent['activo'] ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </value>
                </div>
                <?php if ($agent['asesor']): ?>
                    <div class="detail-item">
                        <label>Supervisor:</label>
                        <value><?= htmlspecialchars($agent['asesor']) ?></value>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Performance Stats -->
        <div class="card detail-card stats-card">
            <h3>Estadísticas de Rendimiento</h3>
            <div class="stats-grid-small">
                <div class="stat-item">
                    <div class="stat-number"><?= $stats['total_sales'] ?></div>
                    <div class="stat-label">Ventas Realizadas</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= formatCurrency($stats['total_commission']) ?></div>
                    <div class="stat-label">Comisiones</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $stats['active_contracts'] ?></div>
                    <div class="stat-label">Contratos Activos</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $stats['scheduled_visits'] ?></div>
                    <div class="stat-label">Visitas Programadas</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card detail-card actions-card">
            <h3>Acciones Rápidas</h3>
            <div class="quick-actions">
                <button type="button" class="btn btn-sm btn-primary" onclick="scheduleVisit(<?= $agentId ?>)">
                    📅 Programar Visita
                </button>
                <button type="button" class="btn btn-sm btn-info" onclick="viewPerformance(<?= $agentId ?>)">
                    📊 Ver Métricas
                </button>
                <button type="button" class="btn btn-sm btn-success" onclick="assignProperty(<?= $agentId ?>)">
                    🏠 Asignar Propiedad
                </button>
                <button type="button" class="btn btn-sm btn-warning" onclick="sendMessage(<?= $agentId ?>)">
                    💬 Enviar Mensaje
                </button>
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
                                    <th>Cliente</th>
                                    <th>Propiedad</th>
                                    <th>Valor</th>
                                    <th>Comisión</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($relatedData['sales'] as $sale): ?>
                                    <tr>
                                        <td><strong>VEN<?= str_pad($sale['id_venta'], 3, '0', STR_PAD_LEFT) ?></strong></td>
                                        <td><?= formatDate($sale['fecha_venta']) ?></td>
                                        <td>
                                            <?= htmlspecialchars($sale['cliente_nombre'] . ' ' . $sale['cliente_apellido']) ?>
                                        </td>
                                        <td>
                                            <div class="property-info">
                                                <strong><?= htmlspecialchars($sale['tipo_inmueble']) ?></strong>
                                                <small><?= htmlspecialchars($sale['propiedad_direccion']) ?></small>
                                            </div>
                                        </td>
                                        <td class="amount"><?= formatCurrency($sale['valor']) ?></td>
                                        <td class="amount"><?= $sale['comision'] ? formatCurrency($sale['comision']) : '-' ?></td>
                                        <td>
                                            <a href="?module=sales&action=view&id=<?= $sale['id_venta'] ?>" class="btn btn-sm btn-info">Ver</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($stats['total_sales'] > count($relatedData['sales'])): ?>
                        <div class="load-more">
                            <button type="button" class="btn btn-outline" onclick="loadAllSales(<?= $agentId ?>)">
                                Ver todas las ventas (<?= $stats['total_sales'] ?>)
                            </button>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted">No hay ventas registradas para este agente.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Contracts Tab -->
        <div id="contracts-tab" class="tab-content">
            <div class="card">
                <h4>Contratos Gestionados</h4>
                <?php if (!empty($relatedData['contracts'])): ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID Contrato</th>
                                    <th>Tipo</th>
                                    <th>Cliente</th>
                                    <th>Propiedad</th>
                                    <th>Valor</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($relatedData['contracts'] as $contract): ?>
                                    <tr>
                                        <td><strong>CON<?= str_pad($contract['id_contrato'], 3, '0', STR_PAD_LEFT) ?></strong></td>
                                        <td><?= htmlspecialchars($contract['tipo_contrato']) ?></td>
                                        <td>
                                            <?= htmlspecialchars($contract['cliente_nombre'] . ' ' . $contract['cliente_apellido']) ?>
                                        </td>
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
                                            <a href="?module=contracts&action=view&id=<?= $contract['id_contrato'] ?>" class="btn btn-sm btn-info">Ver</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No hay contratos registrados para este agente.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Rentals Tab -->
        <div id="rentals-tab" class="tab-content">
            <div class="card">
                <h4>Arriendos Gestionados</h4>
                <?php if (!empty($relatedData['rentals'])): ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID Arriendo</th>
                                    <th>Cliente</th>
                                    <th>Propiedad</th>
                                    <th>Canon</th>
                                    <th>Estado</th>
                                    <th>Vigencia</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($relatedData['rentals'] as $rental): ?>
                                    <tr>
                                        <td><strong>ARR<?= str_pad($rental['id_arriendo'], 3, '0', STR_PAD_LEFT) ?></strong></td>
                                        <td>
                                            <?= htmlspecialchars($rental['cliente_nombre'] . ' ' . $rental['cliente_apellido']) ?>
                                        </td>
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
                                        <td>
                                            <a href="?module=rentals&action=view&id=<?= $rental['id_arriendo'] ?>" class="btn btn-sm btn-info">Ver</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No hay arriendos registrados para este agente.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Visits Tab -->
        <div id="visits-tab" class="tab-content">
            <div class="card">
                <h4>Visitas Programadas y Realizadas</h4>
                <?php if (!empty($relatedData['visits'])): ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID Visita</th>
                                    <th>Fecha/Hora</th>
                                    <th>Cliente</th>
                                    <th>Propiedad</th>
                                    <th>Estado</th>
                                    <th>Calificación</th>
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
                                            <?= htmlspecialchars($visit['cliente_nombre'] . ' ' . $visit['cliente_apellido']) ?>
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
                                        <td>
                                            <a href="?module=visits&action=view&id=<?= $visit['id_visita'] ?>" class="btn btn-sm btn-info">Ver</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No hay visitas registradas para este agente.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php endif; ?>

<!-- JavaScript for Enhanced Functionality -->
<script>
/**
 * Agent detail view functionality
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

function confirmDelete(agentId) {
    if (confirm('¿Está seguro de que desea eliminar este agente?\n\nEsta acción eliminará también todas las transacciones relacionadas y no se puede deshacer.')) {
        deleteAgent(agentId);
    }
}

async function deleteAgent(agentId) {
    try {
        const response = await Ajax.agents.delete(agentId);

        if (response.success) {
            App.showSuccessMessage('Agente eliminado correctamente');
            window.location.href = '?module=agents';
        } else {
            throw new Error(response.error || 'Error desconocido');
        }
    } catch (error) {
        console.error('Error:', error);
        App.showErrorMessage('Error al eliminar el agente: ' + error.message);
    }
}

function exportAgentData(agentId) {
    const exportOptions = `
        <div class="export-options">
            <h4>Seleccione el formato de exportación:</h4>
            <div class="export-buttons">
                <button type="button" class="btn btn-primary" onclick="exportToFormat('pdf', ${agentId})">
                    📄 PDF Completo
                </button>
                <button type="button" class="btn btn-primary" onclick="exportToFormat('excel', ${agentId})">
                    📊 Excel con Datos
                </button>
                <button type="button" class="btn btn-primary" onclick="exportToFormat('performance', ${agentId})">
                    📈 Reporte de Rendimiento
                </button>
            </div>
            <div class="export-options-detail">
                <label>
                    <input type="checkbox" id="includeTransactions" checked>
                    Incluir transacciones realizadas
                </label>
                <label>
                    <input type="checkbox" id="includeCommissions" checked>
                    Incluir cálculo de comisiones
                </label>
            </div>
        </div>
    `;

    const footer = `
        <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
    `;

    App.openModal(exportOptions, 'Exportar Datos del Agente', footer);
}

function exportToFormat(format, agentId) {
    const includeTransactions = document.getElementById('includeTransactions').checked;
    const includeCommissions = document.getElementById('includeCommissions').checked;

    const params = new URLSearchParams({
        module: 'agents',
        action: 'export',
        id: agentId,
        format: format,
        transactions: includeTransactions ? '1' : '0',
        commissions: includeCommissions ? '1' : '0'
    });

    window.open('?' + params.toString(), '_blank');
    App.closeModal();
}

function showContactModal(agentId) {
    const contactOptions = `
        <div class="contact-options">
            <h4>Opciones de Contacto</h4>
            <div class="contact-methods">
                <button type="button" class="btn btn-primary" onclick="sendEmail(${agentId})">
                    📧 Enviar Email
                </button>
                <button type="button" class="btn btn-info" onclick="makeCall(${agentId})">
                    📞 Realizar Llamada
                </button>
                <button type="button" class="btn btn-success" onclick="sendNotification(${agentId})">
                    🔔 Enviar Notificación
                </button>
            </div>
            <div class="contact-templates">
                <h5>Plantillas Rápidas:</h5>
                <select id="templateSelect" class="form-control">
                    <option value="">Seleccione una plantilla</option>
                    <option value="assignment">Asignación de propiedad</option>
                    <option value="performance">Revisión de rendimiento</option>
                    <option value="meeting">Solicitud de reunión</option>
                    <option value="congratulations">Felicitaciones por venta</option>
                </select>
            </div>
        </div>
    `;

    const footer = `
        <button type="button" class="btn btn-secondary" data-modal-close>Cerrar</button>
    `;

    App.openModal(contactOptions, 'Contactar Agente', footer);
}

// Quick action functions
function scheduleVisit(agentId) {
    window.location.href = `?module=visits&action=create&agent_id=${agentId}`;
}

function viewPerformance(agentId) {
    // TODO: Implement performance dashboard
    App.showInfoMessage('Dashboard de rendimiento pendiente de implementación');
}

function assignProperty(agentId) {
    // TODO: Implement property assignment
    App.showInfoMessage('Funcionalidad de asignación de propiedades pendiente de implementación');
}

function sendMessage(agentId) {
    showContactModal(agentId);
}

function sendEmail(agentId) {
    const template = document.getElementById('templateSelect').value;
    // TODO: Implement email functionality
    App.showInfoMessage('Funcionalidad de email pendiente de implementación');
    App.closeModal();
}

function makeCall(agentId) {
    // TODO: Implement call functionality
    App.showInfoMessage('Funcionalidad de llamadas pendiente de implementación');
    App.closeModal();
}

function sendNotification(agentId) {
    // TODO: Implement notification system
    App.showInfoMessage('Sistema de notificaciones pendiente de implementación');
    App.closeModal();
}

function loadAllSales(agentId) {
    window.location.href = `?module=sales&agent_id=${agentId}`;
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    // Add click handlers for contact links
    document.querySelectorAll('.email-link').forEach(link => {
        link.addEventListener('click', function(e) {
            console.log('Email clicked:', this.href);
        });
    });

    document.querySelectorAll('.phone-link').forEach(link => {
        link.addEventListener('click', function(e) {
            console.log('Phone clicked:', this.href);
        });
    });
});
</script>

<style>
/* Additional styles specific to agent detail view */
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

.agent-header {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    margin-bottom: var(--spacing-lg);
}

.agent-info {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: var(--spacing-lg);
}

.agent-main h2 {
    color: white;
    margin-bottom: var(--spacing-sm);
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    flex-wrap: wrap;
}

.agent-id {
    font-size: var(--font-size-md);
    background: rgba(255, 255, 255, 0.2);
    padding: 4px 8px;
    border-radius: var(--border-radius);
    font-weight: 500;
}

.agent-status-badge .agent-status {
    background: rgba(255, 255, 255, 0.9);
    color: var(--primary-color);
    padding: 6px 12px;
    border-radius: var(--border-radius);
    font-weight: 600;
    text-transform: uppercase;
    font-size: var(--font-size-sm);
}

.agent-status.active {
    background: #28a745 !important;
    color: white !important;
}

.agent-status.inactive {
    background: #dc3545 !important;
    color: white !important;
}

.supervisor-info {
    margin-top: var(--spacing-xs);
    opacity: 0.9;
}

.agent-meta {
    text-align: right;
    opacity: 0.9;
}

.meta-item {
    margin-bottom: var(--spacing-xs);
    font-size: var(--font-size-sm);
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
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

.email-link, .phone-link {
    color: var(--secondary-color);
    text-decoration: none;
}

.email-link:hover, .phone-link:hover {
    text-decoration: underline;
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

.actions-card {
    background: var(--bg-light);
}

.quick-actions {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--spacing-sm);
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

.load-more {
    margin-top: var(--spacing-md);
    text-align: center;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .details-grid {
        grid-template-columns: 1fr;
    }

    .agent-info {
        flex-direction: column;
        gap: var(--spacing-md);
    }

    .agent-meta {
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

    .quick-actions {
        grid-template-columns: 1fr;
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