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

// For now, use sample data since database structure is not fully set up
$rentals = [
    [
        'id_arriendo' => 1,
        'inmueble_id' => 'INM003',
        'arrendatario' => 'Ana López',
        'arrendador' => 'Carlos Mendoza',
        'canon_mensual' => 1200000,
        'fecha_inicio' => '2024-08-01',
        'fecha_fin' => '2025-08-01',
        'estado' => 'Activo',
        'deposito' => 2400000,
        'created_at' => '2024-08-01 10:00:00'
    ],
    [
        'id_arriendo' => 2,
        'inmueble_id' => 'INM007',
        'arrendatario' => 'Roberto Silva',
        'arrendador' => 'María Gómez',
        'canon_mensual' => 800000,
        'fecha_inicio' => '2024-07-15',
        'fecha_fin' => '2025-07-15',
        'estado' => 'Activo',
        'deposito' => 1600000,
        'created_at' => '2024-07-15 14:30:00'
    ]
];
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

<!-- Filter Section -->
<div class="card">
    <h3>Filtros</h3>
    <form method="GET" class="filter-form">
        <input type="hidden" name="module" value="rentals">

        <div class="form-row">
            <div class="form-group">
                <label for="estado">Estado:</label>
                <select id="estado" name="estado" class="form-control">
                    <option value="">Todos los estados</option>
                    <option value="Activo">Activo</option>
                    <option value="Vencido">Vencido</option>
                    <option value="Terminado">Terminado</option>
                    <option value="Moroso">Moroso</option>
                </select>
            </div>

            <div class="form-group">
                <label for="mes">Mes de Vencimiento:</label>
                <select id="mes" name="mes" class="form-control">
                    <option value="">Todos los meses</option>
                    <?php for ($i = 1; $i <= 12; $i++): ?>
                        <option value="<?= $i ?>"><?= date('F', mktime(0, 0, 0, $i, 1)) ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="?module=rentals" class="btn btn-secondary">Limpiar</a>
        </div>
    </form>
</div>

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
                    <th>Arrendador</th>
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
                            <span class="property-id"><?= htmlspecialchars($rental['inmueble_id']) ?></span>
                        </td>
                        <td><?= htmlspecialchars($rental['arrendatario']) ?></td>
                        <td><?= htmlspecialchars($rental['arrendador']) ?></td>
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
</style>