<?php
/**
 * Client Create Form - Real Estate Management System
 * Form to create new clients with database integration
 * Educational PHP/MySQL Project
 */

// Prevent direct access
if (!defined('APP_ACCESS')) {
    die('Direct access not permitted');
}

$error = '';
$success = '';
$formData = [
    'nombre' => '',
    'apellido' => '',
    'tipo_documento' => '',
    'nro_documento' => '',
    'correo' => '',
    'direccion' => '',
    'tipo_cliente' => ''
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            // Connect to database and insert
            $db = new Database();
            $pdo = $db->getConnection();

            // Check for duplicate document number
            $checkStmt = $pdo->prepare("SELECT id_cliente FROM cliente WHERE nro_documento = ?");
            $checkStmt->execute([$formData['nro_documento']]);
            if ($checkStmt->fetchColumn()) {
                $error = "Ya existe un cliente con este número de documento";
            } else {
                // Check for duplicate email
                $checkStmt = $pdo->prepare("SELECT id_cliente FROM cliente WHERE correo = ?");
                $checkStmt->execute([$formData['correo']]);
                if ($checkStmt->fetchColumn()) {
                    $error = "Ya existe un cliente con este email";
                } else {
                    // Insert new client
                    $stmt = $pdo->prepare("
                        INSERT INTO cliente (nombre, apellido, tipo_documento, nro_documento, correo, direccion, tipo_cliente)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");

                    $result = $stmt->execute([
                        $formData['nombre'],
                        $formData['apellido'],
                        $formData['tipo_documento'],
                        $formData['nro_documento'],
                        $formData['correo'],
                        $formData['direccion'],
                        $formData['tipo_cliente']
                    ]);

                    if ($result) {
                        $clientId = $pdo->lastInsertId();
                        $success = "Cliente creado exitosamente con ID: CLI" . str_pad($clientId, 3, '0', STR_PAD_LEFT);

                        // Reset form data for new entry
                        $formData = [
                            'nombre' => '',
                            'apellido' => '',
                            'tipo_documento' => '',
                            'nro_documento' => '',
                            'correo' => '',
                            'direccion' => '',
                            'tipo_cliente' => ''
                        ];
                    } else {
                        $error = "Error al crear el cliente. Intente nuevamente.";
                    }
                }
            }
        }

    } catch (PDOException $e) {
        error_log("Error creating client: " . $e->getMessage());
        $error = "Error de base de datos. Intente nuevamente.";
    } catch (Exception $e) {
        error_log("General error creating client: " . $e->getMessage());
        $error = "Error inesperado. Intente nuevamente.";
    }
}
?>

<div class="module-header">
    <h2>Crear Nuevo Cliente</h2>
    <p class="module-description">
        Complete el formulario para registrar un nuevo cliente en el sistema.
    </p>
</div>

<!-- Navigation Breadcrumb -->
<div class="breadcrumb">
    <a href="?module=clients">Clientes</a> > Crear Nuevo
</div>

<!-- Action Buttons -->
<div class="action-buttons">
    <a href="?module=clients" class="btn btn-secondary">
        ← Volver a Lista
    </a>
    <button type="button" class="btn btn-info" onclick="clearForm()">
        Limpiar Formulario
    </button>
</div>

<!-- Success Message -->
<?php if (!empty($success)): ?>
    <div class="alert alert-success">
        <?= htmlspecialchars($success) ?>
        <div class="alert-actions">
            <a href="?module=clients" class="btn btn-sm btn-outline">Ver Lista</a>
            <button type="button" class="btn btn-sm btn-primary" onclick="createAnother()">Crear Otro</button>
        </div>
    </div>
<?php endif; ?>

<!-- Error Message -->
<?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <?= $error ?>
    </div>
<?php endif; ?>

<!-- Client Creation Form -->
<div class="card">
    <h3>Información del Cliente</h3>

    <form method="POST" id="clientForm" class="form-horizontal">
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
                Crear Cliente
            </button>
            <button type="button" class="btn btn-secondary" onclick="window.history.back()">
                Cancelar
            </button>
            <button type="reset" class="btn btn-outline" onclick="clearForm()">
                Limpiar
            </button>
        </div>
    </form>
</div>

<!-- Information Cards -->
<div class="info-cards">
    <div class="card info-card">
        <h4>Tipos de Cliente</h4>
        <ul>
            <li><strong>Comprador:</strong> Interesado en adquirir propiedades</li>
            <li><strong>Vendedor:</strong> Propietario que desea vender</li>
            <li><strong>Arrendatario:</strong> Busca propiedades en arriendo</li>
            <li><strong>Arrendador:</strong> Propietario que arrienda</li>
        </ul>
    </div>

    <div class="card info-card">
        <h4>Documentos Válidos</h4>
        <ul>
            <li><strong>CC:</strong> Cédula de Ciudadanía</li>
            <li><strong>CE:</strong> Cédula de Extranjería</li>
            <li><strong>PP:</strong> Pasaporte</li>
            <li><strong>NIT:</strong> Número de Identificación Tributaria</li>
        </ul>
    </div>
</div>

<!-- JavaScript for Enhanced Functionality -->
<script>
/**
 * Client creation form functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('clientForm');
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

    // Real-time document number validation
    documentNumberInput.addEventListener('input', function() {
        validateDocumentNumber();
    });

    // Real-time email validation
    emailInput.addEventListener('blur', function() {
        validateEmail();
    });

    // Initialize validation for current document type
    if (documentTypeSelect.value) {
        updateDocumentValidation(documentTypeSelect.value);
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

    // Remove existing error message
    const existingError = field.parentNode.querySelector('.error-message');
    if (existingError) {
        existingError.remove();
    }

    // Add new error message if provided
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

    // Clear general error display
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

function clearForm() {
    if (confirm('¿Está seguro de que desea limpiar todos los campos?')) {
        document.getElementById('clientForm').reset();
        clearErrorStates();
    }
}

function createAnother() {
    // Clear any success messages and reset form
    const successAlert = document.querySelector('.alert-success');
    if (successAlert) {
        successAlert.style.display = 'none';
    }
    clearForm();
    document.getElementById('nombre').focus();
}

// Auto-format document number
document.getElementById('nro_documento').addEventListener('input', function(e) {
    // Remove any non-alphanumeric characters except hyphens
    this.value = this.value.replace(/[^0-9A-Za-z\-]/g, '');
});

// Auto-format email to lowercase
document.getElementById('correo').addEventListener('blur', function(e) {
    this.value = this.value.toLowerCase().trim();
});
</script>

<style>
/* Additional styles specific to client creation */
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

.alert-actions {
    margin-top: var(--spacing-sm);
    display: flex;
    gap: var(--spacing-sm);
}

.info-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: var(--spacing-lg);
    margin-top: var(--spacing-xl);
}

.info-card {
    background: var(--bg-secondary);
    border-left: 4px solid var(--secondary-color);
}

.info-card h4 {
    color: var(--secondary-color);
    margin-bottom: var(--spacing-md);
}

.info-card ul {
    list-style: none;
    padding: 0;
}

.info-card li {
    padding: var(--spacing-xs) 0;
    border-bottom: 1px solid var(--border-color);
}

.info-card li:last-child {
    border-bottom: none;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    fieldset {
        padding: var(--spacing-md);
    }

    .form-row {
        grid-template-columns: 1fr;
    }

    .info-cards {
        grid-template-columns: 1fr;
    }
}
</style>