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
$priceMin = $_GET['price_min'] ?? '';
$priceMax = $_GET['price_max'] ?? '';
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
        $whereConditions[] = "(direccion LIKE ? OR descripcion LIKE ?)";
        $searchWildcard = "%{$searchTerm}%";
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

    if (!empty($priceMin)) {
        $whereConditions[] = "precio >= ?";
        $params[] = (float)$priceMin;
    }

    if (!empty($priceMax)) {
        $whereConditions[] = "precio <= ?";
        $params[] = (float)$priceMax;
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
                <label for="search">Buscar por dirección o descripción:</label>
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
                <label>Rango de Precio:</label>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <input
                        type="number"
                        id="price_min"
                        name="price_min"
                        value="<?= htmlspecialchars($priceMin) ?>"
                        placeholder="Mínimo"
                        class="form-control"
                        min="0"
                        step="1000000"
                    >
                    <span style="color: var(--text-secondary);">hasta</span>
                    <input
                        type="number"
                        id="price_max"
                        name="price_max"
                        value="<?= htmlspecialchars($priceMax) ?>"
                        placeholder="Máximo"
                        class="form-control"
                        min="0"
                        step="1000000"
                    >
                </div>
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
        <?php if (!empty($searchTerm) || !empty($statusFilter) || !empty($typeFilter) || !empty($priceMin) || !empty($priceMax)): ?>
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
                    // Get property images from JSON or use default based on property rotation
                    $fotos = null;
                    $hasCustomPhoto = false;
                    $allImages = [];

                    // Safely decode JSON photos
                    if (!empty($property['fotos']) && $property['fotos'] !== 'null') {
                        $fotos = json_decode($property['fotos'], true);
                    }

                    // Build images array
                    if (is_array($fotos) && !empty($fotos)) {
                        foreach ($fotos as $foto) {
                            if (!empty($foto)) {
                                // Check if it's a custom uploaded photo or default image
                                if (strpos($foto, 'img/') === 0 || strpos($foto, 'casa') !== false) {
                                    // Default image from img/ folder
                                    $imagePath = (strpos($foto, 'img/') === 0) ? BASE_URL . $foto : BASE_URL . 'img/' . $foto;
                                } else {
                                    // Custom uploaded photo - use UPLOADS_URL constant
                                    $imagePath = UPLOADS_URL . 'properties/' . $foto;
                                }
                                $allImages[] = $imagePath;
                            }
                        }
                        $hasCustomPhoto = !empty($allImages);
                    }

                    // If no custom photos, use default images
                    if (empty($allImages)) {
                        $defaultImages = [
                            BASE_URL . 'img/casa1.jpeg',
                            BASE_URL . 'img/casa2.jpg',
                            BASE_URL . 'img/casa3.jpeg'
                        ];
                        // Add all default images so carousel works in cards too
                        $allImages = $defaultImages;
                    }

                    // Determine tag class based on property status
                    $tagClass = 'tag-disponible'; // Default for available
                    if ($property['estado'] === 'Vendido') {
                        $tagClass = 'tag-compra';
                    } elseif ($property['estado'] === 'Arrendado') {
                        $tagClass = 'tag-renta';
                    } elseif ($property['estado'] === 'Disponible') {
                        $tagClass = 'tag-disponible';
                    }
                    ?>

                    <!-- Image Gallery (Simple - like client detail page) -->
                    <div class="image-gallery" id="gallery-<?= $property['id_inmueble'] ?>">
                        <img id="gallery-img-<?= $property['id_inmueble'] ?>"
                             src="<?= htmlspecialchars($allImages[0]) ?>"
                             alt="<?= htmlspecialchars($property['tipo_inmueble']) ?> en <?= htmlspecialchars($property['ciudad']) ?>"
                             onerror="this.src='<?= BASE_URL ?>img/casa1.jpeg'">

                        <?php if (count($allImages) > 1): ?>
                            <button class="gallery-nav prev" onclick="changeCardImage(<?= $property['id_inmueble'] ?>, -1)" type="button">‹</button>
                            <button class="gallery-nav next" onclick="changeCardImage(<?= $property['id_inmueble'] ?>, 1)" type="button">›</button>

                            <div class="gallery-controls">
                                <?php for ($i = 0; $i < count($allImages); $i++): ?>
                                    <div class="gallery-dot <?= $i === 0 ? 'active' : '' ?>"
                                         onclick="showCardImage(<?= $property['id_inmueble'] ?>, <?= $i ?>)"></div>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <script>
                        // Inline gallery images for card <?= $property['id_inmueble'] ?>
                        window.galleryImages_<?= $property['id_inmueble'] ?> = <?= json_encode($allImages) ?>;
                        window.currentIndex_<?= $property['id_inmueble'] ?> = 0;
                    </script>

                    <span class="tag <?= $tagClass ?>">
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
                    <div style="margin-top: 15px; text-align: center; display: flex; gap: 8px; justify-content: center; flex-wrap: wrap;">
                        <a href="?module=properties&action=view&id=<?= $property['id_inmueble'] ?>" class="btn btn-small">Ver Detalles</a>
                        <a href="?module=properties&action=edit&id=<?= $property['id_inmueble'] ?>" class="btn btn-small btn-secondary">Editar</a>
                        <?php if ($property['estado'] === 'Disponible'): ?>
                            <button type="button"
                                    class="btn btn-small btn-danger"
                                    onclick="confirmDelete(<?= $property['id_inmueble'] ?>)"
                                    title="Eliminar">
                                Eliminar
                            </button>
                        <?php endif; ?>
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
                        <th>Imagen</th>
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
                        <?php
                        // Prepare images for table row
                        $fotos_table = null;
                        $allImages_table = [];

                        if (!empty($property['fotos']) && $property['fotos'] !== 'null') {
                            $fotos_table = json_decode($property['fotos'], true);
                        }

                        if (is_array($fotos_table) && !empty($fotos_table)) {
                            foreach ($fotos_table as $foto) {
                                if (!empty($foto)) {
                                    // Check if it's a custom uploaded photo or default image
                                    if (strpos($foto, 'img/') === 0 || strpos($foto, 'casa') !== false) {
                                        // Default image from img/ folder
                                        $imagePath = (strpos($foto, 'img/') === 0) ? BASE_URL . $foto : BASE_URL . 'img/' . $foto;
                                    } else {
                                        // Custom uploaded photo - use UPLOADS_URL constant
                                        $imagePath = UPLOADS_URL . 'properties/' . $foto;
                                    }
                                    $allImages_table[] = $imagePath;
                                }
                            }
                        }

                        if (empty($allImages_table)) {
                            $defaultImages = [
                                BASE_URL . 'img/casa1.jpeg',
                                BASE_URL . 'img/casa2.jpg',
                                BASE_URL . 'img/casa3.jpeg'
                            ];
                            // Add all default images so carousel works in table too
                            $allImages_table = $defaultImages;
                        }
                        ?>
                        <tr>
                            <td class="table-image-cell">
                                <div class="table-image-gallery" id="table-gallery-<?= $property['id_inmueble'] ?>">
                                    <img id="table-gallery-img-<?= $property['id_inmueble'] ?>"
                                         src="<?= htmlspecialchars($allImages_table[0]) ?>"
                                         alt="Propiedad <?= $property['id_inmueble'] ?>"
                                         onerror="this.src='<?= BASE_URL ?>img/casa1.jpeg'">

                                    <?php if (count($allImages_table) > 1): ?>
                                        <button class="table-gallery-nav prev"
                                                onclick="changeTableImage(<?= $property['id_inmueble'] ?>, -1)" type="button">
                                            ‹
                                        </button>
                                        <button class="table-gallery-nav next"
                                                onclick="changeTableImage(<?= $property['id_inmueble'] ?>, 1)" type="button">
                                            ›
                                        </button>

                                        <div class="table-gallery-controls">
                                            <?php for ($i = 0; $i < count($allImages_table); $i++): ?>
                                                <div class="table-gallery-dot <?= $i === 0 ? 'active' : '' ?>"
                                                     onclick="showTableImage(<?= $property['id_inmueble'] ?>, <?= $i ?>)"></div>
                                            <?php endfor; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
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

            <!-- Initialize table gallery images -->
            <script>
                <?php
                // Re-fetch properties to initialize table gallery images
                try {
                    $db = new Database();
                    $pdo = $db->getConnection();

                    // Build the WHERE clause (same as above)
                    $whereConditions = [];
                    $params = [];

                    if (!empty($searchTerm)) {
                        $whereConditions[] = "(direccion LIKE ? OR descripcion LIKE ?)";
                        $searchWildcard = "%{$searchTerm}%";
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

                    if (!empty($priceMin)) {
                        $whereConditions[] = "precio >= ?";
                        $params[] = (float)$priceMin;
                    }

                    if (!empty($priceMax)) {
                        $whereConditions[] = "precio <= ?";
                        $params[] = (float)$priceMax;
                    }

                    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

                    // Get properties for table gallery initialization
                    $sql = "SELECT id_inmueble, fotos FROM inmueble
                            {$whereClause}
                            ORDER BY {$sortBy} {$sortOrder}
                            LIMIT ? OFFSET ?";

                    $params[] = RECORDS_PER_PAGE;
                    $params[] = $offset;

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $tableProperties = $stmt->fetchAll();

                    // Initialize each property's table gallery images
                    foreach ($tableProperties as $prop):
                        $fotos_table = null;
                        $allImages_table = [];

                        if (!empty($prop['fotos']) && $prop['fotos'] !== 'null') {
                            $fotos_table = json_decode($prop['fotos'], true);
                        }

                        if (is_array($fotos_table) && !empty($fotos_table)) {
                            foreach ($fotos_table as $foto) {
                                if (!empty($foto)) {
                                    if (strpos($foto, 'img/') === 0 || strpos($foto, 'casa') !== false) {
                                        $imagePath = (strpos($foto, 'img/') === 0) ? BASE_URL . $foto : BASE_URL . 'img/' . $foto;
                                    } else {
                                        $imagePath = UPLOADS_URL . 'properties/' . $foto;
                                    }
                                    $allImages_table[] = $imagePath;
                                }
                            }
                        }

                        if (empty($allImages_table)) {
                            $defaultImages = [
                                BASE_URL . 'img/casa1.jpeg',
                                BASE_URL . 'img/casa2.jpg',
                                BASE_URL . 'img/casa3.jpeg'
                            ];
                            $allImages_table = $defaultImages;
                        }
                ?>
                        window.tableGalleryImages_<?= $prop['id_inmueble'] ?> = <?= json_encode($allImages_table) ?>;
                <?php
                    endforeach;
                } catch (Exception $e) {
                ?>
                    console.error('Error initializing table galleries:', '<?= $e->getMessage() ?>');
                <?php
                }
                ?>
            </script>
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
            <?php if (!empty($searchTerm) || !empty($statusFilter) || !empty($typeFilter) || !empty($priceMin) || !empty($priceMax)): ?>
                <p>No hay propiedades que coincidan con los filtros seleccionados.</p>
                <a href="?module=properties" class="btn btn-secondary">Ver todas las propiedades</a>
            <?php else: ?>
                <p>Aún no hay propiedades registradas en el sistema.</p>
                <a href="?module=properties&action=create" class="btn btn-primary">Agregar Primera Propiedad</a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- JavaScript for view toggle and image slider -->
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

// Carousel Navigation Functions
function moveCarousel(propertyId, direction) {
    const carousel = document.querySelector(`[data-property-id="${propertyId}"]`);
    if (!carousel) return;

    const track = carousel.querySelector('.carousel-track');
    const slides = carousel.querySelectorAll('.carousel-slide');
    const dots = carousel.querySelectorAll('.dot');

    if (!track || slides.length === 0) return;

    // Get current index from data attribute
    let currentIndex = parseInt(carousel.dataset.carouselIndex) || 0;

    // Calculate new index
    currentIndex += direction;

    // Wrap around
    if (currentIndex >= slides.length) {
        currentIndex = 0;
    } else if (currentIndex < 0) {
        currentIndex = slides.length - 1;
    }

    // Update transform
    const translateX = -currentIndex * 100;
    track.style.transform = `translateX(${translateX}%)`;

    // Update dots
    dots.forEach((dot, index) => {
        dot.classList.toggle('active', index === currentIndex);
    });

    // Store current index
    carousel.dataset.carouselIndex = currentIndex;
}

function goToSlide(propertyId, slideIndex) {
    const carousel = document.querySelector(`[data-property-id="${propertyId}"]`);
    if (!carousel) return;

    const track = carousel.querySelector('.carousel-track');
    const slides = carousel.querySelectorAll('.carousel-slide');
    const dots = carousel.querySelectorAll('.dot');

    if (!track || !slides[slideIndex]) return;

    // Update transform
    const translateX = -slideIndex * 100;
    track.style.transform = `translateX(${translateX}%)`;

    // Update dots
    dots.forEach((dot, index) => {
        dot.classList.toggle('active', index === slideIndex);
    });

    // Store current index
    carousel.dataset.carouselIndex = slideIndex;
}

// Image Slider Functions - Horizontal Slide Effect (Table)
function changeSlide(propertyId, direction) {
    const slider = document.querySelector(`[data-property-id="${propertyId}"]`);
    if (!slider) return;

    const sliderImages = slider.querySelector('.slider-images') || slider.querySelector('.table-slider-images');
    const dots = slider.querySelectorAll('.slider-dot, .table-slider-dot');
    const photoCounter = slider.querySelector('.current-photo');

    if (!sliderImages) return;

    // Count total images correctly - handle both regular img tags and nested divs
    const imgElements = sliderImages.querySelectorAll('img');
    const totalImages = imgElements.length > 0 ? imgElements.length : sliderImages.children.length;

    // Get or initialize current index
    if (!slider.dataset.currentIndex) {
        slider.dataset.currentIndex = '0';
    }

    let currentIndex = parseInt(slider.dataset.currentIndex);

    // Calculate new index
    currentIndex += direction;

    // Wrap around
    if (currentIndex >= totalImages) {
        currentIndex = 0;
    } else if (currentIndex < 0) {
        currentIndex = totalImages - 1;
    }

    // Apply transform to slide
    const translateX = -currentIndex * 100;
    sliderImages.style.transform = `translateX(${translateX}%)`;

    // Update dots
    dots.forEach((dot, index) => {
        if (index === currentIndex) {
            dot.classList.add('active');
        } else {
            dot.classList.remove('active');
        }
    });

    // Update photo counter
    if (photoCounter) {
        photoCounter.textContent = currentIndex + 1;
    }

    // Store current index
    slider.dataset.currentIndex = currentIndex;
}

function goToSlide(propertyId, slideIndex) {
    const slider = document.querySelector(`[data-property-id="${propertyId}"]`);
    if (!slider) return;

    const sliderImages = slider.querySelector('.slider-images') || slider.querySelector('.table-slider-images');
    const dots = slider.querySelectorAll('.slider-dot, .table-slider-dot');
    const photoCounter = slider.querySelector('.current-photo');

    if (!sliderImages) return;

    // Apply transform to slide
    const translateX = -slideIndex * 100;
    sliderImages.style.transform = `translateX(${translateX}%)`;

    // Update dots
    dots.forEach((dot, index) => {
        if (index === slideIndex) {
            dot.classList.add('active');
        } else {
            dot.classList.remove('active');
        }
    });

    // Update photo counter
    if (photoCounter) {
        photoCounter.textContent = slideIndex + 1;
    }

    // Store current index
    slider.dataset.currentIndex = slideIndex;
}

// Optional: Auto-advance slider every 5 seconds
function startAutoSlide() {
    const sliders = document.querySelectorAll('.property-slider');
    sliders.forEach(slider => {
        const propertyId = slider.getAttribute('data-property-id');
        const images = slider.querySelectorAll('.slider-image');

        // Only auto-advance if there are multiple images
        if (images.length > 1) {
            setInterval(() => {
                // Only auto-advance if user is not hovering over the card
                const card = slider.closest('.property-card');
                if (card && !card.matches(':hover')) {
                    changeSlide(propertyId, 1);
                }
            }, 5000);
        }
    });
}

// Touch/Swipe Support for Mobile
function initSwipeSupport() {
    const sliders = document.querySelectorAll('.property-slider, .table-slider');

    sliders.forEach(slider => {
        let touchStartX = 0;
        let touchEndX = 0;

        slider.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });

        slider.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe(slider);
        }, { passive: true });

        function handleSwipe(sliderElement) {
            const swipeThreshold = 50; // Minimum distance for swipe
            const diff = touchStartX - touchEndX;

            if (Math.abs(diff) > swipeThreshold) {
                const propertyId = sliderElement.dataset.propertyId;
                if (diff > 0) {
                    // Swiped left - next image
                    changeSlide(propertyId, 1);
                } else {
                    // Swiped right - previous image
                    changeSlide(propertyId, -1);
                }
            }
        }
    });
}

