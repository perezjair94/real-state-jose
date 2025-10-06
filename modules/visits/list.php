<?php
/**
 * Visits List View - Real Estate Management System
 * Display all scheduled property visits
 * Educational PHP/MySQL Project
 */

// Prevent direct access
if (!defined('APP_ACCESS')) {
    die('Direct access not permitted');
}

// Get visits from database
$visits = [];
$searchTerm = $_GET['search'] ?? '';
$estadoFilter = $_GET['estado'] ?? '';
$fechaFilter = $_GET['fecha'] ?? '';
$agenteFilter = $_GET['agente'] ?? '';

try {
    $db = new Database();
    $pdo = $db->getConnection();

    // Build WHERE clause for filters
    $whereConditions = [];
    $params = [];

    if (!empty($searchTerm)) {
        $whereConditions[] = "(v.observaciones LIKE ? OR cl.nombre LIKE ? OR cl.apellido LIKE ? OR i.direccion LIKE ?)";
        $searchWildcard = "%{$searchTerm}%";
        $params[] = $searchWildcard;
        $params[] = $searchWildcard;
        $params[] = $searchWildcard;
        $params[] = $searchWildcard;
    }

    if (!empty($estadoFilter)) {
        $whereConditions[] = "v.estado = ?";
        $params[] = $estadoFilter;
    }

    if (!empty($fechaFilter)) {
        $whereConditions[] = "v.fecha_visita = ?";
        $params[] = $fechaFilter;
    }

    if (!empty($agenteFilter)) {
        $whereConditions[] = "v.id_agente = ?";
        $params[] = $agenteFilter;
    }

    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    // Get visits with related data
    $sql = "SELECT v.id_visita, v.fecha_visita, v.hora_visita, v.estado, v.observaciones, v.calificacion, v.created_at,
                   CONCAT('INM', LPAD(i.id_inmueble, 3, '0')) as inmueble_codigo,
                   CONCAT(i.tipo_inmueble, ' - ', i.direccion) as inmueble_descripcion,
                   CONCAT(cl.nombre, ' ', cl.apellido) as cliente_nombre,
                   ag.nombre as agente_nombre
            FROM visita v
            INNER JOIN inmueble i ON v.id_inmueble = i.id_inmueble
            INNER JOIN cliente cl ON v.id_cliente = cl.id_cliente
            INNER JOIN agente ag ON v.id_agente = ag.id_agente
            {$whereClause}
            ORDER BY v.fecha_visita ASC, v.hora_visita ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $visits = $stmt->fetchAll();

    // Get agents for filter dropdown
    $agentSql = "SELECT id_agente, nombre FROM agente WHERE activo = 1 ORDER BY nombre";
    $agentStmt = $pdo->prepare($agentSql);
    $agentStmt->execute();
    $agents = $agentStmt->fetchAll();

} catch (PDOException $e) {
    error_log("Error fetching visits: " . $e->getMessage());
    $error = "Error al cargar las visitas. Intente nuevamente.";
    $agents = [];
}
?>

<div class="module-header">
    <h2>Gestión de Visitas</h2>
    <p class="module-description">
        Programe y administre las visitas de clientes a las propiedades.
    </p>
</div>

<!-- Action Buttons -->
<div class="action-buttons">
    <a href="?module=visits&action=create" class="btn btn-primary">
        + Programar Nueva Visita
    </a>
    <button type="button" class="btn btn-secondary" onclick="alert('Función en desarrollo')">
        Agenda del Día
    </button>
    <button type="button" class="btn btn-secondary" onclick="alert('Función en desarrollo')">
        Reporte de Visitas
    </button>
</div>

