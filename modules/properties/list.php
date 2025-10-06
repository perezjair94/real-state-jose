<?php
/**
 * Properties List View - Real Estate Management System
 * Display all properties with filtering and actions
 * Educational PHP/MySQL Project
 */

// Prevent direct access
if (!defined('APP_ACCESS')) {
    die('Direct access not permitted');
}

// Initialize variables
$properties = [];
$searchTerm = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$typeFilter = $_GET['type'] ?? '';
$cityFilter = $_GET['city'] ?? '';
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
        $whereConditions[] = "(direccion LIKE ? OR descripcion LIKE ? OR ciudad LIKE ?)";
        $searchWildcard = "%{$searchTerm}%";
        $params[] = $searchWildcard;
        $params[] = $searchWildcard;
        $params[] = $searchWildcard;
    }

    if (!empty($statusFilter)) {
        $whereConditions[] = "estado = ?";
        $params[] = $statusFilter;
    }

    if (!empty($typeFilter)) {
        $whereConditions[] = "tipo_inmueble = ?";
        $params[] = $typeFilter;
    }

    if (!empty($cityFilter)) {
        $whereConditions[] = "ciudad = ?";
        $params[] = $cityFilter;
    }

    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    // Validate sort column to prevent SQL injection
    $allowedSortColumns = ['id_inmueble', 'tipo_inmueble', 'direccion', 'ciudad', 'precio', 'estado', 'created_at'];
    if (!in_array($sortBy, $allowedSortColumns)) {
        $sortBy = 'created_at';
    }

    $sortOrder = (strtoupper($sortOrder) === 'ASC') ? 'ASC' : 'DESC';

    // Get total count for pagination
    $countSql = "SELECT COUNT(*) FROM inmueble {$whereClause}";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetchColumn();
    $totalPages = ceil($totalRecords / RECORDS_PER_PAGE);

    // Get properties with pagination
    $sql = "SELECT * FROM inmueble
            {$whereClause}
            ORDER BY {$sortBy} {$sortOrder}
            LIMIT ? OFFSET ?";

    $params[] = RECORDS_PER_PAGE;
    $params[] = $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $properties = $stmt->fetchAll();

    // Get unique cities for filter dropdown
    $citiesStmt = $pdo->query("SELECT DISTINCT ciudad FROM inmueble ORDER BY ciudad");
    $cities = $citiesStmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    error_log("Error fetching properties: " . $e->getMessage());
    $error = "Error al cargar las propiedades. Intente nuevamente.";
}
?>

<div class="module-header">
    <h2>Gestión de Inmuebles</h2>
    <p class="module-description">
        Administre el catálogo completo de propiedades disponibles para venta y arriendo.
    </p>
</div>

<!-- Action Buttons -->
<div class="action-buttons">
    <a href="?module=properties&action=create" class="btn btn-primary">
        + Agregar Nueva Propiedad
    </a>
    <button type="button" class="btn btn-secondary" onclick="exportProperties()">
        Exportar Lista
    </button>
    <button type="button" class="btn btn-secondary" onclick="printProperties()">
        Imprimir Reporte
    </button>
</div>

<!-- Search and Filter Section -->
<div class="card">
    <h3>Filtros de Búsqueda</h3>

    <form method="GET" class="filter-form">
        <input type="hidden" name="module" value="properties">

        <div class="form-row">
            <div class="form-group">
                <label for="search">Buscar por dirección, descripción o ciudad:</label>
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
                <label for="status">Estado:</label>
                <select id="status" name="status" class="form-control">
                    <option value="">Todos los estados</option>
                    <?php foreach (PROPERTY_STATUS as $key => $label): ?>
                        <option value="<?= $key ?>" <?= $statusFilter === $key ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="type">Tipo de Inmueble:</label>
                <select id="type" name="type" class="form-control">
                    <option value="">Todos los tipos</option>
                    <?php foreach (PROPERTY_TYPES as $key => $label): ?>
                        <option value="<?= $key ?>" <?= $typeFilter === $key ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="city">Ciudad:</label>
                <select id="city" name="city" class="form-control">
                    <option value="">Todas las ciudades</option>
                    <?php foreach ($cities as $city): ?>
                        <option value="<?= htmlspecialchars($city) ?>" <?= $cityFilter === $city ? 'selected' : '' ?>>
                            <?= htmlspecialchars($city) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Buscar</button>
            <a href="?module=properties" class="btn btn-secondary">Limpiar Filtros</a>
        </div>
    </form>
</div>