// Load saved view preference
document.addEventListener('DOMContentLoaded', function() {
    const savedView = localStorage.getItem('propertyView') || 'table';
    toggleView(savedView);

    // Initialize swipe support for touch devices
    initSwipeSupport();

    // Uncomment the next line if you want auto-sliding
    // startAutoSlide();
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
                alert('✓ Propiedad eliminada correctamente');
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

        // Create a more detailed error message
        let errorMessage = error.message;
        let alertIcon = '⚠️';

        // Check if it's a reference constraint error
        if (errorMessage.includes('ventas registradas')) {
            alertIcon = '🔒';
            errorMessage = `NO SE PUEDE ELIMINAR LA PROPIEDAD ${formattedId}\n\n` +
                          `Motivo: Esta propiedad tiene ventas registradas.\n\n` +
                          `Para eliminar esta propiedad, primero debe eliminar o desvincular todas las ventas asociadas.`;
        } else if (errorMessage.includes('contratos activos')) {
            alertIcon = '🔒';
            errorMessage = `NO SE PUEDE ELIMINAR LA PROPIEDAD ${formattedId}\n\n` +
                          `Motivo: Esta propiedad tiene contratos activos.\n\n` +
                          `Para eliminar esta propiedad, primero debe eliminar o desvincular todos los contratos asociados.`;
        } else if (errorMessage.includes('arriendos activos')) {
            alertIcon = '🔒';
            errorMessage = `NO SE PUEDE ELIMINAR LA PROPIEDAD ${formattedId}\n\n` +
                          `Motivo: Esta propiedad tiene arriendos activos.\n\n` +
                          `Para eliminar esta propiedad, primero debe eliminar o desvincular todos los arriendos asociados.`;
        } else if (errorMessage.includes('visitas programadas')) {
            alertIcon = '🔒';
            errorMessage = `NO SE PUEDE ELIMINAR LA PROPIEDAD ${formattedId}\n\n` +
                          `Motivo: Esta propiedad tiene visitas programadas.\n\n` +
                          `Para eliminar esta propiedad, primero debe eliminar o desvincular todas las visitas asociadas.`;
        } else {
            errorMessage = `${alertIcon} Error al eliminar la propiedad ${formattedId}:\n\n${errorMessage}`;
        }

        if (typeof App !== 'undefined' && App.showErrorMessage) {
            App.showErrorMessage(errorMessage);
        } else {
            alert(errorMessage);
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

.module-description {
    color: var(--text-secondary);
    font-size: var(--font-size-sm);
    margin-top: var(--spacing-xs);
}

/* ========================================
   IMAGE GALLERY STYLES (like client detail page)
   ======================================== */
.property-card .image-container {
    position: relative;
    width: 100%;
    padding-bottom: 66.66%;
    overflow: hidden;
    background: #e0e0e0;
}

.image-gallery {
    position: relative;
    width: 100%;
    height: 100%;
    overflow: hidden;
    background: #e0e0e0;
}

.image-gallery img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.gallery-controls {
    position: absolute;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 10px;
}

.gallery-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: rgba(255,255,255,0.5);
    cursor: pointer;
    transition: background 0.3s;
}

.gallery-dot.active {
    background: white;
}

.gallery-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(0,0,0,0.5);
    color: white;
    border: none;
    padding: 15px 20px;
    font-size: 24px;
    cursor: pointer;
    transition: background 0.3s;
}

.gallery-nav:hover {
    background: rgba(0,0,0,0.7);
}

.gallery-nav.prev {
    left: 10px;
}

.gallery-nav.next {
    right: 10px;
}

/* Status Tag */
.property-card .image-container .tag {
    position: absolute;
    top: 15px;
    left: 0;
    padding: 6px 15px;
    color: white;
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    z-index: 20;
    letter-spacing: 1px;
}

/* ========================================
   TABLE IMAGE GALLERY STYLES
   ======================================== */
.table-image-cell {
    width: 120px;
    padding: 8px !important;
}

.table-image-gallery {
    position: relative;
    width: 100px;
    height: 80px;
    border-radius: 6px;
    overflow: hidden;
    background: #e0e0e0;
}

.table-image-gallery img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.table-gallery-controls {
    position: absolute;
    bottom: 5px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 4px;
}

.table-gallery-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: rgba(255,255,255,0.5);
    cursor: pointer;
    transition: background 0.2s;
}

