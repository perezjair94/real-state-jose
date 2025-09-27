<?php
/**
 * Visits List View - Real Estate Management System
 * Display all scheduled property visits
 * Educational PHP/MySQL Project
 */

// Prevent direct access
if (!defined('APP_ACCESS')) {
    die('Direct access not permitted');
}

// For now, use sample data since database structure is not fully set up
$visits = [
    [
        'id_visita' => 1,
        'fecha_visita' => '2024-09-20',
        'hora_visita' => '10:00',
        'inmueble_id' => 'INM001',
        'cliente_nombre' => 'Juan Pérez',
        'agente_nombre' => 'María García',
        'estado' => 'Programada',
        'observaciones' => 'Cliente muy interesado en la propiedad',
        'created_at' => '2024-09-18 15:30:00'
    ],
    [
        'id_visita' => 2,
        'fecha_visita' => '2024-09-18',
        'hora_visita' => '14:00',
        'inmueble_id' => 'INM005',
        'cliente_nombre' => 'Ana López',
        'agente_nombre' => 'Luis Pérez',
        'estado' => 'Realizada',
        'observaciones' => 'Visita exitosa, cliente solicita propuesta',
        'created_at' => '2024-09-16 11:20:00'
    ],
    [
        'id_visita' => 3,
        'fecha_visita' => '2024-09-22',
        'hora_visita' => '16:30',
        'inmueble_id' => 'INM003',
        'cliente_nombre' => 'Roberto Silva',
        'agente_nombre' => 'María García',
        'estado' => 'Programada',
        'observaciones' => '',
        'created_at' => '2024-09-19 09:45:00'
    ]
];
?>

<div class="module-header">
    <h2>Gestión de Visitas</h2>
    <p class="module-description">
        Programe y administre las visitas de clientes a las propiedades.
    </p>
</div>

<!-- Action Buttons -->
<div class="action-buttons">
    <a href="?module=visits&action=create" class="btn btn-primary">
        + Programar Nueva Visita
    </a>
    <button type="button" class="btn btn-secondary" onclick="alert('Función en desarrollo')">
        Agenda del Día
    </button>
    <button type="button" class="btn btn-secondary" onclick="alert('Función en desarrollo')">
        Reporte de Visitas
    </button>
</div>

<!-- Filter Section -->
<div class="card">
    <h3>Filtros</h3>
    <form method="GET" class="filter-form">
        <input type="hidden" name="module" value="visits">

        <div class="form-row">
            <div class="form-group">
                <label for="estado">Estado:</label>
                <select id="estado" name="estado" class="form-control">
                    <option value="">Todos los estados</option>
                    <option value="Programada">Programada</option>
                    <option value="Realizada">Realizada</option>
                    <option value="Cancelada">Cancelada</option>
                    <option value="Reprogramada">Reprogramada</option>
                </select>
            </div>

            <div class="form-group">
                <label for="fecha">Fecha:</label>
                <input type="date" id="fecha" name="fecha" class="form-control">
            </div>

            <div class="form-group">
                <label for="agente">Agente:</label>
                <select id="agente" name="agente" class="form-control">
                    <option value="">Todos los agentes</option>
                    <option value="María García">María García</option>
                    <option value="Luis Pérez">Luis Pérez</option>
                    <option value="Carlos López">Carlos López</option>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="?module=visits" class="btn btn-secondary">Limpiar</a>
        </div>
    </form>
</div>

<!-- Visits for Today -->
<div class="card highlight-card">
    <h3>🗓️ Visitas de Hoy</h3>
    <?php
    $today = date('Y-m-d');
    $todayVisits = array_filter($visits, fn($v) => $v['fecha_visita'] === $today);
    ?>

    <?php if (!empty($todayVisits)): ?>
        <div class="today-visits">
            <?php foreach ($todayVisits as $visit): ?>
                <div class="visit-card">
                    <div class="visit-time"><?= $visit['hora_visita'] ?></div>
                    <div class="visit-info">
                        <strong><?= htmlspecialchars($visit['cliente_nombre']) ?></strong><br>
                        <span class="property-id"><?= $visit['inmueble_id'] ?></span> -
                        <span class="agent-name"><?= $visit['agente_nombre'] ?></span>
                    </div>
                    <div class="visit-status">
                        <span class="status <?= strtolower($visit['estado']) ?>">
                            <?= $visit['estado'] ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="no-visits">No hay visitas programadas para hoy.</p>
    <?php endif; ?>
