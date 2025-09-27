<?php
/**
 * Clients List View - Real Estate Management System
 * Display all clients with filtering and actions
 * Educational PHP/MySQL Project
 */

// Prevent direct access
if (!defined('APP_ACCESS')) {
    die('Direct access not permitted');
}

// Initialize variables
$clients = [];
$searchTerm = $_GET['search'] ?? '';
$typeFilter = $_GET['type'] ?? '';
$documentTypeFilter = $_GET['document_type'] ?? '';
$sortBy = $_GET['sort'] ?? 'created_at';
$sortOrder = $_GET['order'] ?? 'DESC';

// Pagination variables
$page = (int)($_GET['page'] ?? 1);
$page = max(1, $page);
$offset = ($page - 1) * RECORDS_PER_PAGE;

try {
    $db = new Database();
    $pdo = $db->getConnection();

    // Build the WHERE clause dynamically based on filters
    $whereConditions = [];
    $params = [];

    if (!empty($searchTerm)) {
        $whereConditions[] = "(nombre LIKE ? OR apellido LIKE ? OR correo LIKE ? OR nro_documento LIKE ? OR direccion LIKE ?)";
        $searchWildcard = "%{$searchTerm}%";
        $params = array_fill(0, 5, $searchWildcard);
    }

    if (!empty($typeFilter)) {
        $whereConditions[] = "tipo_cliente = ?";
        $params[] = $typeFilter;
    }

    if (!empty($documentTypeFilter)) {
        $whereConditions[] = "tipo_documento = ?";
        $params[] = $documentTypeFilter;
    }

    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    // Validate sort column to prevent SQL injection
    $allowedSortColumns = ['id_cliente', 'nombre', 'apellido', 'tipo_cliente', 'tipo_documento', 'correo', 'created_at'];
    if (!in_array($sortBy, $allowedSortColumns)) {
        $sortBy = 'created_at';
    }

    $sortOrder = (strtoupper($sortOrder) === 'ASC') ? 'ASC' : 'DESC';

    // Get total count for pagination
    $countSql = "SELECT COUNT(*) FROM cliente {$whereClause}";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetchColumn();
    $totalPages = ceil($totalRecords / RECORDS_PER_PAGE);

    // Get clients with pagination
    $sql = "SELECT * FROM cliente
            {$whereClause}
            ORDER BY {$sortBy} {$sortOrder}
            LIMIT ? OFFSET ?";

    $params[] = RECORDS_PER_PAGE;
    $params[] = $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $clients = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Error fetching clients: " . $e->getMessage());
    $error = "Error al cargar los clientes. Intente nuevamente.";
}
?>

<div class="module-header">
    <h2>Gestión de Clientes</h2>
    <p class="module-description">
        Administre la base de datos completa de clientes, compradores, vendedores y arrendatarios.
    </p>
</div>

<!-- Action Buttons -->
<div class="action-buttons">
    <a href="?module=clients&action=create" class="btn btn-primary">
        + Agregar Nuevo Cliente
    </a>
    <button type="button" class="btn btn-secondary" onclick="exportClients()">
        Exportar Lista
    </button>
    <button type="button" class="btn btn-secondary" onclick="printClients()">
        Imprimir Reporte
    </button>
    <button type="button" class="btn btn-info" onclick="importClients()">
        Importar Clientes
    </button>
</div>

<!-- Search and Filter Section -->
<div class="card">
    <h3>Filtros de Búsqueda</h3>

    <form method="GET" class="filter-form">
        <input type="hidden" name="module" value="clients">

        <div class="form-row">
            <div class="form-group">
                <label for="search">Buscar por nombre, documento, email o dirección:</label>
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
                <label for="type">Tipo de Cliente:</label>
                <select id="type" name="type" class="form-control">
                    <option value="">Todos los tipos</option>
                    <?php foreach (CLIENT_TYPES as $key => $label): ?>
                        <option value="<?= $key ?>" <?= $typeFilter === $key ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="document_type">Tipo de Documento:</label>
                <select id="document_type" name="document_type" class="form-control">
                    <option value="">Todos los documentos</option>
                    <?php foreach (DOCUMENT_TYPES as $key => $label): ?>
                        <option value="<?= $key ?>" <?= $documentTypeFilter === $key ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <!-- Reserved for future filters -->
                <label>&nbsp;</label>
                <div style="height: 38px;"></div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Buscar</button>
            <a href="?module=clients" class="btn btn-secondary">Limpiar Filtros</a>
        </div>
    </form>
