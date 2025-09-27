/**
 * Client-Side Validation - Real Estate Management System
 * Educational JavaScript for form validation and user experience
 * Follows 2024 best practices for accessibility and performance
 */

class FormValidator {
    constructor(formId) {
        this.form = document.getElementById(formId);
        if (!this.form) {
            console.warn(`Form with ID "${formId}" not found`);
            return;
        }

        this.errors = {};
        this.rules = {};
        this.init();
    }

    /**
     * Initialize validation system
     */
    init() {
        // Parse validation rules from data attributes
        this.parseValidationRules();

        // Bind event listeners
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));

        // Real-time validation on field changes
        this.form.querySelectorAll('input, select, textarea').forEach(field => {
            // Validate on blur for immediate feedback
            field.addEventListener('blur', () => this.validateField(field));

            // Clear errors on input for better UX
            field.addEventListener('input', () => this.clearFieldError(field));

            // Special handling for specific field types
            this.setupFieldSpecificHandlers(field);
        });

        // Educational comment: Form validation prevents invalid data submission
        this.addEducationalHelpers();
    }

    /**
     * Parse validation rules from data-validation attributes
     */
    parseValidationRules() {
        this.form.querySelectorAll('[data-validation]').forEach(field => {
            const rules = field.dataset.validation.split('|');
            this.rules[field.name] = rules;
        });
    }

    /**
     * Setup field-specific event handlers
     */
    setupFieldSpecificHandlers(field) {
        switch (field.type) {
            case 'email':
                field.addEventListener('input', () => this.formatEmail(field));
                break;

            case 'tel':
                field.addEventListener('input', () => this.formatPhone(field));
                break;

            case 'number':
                field.addEventListener('input', () => this.validateNumericInput(field));
                break;

            default:
                if (field.name === 'precio') {
                    this.setupCurrencyFormatting(field);
                }
                break;
        }
    }

    /**
     * Setup currency formatting for price fields
     */
    setupCurrencyFormatting(field) {
        field.addEventListener('blur', function() {
            let value = this.value.replace(/[^\d]/g, '');
            if (value) {
                this.value = parseInt(value).toLocaleString('es-CO');
            }
        });

        field.addEventListener('focus', function() {
            this.value = this.value.replace(/[^\d]/g, '');
        });
    }

    /**
     * Format email input (lowercase, trim)
     */
    formatEmail(field) {
        field.value = field.value.toLowerCase().trim();
    }

    /**
     * Format phone number input
     */
    formatPhone(field) {
        let value = field.value.replace(/\D/g, '');

        // Colombian phone number formatting
        if (value.length <= 10) {
            if (value.length >= 7) {
                value = value.replace(/(\d{3})(\d{3})(\d{4})/, '$1-$2-$3');
            } else if (value.length >= 4) {
                value = value.replace(/(\d{3})(\d{1,3})/, '$1-$2');
            }
        }

        field.value = value;
    }

    /**
     * Validate numeric input in real-time
     */
    validateNumericInput(field) {
        const value = field.value;

        // Allow decimal numbers with up to 2 decimal places
        if (field.step && field.step !== "1") {
            field.value = value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');
        } else {
            field.value = value.replace(/[^0-9]/g, '');
        }

        // Validate min/max constraints
        if (field.min && parseFloat(field.value) < parseFloat(field.min)) {
            this.setFieldError(field, `El valor mínimo es ${field.min}`);
        } else if (field.max && parseFloat(field.value) > parseFloat(field.max)) {
            this.setFieldError(field, `El valor máximo es ${field.max}`);
        }
    }

    /**
     * Validate individual field
     */
    validateField(field) {
        const value = field.value.trim();
        const rules = this.rules[field.name] || [];

        // Clear previous errors
        this.clearFieldError(field);

        // Apply validation rules
        for (let rule of rules) {
            if (!this.applyRule(field, value, rule)) {
                break; // Stop on first error
            }
        }
    }

    /**
     * Apply specific validation rule
     */
    applyRule(field, value, rule) {
        const [ruleName, parameter] = rule.includes(':') ? rule.split(':') : [rule, null];

        switch (ruleName) {
            case 'required':
                if (!value) {
                    this.setFieldError(field, 'Este campo es obligatorio');
                    return false;
                }
                break;

            case 'email':
                if (value && !this.isValidEmail(value)) {
                    this.setFieldError(field, 'Ingrese un email válido');
                    return false;
                }
                break;

            case 'numeric':
                if (value && !this.isNumeric(value)) {
                    this.setFieldError(field, 'Ingrese solo números');
                    return false;
                }
                break;

            case 'integer':
                if (value && !Number.isInteger(parseFloat(value))) {
                    this.setFieldError(field, 'Ingrese un número entero');
                    return false;
                }
                break;

            case 'min_length':
                if (value && value.length < parseInt(parameter)) {
                    this.setFieldError(field, `Mínimo ${parameter} caracteres`);
                    return false;
                }
                break;

            case 'max_length':
                if (value && value.length > parseInt(parameter)) {
                    this.setFieldError(field, `Máximo ${parameter} caracteres`);
                    return false;
                }
                break;

            case 'min_value':
                if (value && parseFloat(value) < parseFloat(parameter)) {
                    this.setFieldError(field, `El valor mínimo es ${parameter}`);
                    return false;
                }
                break;

            case 'max_value':
                if (value && parseFloat(value) > parseFloat(parameter)) {
                    this.setFieldError(field, `El valor máximo es ${parameter}`);
                    return false;
                }
                break;

            case 'phone':
                if (value && !this.isValidPhone(value)) {
                    this.setFieldError(field, 'Ingrese un teléfono válido');
                    return false;
                }
                break;

            case 'document':
                if (value && !this.isValidDocument(value)) {
                    this.setFieldError(field, 'Número de documento inválido');
                    return false;
                }
                break;

            case 'date':
                if (value && !this.isValidDate(value)) {
                    this.setFieldError(field, 'Ingrese una fecha válida');
                    return false;
                }
                break;

            case 'future_date':
                if (value && !this.isFutureDate(value)) {
                    this.setFieldError(field, 'La fecha debe ser futura');
                    return false;
                }
                break;

            case 'password':
                if (value && !this.isValidPassword(value)) {
                    this.setFieldError(field, 'La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula y un número');
                    return false;
                }
                break;

            case 'confirm_password':
                const passwordField = this.form.querySelector(`[name="${parameter}"]`);
                if (passwordField && value !== passwordField.value) {
                    this.setFieldError(field, 'Las contraseñas no coinciden');
                    return false;
                }
                break;
        }

        return true;
    }

    /**
     * Set field error display
     */
    setFieldError(field, message) {
        this.errors[field.name] = message;
        field.classList.add('error');
        field.setAttribute('aria-invalid', 'true');

        // Create or update error message element
        let errorElement = field.parentNode.querySelector('.error-message');
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'error-message';
            errorElement.setAttribute('role', 'alert');
            field.parentNode.appendChild(errorElement);
        }
        errorElement.textContent = message;

        // Accessibility: Set aria-describedby
        if (!field.getAttribute('aria-describedby')) {
            const errorId = `error-${field.name}-${Date.now()}`;
            errorElement.id = errorId;
            field.setAttribute('aria-describedby', errorId);
        }
    }

    /**
     * Clear field error display
     */
    clearFieldError(field) {
        delete this.errors[field.name];
        field.classList.remove('error');
        field.removeAttribute('aria-invalid');

        const errorElement = field.parentNode.querySelector('.error-message');
        if (errorElement) {
            errorElement.remove();
        }

        // Remove aria-describedby if it was for errors
        const describedBy = field.getAttribute('aria-describedby');
        if (describedBy && describedBy.startsWith('error-')) {
            field.removeAttribute('aria-describedby');
        }
    }

    /**
     * Handle form submission
     */
    handleSubmit(e) {
        // Clear previous errors
        this.errors = {};

        // Validate all fields
        this.form.querySelectorAll('input, select, textarea').forEach(field => {
            if (field.name && !field.disabled) {
                this.validateField(field);
            }
        });

        // Prevent submission if there are errors
        if (Object.keys(this.errors).length > 0) {
            e.preventDefault();
            this.showValidationSummary();
            this.focusFirstError();
            return false;
        }

        // Show loading state
        this.showLoadingState();
        return true;
    }

    /**
     * Show validation error summary
     */
    showValidationSummary() {
        const errorCount = Object.keys(this.errors).length;
        const summaryMessage = `Por favor corrija ${errorCount} error${errorCount > 1 ? 'es' : ''} antes de continuar.`;

        // Remove existing summary
        const existingSummary = this.form.querySelector('.validation-summary');
        if (existingSummary) {
            existingSummary.remove();
        }

        // Create summary element
        const summary = document.createElement('div');
        summary.className = 'alert alert-danger validation-summary';
        summary.setAttribute('role', 'alert');
        summary.innerHTML = `
            <h4>${summaryMessage}</h4>
            <ul>
                ${Object.entries(this.errors).map(([field, error]) =>
                    `<li>${this.getFieldLabel(field)}: ${error}</li>`
                ).join('')}
            </ul>
        `;

        // Insert at top of form
        this.form.insertBefore(summary, this.form.firstChild);

        // Scroll to summary
        summary.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    /**
     * Get user-friendly field label
     */
    getFieldLabel(fieldName) {
        const field = this.form.querySelector(`[name="${fieldName}"]`);
        if (field) {
            const label = this.form.querySelector(`label[for="${field.id}"]`);
            if (label) {
                return label.textContent.replace('*', '').trim();
            }
        }
        return fieldName;
    }

    /**
     * Focus first field with error
     */
    focusFirstError() {
        const firstErrorField = this.form.querySelector('.error');
        if (firstErrorField) {
            firstErrorField.focus();
            firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    /**
     * Show loading state during form submission
     */
    showLoadingState() {
        const submitButton = this.form.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="loading"></span> Procesando...';
        }
    }

    /**
     * Validation helper methods
     */
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    isNumeric(value) {
        return !isNaN(parseFloat(value)) && isFinite(value);
    }

    isValidPhone(phone) {
        // Colombian phone number validation
        const cleanPhone = phone.replace(/\D/g, '');
        return /^([0-9]{7}|[0-9]{10})$/.test(cleanPhone);
    }

    isValidDocument(document) {
        // Basic document validation: alphanumeric, 5-20 characters
        return /^[A-Za-z0-9]{5,20}$/.test(document);
    }

    isValidDate(dateString) {
        const date = new Date(dateString);
        return date instanceof Date && !isNaN(date);
    }

    isFutureDate(dateString) {
        const date = new Date(dateString);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        return date > today;
    }

    isValidPassword(password) {
        // At least 8 characters, one uppercase, one lowercase, one number
        const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/;
        return passwordRegex.test(password);
    }

    /**
     * Add educational helpers for development
     */
    addEducationalHelpers() {
        if (window.location.hostname === 'localhost' || window.location.hostname.includes('dev')) {
            // Add validation indicators in development
            this.form.querySelectorAll('[data-validation]').forEach(field => {
                if (!field.title) {
                    field.title = `Validación: ${field.dataset.validation}`;
                }
            });
        }
    }

    /**
     * Public method to manually validate form
     */
    validate() {
        this.form.dispatchEvent(new Event('submit'));
        return Object.keys(this.errors).length === 0;
    }

    /**
     * Public method to get current errors
     */
    getErrors() {
        return { ...this.errors };
    }

    /**
     * Public method to clear all errors
     */
    clearAllErrors() {
        this.form.querySelectorAll('input, select, textarea').forEach(field => {
            this.clearFieldError(field);
        });

        const summary = this.form.querySelector('.validation-summary');
        if (summary) {
            summary.remove();
        }
    }
}

/**
 * Auto-initialize validation for forms with data-validate attribute
 */
document.addEventListener('DOMContentLoaded', function() {
    // Auto-initialize forms with validation
    document.querySelectorAll('form[data-validate]').forEach(form => {
        if (form.id) {
            new FormValidator(form.id);
        } else {
            console.warn('Form with data-validate attribute must have an ID');
        }
    });

    // Global form enhancements
    enhanceAllForms();
});

/**
 * Enhance all forms with common functionality
 */
function enhanceAllForms() {
    // Prevent double submission
    document.querySelectorAll('form').forEach(form => {
        let isSubmitting = false;

        form.addEventListener('submit', function(e) {
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }
            isSubmitting = true;

            // Reset flag after a delay
            setTimeout(() => {
                isSubmitting = false;
            }, 3000);
        });
    });

    // Auto-resize textareas
    document.querySelectorAll('textarea').forEach(textarea => {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    });

    // Add character counter for text fields with maxlength
    document.querySelectorAll('input[maxlength], textarea[maxlength]').forEach(field => {
        const maxLength = parseInt(field.getAttribute('maxlength'));
        if (maxLength > 50) { // Only for longer fields
            const counter = document.createElement('div');
            counter.className = 'character-counter';
            counter.style.fontSize = '0.8em';
            counter.style.color = '#666';
            counter.style.textAlign = 'right';
            counter.style.marginTop = '5px';

            const updateCounter = () => {
                const remaining = maxLength - field.value.length;
                counter.textContent = `${field.value.length}/${maxLength} caracteres`;
                counter.style.color = remaining < 50 ? '#e74c3c' : '#666';
            };

            field.addEventListener('input', updateCounter);
            updateCounter();

            field.parentNode.appendChild(counter);
        }
    });
}

/**
 * Utility function to validate a single field programmatically
 */
function validateField(fieldId) {
    const field = document.getElementById(fieldId);
    if (field && field.form) {
        const formValidator = field.form._validator;
        if (formValidator) {
            formValidator.validateField(field);
            return !formValidator.errors[field.name];
        }
    }
    return true;
}

/**
 * Export for use in other scripts
 */
window.FormValidator = FormValidator;
window.validateField = validateField;