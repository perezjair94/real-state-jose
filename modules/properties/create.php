<?php
/**
 * Create Property - Real Estate Management System
 * Form to add new properties with validation
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input data
    $formData = array_map('sanitizeInput', $_POST);

    // Validation rules
    $validator = new Validator();
    $rules = [
        'tipo_inmueble' => ['required'],
        'direccion' => ['required', 'min_length:10'],
        'ciudad' => ['required'],
        'precio' => ['required', 'numeric', 'min_value:1'],
        'descripcion' => ['max_length:1000'],
        'area_construida' => ['numeric'],
        'area_lote' => ['numeric'],
        'habitaciones' => ['integer'],
        'banos' => ['integer']
    ];

    if ($validator->validate($formData, $rules)) {
        try {
            $db = new Database();
            $pdo = $db->getConnection();

            // Prepare data for insertion
            $insertData = [
                'tipo_inmueble' => $formData['tipo_inmueble'],
                'direccion' => $formData['direccion'],
                'ciudad' => $formData['ciudad'],
                'precio' => $formData['precio'],
                'descripcion' => $formData['descripcion'] ?: null,
                'area_construida' => !empty($formData['area_construida']) ? $formData['area_construida'] : null,
                'area_lote' => !empty($formData['area_lote']) ? $formData['area_lote'] : null,
                'habitaciones' => !empty($formData['habitaciones']) ? (int)$formData['habitaciones'] : 0,
                'banos' => !empty($formData['banos']) ? (int)$formData['banos'] : 0,
                'garaje' => isset($formData['garaje']) ? 1 : 0,
                'estado' => 'Disponible'
            ];

            // Handle photo uploads with enhanced error logging
            $photos = [];
            $uploadErrors = [];

            if (isset($_FILES['fotos']) && !empty($_FILES['fotos']['name'][0])) {
                // Verify upload directory exists and is writable
                if (!is_dir(UPLOAD_PATH_PROPERTIES)) {
                    mkdir(UPLOAD_PATH_PROPERTIES, 0777, true);
                }

                if (!is_writable(UPLOAD_PATH_PROPERTIES)) {
                    $uploadErrors[] = "El directorio de uploads no tiene permisos de escritura";
                    error_log("Upload directory not writable: " . UPLOAD_PATH_PROPERTIES);
                }

                foreach ($_FILES['fotos']['name'] as $index => $filename) {
                    if (!empty($filename)) {
                        $file = [
                            'name' => $_FILES['fotos']['name'][$index],
                            'type' => $_FILES['fotos']['type'][$index],
                            'tmp_name' => $_FILES['fotos']['tmp_name'][$index],
                            'error' => $_FILES['fotos']['error'][$index],
                            'size' => $_FILES['fotos']['size'][$index]
                        ];

                        $validation = validateUploadedFile($file, ALLOWED_IMAGE_TYPES);
                        if ($validation['valid']) {
                            $newFilename = generateUniqueFilename($filename);
                            $uploadPath = UPLOAD_PATH_PROPERTIES . $newFilename;

                            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                                $photos[] = $newFilename;
                                error_log("File uploaded successfully: " . $uploadPath);
                            } else {
                                $uploadErrors[] = "Error al subir archivo: " . $filename;
                                error_log("Failed to move uploaded file: " . $filename . " to " . $uploadPath);
                            }
                        } else {
                            $uploadErrors[] = $validation['error'] . " (" . $filename . ")";
                            error_log("File validation failed for: " . $filename . " - " . $validation['error']);
                        }
                    }
                }
            }

            // Always set fotos field, even if empty (as NULL or empty JSON array)
            $insertData['fotos'] = !empty($photos) ? json_encode($photos) : null;

            // Log upload results for debugging
            if (!empty($uploadErrors)) {
                error_log("Upload errors: " . implode(", ", $uploadErrors));
            }
            if (!empty($photos)) {
                error_log("Successfully uploaded photos: " . json_encode($photos));
            }

            // Insert into database
            $sql = "INSERT INTO inmueble (tipo_inmueble, direccion, ciudad, precio, descripcion,
                    area_construida, area_lote, habitaciones, banos, garaje, fotos, estado)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                $insertData['tipo_inmueble'],
                $insertData['direccion'],
                $insertData['ciudad'],
                $insertData['precio'],
                $insertData['descripcion'],
                $insertData['area_construida'],
                $insertData['area_lote'],
                $insertData['habitaciones'],
                $insertData['banos'],
                $insertData['garaje'],
                $insertData['fotos'] ?? null,
                $insertData['estado']
            ]);

            if ($result) {
                $propertyId = $pdo->lastInsertId();
                $formattedId = generateFormattedId('INM', $propertyId);

                redirectWithMessage(
                    '?module=properties&action=view&id=' . $propertyId,
                    "Propiedad {$formattedId} creada exitosamente",
                    'success'
                );
            } else {
                throw new Exception("Error al insertar en la base de datos");
            }

        } catch (PDOException $e) {
            error_log("Error creating property: " . $e->getMessage());
            $errors['general'] = "Error al crear la propiedad. Intente nuevamente.";
        } catch (Exception $e) {
            error_log("General error creating property: " . $e->getMessage());
            $errors['general'] = $e->getMessage();
        }
    } else {
        $errors = $validator->getErrors();
    }
}
?>

<div class="module-header">
    <h2>Agregar Nueva Propiedad</h2>
    <p class="module-description">
        Complete el formulario para registrar una nueva propiedad en el sistema.
    </p>
</div>

<!-- Navigation Buttons -->
<div class="action-buttons">
    <a href="?module=properties" class="btn btn-secondary">
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

<!-- Property Creation Form -->
<div class="card">
    <h3>Información de la Propiedad</h3>

    <form method="POST" enctype="multipart/form-data" data-validate id="property-form" novalidate>
        <!-- CSRF Token -->
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

        <!-- Property Type and Price -->
        <div class="form-row">
            <div class="form-group">
                <label for="tipo_inmueble" class="required">Tipo de Inmueble:</label>
                <select name="tipo_inmueble" id="tipo_inmueble" required data-validation="required">
                    <option value="">Seleccione el tipo...</option>
                    <?php foreach (PROPERTY_TYPES as $key => $label): ?>
                        <option value="<?= $key ?>" <?= ($formData['tipo_inmueble'] ?? '') === $key ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['tipo_inmueble'])): ?>
                    <div class="error-message"><?= htmlspecialchars($errors['tipo_inmueble']) ?></div>
                <?php endif; ?>
                <?php if (SHOW_EDUCATIONAL_COMMENTS): ?>
                    <div class="field-help">Seleccione el tipo que mejor describe la propiedad</div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="precio" class="required">Precio (COP):</label>
                <input
                    type="number"
                    name="precio"
                    id="precio"
                    placeholder="0"
                    required
                    min="1"
                    step="1000"
                    data-validation="required|numeric|min_value:1"
                    value="<?= htmlspecialchars($formData['precio'] ?? '') ?>"
                >
                <?php if (!empty($errors['precio'])): ?>
                    <div class="error-message"><?= htmlspecialchars($errors['precio']) ?></div>
                <?php endif; ?>
                <?php if (SHOW_EDUCATIONAL_COMMENTS): ?>
                    <div class="field-help">Precio en pesos colombianos</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Address Information -->
        <div class="form-group">
            <label for="direccion" class="required">Dirección Completa:</label>
            <input
                type="text"
                name="direccion"
                id="direccion"
                placeholder="Ej: Calle 123 #45-67, Barrio El Poblado"
                required
                data-validation="required|min_length:10"
                value="<?= htmlspecialchars($formData['direccion'] ?? '') ?>"
            >
            <?php if (!empty($errors['direccion'])): ?>
                <div class="error-message"><?= htmlspecialchars($errors['direccion']) ?></div>
            <?php endif; ?>
            <?php if (SHOW_EDUCATIONAL_COMMENTS): ?>
                <div class="field-help">Incluya calle, número, barrio y puntos de referencia</div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="ciudad" class="required">Ciudad:</label>
            <select name="ciudad" id="ciudad" required data-validation="required">
                <option value="">Seleccione la ciudad...</option>
                <?php foreach (CITIES as $key => $label): ?>
                    <option value="<?= $key ?>" <?= ($formData['ciudad'] ?? '') === $key ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['ciudad'])): ?>
                <div class="error-message"><?= htmlspecialchars($errors['ciudad']) ?></div>
            <?php endif; ?>
        </div>

        <!-- Property Details -->
        <fieldset class="form-section">
            <legend>Características de la Propiedad</legend>

            <div class="form-row">
                <div class="form-group">
                    <label for="area_construida">Área Construida (m²):</label>
                    <input
                        type="number"
                        name="area_construida"
                        id="area_construida"
                        placeholder="0.00"
                        min="0"
                        step="0.01"
                        data-validation="numeric"
                        value="<?= htmlspecialchars($formData['area_construida'] ?? '') ?>"
                    >
                    <?php if (!empty($errors['area_construida'])): ?>
                        <div class="error-message"><?= htmlspecialchars($errors['area_construida']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="area_lote">Área del Lote (m²):</label>
                    <input
                        type="number"
                        name="area_lote"
                        id="area_lote"
                        placeholder="0.00"
                        min="0"
                        step="0.01"
                        data-validation="numeric"
                        value="<?= htmlspecialchars($formData['area_lote'] ?? '') ?>"
                    >
                    <?php if (!empty($errors['area_lote'])): ?>
                        <div class="error-message"><?= htmlspecialchars($errors['area_lote']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="habitaciones">Número de Habitaciones:</label>
                    <input
                        type="number"
                        name="habitaciones"
                        id="habitaciones"
                        placeholder="0"
                        min="0"
                        max="20"
                        data-validation="integer"
                        value="<?= htmlspecialchars($formData['habitaciones'] ?? '') ?>"
                    >
                    <?php if (!empty($errors['habitaciones'])): ?>
                        <div class="error-message"><?= htmlspecialchars($errors['habitaciones']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="banos">Número de Baños:</label>
                    <input
                        type="number"
                        name="banos"
                        id="banos"
                        placeholder="0"
                        min="0"
                        max="10"
                        data-validation="integer"
                        value="<?= htmlspecialchars($formData['banos'] ?? '') ?>"
                    >
                    <?php if (!empty($errors['banos'])): ?>
                        <div class="error-message"><?= htmlspecialchars($errors['banos']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input
                        type="checkbox"
                        name="garaje"
                        id="garaje"
                        value="1"
                        <?= isset($formData['garaje']) ? 'checked' : '' ?>
                    >
                    La propiedad cuenta con garaje
                </label>
            </div>
        </fieldset>

        <!-- Description -->
        <div class="form-group">
            <label for="descripcion">Descripción Detallada:</label>
            <textarea
                name="descripcion"
                id="descripcion"
                placeholder="Describa las características especiales, acabados, ubicación, etc."
                data-validation="max_length:1000"
                maxlength="1000"
            ><?= htmlspecialchars($formData['descripcion'] ?? '') ?></textarea>
            <?php if (!empty($errors['descripcion'])): ?>
                <div class="error-message"><?= htmlspecialchars($errors['descripcion']) ?></div>
            <?php endif; ?>
            <div class="field-help">Máximo 1000 caracteres. Incluya detalles que hagan atractiva la propiedad.</div>
        </div>

        <!-- Photo Upload -->
        <div class="form-group">
            <label for="fotos">Fotografías de la Propiedad:</label>
            <div class="file-upload">
                <input
                    type="file"
                    name="fotos[]"
                    id="fotos"
                    multiple
                    accept="image/*"
                    data-max-files="10"
                >
                <label for="fotos" class="file-upload-label">
                    <span class="upload-icon">📷</span>
                    <span class="upload-text">
                        Haga clic para seleccionar fotos o arrástrelas aquí<br>
                        <small>Máximo 10 fotos, formatos JPG, PNG, GIF, WebP</small>
                    </span>
                </label>
            </div>
            <div id="photo-preview" class="photo-preview"></div>
            <?php if (SHOW_EDUCATIONAL_COMMENTS): ?>
                <div class="field-help">Las fotos ayudan a los clientes a conocer mejor la propiedad</div>
            <?php endif; ?>
        </div>

        <!-- Form Actions -->
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                💾 Crear Propiedad
            </button>
            <button type="reset" class="btn btn-secondary">
                🔄 Limpiar Formulario
            </button>
            <a href="?module=properties" class="btn btn-secondary">
                ❌ Cancelar
            </a>
        </div>
    </form>
</div>

<!-- Educational JavaScript Section -->
<script>
/**
 * Educational Note: Form enhancement and validation
 * This JavaScript provides real-time feedback and file upload preview
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize form validation
    const form = document.getElementById('property-form');
    if (form) {
        new FormValidator('property-form');
    }

    // File upload preview
    const fileInput = document.getElementById('fotos');
    const previewContainer = document.getElementById('photo-preview');

    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            previewContainer.innerHTML = '';

            const files = Array.from(e.target.files);
            const maxFiles = parseInt(fileInput.dataset.maxFiles) || 10;

            if (files.length > maxFiles) {
                alert(`Máximo ${maxFiles} archivos permitidos`);
                e.target.value = '';
                return;
            }

            files.forEach((file, index) => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const previewDiv = document.createElement('div');
                        previewDiv.className = 'photo-preview-item';
                        previewDiv.innerHTML = `
                            <img src="${e.target.result}" alt="Preview ${index + 1}">
                            <div class="photo-info">
                                <span class="photo-name">${file.name}</span>
                                <span class="photo-size">${formatBytes(file.size)}</span>
                            </div>
                        `;
                        previewContainer.appendChild(previewDiv);
                    };
                    reader.readAsDataURL(file);
                }
            });
        });

        // Drag and drop functionality
        const uploadLabel = fileInput.nextElementSibling;
        if (uploadLabel) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                uploadLabel.addEventListener(eventName, preventDefaults, false);
            });

            ['dragenter', 'dragover'].forEach(eventName => {
                uploadLabel.addEventListener(eventName, () => {
                    uploadLabel.parentElement.classList.add('dragover');
                }, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                uploadLabel.addEventListener(eventName, () => {
                    uploadLabel.parentElement.classList.remove('dragover');
                }, false);
            });

            uploadLabel.addEventListener('drop', function(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                fileInput.files = files;
                fileInput.dispatchEvent(new Event('change'));
            }, false);
        }
    }

    // Format currency input
    const priceInput = document.getElementById('precio');
    if (priceInput) {
        priceInput.addEventListener('input', function(e) {
            // Remove non-numeric characters except decimal point
            let value = e.target.value.replace(/[^\d]/g, '');
            if (value) {
                // Format with thousands separator
                e.target.value = parseInt(value).toLocaleString('es-CO');
            }
        });

        priceInput.addEventListener('focus', function(e) {
            // Remove formatting for editing
            e.target.value = e.target.value.replace(/[^\d]/g, '');
        });
    }

    // Auto-suggest cities based on input
    const ciudadSelect = document.getElementById('ciudad');
    if (ciudadSelect) {
        // Add search functionality to select
        ciudadSelect.addEventListener('focus', function() {
            this.size = Math.min(Object.keys(<?= json_encode(CITIES) ?>).length, 8);
        });

        ciudadSelect.addEventListener('blur', function() {
            this.size = 1;
        });
    }
});

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

function formatBytes(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Educational: Form auto-save to localStorage (optional enhancement)
if (typeof Storage !== "undefined") {
    const form = document.getElementById('property-form');
    const formData = new FormData(form);

    // Save form data every 30 seconds
    setInterval(() => {
        if (form.querySelector('input:focus, textarea:focus, select:focus')) {
            const currentData = new FormData(form);
            const dataObj = {};
            for (let [key, value] of currentData.entries()) {
                if (key !== 'csrf_token' && key !== 'fotos[]') {
                    dataObj[key] = value;
                }
            }
            localStorage.setItem('property-form-draft', JSON.stringify(dataObj));
        }
    }, 30000);
}
</script>

<style>
/* Additional styles for create form */
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
}

.checkbox-label input[type="checkbox"] {
    width: auto;
    margin: 0;
}

.file-upload-label {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--spacing-sm);
    min-height: 120px;
    justify-content: center;
}

.upload-icon {
    font-size: 2rem;
}

.upload-text {
    text-align: center;
    color: var(--text-secondary);
}

.photo-preview {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: var(--spacing-sm);
    margin-top: var(--spacing-md);
}

.photo-preview-item {
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    overflow: hidden;
    background: var(--bg-primary);
}

.photo-preview-item img {
    width: 100%;
    height: 100px;
    object-fit: cover;
}

.photo-info {
    padding: var(--spacing-xs);
    font-size: var(--font-size-xs);
}

.photo-name {
    display: block;
    font-weight: 500;
    margin-bottom: 2px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.photo-size {
    color: var(--text-secondary);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .form-actions {
        flex-direction: column;
    }

    .form-actions .btn {
        width: 100%;
        margin-right: 0;
    }

    .photo-preview {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    }
}
</style>