</div>

<!-- Results Summary -->
<div class="results-summary">
    <p>
        Mostrando <?= count($clients) ?> de <?= $totalRecords ?> clientes
        <?php if (!empty($searchTerm) || !empty($typeFilter) || !empty($documentTypeFilter)): ?>
            (filtrados)
        <?php endif; ?>
    </p>

    <!-- Sort Options -->
    <div class="sort-options">
        <label>Ordenar por:</label>
        <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'nombre', 'order' => 'ASC'])) ?>"
           class="<?= $sortBy === 'nombre' && $sortOrder === 'ASC' ? 'active' : '' ?>">
            Nombre ↑
        </a>
        <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'nombre', 'order' => 'DESC'])) ?>"
           class="<?= $sortBy === 'nombre' && $sortOrder === 'DESC' ? 'active' : '' ?>">
            Nombre ↓
        </a>
        <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'created_at', 'order' => 'DESC'])) ?>"
           class="<?= $sortBy === 'created_at' && $sortOrder === 'DESC' ? 'active' : '' ?>">
            Más Recientes
        </a>
    </div>
</div>

<!-- Error Display -->
<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Clients Table -->
<?php if (!empty($clients)): ?>
    <div class="card">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre Completo</th>
                        <th>Tipo de Cliente</th>
                        <th>Documento</th>
                        <th>Email</th>
                        <th>Dirección</th>
                        <th>Fecha Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $client): ?>
                        <tr>
                            <td>
                                <strong>CLI<?= str_pad($client['id_cliente'], 3, '0', STR_PAD_LEFT) ?></strong>
                            </td>
                            <td>
                                <div class="client-name">
                                    <strong><?= htmlspecialchars($client['nombre'] . ' ' . $client['apellido']) ?></strong>
                                </div>
                            </td>
                            <td>
                                <span class="client-type <?= strtolower(str_replace(' ', '-', $client['tipo_cliente'])) ?>">
                                    <?= htmlspecialchars($client['tipo_cliente']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="document-info">
                                    <span class="document-type"><?= htmlspecialchars($client['tipo_documento']) ?></span>
                                    <span class="document-number"><?= htmlspecialchars($client['nro_documento']) ?></span>
                                </div>
                            </td>
                            <td>
                                <a href="mailto:<?= htmlspecialchars($client['correo']) ?>" class="email-link">
                                    <?= htmlspecialchars($client['correo']) ?>
                                </a>
                            </td>
                            <td>
                                <div class="address-info">
                                    <?= htmlspecialchars($client['direccion'] ?: 'No especificada') ?>
                                </div>
                            </td>
                            <td>
                                <?= formatDate($client['created_at']) ?>
                            </td>
                            <td class="table-actions">
                                <a href="?module=clients&action=view&id=<?= $client['id_cliente'] ?>"
                                   class="btn btn-sm btn-info" title="Ver detalles">
                                    Ver
                                </a>
                                <a href="?module=clients&action=edit&id=<?= $client['id_cliente'] ?>"
                                   class="btn btn-sm btn-secondary" title="Editar">
                                    Editar
                                </a>
                                <button type="button"
                                        class="btn btn-sm btn-warning"
                                        onclick="viewClientHistory(<?= $client['id_cliente'] ?>)"
                                        title="Ver historial">
                                    Historial
                                </button>
                                <button type="button"
                                        class="btn btn-sm btn-danger"
                                        onclick="confirmDelete(<?= $client['id_cliente'] ?>)"
                                        title="Eliminar">
                                    Eliminar
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php
            $baseUrl = '?' . http_build_query(array_merge($_GET, ['page' => '']));
            $baseUrl = rtrim($baseUrl, '=');
            ?>

            <?php if ($page > 1): ?>
                <a href="<?= $baseUrl ?>=1" class="pagination-link">« Primera</a>
                <a href="<?= $baseUrl ?>=<?= $page - 1 ?>" class="pagination-link">‹ Anterior</a>
            <?php endif; ?>

            <?php
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);

            for ($i = $start; $i <= $end; $i++):
            ?>
                <a href="<?= $baseUrl ?>=<?= $i ?>"
                   class="pagination-link <?= $i === $page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="<?= $baseUrl ?>=<?= $page + 1 ?>" class="pagination-link">Siguiente ›</a>
                <a href="<?= $baseUrl ?>=<?= $totalPages ?>" class="pagination-link">Última »</a>
            <?php endif; ?>
        </div>

        <div class="pagination-info">
            Página <?= $page ?> de <?= $totalPages ?>
            (<?= $totalRecords ?> registros en total)
        </div>
    <?php endif; ?>

