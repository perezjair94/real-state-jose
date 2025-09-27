<?php
/**
 * Create Rental - Real Estate Management System
 * Form to register new rental agreements
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
    'INM005' => 'Local - Avenida 50 #20-15, Medellín',
    'INM007' => 'Apartamento - Carrera 80 #30-45, Medellín'
];

$tenants = [
    'CLI001' => 'Juan Pérez - CC 12345678',
    'CLI002' => 'Ana López - CC 87654321',
    'CLI003' => 'Carlos Mendoza - CC 11223344',
    'CLI004' => 'Roberto Silva - CC 55667788'
];

$landlords = [
    'CLI005' => 'María Gómez - CC 99887766',
    'CLI006' => 'Luis Torres - CC 44332211',
    'CLI007' => 'Elena Vargas - CC 77889900'
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input data
    $formData = array_map('sanitizeInput', $_POST);

    // Basic validation
    if (empty($formData['inmueble_id'])) {
        $errors['inmueble_id'] = 'Debe seleccionar un inmueble';
    }
    if (empty($formData['arrendatario_id'])) {
        $errors['arrendatario_id'] = 'Debe seleccionar un arrendatario';
    }
    if (empty($formData['arrendador_id'])) {
        $errors['arrendador_id'] = 'Debe seleccionar un arrendador';
    }
    if (empty($formData['fecha_inicio'])) {
        $errors['fecha_inicio'] = 'La fecha de inicio es obligatoria';
    }
    if (empty($formData['fecha_fin'])) {
        $errors['fecha_fin'] = 'La fecha de fin es obligatoria';
    }
    if (empty($formData['canon_mensual']) || !is_numeric($formData['canon_mensual']) || $formData['canon_mensual'] <= 0) {
        $errors['canon_mensual'] = 'El canon mensual debe ser un número positivo';
    }
    if (!empty($formData['deposito']) && (!is_numeric($formData['deposito']) || $formData['deposito'] < 0)) {
        $errors['deposito'] = 'El depósito debe ser un número positivo o cero';
    }

    // Date validation
    if (!empty($formData['fecha_inicio']) && !empty($formData['fecha_fin'])) {
        if (strtotime($formData['fecha_fin']) <= strtotime($formData['fecha_inicio'])) {
            $errors['fecha_fin'] = 'La fecha de fin debe ser posterior a la fecha de inicio';
        }
    }

    if (empty($errors)) {
        // For now, simulate success since database integration is pending
        $success = true;
        $rentalId = rand(100, 999); // Simulate generated ID
    }
}
?>

<div class="module-header">
    <h2>Registrar Nuevo Arriendo</h2>
    <p class="module-description">
        Complete el formulario para registrar un nuevo contrato de arrendamiento.
    </p>
</div>

<!-- Navigation Buttons -->
<div class="action-buttons">
    <a href="?module=rentals" class="btn btn-secondary">
        ← Volver a la Lista
    </a>
</div>

<!-- Success Message -->
<?php if ($success): ?>
    <div class="alert alert-success">
        <h4>✅ Arriendo registrado exitosamente</h4>
        <p>El contrato de arriendo ha sido registrado con el ID <strong>ARR<?= str_pad($rentalId, 3, '0', STR_PAD_LEFT) ?></strong></p>
        <div class="rental-summary">
            <p><strong>Inmueble:</strong> <?= htmlspecialchars($properties[$formData['inmueble_id']] ?? $formData['inmueble_id']) ?></p>
            <p><strong>Arrendatario:</strong> <?= htmlspecialchars($tenants[$formData['arrendatario_id']] ?? $formData['arrendatario_id']) ?></p>
            <p><strong>Arrendador:</strong> <?= htmlspecialchars($landlords[$formData['arrendador_id']] ?? $formData['arrendador_id']) ?></p>
            <p><strong>Canon Mensual:</strong> <?= formatCurrency($formData['canon_mensual']) ?></p>
            <p><strong>Período:</strong> <?= formatDate($formData['fecha_inicio']) ?> - <?= formatDate($formData['fecha_fin']) ?></p>
            <?php if (!empty($formData['deposito'])): ?>
                <p><strong>Depósito:</strong> <?= formatCurrency($formData['deposito']) ?></p>
            <?php endif; ?>
        </div>
        <p><strong>Nota:</strong> Esta es una simulación. En la versión completa se guardará en la base de datos y se generará el contrato.</p>
        <a href="?module=rentals" class="btn btn-primary">Ver Lista de Arriendos</a>
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

<!-- Rental Creation Form -->
<?php if (!$success): ?>
<div class="card">
    <h3>Información del Arriendo</h3>

    <form method="POST" id="rental-form">
        <!-- CSRF Token -->
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

        <!-- Rental ID (Auto-generated) -->
        <div class="form-group">
            <label>ID Arriendo:</label>
            <input type="text" value="Se generará automáticamente" readonly class="form-control">
            <div class="field-help">El ID se asignará automáticamente al registrar el arriendo</div>
        </div>

        <!-- Property Selection -->
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

        <!-- Parties -->
        <div class="form-row">
            <div class="form-group">
                <label for="arrendatario_id" class="required">Arrendatario (Inquilino):</label>
                <select name="arrendatario_id" id="arrendatario_id" required class="form-control">
                    <option value="">Seleccione el arrendatario...</option>
                    <?php foreach ($tenants as $id => $description): ?>
                        <option value="<?= $id ?>" <?= ($formData['arrendatario_id'] ?? '') === $id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($description) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['arrendatario_id'])): ?>
                    <div class="error-message"><?= htmlspecialchars($errors['arrendatario_id']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="arrendador_id" class="required">Arrendador (Propietario):</label>
                <select name="arrendador_id" id="arrendador_id" required class="form-control">
                    <option value="">Seleccione el arrendador...</option>
                    <?php foreach ($landlords as $id => $description): ?>
                        <option value="<?= $id ?>" <?= ($formData['arrendador_id'] ?? '') === $id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($description) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['arrendador_id'])): ?>
                    <div class="error-message"><?= htmlspecialchars($errors['arrendador_id']) ?></div>
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
                <div id="duration-info" class="field-help"></div>
            </div>
        </div>

        <!-- Financial Information -->
        <div class="form-row">
            <div class="form-group">
                <label for="canon_mensual" class="required">Canon Mensual (COP):</label>
                <input
                    type="number"
                    name="canon_mensual"
                    id="canon_mensual"
                    placeholder="0"
                    required
                    min="1"
                    step="1000"
                    value="<?= htmlspecialchars($formData['canon_mensual'] ?? '') ?>"
                    class="form-control"
                >
                <?php if (!empty($errors['canon_mensual'])): ?>
                    <div class="error-message"><?= htmlspecialchars($errors['canon_mensual']) ?></div>
                <?php endif; ?>
                <div class="field-help">Valor mensual del arriendo</div>
            </div>

            <div class="form-group">
                <label for="deposito">Depósito de Garantía (COP):</label>
                <input
                    type="number"
                    name="deposito"
                    id="deposito"
                    placeholder="0"
                    min="0"
                    step="1000"
                    value="<?= htmlspecialchars($formData['deposito'] ?? '') ?>"
                    class="form-control"
                >
                <?php if (!empty($errors['deposito'])): ?>
                    <div class="error-message"><?= htmlspecialchars($errors['deposito']) ?></div>
                <?php endif; ?>
                <div class="field-help">Opcional: Generalmente equivale a 1-2 meses de canon</div>
            </div>
        </div>

        <!-- Status -->
        <div class="form-group">
            <label for="estado">Estado:</label>
            <select name="estado" id="estado" class="form-control">
                <option value="Activo" <?= ($formData['estado'] ?? 'Activo') === 'Activo' ? 'selected' : '' ?>>
                    Activo
                </option>
                <option value="Pendiente" <?= ($formData['estado'] ?? '') === 'Pendiente' ? 'selected' : '' ?>>
                    Pendiente de Inicio
                </option>
            </select>
            <div class="field-help">Estado inicial del contrato de arriendo</div>
        </div>

        <!-- Additional Terms -->
        <div class="form-group">
            <label for="clausulas_especiales">Cláusulas Especiales:</label>
            <textarea
                name="clausulas_especiales"
                id="clausulas_especiales"
                placeholder="Condiciones especiales del arriendo: mascotas, reparaciones, incrementos, etc."
                class="form-control"
            ><?= htmlspecialchars($formData['clausulas_especiales'] ?? '') ?></textarea>
            <div class="field-help">Términos especiales del contrato de arrendamiento</div>
        </div>

        <!-- Payment Information -->
        <fieldset class="form-section">
            <legend>Información de Pagos</legend>

            <div class="form-row">
                <div class="form-group">
                    <label for="dia_pago">Día de Pago Mensual:</label>
                    <select name="dia_pago" id="dia_pago" class="form-control">
                        <?php for ($i = 1; $i <= 28; $i++): ?>
                            <option value="<?= $i ?>" <?= ($formData['dia_pago'] ?? '1') == $i ? 'selected' : '' ?>>
                                <?= $i ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    <div class="field-help">Día del mes para el pago del canon</div>
                </div>

                <div class="form-group">
                    <label for="incremento_anual">Incremento Anual (%):</label>
                    <input
                        type="number"
                        name="incremento_anual"
                        id="incremento_anual"
                        placeholder="0.00"
                        min="0"
                        max="100"
                        step="0.01"
                        value="<?= htmlspecialchars($formData['incremento_anual'] ?? '') ?>"
                        class="form-control"
                    >
                    <div class="field-help">Porcentaje de incremento anual del canon (opcional)</div>
                </div>
            </div>
        </fieldset>

        <!-- Form Actions -->
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                🏠 Registrar Arriendo
            </button>
            <button type="reset" class="btn btn-secondary">
                🔄 Limpiar Formulario
            </button>
            <a href="?module=rentals" class="btn btn-secondary">
                ❌ Cancelar
            </a>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="info-message">
    <p><strong>Nota de Desarrollo:</strong> Este formulario simula el registro de arriendos. En la versión completa se integrará con la base de datos y se generarán automáticamente los recordatorios de pago.</p>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fechaInicio = document.getElementById('fecha_inicio');
    const fechaFin = document.getElementById('fecha_fin');
    const canonInput = document.getElementById('canon_mensual');
    const depositoInput = document.getElementById('deposito');
    const durationInfo = document.getElementById('duration-info');

    // Auto-calculate end date (1 year from start)
    if (fechaInicio) {
        fechaInicio.addEventListener('change', function() {
            if (!fechaFin.value) {
                const startDate = new Date(this.value);
                const endDate = new Date(startDate);
                endDate.setFullYear(endDate.getFullYear() + 1);

                const year = endDate.getFullYear();
                const month = String(endDate.getMonth() + 1).padStart(2, '0');
                const day = String(endDate.getDate()).padStart(2, '0');

                fechaFin.value = `${year}-${month}-${day}`;
                updateDuration();
            }
        });
    }

    // Calculate duration
    function updateDuration() {
        if (fechaInicio.value && fechaFin.value) {
            const start = new Date(fechaInicio.value);
            const end = new Date(fechaFin.value);
            const diffTime = Math.abs(end - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            const diffMonths = Math.round(diffDays / 30);

            if (durationInfo) {
                durationInfo.textContent = `Duración: ${diffDays} días (≈${diffMonths} meses)`;
                durationInfo.style.color = 'var(--primary-color)';
                durationInfo.style.fontWeight = '500';
            }
        }
    }

    if (fechaFin) {
        fechaFin.addEventListener('change', updateDuration);
    }

    // Auto-suggest deposit based on canon
    if (canonInput && depositoInput) {
        canonInput.addEventListener('blur', function() {
            const canon = parseFloat(this.value.replace(/[^\d]/g, '')) || 0;

            if (canon > 0 && !depositoInput.value) {
                // Suggest 2 months as deposit
                const suggestedDeposit = canon * 2;
                depositoInput.value = suggestedDeposit;

                // Show suggestion message
                const suggestion = document.createElement('div');
                suggestion.className = 'suggestion-message';
                suggestion.textContent = `Sugerencia: 2 meses de canon (${suggestedDeposit.toLocaleString('es-CO', {style: 'currency', currency: 'COP'})})`;
                suggestion.style.cssText = 'color: var(--primary-color); font-size: var(--font-size-sm); margin-top: var(--spacing-xs);';

                const existingSuggestion = depositoInput.parentNode.querySelector('.suggestion-message');
                if (existingSuggestion) {
                    existingSuggestion.remove();
                }

                depositoInput.parentNode.appendChild(suggestion);

                setTimeout(() => {
                    suggestion.remove();
                }, 5000);
            }
        });
    }

    // Format currency inputs
    function formatCurrencyInput(input) {
        if (!input) return;

        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^\d]/g, '');
            e.target.setAttribute('data-raw-value', value);
        });

        input.addEventListener('blur', function(e) {
            const value = e.target.value.replace(/[^\d]/g, '');
            if (value) {
                e.target.value = parseInt(value).toLocaleString('es-CO');
            }
        });

        input.addEventListener('focus', function(e) {
            const rawValue = e.target.getAttribute('data-raw-value');
            if (rawValue) {
                e.target.value = rawValue;
            }
        });
    }

    formatCurrencyInput(canonInput);
    formatCurrencyInput(depositoInput);

    // Calculate total contract value
    function updateContractInfo() {
        if (fechaInicio.value && fechaFin.value && canonInput.value) {
            const start = new Date(fechaInicio.value);
            const end = new Date(fechaFin.value);
            const months = Math.round((end - start) / (1000 * 60 * 60 * 24 * 30));
            const canon = parseFloat(canonInput.value.replace(/[^\d]/g, '')) || 0;
            const totalValue = canon * months;

            const infoElement = document.getElementById('contract-total');
            if (infoElement && canon > 0) {
                infoElement.textContent = `Valor total del contrato: ${totalValue.toLocaleString('es-CO', {style: 'currency', currency: 'COP'})} (${months} meses)`;
                infoElement.style.display = 'block';
            }
        }
    }

    if (canonInput) {
        canonInput.addEventListener('input', updateContractInfo);
    }

    // Initialize calculations
    updateDuration();
    updateContractInfo();
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

.rental-summary {
    background: rgba(255, 255, 255, 0.7);
    padding: var(--spacing-md);
    border-radius: var(--border-radius);
    margin: var(--spacing-md) 0;
}

.rental-summary p {
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

#contract-total {
    display: none;
    color: var(--accent-color);
    font-weight: 600;
    margin-top: var(--spacing-sm);
    padding: var(--spacing-sm);
    background: #fff3cd;
    border-radius: var(--border-radius);
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