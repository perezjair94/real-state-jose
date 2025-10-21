<?php
/**
 * Create Visit - Real Estate Management System
 * Form to schedule new property visits
 * Educational PHP/MySQL Project
 */

// Prevent direct access
if (!defined('APP_ACCESS')) {
    die('Direct access not permitted');
}

// Initialize variables
$errors = [];
$formData = [];
$success = false;

// Get real data from database
$properties = [];
$clients = [];
$agents = [];

try {
    $db = new Database();
    $pdo = $db->getConnection();

    // Get available properties
    $stmt = $pdo->prepare("SELECT id_inmueble, tipo_inmueble, direccion, ciudad FROM inmueble WHERE estado = 'Disponible' ORDER BY tipo_inmueble, direccion");
    $stmt->execute();
    $propertiesData = $stmt->fetchAll();
    foreach ($propertiesData as $property) {
        $properties[$property['id_inmueble']] = $property['tipo_inmueble'] . ' - ' . $property['direccion'] . ', ' . $property['ciudad'];
    }

    // Get clients
    $stmt = $pdo->prepare("SELECT id_cliente, nombre, apellido, tipo_documento, nro_documento FROM cliente ORDER BY nombre, apellido");
    $stmt->execute();
    $clientsData = $stmt->fetchAll();
    foreach ($clientsData as $client) {
        $clients[$client['id_cliente']] = $client['nombre'] . ' ' . $client['apellido'] . ' - ' . $client['tipo_documento'] . ' ' . $client['nro_documento'];
    }

    // Get active agents
    $stmt = $pdo->prepare("SELECT id_agente, nombre FROM agente WHERE activo = 1 ORDER BY nombre");
    $stmt->execute();
    $agentsData = $stmt->fetchAll();
    foreach ($agentsData as $agent) {
        $agents[$agent['id_agente']] = $agent['nombre'];
    }

} catch (PDOException $e) {
    error_log("Error loading form data: " . $e->getMessage());
    $errors['database'] = "Error al cargar los datos del formulario. Intente nuevamente.";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input data
    $formData = array_map('sanitizeInput', $_POST);

    // Basic validation
    if (empty($formData['fecha_visita'])) {
        $errors['fecha_visita'] = 'La fecha de visita es obligatoria';
    } else {
        // Check if date is not in the past
        if (strtotime($formData['fecha_visita']) < strtotime(date('Y-m-d'))) {
            $errors['fecha_visita'] = 'La fecha de visita no puede ser anterior a hoy';
        }
    }

    if (empty($formData['hora_visita'])) {
        $errors['hora_visita'] = 'La hora de visita es obligatoria';
    }

    if (empty($formData['inmueble_id'])) {
        $errors['inmueble_id'] = 'Debe seleccionar un inmueble';
    }

    if (empty($formData['cliente_id'])) {
        $errors['cliente_id'] = 'Debe seleccionar un cliente';
    }

    if (empty($formData['agente_id'])) {
        $errors['agente_id'] = 'Debe seleccionar un agente';
    }

    // Business hours validation
    if (!empty($formData['hora_visita'])) {
        $hour = (int)substr($formData['hora_visita'], 0, 2);
        if ($hour < 8 || $hour > 18) {
            $errors['hora_visita'] = 'Las visitas solo se pueden programar entre 8:00 AM y 6:00 PM';
        }
    }

    if (empty($errors)) {
        // Save to database
        try {
            $db = new Database();
            $pdo = $db->getConnection();

            // Insert visit
            $stmt = $pdo->prepare("
                INSERT INTO visita (fecha_visita, hora_visita, estado, observaciones,
                                   id_inmueble, id_cliente, id_agente)
                VALUES (?, ?, 'Programada', ?, ?, ?, ?)
            ");

            $result = $stmt->execute([
                $formData['fecha_visita'],
                $formData['hora_visita'],
                $formData['observaciones'] ?? '',
                $formData['inmueble_id'],
                $formData['cliente_id'],
                $formData['agente_id']
            ]);

            if ($result) {
                $visitId = $pdo->lastInsertId();
                $success = true;
            } else {
                $errors['database'] = 'Error al guardar la visita en la base de datos';
            }

        } catch (PDOException $e) {
            error_log("Error saving visit: " . $e->getMessage());
            $errors['database'] = 'Error al guardar la visita. Intente nuevamente.';
        }
    }
}
?>

<div class="module-header">
    <h2>Programar Nueva Visita</h2>
    <p class="module-description">
        Complete el formulario para programar una visita a una propiedad.
    </p>
</div>

<!-- Navigation Buttons -->
<div class="action-buttons">
    <a href="?module=visits" class="btn btn-secondary">
        ← Volver a la Lista
    </a>
    <a href="?module=visits&action=calendar" class="btn btn-info">
        📅 Ver Calendario
    </a>
</div>

<!-- Success Message -->
<?php if ($success): ?>
    <div class="alert alert-success">
        <h4>✅ Visita programada exitosamente</h4>
        <p>La visita ha sido programada con el ID <strong>VIS<?= str_pad($visitId, 3, '0', STR_PAD_LEFT) ?></strong></p>
        <div class="visit-summary">
            <p><strong>📅 Fecha:</strong> <?= formatDate($formData['fecha_visita']) ?></p>
            <p><strong>🕐 Hora:</strong> <?= date('g:i A', strtotime($formData['hora_visita'])) ?></p>
            <p><strong>🏠 Inmueble:</strong> <?= htmlspecialchars($properties[$formData['inmueble_id']] ?? $formData['inmueble_id']) ?></p>
            <p><strong>👤 Cliente:</strong> <?= htmlspecialchars($clients[$formData['cliente_id']] ?? $formData['cliente_id']) ?></p>
            <p><strong>👨‍💼 Agente:</strong> <?= htmlspecialchars($agents[$formData['agente_id']] ?? $formData['agente_id']) ?></p>
            <?php if (!empty($formData['observaciones'])): ?>
                <p><strong>📝 Observaciones:</strong> <?= htmlspecialchars($formData['observaciones']) ?></p>
            <?php endif; ?>
        </div>
        <div class="alert-actions">
            <a href="?module=visits" class="btn btn-primary">Ver Lista de Visitas</a>
            <a href="?module=visits&action=create" class="btn btn-secondary">Programar Otra Visita</a>
        </div>
    </div>
<?php endif; ?>

<!-- Error Display -->
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <h4>Por favor corrija los siguientes errores:</h4>
        <ul>
            <?php foreach ($errors as $field => $message): ?>
                <li><?= htmlspecialchars($message) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Visit Scheduling Form -->
<?php if (!$success): ?>
<div class="card">
    <h3>Información de la Visita</h3>

    <form method="POST" id="visit-form">
        <!-- CSRF Token -->
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

        <!-- Visit ID (Auto-generated) -->
        <div class="form-group">
            <label>ID Visita:</label>
            <input type="text" value="Se generará automáticamente" readonly class="form-control">
            <div class="field-help">El ID se asignará automáticamente al programar la visita</div>
        </div>

        <!-- Date and Time -->
        <div class="form-row">
            <div class="form-group">
                <label for="fecha_visita" class="required">Fecha de Visita:</label>
                <input
                    type="date"
                    name="fecha_visita"
                    id="fecha_visita"
                    required
                    min="<?= date('Y-m-d') ?>"
                    value="<?= htmlspecialchars($formData['fecha_visita'] ?? '') ?>"
                    class="form-control"
                >
                <?php if (!empty($errors['fecha_visita'])): ?>
                    <div class="error-message"><?= htmlspecialchars($errors['fecha_visita']) ?></div>
                <?php endif; ?>
                <div class="field-help">La fecha no puede ser anterior a hoy</div>
            </div>

            <div class="form-group">
                <label for="hora_visita" class="required">Hora de Visita:</label>
                <input
                    type="time"
                    name="hora_visita"
                    id="hora_visita"
                    required
                    min="08:00"
                    max="18:00"
                    value="<?= htmlspecialchars($formData['hora_visita'] ?? '') ?>"
                    class="form-control"
                >
                <?php if (!empty($errors['hora_visita'])): ?>
                    <div class="error-message"><?= htmlspecialchars($errors['hora_visita']) ?></div>
                <?php endif; ?>
                <div class="field-help">Horario de atención: 8:00 AM - 6:00 PM</div>
            </div>
        </div>

        <!-- Property and Client -->
        <div class="form-row">
            <div class="form-group">
                <label for="inmueble_id" class="required">Inmueble:</label>
                <select name="inmueble_id" id="inmueble_id" required class="form-control">
                    <option value="">Seleccione el inmueble...</option>
                    <?php foreach ($properties as $id => $description): ?>
                        <option value="<?= $id ?>" <?= ($formData['inmueble_id'] ?? '') === $id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($description) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['inmueble_id'])): ?>
                    <div class="error-message"><?= htmlspecialchars($errors['inmueble_id']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="cliente_id" class="required">Cliente:</label>
                <select name="cliente_id" id="cliente_id" required class="form-control">
                    <option value="">Seleccione el cliente...</option>
                    <?php foreach ($clients as $id => $description): ?>
                        <option value="<?= $id ?>" <?= ($formData['cliente_id'] ?? '') === $id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($description) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['cliente_id'])): ?>
                    <div class="error-message"><?= htmlspecialchars($errors['cliente_id']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Agent -->
        <div class="form-group">
            <label for="agente_id" class="required">Agente Responsable:</label>
            <select name="agente_id" id="agente_id" required class="form-control">
                <option value="">Seleccione el agente...</option>
                <?php foreach ($agents as $id => $name): ?>
                    <option value="<?= $id ?>" <?= ($formData['agente_id'] ?? '') === $id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['agente_id'])): ?>
                <div class="error-message"><?= htmlspecialchars($errors['agente_id']) ?></div>
            <?php endif; ?>
            <div class="field-help">El agente que acompañará al cliente en la visita</div>
        </div>

        <!-- Visit Type -->
        <div class="form-group">
            <label for="tipo_visita">Tipo de Visita:</label>
            <select name="tipo_visita" id="tipo_visita" class="form-control">
                <option value="Primera Visita" <?= ($formData['tipo_visita'] ?? 'Primera Visita') === 'Primera Visita' ? 'selected' : '' ?>>
                    Primera Visita
                </option>
                <option value="Revisita" <?= ($formData['tipo_visita'] ?? '') === 'Revisita' ? 'selected' : '' ?>>
                    Revisita
                </option>
                <option value="Inspección" <?= ($formData['tipo_visita'] ?? '') === 'Inspección' ? 'selected' : '' ?>>
                    Inspección Técnica
                </option>
                <option value="Entrega" <?= ($formData['tipo_visita'] ?? '') === 'Entrega' ? 'selected' : '' ?>>
                    Entrega de Llaves
                </option>
            </select>
            <div class="field-help">Tipo de visita a realizar</div>
        </div>

        <!-- Contact Information -->
        <fieldset class="form-section">
            <legend>Información de Contacto</legend>

            <div class="form-row">
                <div class="form-group">
                    <label for="telefono_contacto">Teléfono de Contacto:</label>
                    <input
                        type="tel"
                        name="telefono_contacto"
                        id="telefono_contacto"
                        placeholder="300-123-4567"
                        value="<?= htmlspecialchars($formData['telefono_contacto'] ?? '') ?>"
                        class="form-control"
                    >
                    <div class="field-help">Teléfono alternativo para confirmar la visita</div>
                </div>

                <div class="form-group">
                    <label for="email_contacto">Email de Contacto:</label>
                    <input
                        type="email"
                        name="email_contacto"
                        id="email_contacto"
                        placeholder="cliente@email.com"
                        value="<?= htmlspecialchars($formData['email_contacto'] ?? '') ?>"
                        class="form-control"
                    >
                    <div class="field-help">Email para envío de confirmación</div>
                </div>
            </div>
        </fieldset>

        <!-- Observations -->
        <div class="form-group">
            <label for="observaciones">Observaciones:</label>
            <textarea
                name="observaciones"
                id="observaciones"
                placeholder="Información adicional sobre la visita: intereses específicos del cliente, preparativos necesarios, etc."
                class="form-control"
            ><?= htmlspecialchars($formData['observaciones'] ?? '') ?></textarea>
            <div class="field-help">Información adicional relevante para la visita</div>
        </div>

        <!-- Reminders -->
        <div class="form-group">
            <label class="checkbox-label">
                <input
                    type="checkbox"
                    name="recordatorio_cliente"
                    value="1"
                    <?= isset($formData['recordatorio_cliente']) ? 'checked' : 'checked' ?>
                >
                Enviar recordatorio al cliente (24 horas antes)
            </label>
        </div>

        <div class="form-group">
            <label class="checkbox-label">
                <input
                    type="checkbox"
                    name="recordatorio_agente"
                    value="1"
                    <?= isset($formData['recordatorio_agente']) ? 'checked' : 'checked' ?>
                >
                Enviar recordatorio al agente (2 horas antes)
            </label>
        </div>

        <!-- Availability Check -->
        <div id="availability-check" class="availability-info">
            <p>🔍 Verificando disponibilidad...</p>
        </div>

        <!-- Form Actions -->
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                📅 Programar Visita
            </button>
            <button type="reset" class="btn btn-secondary">
                🔄 Limpiar Formulario
            </button>
            <a href="?module=visits" class="btn btn-secondary">
                ❌ Cancelar
            </a>
        </div>
    </form>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fechaInput = document.getElementById('fecha_visita');
    const horaInput = document.getElementById('hora_visita');
    const agenteSelect = document.getElementById('agente_id');
    const availabilityCheck = document.getElementById('availability-check');

    // Format phone input
    const phoneInput = document.getElementById('telefono_contacto');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 3) {
                value = value.substring(0, 3) + '-' + value.substring(3, 6) + '-' + value.substring(6, 10);
            }
            e.target.value = value;
        });
    }

    // Disable weekends for visits
    if (fechaInput) {
        fechaInput.addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const dayOfWeek = selectedDate.getDay();

            if (dayOfWeek === 0 || dayOfWeek === 6) { // Sunday = 0, Saturday = 6
                alert('Las visitas no se programan los fines de semana. Por favor seleccione un día entre lunes y viernes.');
                this.value = '';
                return;
            }

            checkAvailability();
        });
    }

    // Check availability when time or agent changes
    if (horaInput) {
        horaInput.addEventListener('change', checkAvailability);
    }

    if (agenteSelect) {
        agenteSelect.addEventListener('change', checkAvailability);
    }

    function checkAvailability() {
        const fecha = fechaInput.value;
        const hora = horaInput.value;
        const agente = agenteSelect.value;

        if (fecha && hora && agente) {
            // Simulate availability check
            availabilityCheck.style.display = 'block';
            availabilityCheck.innerHTML = '<p>🔍 Verificando disponibilidad...</p>';

            setTimeout(() => {
                // Simulate random availability
                const isAvailable = Math.random() > 0.2; // 80% chance of being available

                if (isAvailable) {
                    availabilityCheck.innerHTML = '<p style="color: green;">✅ Horario disponible</p>';
                    availabilityCheck.className = 'availability-info available';
                } else {
                    availabilityCheck.innerHTML = '<p style="color: orange;">⚠️ El agente tiene otra cita a esta hora. Se recomienda seleccionar otro horario.</p>';
                    availabilityCheck.className = 'availability-info conflict';
                }
            }, 1000);
        } else {
            availabilityCheck.style.display = 'none';
        }
    }

    // Auto-suggest next available time slot
    function suggestNextSlot() {
        if (!horaInput.value && fechaInput.value) {
            const now = new Date();
            const selectedDate = new Date(fechaInput.value);

            if (selectedDate.toDateString() === now.toDateString()) {
                // If it's today, suggest next hour
                const nextHour = now.getHours() + 1;
                if (nextHour >= 8 && nextHour <= 18) {
                    horaInput.value = String(nextHour).padStart(2, '0') + ':00';
                } else if (nextHour < 8) {
                    horaInput.value = '08:00';
                }
            } else {
                // If it's another day, suggest 9 AM
                horaInput.value = '09:00';
            }
        }
    }

    if (fechaInput) {
        fechaInput.addEventListener('change', suggestNextSlot);
    }

    // Initial availability check if form has values
    if (fechaInput.value && horaInput.value && agenteSelect.value) {
        checkAvailability();
    }

    // Auto-complete client contact info (simulation)
    const clientSelect = document.getElementById('cliente_id');
    const emailInput = document.getElementById('email_contacto');

    if (clientSelect && emailInput) {
        clientSelect.addEventListener('change', function() {
            const clientValue = this.value;
            // Simulate getting client email from selection
            if (clientValue && !emailInput.value) {
                const clientText = this.options[this.selectedIndex].text;
                const clientName = clientText.split(' - ')[0].toLowerCase().replace(' ', '.');
                emailInput.value = clientName + '@email.com';
            }
        });
    }
});
</script>