<?php else: ?>
    <!-- No Results -->
    <div class="card">
        <div class="no-results">
            <h3>No se encontraron clientes</h3>
            <?php if (!empty($searchTerm) || !empty($typeFilter) || !empty($documentTypeFilter)): ?>
                <p>No hay clientes que coincidan con los filtros seleccionados.</p>
                <a href="?module=clients" class="btn btn-secondary">Ver todos los clientes</a>
            <?php else: ?>
                <p>Aún no hay clientes registrados en el sistema.</p>
                <a href="?module=clients&action=create" class="btn btn-primary">Agregar Primer Cliente</a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Client Statistics Card -->
<div class="card">
    <h3>Estadísticas de Clientes</h3>
    <div class="stats-grid">
        <?php
        try {
            // Get client statistics
            $statsQueries = [
                'total' => "SELECT COUNT(*) FROM cliente",
                'compradores' => "SELECT COUNT(*) FROM cliente WHERE tipo_cliente = 'Comprador'",
                'vendedores' => "SELECT COUNT(*) FROM cliente WHERE tipo_cliente = 'Vendedor'",
                'arrendatarios' => "SELECT COUNT(*) FROM cliente WHERE tipo_cliente = 'Arrendatario'",
                'arrendadores' => "SELECT COUNT(*) FROM cliente WHERE tipo_cliente = 'Arrendador'"
            ];

            $stats = [];
            foreach ($statsQueries as $key => $query) {
                $stmt = $pdo->prepare($query);
                $stmt->execute();
                $stats[$key] = $stmt->fetchColumn();
            }
        ?>
            <div class="stat-item">
                <div class="stat-number"><?= $stats['total'] ?></div>
                <div class="stat-label">Total Clientes</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?= $stats['compradores'] ?></div>
                <div class="stat-label">Compradores</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?= $stats['vendedores'] ?></div>
                <div class="stat-label">Vendedores</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?= $stats['arrendatarios'] ?></div>
                <div class="stat-label">Arrendatarios</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?= $stats['arrendadores'] ?></div>
                <div class="stat-label">Arrendadores</div>
            </div>
        <?php
        } catch (Exception $e) {
            echo '<p class="text-muted">Error al cargar estadísticas</p>';
        }
        ?>
    </div>
</div>

<!-- JavaScript for Enhanced Functionality -->
<script>
/**
 * Clients list functionality
 */

function confirmDelete(clientId) {
    if (confirm('¿Está seguro de que desea eliminar este cliente?\n\nEsta acción no se puede deshacer y puede afectar transacciones relacionadas.')) {
        deleteClient(clientId);
    }
}

async function deleteClient(clientId) {
    try {
        const response = await Ajax.clients.delete(clientId);

        if (response.success) {
            App.showSuccessMessage('Cliente eliminado correctamente');
            window.location.reload();
        } else {
            throw new Error(response.error || 'Error desconocido');
        }
    } catch (error) {
        console.error('Error:', error);
        App.showErrorMessage('Error al eliminar el cliente: ' + error.message);
    }
}