.table-gallery-dot.active {
    background: white;
}

.table-gallery-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(0,0,0,0.5);
    color: white;
    border: none;
    padding: 5px 8px;
    font-size: 14px;
    cursor: pointer;
    transition: background 0.2s;
}

.table-gallery-nav:hover {
    background: rgba(0,0,0,0.7);
}

.table-gallery-nav.prev {
    left: 2px;
}

.table-gallery-nav.next {
    right: 2px;
}

.table-image-container {
    position: relative;
    width: 100px;
    height: 80px;
    border-radius: 6px;
    overflow: hidden;
    background: #e0e0e0;
}

.table-slider {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

.table-slider-images {
    display: flex;
    width: 100%;
    height: 100%;
    transition: transform 0.4s ease-in-out;
    overflow: hidden;
    flex-wrap: nowrap;
    /* Fix: ensure container is wide enough for all images */
    flex-basis: 100%;
}

.table-slider-image {
    min-width: 100%;
    width: 100%;
    height: 100%;
    object-fit: cover;
    flex-shrink: 0;
    display: block; /* Ensure images display as block elements */
}

/* Table Navigation Arrows - Always Visible */
.table-slider-nav {
    position: absolute !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    background: rgba(0, 0, 0, 0.7) !important;
    color: white !important;
    border: 1px solid rgba(255, 255, 255, 0.9) !important;
    width: 26px !important;
    height: 26px !important;
    border-radius: 50% !important;
    cursor: pointer !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    transition: all 0.3s ease !important;
    z-index: 20 !important;
    opacity: 0.9 !important;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3) !important;
    visibility: visible !important;
    pointer-events: auto !important;
    padding: 0 !important;
    margin: 0 !important;
    line-height: 1 !important;
    font-size: 18px !important;
    font-weight: bold !important;
}

