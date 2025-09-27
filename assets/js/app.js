
/**
 * Main Application JavaScript - Real Estate Management System
 * Core functionality and utilities
 * Educational PHP/MySQL Project
 */

/**
 * Main Application Object
 */
const App = {
  // Configuration
  config: {
    ajaxTimeout: 30000,
    maxFileSize: 5 * 1024 * 1024, // 5MB
    allowedImageTypes: ['jpg', 'jpeg', 'png', 'gif', 'webp'],
    allowedDocumentTypes: ['pdf', 'doc', 'docx', 'txt'],
    currency: 'COP',
    dateFormat: 'dd/mm/yyyy'
  },

  // State management
  state: {
    currentModule: null,
    currentAction: null,
    currentId: null,
    isLoading: false,
    errors: {},
    user: null
  },

  // Cache for frequently accessed elements
  cache: {
    body: null,
    nav: null,
    content: null,
    modals: {}
  },

  /**
   * Initialize application
   */
  init() {
    console.log('Initializing Real Estate Management System...');

    // Cache DOM elements
    this.cacheElements();

    // Set up event listeners
    this.bindEvents();

    // Initialize components
    this.initComponents();

    // Parse current state from URL
    this.parseCurrentState();

    // Set up CSRF token for AJAX requests
    this.setupCSRFToken();

    // Initialize educational features
    if (this.isEducationalMode()) {
      this.enableEducationalMode();
    }

    console.log('Application initialized successfully');
  },

  /**
   * Cache frequently used DOM elements
   */
  cacheElements() {
    this.cache.body = document.body;
    this.cache.nav = document.querySelector('.nav');
    this.cache.content = document.querySelector('.content');
  },

  /**
   * Bind global event listeners
   */
  bindEvents() {
    // Navigation handling
    if (this.cache.nav) {
      this.cache.nav.addEventListener('click', (e) => {
        if (e.target && e.target.matches && e.target.matches('.nav-button')) {
          this.handleNavigation(e.target);
        }
      });
    }

    // Global keyboard shortcuts
    document.addEventListener('keydown', (e) => {
      this.handleKeyboardShortcuts(e);
    });

    // Handle browser back/forward
    window.addEventListener('popstate', (e) => {
      this.handlePopState(e);
    });

    // Global error handling
    window.addEventListener('error', (e) => {
      this.handleGlobalError(e);
    });

    // Handle AJAX errors
    document.addEventListener('ajaxError', (e) => {
      this.handleAjaxError(e.detail);
    });

    // Handle form submissions
    document.addEventListener('submit', (e) => {
      if (e.target && e.target.matches && e.target.matches('form[data-ajax]')) {
        e.preventDefault();
        this.handleAjaxForm(e.target);
      }
    });

    // Handle modal triggers
    document.addEventListener('click', (e) => {
      if (e.target && e.target.matches && e.target.matches('[data-modal]')) {
        e.preventDefault();
        this.openModal(e.target.dataset.modal);
      }

      if (e.target && e.target.matches && e.target.matches('[data-modal-close]')) {
        this.closeModal();
      }
    });

    // Handle tooltips
    document.addEventListener('mouseenter', (e) => {
      if (e.target && e.target.matches && e.target.matches('[data-tooltip]')) {
        this.showTooltip(e.target);
      }
    }, true);

    document.addEventListener('mouseleave', (e) => {
      if (e.target && e.target.matches && e.target.matches('[data-tooltip]')) {
        this.hideTooltip();
      }
    }, true);
  },

  /**
   * Initialize components
   */
  initComponents() {
    // Initialize modals
    this.initModals();

    // Initialize tooltips
    this.initTooltips();

    // Initialize file uploads
    this.initFileUploads();

    // Initialize data tables
    this.initDataTables();

    // Initialize date pickers
    this.initDatePickers();

    // Initialize search functionality
    this.initSearch();
  },

  /**
   * Parse current state from URL parameters
   */
  parseCurrentState() {
    const urlParams = new URLSearchParams(window.location.search);
    this.state.currentModule = urlParams.get('module') || 'properties';
    this.state.currentAction = urlParams.get('action') || 'list';
    this.state.currentId = urlParams.get('id') || null;
  },

  /**
   * Setup CSRF token for AJAX requests
   */
  setupCSRFToken() {
    const token = document.querySelector('meta[name="csrf-token"]');
    if (token) {
      window.csrfToken = token.getAttribute('content');
    }

    // Add CSRF token to all AJAX requests
    const originalFetch = window.fetch;
    window.fetch = function (url, options = {}) {
      if (options.method && options.method !== 'GET') {
        if (options.body instanceof FormData) {
          options.body.append('csrf_token', window.csrfToken);
        } else if (options.body && typeof options.body === 'string') {
          try {
            const data = JSON.parse(options.body);
            data.csrf_token = window.csrfToken;
            options.body = JSON.stringify(data);
          } catch (e) {
            // If not JSON, append as form data
            options.body += '&csrf_token=' + encodeURIComponent(window.csrfToken);
          }
        }
      }
      return originalFetch(url, options);
    };
  },

  /**
   * Handle navigation
   */
  handleNavigation(element) {
    const href = element.getAttribute('onclick');
    if (href && href.includes('location.href')) {
      // Extract URL from onclick
      const match = href.match(/location\.href='([^']+)'/);
      if (match) {
        this.navigateTo(match[1]);
      }
    }
  },

  /**
   * Navigate to URL
   */
  navigateTo(url) {
    if (this.state.isLoading) {
      return;
    }

    this.showLoadingState();
    window.location.href = url;
  },

  /**
   * Handle keyboard shortcuts
   */
  handleKeyboardShortcuts(e) {
    // Ctrl/Cmd + specific keys
    if (e.ctrlKey || e.metaKey) {
      switch (e.key) {
        case 's':
          e.preventDefault();
          this.saveCurrentForm();
          break;
        case 'f':
          e.preventDefault();
          this.focusSearch();
          break;
      }
    }

    // Escape key
    if (e.key === 'Escape') {
      this.closeModal();
      this.hideTooltip();
    }
  },

  /**
   * Handle browser back/forward
   */
  handlePopState(e) {
    this.parseCurrentState();
    // Refresh page content if needed
    if (e.state && e.state.module !== this.state.currentModule) {
      window.location.reload();
    }
  },

  /**
   * Handle global errors
   */
  handleGlobalError(e) {
    console.error('Global error:', e.error);

    if (this.isEducationalMode()) {
      this.showErrorMessage('Error en la aplicación: ' + e.error.message);
    }
  },

  /**
   * Handle AJAX errors
   */
  handleAjaxError(error) {
    console.error('AJAX error:', error);
    this.hideLoadingState();
    this.showErrorMessage('Error de conexión. Intente nuevamente.');
  },

  /**
   * Initialize modals
   */
  initModals() {
    // Create modal container if it doesn't exist
    if (!document.getElementById('modal-container')) {
      const modalContainer = document.createElement('div');
      modalContainer.id = 'modal-container';
      modalContainer.innerHTML = `
                <div class="modal" id="app-modal" style="display: none;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3 id="modal-title">Modal Title</h3>
                            <button type="button" class="modal-close" data-modal-close>&times;</button>
                        </div>
                        <div class="modal-body" id="modal-body">
                            Modal content goes here
                        </div>
                        <div class="modal-footer" id="modal-footer">
                            <button type="button" class="btn btn-secondary" data-modal-close>Cerrar</button>
                        </div>
                    </div>
                </div>
            `;
      document.body.appendChild(modalContainer);
    }
  },

  /**
   * Open modal
   */
  openModal(content, title = 'Modal', footer = null) {
    const modal = document.getElementById('app-modal');
    const modalTitle = document.getElementById('modal-title');
    const modalBody = document.getElementById('modal-body');
    const modalFooter = document.getElementById('modal-footer');

    if (modal && modalTitle && modalBody) {
      modalTitle.textContent = title;

      if (typeof content === 'string') {
        modalBody.innerHTML = content;
      } else if (content instanceof HTMLElement) {
        modalBody.innerHTML = '';
        modalBody.appendChild(content);
      }

      if (footer) {
        modalFooter.innerHTML = footer;
      }

      modal.style.display = 'block';
      document.body.classList.add('modal-open');

      // Focus management for accessibility
      const firstFocusable = modal.querySelector('button, input, select, textarea, [tabindex]:not([tabindex="-1"])');
      if (firstFocusable) {
        firstFocusable.focus();
      }
    }
  },

  /**
   * Close modal
   */
  closeModal() {
    const modal = document.getElementById('app-modal');
    if (modal) {
      modal.style.display = 'none';
      document.body.classList.remove('modal-open');
    }
  },

  /**
   * Initialize tooltips
   */
  initTooltips() {
    // Tooltips are handled via CSS and event delegation
    this.tooltipElement = null;
  },

  /**
   * Show tooltip
   */
  showTooltip(element) {
    const text = element.dataset.tooltip;
    if (!text) return;

    this.hideTooltip();

    this.tooltipElement = document.createElement('div');
    this.tooltipElement.className = 'tooltip';
    this.tooltipElement.textContent = text;
    this.tooltipElement.style.cssText = `
            position: absolute;
            background: #333;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            z-index: 1000;
            pointer-events: none;
            white-space: nowrap;
        `;

    document.body.appendChild(this.tooltipElement);

    // Position tooltip
    const rect = element.getBoundingClientRect();
    const tooltipRect = this.tooltipElement.getBoundingClientRect();

    let top = rect.top - tooltipRect.height - 10;
    let left = rect.left + (rect.width / 2) - (tooltipRect.width / 2);

    // Adjust if tooltip goes off screen
    if (top < 0) {
      top = rect.bottom + 10;
    }
    if (left < 0) {
      left = 10;
    }
    if (left + tooltipRect.width > window.innerWidth) {
      left = window.innerWidth - tooltipRect.width - 10;
    }

    this.tooltipElement.style.top = top + window.scrollY + 'px';
    this.tooltipElement.style.left = left + 'px';
  },

  /**
   * Hide tooltip
   */
  hideTooltip() {
    if (this.tooltipElement) {
      this.tooltipElement.remove();
      this.tooltipElement = null;
    }
  },

  /**
   * Initialize file uploads
   */
  initFileUploads() {
    document.querySelectorAll('.file-upload').forEach(container => {
      const input = container.querySelector('input[type="file"]');
      const label = container.querySelector('.file-upload-label');

      if (input && label) {
        // Drag and drop
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
          label.addEventListener(eventName, this.preventDefaults, false);
        });

        ['dragenter', 'dragover'].forEach(eventName => {
          label.addEventListener(eventName, () => {
            container.classList.add('dragover');
          }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
          label.addEventListener(eventName, () => {
            container.classList.remove('dragover');
          }, false);
        });

        label.addEventListener('drop', (e) => {
          const files = e.dataTransfer.files;
          input.files = files;
          input.dispatchEvent(new Event('change'));
        }, false);
      }
    });
  },

  /**
   * Prevent default for drag/drop
   */
  preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
  },

  /**
   * Initialize data tables
   */
  initDataTables() {
    // Add sorting functionality to tables
    document.querySelectorAll('.table th[data-sort]').forEach(header => {
      header.style.cursor = 'pointer';
      header.addEventListener('click', () => {
        this.sortTable(header);
      });
    });

    // Add row selection functionality
    document.querySelectorAll('.table tbody tr').forEach(row => {
      row.addEventListener('click', (e) => {
        if (!e.target.matches('button, a, input')) {
          row.classList.toggle('selected');
        }
      });
    });
  },

  /**
   * Sort table
   */
  sortTable(header) {
    const table = header.closest('table');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const column = Array.from(header.parentNode.children).indexOf(header);
    const isAscending = header.dataset.sortDir !== 'asc';

    rows.sort((a, b) => {
      const aText = a.children[column].textContent.trim();
      const bText = b.children[column].textContent.trim();

      // Try to parse as numbers
      const aNum = parseFloat(aText.replace(/[^\d.-]/g, ''));
      const bNum = parseFloat(bText.replace(/[^\d.-]/g, ''));

      if (!isNaN(aNum) && !isNaN(bNum)) {
        return isAscending ? aNum - bNum : bNum - aNum;
      }

      // String comparison
      return isAscending ? aText.localeCompare(bText) : bText.localeCompare(aText);
    });

    // Clear existing rows and add sorted rows
    tbody.innerHTML = '';
    rows.forEach(row => tbody.appendChild(row));

    // Update sort indicators
    table.querySelectorAll('th').forEach(th => {
      th.removeAttribute('data-sort-dir');
    });
    header.dataset.sortDir = isAscending ? 'asc' : 'desc';
  },

  /**
   * Initialize date pickers
   */
  initDatePickers() {
    // Simple date picker enhancement
    document.querySelectorAll('input[type="date"]').forEach(input => {
      // Add date format helper
      if (!input.placeholder) {
        input.placeholder = this.config.dateFormat;
      }

      // Add min/max date validation
      if (input.dataset.minDate === 'today') {
        input.min = new Date().toISOString().split('T')[0];
      }
    });
  },

  /**
   * Initialize search functionality
   */
  initSearch() {
    document.querySelectorAll('input[type="search"], input[name="search"]').forEach(input => {
      let searchTimeout;

      input.addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
          this.performSearch(e.target);
        }, 500);
      });
    });
  },

  /**
   * Perform search
   */
  performSearch(input) {
    const query = input.value.trim();
    if (query.length >= 2) {
      // Auto-submit search form
      const form = input.closest('form');
      if (form) {
        form.submit();
      }
    }
  },

  /**
   * Show loading state
   */
  showLoadingState() {
    this.state.isLoading = true;
    document.body.classList.add('loading');

    // Show loading overlay
    if (!document.getElementById('loading-overlay')) {
      const overlay = document.createElement('div');
      overlay.id = 'loading-overlay';
      overlay.className = 'loading-overlay';
      overlay.innerHTML = '<div class="loading"></div>';
      document.body.appendChild(overlay);
    }
  },

  /**
   * Hide loading state
   */
  hideLoadingState() {
    this.state.isLoading = false;
    document.body.classList.remove('loading');

    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
      overlay.remove();
    }

    // Re-enable submit buttons
    document.querySelectorAll('button[type="submit"][disabled]').forEach(button => {
      button.disabled = false;
      button.innerHTML = button.dataset.originalText || button.innerHTML.replace(/.*Procesando.*/, 'Enviar');
    });
  },

  /**
   * Show success message
   */
  showSuccessMessage(message) {
    this.showMessage(message, 'success');
  },

  /**
   * Show error message
   */
  showErrorMessage(message) {
    this.showMessage(message, 'error');
  },

  /**
   * Show message
   */
  showMessage(message, type = 'info') {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible`;
    alert.innerHTML = `
            <button type="button" class="close" onclick="this.parentElement.remove()">&times;</button>
            ${message}
        `;

    // Insert at top of content
    const content = this.cache.content || document.querySelector('.content');
    if (content) {
      content.insertBefore(alert, content.firstChild);

      // Auto-remove after 5 seconds
      setTimeout(() => {
        if (alert.parentNode) {
          alert.remove();
        }
      }, 5000);
    }
  },

  /**
   * Format currency
   */
  formatCurrency(amount) {
    return new Intl.NumberFormat('es-CO', {
      style: 'currency',
      currency: this.config.currency,
      minimumFractionDigits: 0
    }).format(amount);
  },

  /**
   * Format date
   */
  formatDate(date, format = this.config.dateFormat) {
    if (typeof date === 'string') {
      date = new Date(date);
    }

    if (!(date instanceof Date) || isNaN(date)) {
      return '';
    }

    return date.toLocaleDateString('es-CO');
  },

  /**
   * Check if educational mode is enabled
   */
  isEducationalMode() {
    return window.location.hostname.includes('localhost') ||
      document.body.dataset.educational === 'true';
  },

  /**
   * Enable educational mode features
   */
  enableEducationalMode() {
    console.log('Educational mode enabled');

    // Add educational tooltips
    document.querySelectorAll('[data-validation]').forEach(field => {
      if (!field.dataset.tooltip) {
        field.dataset.tooltip = `Validación: ${field.dataset.validation}`;
      }
    });

    // Add debug information
    this.addDebugPanel();
  },

  /**
   * Add debug panel for educational purposes
   */
  addDebugPanel() {
    if (document.getElementById('debug-panel')) return;

    const panel = document.createElement('div');
    panel.id = 'debug-panel';
    panel.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #2c3e50;
            color: white;
            padding: 10px;
            border-radius: 8px;
            font-size: 12px;
            z-index: 9999;
            max-width: 300px;
            font-family: monospace;
        `;

    panel.innerHTML = `
            <div><strong>Debug Info</strong></div>
            <div>Module: ${this.state.currentModule}</div>
            <div>Action: ${this.state.currentAction}</div>
            <div>Memory: <span id="memory-usage">-</span></div>
            <div>Errors: <span id="error-count">0</span></div>
            <button onclick="this.parentElement.remove()" style="float: right; background: none; border: none; color: white; cursor: pointer;">&times;</button>
        `;

    document.body.appendChild(panel);

    // Update memory usage periodically
    setInterval(() => {
      if (performance.memory) {
        const usage = Math.round(performance.memory.usedJSHeapSize / 1024 / 1024);
        const usageElement = document.getElementById('memory-usage');
        if (usageElement) {
          usageElement.textContent = usage + ' MB';
        }
      }
    }, 2000);
  },

  /**
   * Save current form (Ctrl+S shortcut)
   */
  saveCurrentForm() {
    const form = document.querySelector('form:not([data-no-save])');
    if (form) {
      const submitButton = form.querySelector('button[type="submit"]');
      if (submitButton) {
        submitButton.click();
      }
    }
  },

  /**
   * Focus search input (Ctrl+F shortcut)
   */
  focusSearch() {
    const searchInput = document.querySelector('input[type="search"], input[name="search"]');
    if (searchInput) {
      searchInput.focus();
      searchInput.select();
    }
  }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  App.init();
});

// Export for global access
window.App = App;