async function viewClientHistory(clientId) {
    try {
        // This would fetch client history from the server
        const content = `
            <div class="client-history">
                <p>Cargando historial del cliente...</p>
                <div class="loading"></div>
            </div>
        `;

        App.openModal(content, 'Historial del Cliente', `
            <button type="button" class="btn btn-secondary" data-modal-close>Cerrar</button>
        `);

        // TODO: Load actual client history data
        setTimeout(() => {
            const modalBody = document.getElementById('modal-body');
            if (modalBody) {
                modalBody.innerHTML = `
                    <div class="client-history">
                        <h4>Historial de Transacciones</h4>
                        <p>Esta funcionalidad mostrará:</p>
                        <ul>
                            <li>Propiedades visitadas</li>
                            <li>Contratos firmados</li>
                            <li>Ventas realizadas</li>
                            <li>Arriendos activos</li>
                        </ul>
                        <p><em>Implementación pendiente en próximas versiones.</em></p>
                    </div>
                `;
            }
        }, 1500);

    } catch (error) {
        console.error('Error:', error);
        App.showErrorMessage('Error al cargar el historial: ' + error.message);
    }
}

function exportClients() {
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('export', 'excel');
    window.open(currentUrl.toString(), '_blank');
}

function printClients() {
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('print', 'true');
    window.open(currentUrl.toString(), '_blank');
}

function importClients() {
    const content = `
        <div class="import-form">
            <p>Importe clientes desde un archivo Excel o CSV.</p>
            <div class="form-group">
                <label for="import-file">Seleccione el archivo:</label>
                <input type="file" id="import-file" accept=".xlsx,.xls,.csv" class="form-control">
            </div>
            <div class="field-help">
                El archivo debe contener las columnas: Nombre, Apellido, Tipo Documento, Número Documento, Email, Dirección, Tipo Cliente
            </div>
        </div>
    `;

    const footer = `
        <button type="button" class="btn btn-primary" onclick="processImport()">Importar</button>
        <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
    `;

    App.openModal(content, 'Importar Clientes', footer);
}

function processImport() {
    const fileInput = document.getElementById('import-file');
    const file = fileInput.files[0];

    if (!file) {
        alert('Seleccione un archivo para importar');
        return;
    }

    // TODO: Implement actual file processing
    App.showSuccessMessage('Funcionalidad de importación pendiente de implementación');
    App.closeModal();
}

// Real-time search functionality
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

    // Email link handling
    document.querySelectorAll('.email-link').forEach(link => {
        link.addEventListener('click', function(e) {
            // Optional: Track email interactions
            console.log('Email clicked:', this.href);
        });
    });
});
</script>

<style>
/* Additional styles specific to clients list */
.client-name {
    font-weight: 600;
    color: var(--primary-color);
}

.client-type {
    display: inline-block;
    padding: 4px 8px;
    border-radius: var(--border-radius);
    font-size: var(--font-size-xs);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.client-type.comprador {
    background: #d4edda;
    color: #155724;
}

.client-type.vendedor {
    background: #d1ecf1;
    color: #0c5460;
}

.client-type.arrendatario {
    background: #fff3cd;
    color: #856404;
}

.client-type.arrendador {
    background: #f8d7da;
    color: #721c24;
}

.document-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.document-type {
    font-size: var(--font-size-xs);
    color: var(--text-secondary);
    font-weight: 500;
}

.document-number {
    font-family: monospace;
    font-weight: 600;
}

.email-link {
    color: var(--secondary-color);
    text-decoration: none;
    font-size: var(--font-size-sm);
}

.email-link:hover {
    text-decoration: underline;
}

.address-info {
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
    gap: var(--spacing-lg);
    margin-top: var(--spacing-md);
}

.stat-item {
    text-align: center;
    padding: var(--spacing-md);
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
    border: 1px solid var(--border-color);
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: var(--primary-color);
    margin-bottom: var(--spacing-xs);
}

.stat-label {
    font-size: var(--font-size-sm);
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.import-form {
    padding: var(--spacing-md);
}

.client-history {
    padding: var(--spacing-md);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .address-info {
        max-width: 120px;
    }

    .table-actions {
        min-width: 120px;
    }

    .table-actions .btn {
        margin-bottom: 4px;
        font-size: var(--font-size-xs);
        padding: 4px 8px;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }

    .table th,
    .table td {
        padding: 6px;
        font-size: var(--font-size-xs);
    }
}
</style>