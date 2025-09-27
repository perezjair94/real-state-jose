<?php
/**
 * Create Agent - Real Estate Management System
 * Form to add new agents
 * Educational PHP/MySQL Project
 */

// Prevent direct access
if (!defined('APP_ACCESS')) {
    die('Direct access not permitted');
}

// Initialize variables
$errors = [];
$formData = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input data
    $formData = array_map('sanitizeInput', $_POST);

    // Basic validation
    if (empty($formData['nombre'])) {
        $errors['nombre'] = 'El nombre es obligatorio';
    }
    if (empty($formData['correo'])) {
        $errors['correo'] = 'El correo es obligatorio';
    } elseif (!filter_var($formData['correo'], FILTER_VALIDATE_EMAIL)) {
        $errors['correo'] = 'El correo no tiene un formato válido';
    }
    if (empty($formData['telefono'])) {
        $errors['telefono'] = 'El teléfono es obligatorio';
    }

    if (empty($errors)) {
        try {
            $db = new Database();
            $pdo = $db->getConnection();

            // Check if email already exists
            $checkSql = "SELECT id_agente FROM agente WHERE correo = ?";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([$formData['correo']]);

            if ($checkStmt->fetchColumn()) {
                $errors['correo'] = 'Ya existe un agente con este correo electrónico';
            } else {
                // Insert new agent
                $sql = "INSERT INTO agente (nombre, correo, telefono, asesor, activo) VALUES (?, ?, ?, ?, 1)";
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([
                    $formData['nombre'],
                    $formData['correo'],
                    $formData['telefono'],
                    $formData['asesor'] ?: null
                ]);

                if ($result) {
                    $agentId = $pdo->lastInsertId();
                    redirectWithMessage(
                        '?module=agents',
                        "Agente AGE" . str_pad($agentId, 3, '0', STR_PAD_LEFT) . " creado exitosamente",
                        'success'
                    );
                } else {
                    throw new Exception("Error al insertar en la base de datos");
                }
            }

        } catch (PDOException $e) {
            error_log("Error creating agent: " . $e->getMessage());
            $errors['general'] = "Error al crear el agente. Intente nuevamente.";
        } catch (Exception $e) {
            error_log("General error creating agent: " . $e->getMessage());
            $errors['general'] = $e->getMessage();
        }
    }
}
?>

<div class="module-header">
    <h2>Agregar Nuevo Agente</h2>
    <p class="module-description">
        Complete el formulario para registrar un nuevo agente inmobiliario.
    </p>
</div>

<!-- Navigation Buttons -->
<div class="action-buttons">
    <a href="?module=agents" class="btn btn-secondary">
        ← Volver a la Lista
    </a>
</div>


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

<!-- Agent Creation Form -->
<div class="card">
    <h3>Información del Agente</h3>

    <form method="POST" id="agent-form">
        <!-- CSRF Token -->
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

        <!-- Agent ID (Auto-generated) -->
        <div class="form-group">
            <label>ID Agente:</label>
            <input type="text" value="Se generará automáticamente" readonly class="form-control">
            <div class="field-help">El ID se asignará automáticamente al crear el agente</div>
        </div>

        <!-- Basic Information -->
        <div class="form-row">
            <div class="form-group">
                <label for="nombre" class="required">Nombre Completo:</label>
                <input
                    type="text"
                    name="nombre"
                    id="nombre"
                    placeholder="Nombre y apellidos del agente"
                    required
                    value="<?= htmlspecialchars($formData['nombre'] ?? '') ?>"
                    class="form-control"
                >
                <?php if (!empty($errors['nombre'])): ?>
                    <div class="error-message"><?= htmlspecialchars($errors['nombre']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="correo" class="required">Correo Electrónico:</label>
                <input
                    type="email"
                    name="correo"
                    id="correo"
                    placeholder="agente@inmobiliaria.com"
                    required
                    value="<?= htmlspecialchars($formData['correo'] ?? '') ?>"
                    class="form-control"
                >
                <?php if (!empty($errors['correo'])): ?>
                    <div class="error-message"><?= htmlspecialchars($errors['correo']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="telefono" class="required">Teléfono:</label>
                <input
                    type="tel"
                    name="telefono"
                    id="telefono"
                    placeholder="300-123-4567"
                    required
                    value="<?= htmlspecialchars($formData['telefono'] ?? '') ?>"
                    class="form-control"
                >
                <?php if (!empty($errors['telefono'])): ?>
                    <div class="error-message"><?= htmlspecialchars($errors['telefono']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="asesor">Asesor/Supervisor:</label>
                <input
                    type="text"
                    name="asesor"
                    id="asesor"
                    placeholder="Nombre del supervisor"
                    value="<?= htmlspecialchars($formData['asesor'] ?? '') ?>"
                    class="form-control"
                >
                <div class="field-help">Opcional: Nombre del supervisor o asesor responsable</div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                💾 Crear Agente
            </button>
            <button type="reset" class="btn btn-secondary">
                🔄 Limpiar Formulario
            </button>
            <a href="?module=agents" class="btn btn-secondary">
                ❌ Cancelar
            </a>
        </div>
    </form>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    // Format phone input
    const phoneInput = document.getElementById('telefono');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 3) {
                value = value.substring(0, 3) + '-' + value.substring(3, 6) + '-' + value.substring(6, 10);
            }
            e.target.value = value;
        });
    }

    // Email validation
    const emailInput = document.getElementById('correo');
    if (emailInput) {
        emailInput.addEventListener('blur', function(e) {
            const email = e.target.value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (email && !emailRegex.test(email)) {
                e.target.style.borderColor = '#dc3545';
            } else {
                e.target.style.borderColor = '';
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

.info-message {
    background-color: #e2e3e5;
    border: 1px solid #d6d8db;
    color: #383d41;
    padding: var(--spacing-md);
    border-radius: var(--border-radius);
    margin-top: var(--spacing-lg);
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