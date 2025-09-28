<?php
/**
 * Rentals List View - Real Estate Management System
 * Display all rental agreements and their status
 * Educational PHP/MySQL Project
 */

// Prevent direct access
if (!defined('APP_ACCESS')) {
    die('Direct access not permitted');
}

// Get rentals from database
$rentals = [];
$searchTerm = $_GET['search'] ?? '';
$estadoFilter = $_GET['estado'] ?? '';
$mesFilter = $_GET['mes'] ?? '';

try {
    $db = new Database();
    $pdo = $db->getConnection();

    // Build WHERE clause for filters
    $whereConditions = [];
    $params = [];

    if (!empty($searchTerm)) {
        $whereConditions[] = "(a.observaciones LIKE ? OR cl.nombre LIKE ? OR cl.apellido LIKE ? OR i.direccion LIKE ?)";
        $searchWildcard = "%{$searchTerm}%";
        $params[] = $searchWildcard;
        $params[] = $searchWildcard;
        $params[] = $searchWildcard;
        $params[] = $searchWildcard;
    }

    if (!empty($estadoFilter)) {
        $whereConditions[] = "a.estado = ?";
        $params[] = $estadoFilter;
    }

    if (!empty($mesFilter)) {
        $whereConditions[] = "MONTH(a.fecha_fin) = ?";
        $params[] = $mesFilter;
    }

    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    // Get rentals with related data
    $sql = "SELECT a.id_arriendo, a.fecha_inicio, a.fecha_fin, a.canon_mensual, a.deposito,
                   a.estado, a.observaciones, a.created_at,
                   CONCAT('INM', LPAD(i.id_inmueble, 3, '0')) as inmueble_codigo,
                   CONCAT(i.tipo_inmueble, ' - ', i.direccion) as inmueble_descripcion,
                   CONCAT(cl.nombre, ' ', cl.apellido) as arrendatario_nombre,
                   ag.nombre as agente_nombre
            FROM arriendo a
            INNER JOIN inmueble i ON a.id_inmueble = i.id_inmueble
            INNER JOIN cliente cl ON a.id_cliente = cl.id_cliente
            LEFT JOIN agente ag ON a.id_agente = ag.id_agente
            {$whereClause}
            ORDER BY a.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rentals = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Error fetching rentals: " . $e->getMessage());
    $error = "Error al cargar los arriendos. Intente nuevamente.";
}
?>

<div class="module-header">
    <h2>Gestión de Arriendos</h2>
    <p class="module-description">
        Administre todos los contratos de arrendamiento y el seguimiento de pagos.
    </p>
</div>

<!-- Action Buttons -->
<div class="action-buttons">
    <a href="?module=rentals&action=create" class="btn btn-primary">
        + Registrar Nuevo Arriendo
    </a>
    <button type="button" class="btn btn-secondary" onclick="alert('Función en desarrollo')">
        Pagos Pendientes
    </button>
    <button type="button" class="btn btn-secondary" onclick="alert('Función en desarrollo')">
        Contratos por Vencer
    </button>
</div>

<!-- Search and Filter Section -->
<div class="card">
    <h3>Filtros de Búsqueda</h3>
    <form method="GET" class="filter-form">
        <input type="hidden" name="module" value="rentals">

        <div class="form-row">
            <div class="form-group">
                <label for="search">Buscar:</label>
                <input
                    type="text"
                    id="search"
                    name="search"
                    value="<?= htmlspecialchars($searchTerm) ?>"
                    placeholder="Arrendatario, dirección, observaciones..."
                    class="form-control"
                >
            </div>

            <div class="form-group">
                <label for="estado">Estado:</label>
                <select id="estado" name="estado" class="form-control">
                    <option value="">Todos los estados</option>
                    <option value="Activo" <?= $estadoFilter === 'Activo' ? 'selected' : '' ?>>Activo</option>
                    <option value="Vencido" <?= $estadoFilter === 'Vencido' ? 'selected' : '' ?>>Vencido</option>
                    <option value="Terminado" <?= $estadoFilter === 'Terminado' ? 'selected' : '' ?>>Terminado</option>
                    <option value="Moroso" <?= $estadoFilter === 'Moroso' ? 'selected' : '' ?>>Moroso</option>
                </select>
            </div>

            <div class="form-group">
                <label for="mes">Mes de Vencimiento:</label>
                <select id="mes" name="mes" class="form-control">
                    <option value="">Todos los meses</option>
                    <?php for ($i = 1; $i <= 12; $i++): ?>
                        <option value="<?= $i ?>" <?= $mesFilter == $i ? 'selected' : '' ?>>
                            <?= date('F', mktime(0, 0, 0, $i, 1)) ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Buscar</button>
            <a href="?module=rentals" class="btn btn-secondary">Limpiar Filtros</a>
        </div>
    </form>
