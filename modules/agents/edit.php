<?php
/**
 * Agent Edit Form - Real Estate Management System
 * Form to edit existing agents with database integration
 * Educational PHP/MySQL Project
 */

// Prevent direct access
if (!defined('APP_ACCESS')) {
    die('Direct access not permitted');
}

$error = '';
$success = '';
$agent = null;
$agentId = (int)($_GET['id'] ?? 0);

// Initialize form data
$formData = [
    'nombre' => '',
    'correo' => '',
    'telefono' => '',
    'asesor' => '',
    'activo' => 1
];

// Validate agent ID and load existing data
if ($agentId <= 0) {
    $error = "ID de agente inválido";
} else {
    try {
        $db = new Database();
        $pdo = $db->getConnection();

        // Load existing agent data
        $stmt = $pdo->prepare("SELECT * FROM agente WHERE id_agente = ?");
        $stmt->execute([$agentId]);
        $agent = $stmt->fetch();

        if (!$agent) {
            $error = "Agente no encontrado";
        } else {
            // Populate form data with existing values
            $formData = [
                'nombre' => $agent['nombre'],
                'correo' => $agent['correo'],
                'telefono' => $agent['telefono'],
                'asesor' => $agent['asesor'] ?? '',
                'activo' => $agent['activo']
            ];
        }

    } catch (PDOException $e) {
        error_log("Error loading agent: " . $e->getMessage());
        $error = "Error al cargar los datos del agente";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error && $agent) {
    try {
        // Get form data
        $formData = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'correo' => trim($_POST['correo'] ?? ''),
            'telefono' => trim($_POST['telefono'] ?? ''),
            'asesor' => trim($_POST['asesor'] ?? ''),
            'activo' => (int)($_POST['activo'] ?? 1)
        ];

        // Validation
        $errors = [];

        if (empty($formData['nombre'])) {
            $errors[] = "El nombre es obligatorio";
        }

        if (empty($formData['correo']) || !filter_var($formData['correo'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Ingrese un email válido";
        }

        if (empty($formData['telefono'])) {
            $errors[] = "El teléfono es obligatorio";
        }

        if (!empty($errors)) {
            $error = implode('<br>', $errors);
        } else {
            // Check for duplicate email (excluding current agent)
            $checkStmt = $pdo->prepare("SELECT id_agente FROM agente WHERE correo = ? AND id_agente != ?");
            $checkStmt->execute([$formData['correo'], $agentId]);
            if ($checkStmt->fetchColumn()) {
                $error = "Ya existe otro agente con este email";
            } else {
                // Update agent
                $stmt = $pdo->prepare("
                    UPDATE agente
                    SET nombre = ?, correo = ?, telefono = ?, asesor = ?, activo = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id_agente = ?
                ");

                $result = $stmt->execute([
                    $formData['nombre'],
                    $formData['correo'],
                    $formData['telefono'],
                    $formData['asesor'],
                    $formData['activo'],
                    $agentId
                ]);

                if ($result) {
                    $success = "Agente actualizado exitosamente";

                    // Refresh agent data
                    $stmt = $pdo->prepare("SELECT * FROM agente WHERE id_agente = ?");
                    $stmt->execute([$agentId]);
                    $agent = $stmt->fetch();
                } else {
                    $error = "Error al actualizar el agente. Intente nuevamente.";
                }
            }
        }

    } catch (PDOException $e) {
        error_log("Error updating agent: " . $e->getMessage());
        $error = "Error de base de datos. Intente nuevamente.";
    } catch (Exception $e) {
        error_log("General error updating agent: " . $e->getMessage());
        $error = "Error inesperado. Intente nuevamente.";
    }
}
?>

<div class="module-header">
    <h2>Editar Agente</h2>
    <p class="module-description">
        Modifique los datos del agente inmobiliario registrado en el sistema.
    </p>
</div>

<!-- Navigation Breadcrumb -->
<div class="breadcrumb">
    <a href="?module=agents">Agentes</a> >
    <a href="?module=agents&action=view&id=<?= $agentId ?>">AGE<?= str_pad($agentId, 3, '0', STR_PAD_LEFT) ?></a> >
    Editar
</div>

<!-- Action Buttons -->
<div class="action-buttons">
    <a href="?module=agents" class="btn btn-secondary">
        ← Volver a Lista
    </a>
    <a href="?module=agents&action=view&id=<?= $agentId ?>" class="btn btn-info">
        Ver Detalles
    </a>
    <?php if ($agent): ?>
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
            <a href="?module=agents" class="btn btn-sm btn-outline">Ver Lista</a>
            <a href="?module=agents&action=view&id=<?= $agentId ?>" class="btn btn-sm btn-info">Ver Detalles</a>
        </div>
    </div>
<?php endif; ?>

<?php if ($agent): ?>
    <!-- Agent Information Summary -->
    <div class="card summary-card">
        <h3>
            Agente: AGE<?= str_pad($agent['id_agente'], 3, '0', STR_PAD_LEFT) ?>
            <span class="agent-status <?= $agent['activo'] ? 'active' : 'inactive' ?>">
                <?= $agent['activo'] ? 'Activo' : 'Inactivo' ?>
            </span>
        </h3>
        <div class="summary-info">
            <div class="summary-item">
                <strong>Registrado:</strong> <?= formatDate($agent['created_at']) ?>
            </div>
            <?php if ($agent['updated_at'] !== $agent['created_at']): ?>
                <div class="summary-item">
                    <strong>Última actualización:</strong> <?= formatDate($agent['updated_at']) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Agent Edit Form -->
    <div class="card">
        <h3>Modificar Información</h3>

        <form method="POST" id="agentEditForm" class="form-horizontal">

            <!-- Personal Information Section -->
            <fieldset>
                <legend>Información Personal</legend>

                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre" class="required">Nombre Completo:</label>
                        <input
                            type="text"
                            id="nombre"
                            name="nombre"
                            value="<?= htmlspecialchars($formData['nombre']) ?>"
                            required
                            maxlength="200"
                            class="form-control"
                            placeholder="Ingrese el nombre completo del agente"
                        >
                        <small class="field-help">Nombre completo del agente inmobiliario</small>
                    </div>

                    <div class="form-group">
                        <label for="correo" class="required">Email:</label>
                        <input
                            type="email"
                            id="correo"
                            name="correo"
                            value="<?= htmlspecialchars($formData['correo']) ?>"
                            required
                            maxlength="150"
                            class="form-control"
                            placeholder="agente@email.com"
                        >
                        <small class="field-help">Dirección de correo electrónico única</small>
                    </div>
                </div>
            </fieldset>

            <!-- Contact Information Section -->
            <fieldset>
                <legend>Información de Contacto</legend>

                <div class="form-row">
                    <div class="form-group">
                        <label for="telefono" class="required">Teléfono:</label>
                        <input
                            type="tel"
                            id="telefono"
                            name="telefono"
                            value="<?= htmlspecialchars($formData['telefono']) ?>"
                            required
                            maxlength="20"
                            class="form-control"
                            placeholder="Ej: 300 123 4567"
                            pattern="[0-9\s\-\+\(\)]+"
                        >
                        <small class="field-help">Número de teléfono de contacto</small>
                    </div>

                    <div class="form-group">
                        <label for="asesor">Asesor/Supervisor:</label>
                        <input
                            type="text"
                            id="asesor"
                            name="asesor"
                            value="<?= htmlspecialchars($formData['asesor']) ?>"
                            maxlength="200"
                            class="form-control"
                            placeholder="Nombre del supervisor (opcional)"
                        >
                        <small class="field-help">Nombre del supervisor o mentor (opcional)</small>
                    </div>
                </div>
            </fieldset>

            <!-- Status Section -->
            <fieldset>
                <legend>Estado del Agente</legend>

                <div class="form-row">
                    <div class="form-group">
                        <label for="activo" class="required">Estado:</label>
                        <select id="activo" name="activo" required class="form-control">
                            <option value="1" <?= $formData['activo'] == 1 ? 'selected' : '' ?>>Activo</option>
                            <option value="0" <?= $formData['activo'] == 0 ? 'selected' : '' ?>>Inactivo</option>
                        </select>
                        <small class="field-help">Estado actual del agente en el sistema</small>
                    </div>

                    <div class="form-group">
                        <!-- Reserved for future fields -->
                        <label>&nbsp;</label>
                        <div style="height: 38px;"></div>
                    </div>
                </div>
            </fieldset>

            <!-- Form Actions -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    Guardar Cambios
                </button>
                <a href="?module=agents&action=view&id=<?= $agentId ?>" class="btn btn-secondary">
                    Cancelar
                </a>
                <button type="button" class="btn btn-outline" onclick="resetForm()">
                    Revertir
                </button>
            </div>
        </form>
    </div>

    <!-- Related Information -->
    <div class="related-info">
        <h3>Información Relacionada</h3>
        <div class="info-tabs">
            <button class="tab-button active" onclick="showTab('transactions')">Transacciones</button>
            <button class="tab-button" onclick="showTab('properties')">Propiedades</button>
            <button class="tab-button" onclick="showTab('performance')">Rendimiento</button>
        </div>

        <div id="transactions-tab" class="tab-content active">
            <div class="card">
                <h4>Transacciones del Agente</h4>
                <p class="text-muted">
                    Esta funcionalidad mostrará las ventas, contratos y arriendos gestionados por este agente.
                </p>
                <button type="button" class="btn btn-info" onclick="loadAgentTransactions(<?= $agentId ?>)">
                    Cargar Transacciones
                </button>
            </div>
        </div>

        <div id="properties-tab" class="tab-content">
            <div class="card">
                <h4>Propiedades Asignadas</h4>
                <p class="text-muted">
                    Aquí se mostrarán las propiedades que este agente tiene asignadas para gestionar.
                </p>
                <button type="button" class="btn btn-info" onclick="loadAgentProperties(<?= $agentId ?>)">
                    Cargar Propiedades
                </button>
            </div>
        </div>

        <div id="performance-tab" class="tab-content">
            <div class="card">
                <h4>Métricas de Rendimiento</h4>
                <p class="text-muted">
                    Estadísticas de ventas, comisiones y rendimiento del agente.
                </p>
                <button type="button" class="btn btn-info" onclick="loadAgentPerformance(<?= $agentId ?>)">
                    Cargar Métricas
                </button>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- No Agent Found -->
    <div class="card">
        <div class="no-results">
            <h3>Agente No Encontrado</h3>
            <p>El agente solicitado no existe o ha sido eliminado.</p>
            <a href="?module=agents" class="btn btn-primary">Volver a Lista de Agentes</a>
        </div>
    </div>
<?php endif; ?>

<!-- JavaScript for Enhanced Functionality -->
<script>
/**
 * Agent edit form functionality
 */

// Store original form values for reset functionality
const originalFormData = <?= json_encode($formData) ?>;

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('agentEditForm');

    if (form) {
        const emailInput = document.getElementById('correo');
        const phoneInput = document.getElementById('telefono');

        // Form validation
        form.addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
            }
        });

        // Real-time validation
        emailInput.addEventListener('blur', validateEmail);
        phoneInput.addEventListener('input', formatPhone);

        // Track changes for unsaved changes warning
        trackFormChanges();
    }
});

