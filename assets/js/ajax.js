/**
 * AJAX Utilities - Real Estate Management System
 * Educational JavaScript for AJAX operations
 * Follows 2024 best practices for API communication
 */

/**
 * AJAX Manager Class
 * Handles all AJAX communications with the server
 */
class AjaxManager {
    constructor() {
        this.baseUrl = window.location.origin + window.location.pathname;
        this.defaultHeaders = {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        };
        this.timeout = 30000; // 30 seconds
        this.retryAttempts = 3;
        this.retryDelay = 1000; // 1 second
    }

    /**
     * Make AJAX request with error handling and retries
     */
    async request(url, options = {}) {
        const config = {
            method: 'GET',
            headers: { ...this.defaultHeaders },
            timeout: this.timeout,
            ...options
        };

        // Add CSRF token for non-GET requests
        if (config.method !== 'GET' && window.csrfToken) {
            if (config.body instanceof FormData) {
                config.body.append('csrf_token', window.csrfToken);
                config.body.append('ajax', 'true');
                // Remove Content-Type for FormData (browser sets it with boundary)
                delete config.headers['Content-Type'];
            } else {
                config.headers['X-CSRF-Token'] = window.csrfToken;

                if (typeof config.body === 'object' && config.body !== null) {
                    config.body = JSON.stringify({
                        ...config.body,
                        csrf_token: window.csrfToken,
                        ajax: 'true'
                    });
                }
            }
        }

        let lastError;

        for (let attempt = 1; attempt <= this.retryAttempts; attempt++) {
            try {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), config.timeout);

                const response = await fetch(url, {
                    ...config,
                    signal: controller.signal
                });

                clearTimeout(timeoutId);

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                // Get response text first to handle potential JSON parse errors
                const text = await response.text();

                // Try to parse as JSON
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('JSON Parse Error. Server response:', text.substring(0, 500));
                    throw new Error('El servidor devolvió una respuesta inválida. Revisa la consola para más detalles.');
                }

                if (data.success === false) {
                    throw new Error(data.message || data.error || 'Server returned an error');
                }

                return data;

            } catch (error) {
                lastError = error;

                // Don't retry on certain errors
                if (error.name === 'AbortError' ||
                    error.message.includes('403') ||
                    error.message.includes('401') ||
                    attempt === this.retryAttempts) {
                    break;
                }

                // Wait before retry
                if (attempt < this.retryAttempts) {
                    await this.delay(this.retryDelay * attempt);
                }
            }
        }

        // If we get here, all attempts failed
        console.error('AJAX request failed after retries:', lastError);
        throw lastError;
    }

    /**
     * GET request
     */
    async get(url, params = {}) {
        const searchParams = new URLSearchParams(params);
        const fullUrl = `${url}${searchParams.toString() ? '?' + searchParams.toString() : ''}`;

        return this.request(fullUrl, { method: 'GET' });
    }

    /**
     * POST request
     */
    async post(url, data = {}) {
        const options = {
            method: 'POST'
        };

        if (data instanceof FormData) {
            options.body = data;
        } else {
            options.body = data;
        }

        return this.request(url, options);
    }

    /**
     * PUT request
     */
    async put(url, data = {}) {
        return this.request(url, {
            method: 'PUT',
            body: data
        });
    }

    /**
     * DELETE request
     */
    async delete(url, data = {}) {
        return this.request(url, {
            method: 'DELETE',
            body: data
        });
    }

    /**
     * Upload file with progress tracking
     */
    async uploadFile(url, file, onProgress = null) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            const formData = new FormData();

            formData.append('file', file);
            formData.append('csrf_token', window.csrfToken);
            formData.append('ajax', 'true');

            // Track upload progress
            if (onProgress && xhr.upload) {
                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        const percentComplete = (e.loaded / e.total) * 100;
                        onProgress(Math.round(percentComplete));
                    }
                });
            }

            xhr.addEventListener('load', () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        resolve(response);
                    } catch (e) {
                        reject(new Error('Invalid JSON response'));
                    }
                } else {
                    reject(new Error(`HTTP ${xhr.status}: ${xhr.statusText}`));
                }
            });

            xhr.addEventListener('error', () => {
                reject(new Error('Network error'));
            });

            xhr.addEventListener('timeout', () => {
                reject(new Error('Request timeout'));
            });

            xhr.timeout = this.timeout;
            xhr.open('POST', url);
            xhr.send(formData);
        });
    }

    /**
     * Utility delay function
     */
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
}

/**
 * Module-specific AJAX functions
 */