<style>
.form-actions {
    margin-top: var(--spacing-xl);
    padding-top: var(--spacing-lg);
    border-top: 1px solid var(--border-color);
    display: flex;
    gap: var(--spacing-sm);
    flex-wrap: wrap;
}

fieldset.form-section {
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: var(--spacing-lg);
    margin: var(--spacing-lg) 0;
}

fieldset.form-section legend {
    font-weight: 600;
    color: var(--primary-color);
    padding: 0 var(--spacing-sm);
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    cursor: pointer;
    margin-bottom: var(--spacing-sm);
}

.checkbox-label input[type="checkbox"] {
    width: auto;
    margin: 0;
}

.required::after {
    content: " *";
    color: #dc3545;
}

.field-help {
    font-size: var(--font-size-sm);
    color: var(--text-secondary);
    margin-top: var(--spacing-xs);
}

.error-message {
    color: #dc3545;
    font-size: var(--font-size-sm);
    margin-top: var(--spacing-xs);
}

.alert {
    padding: var(--spacing-md);
    border-radius: var(--border-radius);
    margin-bottom: var(--spacing-lg);
}

.alert-success {
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.alert-danger {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.visit-summary {
    background: rgba(255, 255, 255, 0.7);
    padding: var(--spacing-md);
    border-radius: var(--border-radius);
    margin: var(--spacing-md) 0;
}

.visit-summary p {
    margin-bottom: var(--spacing-xs);
}

.alert-actions {
    margin-top: var(--spacing-md);
    display: flex;
    gap: var(--spacing-sm);
    flex-wrap: wrap;
}

.info-message {
    background-color: #e2e3e5;
    border: 1px solid #d6d8db;
    color: #383d41;
    padding: var(--spacing-md);
    border-radius: var(--border-radius);
    margin-top: var(--spacing-lg);
}

.availability-info {
    margin: var(--spacing-md) 0;
    padding: var(--spacing-sm);
    border-radius: var(--border-radius);
    border: 1px solid var(--border-color);
    background: var(--bg-secondary);
    display: none;
}

.availability-info.available {
    background-color: #d4edda;
    border-color: #c3e6cb;
}

.availability-info.conflict {
    background-color: #fff3cd;
    border-color: #ffeaa7;
}

.availability-info p {
    margin: 0;
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
}

@media (max-width: 768px) {
    .form-actions,
    .alert-actions {
        flex-direction: column;
    }

    .form-actions .btn,
    .alert-actions .btn {
        width: 100%;
    }
}
</style>