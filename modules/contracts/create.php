<?php
/**
 * Create Contract - Real Estate Management System
 * Form to create new contracts
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

// Get data for dropdowns from database
$properties = [];
$clients = [];

try {
    $db = new Database();
    $pdo = $db->getConnection();

    // Get available properties
    $propSql = "SELECT id_inmueble, tipo_inmueble, direccion, ciudad, precio, estado
                FROM inmueble
                WHERE estado = 'Disponible'
                ORDER BY created_at DESC";
    $propStmt = $pdo->prepare($propSql);
    $propStmt->execute();

    while ($prop = $propStmt->fetch()) {
        $key = $prop['id_inmueble'];
        $value = $prop['tipo_inmueble'] . ' - ' . $prop['direccion'] . ', ' . $prop['ciudad'] .
                 ' (' . formatCurrency($prop['precio']) . ') - ' . $prop['estado'];
        $properties[$key] = $value;
    }

    // Get all clients
    $clientSql = "SELECT id_cliente, nombre, apellido, tipo_documento, nro_documento, tipo_cliente
                  FROM cliente
                  ORDER BY nombre, apellido";
    $clientStmt = $pdo->prepare($clientSql);
    $clientStmt->execute();

    while ($client = $clientStmt->fetch()) {
        $key = $client['id_cliente'];
        $value = $client['nombre'] . ' ' . $client['apellido'] . ' - ' .
                 $client['tipo_documento'] . ' ' . $client['nro_documento'] .
                 ' (' . $client['tipo_cliente'] . ')';
        $clients[$key] = $value;
    }

} catch (PDOException $e) {
    error_log("Error loading form data: " . $e->getMessage());
    $error = "Error al cargar los datos del formulario.";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input data
    $formData = array_map('sanitizeInput', $_POST);

    // Basic validation
    if (empty($formData['tipo_contrato'])) {
        $errors['tipo_contrato'] = 'El tipo de contrato es obligatorio';
    }
    if (empty($formData['inmueble_id'])) {
        $errors['inmueble_id'] = 'Debe seleccionar un inmueble';
    }
    if (empty($formData['cliente_id'])) {
        $errors['cliente_id'] = 'Debe seleccionar un cliente';
    }
    if (empty($formData['fecha_inicio'])) {
        $errors['fecha_inicio'] = 'La fecha de inicio es obligatoria';
    }
    if (empty($formData['fecha_fin'])) {
        $errors['fecha_fin'] = 'La fecha de fin es obligatoria';
    }
    if (!empty($formData['fecha_inicio']) && !empty($formData['fecha_fin'])) {
        if (strtotime($formData['fecha_fin']) <= strtotime($formData['fecha_inicio'])) {
            $errors['fecha_fin'] = 'La fecha de fin debe ser posterior a la fecha de inicio';
        }
    }

    if (empty($errors)) {
        try {
            $db = new Database();
            $pdo = $db->getConnection();

            // Handle file upload if present
            $archivoContrato = null;
            if (isset($_FILES['archivo_contrato']) && $_FILES['archivo_contrato']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['archivo_contrato'];
                $validation = validateUploadedFile($file, ALLOWED_DOCUMENT_TYPES);

                if ($validation['valid']) {
                    $newFilename = generateUniqueFilename($file['name']);
                    $uploadPath = UPLOAD_PATH_CONTRACTS . $newFilename;

                    // Create directory if it doesn't exist
                    if (!file_exists(UPLOAD_PATH_CONTRACTS)) {
                        mkdir(UPLOAD_PATH_CONTRACTS, 0755, true);
                    }

                    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                        $archivoContrato = $newFilename;
                    }
                } else {
                    $errors['archivo_contrato'] = $validation['error'];
                }
            }

            if (empty($errors)) {
                // Insert new contract
                $sql = "INSERT INTO contrato (tipo_contrato, fecha_inicio, fecha_fin, valor_contrato,
                        archivo_contrato, estado, observaciones, id_inmueble, id_cliente, id_agente)
                        VALUES (?, ?, ?, ?, ?, 'Borrador', ?, ?, ?, NULL)";

                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([
                    $formData['tipo_contrato'],
                    $formData['fecha_inicio'],
                    $formData['fecha_fin'] ?: null,
                    $formData['valor_contrato'] ?: 0,
                    $archivoContrato,
                    $formData['clausulas_especiales'] ?: null,
                    $formData['inmueble_id'],
                    $formData['cliente_id']
                ]);

                if ($result) {
                    $contractId = $pdo->lastInsertId();

                    redirectWithMessage(
                        '?module=contracts',
                        "Contrato CON" . str_pad($contractId, 3, '0', STR_PAD_LEFT) . " creado exitosamente",
                        'success'
                    );
                } else {
                    throw new Exception("Error al insertar en la base de datos");
                }
            }

        } catch (PDOException $e) {
            error_log("Error creating contract: " . $e->getMessage());
            $errors['general'] = "Error al crear el contrato. Intente nuevamente.";
        } catch (Exception $e) {
            error_log("General error creating contract: " . $e->getMessage());
            $errors['general'] = $e->getMessage();
        }
    }
}
?>

<div class="module-header">
    <h2>Crear Nuevo Contrato</h2>
    <p class="module-description">
        Complete el formulario para crear un contrato de compraventa o arrendamiento.
    </p>
</div>

<!-- Navigation Buttons -->
<div class="action-buttons">
    <a href="?module=contracts" class="btn btn-secondary">
        ← Volver a la Lista
    </a>
</div>

<!-- Success Message -->
<?php if ($success): ?>
    <div class="alert alert-success">
        <h4>✅ Contrato creado exitosamente</h4>
        <p>El contrato ha sido creado con el ID <strong>CON<?= str_pad($contractId, 3, '0', STR_PAD_LEFT) ?></strong></p>
        <div class="contract-summary">
            <p><strong>Tipo:</strong> <?= htmlspecialchars($formData['tipo_contrato']) ?></p>
            <p><strong>Inmueble:</strong> <?= htmlspecialchars($properties[$formData['inmueble_id']] ?? $formData['inmueble_id']) ?></p>
            <p><strong>Cliente:</strong> <?= htmlspecialchars($clients[$formData['cliente_id']] ?? $formData['cliente_id']) ?></p>
            <p><strong>Período:</strong> <?= formatDate($formData['fecha_inicio']) ?> - <?= formatDate($formData['fecha_fin']) ?></p>
        </div>
        <p><strong>Nota:</strong> Esta es una simulación. En la versión completa se guardará en la base de datos y se generará el documento PDF.</p>
        <a href="?module=contracts" class="btn btn-primary">Ver Lista de Contratos</a>
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

<!-- Contract Creation Form -->
<?php if (!$success): ?>
<div class="card">
    <h3>Información del Contrato</h3>

    <form method="POST" enctype="multipart/form-data" id="contract-form">
        <!-- CSRF Token -->
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

        <!-- Contract ID (Auto-generated) -->
        <div class="form-group">
            <label>ID Contrato:</label>
            <input type="text" value="Se generará automáticamente" readonly class="form-control">
            <div class="field-help">El ID se asignará automáticamente al crear el contrato</div>
        </div>

        <!-- Contract Type and Property -->
        <div class="form-row">
            <div class="form-group">
                <label for="tipo_contrato" class="required">Tipo de Contrato:</label>
                <select name="tipo_contrato" id="tipo_contrato" required class="form-control">
                    <option value="">Seleccione el tipo...</option>
                    <option value="Venta" <?= ($formData['tipo_contrato'] ?? '') === 'Venta' ? 'selected' : '' ?>>
                        Venta
                    </option>
                    <option value="Arriendo" <?= ($formData['tipo_contrato'] ?? '') === 'Arriendo' ? 'selected' : '' ?>>
                        Arriendo
                    </option>
                </select>
                <?php if (!empty($errors['tipo_contrato'])): ?>
                    <div class="error-message"><?= htmlspecialchars($errors['tipo_contrato']) ?></div>
                <?php endif; ?>
            </div>

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
        </div>

        <!-- Dates -->
        <div class="form-row">
            <div class="form-group">
                <label for="fecha_inicio" class="required">Fecha de Inicio:</label>
                <input
                    type="date"
                    name="fecha_inicio"
                    id="fecha_inicio"
                    required
                    value="<?= htmlspecialchars($formData['fecha_inicio'] ?? date('Y-m-d')) ?>"
                    class="form-control"
                >
                <?php if (!empty($errors['fecha_inicio'])): ?>
                    <div class="error-message"><?= htmlspecialchars($errors['fecha_inicio']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="fecha_fin" class="required">Fecha de Fin:</label>
                <input
                    type="date"
                    name="fecha_fin"
                    id="fecha_fin"
                    required
                    value="<?= htmlspecialchars($formData['fecha_fin'] ?? '') ?>"
                    class="form-control"
                >
                <?php if (!empty($errors['fecha_fin'])): ?>
                    <div class="error-message"><?= htmlspecialchars($errors['fecha_fin']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Client Selection -->
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

        <!-- Contract Value (conditional) -->
        <div class="form-group" id="valor-group" style="display: none;">
            <label for="valor_contrato">Valor del Contrato (COP):</label>
            <input
                type="number"
                name="valor_contrato"
                id="valor_contrato"
                placeholder="0"
                min="1"
                step="1000"
                value="<?= htmlspecialchars($formData['valor_contrato'] ?? '') ?>"
                class="form-control"
            >
            <div class="field-help" id="valor-help">Valor total del contrato</div>
        </div>

        <!-- Additional Terms -->
        <div class="form-group">
            <label for="clausulas_especiales">Cláusulas Especiales:</label>
            <textarea
                name="clausulas_especiales"
                id="clausulas_especiales"
                placeholder="Condiciones especiales, términos adicionales..."
                class="form-control"
            ><?= htmlspecialchars($formData['clausulas_especiales'] ?? '') ?></textarea>
            <div class="field-help">Términos y condiciones especiales del contrato</div>
        </div>

        <!-- File Upload -->
        <div class="form-group">
            <label for="archivo_contrato">Archivo del Contrato:</label>
            <input
                type="file"
                name="archivo_contrato"
                id="archivo_contrato"
                accept=".pdf,.doc,.docx"
                class="form-control"
            >
            <div class="field-help">Opcional: Archivo PDF o Word del contrato firmado</div>
        </div>

        <!-- Form Actions -->
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                📄 Crear Contrato
            </button>
            <button type="reset" class="btn btn-secondary">
                🔄 Limpiar Formulario
            </button>
            <a href="?module=contracts" class="btn btn-secondary">
                ❌ Cancelar
            </a>
        </div>
    </form>
</div>
<?php endif; ?>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const tipoContrato = document.getElementById('tipo_contrato');
    const valorGroup = document.getElementById('valor-group');
    const valorInput = document.getElementById('valor_contrato');
    const valorHelp = document.getElementById('valor-help');

    // Show/hide value field based on contract type
    function toggleValueField() {
        const tipo = tipoContrato.value;

        if (tipo === 'Venta') {
            valorGroup.style.display = 'block';
            valorInput.required = true;
            valorHelp.textContent = 'Precio de venta total';
        } else if (tipo === 'Arriendo') {
            valorGroup.style.display = 'block';
            valorInput.required = false;
            valorHelp.textContent = 'Canon mensual de arrendamiento';
        } else {
            valorGroup.style.display = 'none';
            valorInput.required = false;
        }
    }

    if (tipoContrato) {
        tipoContrato.addEventListener('change', toggleValueField);
        toggleValueField(); // Initialize on page load
    }

    // Auto-calculate end date for rental contracts (1 year default)
    const fechaInicio = document.getElementById('fecha_inicio');
    const fechaFin = document.getElementById('fecha_fin');

    if (fechaInicio && fechaFin) {
        fechaInicio.addEventListener('change', function() {
            if (tipoContrato.value === 'Arriendo' && !fechaFin.value) {
                const startDate = new Date(this.value);
                const endDate = new Date(startDate);
                endDate.setFullYear(endDate.getFullYear() + 1); // Add 1 year

                const year = endDate.getFullYear();
                const month = String(endDate.getMonth() + 1).padStart(2, '0');
                const day = String(endDate.getDate()).padStart(2, '0');

                fechaFin.value = `${year}-${month}-${day}`;
            }
        });
    }

    // Format value input
    if (valorInput) {
        valorInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^\d]/g, '');
            if (value) {
                e.target.setAttribute('data-raw-value', value);
            }
        });

        valorInput.addEventListener('blur', function(e) {
            const value = e.target.value.replace(/[^\d]/g, '');
            if (value) {
                e.target.value = parseInt(value).toLocaleString('es-CO');
            }
        });

        valorInput.addEventListener('focus', function(e) {
            const rawValue = e.target.getAttribute('data-raw-value');
            if (rawValue) {
                e.target.value = rawValue;
            }
        });
    }

    // Calculate contract duration
    function updateDuration() {
        if (fechaInicio.value && fechaFin.value) {
            const start = new Date(fechaInicio.value);
            const end = new Date(fechaFin.value);
            const diffTime = Math.abs(end - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            const diffMonths = Math.round(diffDays / 30);

            const durationInfo = document.getElementById('duration-info');
            if (durationInfo) {
                durationInfo.textContent = `Duración: ${diffDays} días (≈${diffMonths} meses)`;
                durationInfo.style.display = 'block';
            }
        }
    }

    if (fechaInicio && fechaFin) {
        fechaInicio.addEventListener('change', updateDuration);
        fechaFin.addEventListener('change', updateDuration);
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

.contract-summary {
    background: rgba(255, 255, 255, 0.7);
    padding: var(--spacing-md);
    border-radius: var(--border-radius);
    margin: var(--spacing-md) 0;
}

.contract-summary p {
    margin-bottom: var(--spacing-xs);
}

.info-message {
    background-color: #e2e3e5;
    border: 1px solid #d6d8db;
    color: #383d41;
    padding: var(--spacing-md);
    border-radius: var(--border-radius);
    margin-top: var(--spacing-lg);
}

#duration-info {
    display: none;
    color: var(--primary-color);
    font-weight: 500;
    margin-top: var(--spacing-xs);
}

@media (max-width: 768px) {
    .form-actions {
        flex-direction: column;
    }

    .form-actions .btn {
        width: 100%;
    }
}
</style>