</div>

<!-- All Visits Table -->
<div class="card">
    <h3>Todas las Visitas</h3>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Inmueble</th>
                    <th>Cliente</th>
                    <th>Agente</th>
                    <th>Estado</th>
                    <th>Observaciones</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($visits as $visit): ?>
                    <tr class="<?= $visit['fecha_visita'] === $today ? 'today-row' : '' ?>">
                        <td>
                            <strong>VIS<?= str_pad($visit['id_visita'], 3, '0', STR_PAD_LEFT) ?></strong>
                        </td>
                        <td>
                            <?= formatDate($visit['fecha_visita']) ?>
                            <?php if ($visit['fecha_visita'] === $today): ?>
                                <span class="today-badge">HOY</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= $visit['hora_visita'] ?></strong>
                        </td>
                        <td>
                            <span class="property-id"><?= htmlspecialchars($visit['inmueble_id']) ?></span>
                        </td>
                        <td><?= htmlspecialchars($visit['cliente_nombre']) ?></td>
                        <td><?= htmlspecialchars($visit['agente_nombre']) ?></td>
                        <td>
                            <span class="status <?= strtolower($visit['estado']) ?>">
                                <?= htmlspecialchars($visit['estado']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="observations">
                                <?= htmlspecialchars($visit['observaciones']) ?: 'Sin observaciones' ?>
                            </div>
                        </td>
                        <td class="table-actions">
                            <a href="?module=visits&action=view&id=<?= $visit['id_visita'] ?>"
                               class="btn btn-sm btn-info" title="Ver detalles">
                                Ver
                            </a>
                            <?php if ($visit['estado'] === 'Programada'): ?>
                                <a href="?module=visits&action=edit&id=<?= $visit['id_visita'] ?>"
                                   class="btn btn-sm btn-secondary" title="Editar">
                                    Editar
                                </a>
                                <button type="button" class="btn btn-sm btn-danger"
                                        onclick="alert('Cancelar visita en desarrollo')" title="Cancelar">
                                    Cancelar
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Visits Summary -->
<div class="card">
    <h3>Resumen de Visitas</h3>
    <div class="stats-grid">
        <div class="stat-item">
            <span class="stat-label">Total Visitas</span>
            <span class="stat-value"><?= count($visits) ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">Programadas</span>
            <span class="stat-value"><?= count(array_filter($visits, fn($v) => $v['estado'] === 'Programada')) ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">Realizadas</span>
            <span class="stat-value"><?= count(array_filter($visits, fn($v) => $v['estado'] === 'Realizada')) ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">Hoy</span>
            <span class="stat-value"><?= count($todayVisits) ?></span>
        </div>
    </div>
</div>

<div class="info-message">
    <p><strong>Nota:</strong> Este módulo muestra datos de ejemplo. La integración completa con la base de datos está en desarrollo.</p>
</div>

<style>
.highlight-card {
    border-left: 4px solid var(--primary-color);
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.today-visits {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-sm);
    margin-top: var(--spacing-md);
}

.visit-card {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    padding: var(--spacing-sm);
    background: white;
    border-radius: var(--border-radius);
    border: 1px solid var(--border-color);
}

.visit-time {
    font-weight: 600;
    font-size: var(--font-size-lg);
    color: var(--primary-color);
    min-width: 60px;
}

.visit-info {
    flex: 1;
    font-size: var(--font-size-sm);
}

.visit-status {
    margin-left: auto;
}

.no-visits {
    text-align: center;
    color: var(--text-secondary);
    padding: var(--spacing-lg);
    font-style: italic;
}

.today-row {
    background-color: #fff3cd;
}

.today-badge {
    background: var(--accent-color);
    color: white;
    padding: 2px 6px;
    border-radius: var(--border-radius);
    font-size: var(--font-size-xs);
    font-weight: 500;
    margin-left: var(--spacing-xs);
}

.observations {
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

.agent-name {
    color: var(--text-secondary);
    font-size: var(--font-size-xs);
}

.filter-form .form-actions {
    margin-top: var(--spacing-md);
    display: flex;
    gap: var(--spacing-sm);
}
</style>