const Ajax = {
    manager: new AjaxManager(),

    // Properties module
    properties: {
        /**
         * Get all properties with filters
         */
        async list(filters = {}) {
            const params = new URLSearchParams({
                module: 'properties',
                action: 'list',
                ...filters
            });

            return Ajax.manager.get('index.php?' + params.toString());
        },

        /**
         * Get single property
         */
        async get(id) {
            const formData = new FormData();
            formData.append('action', 'get_property');
            formData.append('id', id);

            return Ajax.manager.post('index.php?module=properties', formData);
        },

        /**
         * Create property
         */
        async create(data) {
            const formData = new FormData();

            // Add all form fields to FormData
            for (const [key, value] of Object.entries(data)) {
                if (value instanceof FileList) {
                    Array.from(value).forEach(file => {
                        formData.append(`${key}[]`, file);
                    });
                } else {
                    formData.append(key, value);
                }
            }

            formData.append('action', 'create');

            return Ajax.manager.post('index.php?module=properties&action=create', formData);
        },

        /**
         * Update property
         */
        async update(id, data) {
            const formData = new FormData();

            for (const [key, value] of Object.entries(data)) {
                formData.append(key, value);
            }

            formData.append('action', 'update');
            formData.append('id', id);

            return Ajax.manager.post('index.php?module=properties&action=edit', formData);
        },

        /**
         * Delete property
         */
        async delete(id) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);

            return Ajax.manager.post('index.php?module=properties', formData);
        },

        /**
         * Update property status
         */
        async updateStatus(id, status) {
            const formData = new FormData();
            formData.append('action', 'update_status');
            formData.append('id', id);
            formData.append('status', status);

            return Ajax.manager.post('index.php?module=properties', formData);
        },

        /**
         * Search properties
         */
        async search(query, limit = 10) {
            const formData = new FormData();
            formData.append('action', 'search');
            formData.append('search', query);
            formData.append('limit', limit);

            return Ajax.manager.post('index.php?module=properties', formData);
        },

        /**
         * Upload property photo
         */
        async uploadPhoto(propertyId, file, onProgress = null) {
            const formData = new FormData();
            formData.append('action', 'upload_photo');
            formData.append('property_id', propertyId);
            formData.append('photo', file);

            return Ajax.manager.uploadFile('index.php?module=properties', file, onProgress);
        },

        /**
         * Delete property photo
         */
        async deletePhoto(propertyId, filename) {
            const formData = new FormData();
            formData.append('action', 'delete_photo');
            formData.append('property_id', propertyId);
            formData.append('filename', filename);

            return Ajax.manager.post('index.php?module=properties', formData);
        }
    },

    // Clients module (basic structure - to be expanded)
    clients: {
        async list(filters = {}) {
            const params = new URLSearchParams({
                module: 'clients',
                action: 'list',
                ...filters
            });

            return Ajax.manager.get('index.php?' + params.toString());
        },

        async get(id) {
            const formData = new FormData();
            formData.append('action', 'get_client');
            formData.append('id', id);

            return Ajax.manager.post('index.php?module=clients', formData);
        },

        async create(data) {
            const formData = new FormData();

            for (const [key, value] of Object.entries(data)) {
                formData.append(key, value);
            }

            formData.append('action', 'create');

            return Ajax.manager.post('index.php?module=clients&action=create', formData);
        },

        async update(id, data) {
            const formData = new FormData();

            for (const [key, value] of Object.entries(data)) {
                formData.append(key, value);
            }

            formData.append('action', 'update');
            formData.append('id', id);

            return Ajax.manager.post('index.php?module=clients&action=edit', formData);
        },

        async delete(id) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);

            return Ajax.manager.post('index.php?module=clients', formData);
        }
    },

    // Sales module
    sales: {
        async list(filters = {}) {
            const params = new URLSearchParams({
                module: 'sales',
                action: 'list',
                ...filters
            });

            return Ajax.manager.get('index.php?' + params.toString());
        },

        async get(id) {
            const formData = new FormData();
            formData.append('action', 'get');
            formData.append('id', id);

            return Ajax.manager.post('index.php?module=sales&action=ajax', formData);
        },

        async create(data) {
            const formData = new FormData();

            for (const [key, value] of Object.entries(data)) {
                formData.append(key, value);
            }

            formData.append('action', 'create');

            return Ajax.manager.post('index.php?module=sales&action=ajax', formData);
        },

        async update(id, data) {
            const formData = new FormData();

            for (const [key, value] of Object.entries(data)) {
                formData.append(key, value);
            }

            formData.append('action', 'update');
            formData.append('id', id);

            return Ajax.manager.post('index.php?module=sales&action=ajax', formData);
        },

        async delete(id) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);

            return Ajax.manager.post('index.php?module=sales&action=ajax', formData);
        },

        async search(query, filters = {}) {
            const formData = new FormData();
            formData.append('action', 'search');
            formData.append('term', query);

            for (const [key, value] of Object.entries(filters)) {
                formData.append(key, value);
            }

            return Ajax.manager.post('index.php?module=sales&action=ajax', formData);
        },

        async getStatistics(period = 'all') {
            const formData = new FormData();
            formData.append('action', 'statistics');
            formData.append('period', period);

            return Ajax.manager.post('index.php?module=sales&action=ajax', formData);
        }
    },

    // Utility functions
    utils: {
        /**
         * Test connection to server
         */
        async ping() {
            try {
                const response = await Ajax.manager.get('index.php?ping=1');
                return response;
            } catch (error) {
                console.error('Ping failed:', error);
                return { success: false, error: error.message };
            }
        },

        /**
         * Get system status
         */
        async getStatus() {
            const formData = new FormData();
            formData.append('action', 'status');

            return Ajax.manager.post('index.php', formData);
        }
    }
};