.table-image-container:hover .table-slider-nav {
    opacity: 1;
    transform: translateY(-50%) scale(1.1);
}

.table-slider-nav:hover {
    background: rgba(0, 222, 85, 0.95);
    border-color: #00de55;
    transform: translateY(-50%) scale(1.2);
    box-shadow: 0 3px 8px rgba(0, 222, 85, 0.5);
}

.table-slider-prev {
    left: 4px !important;
}

.table-slider-next {
    right: 4px !important;
}

/* Table Dots Indicator */
.table-slider-dots {
    position: absolute;
    bottom: 4px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 3px;
    z-index: 10;
}

.table-slider-dot {
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.5);
    border: 1px solid rgba(255, 255, 255, 0.8);
    cursor: pointer;
    transition: all 0.3s ease;
}

.table-slider-dot:hover {
    background: rgba(255, 255, 255, 0.8);
    transform: scale(1.3);
}

.table-slider-dot.active {
    background: #00de55;
    border-color: #00de55;
    width: 14px;
    border-radius: 3px;
}

/* Table Photo Counter */
.table-photo-count {
    position: absolute;
    top: 4px;
    right: 4px;
    background: rgba(0, 0, 0, 0.75);
    color: white;
    padding: 2px 5px;
    border-radius: 3px;
    font-size: 9px;
    z-index: 10;
    font-weight: 600;
    line-height: 1;
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

    /* Smaller table images on mobile */
    .table-image-container {
        width: 80px;
        height: 60px;
    }

    .table-image-cell {
        width: 100px;
    }
}
</style>

