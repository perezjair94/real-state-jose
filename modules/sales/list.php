<?php
/**
 * Sales List View - Real Estate Management System
 * Display all sales transactions
 * Educational PHP/MySQL Project
 */

// Prevent direct access
if (!defined('APP_ACCESS')) {
    die('Direct access not permitted');
}

// Get sales from database
$sales = [];

try {
    $db = new Database();
    $pdo = $db->getConnection();

    // Get sales with related data
    $sql = "SELECT v.id_venta, v.fecha_venta, v.valor, v.comision, v.observaciones, v.created_at,
                   CONCAT('INM', LPAD(i.id_inmueble, 3, '0')) as inmueble_codigo,
                   CONCAT(i.tipo_inmueble, ' - ', i.direccion) as inmueble_descripcion,
                   CONCAT(c.nombre, ' ', c.apellido) as cliente_nombre,
                   a.nombre as agente_nombre
            FROM venta v
            INNER JOIN inmueble i ON v.id_inmueble = i.id_inmueble
            INNER JOIN cliente c ON v.id_cliente = c.id_cliente
            LEFT JOIN agente a ON v.id_agente = a.id_agente
            ORDER BY v.fecha_venta DESC, v.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $sales = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Error fetching sales: " . $e->getMessage());
    $error = "Error al cargar las ventas. Intente nuevamente.";
}
?>

<div class="module-header">
    <h2>Gestión de Ventas</h2>
    <p class="module-description">
        Administre todas las transacciones de venta de propiedades.
    </p>
</div>

<!-- Action Buttons -->
<div class="action-buttons">
    <a href="?module=sales&action=create" class="btn btn-primary">
        + Registrar Nueva Venta
    </a>
    <button type="button" class="btn btn-secondary" onclick="alert('Reporte de ventas en desarrollo')">
        Reporte de Ventas
    </button>
</div>

<!-- Sales Table -->
<div class="card">
    <h3>Lista de Ventas</h3>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Inmueble</th>
                    <th>Cliente</th>
                    <th>Agente</th>
                    <th>Valor</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sales as $sale): ?>
                    <tr>
                        <td>
                            <strong>VEN<?= str_pad($sale['id_venta'], 3, '0', STR_PAD_LEFT) ?></strong>
                        </td>
                        <td><?= formatDate($sale['fecha_venta']) ?></td>
                        <td>
                            <span class="property-id"><?= htmlspecialchars($sale['inmueble_codigo']) ?></span>
                            <div class="property-description"><?= htmlspecialchars($sale['inmueble_descripcion']) ?></div>
                        </td>
                        <td><?= htmlspecialchars($sale['cliente_nombre']) ?></td>
                        <td><?= htmlspecialchars($sale['agente_nombre'] ?: 'Sin agente') ?></td>
                        <td>
                            <strong class="price"><?= formatCurrency($sale['valor']) ?></strong>
                            <?php if ($sale['comision']): ?>
                                <div class="commission">Comisión: <?= formatCurrency($sale['comision']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="table-actions">
                            <a href="?module=sales&action=view&id=<?= $sale['id_venta'] ?>"
                               class="btn btn-sm btn-info" title="Ver detalles">
                                Ver Detalles
                            </a>
                            <a href="?module=sales&action=edit&id=<?= $sale['id_venta'] ?>"
                               class="btn btn-sm btn-secondary" title="Editar">
                                Editar
                            </a>
                            <button type="button"
                                    class="btn btn-sm btn-danger"
                                    onclick="confirmDelete(<?= $sale['id_venta'] ?>)"
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

<!-- Sales Summary -->
<div class="card">
    <h3>Resumen de Ventas</h3>
    <div class="stats-grid">
        <div class="stat-item">
            <span class="stat-label">Total Ventas</span>
            <span class="stat-value"><?= count($sales) ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">Valor Total</span>
            <span class="stat-value"><?= formatCurrency(array_sum(array_column($sales, 'valor'))) ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">Promedio por Venta</span>
            <span class="stat-value"><?= count($sales) > 0 ? formatCurrency(array_sum(array_column($sales, 'valor')) / count($sales)) : '$0' ?></span>
        </div>
    </div>
</div>

<!-- Error Display -->
<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

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

.property-description {
    font-size: var(--font-size-xs);
    color: var(--text-secondary);
    margin-top: 2px;
}

.commission {
    font-size: var(--font-size-xs);
    color: var(--text-secondary);
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
 * Delete sale with confirmation
 */
async function confirmDelete(saleId) {
    const formattedId = 'VEN' + String(saleId).padStart(3, '0');

    if (!confirm(`¿Está seguro de que desea eliminar la venta ${formattedId}?\n\nEsta acción no se puede deshacer y el inmueble volverá a estar disponible.`)) {
        return;
    }

    try {
        console.log('Eliminando venta:', saleId);

        // Show loading state
        const deleteButtons = document.querySelectorAll(`button[onclick*="confirmDelete(${saleId})"]`);
        deleteButtons.forEach(btn => {
            btn.disabled = true;
            btn.innerHTML = 'Eliminando...';
        });

        const response = await Ajax.sales.delete(saleId);
        console.log('Respuesta del servidor:', response);

        if (response.success) {
            if (typeof App !== 'undefined' && App.showSuccessMessage) {
                App.showSuccessMessage(response.message || 'Venta eliminada correctamente');
            } else {
                alert('✓ ' + (response.message || 'Venta eliminada correctamente'));
            }

            // Refresh the page after a short delay
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            throw new Error(response.message || 'Error desconocido');
        }
    } catch (error) {
        console.error('Error al eliminar venta:', error);

        let errorMessage = error.message;
        let alertIcon = '⚠️';

        // Check if it's a reference constraint error
        if (errorMessage.includes('contrato(s) relacionado(s)')) {
            alertIcon = '🔒';
            errorMessage = `NO SE PUEDE ELIMINAR LA VENTA ${formattedId}\n\n` +
                          `Motivo: Esta venta tiene contratos relacionados.\n\n` +
                          `Para eliminar esta venta, primero debe eliminar o desvincular todos los contratos asociados.`;
        } else {
            errorMessage = `${alertIcon} Error al eliminar la venta ${formattedId}:\n\n${errorMessage}`;
        }

        if (typeof App !== 'undefined' && App.showErrorMessage) {
            App.showErrorMessage(errorMessage);
        } else {
            alert(errorMessage);
        }

        // Restore buttons on error
        const deleteButtons = document.querySelectorAll(`button[onclick*="confirmDelete(${saleId})"]`);
        deleteButtons.forEach(btn => {
            btn.disabled = false;
            btn.innerHTML = 'Eliminar';
        });
    }
}
</script>