<!-- Results Summary -->
<div class="results-summary">
    <p>
        Mostrando <?= count($properties) ?> de <?= $totalRecords ?> propiedades
        <?php if (!empty($searchTerm) || !empty($statusFilter) || !empty($typeFilter) || !empty($cityFilter)): ?>
            (filtradas)
        <?php endif; ?>
    </p>

    <!-- Sort Options -->
    <div class="sort-options">
        <label>Ordenar por:</label>
        <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'precio', 'order' => 'ASC'])) ?>"
           class="<?= $sortBy === 'precio' && $sortOrder === 'ASC' ? 'active' : '' ?>">
            Precio ↑
        </a>
        <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'precio', 'order' => 'DESC'])) ?>"
           class="<?= $sortBy === 'precio' && $sortOrder === 'DESC' ? 'active' : '' ?>">
            Precio ↓
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

<!-- Properties Display -->
<?php if (!empty($properties)): ?>
    <!-- View Toggle -->
    <div class="view-toggle">
        <button onclick="toggleView('cards')" id="view-cards-btn" class="btn btn-secondary">
            <i class="fas fa-th-large"></i>
            <span>Vista Tarjetas</span>
        </button>
        <button onclick="toggleView('table')" id="view-table-btn" class="btn btn-secondary">
            <i class="fas fa-table"></i>
            <span>Vista Tabla</span>
        </button>
    </div>

    <!-- Properties Cards View (New Modern Design) -->
    <div id="cards-view" class="card" style="display: none;">
        <h3>Propiedades Disponibles</h3>
        <div class="property-list">
            <?php foreach ($properties as $property): ?>
            <div class="property-card">
                <div class="image-container">
                    <?php
                    // Get property image from JSON or use default based on property type
                    $fotos = json_decode($property['fotos'] ?? '[]', true);
                    $hasCustomPhoto = false;

                    if (!empty($fotos) && isset($fotos[0])) {
                        // Use uploaded photo
                        $imageSrc = 'assets/uploads/properties/' . htmlspecialchars($fotos[0]);
                        $hasCustomPhoto = true;
                    } else {
                        // Use default image based on property type or rotation
                        $defaultImages = ['img/casa1.jpeg', 'img/casa2.jpg', 'img/casa3.jpeg'];
                        $imageIndex = $property['id_inmueble'] % count($defaultImages);
                        $imageSrc = $defaultImages[$imageIndex];
                    }
                    ?>
                    <img src="<?= $imageSrc ?>"
                         alt="<?= htmlspecialchars($property['tipo_inmueble']) ?> en <?= htmlspecialchars($property['ciudad']) ?>"
                         onerror="this.src='img/casa1.jpeg'">
                    <?php if ($hasCustomPhoto && count($fotos) > 1): ?>
                        <span class="photo-count">
                            <i class="fa fa-camera"></i> <?= count($fotos) ?>
                        </span>
                    <?php endif; ?>
                    <span class="tag <?= $property['estado'] === 'Vendido' ? 'tag-compra' : 'tag-renta' ?>">
                        <?= htmlspecialchars(strtoupper($property['estado'])) ?>
                    </span>
                </div>
                <div class="card-body">
                    <h3 class="title"><?= htmlspecialchars($property['ciudad']) ?></h3>
                    <p class="location"><?= htmlspecialchars($property['direccion']) ?></p>
                    <p class="price"><?= formatCurrency($property['precio']) ?></p>
                    <hr>
                    <div class="details">
                        <div class="detail-item">
                            <i class="fa-solid fa-bed"></i>
                            <p>Habitaciones</p>
                            <span><?= $property['habitaciones'] ?: '0' ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="fa-solid fa-bath"></i>
                            <p>Baños</p>
                            <span><?= $property['banos'] ?: '0' ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="fa-solid fa-ruler-combined"></i>
                            <p>M²</p>
                            <span><?= $property['area_construida'] ? number_format($property['area_construida'], 0) : 'N/A' ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="fa-solid fa-home"></i>
                            <p>Tipo</p>
                            <span><?= htmlspecialchars(substr($property['tipo_inmueble'], 0, 4)) ?></span>
                        </div>
                    </div>
                    <div style="margin-top: 15px; text-align: center;">
                        <a href="?module=properties&action=view&id=<?= $property['id_inmueble'] ?>" class="btn btn-small">Ver Detalles</a>
                        <a href="?module=properties&action=edit&id=<?= $property['id_inmueble'] ?>" class="btn btn-small btn-secondary">Editar</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Properties Table (Original View) -->
    <div id="table-view" class="card">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tipo</th>
                        <th>Dirección</th>
                        <th>Ciudad</th>
                        <th>Precio</th>
                        <th>Estado</th>
                        <th>Área (m²)</th>
                        <th>Habitaciones</th>
                        <th>Baños</th>
                        <th>Fecha Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($properties as $property): ?>
                        <tr>
                            <td>
                                <strong class="property-id">INM<?= str_pad($property['id_inmueble'], 3, '0', STR_PAD_LEFT) ?></strong>
                            </td>
                            <td>
                                <span class="property-type"><?= htmlspecialchars($property['tipo_inmueble']) ?></span>
                            </td>
                            <td>
                                <div class="property-address">
                                    <?= htmlspecialchars($property['direccion']) ?>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($property['ciudad']) ?></td>
                            <td>
                                <strong class="price"><?= formatCurrency($property['precio']) ?></strong>
                            </td>
                            <td>
                                <span class="status <?= strtolower($property['estado']) ?>">
                                    <?= htmlspecialchars($property['estado']) ?>
                                </span>
                            </td>
                            <td>
                                <?= $property['area_construida'] ? number_format($property['area_construida'], 1) : 'N/A' ?>
                            </td>
                            <td>
                                <?= $property['habitaciones'] ?: 'N/A' ?>
                            </td>
                            <td>
                                <?= $property['banos'] ?: 'N/A' ?>
                            </td>
                            <td>
                                <?= formatDate($property['created_at']) ?>
                            </td>
                            <td class="table-actions">
                                <a href="?module=properties&action=view&id=<?= $property['id_inmueble'] ?>"
                                   class="btn btn-sm btn-info" title="Ver detalles">
                                    Ver
                                </a>
                                <a href="?module=properties&action=edit&id=<?= $property['id_inmueble'] ?>"
                                   class="btn btn-sm btn-secondary" title="Editar">
                                    Editar
                                </a>
                                <?php if ($property['estado'] === 'Disponible'): ?>
                                    <button type="button"
                                            class="btn btn-sm btn-danger"
                                            onclick="confirmDelete(<?= $property['id_inmueble'] ?>)"
                                            title="Eliminar">
                                        Eliminar
                                    </button>
                                <?php endif; ?>
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
            // Build base URL for pagination
            $baseUrl = '?' . http_build_query(array_merge($_GET, ['page' => '']));
            $baseUrl = rtrim($baseUrl, '='); // Remove trailing = from page parameter
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
    </div>
    <!-- End Table View -->

