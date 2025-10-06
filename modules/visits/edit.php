<?php
/**
 * Visit Edit Form - Real Estate Management System
 * Form to edit existing property visit appointments
 * Educational PHP/MySQL Project
 */

// Prevent direct access
if (!defined('APP_ACCESS')) {
    die('Direct access not permitted');
}

$error = '';
$success = '';
$visit = null;
$visitId = (int)($_GET['id'] ?? 0);

// Initialize form data
$formData = [
    'fecha_visita' => '',
    'hora_visita' => '',
    'estado' => '',
    'calificacion' => '',
    'observaciones' => '',
    'id_inmueble' => '',
    'id_cliente' => '',
    'id_agente' => ''
];

// Available data for selection
$properties = [];
$clients = [];
$agents = [];

// Validate visit ID and load existing data
if ($visitId <= 0) {
    $error = "ID de visita inválido";
} else {
    try {
        $db = new Database();
        $pdo = $db->getConnection();

        // Load existing visit data with related information
        $stmt = $pdo->prepare("
            SELECT v.*,
                   i.direccion as propiedad_direccion, i.tipo_inmueble, i.ciudad,
                   cl.nombre as cliente_nombre, cl.apellido as cliente_apellido,
                   a.nombre as agente_nombre
            FROM visita v
            LEFT JOIN inmueble i ON v.id_inmueble = i.id_inmueble
            LEFT JOIN cliente cl ON v.id_cliente = cl.id_cliente
            LEFT JOIN agente a ON v.id_agente = a.id_agente
            WHERE v.id_visita = ?
        ");
        $stmt->execute([$visitId]);
        $visit = $stmt->fetch();

        if (!$visit) {
            $error = "Visita no encontrada";
        } else {
            // Populate form data with existing values
            $formData = [
                'fecha_visita' => $visit['fecha_visita'],
                'hora_visita' => $visit['hora_visita'],
                'estado' => $visit['estado'],
                'calificacion' => $visit['calificacion'] ?? '',
                'observaciones' => $visit['observaciones'] ?? '',
                'id_inmueble' => $visit['id_inmueble'],
                'id_cliente' => $visit['id_cliente'],
                'id_agente' => $visit['id_agente']
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
        error_log("Error loading visit: " . $e->getMessage());
        $error = "Error al cargar los datos de la visita";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error && $visit) {
    try {
        // Get form data
        $formData = [
            'fecha_visita' => trim($_POST['fecha_visita'] ?? ''),
            'hora_visita' => trim($_POST['hora_visita'] ?? ''),
            'estado' => $_POST['estado'] ?? '',
            'calificacion' => trim($_POST['calificacion'] ?? ''),
            'observaciones' => trim($_POST['observaciones'] ?? ''),
            'id_inmueble' => (int)($_POST['id_inmueble'] ?? 0),
            'id_cliente' => (int)($_POST['id_cliente'] ?? 0),
            'id_agente' => (int)($_POST['id_agente'] ?? 0)
        ];

        // Validation
        $errors = [];

        if (empty($formData['fecha_visita'])) {
            $errors[] = "La fecha de visita es obligatoria";
        }

        if (empty($formData['hora_visita'])) {
            $errors[] = "La hora de visita es obligatoria";
        }

        if (empty($formData['estado']) || !array_key_exists($formData['estado'], VISIT_STATUS)) {
            $errors[] = "El estado de la visita es inválido";
        }

        if ($formData['id_inmueble'] <= 0) {
            $errors[] = "Debe seleccionar un inmueble";
        }

        if ($formData['id_cliente'] <= 0) {
            $errors[] = "Debe seleccionar un cliente";
        }

        if ($formData['id_agente'] <= 0) {
            $errors[] = "Debe seleccionar un agente";
        }

        if (!empty($errors)) {
            $error = implode('<br>', $errors);
        } else {
            // Update visit
            $stmt = $pdo->prepare("
                UPDATE visita
                SET fecha_visita = ?, hora_visita = ?, estado = ?, calificacion = ?,
                    observaciones = ?, id_inmueble = ?, id_cliente = ?, id_agente = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id_visita = ?
            ");

            $result = $stmt->execute([
                $formData['fecha_visita'],
                $formData['hora_visita'],
                $formData['estado'],
                $formData['calificacion'],
                $formData['observaciones'],
                $formData['id_inmueble'],
                $formData['id_cliente'],
                $formData['id_agente'],
                $visitId
            ]);

            if ($result) {
                $success = "Visita actualizada exitosamente";

                // Refresh visit data
                $stmt = $pdo->prepare("
                    SELECT v.*,
                           i.direccion as propiedad_direccion, i.tipo_inmueble,
                           cl.nombre as cliente_nombre, cl.apellido as cliente_apellido,
                           a.nombre as agente_nombre
                    FROM visita v
                    LEFT JOIN inmueble i ON v.id_inmueble = i.id_inmueble
                    LEFT JOIN cliente cl ON v.id_cliente = cl.id_cliente
                    LEFT JOIN agente a ON v.id_agente = a.id_agente
                    WHERE v.id_visita = ?
                ");
                $stmt->execute([$visitId]);
                $visit = $stmt->fetch();
            } else {
                $error = "Error al actualizar la visita. Intente nuevamente.";
            }
        }

    } catch (PDOException $e) {
        error_log("Error updating visit: " . $e->getMessage());
        $error = "Error de base de datos. Intente nuevamente.";
    } catch (Exception $e) {
        error_log("General error updating visit: " . $e->getMessage());
        $error = "Error inesperado. Intente nuevamente.";
    }
}
?>

<div class="module-header">
    <h2>Editar Visita</h2>
    <p class="module-description">
        Modifique los datos de la visita programada.
    </p>
</div>

<!-- Navigation Breadcrumb -->
<div class="breadcrumb">
    <a href="?module=visits">Visitas</a> >
    <a href="?module=visits&action=view&id=<?= $visitId ?>">VIS<?= str_pad($visitId, 3, '0', STR_PAD_LEFT) ?></a> >
    Editar
</div>

<!-- Action Buttons -->
<div class="action-buttons">
    <a href="?module=visits" class="btn btn-secondary">
        ← Volver a Lista
    </a>
    <a href="?module=visits&action=view&id=<?= $visitId ?>" class="btn btn-info">
        Ver Detalles
    </a>
    <?php if ($visit): ?>
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
            <a href="?module=visits" class="btn btn-sm btn-outline">Ver Lista</a>
            <a href="?module=visits&action=view&id=<?= $visitId ?>" class="btn btn-sm btn-info">Ver Detalles</a>
        </div>
    </div>
<?php endif; ?>

<?php if ($visit): ?>
    <!-- Visit Information Summary -->
    <div class="card summary-card status-<?= strtolower($visit['estado']) ?>">
        <h3>
            Visita: VIS<?= str_pad($visit['id_visita'], 3, '0', STR_PAD_LEFT) ?>
            <span class="visit-status-badge">
                <?= htmlspecialchars($visit['estado']) ?>
            </span>
        </h3>
        <div class="summary-info">
            <div class="summary-item">
                <strong>Fecha:</strong> <?= formatDate($visit['fecha_visita']) ?> a las <?= $visit['hora_visita'] ?>
            </div>
            <div class="summary-item">
                <strong>Inmueble:</strong> <?= htmlspecialchars($visit['tipo_inmueble']) ?> - <?= htmlspecialchars($visit['propiedad_direccion']) ?>
            </div>
            <div class="summary-item">
                <strong>Cliente:</strong> <?= htmlspecialchars($visit['cliente_nombre'] . ' ' . $visit['cliente_apellido']) ?>
            </div>
        </div>
    </div>

    <!-- Visit Edit Form -->
    <div class="card">
        <h3>Modificar Información de la Visita</h3>

        <form method="POST" id="visitEditForm" class="form-horizontal">

            <!-- Date and Time Section -->
            <fieldset>
                <legend>Fecha y Hora de la Visita</legend>

                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha_visita" class="required">Fecha de Visita:</label>
                        <input
                            type="date"
                            id="fecha_visita"
                            name="fecha_visita"
                            value="<?= htmlspecialchars($formData['fecha_visita']) ?>"
                            required
                            class="form-control"
                        >
                        <small class="field-help">Fecha programada para la visita</small>
                    </div>

                    <div class="form-group">
                        <label for="hora_visita" class="required">Hora de Visita:</label>
                        <input
                            type="time"
                            id="hora_visita"
                            name="hora_visita"
                            value="<?= htmlspecialchars($formData['hora_visita']) ?>"
                            required
                            min="08:00"
                            max="18:00"
                            class="form-control"
                        >
                        <small class="field-help">Horario: 8:00 AM - 6:00 PM</small>
                    </div>
                </div>
            </fieldset>

            <!-- Status and Rating Section -->
            <fieldset>
                <legend>Estado de la Visita</legend>

                <div class="form-row">
                    <div class="form-group">
                        <label for="estado" class="required">Estado:</label>
                        <select id="estado" name="estado" required class="form-control">
                            <option value="">Seleccione el estado</option>
                            <?php foreach (VISIT_STATUS as $key => $label): ?>
                                <option value="<?= $key ?>" <?= $formData['estado'] === $key ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="field-help">Estado actual de la visita</small>
                    </div>

                    <div class="form-group">
                        <label for="calificacion">Nivel de Interés:</label>
                        <select id="calificacion" name="calificacion" class="form-control">
                            <option value="">Sin calificar</option>
                            <option value="Muy Alto" <?= $formData['calificacion'] === 'Muy Alto' ? 'selected' : '' ?>>Muy Alto</option>
                            <option value="Alto" <?= $formData['calificacion'] === 'Alto' ? 'selected' : '' ?>>Alto</option>
                            <option value="Medio" <?= $formData['calificacion'] === 'Medio' ? 'selected' : '' ?>>Medio</option>
                            <option value="Bajo" <?= $formData['calificacion'] === 'Bajo' ? 'selected' : '' ?>>Bajo</option>
                            <option value="Sin Interés" <?= $formData['calificacion'] === 'Sin Interés' ? 'selected' : '' ?>>Sin Interés</option>
                        </select>
                        <small class="field-help">Nivel de interés del cliente (opcional)</small>
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
                        <small class="field-help">Propiedad a visitar</small>
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
                        <small class="field-help">Cliente que realizará la visita</small>
                    </div>
                </div>
            </fieldset>

            <!-- Agent Selection Section -->
            <fieldset>
                <legend>Agente Responsable</legend>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="id_agente" class="required">Seleccione el Agente:</label>
                        <select id="id_agente" name="id_agente" required class="form-control">
                            <option value="">Seleccione un agente</option>
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
                        <small class="field-help">Agente que acompañará en la visita</small>
                    </div>
                </div>
            </fieldset>

            <!-- Observations Section -->
            <fieldset>
                <legend>Observaciones</legend>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="observaciones">Notas de la Visita:</label>
                        <textarea
                            id="observaciones"
                            name="observaciones"
                            rows="4"
                            class="form-control"
                            placeholder="Ingrese observaciones sobre la visita, comentarios del cliente, etc. (opcional)"
                        ><?= htmlspecialchars($formData['observaciones']) ?></textarea>
                        <small class="field-help">Información adicional sobre la visita</small>
                    </div>
                </div>
            </fieldset>

            <!-- Form Actions -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    Guardar Cambios
                </button>
                <a href="?module=visits&action=view&id=<?= $visitId ?>" class="btn btn-secondary">
                    Cancelar
                </a>
                <button type="button" class="btn btn-outline" onclick="resetForm()">
                    Revertir
                </button>
            </div>
        </form>
    </div>

<?php else: ?>
    <!-- No Visit Found -->
    <div class="card">
        <div class="no-results">
            <h3>Visita No Encontrada</h3>
            <p>La visita solicitada no existe o ha sido eliminada.</p>
            <a href="?module=visits" class="btn btn-primary">Volver a Lista de Visitas</a>
        </div>
    </div>
<?php endif; ?>

<!-- JavaScript for Enhanced Functionality -->
<script>
/**
 * Visit edit form functionality
 */

// Store original form values for reset functionality
const originalFormData = <?= json_encode($formData) ?>;

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('visitEditForm');

    if (form) {
        // Form validation
        form.addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
            }
        });

        // Track changes for unsaved changes warning
        trackFormChanges();
    }
});

function validateForm() {
    let isValid = true;
    const errors = [];

    clearErrorStates();

    // Validate fecha_visita
    const fechaVisita = document.getElementById('fecha_visita').value;
    if (!fechaVisita) {
        errors.push('La fecha de visita es obligatoria');
        markFieldError(document.getElementById('fecha_visita'));
        isValid = false;
    }

    // Validate hora_visita
    const horaVisita = document.getElementById('hora_visita').value;
    if (!horaVisita) {
        errors.push('La hora de visita es obligatoria');
        markFieldError(document.getElementById('hora_visita'));
        isValid = false;
    }

    // Validate business hours
    if (horaVisita) {
        const hour = parseInt(horaVisita.split(':')[0]);
        if (hour < 8 || hour > 18) {
            errors.push('Las visitas solo se pueden programar entre 8:00 AM y 6:00 PM');
            markFieldError(document.getElementById('hora_visita'));
            isValid = false;
        }
    }

    // Validate estado
    if (!document.getElementById('estado').value) {
        errors.push('El estado de la visita es obligatorio');
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

    // Validate id_agente
    if (!document.getElementById('id_agente').value) {
        errors.push('Debe seleccionar un agente');
        markFieldError(document.getElementById('id_agente'));
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
    }
}

function trackFormChanges() {
    let hasChanges = false;
    const form = document.getElementById('visitEditForm');
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
/* Additional styles specific to visit editing */
.summary-card {
    color: white;
    margin-bottom: var(--spacing-lg);
}

.summary-card.status-programada { background: linear-gradient(135deg, #2196f3, #1976d2); }
.summary-card.status-realizada { background: linear-gradient(135deg, #28a745, #20c997); }
.summary-card.status-cancelada { background: linear-gradient(135deg, #dc3545, #c82333); }
.summary-card.status-reprogramada { background: linear-gradient(135deg, #ff9800, #f57c00); }

.visit-status-badge {
    background: rgba(255, 255, 255, 0.3);
    padding: 6px 12px;
    border-radius: var(--border-radius);
    font-size: var(--font-size-sm);
}

.required::after {
    content: " *";
    color: #dc3545;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .form-row { grid-template-columns: 1fr; }
}
</style>