function validateForm() {
    let isValid = true;
    const errors = [];

    // Clear previous error states
    clearErrorStates();

    // Validate required fields
    const requiredFields = ['nombre', 'correo', 'telefono'];

    requiredFields.forEach(fieldName => {
        const field = document.getElementById(fieldName);
        if (!field.value.trim()) {
            markFieldError(field);
            errors.push(`${getFieldLabel(fieldName)} es obligatorio`);
            isValid = false;
        }
    });

    // Validate email format
    const email = document.getElementById('correo').value;
    if (email && !isValidEmail(email)) {
        markFieldError(document.getElementById('correo'));
        errors.push('Formato de email inválido');
        isValid = false;
    }

    // Validate phone format
    const phone = document.getElementById('telefono').value;
    if (phone && !isValidPhone(phone)) {
        markFieldError(document.getElementById('telefono'));
        errors.push('Formato de teléfono inválido');
        isValid = false;
    }

    // Show errors if any
    if (!isValid) {
        showFormErrors(errors);
    }

    return isValid;
}

function validateEmail() {
    const email = document.getElementById('correo').value;
    const emailInput = document.getElementById('correo');

    if (email && !isValidEmail(email)) {
        markFieldError(emailInput, 'Formato de email inválido');
        return false;
    } else {
        clearFieldError(emailInput);
        return true;
    }
}

