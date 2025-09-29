<?php
/**
 * Rental Edit Form - Real Estate Management System
 * Form to edit existing rental agreements
 * Educational PHP/MySQL Project
 */

// Prevent direct access
if (!defined('APP_ACCESS')) {
    die('Direct access not permitted');
}

$error = '';
$success = '';
$rental = null;
$rentalId = (int)($_GET['id'] ?? 0);

// Initialize form data
$formData = [
    'fecha_inicio' => '',
    'fecha_fin' => '',
    'canon_mensual' => '',
    'deposito' => '',
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

// Validate rental ID and load existing data
if ($rentalId <= 0) {
    $error = "ID de arriendo inválido";
} else {
    try {
        $db = new Database();
        $pdo = $db->getConnection();

        // Load existing rental data with related information
        $stmt = $pdo->prepare("
            SELECT a.*,
                   i.direccion as propiedad_direccion, i.tipo_inmueble, i.ciudad,
                   cl.nombre as cliente_nombre, cl.apellido as cliente_apellido,
                   ag.nombre as agente_nombre
            FROM arriendo a
            LEFT JOIN inmueble i ON a.id_inmueble = i.id_inmueble
            LEFT JOIN cliente cl ON a.id_cliente = cl.id_cliente
            LEFT JOIN agente ag ON a.id_agente = ag.id_agente
            WHERE a.id_arriendo = ?
        ");
        $stmt->execute([$rentalId]);
        $rental = $stmt->fetch();

        if (!$rental) {
            $error = "Arriendo no encontrado";
        } else {
            // Populate form data with existing values
            $formData = [
                'fecha_inicio' => $rental['fecha_inicio'],
                'fecha_fin' => $rental['fecha_fin'],
                'canon_mensual' => $rental['canon_mensual'],
                'deposito' => $rental['deposito'] ?? '',
                'estado' => $rental['estado'],
                'observaciones' => $rental['observaciones'] ?? '',
                'id_inmueble' => $rental['id_inmueble'],
                'id_cliente' => $rental['id_cliente'],
                'id_agente' => $rental['id_agente'] ?? ''
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
        error_log("Error loading rental: " . $e->getMessage());
        $error = "Error al cargar los datos del arriendo";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error && $rental) {
    try {
        // Get form data
        $formData = [
            'fecha_inicio' => trim($_POST['fecha_inicio'] ?? ''),
            'fecha_fin' => trim($_POST['fecha_fin'] ?? ''),
            'canon_mensual' => trim($_POST['canon_mensual'] ?? ''),
            'deposito' => trim($_POST['deposito'] ?? ''),
            'estado' => $_POST['estado'] ?? '',
            'observaciones' => trim($_POST['observaciones'] ?? ''),
            'id_inmueble' => (int)($_POST['id_inmueble'] ?? 0),
            'id_cliente' => (int)($_POST['id_cliente'] ?? 0),
            'id_agente' => (int)($_POST['id_agente'] ?? 0)
        ];

        // Validation
        $errors = [];

        if (empty($formData['fecha_inicio'])) {
            $errors[] = "La fecha de inicio es obligatoria";
        }

        if (empty($formData['fecha_fin'])) {
            $errors[] = "La fecha de fin es obligatoria";
        }

        if (!empty($formData['fecha_inicio']) && !empty($formData['fecha_fin']) && $formData['fecha_fin'] <= $formData['fecha_inicio']) {
            $errors[] = "La fecha de fin debe ser posterior a la fecha de inicio";
        }

        if (empty($formData['canon_mensual']) || $formData['canon_mensual'] <= 0) {
            $errors[] = "El canon mensual debe ser mayor a 0";
        }

        if (!empty($formData['deposito']) && $formData['deposito'] < 0) {
            $errors[] = "El depósito no puede ser negativo";
        }

        if (empty($formData['estado']) || !array_key_exists($formData['estado'], RENTAL_STATUS)) {
            $errors[] = "El estado del arriendo es inválido";
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
            // Update rental
            $stmt = $pdo->prepare("
                UPDATE arriendo
                SET fecha_inicio = ?, fecha_fin = ?, canon_mensual = ?, deposito = ?,
                    estado = ?, observaciones = ?, id_inmueble = ?, id_cliente = ?, id_agente = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id_arriendo = ?
            ");

            $result = $stmt->execute([
                $formData['fecha_inicio'],
                $formData['fecha_fin'],
                $formData['canon_mensual'],
                !empty($formData['deposito']) ? $formData['deposito'] : null,
                $formData['estado'],
                $formData['observaciones'],
                $formData['id_inmueble'],
                $formData['id_cliente'],
                !empty($formData['id_agente']) ? $formData['id_agente'] : null,
                $rentalId
            ]);

            if ($result) {
                $success = "Arriendo actualizado exitosamente";

                // Refresh rental data
                $stmt = $pdo->prepare("
                    SELECT a.*,
                           i.direccion as propiedad_direccion, i.tipo_inmueble,
                           cl.nombre as cliente_nombre, cl.apellido as cliente_apellido,
                           ag.nombre as agente_nombre
                    FROM arriendo a
                    LEFT JOIN inmueble i ON a.id_inmueble = i.id_inmueble
                    LEFT JOIN cliente cl ON a.id_cliente = cl.id_cliente
                    LEFT JOIN agente ag ON a.id_agente = ag.id_agente
                    WHERE a.id_arriendo = ?
                ");
                $stmt->execute([$rentalId]);
                $rental = $stmt->fetch();
            } else {
                $error = "Error al actualizar el arriendo. Intente nuevamente.";
            }
        }

    } catch (PDOException $e) {
        error_log("Error updating rental: " . $e->getMessage());
        $error = "Error de base de datos. Intente nuevamente.";
    } catch (Exception $e) {
        error_log("General error updating rental: " . $e->getMessage());
        $error = "Error inesperado. Intente nuevamente.";
    }
}
?>

<div class="module-header">
    <h2>Editar Arriendo</h2>
    <p class="module-description">
        Modifique los datos del contrato de arrendamiento registrado en el sistema.
    </p>
</div>

<!-- Navigation Breadcrumb -->
<div class="breadcrumb">
    <a href="?module=rentals">Arriendos</a> >
    <a href="?module=rentals&action=view&id=<?= $rentalId ?>">ARR<?= str_pad($rentalId, 3, '0', STR_PAD_LEFT) ?></a> >
    Editar
</div>

<!-- Action Buttons -->
<div class="action-buttons">
    <a href="?module=rentals" class="btn btn-secondary">
        ← Volver a Lista
    </a>
    <a href="?module=rentals&action=view&id=<?= $rentalId ?>" class="btn btn-info">
        Ver Detalles
    </a>
    <?php if ($rental): ?>
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
            <a href="?module=rentals" class="btn btn-sm btn-outline">Ver Lista</a>
            <a href="?module=rentals&action=view&id=<?= $rentalId ?>" class="btn btn-sm btn-info">Ver Detalles</a>
        </div>
    </div>
<?php endif; ?>

<?php if ($rental): ?>
    <!-- Rental Information Summary -->
    <div class="card summary-card status-<?= strtolower($rental['estado']) ?>">
        <h3>
            Arriendo: ARR<?= str_pad($rental['id_arriendo'], 3, '0', STR_PAD_LEFT) ?>
            <span class="rental-status-badge">
                <?= htmlspecialchars($rental['estado']) ?>
            </span>
        </h3>
        <div class="summary-info">
            <div class="summary-item">
                <strong>Canon Mensual:</strong> <?= formatCurrency($rental['canon_mensual']) ?>
            </div>
            <div class="summary-item">
                <strong>Inmueble:</strong> <?= htmlspecialchars($rental['tipo_inmueble']) ?> - <?= htmlspecialchars($rental['propiedad_direccion']) ?>
            </div>
            <div class="summary-item">
                <strong>Arrendatario:</strong> <?= htmlspecialchars($rental['cliente_nombre'] . ' ' . $rental['cliente_apellido']) ?>
            </div>
        </div>
    </div>

    <!-- Rental Edit Form -->
    <div class="card">
        <h3>Modificar Información del Arriendo</h3>

        <form method="POST" id="rentalEditForm" class="form-horizontal">

            <!-- Dates Section -->
            <fieldset>
                <legend>Período del Arriendo</legend>

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
                        <label for="fecha_fin" class="required">Fecha de Fin:</label>
                        <input
                            type="date"
                            id="fecha_fin"
                            name="fecha_fin"
                            value="<?= htmlspecialchars($formData['fecha_fin']) ?>"
                            required
                            class="form-control"
                        >
                        <small class="field-help">Fecha de finalización del contrato</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <div class="rental-duration" id="rental_duration_display">
                            <span class="duration-info">Duración: Se calculará automáticamente</span>
                        </div>
                    </div>
                </div>
            </fieldset>

            <!-- Financial Information Section -->
            <fieldset>
                <legend>Información Financiera</legend>

                <div class="form-row">
                    <div class="form-group">
                        <label for="canon_mensual" class="required">Canon Mensual:</label>
                        <input
                            type="number"
                            id="canon_mensual"
                            name="canon_mensual"
                            value="<?= htmlspecialchars($formData['canon_mensual']) ?>"
                            required
                            min="0"
                            step="1000"
                            class="form-control"
                            placeholder="0"
                        >
                        <small class="field-help">Valor mensual del arriendo en COP</small>
                    </div>

                    <div class="form-group">
                        <label for="deposito">Depósito de Garantía:</label>
                        <input
                            type="number"
                            id="deposito"
                            name="deposito"
                            value="<?= htmlspecialchars($formData['deposito']) ?>"
                            min="0"
                            step="1000"
                            class="form-control"
                            placeholder="0"
                        >
                        <small class="field-help">Depósito de garantía (opcional)</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="estado" class="required">Estado:</label>
                        <select id="estado" name="estado" required class="form-control">
                            <option value="">Seleccione el estado</option>
                            <?php foreach (RENTAL_STATUS as $key => $label): ?>
                                <option value="<?= $key ?>" <?= $formData['estado'] === $key ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="field-help">Estado actual del contrato de arriendo</small>
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
                                    INM<?= str_pad($property['id_inmueble'], 3, '0', STR_PAD_LEFT) ?> -
                                    <?= htmlspecialchars($property['tipo_inmueble']) ?> en
                                    <?= htmlspecialchars($property['ciudad']) ?> -
                                    <?= htmlspecialchars($property['direccion']) ?>
                                    (<?= htmlspecialchars($property['estado']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="field-help">Propiedad objeto del contrato de arrendamiento</small>
                    </div>
                </div>
            </fieldset>

            <!-- Client Selection Section -->
            <fieldset>
                <legend>Arrendatario</legend>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="id_cliente" class="required">Seleccione el Arrendatario:</label>
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
                        <small class="field-help">Cliente que arrienda el inmueble</small>
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
                        <small class="field-help">Agente que gestiona el arriendo (opcional)</small>
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
                            placeholder="Ingrese observaciones sobre el arriendo, cláusulas especiales, etc. (opcional)"
                        ><?= htmlspecialchars($formData['observaciones']) ?></textarea>
                        <small class="field-help">Información adicional, condiciones especiales, etc.</small>
                    </div>
                </div>
            </fieldset>

            <!-- Form Actions -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    Guardar Cambios
                </button>
                <a href="?module=rentals&action=view&id=<?= $rentalId ?>" class="btn btn-secondary">
                    Cancelar
                </a>
                <button type="button" class="btn btn-outline" onclick="resetForm()">
                    Revertir
                </button>
            </div>
        </form>
    </div>

<?php else: ?>
    <!-- No Rental Found -->
    <div class="card">
        <div class="no-results">
            <h3>Arriendo No Encontrado</h3>
            <p>El arriendo solicitado no existe o ha sido eliminado.</p>
            <a href="?module=rentals" class="btn btn-primary">Volver a Lista de Arriendos</a>
        </div>
    </div>
<?php endif; ?>

<!-- JavaScript for Enhanced Functionality -->
<script>
/**
 * Rental edit form functionality
 */

// Store original form values for reset functionality
const originalFormData = <?= json_encode($formData) ?>;

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('rentalEditForm');

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

        // Initialize calculations
        calculateDuration();

        // Track changes for unsaved changes warning
        trackFormChanges();
    }
});

function calculateDuration() {
    const fechaInicio = document.getElementById('fecha_inicio').value;
    const fechaFin = document.getElementById('fecha_fin').value;
    const display = document.getElementById('rental_duration_display');

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

    // Validate fecha_inicio
    const fechaInicio = document.getElementById('fecha_inicio').value;
    if (!fechaInicio) {
        errors.push('La fecha de inicio es obligatoria');
        markFieldError(document.getElementById('fecha_inicio'));
        isValid = false;
    }

    // Validate fecha_fin
    const fechaFin = document.getElementById('fecha_fin').value;
    if (!fechaFin) {
        errors.push('La fecha de fin es obligatoria');
        markFieldError(document.getElementById('fecha_fin'));
        isValid = false;
    }

    // Validate date order
    if (fechaInicio && fechaFin && fechaFin <= fechaInicio) {
        errors.push('La fecha de fin debe ser posterior a la fecha de inicio');
        markFieldError(document.getElementById('fecha_fin'));
        isValid = false;
    }

    // Validate canon_mensual
    const canonMensual = parseFloat(document.getElementById('canon_mensual').value);
    if (!canonMensual || canonMensual <= 0) {
        errors.push('El canon mensual debe ser mayor a 0');
        markFieldError(document.getElementById('canon_mensual'));
        isValid = false;
    }

    // Validate estado
    if (!document.getElementById('estado').value) {
        errors.push('El estado del arriendo es obligatorio');
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
        calculateDuration();
    }
}

function trackFormChanges() {
    let hasChanges = false;
    const form = document.getElementById('rentalEditForm');
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
/* Additional styles specific to rental editing */
.summary-card {
    color: white;
    margin-bottom: var(--spacing-lg);
}

.summary-card.status-activo { background: linear-gradient(135deg, #28a745, #20c997); }
.summary-card.status-vencido { background: linear-gradient(135deg, #dc3545, #c82333); }
.summary-card.status-terminado { background: linear-gradient(135deg, #6c757d, #5a6268); }
.summary-card.status-moroso { background: linear-gradient(135deg, #ff9800, #f57c00); }

.rental-status-badge {
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

.required::after {
    content: " *";
    color: #dc3545;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .form-row { grid-template-columns: 1fr; }
}
</style>