<?php
/**
 * Contracts List View - Real Estate Management System
 * Display all contracts with their status
 * Educational PHP/MySQL Project
 */

// Prevent direct access
if (!defined('APP_ACCESS')) {
    die('Direct access not permitted');
}

// Get contracts from database
$contracts = [];
$searchTerm = $_GET['search'] ?? '';
$tipoFilter = $_GET['tipo'] ?? '';
$estadoFilter = $_GET['estado'] ?? '';

try {
    $db = new Database();
    $pdo = $db->getConnection();

    // Build WHERE clause for filters
    $whereConditions = [];
    $params = [];

    if (!empty($searchTerm)) {
        $whereConditions[] = "(c.observaciones LIKE ? OR cl.nombre LIKE ? OR cl.apellido LIKE ? OR i.direccion LIKE ?)";
        $searchWildcard = "%{$searchTerm}%";
        $params[] = $searchWildcard;
        $params[] = $searchWildcard;
        $params[] = $searchWildcard;
        $params[] = $searchWildcard;
    }

    if (!empty($tipoFilter)) {
        $whereConditions[] = "c.tipo_contrato = ?";
        $params[] = $tipoFilter;
    }

    if (!empty($estadoFilter)) {
        $whereConditions[] = "c.estado = ?";
        $params[] = $estadoFilter;
    }

    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    // Get contracts with related data
    $sql = "SELECT c.id_contrato, c.tipo_contrato, c.fecha_inicio, c.fecha_fin, c.valor_contrato,
                   c.estado, c.observaciones, c.archivo_contrato, c.created_at,
                   CONCAT('INM', LPAD(i.id_inmueble, 3, '0')) as inmueble_codigo,
                   CONCAT(i.tipo_inmueble, ' - ', i.direccion) as inmueble_descripcion,
                   CONCAT(cl.nombre, ' ', cl.apellido) as cliente_nombre,
                   a.nombre as agente_nombre
            FROM contrato c
            INNER JOIN inmueble i ON c.id_inmueble = i.id_inmueble
            INNER JOIN cliente cl ON c.id_cliente = cl.id_cliente
            LEFT JOIN agente a ON c.id_agente = a.id_agente
            {$whereClause}
            ORDER BY c.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $contracts = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Error fetching contracts: " . $e->getMessage());
    $error = "Error al cargar los contratos. Intente nuevamente.";
}
?>

<div class="module-header">
    <h2>Gestión de Contratos</h2>
    <p class="module-description">
        Administre todos los contratos de compraventa y arrendamiento.
    </p>
</div>

<!-- Action Buttons -->
<div class="action-buttons">
    <a href="?module=contracts&action=create" class="btn btn-primary">
        + Crear Nuevo Contrato
    </a>
    <button type="button" class="btn btn-secondary" onclick="alert('Función en desarrollo')">
        Generar Reporte
    </button>
    <button type="button" class="btn btn-secondary" onclick="alert('Función en desarrollo')">
        Contratos Vencidos
    </button>
</div>

<!-- Search and Filter Section -->
<div class="card">
    <h3>Filtros de Búsqueda</h3>
    <form method="GET" class="filter-form">
        <input type="hidden" name="module" value="contracts">

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
                <label for="tipo">Tipo de Contrato:</label>
                <select id="tipo" name="tipo" class="form-control">
                    <option value="">Todos los tipos</option>
                    <option value="Venta" <?= $tipoFilter === 'Venta' ? 'selected' : '' ?>>Venta</option>
                    <option value="Arriendo" <?= $tipoFilter === 'Arriendo' ? 'selected' : '' ?>>Arriendo</option>
                </select>
            </div>

            <div class="form-group">
                <label for="estado">Estado:</label>
                <select id="estado" name="estado" class="form-control">
                    <option value="">Todos los estados</option>
                    <option value="Borrador" <?= $estadoFilter === 'Borrador' ? 'selected' : '' ?>>Borrador</option>
                    <option value="Activo" <?= $estadoFilter === 'Activo' ? 'selected' : '' ?>>Activo</option>
                    <option value="Finalizado" <?= $estadoFilter === 'Finalizado' ? 'selected' : '' ?>>Finalizado</option>
                    <option value="Cancelado" <?= $estadoFilter === 'Cancelado' ? 'selected' : '' ?>>Cancelado</option>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Buscar</button>
            <a href="?module=contracts" class="btn btn-secondary">Limpiar Filtros</a>
        </div>
    </form>