function formatPhone() {
    const phoneInput = document.getElementById('telefono');
    let phone = phoneInput.value.replace(/\D/g, '');

    // Format Colombian phone numbers
    if (phone.length >= 10) {
        phone = phone.replace(/(\d{3})(\d{3})(\d{4})/, '$1 $2 $3');
    }

    phoneInput.value = phone;

    if (phone && !isValidPhone(phone)) {
        markFieldError(phoneInput, 'Formato de teléfono inválido');
    } else {
        clearFieldError(phoneInput);
    }
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function isValidPhone(phone) {
    // Colombian phone number validation (10 digits)
    const phoneRegex = /^[\d\s\-\+\(\)]{10,20}$/;
    return phoneRegex.test(phone);
}

function markFieldError(field, message = '') {
    field.classList.add('error');

    const existingError = field.parentNode.querySelector('.error-message');
    if (existingError) {
        existingError.remove();
    }

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
    if (errorMessage) {
        errorMessage.remove();
    }
}

function clearErrorStates() {
    document.querySelectorAll('.form-control.error').forEach(field => {
        clearFieldError(field);
    });

    const alertError = document.querySelector('.alert-danger');
    if (alertError) {
        alertError.style.display = 'none';
    }
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

function getFieldLabel(fieldName) {
    const labels = {
        'nombre': 'Nombre',
        'correo': 'Email',
        'telefono': 'Teléfono'
    };
    return labels[fieldName] || fieldName;
}

function resetForm() {
    if (confirm('¿Está seguro de que desea revertir todos los cambios?')) {
        Object.keys(originalFormData).forEach(fieldName => {
            const field = document.getElementById(fieldName);
            if (field) {
                field.value = originalFormData[fieldName];
            }
        });
        clearErrorStates();
    }
}

function trackFormChanges() {
    let hasChanges = false;
    const form = document.getElementById('agentEditForm');

    if (form) {
        form.addEventListener('input', function() {
            hasChanges = true;
        });

        window.addEventListener('beforeunload', function(e) {
            if (hasChanges) {
                e.preventDefault();
                e.returnValue = '¿Está seguro de que desea salir? Los cambios no guardados se perderán.';
            }
        });

        form.addEventListener('submit', function() {
            hasChanges = false;
        });
    }
}

// Tab functionality
function showTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });

    // Remove active class from all buttons
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active');
    });

    // Show selected tab
    document.getElementById(tabName + '-tab').classList.add('active');
    event.target.classList.add('active');
}