<script>
// Store current image indices for card galleries
const cardGalleryIndices = {};

// Card Gallery - Change image by clicking arrows
function changeCardImage(propertyId, direction) {
    const images = window[`galleryImages_${propertyId}`];
    if (!images || images.length <= 1) return;

    // Initialize index if not exists
    if (cardGalleryIndices[propertyId] === undefined) {
        cardGalleryIndices[propertyId] = 0;
    }

    cardGalleryIndices[propertyId] += direction;

    // Wrap around (loop)
    if (cardGalleryIndices[propertyId] < 0) {
        cardGalleryIndices[propertyId] = images.length - 1;
    } else if (cardGalleryIndices[propertyId] >= images.length) {
        cardGalleryIndices[propertyId] = 0;
    }

    showCardImage(propertyId, cardGalleryIndices[propertyId]);
}

// Card Gallery - Show specific image by clicking dots
function showCardImage(propertyId, index) {
    const images = window[`galleryImages_${propertyId}`];
    if (!images || images.length === 0) return;

    cardGalleryIndices[propertyId] = index;
    const imgElement = document.getElementById(`gallery-img-${propertyId}`);
    if (imgElement) {
        imgElement.src = images[index];
    }

    // Update active dot
    const gallery = document.getElementById(`gallery-${propertyId}`);
    if (gallery) {
        const dots = gallery.querySelectorAll('.gallery-dot');
        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === index);
        });
    }
}

