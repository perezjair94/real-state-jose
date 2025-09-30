<?php
/**
 * Client Edit Form - Real Estate Management System
 * Form to edit existing clients with database integration
 * Educational PHP/MySQL Project
 */

// Prevent direct access
if (!defined('APP_ACCESS')) {
    die('Direct access not permitted');
}

$error = '';
$success = '';
$client = null;
$clientId = (int)($_GET['id'] ?? 0);

// Initialize form data
$formData = [
    'nombre' => '',
    'apellido' => '',
    'tipo_documento' => '',
    'nro_documento' => '',
    'correo' => '',
    'direccion' => '',
    'tipo_cliente' => ''
];

// Validate client ID and load existing data
if ($clientId <= 0) {
    $error = "ID de cliente inválido";
} else {
    try {
        $db = new Database();
        $pdo = $db->getConnection();

        // Load existing client data
        $stmt = $pdo->prepare("SELECT * FROM cliente WHERE id_cliente = ?");
        $stmt->execute([$clientId]);
        $client = $stmt->fetch();

        if (!$client) {
            $error = "Cliente no encontrado";
        } else {
            // Populate form data with existing values
            $formData = [
                'nombre' => $client['nombre'],
                'apellido' => $client['apellido'],
                'tipo_documento' => $client['tipo_documento'],
                'nro_documento' => $client['nro_documento'],
                'correo' => $client['correo'],
                'direccion' => $client['direccion'] ?? '',
                'tipo_cliente' => $client['tipo_cliente']
            ];
        }

    } catch (PDOException $e) {
        error_log("Error loading client: " . $e->getMessage());
        $error = "Error al cargar los datos del cliente";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error && $client) {
    try {
        // Get form data
        $formData = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'apellido' => trim($_POST['apellido'] ?? ''),
            'tipo_documento' => $_POST['tipo_documento'] ?? '',
            'nro_documento' => trim($_POST['nro_documento'] ?? ''),
            'correo' => trim($_POST['correo'] ?? ''),
            'direccion' => trim($_POST['direccion'] ?? ''),
            'tipo_cliente' => $_POST['tipo_cliente'] ?? ''
        ];

        // Validation
        $errors = [];

        if (empty($formData['nombre'])) {
            $errors[] = "El nombre es obligatorio";
        }

        if (empty($formData['apellido'])) {
            $errors[] = "El apellido es obligatorio";
        }

        if (empty($formData['tipo_documento']) || !array_key_exists($formData['tipo_documento'], DOCUMENT_TYPES)) {
            $errors[] = "Seleccione un tipo de documento válido";
        }

        if (empty($formData['nro_documento'])) {
            $errors[] = "El número de documento es obligatorio";
        }

        if (empty($formData['correo']) || !filter_var($formData['correo'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Ingrese un email válido";
        }

        if (empty($formData['tipo_cliente']) || !array_key_exists($formData['tipo_cliente'], CLIENT_TYPES)) {
            $errors[] = "Seleccione un tipo de cliente válido";
        }

        if (!empty($errors)) {
            $error = implode('<br>', $errors);
        } else {
            // Check for duplicate document number (excluding current client)
            $checkStmt = $pdo->prepare("SELECT id_cliente FROM cliente WHERE nro_documento = ? AND id_cliente != ?");
            $checkStmt->execute([$formData['nro_documento'], $clientId]);
            if ($checkStmt->fetchColumn()) {
                $error = "Ya existe otro cliente con este número de documento";
            } else {
                // Check for duplicate email (excluding current client)
                $checkStmt = $pdo->prepare("SELECT id_cliente FROM cliente WHERE correo = ? AND id_cliente != ?");
                $checkStmt->execute([$formData['correo'], $clientId]);
                if ($checkStmt->fetchColumn()) {
                    $error = "Ya existe otro cliente con este email";
                } else {
                    // Update client
                    $stmt = $pdo->prepare("
                        UPDATE cliente
                        SET nombre = ?, apellido = ?, tipo_documento = ?, nro_documento = ?,
                            correo = ?, direccion = ?, tipo_cliente = ?, updated_at = CURRENT_TIMESTAMP
                        WHERE id_cliente = ?
                    ");

                    $result = $stmt->execute([
                        $formData['nombre'],
                        $formData['apellido'],
                        $formData['tipo_documento'],
                        $formData['nro_documento'],
                        $formData['correo'],
                        $formData['direccion'],
                        $formData['tipo_cliente'],
                        $clientId
                    ]);

                    if ($result) {
                        $success = "Cliente actualizado exitosamente";

                        // Refresh client data
                        $stmt = $pdo->prepare("SELECT * FROM cliente WHERE id_cliente = ?");
                        $stmt->execute([$clientId]);
                        $client = $stmt->fetch();
                    } else {
                        $error = "Error al actualizar el cliente. Intente nuevamente.";
                    }
                }
            }
        }

    } catch (PDOException $e) {
        error_log("Error updating client: " . $e->getMessage());
        $error = "Error de base de datos. Intente nuevamente.";
    } catch (Exception $e) {
        error_log("General error updating client: " . $e->getMessage());
        $error = "Error inesperado. Intente nuevamente.";
    }
}
?>

<div class="module-header">
    <h2>Editar Cliente</h2>
    <p class="module-description">
        Modifique los datos del cliente registrado en el sistema.
    </p>
</div>

<!-- Navigation Breadcrumb -->
<div class="breadcrumb">
    <a href="?module=clients">Clientes</a> >
    <a href="?module=clients&action=view&id=<?= $clientId ?>">CLI<?= str_pad($clientId, 3, '0', STR_PAD_LEFT) ?></a> >
    Editar
</div>

<!-- Action Buttons -->
<div class="action-buttons">
    <a href="?module=clients" class="btn btn-secondary">
        ← Volver a Lista
    </a>
    <a href="?module=clients&action=view&id=<?= $clientId ?>" class="btn btn-info">
        Ver Detalles
    </a>
    <?php if ($client): ?>
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
            <a href="?module=clients" class="btn btn-sm btn-outline">Ver Lista</a>
            <a href="?module=clients&action=view&id=<?= $clientId ?>" class="btn btn-sm btn-info">Ver Detalles</a>
        </div>
    </div>
<?php endif; ?>

<?php if ($client): ?>
    <!-- Client Information Summary -->
    <div class="card summary-card">
        <h3>
            Cliente: CLI<?= str_pad($client['id_cliente'], 3, '0', STR_PAD_LEFT) ?>
            <span class="client-status <?= strtolower(str_replace(' ', '-', $client['tipo_cliente'])) ?>">
                <?= htmlspecialchars($client['tipo_cliente']) ?>
            </span>
        </h3>
        <div class="summary-info">
            <div class="summary-item">
                <strong>Registrado:</strong> <?= formatDate($client['created_at']) ?>
            </div>
            <?php if ($client['updated_at'] !== $client['created_at']): ?>
                <div class="summary-item">
                    <strong>Última actualización:</strong> <?= formatDate($client['updated_at']) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Client Edit Form -->
    <div class="card">
        <h3>Modificar Información</h3>

        <form method="POST" id="clientEditForm" class="form-horizontal">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

            <!-- Personal Information Section -->
            <fieldset>
                <legend>Datos Personales</legend>

                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre" class="required">Nombre:</label>
                        <input
                            type="text"
                            id="nombre"
                            name="nombre"
                            value="<?= htmlspecialchars($formData['nombre']) ?>"
                            required
                            maxlength="100"
                            class="form-control"
                            placeholder="Ingrese el nombre"
                        >
                        <small class="field-help">Nombre de pila del cliente</small>
                    </div>

                    <div class="form-group">
                        <label for="apellido" class="required">Apellido:</label>
                        <input
                            type="text"
                            id="apellido"
                            name="apellido"
                            value="<?= htmlspecialchars($formData['apellido']) ?>"
                            required
                            maxlength="100"
                            class="form-control"
                            placeholder="Ingrese el apellido"
                        >
                        <small class="field-help">Apellidos del cliente</small>
                    </div>
                </div>
            </fieldset>

            <!-- Document Information Section -->
            <fieldset>
                <legend>Identificación</legend>

                <div class="form-row">
                    <div class="form-group">
                        <label for="tipo_documento" class="required">Tipo de Documento:</label>
                        <select id="tipo_documento" name="tipo_documento" required class="form-control">
                            <option value="">Seleccione el tipo</option>
                            <?php foreach (DOCUMENT_TYPES as $key => $label): ?>
                                <option value="<?= $key ?>" <?= $formData['tipo_documento'] === $key ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="field-help">Tipo de documento de identificación</small>
                    </div>

                    <div class="form-group">
                        <label for="nro_documento" class="required">Número de Documento:</label>
                        <input
                            type="text"
                            id="nro_documento"
                            name="nro_documento"
                            value="<?= htmlspecialchars($formData['nro_documento']) ?>"
                            required
                            maxlength="20"
                            class="form-control"
                            placeholder="Número sin puntos ni comas"
                            pattern="[0-9A-Za-z\-]+"
                        >
                        <small class="field-help">Número único de identificación</small>
                    </div>
                </div>
            </fieldset>

            <!-- Contact Information Section -->
            <fieldset>
                <legend>Información de Contacto</legend>

                <div class="form-row">
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
                            placeholder="cliente@email.com"
                        >
                        <small class="field-help">Dirección de correo electrónico única</small>
                    </div>

                    <div class="form-group">
                        <label for="tipo_cliente" class="required">Tipo de Cliente:</label>
                        <select id="tipo_cliente" name="tipo_cliente" required class="form-control">
                            <option value="">Seleccione el tipo</option>
                            <?php foreach (CLIENT_TYPES as $key => $label): ?>
                                <option value="<?= $key ?>" <?= $formData['tipo_cliente'] === $key ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="field-help">Clasificación del cliente según su interés</small>
                    </div>
                </div>
            </fieldset>

            <!-- Address Information Section -->
            <fieldset>
                <legend>Dirección</legend>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="direccion">Dirección Completa:</label>
                        <textarea
                            id="direccion"
                            name="direccion"
                            rows="3"
                            class="form-control"
                            placeholder="Ingrese la dirección completa del cliente (opcional)"
                        ><?= htmlspecialchars($formData['direccion']) ?></textarea>
                        <small class="field-help">Dirección de residencia o contacto (opcional)</small>
                    </div>
                </div>
            </fieldset>

            <!-- Form Actions -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    Guardar Cambios
                </button>
                <a href="?module=clients&action=view&id=<?= $clientId ?>" class="btn btn-secondary">
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
            <button class="tab-button" onclick="showTab('visits')">Visitas</button>
            <button class="tab-button" onclick="showTab('history')">Historial</button>
        </div>

        <div id="transactions-tab" class="tab-content active">
            <div class="card">
                <h4>Transacciones Relacionadas</h4>
                <p class="text-muted">
                    Esta funcionalidad mostrará las ventas, contratos y arriendos relacionados con este cliente.
                </p>
                <button type="button" class="btn btn-info" onclick="loadClientTransactions(<?= $clientId ?>)">
                    Cargar Transacciones
                </button>
            </div>
        </div>

        <div id="visits-tab" class="tab-content">
            <div class="card">
                <h4>Visitas Programadas</h4>
                <p class="text-muted">
                    Aquí se mostrarán las visitas programadas y realizadas por este cliente.
                </p>
                <button type="button" class="btn btn-info" onclick="loadClientVisits(<?= $clientId ?>)">
                    Cargar Visitas
                </button>
            </div>
        </div>

        <div id="history-tab" class="tab-content">
            <div class="card">
                <h4>Historial de Cambios</h4>
                <p class="text-muted">
                    Registro de todas las modificaciones realizadas a este cliente.
                </p>
                <button type="button" class="btn btn-info" onclick="loadClientHistory(<?= $clientId ?>)">
                    Cargar Historial
                </button>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- No Client Found -->
    <div class="card">
        <div class="no-results">
            <h3>Cliente No Encontrado</h3>
            <p>El cliente solicitado no existe o ha sido eliminado.</p>
            <a href="?module=clients" class="btn btn-primary">Volver a Lista de Clientes</a>
        </div>
    </div>
<?php endif; ?>

<!-- JavaScript for Enhanced Functionality -->
<script>
/**
 * Client edit form functionality
 */

// Store original form values for reset functionality
const originalFormData = <?= json_encode($formData) ?>;

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('clientEditForm');

    if (form) {
        const documentTypeSelect = document.getElementById('tipo_documento');
        const documentNumberInput = document.getElementById('nro_documento');
        const emailInput = document.getElementById('correo');

        // Form validation
        form.addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
            }
        });

        // Document type change handler
        documentTypeSelect.addEventListener('change', function() {
            updateDocumentValidation(this.value);
        });

        // Real-time validation
        documentNumberInput.addEventListener('input', validateDocumentNumber);
        emailInput.addEventListener('blur', validateEmail);

        // Initialize validation for current document type
        if (documentTypeSelect.value) {
            updateDocumentValidation(documentTypeSelect.value);
        }

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
    const requiredFields = ['nombre', 'apellido', 'tipo_documento', 'nro_documento', 'correo', 'tipo_cliente'];

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

    // Validate document number format
    if (!validateDocumentNumber()) {
        isValid = false;
    }

    // Show errors if any
    if (!isValid) {
        showFormErrors(errors);
    }

    return isValid;
}