<?php else: ?>
    <!-- No Results -->
    <div class="card">
        <div class="no-results">
            <h3>No se encontraron propiedades</h3>
            <?php if (!empty($searchTerm) || !empty($statusFilter) || !empty($typeFilter) || !empty($cityFilter)): ?>
                <p>No hay propiedades que coincidan con los filtros seleccionados.</p>
                <a href="?module=properties" class="btn btn-secondary">Ver todas las propiedades</a>
            <?php else: ?>
                <p>Aún no hay propiedades registradas en el sistema.</p>
                <a href="?module=properties&action=create" class="btn btn-primary">Agregar Primera Propiedad</a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- JavaScript for view toggle -->
<script>
function toggleView(view) {
    const cardsView = document.getElementById('cards-view');
    const tableView = document.getElementById('table-view');
    const cardsBtn = document.getElementById('view-cards-btn');
    const tableBtn = document.getElementById('view-table-btn');

    if (view === 'cards') {
        // Show cards view, hide table view
        cardsView.style.display = 'block';
        tableView.style.display = 'none';

        // Cards button active (remove btn-secondary)
        cardsBtn.classList.remove('btn-secondary');

        // Table button inactive (add btn-secondary)
        tableBtn.classList.add('btn-secondary');

        localStorage.setItem('propertyView', 'cards');
    } else {
        // Show table view, hide cards view
        cardsView.style.display = 'none';
        tableView.style.display = 'block';

        // Table button active (remove btn-secondary)
        tableBtn.classList.remove('btn-secondary');

        // Cards button inactive (add btn-secondary)
        cardsBtn.classList.add('btn-secondary');

        localStorage.setItem('propertyView', 'table');
    }
}

// Load saved view preference
document.addEventListener('DOMContentLoaded', function() {
    const savedView = localStorage.getItem('propertyView') || 'table';
    toggleView(savedView);
});
</script>

<!-- Educational JavaScript Section -->
<script>
/**
 * Educational Note: JavaScript functions for property management
 * These functions enhance user experience with AJAX and interactive features
 */