// Store current image indices for table galleries
const tableGalleryIndices = {};

// Table Gallery - Change image by clicking arrows
function changeTableImage(propertyId, direction) {
    const images = window[`tableGalleryImages_${propertyId}`];
    if (!images || images.length <= 1) return;

    // Initialize index if not exists
    if (tableGalleryIndices[propertyId] === undefined) {
        tableGalleryIndices[propertyId] = 0;
    }

    tableGalleryIndices[propertyId] += direction;

    // Wrap around (loop)
    if (tableGalleryIndices[propertyId] < 0) {
        tableGalleryIndices[propertyId] = images.length - 1;
    } else if (tableGalleryIndices[propertyId] >= images.length) {
        tableGalleryIndices[propertyId] = 0;
    }

    showTableImage(propertyId, tableGalleryIndices[propertyId]);
}

// Table Gallery - Show specific image by clicking dots
function showTableImage(propertyId, index) {
    const images = window[`tableGalleryImages_${propertyId}`];
    if (!images || images.length === 0) return;

    tableGalleryIndices[propertyId] = index;
    const imgElement = document.getElementById(`table-gallery-img-${propertyId}`);
    if (imgElement) {
        imgElement.src = images[index];
    }

    // Update active dot
    const gallery = document.getElementById(`table-gallery-${propertyId}`);
    if (gallery) {
        const dots = gallery.querySelectorAll('.table-gallery-dot');
        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === index);
        });
    }
}
</script>