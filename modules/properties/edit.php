<?php
/**
 * Edit Property - Real Estate Management System
 * Form to edit existing properties with validation
 * Educational PHP/MySQL Project
 */

// Prevent direct access
if (!defined('APP_ACCESS')) {
    die('Direct access not permitted');
}

// Get property ID from URL
$propertyId = (int)($_GET['id'] ?? 0);

if ($propertyId <= 0) {
    redirectWithMessage('?module=properties', 'ID de propiedad inválido', 'error');
}

// Initialize variables
$errors = [];
$property = null;
$success = false;

// Get existing property data
try {
    $db = new Database();
    $pdo = $db->getConnection();

    $sql = "SELECT * FROM inmueble WHERE id_inmueble = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$propertyId]);
    $property = $stmt->fetch();

    if (!$property) {
        redirectWithMessage('?module=properties', 'Propiedad no encontrada', 'error');
    }

} catch (PDOException $e) {
    error_log("Error fetching property: " . $e->getMessage());
    redirectWithMessage('?module=properties', 'Error al cargar la propiedad', 'error');
}

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
        'banos' => ['integer'],
        'estado' => ['required']
    ];

    if ($validator->validate($formData, $rules)) {
        try {
            // Prepare data for update
            $updateData = [
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
                'estado' => $formData['estado']
            ];

            // Handle photo uploads (additional photos) with enhanced error logging
            $currentPhotos = !empty($property['fotos']) ? json_decode($property['fotos'], true) : [];
            $newPhotos = [];
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
                                $newPhotos[] = $newFilename;
                                error_log("File uploaded successfully (edit): " . $uploadPath);
                            } else {
                                $uploadErrors[] = "Error al subir archivo: " . $filename;
                                error_log("Failed to move uploaded file (edit): " . $filename . " to " . $uploadPath);
                            }
                        } else {
                            $uploadErrors[] = $validation['error'] . " (" . $filename . ")";
                            error_log("File validation failed (edit): " . $filename . " - " . $validation['error']);
                        }
                    }
                }
            }

            // Merge current and new photos
            $allPhotos = array_merge($currentPhotos, $newPhotos);
            if (!empty($allPhotos)) {
                $updateData['fotos'] = json_encode($allPhotos);
            } else {
                $updateData['fotos'] = null;
            }

            // Log upload results for debugging
            if (!empty($uploadErrors)) {
                error_log("Upload errors (edit): " . implode(", ", $uploadErrors));
            }
            if (!empty($newPhotos)) {
                error_log("Successfully uploaded new photos (edit): " . json_encode($newPhotos));
            }

            // Update in database
            $sql = "UPDATE inmueble SET
                    tipo_inmueble = ?, direccion = ?, ciudad = ?, precio = ?,
                    descripcion = ?, area_construida = ?, area_lote = ?,
                    habitaciones = ?, banos = ?, garaje = ?, estado = ?, fotos = ?,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE id_inmueble = ?";

            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                $updateData['tipo_inmueble'],
                $updateData['direccion'],
                $updateData['ciudad'],
                $updateData['precio'],
                $updateData['descripcion'],
                $updateData['area_construida'],
                $updateData['area_lote'],
                $updateData['habitaciones'],
                $updateData['banos'],
                $updateData['garaje'],
                $updateData['estado'],
                $updateData['fotos'] ?? $property['fotos'],
                $propertyId
            ]);

            if ($result) {
                $formattedId = generateFormattedId('INM', $propertyId);

                redirectWithMessage(
                    '?module=properties&action=view&id=' . $propertyId,
                    "Propiedad {$formattedId} actualizada exitosamente",
                    'success'
                );
            } else {
                throw new Exception("Error al actualizar en la base de datos");
            }

        } catch (PDOException $e) {
            error_log("Error updating property: " . $e->getMessage());
            $errors['general'] = "Error al actualizar la propiedad. Intente nuevamente.";
        } catch (Exception $e) {
            error_log("General error updating property: " . $e->getMessage());
            $errors['general'] = $e->getMessage();
        }
    } else {
        $errors = $validator->getErrors();
    }
} else {
    // Pre-populate form with existing data
    $formData = $property;
}

// Parse photos for display
$photos = [];
if (!empty($property['fotos'])) {
    $photos = json_decode($property['fotos'], true) ?: [];
}

$formattedId = generateFormattedId('INM', $property['id_inmueble']);
?>

<div class="module-header">
    <h2>Editar Propiedad <span class="property-id"><?= $formattedId ?></span></h2>
    <p class="module-description">
        Modifique la información de la propiedad y guarde los cambios.
    </p>
</div>

<!-- Navigation Buttons -->
<div class="action-buttons">
    <a href="?module=properties&action=view&id=<?= $propertyId ?>" class="btn btn-secondary">
        ← Volver a Ver Propiedad
    </a>
    <a href="?module=properties" class="btn btn-secondary">
        📋 Lista de Propiedades
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

