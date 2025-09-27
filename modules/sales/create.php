<?php
/**
 * Create Sale - Real Estate Management System
 * Form to register new sales
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

// Sample data for dropdowns (in real app, would come from database)
$properties = [
    'INM001' => 'Casa - Calle 123 #45-67, Medellín',
    'INM003' => 'Apartamento - Carrera 70 #25-30, Medellín',
    'INM005' => 'Local - Avenida 50 #20-15, Medellín'
];

$clients = [
    'CLI001' => 'Juan Pérez - CC 12345678',
    'CLI002' => 'Ana López - CC 87654321',
    'CLI003' => 'Carlos Mendoza - CC 11223344'
];

$agents = [
    'AGE001' => 'María García',
    'AGE002' => 'Luis Fernando Pérez',
    'AGE003' => 'Carlos López'
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input data
    $formData = array_map('sanitizeInput', $_POST);

    // Basic validation
    if (empty($formData['fecha_venta'])) {
        $errors['fecha_venta'] = 'La fecha de venta es obligatoria';
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
    if (empty($formData['valor_venta']) || !is_numeric($formData['valor_venta']) || $formData['valor_venta'] <= 0) {
        $errors['valor_venta'] = 'El valor de venta debe ser un número positivo';
    }

    if (empty($errors)) {
        // For now, simulate success since database integration is pending
        $success = true;
        $saleId = rand(100, 999); // Simulate generated ID
    }
}
?>

<div class="module-header">
    <h2>Registrar Nueva Venta</h2>
    <p class="module-description">
        Complete el formulario para registrar una transacción de venta.
    </p>
</div>

<!-- Navigation Buttons -->
<div class="action-buttons">
    <a href="?module=sales" class="btn btn-secondary">
        ← Volver a la Lista
    </a>
</div>

<!-- Success Message -->
<?php if ($success): ?>
    <div class="alert alert-success">
        <h4>✅ Venta registrada exitosamente</h4>
        <p>La venta ha sido registrada con el ID <strong>VEN<?= str_pad($saleId, 3, '0', STR_PAD_LEFT) ?></strong></p>
        <div class="sale-summary">
            <p><strong>Inmueble:</strong> <?= htmlspecialchars($properties[$formData['inmueble_id']] ?? $formData['inmueble_id']) ?></p>
            <p><strong>Cliente:</strong> <?= htmlspecialchars($clients[$formData['cliente_id']] ?? $formData['cliente_id']) ?></p>
            <p><strong>Agente:</strong> <?= htmlspecialchars($agents[$formData['agente_id']] ?? $formData['agente_id']) ?></p>
            <p><strong>Valor:</strong> <?= formatCurrency($formData['valor_venta']) ?></p>
            <p><strong>Fecha:</strong> <?= formatDate($formData['fecha_venta']) ?></p>
        </div>
        <p><strong>Nota:</strong> Esta es una simulación. En la versión completa se guardará en la base de datos.</p>
        <a href="?module=sales" class="btn btn-primary">Ver Lista de Ventas</a>
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

<!-- Sale Creation Form -->
<?php if (!$success): ?>
<div class="card">
    <h3>Información de la Venta</h3>

    <form method="POST" id="sale-form">
        <!-- CSRF Token -->
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

        <!-- Sale ID (Auto-generated) -->
        <div class="form-group">
            <label>ID Venta:</label>
            <input type="text" value="Se generará automáticamente" readonly class="form-control">
            <div class="field-help">El ID se asignará automáticamente al registrar la venta</div>
        </div>

        <!-- Date and Property -->
        <div class="form-row">
            <div class="form-group">
                <label for="fecha_venta" class="required">Fecha de Venta:</label>
                <input
                    type="date"
                    name="fecha_venta"
                    id="fecha_venta"
                    required
                    value="<?= htmlspecialchars($formData['fecha_venta'] ?? date('Y-m-d')) ?>"
                    class="form-control"
                >
                <?php if (!empty($errors['fecha_venta'])): ?>
                    <div class="error-message"><?= htmlspecialchars($errors['fecha_venta']) ?></div>
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

        <!-- Client and Agent -->
        <div class="form-row">
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
            </div>
        </div>

        <!-- Sale Value -->
        <div class="form-group">
            <label for="valor_venta" class="required">Valor de Venta (COP):</label>
            <input
                type="number"
                name="valor_venta"
                id="valor_venta"
                placeholder="0"
                required
                min="1"
                step="1000"
                value="<?= htmlspecialchars($formData['valor_venta'] ?? '') ?>"
                class="form-control"
            >
            <?php if (!empty($errors['valor_venta'])): ?>
                <div class="error-message"><?= htmlspecialchars($errors['valor_venta']) ?></div>
            <?php endif; ?>
            <div class="field-help">Valor final de la transacción en pesos colombianos</div>
        </div>

        <!-- Additional Information -->
        <div class="form-group">
            <label for="observaciones">Observaciones:</label>
            <textarea
                name="observaciones"
                id="observaciones"
                placeholder="Información adicional sobre la venta..."
                class="form-control"
            ><?= htmlspecialchars($formData['observaciones'] ?? '') ?></textarea>
            <div class="field-help">Información adicional, condiciones especiales, etc.</div>
        </div>

        <!-- Form Actions -->
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                💰 Registrar Venta
            </button>
            <button type="reset" class="btn btn-secondary">
                🔄 Limpiar Formulario
            </button>
            <a href="?module=sales" class="btn btn-secondary">
                ❌ Cancelar
            </a>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="info-message">
    <p><strong>Nota de Desarrollo:</strong> Este formulario simula el registro de ventas. En la versión completa se integrará con la base de datos y se generarán documentos de la transacción.</p>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Format price input
    const priceInput = document.getElementById('valor_venta');
    if (priceInput) {
        priceInput.addEventListener('input', function(e) {
            // Remove non-numeric characters
            let value = e.target.value.replace(/[^\d]/g, '');
            if (value) {
                // Format with thousands separator for display
                const formatted = parseInt(value).toLocaleString('es-CO');
                // Keep raw value for form submission
                e.target.setAttribute('data-raw-value', value);
            }
        });

        priceInput.addEventListener('focus', function(e) {
            // Show raw value for editing
            const rawValue = e.target.getAttribute('data-raw-value');
            if (rawValue) {
                e.target.value = rawValue;
            }
        });

        priceInput.addEventListener('blur', function(e) {
            // Show formatted value
            const value = e.target.value.replace(/[^\d]/g, '');
            if (value) {
                e.target.value = parseInt(value).toLocaleString('es-CO');
            }
        });
    }

    // Auto-calculate commission (example)
    const form = document.getElementById('sale-form');
    if (form) {
        form.addEventListener('input', function() {
            const valor = parseFloat(priceInput.value.replace(/[^\d]/g, '')) || 0;
            const commission = valor * 0.03; // 3% commission

            // Show commission info (if element exists)
            const commissionInfo = document.getElementById('commission-info');
            if (commissionInfo && valor > 0) {
                commissionInfo.textContent = `Comisión estimada (3%): ${commission.toLocaleString('es-CO', {style: 'currency', currency: 'COP'})}`;
                commissionInfo.style.display = 'block';
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

.sale-summary {
    background: rgba(255, 255, 255, 0.7);
    padding: var(--spacing-md);
    border-radius: var(--border-radius);
    margin: var(--spacing-md) 0;
}

.sale-summary p {
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

#commission-info {
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