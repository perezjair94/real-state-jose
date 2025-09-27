<?php
/**
 * Agents List View - Real Estate Management System
 * Display all agents with basic information
 * Educational PHP/MySQL Project
 */

// Prevent direct access
if (!defined('APP_ACCESS')) {
    die('Direct access not permitted');
}

// Get agents from database
$agents = [];
$searchTerm = $_GET['search'] ?? '';
$statusFilter = $_GET['activo'] ?? '';

try {
    $db = new Database();
    $pdo = $db->getConnection();

    // Build WHERE clause for filters
    $whereConditions = [];
    $params = [];

    if (!empty($searchTerm)) {
        $whereConditions[] = "(nombre LIKE ? OR correo LIKE ? OR asesor LIKE ?)";
        $searchWildcard = "%{$searchTerm}%";
        $params[] = $searchWildcard;
        $params[] = $searchWildcard;
        $params[] = $searchWildcard;
    }

    if ($statusFilter !== '') {
        $whereConditions[] = "activo = ?";
        $params[] = (int)$statusFilter;
    }

    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    // Get agents with optional filtering
    $sql = "SELECT id_agente, nombre, correo, telefono, asesor, activo, created_at
            FROM agente
            {$whereClause}
            ORDER BY created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $agents = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Error fetching agents: " . $e->getMessage());
    $error = "Error al cargar los agentes. Intente nuevamente.";
}
?>

<div class="module-header">
    <h2>Gestión de Agentes</h2>
    <p class="module-description">
        Administre el equipo de agentes inmobiliarios y sus supervisores.
    </p>
</div>

<!-- Action Buttons -->
<div class="action-buttons">
    <a href="?module=agents&action=create" class="btn btn-primary">
        + Agregar Nuevo Agente
    </a>
    <button type="button" class="btn btn-secondary" onclick="alert('Exportar función en desarrollo')">
        Exportar Lista
    </button>
</div>

<!-- Search and Filter Section -->
<div class="card">
    <h3>Filtros de Búsqueda</h3>

    <form method="GET" class="filter-form">
        <input type="hidden" name="module" value="agents">

        <div class="form-row">
            <div class="form-group">
                <label for="search">Buscar por nombre, correo o asesor:</label>
                <input
                    type="text"
                    id="search"
                    name="search"
                    value="<?= htmlspecialchars($searchTerm) ?>"
                    placeholder="Ingrese términos de búsqueda..."
                    class="form-control"
                >
            </div>

            <div class="form-group">
                <label for="activo">Estado:</label>
                <select id="activo" name="activo" class="form-control">
                    <option value="">Todos los estados</option>
                    <option value="1" <?= $statusFilter === '1' ? 'selected' : '' ?>>Activos</option>
                    <option value="0" <?= $statusFilter === '0' ? 'selected' : '' ?>>Inactivos</option>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Buscar</button>
            <a href="?module=agents" class="btn btn-secondary">Limpiar Filtros</a>
        </div>
    </form>
</div>

<!-- Results Summary -->
<div class="results-summary">
    <p>
        Mostrando <?= count($agents) ?> agentes
        <?php if (!empty($searchTerm) || $statusFilter !== ''): ?>
            (filtrados)
        <?php endif; ?>
    </p>
</div>

<!-- Error Display -->
<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (!empty($agents)): ?>
    <!-- Agents Table -->
    <div class="card">
        <h3>Lista de Agentes</h3>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Teléfono</th>
                        <th>Asesor</th>
                        <th>Estado</th>
                        <th>Fecha Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($agents as $agent): ?>
                        <tr>
                            <td>
                                <strong>AGE<?= str_pad($agent['id_agente'], 3, '0', STR_PAD_LEFT) ?></strong>
                            </td>
                            <td><?= htmlspecialchars($agent['nombre']) ?></td>
                            <td>
                                <a href="mailto:<?= htmlspecialchars($agent['correo']) ?>">
                                    <?= htmlspecialchars($agent['correo']) ?>
                                </a>
                            </td>
                            <td>
                                <a href="tel:<?= htmlspecialchars($agent['telefono']) ?>">
                                    <?= htmlspecialchars($agent['telefono']) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($agent['asesor'] ?: 'Sin asesor') ?></td>
                            <td>
                                <span class="status <?= $agent['activo'] ? 'activo' : 'inactivo' ?>">
                                    <?= $agent['activo'] ? 'Activo' : 'Inactivo' ?>
                                </span>
                            </td>
                            <td><?= formatDate($agent['created_at']) ?></td>
                            <td class="table-actions">
                                <a href="?module=agents&action=view&id=<?= $agent['id_agente'] ?>"
                                   class="btn btn-sm btn-info" title="Ver detalles">
                                    Ver
                                </a>
                                <a href="?module=agents&action=edit&id=<?= $agent['id_agente'] ?>"
                                   class="btn btn-sm btn-secondary" title="Editar">
                                    Editar
                                </a>
                                <?php if ($agent['activo']): ?>
                                    <button type="button"
                                            class="btn btn-sm btn-warning"
                                            onclick="toggleAgentStatus(<?= $agent['id_agente'] ?>, 0)"
                                            title="Desactivar">
                                        Desactivar
                                    </button>
                                <?php else: ?>
                                    <button type="button"
                                            class="btn btn-sm btn-success"
                                            onclick="toggleAgentStatus(<?= $agent['id_agente'] ?>, 1)"
                                            title="Activar">
                                        Activar
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php else: ?>
    <!-- No Results -->
    <div class="card">
        <div class="no-results">
            <h3>No se encontraron agentes</h3>
            <?php if (!empty($searchTerm) || $statusFilter !== ''): ?>
                <p>No hay agentes que coincidan con los filtros seleccionados.</p>
                <a href="?module=agents" class="btn btn-secondary">Ver todos los agentes</a>
            <?php else: ?>
                <p>Aún no hay agentes registrados en el sistema.</p>
                <a href="?module=agents&action=create" class="btn btn-primary">Agregar Primer Agente</a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<script>
function toggleAgentStatus(agentId, newStatus) {
    const statusText = newStatus ? 'activar' : 'desactivar';

    if (confirm(`¿Está seguro de que desea ${statusText} este agente?`)) {
        // Use AJAX to update agent status
        const formData = new FormData();
        formData.append('action', 'toggle_status');
        formData.append('id', agentId);
        formData.append('status', newStatus);
        formData.append('ajax', 'true');
        formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

        fetch('index.php?module=agents', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Agente ${statusText}do correctamente`);
                window.location.reload();
            } else {
                alert('Error al actualizar el estado: ' + (data.error || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión al actualizar el estado');
        });
    }
}

// Auto-search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search');
    let searchTimeout;

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (this.value.length >= 3 || this.value.length === 0) {
                    this.form.submit();
                }
            }, 500);
        });
    }
});
</script>

<style>
.filter-form .form-actions {
    margin-top: var(--spacing-md);
    display: flex;
    gap: var(--spacing-sm);
}

.results-summary {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: var(--spacing-md) 0;
    padding: var(--spacing-sm) 0;
    border-bottom: 1px solid var(--border-color);
}

.no-results {
    text-align: center;
    padding: var(--spacing-xl);
}

.no-results h3 {
    color: var(--text-secondary);
    margin-bottom: var(--spacing-md);
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