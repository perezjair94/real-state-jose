<?php
/**
 * Sale Edit Form - Real Estate Management System
 * Form to edit existing sales with database integration
 * Educational PHP/MySQL Project
 */

// Prevent direct access
if (!defined('APP_ACCESS')) {
    die('Direct access not permitted');
}

$error = '';
$success = '';
$sale = null;
$saleId = (int)($_GET['id'] ?? 0);

// Initialize form data
$formData = [
    'fecha_venta' => '',
    'valor' => '',
    'comision' => '',
    'observaciones' => '',
    'id_inmueble' => '',
    'id_cliente' => '',
    'id_agente' => ''
];

// Available properties, clients and agents for selection
$properties = [];
$clients = [];
$agents = [];

// Validate sale ID and load existing data
if ($saleId <= 0) {
    $error = "ID de venta inválido";
} else {
    try {
        $db = new Database();
        $pdo = $db->getConnection();

        // Load existing sale data with related information
        $stmt = $pdo->prepare("
            SELECT v.*,
                   i.direccion as propiedad_direccion, i.tipo_inmueble, i.precio as precio_inmueble,
                   c.nombre as cliente_nombre, c.apellido as cliente_apellido,
                   a.nombre as agente_nombre
            FROM venta v
            LEFT JOIN inmueble i ON v.id_inmueble = i.id_inmueble
            LEFT JOIN cliente c ON v.id_cliente = c.id_cliente
            LEFT JOIN agente a ON v.id_agente = a.id_agente
            WHERE v.id_venta = ?
        ");
        $stmt->execute([$saleId]);
        $sale = $stmt->fetch();

        if (!$sale) {
            $error = "Venta no encontrada";
        } else {
            // Populate form data with existing values
            $formData = [
                'fecha_venta' => $sale['fecha_venta'],
                'valor' => $sale['valor'],
                'comision' => $sale['comision'] ?? '',
                'observaciones' => $sale['observaciones'] ?? '',
                'id_inmueble' => $sale['id_inmueble'],
                'id_cliente' => $sale['id_cliente'],
                'id_agente' => $sale['id_agente'] ?? ''
            ];

            // Load available properties (only available ones + current)
            $stmt = $pdo->prepare("
                SELECT id_inmueble, tipo_inmueble, direccion, ciudad, precio, estado
                FROM inmueble
                WHERE estado = 'Disponible' OR id_inmueble = ?
                ORDER BY ciudad, direccion
            ");
            $stmt->execute([$sale['id_inmueble']]);
            $properties = $stmt->fetchAll();

            // Load all clients
            $stmt = $pdo->prepare("SELECT id_cliente, nombre, apellido, tipo_cliente FROM cliente ORDER BY nombre, apellido");
            $stmt->execute();
            $clients = $stmt->fetchAll();

            // Load active agents
            $stmt = $pdo->prepare("SELECT id_agente, nombre, correo FROM agente WHERE activo = 1 ORDER BY nombre");
            $stmt->execute();
            $agents = $stmt->fetchAll();
        }

    } catch (PDOException $e) {
        error_log("Error loading sale: " . $e->getMessage());
        $error = "Error al cargar los datos de la venta";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error && $sale) {
    try {
        // Get form data
        $formData = [
            'fecha_venta' => trim($_POST['fecha_venta'] ?? ''),
            'valor' => trim($_POST['valor'] ?? ''),
            'comision' => trim($_POST['comision'] ?? ''),
            'observaciones' => trim($_POST['observaciones'] ?? ''),
            'id_inmueble' => (int)($_POST['id_inmueble'] ?? 0),
            'id_cliente' => (int)($_POST['id_cliente'] ?? 0),
            'id_agente' => (int)($_POST['id_agente'] ?? 0)
        ];

        // Validation
        $errors = [];

        if (empty($formData['fecha_venta'])) {
            $errors[] = "La fecha de venta es obligatoria";
        }

        if (empty($formData['valor']) || $formData['valor'] <= 0) {
            $errors[] = "El valor de la venta debe ser mayor a 0";
        }

        if ($formData['id_inmueble'] <= 0) {
            $errors[] = "Debe seleccionar un inmueble";
        }

        if ($formData['id_cliente'] <= 0) {
            $errors[] = "Debe seleccionar un cliente";
        }

        if (!empty($formData['comision']) && $formData['comision'] < 0) {
            $errors[] = "La comisión no puede ser negativa";
        }

        if (!empty($errors)) {
            $error = implode('<br>', $errors);
        } else {
            // Update sale
            $stmt = $pdo->prepare("
                UPDATE venta
                SET fecha_venta = ?, valor = ?, comision = ?, observaciones = ?,
                    id_inmueble = ?, id_cliente = ?, id_agente = ?
                WHERE id_venta = ?
            ");

            $result = $stmt->execute([
                $formData['fecha_venta'],
                $formData['valor'],
                !empty($formData['comision']) ? $formData['comision'] : null,
                $formData['observaciones'],
                $formData['id_inmueble'],
                $formData['id_cliente'],
                !empty($formData['id_agente']) ? $formData['id_agente'] : null,
                $saleId
            ]);

            if ($result) {
                $success = "Venta actualizada exitosamente";

                // Refresh sale data
                $stmt = $pdo->prepare("
                    SELECT v.*,
                           i.direccion as propiedad_direccion, i.tipo_inmueble,
                           c.nombre as cliente_nombre, c.apellido as cliente_apellido,
                           a.nombre as agente_nombre
                    FROM venta v
                    LEFT JOIN inmueble i ON v.id_inmueble = i.id_inmueble
                    LEFT JOIN cliente c ON v.id_cliente = c.id_cliente
                    LEFT JOIN agente a ON v.id_agente = a.id_agente
                    WHERE v.id_venta = ?
                ");
                $stmt->execute([$saleId]);
                $sale = $stmt->fetch();
            } else {
                $error = "Error al actualizar la venta. Intente nuevamente.";
            }
        }

    } catch (PDOException $e) {
        error_log("Error updating sale: " . $e->getMessage());
        $error = "Error de base de datos. Intente nuevamente.";
    } catch (Exception $e) {
        error_log("General error updating sale: " . $e->getMessage());
        $error = "Error inesperado. Intente nuevamente.";
    }
}
?>

<div class="module-header">
    <h2>Editar Venta</h2>
    <p class="module-description">
        Modifique los datos de la venta registrada en el sistema.
    </p>
</div>

<!-- Navigation Breadcrumb -->
<div class="breadcrumb">
    <a href="?module=sales">Ventas</a> >
    <a href="?module=sales&action=view&id=<?= $saleId ?>">VEN<?= str_pad($saleId, 3, '0', STR_PAD_LEFT) ?></a> >
    Editar
</div>

<!-- Action Buttons -->
<div class="action-buttons">
    <a href="?module=sales" class="btn btn-secondary">
        ← Volver a Lista
    </a>
    <a href="?module=sales&action=view&id=<?= $saleId ?>" class="btn btn-info">
        Ver Detalles
    </a>
    <?php if ($sale): ?>
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
            <a href="?module=sales" class="btn btn-sm btn-outline">Ver Lista</a>
            <a href="?module=sales&action=view&id=<?= $saleId ?>" class="btn btn-sm btn-info">Ver Detalles</a>
        </div>
    </div>
<?php endif; ?>

<?php if ($sale): ?>
    <!-- Sale Information Summary -->
    <div class="card summary-card">
        <h3>
            Venta: VEN<?= str_pad($sale['id_venta'], 3, '0', STR_PAD_LEFT) ?>
            <span class="sale-amount"><?= formatCurrency($sale['valor']) ?></span>
        </h3>
        <div class="summary-info">
            <div class="summary-item">
                <strong>Propiedad:</strong> <?= htmlspecialchars($sale['tipo_inmueble']) ?> - <?= htmlspecialchars($sale['propiedad_direccion']) ?>
            </div>
            <div class="summary-item">
                <strong>Cliente:</strong> <?= htmlspecialchars($sale['cliente_nombre'] . ' ' . $sale['cliente_apellido']) ?>
            </div>
            <div class="summary-item">
                <strong>Registrado:</strong> <?= formatDate($sale['created_at']) ?>
            </div>
        </div>
    </div>

    <!-- Sale Edit Form -->
    <div class="card">
        <h3>Modificar Información de la Venta</h3>

        <form method="POST" id="saleEditForm" class="form-horizontal">

            <!-- Sale Details Section -->
            <fieldset>
                <legend>Detalles de la Venta</legend>

                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha_venta" class="required">Fecha de Venta:</label>
                        <input
                            type="date"
                            id="fecha_venta"
                            name="fecha_venta"
                            value="<?= htmlspecialchars($formData['fecha_venta']) ?>"
                            required
                            max="<?= date('Y-m-d') ?>"
                            class="form-control"
                        >
                        <small class="field-help">Fecha en que se completó la venta</small>
                    </div>

                    <div class="form-group">
                        <label for="valor" class="required">Valor de la Venta:</label>
                        <input
                            type="number"
                            id="valor"
                            name="valor"
                            value="<?= htmlspecialchars($formData['valor']) ?>"
                            required
                            min="0"
                            step="0.01"
                            class="form-control"
                            placeholder="0.00"
                        >
                        <small class="field-help">Precio final de venta en COP</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="comision">Comisión del Agente:</label>
                        <input
                            type="number"
                            id="comision"
                            name="comision"
                            value="<?= htmlspecialchars($formData['comision']) ?>"
                            min="0"
                            step="0.01"
                            class="form-control"
                            placeholder="0.00"
                        >
                        <small class="field-help">Comisión pagada al agente (opcional)</small>
                    </div>

                    <div class="form-group">
                        <label>&nbsp;</label>
                        <div class="commission-calculator">
                            <button type="button" class="btn btn-sm btn-outline" onclick="calculateCommission(5)">
                                5% del valor
                            </button>
                            <button type="button" class="btn btn-sm btn-outline" onclick="calculateCommission(3)">
                                3% del valor
                            </button>
                        </div>
                    </div>
                </div>
            </fieldset>

            <!-- Property Selection Section -->
            <fieldset>
                <legend>Inmueble Vendido</legend>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="id_inmueble" class="required">Seleccione el Inmueble:</label>
                        <select id="id_inmueble" name="id_inmueble" required class="form-control">
                            <option value="">Seleccione un inmueble</option>
                            <?php foreach ($properties as $property): ?>
                                <option
                                    value="<?= $property['id_inmueble'] ?>"
                                    data-price="<?= $property['precio'] ?>"
                                    data-status="<?= $property['estado'] ?>"
                                    <?= $formData['id_inmueble'] == $property['id_inmueble'] ? 'selected' : '' ?>
                                >
                                    INM<?= str_pad($property['id_inmueble'], 3, '0', STR_PAD_LEFT) ?> -
                                    <?= htmlspecialchars($property['tipo_inmueble']) ?> en
                                    <?= htmlspecialchars($property['ciudad']) ?> -
                                    <?= formatCurrency($property['precio']) ?>
                                    <?php if ($property['estado'] === 'Vendido'): ?>
                                        (Vendido)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="field-help">Propiedad que se vendió</small>
                    </div>
                </div>
            </fieldset>

            <!-- Client Selection Section -->
            <fieldset>
                <legend>Cliente Comprador</legend>

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
                        <small class="field-help">Cliente que compró la propiedad</small>
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
                        <small class="field-help">Agente que gestionó la venta (opcional)</small>
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
                            placeholder="Ingrese observaciones sobre la venta (opcional)"
                        ><?= htmlspecialchars($formData['observaciones']) ?></textarea>
                        <small class="field-help">Información adicional sobre la transacción</small>
                    </div>
                </div>
            </fieldset>

            <!-- Form Actions -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    Guardar Cambios
                </button>
                <a href="?module=sales&action=view&id=<?= $saleId ?>" class="btn btn-secondary">
                    Cancelar
                </a>
                <button type="button" class="btn btn-outline" onclick="resetForm()">
                    Revertir
                </button>
            </div>
        </form>
    </div>

<?php else: ?>
    <!-- No Sale Found -->
    <div class="card">
        <div class="no-results">
            <h3>Venta No Encontrada</h3>
            <p>La venta solicitada no existe o ha sido eliminada.</p>
            <a href="?module=sales" class="btn btn-primary">Volver a Lista de Ventas</a>
        </div>
    </div>
<?php endif; ?>

<!-- JavaScript for Enhanced Functionality -->
<script>
/**
 * Sale edit form functionality
 */

// Store original form values for reset functionality
const originalFormData = <?= json_encode($formData) ?>;

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('saleEditForm');

    if (form) {
        // Form validation
        form.addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
            }
        });

        // Property selection handler
        const propertySelect = document.getElementById('id_inmueble');
        propertySelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const price = selectedOption.dataset.price;

            if (price && !document.getElementById('valor').value) {
                document.getElementById('valor').value = price;
                calculateCommission(5); // Default 5% commission
            }
        });

        // Track changes for unsaved changes warning
        trackFormChanges();
    }
});