</div>

<!-- Results Summary -->
<div class="results-summary">
    <p>
        Mostrando <?= count($rentals) ?> arriendos
        <?php if (!empty($searchTerm) || !empty($estadoFilter) || !empty($mesFilter)): ?>
            (filtrados)
        <?php endif; ?>
    </p>
</div>

<!-- Error Display -->
<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Rentals Table -->
<div class="card">
    <h3>Lista de Arriendos</h3>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Inmueble</th>
                    <th>Arrendatario</th>
                    <th>Agente</th>
                    <th>Canon Mensual</th>
                    <th>Fecha Inicio</th>
                    <th>Fecha Fin</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rentals as $rental): ?>
                    <tr>
                        <td>
                            <strong>ARR<?= str_pad($rental['id_arriendo'], 3, '0', STR_PAD_LEFT) ?></strong>
                        </td>
                        <td>
                            <span class="property-id"><?= htmlspecialchars($rental['inmueble_codigo']) ?></span>
                            <div class="property-description"><?= htmlspecialchars($rental['inmueble_descripcion']) ?></div>
                        </td>
                        <td><?= htmlspecialchars($rental['arrendatario_nombre']) ?></td>
                        <td>
                            <?php if ($rental['agente_nombre']): ?>
                                <?= htmlspecialchars($rental['agente_nombre']) ?>
                            <?php else: ?>
                                <span class="text-muted">Sin agente</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong class="price"><?= formatCurrency($rental['canon_mensual']) ?></strong>
                            <div class="deposit-info">
                                Depósito: <?= formatCurrency($rental['deposito']) ?>
                            </div>
                        </td>
                        <td><?= formatDate($rental['fecha_inicio']) ?></td>
                        <td>
                            <?= formatDate($rental['fecha_fin']) ?>
                            <?php
                            $daysLeft = ceil((strtotime($rental['fecha_fin']) - time()) / 86400);
                            if ($daysLeft <= 30 && $daysLeft > 0):
                            ?>
                                <div class="warning-text">
                                    Vence en <?= $daysLeft ?> días
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status <?= strtolower($rental['estado']) ?>">
                                <?= htmlspecialchars($rental['estado']) ?>
                            </span>
                        </td>
                        <td class="table-actions">
                            <a href="?module=rentals&action=view&id=<?= $rental['id_arriendo'] ?>"
                               class="btn btn-sm btn-info" title="Ver detalles">
                                Ver
                            </a>
                            <button type="button" class="btn btn-sm btn-success"
                                    onclick="alert('Registrar pago en desarrollo')" title="Pago">
                                Pago
                            </button>
                            <a href="?module=rentals&action=edit&id=<?= $rental['id_arriendo'] ?>"
                               class="btn btn-sm btn-secondary" title="Editar">
                                Editar
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Rentals Summary -->
<div class="card">
    <h3>Resumen de Arriendos</h3>
    <div class="stats-grid">
        <div class="stat-item">
            <span class="stat-label">Total Arriendos</span>
            <span class="stat-value"><?= count($rentals) ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">Arriendos Activos</span>
            <span class="stat-value"><?= count(array_filter($rentals, fn($r) => $r['estado'] === 'Activo')) ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">Ingresos Mensuales</span>
            <span class="stat-value"><?= formatCurrency(array_sum(array_column($rentals, 'canon_mensual'))) ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">Canon Promedio</span>
            <span class="stat-value"><?= formatCurrency(array_sum(array_column($rentals, 'canon_mensual')) / count($rentals)) ?></span>
        </div>
    </div>
</div>


<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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

.deposit-info {
    font-size: var(--font-size-xs);
    color: var(--text-secondary);
    margin-top: 2px;
}

.warning-text {
    font-size: var(--font-size-xs);
    color: #ff9800;
    font-weight: 500;
    margin-top: 2px;
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

.text-muted {
    color: var(--text-secondary);
    font-style: italic;
}

.results-summary {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: var(--spacing-md) 0;
    padding: var(--spacing-sm) 0;
    border-bottom: 1px solid var(--border-color);
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