<!-- Property Edit Form -->
<div class="card">
    <h3>Información de la Propiedad</h3>

    <form method="POST" enctype="multipart/form-data" data-validate id="property-edit-form" novalidate>
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
            </div>
        </div>

        <!-- Status -->
        <div class="form-group">
            <label for="estado" class="required">Estado de la Propiedad:</label>
            <select name="estado" id="estado" required data-validation="required">
                <?php foreach (PROPERTY_STATUS as $key => $label): ?>
                    <option value="<?= $key ?>" <?= ($formData['estado'] ?? '') === $key ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['estado'])): ?>
                <div class="error-message"><?= htmlspecialchars($errors['estado']) ?></div>
            <?php endif; ?>
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
        </div>

        <div class="form-group">
            <label for="ciudad" class="required">Ciudad:</label>
            <select name="ciudad" id="ciudad" required data-validation="required">
                <option value="Montería" selected>Montería</option>
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
                        <?= isset($formData['garaje']) && $formData['garaje'] ? 'checked' : '' ?>
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

        <!-- Current Photos Display -->
        <?php if (!empty($photos)): ?>
            <div class="form-group">
                <label>Fotografías Actuales:</label>
                <div class="current-photos">
                    <?php foreach ($photos as $index => $photo): ?>
                        <?php
                        // Determine the correct path for the image
                        // Check if it's a custom uploaded photo or a default image
                        if (strpos($photo, 'img/') === 0 || strpos($photo, 'casa') !== false) {
                            // Default image from img/ folder
                            $photoSrc = (strpos($photo, 'img/') === 0) ? $photo : 'img/' . $photo;
                        } else {
                            // Custom uploaded photo
                            $photoSrc = 'assets/uploads/properties/' . $photo;
                        }
                        ?>
                        <div class="current-photo-item" data-photo="<?= htmlspecialchars($photo) ?>">
                            <img
                                src="<?= htmlspecialchars($photoSrc) ?>"
                                alt="Foto <?= $index + 1 ?>"
                                onclick="viewPhoto('<?= htmlspecialchars($photo) ?>')"
                                onerror="this.src='img/casa1.jpeg'"
                            >
                            <button
                                type="button"
                                class="photo-delete-btn"
                                onclick="deletePhoto('<?= htmlspecialchars($photo) ?>', <?= $propertyId ?>)"
                                title="Eliminar foto"
                            >
                                ❌
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="field-help">Haga clic en una foto para verla en tamaño completo, o en ❌ para eliminarla.</div>
            </div>
        <?php endif; ?>

        <!-- Add New Photos -->
        <div class="form-group">
            <label for="fotos">Agregar Nuevas Fotografías:</label>
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
                        Haga clic para seleccionar fotos adicionales o arrástrelas aquí<br>
                        <small>Máximo 10 fotos nuevas, formatos JPG, PNG, GIF, WebP</small>
                    </span>
                </label>
            </div>
            <div id="photo-preview" class="photo-preview"></div>
            <div class="field-help">Las fotos nuevas se agregarán a las existentes.</div>
        </div>

        <!-- Form Actions -->
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                💾 Actualizar Propiedad
            </button>
            <button type="reset" class="btn btn-secondary" onclick="resetForm()">
                🔄 Deshacer Cambios
            </button>
            <a href="?module=properties&action=view&id=<?= $propertyId ?>" class="btn btn-secondary">
                ❌ Cancelar
            </a>
        </div>
    </form>
</div>