</div>

<!-- Results Summary -->
<div class="results-summary">
    <p>
        Mostrando <?= count($contracts) ?> contratos
        <?php if (!empty($searchTerm) || !empty($tipoFilter) || !empty($estadoFilter)): ?>
            (filtrados)
        <?php endif; ?>
    </p>
</div>

<!-- Error Display -->
<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Contracts Table -->
<div class="card">
    <h3>Lista de Contratos</h3>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tipo</th>
                    <th>Inmueble</th>
                    <th>Cliente</th>
                    <th>Fecha Inicio</th>
                    <th>Fecha Fin</th>
                    <th>Valor</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($contracts as $contract): ?>
                    <tr>
                        <td>
                            <strong>CON<?= str_pad($contract['id_contrato'], 3, '0', STR_PAD_LEFT) ?></strong>
                        </td>
                        <td>
                            <span class="contract-type <?= strtolower($contract['tipo_contrato']) ?>">
                                <?= htmlspecialchars($contract['tipo_contrato']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="property-id"><?= htmlspecialchars($contract['inmueble_codigo']) ?></span>
                            <div class="property-description"><?= htmlspecialchars($contract['inmueble_descripcion']) ?></div>
                        </td>
                        <td><?= htmlspecialchars($contract['cliente_nombre']) ?></td>
                        <td><?= formatDate($contract['fecha_inicio']) ?></td>
                        <td>
                            <?php if ($contract['fecha_fin']): ?>
                                <?= formatDate($contract['fecha_fin']) ?>
                                <?php
                                $daysLeft = ceil((strtotime($contract['fecha_fin']) - time()) / 86400);
                                if ($daysLeft <= 30 && $daysLeft > 0 && $contract['estado'] === 'Activo'):
                                ?>
                                    <div class="warning-text">Vence en <?= $daysLeft ?> días</div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong class="price">
                                <?= formatCurrency($contract['valor_contrato']) ?>
                                <?php if ($contract['tipo_contrato'] === 'Arriendo'): ?>
                                    <small>/mes</small>
                                <?php endif; ?>
                            </strong>
                        </td>
                        <td>
                            <span class="status <?= strtolower($contract['estado']) ?>">
                                <?= htmlspecialchars($contract['estado']) ?>
                            </span>
                        </td>
                        <td class="table-actions">
                            <a href="?module=contracts&action=view&id=<?= $contract['id_contrato'] ?>"
                               class="btn btn-sm btn-info" title="Ver contrato">
                                Ver
                            </a>
                            <button type="button" class="btn btn-sm btn-secondary"
                                    onclick="alert('Descargar PDF en desarrollo')" title="Descargar">
                                PDF
                            </button>
                            <?php if ($contract['estado'] === 'Activo'): ?>
                                <a href="?module=contracts&action=edit&id=<?= $contract['id_contrato'] ?>"
                                   class="btn btn-sm btn-warning" title="Editar">
                                    Editar
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>


<style>
.contract-type {
    padding: 2px 6px;
    border-radius: var(--border-radius);
    font-size: var(--font-size-xs);
    font-weight: 500;
}

.contract-type.venta {
    background: #e3f2fd;
    color: #1976d2;
}

.contract-type.arriendo {
    background: #f3e5f5;
    color: #7b1fa2;
}

.property-id {
    background: var(--bg-secondary);
    padding: 2px 6px;
    border-radius: var(--border-radius);
    font-size: var(--font-size-xs);
    font-weight: 500;
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

.warning-text {
    font-size: var(--font-size-xs);
    color: #ff9800;
    font-weight: 500;
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