async function confirmDelete(propertyId) {
    const formattedId = 'INM' + String(propertyId).padStart(3, '0');

    if (!confirm(`¿Está seguro de que desea eliminar la propiedad ${formattedId}?\n\nEsta acción no se puede deshacer.`)) {
        return;
    }

    try {
        console.log('Eliminando propiedad:', propertyId);

        // Show loading state
        const deleteButtons = document.querySelectorAll(`button[onclick*="confirmDelete(${propertyId})"]`);
        deleteButtons.forEach(btn => {
            btn.disabled = true;
            btn.innerHTML = 'Eliminando...';
        });

        const response = await Ajax.properties.delete(propertyId);
        console.log('Respuesta del servidor:', response);

        if (response.success) {
            if (typeof App !== 'undefined' && App.showSuccessMessage) {
                App.showSuccessMessage(response.data?.message || 'Propiedad eliminada correctamente');
            } else {
                alert('Propiedad eliminada correctamente');
            }

            // Refresh the page after a short delay
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            throw new Error(response.error || 'Error desconocido');
        }
    } catch (error) {
        console.error('Error al eliminar propiedad:', error);

        if (typeof App !== 'undefined' && App.showErrorMessage) {
            App.showErrorMessage('Error al eliminar la propiedad: ' + error.message);
        } else {
            alert('Error al eliminar la propiedad: ' + error.message);
        }

        // Restore buttons on error
        const deleteButtons = document.querySelectorAll(`button[onclick*="confirmDelete(${propertyId})"]`);
        deleteButtons.forEach(btn => {
            btn.disabled = false;
            btn.innerHTML = 'Eliminar';
        });
    }
}

function exportProperties() {
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('export', 'excel');
    window.open(currentUrl.toString(), '_blank');
}

function printProperties() {
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('print', 'true');
    window.open(currentUrl.toString(), '_blank');
}

// Educational comment: Real-time search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search');
    let searchTimeout;

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                // Auto-submit form after 500ms of no typing
                if (this.value.length >= 3 || this.value.length === 0) {
                    this.form.submit();
                }
            }, 500);
        });
    }
});
</script>

<style>
/* Additional styles specific to properties list */
.module-header {
    margin-bottom: var(--spacing-lg);
    text-align: center;
}

.photo-count {
    position: absolute;
    bottom: 10px;
    left: 10px;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    display: flex;
    align-items: center;
    gap: 4px;
}

.module-description {
    color: var(--text-secondary);
    font-size: var(--font-size-sm);
    margin-top: var(--spacing-xs);
}

.action-buttons {
    display: flex;
    gap: var(--spacing-sm);
    margin-bottom: var(--spacing-lg);
    flex-wrap: wrap;
}

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
    flex-wrap: wrap;
    gap: var(--spacing-sm);
}

.sort-options {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    font-size: var(--font-size-sm);
}

.sort-options label {
    font-weight: 600;
    margin-right: var(--spacing-xs);
}

.sort-options a {
    color: var(--secondary-color);
    text-decoration: none;
    padding: 4px 8px;
    border-radius: var(--border-radius);
    font-size: var(--font-size-xs);
}

.sort-options a:hover,
.sort-options a.active {
    background: var(--secondary-color);
    color: white;
}

.property-type {
    background: var(--bg-secondary);
    padding: 2px 6px;
    border-radius: var(--border-radius);
    font-size: var(--font-size-xs);
    font-weight: 500;
}

.property-address {
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.price {
    color: var(--accent-color);
    font-size: var(--font-size-sm);
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: var(--spacing-xs);
    margin: var(--spacing-lg) 0;
    flex-wrap: wrap;
}

.pagination-link {
    padding: 8px 12px;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    color: var(--secondary-color);
    text-decoration: none;
    font-size: var(--font-size-sm);
    transition: all var(--transition-fast);
}

.pagination-link:hover,
.pagination-link.active {
    background: var(--secondary-color);
    color: white;
    border-color: var(--secondary-color);
}

.pagination-info {
    text-align: center;
    color: var(--text-secondary);
    font-size: var(--font-size-sm);
    margin-top: var(--spacing-sm);
}

.no-results {
    text-align: center;
    padding: var(--spacing-xl);
}

.no-results h3 {
    color: var(--text-secondary);
    margin-bottom: var(--spacing-md);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .results-summary {
        flex-direction: column;
        align-items: flex-start;
    }

    .sort-options {
        flex-wrap: wrap;
    }

    .action-buttons {
        justify-content: center;
    }

    .table-container {
        overflow-x: auto;
    }

    .property-address {
        max-width: 150px;
    }
}
</style>