// Placeholder functions for related information
function loadAgentTransactions(agentId) {
    // TODO: Implement transaction loading
    alert('Funcionalidad de transacciones pendiente de implementación');
}

function loadAgentProperties(agentId) {
    // TODO: Implement properties loading
    alert('Funcionalidad de propiedades pendiente de implementación');
}

function loadAgentPerformance(agentId) {
    // TODO: Implement performance metrics
    alert('Funcionalidad de métricas pendiente de implementación');
}

// Auto-format inputs
document.getElementById('correo').addEventListener('blur', function(e) {
    this.value = this.value.toLowerCase().trim();
});
</script>

<style>
/* Additional styles specific to agent editing */
.summary-card {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    margin-bottom: var(--spacing-lg);
}

.summary-card h3 {
    color: white;
    margin-bottom: var(--spacing-md);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: var(--spacing-sm);
}

.agent-status {
    display: inline-block;
    padding: 4px 12px;
    border-radius: var(--border-radius);
    font-size: var(--font-size-sm);
    font-weight: 600;
    text-transform: uppercase;
    background: rgba(255, 255, 255, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.agent-status.active {
    background: #28a745;
}

.agent-status.inactive {
    background: #dc3545;
}

.summary-info {
    display: flex;
    gap: var(--spacing-lg);
    flex-wrap: wrap;
}

.summary-item {
    font-size: var(--font-size-sm);
    opacity: 0.9;
}

.related-info {
    margin-top: var(--spacing-xl);
}

.info-tabs {
    display: flex;
    gap: 2px;
    margin-bottom: var(--spacing-md);
    border-bottom: 2px solid var(--border-color);
}

.tab-button {
    padding: var(--spacing-sm) var(--spacing-md);
    border: none;
    background: var(--bg-secondary);
    color: var(--text-secondary);
    cursor: pointer;
    border-radius: var(--border-radius) var(--border-radius) 0 0;
    transition: all 0.2s ease;
}

.tab-button:hover {
    background: var(--primary-color);
    color: white;
}

.tab-button.active {
    background: var(--primary-color);
    color: white;
    font-weight: 600;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.alert-actions {
    margin-top: var(--spacing-sm);
    display: flex;
    gap: var(--spacing-sm);
}

.breadcrumb {
    margin-bottom: var(--spacing-md);
    font-size: var(--font-size-sm);
    color: var(--text-secondary);
}

.breadcrumb a {
    color: var(--primary-color);
    text-decoration: none;
}

.breadcrumb a:hover {
    text-decoration: underline;
}

fieldset {
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: var(--spacing-lg);
    margin-bottom: var(--spacing-lg);
}

fieldset legend {
    font-weight: 600;
    color: var(--primary-color);
    padding: 0 var(--spacing-sm);
    font-size: var(--font-size-lg);
}

.form-control.error {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

.error-message {
    color: #dc3545;
    font-size: var(--font-size-xs);
    margin-top: 4px;
    display: block;
}

.field-help {
    font-size: var(--font-size-xs);
    color: var(--text-secondary);
    margin-top: 4px;
    display: block;
}

.required::after {
    content: ' *';
    color: #dc3545;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    fieldset {
        padding: var(--spacing-md);
    }

    .form-row {
        grid-template-columns: 1fr;
    }

    .summary-info {
        flex-direction: column;
        gap: var(--spacing-sm);
    }

    .info-tabs {
        flex-direction: column;
    }

    .tab-button {
        border-radius: var(--border-radius);
    }
}
</style>