<!-- JavaScript for Enhanced Functionality -->
<script>
/**
 * Property edit form functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize form validation
    const form = document.getElementById('property-edit-form');
    if (form) {
        new FormValidator('property-edit-form');
    }

    // Store original form data for reset functionality
    window.originalFormData = new FormData(form);

    // File upload preview (same as create form)
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

        // Drag and drop functionality (same as create form)
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
        // Display formatted value next to input
        const displaySpan = document.createElement('span');
        displaySpan.className = 'price-display';
        displaySpan.style.cssText = 'margin-left: 10px; color: #27ae60; font-weight: 600;';
        priceInput.parentNode.insertBefore(displaySpan, priceInput.nextSibling);

        priceInput.addEventListener('input', function(e) {
            // Keep only numbers
            let value = e.target.value.replace(/[^\d]/g, '');
            e.target.value = value;

            // Show formatted version
            if (value) {
                displaySpan.textContent = '$ ' + parseInt(value).toLocaleString('es-CO');
            } else {
                displaySpan.textContent = '';
            }
        });

        // Trigger initial display
        if (priceInput.value) {
            priceInput.dispatchEvent(new Event('input'));
        }
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

function viewPhoto(filename) {
    // Determine the correct path for the image
    let photoPath;
    if (filename.indexOf('img/') === 0 || filename.indexOf('casa') !== -1) {
        photoPath = filename.indexOf('img/') === 0 ? filename : 'img/' + filename;
    } else {
        photoPath = 'assets/uploads/properties/' + filename;
    }

    const modal = document.createElement('div');
    modal.className = 'photo-modal';
    modal.innerHTML = `
        <div class="photo-modal-content">
            <button class="photo-modal-close" onclick="this.closest('.photo-modal').remove()">&times;</button>
            <img src="${photoPath}" alt="Foto de la propiedad" onerror="this.src='img/casa1.jpeg'">
        </div>
    `;

    document.body.appendChild(modal);

    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.remove();
        }
    });
}

async function deletePhoto(filename, propertyId) {
    if (!confirm('¿Está seguro de que desea eliminar esta foto?\n\nEsta acción no se puede deshacer.')) {
        return;
    }

    try {
        const response = await Ajax.properties.deletePhoto(propertyId, filename);

        if (response.success) {
            // Remove photo from display
            const photoElement = document.querySelector(`[data-photo="${filename}"]`);
            if (photoElement) {
                photoElement.remove();
            }

            App.showSuccessMessage('Foto eliminada correctamente');

            // If no photos left, hide the current photos section
            const currentPhotos = document.querySelector('.current-photos');
            if (currentPhotos && currentPhotos.children.length === 0) {
                currentPhotos.closest('.form-group').style.display = 'none';
            }
        } else {
            throw new Error(response.error || 'Error desconocido');
        }
    } catch (error) {
        console.error('Error deleting photo:', error);
        App.showErrorMessage('Error al eliminar la foto: ' + error.message);
    }
}

function resetForm() {
    if (confirm('¿Está seguro de que desea deshacer todos los cambios?')) {
        // Reset to original values
        const form = document.getElementById('property-edit-form');
        const originalData = window.originalFormData;

        if (form && originalData) {
            form.reset();

            // Manually set values for complex fields
            for (let [key, value] of originalData.entries()) {
                const field = form.querySelector(`[name="${key}"]`);
                if (field) {
                    if (field.type === 'checkbox') {
                        field.checked = value === '1';
                    } else if (field.tagName === 'SELECT') {
                        field.value = value;
                    } else if (field.type !== 'file') {
                        field.value = value;
                    }
                }
            }

            // Clear photo preview
            const previewContainer = document.getElementById('photo-preview');
            if (previewContainer) {
                previewContainer.innerHTML = '';
            }

            // Clear file input
            const fileInput = document.getElementById('fotos');
            if (fileInput) {
                fileInput.value = '';
            }

            App.showSuccessMessage('Formulario restaurado a los valores originales');
        }
    }
}

// Auto-save draft functionality (educational enhancement)
if (typeof Storage !== "undefined") {
    const form = document.getElementById('property-edit-form');

    // Save draft every 30 seconds
    setInterval(() => {
        if (form.querySelector('input:focus, textarea:focus, select:focus')) {
            const formData = new FormData(form);
            const draftData = {};

            for (let [key, value] of formData.entries()) {
                if (key !== 'csrf_token' && key !== 'fotos[]') {
                    draftData[key] = value;
                }
            }

            localStorage.setItem(`property-edit-draft-${<?= $propertyId ?>}`, JSON.stringify(draftData));

            // Show subtle indicator
            const saveIndicator = document.createElement('div');
            saveIndicator.textContent = '✓ Borrador guardado';
            saveIndicator.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #27ae60;
                color: white;
                padding: 8px 12px;
                border-radius: 4px;
                font-size: 12px;
                z-index: 1000;
                opacity: 0.8;
            `;

            document.body.appendChild(saveIndicator);

            setTimeout(() => {
                if (saveIndicator.parentNode) {
                    saveIndicator.remove();
                }
            }, 2000);
        }
    }, 30000);
}
</script>

<style>
/* Additional styles for edit form */
.current-photos {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: var(--spacing-sm);
    margin-bottom: var(--spacing-md);
}

.current-photo-item {
    position: relative;
    aspect-ratio: 1;
    border-radius: var(--border-radius);
    overflow: hidden;
    border: 2px solid var(--border-color);
    transition: border-color var(--transition-fast);
}

.current-photo-item:hover {
    border-color: var(--secondary-color);
}

.current-photo-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    cursor: pointer;
}

.photo-delete-btn {
    position: absolute;
    top: 4px;
    right: 4px;
    background: rgba(255, 255, 255, 0.9);
    border: none;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 12px;
    transition: all var(--transition-fast);
    opacity: 0;
}

.current-photo-item:hover .photo-delete-btn {
    opacity: 1;
}

.photo-delete-btn:hover {
    background: rgba(231, 76, 60, 0.9);
    color: white;
}

/* Status change highlighting */
#estado {
    font-weight: 600;
}

#estado option[value="Vendido"],
#estado option[value="Arrendado"] {
    background-color: #fff3cd;
}

#estado option[value="Reservado"] {
    background-color: #f8d7da;
}

#estado option[value="Disponible"] {
    background-color: #d4edda;
}

/* Form modification indicators */
.form-group.modified label::after {
    content: " (modificado)";
    color: var(--secondary-color);
    font-size: 0.8em;
    font-weight: normal;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .current-photos {
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    }

    .form-actions {
        flex-direction: column;
    }

    .form-actions .btn {
        width: 100%;
        margin-right: 0;
    }
}
</style>