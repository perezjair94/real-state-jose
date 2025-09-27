<?php
/**
 * Agents List View - Real Estate Management System
 * Display all agents with basic information
 * Educational PHP/MySQL Project
 */

// Prevent direct access
if (!defined('APP_ACCESS')) {
    die('Direct access not permitted');
}

// For now, use sample data since database structure is not fully set up
$agents = [
    [
        'id_agente' => 1,
        'nombre' => 'María García',
        'correo' => 'maria@inmobiliaria.com',
        'telefono' => '300-555-0123',
        'asesor' => 'Carlos Rodríguez',
        'created_at' => '2024-09-01 10:00:00'
    ],
    [
        'id_agente' => 2,
        'nombre' => 'Luis Fernando Pérez',
        'correo' => 'luis@inmobiliaria.com',
        'telefono' => '310-555-0124',
        'asesor' => 'Ana López',
        'created_at' => '2024-09-02 14:30:00'
    ]
];
?>

<div class="module-header">
    <h2>Gestión de Agentes</h2>
    <p class="module-description">
        Administre el equipo de agentes inmobiliarios y sus supervisores.
    </p>
</div>

<!-- Action Buttons -->
<div class="action-buttons">
    <a href="?module=agents&action=create" class="btn btn-primary">
        + Agregar Nuevo Agente
    </a>
    <button type="button" class="btn btn-secondary" onclick="alert('Exportar función en desarrollo')">
        Exportar Lista
    </button>
</div>

<!-- Agents Table -->
<div class="card">
    <h3>Lista de Agentes</h3>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Teléfono</th>
                    <th>Asesor</th>
                    <th>Fecha Registro</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($agents as $agent): ?>
                    <tr>
                        <td>
                            <strong>AGE<?= str_pad($agent['id_agente'], 3, '0', STR_PAD_LEFT) ?></strong>
                        </td>
                        <td><?= htmlspecialchars($agent['nombre']) ?></td>
                        <td><?= htmlspecialchars($agent['correo']) ?></td>
                        <td><?= htmlspecialchars($agent['telefono']) ?></td>
                        <td><?= htmlspecialchars($agent['asesor']) ?></td>
                        <td><?= formatDate($agent['created_at']) ?></td>
                        <td class="table-actions">
                            <a href="?module=agents&action=view&id=<?= $agent['id_agente'] ?>"
                               class="btn btn-sm btn-info" title="Ver detalles">
                                Ver
                            </a>
                            <a href="?module=agents&action=edit&id=<?= $agent['id_agente'] ?>"
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

<div class="info-message">
    <p><strong>Nota:</strong> Este módulo muestra datos de ejemplo. La integración completa con la base de datos está en desarrollo.</p>
</div>