/**
 * Global AJAX event handlers
 */
document.addEventListener('DOMContentLoaded', function() {
    // Handle AJAX forms automatically
    document.addEventListener('submit', async function(e) {
        const form = e.target;

        if (!form.matches('[data-ajax="true"]')) return;

        e.preventDefault();

        try {
            // Show loading state
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.dataset.originalText = submitButton.innerHTML;
                submitButton.innerHTML = '<span class="loading"></span> Procesando...';
            }

            // Collect form data
            const formData = new FormData(form);
            const module = formData.get('module') || getUrlParam('module') || 'properties';
            const action = formData.get('action') || 'create';

            // Determine URL
            const url = form.action || `index.php?module=${module}&action=${action}`;

            // Submit form
            const response = await Ajax.manager.post(url, formData);

            if (response.success) {
                // Show success message
                if (response.message) {
                    App.showSuccessMessage(response.message);
                }

                // Handle redirect
                if (response.redirect) {
                    window.location.href = response.redirect;
                } else if (form.dataset.redirect) {
                    window.location.href = form.dataset.redirect;
                } else {
                    // Default redirect to list view
                    window.location.href = `?module=${module}`;
                }
            } else {
                throw new Error(response.error || 'Unknown error occurred');
            }

        } catch (error) {
            console.error('Form submission error:', error);
            App.showErrorMessage(error.message || 'Error al procesar el formulario');
        } finally {
            // Restore submit button
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = submitButton.dataset.originalText || submitButton.innerHTML;
            }
        }
    });

    // Handle AJAX links
    document.addEventListener('click', async function(e) {
        const link = e.target.closest('[data-ajax="true"]');

        if (!link || !link.href) return;

        e.preventDefault();

        try {
            App.showLoadingState();

            const response = await Ajax.manager.get(link.href);

            if (response.success) {
                if (response.html) {
                    // Replace content
                    const contentArea = document.querySelector('.content');
                    if (contentArea) {
                        contentArea.innerHTML = response.html;
                    }
                } else if (response.redirect) {
                    window.location.href = response.redirect;
                }
            } else {
                throw new Error(response.error || 'Unknown error occurred');
            }

        } catch (error) {
            console.error('AJAX link error:', error);
            App.showErrorMessage(error.message || 'Error al cargar la página');
        } finally {
            App.hideLoadingState();
        }
    });
});

/**
 * Utility functions
 */
function getUrlParam(param) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(param);
}

/**
 * Educational AJAX demonstration functions
 */
const AjaxDemo = {
    /**
     * Demonstrate successful AJAX call
     */
    async testSuccess() {
        try {
            const response = await Ajax.utils.ping();
            console.log('AJAX Success:', response);
            App.showSuccessMessage('Conexión exitosa con el servidor');
        } catch (error) {
            console.error('AJAX Error:', error);
            App.showErrorMessage('Error de conexión: ' + error.message);
        }
    },

    /**
     * Demonstrate error handling
     */
    async testError() {
        try {
            const response = await Ajax.manager.get('nonexistent-endpoint.php');
            console.log('This should not execute');
        } catch (error) {
            console.log('Expected error caught:', error);
            App.showErrorMessage('Error demostrado correctamente: ' + error.message);
        }
    },

    /**
     * Demonstrate file upload
     */
    async testFileUpload(file) {
        try {
            const response = await Ajax.properties.uploadPhoto(1, file, (progress) => {
                console.log(`Upload progress: ${progress}%`);
            });

            console.log('Upload response:', response);
            App.showSuccessMessage('Archivo subido correctamente');
        } catch (error) {
            console.error('Upload error:', error);
            App.showErrorMessage('Error al subir archivo: ' + error.message);
        }
    }
};

// Export for global use
window.Ajax = Ajax;
window.AjaxManager = AjaxManager;
window.AjaxDemo = AjaxDemo;

// Educational console information
if (console && typeof console.log === 'function') {
    console.log('%c Real Estate Management System - AJAX Module Loaded',
                'color: #2c3e50; font-weight: bold; font-size: 14px;');
    console.log('%c Use Ajax.properties.list() to test property API calls',
                'color: #3498db; font-style: italic;');
    console.log('%c Use AjaxDemo.testSuccess() to test connection',
                'color: #27ae60; font-style: italic;');
}