function validateForm() {
    let isValid = true;
    const errors = [];

    // Clear previous error states
    clearErrorStates();

    // Validate fecha_venta
    const fechaVenta = document.getElementById('fecha_venta').value;
    if (!fechaVenta) {
        errors.push('La fecha de venta es obligatoria');
        markFieldError(document.getElementById('fecha_venta'));
        isValid = false;
    }

    // Validate valor
    const valor = parseFloat(document.getElementById('valor').value);
    if (!valor || valor <= 0) {
        errors.push('El valor de la venta debe ser mayor a 0');
        markFieldError(document.getElementById('valor'));
        isValid = false;
    }

    // Validate id_inmueble
    const idInmueble = document.getElementById('id_inmueble').value;
    if (!idInmueble) {
        errors.push('Debe seleccionar un inmueble');
        markFieldError(document.getElementById('id_inmueble'));
        isValid = false;
    }

    // Validate id_cliente
    const idCliente = document.getElementById('id_cliente').value;
    if (!idCliente) {
        errors.push('Debe seleccionar un cliente');
        markFieldError(document.getElementById('id_cliente'));
        isValid = false;
    }

    // Validate comision (if provided)
    const comision = document.getElementById('comision').value;
    if (comision && parseFloat(comision) < 0) {
        errors.push('La comisión no puede ser negativa');
        markFieldError(document.getElementById('comision'));
        isValid = false;
    }

    if (!isValid) {
        showFormErrors(errors);
    }

    return isValid;
}

function calculateCommission(percentage) {
    const valor = parseFloat(document.getElementById('valor').value);
    if (valor && valor > 0) {
        const commission = (valor * percentage / 100).toFixed(2);
        document.getElementById('comision').value = commission;
    } else {
        alert('Ingrese primero el valor de la venta');
    }
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
    const form = document.getElementById('saleEditForm');

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
</script>

<style>
/* Additional styles specific to sale editing */
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

.summary-card {
    background: linear-gradient(135deg, #28a745, #20c997);
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

.sale-amount {
    font-size: 1.5rem;
    font-weight: 700;
    background: rgba(255, 255, 255, 0.2);
    padding: 8px 16px;
    border-radius: var(--border-radius);
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

.commission-calculator {
    display: flex;
    gap: var(--spacing-sm);
    padding-top: 8px;
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

    .commission-calculator {
        flex-direction: column;
    }
}
</style>