function updateDocumentValidation(documentType) {
    const documentInput = document.getElementById('nro_documento');

    switch(documentType) {
        case 'CC':
        case 'CE':
            documentInput.pattern = '[0-9]{6,12}';
            documentInput.placeholder = 'Ej: 12345678';
            break;
        case 'PP':
            documentInput.pattern = '[A-Za-z0-9]{6,12}';
            documentInput.placeholder = 'Ej: AB123456';
            break;
        case 'NIT':
            documentInput.pattern = '[0-9]{8,15}';
            documentInput.placeholder = 'Ej: 123456789';
            break;
        default:
            documentInput.pattern = '[0-9A-Za-z\-]+';
            documentInput.placeholder = 'Número de documento';
    }
}

function validateDocumentNumber() {
    const documentType = document.getElementById('tipo_documento').value;
    const documentNumber = document.getElementById('nro_documento').value;
    const documentInput = document.getElementById('nro_documento');

    if (!documentType || !documentNumber) return true;

    let isValid = true;
    let errorMessage = '';

    switch(documentType) {
        case 'CC':
        case 'CE':
            if (!/^[0-9]{6,12}$/.test(documentNumber)) {
                isValid = false;
                errorMessage = 'Debe contener entre 6 y 12 dígitos';
            }
            break;
        case 'PP':
            if (!/^[A-Za-z0-9]{6,12}$/.test(documentNumber)) {
                isValid = false;
                errorMessage = 'Debe contener entre 6 y 12 caracteres alfanuméricos';
            }
            break;
        case 'NIT':
            if (!/^[0-9]{8,15}$/.test(documentNumber)) {
                isValid = false;
                errorMessage = 'Debe contener entre 8 y 15 dígitos';
            }
            break;
    }

    if (!isValid) {
        markFieldError(documentInput, errorMessage);
    } else {
        clearFieldError(documentInput);
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

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
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
        'apellido': 'Apellido',
        'tipo_documento': 'Tipo de documento',
        'nro_documento': 'Número de documento',
        'correo': 'Email',
        'tipo_cliente': 'Tipo de cliente'
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
    const form = document.getElementById('clientEditForm');

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
function loadClientTransactions(clientId) {
    // TODO: Implement transaction loading
    alert('Funcionalidad de transacciones pendiente de implementación');
}

function loadClientVisits(clientId) {
    // TODO: Implement visits loading
    alert('Funcionalidad de visitas pendiente de implementación');
}

function loadClientHistory(clientId) {
    // TODO: Implement history loading
    alert('Funcionalidad de historial pendiente de implementación');
}

// Auto-format inputs
document.getElementById('nro_documento').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9A-Za-z\-]/g, '');
});

document.getElementById('correo').addEventListener('blur', function(e) {
    this.value = this.value.toLowerCase().trim();
});
</script>

<style>
/* Additional styles specific to client editing */
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

.client-status {
    display: inline-block;
    padding: 4px 12px;
    border-radius: var(--border-radius);
    font-size: var(--font-size-sm);
    font-weight: 600;
    text-transform: uppercase;
    background: rgba(255, 255, 255, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.3);
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

.form-group.full-width {
    grid-column: 1 / -1;
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