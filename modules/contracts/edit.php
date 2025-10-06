<?php
/**
 * Contract Edit Form - Real Estate Management System
 * Form to edit existing contracts with database integration
 * Educational PHP/MySQL Project
 */

// Prevent direct access
if (!defined('APP_ACCESS')) {
    die('Direct access not permitted');
}

$error = '';
$success = '';
$contract = null;
$contractId = (int)($_GET['id'] ?? 0);

// Initialize form data
$formData = [
    'tipo_contrato' => '',
    'fecha_inicio' => '',
    'fecha_fin' => '',
    'valor_contrato' => '',
    'estado' => '',
    'observaciones' => '',
    'id_inmueble' => '',
    'id_cliente' => '',
    'id_agente' => ''
];

// Available data for selection
$properties = [];
$clients = [];
$agents = [];

// Validate contract ID and load existing data
if ($contractId <= 0) {
    $error = "ID de contrato inválido";
} else {
    try {
        $db = new Database();
        $pdo = $db->getConnection();

        // Load existing contract data with related information
        $stmt = $pdo->prepare("
            SELECT c.*,
                   i.direccion as propiedad_direccion, i.tipo_inmueble, i.ciudad,
                   cl.nombre as cliente_nombre, cl.apellido as cliente_apellido,
                   a.nombre as agente_nombre
            FROM contrato c
            LEFT JOIN inmueble i ON c.id_inmueble = i.id_inmueble
            LEFT JOIN cliente cl ON c.id_cliente = cl.id_cliente
            LEFT JOIN agente a ON c.id_agente = a.id_agente
            WHERE c.id_contrato = ?
        ");
        $stmt->execute([$contractId]);
        $contract = $stmt->fetch();

        if (!$contract) {
            $error = "Contrato no encontrado";
        } else {
            // Populate form data with existing values
            $formData = [
                'tipo_contrato' => $contract['tipo_contrato'],
                'fecha_inicio' => $contract['fecha_inicio'],
                'fecha_fin' => $contract['fecha_fin'] ?? '',
                'valor_contrato' => $contract['valor_contrato'],
                'estado' => $contract['estado'],
                'observaciones' => $contract['observaciones'] ?? '',
                'id_inmueble' => $contract['id_inmueble'],
                'id_cliente' => $contract['id_cliente'],
                'id_agente' => $contract['id_agente'] ?? ''
            ];

            // Load all properties
            $stmt = $pdo->prepare("SELECT id_inmueble, tipo_inmueble, direccion, ciudad, estado FROM inmueble ORDER BY ciudad, direccion");
            $stmt->execute();
            $properties = $stmt->fetchAll();

            // Load all clients
            $stmt = $pdo->prepare("SELECT id_cliente, nombre, apellido, tipo_cliente FROM cliente ORDER BY nombre, apellido");
            $stmt->execute();
            $clients = $stmt->fetchAll();

            // Load active agents
            $stmt = $pdo->prepare("SELECT id_agente, nombre FROM agente WHERE activo = 1 ORDER BY nombre");
            $stmt->execute();
            $agents = $stmt->fetchAll();
        }

    } catch (PDOException $e) {
        error_log("Error loading contract: " . $e->getMessage());
        $error = "Error al cargar los datos del contrato";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error && $contract) {
    try {
        // Get form data
        $formData = [
            'tipo_contrato' => $_POST['tipo_contrato'] ?? '',
            'fecha_inicio' => trim($_POST['fecha_inicio'] ?? ''),
            'fecha_fin' => trim($_POST['fecha_fin'] ?? ''),
            'valor_contrato' => trim($_POST['valor_contrato'] ?? ''),
            'estado' => $_POST['estado'] ?? '',
            'observaciones' => trim($_POST['observaciones'] ?? ''),
            'id_inmueble' => (int)($_POST['id_inmueble'] ?? 0),
            'id_cliente' => (int)($_POST['id_cliente'] ?? 0),
            'id_agente' => (int)($_POST['id_agente'] ?? 0)
        ];

        // Validation
        $errors = [];

        if (empty($formData['tipo_contrato']) || !in_array($formData['tipo_contrato'], ['Venta', 'Arriendo'])) {
            $errors[] = "El tipo de contrato es inválido";
        }

        if (empty($formData['fecha_inicio'])) {
            $errors[] = "La fecha de inicio es obligatoria";
        }

        if ($formData['tipo_contrato'] === 'Arriendo' && empty($formData['fecha_fin'])) {
            $errors[] = "La fecha de fin es obligatoria para contratos de arriendo";
        }

        if (!empty($formData['fecha_inicio']) && !empty($formData['fecha_fin']) && $formData['fecha_fin'] <= $formData['fecha_inicio']) {
            $errors[] = "La fecha de fin debe ser posterior a la fecha de inicio";
        }

        if (empty($formData['valor_contrato']) || $formData['valor_contrato'] <= 0) {
            $errors[] = "El valor del contrato debe ser mayor a 0";
        }

        if (empty($formData['estado']) || !array_key_exists($formData['estado'], CONTRACT_STATUS)) {
            $errors[] = "El estado del contrato es inválido";
        }

        if ($formData['id_inmueble'] <= 0) {
            $errors[] = "Debe seleccionar un inmueble";
        }

        if ($formData['id_cliente'] <= 0) {
            $errors[] = "Debe seleccionar un cliente";
        }

        if (!empty($errors)) {
            $error = implode('<br>', $errors);
        } else {
            // Update contract
            $stmt = $pdo->prepare("
                UPDATE contrato
                SET tipo_contrato = ?, fecha_inicio = ?, fecha_fin = ?, valor_contrato = ?,
                    estado = ?, observaciones = ?, id_inmueble = ?, id_cliente = ?, id_agente = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id_contrato = ?
            ");

            $result = $stmt->execute([
                $formData['tipo_contrato'],
                $formData['fecha_inicio'],
                !empty($formData['fecha_fin']) ? $formData['fecha_fin'] : null,
                $formData['valor_contrato'],
                $formData['estado'],
                $formData['observaciones'],
                $formData['id_inmueble'],
                $formData['id_cliente'],
                !empty($formData['id_agente']) ? $formData['id_agente'] : null,
                $contractId
            ]);

            if ($result) {
                $success = "Contrato actualizado exitosamente";

                // Refresh contract data
                $stmt = $pdo->prepare("
                    SELECT c.*,
                           i.direccion as propiedad_direccion, i.tipo_inmueble,
                           cl.nombre as cliente_nombre, cl.apellido as cliente_apellido,
                           a.nombre as agente_nombre
                    FROM contrato c
                    LEFT JOIN inmueble i ON c.id_inmueble = i.id_inmueble
                    LEFT JOIN cliente cl ON c.id_cliente = cl.id_cliente
                    LEFT JOIN agente a ON c.id_agente = a.id_agente
                    WHERE c.id_contrato = ?
                ");
                $stmt->execute([$contractId]);
                $contract = $stmt->fetch();
            } else {
                $error = "Error al actualizar el contrato. Intente nuevamente.";
            }
        }

    } catch (PDOException $e) {
        error_log("Error updating contract: " . $e->getMessage());
        $error = "Error de base de datos. Intente nuevamente.";
    } catch (Exception $e) {
        error_log("General error updating contract: " . $e->getMessage());
        $error = "Error inesperado. Intente nuevamente.";
    }
}
?>

<div class="module-header">
    <h2>Editar Contrato</h2>
    <p class="module-description">
        Modifique los datos del contrato registrado en el sistema.
    </p>
</div>

<!-- Navigation Breadcrumb -->
<div class="breadcrumb">
    <a href="?module=contracts">Contratos</a> >
    <a href="?module=contracts&action=view&id=<?= $contractId ?>">CON<?= str_pad($contractId, 3, '0', STR_PAD_LEFT) ?></a> >
    Editar
</div>

<!-- Action Buttons -->
<div class="action-buttons">
    <a href="?module=contracts" class="btn btn-secondary">
        ← Volver a Lista
    </a>
    <a href="?module=contracts&action=view&id=<?= $contractId ?>" class="btn btn-info">
        Ver Detalles
    </a>
    <?php if ($contract): ?>
        <button type="button" class="btn btn-warning" onclick="resetForm()">
            Revertir Cambios
        </button>
    <?php endif; ?>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <?= $error ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success">
        <?= htmlspecialchars($success) ?>
        <div class="alert-actions">
            <a href="?module=contracts" class="btn btn-sm btn-outline">Ver Lista</a>
            <a href="?module=contracts&action=view&id=<?= $contractId ?>" class="btn btn-sm btn-info">Ver Detalles</a>
        </div>
    </div>
<?php endif; ?>

<?php if ($contract): ?>
    <!-- Contract Information Summary -->
    <div class="card summary-card status-<?= strtolower($contract['estado']) ?>">
        <h3>
            Contrato: CON<?= str_pad($contract['id_contrato'], 3, '0', STR_PAD_LEFT) ?>
            <span class="contract-status-badge">
                <?= htmlspecialchars($contract['estado']) ?>
            </span>
        </h3>
        <div class="summary-info">
            <div class="summary-item">
                <strong>Tipo:</strong> <?= htmlspecialchars($contract['tipo_contrato']) ?>
            </div>
            <div class="summary-item">
                <strong>Inmueble:</strong> <?= htmlspecialchars($contract['tipo_inmueble']) ?> - <?= htmlspecialchars($contract['propiedad_direccion']) ?>
            </div>
            <div class="summary-item">
                <strong>Cliente:</strong> <?= htmlspecialchars($contract['cliente_nombre'] . ' ' . $contract['cliente_apellido']) ?>
            </div>
        </div>
    </div>

    <!-- Contract Edit Form -->
    <div class="card">
        <h3>Modificar Información del Contrato</h3>

        <form method="POST" id="contractEditForm" class="form-horizontal">

            <!-- Contract Type Section -->
            <fieldset>
                <legend>Tipo de Contrato</legend>

                <div class="form-row">
                    <div class="form-group">
                        <label for="tipo_contrato" class="required">Tipo:</label>
                        <select id="tipo_contrato" name="tipo_contrato" required class="form-control" onchange="toggleContractFields()">
                            <option value="">Seleccione el tipo</option>
                            <?php foreach (CONTRACT_TYPES as $key => $label): ?>
                                <option value="<?= $key ?>" <?= $formData['tipo_contrato'] === $key ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="field-help">Venta o Arriendo</small>
                    </div>

                    <div class="form-group">
                        <label for="estado" class="required">Estado:</label>
                        <select id="estado" name="estado" required class="form-control">
                            <option value="">Seleccione el estado</option>
                            <?php foreach (CONTRACT_STATUS as $key => $label): ?>
                                <option value="<?= $key ?>" <?= $formData['estado'] === $key ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="field-help">Estado actual del contrato</small>
                    </div>
                </div>
            </fieldset>

            <!-- Dates Section -->
            <fieldset>
                <legend>Vigencia del Contrato</legend>

                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha_inicio" class="required">Fecha de Inicio:</label>
                        <input
                            type="date"
                            id="fecha_inicio"
                            name="fecha_inicio"
                            value="<?= htmlspecialchars($formData['fecha_inicio']) ?>"
                            required
                            class="form-control"
                        >
                        <small class="field-help">Fecha de inicio del contrato</small>
                    </div>

                    <div class="form-group">
                        <label for="fecha_fin" id="label_fecha_fin">Fecha de Fin:</label>
                        <input
                            type="date"
                            id="fecha_fin"
                            name="fecha_fin"
                            value="<?= htmlspecialchars($formData['fecha_fin']) ?>"
                            class="form-control"
                        >
                        <small class="field-help" id="help_fecha_fin">Fecha de finalización (opcional para ventas)</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="valor_contrato" class="required">Valor del Contrato:</label>
                        <input
                            type="number"
                            id="valor_contrato"
                            name="valor_contrato"
                            value="<?= htmlspecialchars($formData['valor_contrato']) ?>"
                            required
                            min="0"
                            step="0.01"
                            class="form-control"
                            placeholder="0.00"
                        >
                        <small class="field-help">Valor total del contrato en COP</small>
                    </div>

                    <div class="form-group">
                        <label>&nbsp;</label>
                        <div class="contract-duration" id="contract_duration_display">
                            <span class="duration-info">Duración: Se calculará automáticamente</span>
                        </div>
                    </div>
                </div>
            </fieldset>

            <!-- Property Selection Section -->
            <fieldset>
                <legend>Inmueble</legend>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="id_inmueble" class="required">Seleccione el Inmueble:</label>
                        <select id="id_inmueble" name="id_inmueble" required class="form-control">
                            <option value="">Seleccione un inmueble</option>
                            <?php foreach ($properties as $property): ?>
                                <option
                                    value="<?= $property['id_inmueble'] ?>"
                                    data-status="<?= $property['estado'] ?>"
                                    <?= $formData['id_inmueble'] == $property['id_inmueble'] ? 'selected' : '' ?>
                                >
                                    <span class="property-id">INM<?= str_pad($property['id_inmueble'], 3, '0', STR_PAD_LEFT) ?></span> -
                                    <?= htmlspecialchars($property['tipo_inmueble']) ?> en
                                    <?= htmlspecialchars($property['ciudad']) ?> -
                                    <?= htmlspecialchars($property['direccion']) ?>
                                    (<?= htmlspecialchars($property['estado']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="field-help">Propiedad objeto del contrato</small>
                    </div>
                </div>
            </fieldset>

            <!-- Client Selection Section -->
            <fieldset>
                <legend>Cliente</legend>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="id_cliente" class="required">Seleccione el Cliente:</label>
                        <select id="id_cliente" name="id_cliente" required class="form-control">
                            <option value="">Seleccione un cliente</option>
                            <?php foreach ($clients as $client): ?>
                                <option
                                    value="<?= $client['id_cliente'] ?>"
                                    <?= $formData['id_cliente'] == $client['id_cliente'] ? 'selected' : '' ?>
                                >
                                    CLI<?= str_pad($client['id_cliente'], 3, '0', STR_PAD_LEFT) ?> -
                                    <?= htmlspecialchars($client['nombre'] . ' ' . $client['apellido']) ?>
                                    (<?= htmlspecialchars($client['tipo_cliente']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="field-help">Cliente que firma el contrato</small>
                    </div>
                </div>
            </fieldset>

            <!-- Agent Selection Section -->
            <fieldset>
                <legend>Agente Responsable</legend>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="id_agente">Seleccione el Agente:</label>
                        <select id="id_agente" name="id_agente" class="form-control">
                            <option value="">Sin agente asignado</option>
                            <?php foreach ($agents as $agent): ?>
                                <option
                                    value="<?= $agent['id_agente'] ?>"
                                    <?= $formData['id_agente'] == $agent['id_agente'] ? 'selected' : '' ?>
                                >
                                    AGE<?= str_pad($agent['id_agente'], 3, '0', STR_PAD_LEFT) ?> -
                                    <?= htmlspecialchars($agent['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="field-help">Agente que gestiona el contrato (opcional)</small>
                    </div>
                </div>
            </fieldset>

            <!-- Observations Section -->
            <fieldset>
                <legend>Observaciones</legend>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="observaciones">Notas Adicionales:</label>
                        <textarea
                            id="observaciones"
                            name="observaciones"
                            rows="4"
                            class="form-control"
                            placeholder="Ingrese observaciones sobre el contrato (opcional)"
                        ><?= htmlspecialchars($formData['observaciones']) ?></textarea>
                        <small class="field-help">Información adicional o cláusulas especiales</small>
                    </div>
                </div>
            </fieldset>

            <!-- Form Actions -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    Guardar Cambios
                </button>
                <a href="?module=contracts&action=view&id=<?= $contractId ?>" class="btn btn-secondary">
                    Cancelar
                </a>
                <button type="button" class="btn btn-outline" onclick="resetForm()">
                    Revertir
                </button>
            </div>
        </form>
    </div>

<?php else: ?>
    <!-- No Contract Found -->
    <div class="card">
        <div class="no-results">
            <h3>Contrato No Encontrado</h3>
            <p>El contrato solicitado no existe o ha sido eliminado.</p>
            <a href="?module=contracts" class="btn btn-primary">Volver a Lista de Contratos</a>
        </div>
    </div>
<?php endif; ?>

<!-- JavaScript for Enhanced Functionality -->
<script>
/**
 * Contract edit form functionality
 */

// Store original form values for reset functionality
const originalFormData = <?= json_encode($formData) ?>;

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('contractEditForm');

    if (form) {
        // Form validation
        form.addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
            }
        });

        // Date change handlers
        document.getElementById('fecha_inicio').addEventListener('change', calculateDuration);
        document.getElementById('fecha_fin').addEventListener('change', calculateDuration);

        // Initialize toggle
        toggleContractFields();
        calculateDuration();

        // Track changes for unsaved changes warning
        trackFormChanges();
    }
});

function toggleContractFields() {
    const tipoContrato = document.getElementById('tipo_contrato').value;
    const fechaFin = document.getElementById('fecha_fin');
    const labelFechaFin = document.getElementById('label_fecha_fin');
    const helpFechaFin = document.getElementById('help_fecha_fin');

    if (tipoContrato === 'Arriendo') {
        fechaFin.required = true;
        labelFechaFin.innerHTML = 'Fecha de Fin: <span class="required-mark">*</span>';
        helpFechaFin.textContent = 'Fecha de finalización del arriendo (obligatorio)';
    } else {
        fechaFin.required = false;
        labelFechaFin.textContent = 'Fecha de Fin:';
        helpFechaFin.textContent = 'Fecha de finalización (opcional para ventas)';
    }

    calculateDuration();
}

function calculateDuration() {
    const fechaInicio = document.getElementById('fecha_inicio').value;
    const fechaFin = document.getElementById('fecha_fin').value;
    const display = document.getElementById('contract_duration_display');

    if (fechaInicio && fechaFin) {
        const inicio = new Date(fechaInicio);
        const fin = new Date(fechaFin);
        const diffTime = fin - inicio;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        if (diffDays > 0) {
            const months = Math.floor(diffDays / 30);
            const days = diffDays % 30;
            let durationText = `Duración: ${diffDays} días`;

            if (months > 0) {
                durationText += ` (${months} ${months === 1 ? 'mes' : 'meses'}`;
                if (days > 0) {
                    durationText += ` y ${days} ${days === 1 ? 'día' : 'días'}`;
                }
                durationText += ')';
            }

            display.innerHTML = `<span class="duration-info success">${durationText}</span>`;
        } else {
            display.innerHTML = `<span class="duration-info error">La fecha de fin debe ser posterior a la fecha de inicio</span>`;
        }
    } else {
        display.innerHTML = '<span class="duration-info">Ingrese ambas fechas para calcular la duración</span>';
    }
}

function validateForm() {
    let isValid = true;
    const errors = [];

    clearErrorStates();

    // Validate tipo_contrato
    const tipoContrato = document.getElementById('tipo_contrato').value;
    if (!tipoContrato) {
        errors.push('El tipo de contrato es obligatorio');
        markFieldError(document.getElementById('tipo_contrato'));
        isValid = false;
    }

    // Validate fecha_inicio
    const fechaInicio = document.getElementById('fecha_inicio').value;
    if (!fechaInicio) {
        errors.push('La fecha de inicio es obligatoria');
        markFieldError(document.getElementById('fecha_inicio'));
        isValid = false;
    }

    // Validate fecha_fin for Arriendo
    const fechaFin = document.getElementById('fecha_fin').value;
    if (tipoContrato === 'Arriendo' && !fechaFin) {
        errors.push('La fecha de fin es obligatoria para contratos de arriendo');
        markFieldError(document.getElementById('fecha_fin'));
        isValid = false;
    }

    // Validate date order
    if (fechaInicio && fechaFin && fechaFin <= fechaInicio) {
        errors.push('La fecha de fin debe ser posterior a la fecha de inicio');
        markFieldError(document.getElementById('fecha_fin'));
        isValid = false;
    }

    // Validate valor_contrato
    const valorContrato = parseFloat(document.getElementById('valor_contrato').value);
    if (!valorContrato || valorContrato <= 0) {
        errors.push('El valor del contrato debe ser mayor a 0');
        markFieldError(document.getElementById('valor_contrato'));
        isValid = false;
    }

    // Validate estado
    if (!document.getElementById('estado').value) {
        errors.push('El estado del contrato es obligatorio');
        markFieldError(document.getElementById('estado'));
        isValid = false;
    }

    // Validate id_inmueble
    if (!document.getElementById('id_inmueble').value) {
        errors.push('Debe seleccionar un inmueble');
        markFieldError(document.getElementById('id_inmueble'));
        isValid = false;
    }

    // Validate id_cliente
    if (!document.getElementById('id_cliente').value) {
        errors.push('Debe seleccionar un cliente');
        markFieldError(document.getElementById('id_cliente'));
        isValid = false;
    }

    if (!isValid) {
        showFormErrors(errors);
    }

    return isValid;
}

function markFieldError(field, message = '') {
    field.classList.add('error');
    const existingError = field.parentNode.querySelector('.error-message');
    if (existingError) existingError.remove();
    if (message) {
        const errorElement = document.createElement('small');
        errorElement.className = 'error-message';
        errorElement.textContent = message;
        field.parentNode.appendChild(errorElement);
    }
}

function clearFieldError(field) {
    field.classList.remove('error');
    const errorMessage = field.parentNode.querySelector('.error-message');
    if (errorMessage) errorMessage.remove();
}

function clearErrorStates() {
    document.querySelectorAll('.form-control.error').forEach(field => clearFieldError(field));
    const alertError = document.querySelector('.alert-danger');
    if (alertError) alertError.style.display = 'none';
}

function showFormErrors(errors) {
    let alertDiv = document.querySelector('.alert-danger');
    if (!alertDiv) {
        alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger';
        document.querySelector('.card').insertBefore(alertDiv, document.querySelector('form'));
    }
    alertDiv.innerHTML = errors.join('<br>');
    alertDiv.style.display = 'block';
    alertDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function resetForm() {
    if (confirm('¿Está seguro de que desea revertir todos los cambios?')) {
        Object.keys(originalFormData).forEach(fieldName => {
            const field = document.getElementById(fieldName);
            if (field) field.value = originalFormData[fieldName];
        });
        clearErrorStates();
        toggleContractFields();
        calculateDuration();
    }
}

function trackFormChanges() {
    let hasChanges = false;
    const form = document.getElementById('contractEditForm');
    if (form) {
        form.addEventListener('input', () => hasChanges = true);
        window.addEventListener('beforeunload', function(e) {
            if (hasChanges) {
                e.preventDefault();
                e.returnValue = '¿Está seguro de que desea salir? Los cambios no guardados se perderán.';
            }
        });
        form.addEventListener('submit', () => hasChanges = false);
    }
}
</script>

<style>
/* Additional styles specific to contract editing */
.summary-card {
    color: white;
    margin-bottom: var(--spacing-lg);
}

.summary-card.status-borrador { background: linear-gradient(135deg, #6c757d, #5a6268); }
.summary-card.status-activo { background: linear-gradient(135deg, #28a745, #20c997); }
.summary-card.status-finalizado { background: linear-gradient(135deg, #17a2b8, #138496); }
.summary-card.status-cancelado { background: linear-gradient(135deg, #dc3545, #c82333); }

.contract-status-badge {
    background: rgba(255, 255, 255, 0.3);
    padding: 6px 12px;
    border-radius: var(--border-radius);
    font-size: var(--font-size-sm);
}

.duration-info {
    display: inline-block;
    padding: 8px 12px;
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
    font-size: var(--font-size-sm);
}

.duration-info.success { color: #28a745; font-weight: 600; }
.duration-info.error { color: #dc3545; font-weight: 600; }

.required-mark { color: #dc3545; }

/* Responsive adjustments */
@media (max-width: 768px) {
    .form-row { grid-template-columns: 1fr; }
}
</style>