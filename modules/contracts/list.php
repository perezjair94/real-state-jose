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

// For now, use sample data since database structure is not fully set up
$contracts = [
    [
        'id_contrato' => 1,
        'tipo_contrato' => 'Compraventa',
        'inmueble_id' => 'INM002',
        'cliente_nombre' => 'Juan Pérez',
        'fecha_inicio' => '2024-09-15',
        'fecha_fin' => '2024-12-15',
        'estado' => 'Activo',
        'valor' => 280000000,
        'created_at' => '2024-09-15 16:30:00'
    ],
    [
        'id_contrato' => 2,
        'tipo_contrato' => 'Arrendamiento',
        'inmueble_id' => 'INM003',
        'cliente_nombre' => 'Ana López',
        'fecha_inicio' => '2024-08-01',
        'fecha_fin' => '2025-08-01',
        'estado' => 'Activo',
        'valor' => 1200000,
        'created_at' => '2024-08-01 10:00:00'
    ]
];
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

<!-- Filter Section -->
<div class="card">
    <h3>Filtros</h3>
    <form method="GET" class="filter-form">
        <input type="hidden" name="module" value="contracts">

        <div class="form-row">
            <div class="form-group">
                <label for="tipo">Tipo de Contrato:</label>
                <select id="tipo" name="tipo" class="form-control">
                    <option value="">Todos los tipos</option>
                    <option value="Compraventa">Compraventa</option>
                    <option value="Arrendamiento">Arrendamiento</option>
                </select>
            </div>

            <div class="form-group">
                <label for="estado">Estado:</label>
                <select id="estado" name="estado" class="form-control">
                    <option value="">Todos los estados</option>
                    <option value="Activo">Activo</option>
                    <option value="Finalizado">Finalizado</option>
                    <option value="Cancelado">Cancelado</option>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="?module=contracts" class="btn btn-secondary">Limpiar</a>
        </div>
    </form>
</div>

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
                            <span class="property-id"><?= htmlspecialchars($contract['inmueble_id']) ?></span>
                        </td>
                        <td><?= htmlspecialchars($contract['cliente_nombre']) ?></td>
                        <td><?= formatDate($contract['fecha_inicio']) ?></td>
                        <td><?= formatDate($contract['fecha_fin']) ?></td>
                        <td>
                            <strong class="price">
                                <?= formatCurrency($contract['valor']) ?>
                                <?php if ($contract['tipo_contrato'] === 'Arrendamiento'): ?>
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

<div class="info-message">
    <p><strong>Nota:</strong> Este módulo muestra datos de ejemplo. La integración completa con la base de datos está en desarrollo.</p>
</div>

<style>
.contract-type {
    padding: 2px 6px;
    border-radius: var(--border-radius);
    font-size: var(--font-size-xs);
    font-weight: 500;
}

.contract-type.compraventa {
    background: #e3f2fd;
    color: #1976d2;
}

.contract-type.arrendamiento {
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
</style>