<!-- Filter Section -->
<div class="card">
    <h3>Filtros</h3>
    <form method="GET" class="filter-form">
        <input type="hidden" name="module" value="visits">

        <div class="form-row">
            <div class="form-group">
                <label for="search">Buscar:</label>
                <input
                    type="text"
                    id="search"
                    name="search"
                    value="<?= htmlspecialchars($searchTerm) ?>"
                    placeholder="Cliente, dirección, observaciones..."
                    class="form-control"
                >
            </div>

            <div class="form-group">
                <label for="estado">Estado:</label>
                <select id="estado" name="estado" class="form-control">
                    <option value="">Todos los estados</option>
                    <option value="Programada" <?= $estadoFilter === 'Programada' ? 'selected' : '' ?>>Programada</option>
                    <option value="Realizada" <?= $estadoFilter === 'Realizada' ? 'selected' : '' ?>>Realizada</option>
                    <option value="Cancelada" <?= $estadoFilter === 'Cancelada' ? 'selected' : '' ?>>Cancelada</option>
                    <option value="Reprogramada" <?= $estadoFilter === 'Reprogramada' ? 'selected' : '' ?>>Reprogramada</option>
                </select>
            </div>

            <div class="form-group">
                <label for="fecha">Fecha:</label>
                <input
                    type="date"
                    id="fecha"
                    name="fecha"
                    value="<?= htmlspecialchars($fechaFilter) ?>"
                    class="form-control"
                >
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="agente">Agente:</label>
                <select id="agente" name="agente" class="form-control">
                    <option value="">Todos los agentes</option>
                    <?php foreach ($agents as $agent): ?>
                        <option value="<?= $agent['id_agente'] ?>" <?= $agenteFilter == $agent['id_agente'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($agent['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="?module=visits" class="btn btn-secondary">Limpiar</a>
        </div>
    </form>
</div>

<!-- Visits for Today -->
<div class="card highlight-card">
    <h3>🗓️ Visitas de Hoy</h3>
    <?php
    $today = date('Y-m-d');
    $todayVisits = array_filter($visits, fn($v) => $v['fecha_visita'] === $today);
    ?>

    <?php if (!empty($todayVisits)): ?>
        <div class="today-visits">
            <?php foreach ($todayVisits as $visit): ?>
                <div class="visit-card">
                    <div class="visit-time"><?= $visit['hora_visita'] ?></div>
                    <div class="visit-info">
                        <strong><?= htmlspecialchars($visit['cliente_nombre']) ?></strong><br>
                        <span class="property-id"><?= $visit['inmueble_id'] ?></span> -
                        <span class="agent-name"><?= $visit['agente_nombre'] ?></span>
                    </div>
                    <div class="visit-status">
                        <span class="status <?= strtolower($visit['estado']) ?>">
                            <?= $visit['estado'] ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="no-visits">No hay visitas programadas para hoy.</p>
    <?php endif; ?>
</div>

<!-- All Visits Table -->
<div class="card">
    <h3>Todas las Visitas</h3>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Inmueble</th>
                    <th>Cliente</th>
                    <th>Agente</th>
                    <th>Estado</th>
                    <th>Observaciones</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($visits as $visit): ?>
                    <tr class="<?= $visit['fecha_visita'] === $today ? 'today-row' : '' ?>">
                        <td>
                            <strong>VIS<?= str_pad($visit['id_visita'], 3, '0', STR_PAD_LEFT) ?></strong>
                        </td>
                        <td>
                            <?= formatDate($visit['fecha_visita']) ?>
                            <?php if ($visit['fecha_visita'] === $today): ?>
                                <span class="today-badge">HOY</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= $visit['hora_visita'] ?></strong>
                        </td>
                        <td>
                            <span class="property-id"><?= htmlspecialchars($visit['inmueble_codigo']) ?></span>
                            <div class="property-description"><?= htmlspecialchars($visit['inmueble_descripcion']) ?></div>
                        </td>
                        <td><?= htmlspecialchars($visit['cliente_nombre']) ?></td>
                        <td><?= htmlspecialchars($visit['agente_nombre']) ?></td>
                        <td>
                            <span class="status <?= strtolower($visit['estado']) ?>">
                                <?= htmlspecialchars($visit['estado']) ?>
                            </span>
                            <?php if ($visit['calificacion']): ?>
                                <div class="interest-level"><?= htmlspecialchars($visit['calificacion']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="observations">
                                <?= htmlspecialchars($visit['observaciones']) ?: 'Sin observaciones' ?>
                            </div>
                        </td>
                        <td class="table-actions">
                            <a href="?module=visits&action=view&id=<?= $visit['id_visita'] ?>"
                               class="btn btn-sm btn-info" title="Ver detalles">
                                Ver
                            </a>
                            <?php if ($visit['estado'] === 'Programada'): ?>
                                <a href="?module=visits&action=edit&id=<?= $visit['id_visita'] ?>"
                                   class="btn btn-sm btn-secondary" title="Editar">
                                    Editar
                                </a>
                                <button type="button" class="btn btn-sm btn-danger"
                                        onclick="cancelVisit(<?= $visit['id_visita'] ?>)" title="Cancelar">
                                    Cancelar
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Visits Summary -->
<div class="card">
    <h3>Resumen de Visitas</h3>
    <div class="stats-grid">
        <div class="stat-item">
            <span class="stat-label">Total Visitas</span>
            <span class="stat-value"><?= count($visits) ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">Programadas</span>
            <span class="stat-value"><?= count(array_filter($visits, fn($v) => $v['estado'] === 'Programada')) ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">Realizadas</span>
            <span class="stat-value"><?= count(array_filter($visits, fn($v) => $v['estado'] === 'Realizada')) ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">Hoy</span>
            <span class="stat-value"><?= count($todayVisits) ?></span>
        </div>
    </div>
</div>

<!-- Error Display -->
<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<style>
.highlight-card {
    border-left: 4px solid var(--primary-color);
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.today-visits {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-sm);
    margin-top: var(--spacing-md);
}

.visit-card {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    padding: var(--spacing-sm);
    background: white;
    border-radius: var(--border-radius);
    border: 1px solid var(--border-color);
}

.visit-time {
    font-weight: 600;
    font-size: var(--font-size-lg);
    color: var(--primary-color);
    min-width: 60px;
}

.visit-info {
    flex: 1;
    font-size: var(--font-size-sm);
}

.visit-status {
    margin-left: auto;
}

.no-visits {
    text-align: center;
    color: var(--text-secondary);
    padding: var(--spacing-lg);
    font-style: italic;
}

.today-row {
    background-color: #fff3cd;
}

.today-badge {
    background: var(--accent-color);
    color: white;
    padding: 2px 6px;
    border-radius: var(--border-radius);
    font-size: var(--font-size-xs);
    font-weight: 500;
    margin-left: var(--spacing-xs);
}

.observations {
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: var(--font-size-sm);
    color: var(--text-secondary);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: var(--spacing-md);
    margin-top: var(--spacing-md);
}

.stat-item {
    text-align: center;
    padding: var(--spacing-md);
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
}

.stat-label {
    display: block;
    font-size: var(--font-size-sm);
    color: var(--text-secondary);
    margin-bottom: var(--spacing-xs);
}

.stat-value {
    display: block;
    font-size: var(--font-size-lg);
    font-weight: 600;
    color: var(--primary-color);
}

.property-id {
    background: var(--bg-secondary);
    padding: 2px 6px;
    border-radius: var(--border-radius);
    font-size: var(--font-size-xs);
    font-weight: 500;
}

.agent-name {
    color: var(--text-secondary);
    font-size: var(--font-size-xs);
}

.filter-form .form-actions {
    margin-top: var(--spacing-md);
    display: flex;
    gap: var(--spacing-sm);
}

.property-description {
    font-size: var(--font-size-xs);
    color: var(--text-secondary);
    margin-top: 2px;
}

.interest-level {
    font-size: var(--font-size-xs);
    color: var(--accent-color);
    font-weight: 500;
    margin-top: 2px;
}

.alert {
    padding: var(--spacing-md);
    border-radius: var(--border-radius);
    margin-bottom: var(--spacing-lg);
}

.alert-danger {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}
</style>

<script>
/**
 * Cancel a scheduled visit
 */
function cancelVisit(visitId) {
    if (!visitId || visitId <= 0) {
        alert('ID de visita inválido');
        return;
    }

    // Ask for confirmation
    if (!confirm('¿Está seguro de que desea cancelar esta visita?\n\nEsta acción cambiará el estado de la visita a "Cancelada".')) {
        return;
    }

    // Create form data
    const formData = new FormData();
    formData.append('action', 'updateStatus');
    formData.append('id', visitId);
    formData.append('estado', 'Cancelada');

    // Show loading state (optional - disable button)
    const buttons = document.querySelectorAll(`button[onclick*="cancelVisit(${visitId})"]`);
    buttons.forEach(btn => {
        btn.disabled = true;
        btn.textContent = 'Cancelando...';
    });

    // Send AJAX request
    fetch('?module=visits&action=ajax', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Visita cancelada exitosamente');
            // Reload page to show updated status
            window.location.reload();
        } else {
            alert('Error al cancelar la visita: ' + (data.message || 'Error desconocido'));
            // Re-enable buttons on error
            buttons.forEach(btn => {
                btn.disabled = false;
                btn.textContent = 'Cancelar';
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión al cancelar la visita');
        // Re-enable buttons on error
        buttons.forEach(btn => {
            btn.disabled = false;
            btn.textContent = 'Cancelar';
        });
    });
}
</script>