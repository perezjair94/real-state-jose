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

// For now, use sample data since database structure is not fully set up
$sales = [
    [
        'id_venta' => 1,
        'fecha_venta' => '2024-09-15',
        'inmueble_id' => 'INM002',
        'cliente_nombre' => 'Juan Pérez',
        'valor_venta' => 280000000,
        'agente_nombre' => 'María García',
        'created_at' => '2024-09-15 16:30:00'
    ],
    [
        'id_venta' => 2,
        'fecha_venta' => '2024-09-10',
        'inmueble_id' => 'INM005',
        'cliente_nombre' => 'Ana López',
        'valor_venta' => 350000000,
        'agente_nombre' => 'Luis Pérez',
        'created_at' => '2024-09-10 11:20:00'
    ]
];
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
                            <span class="property-id"><?= htmlspecialchars($sale['inmueble_id']) ?></span>
                        </td>
                        <td><?= htmlspecialchars($sale['cliente_nombre']) ?></td>
                        <td><?= htmlspecialchars($sale['agente_nombre']) ?></td>
                        <td>
                            <strong class="price"><?= formatCurrency($sale['valor_venta']) ?></strong>
                        </td>
                        <td class="table-actions">
                            <a href="?module=sales&action=view&id=<?= $sale['id_venta'] ?>"
                               class="btn btn-sm btn-info" title="Ver detalles">
                                Ver Detalles
                            </a>
                            <button type="button" class="btn btn-sm btn-secondary"
                                    onclick="alert('Generar factura en desarrollo')" title="Factura">
                                Factura
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
            <span class="stat-value"><?= formatCurrency(array_sum(array_column($sales, 'valor_venta'))) ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">Promedio por Venta</span>
            <span class="stat-value"><?= formatCurrency(array_sum(array_column($sales, 'valor_venta')) / count($sales)) ?></span>
        </div>
    </div>
</div>

<div class="info-message">
    <p><strong>Nota:</strong> Este módulo muestra datos de ejemplo. La integración completa con la base de datos está en desarrollo.</p>
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
</style>