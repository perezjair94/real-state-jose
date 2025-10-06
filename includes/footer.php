<?php
/**
 * Common Footer - Real Estate Management System
 * Footer content and closing tags
 * Educational PHP/MySQL Project
 */

// Prevent direct access
if (!defined('APP_ACCESS')) {
    die('Direct access not permitted');
}
?>
        </main>
        <!-- Main Content Area Ends Here -->

        <!-- Footer Section -->
        <footer class="footer">
            <div class="footer-content">
                <div class="footer-info">
                    <p>&copy; <?= date('Y') ?> <?= APP_NAME ?> - <?= APP_DESCRIPTION ?></p>
                    <p>Versión <?= APP_VERSION ?> | Proyecto Educativo PHP/MySQL</p>
                </div>

                <?php if (ENVIRONMENT === 'development' && SHOW_DEBUG_INFO): ?>
                    <div class="footer-debug">
                        <details>
                            <summary>Información de Desarrollo</summary>
                            <div class="debug-panel">
                                <p><strong>Entorno:</strong> <?= ENVIRONMENT ?></p>
                                <p><strong>Base de Datos:</strong> <?= DB_NAME ?></p>
                                <p><strong>Módulo Actual:</strong> <?= $_GET['module'] ?? DEFAULT_MODULE ?></p>
                                <p><strong>Acción Actual:</strong> <?= $_GET['action'] ?? 'list' ?></p>
                                <p><strong>Memoria Usada:</strong> <?= formatBytes(memory_get_usage(true)) ?></p>
                                <p><strong>Tiempo de Ejecución:</strong> <?= round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 4) ?>s</p>

                                <?php if (LOG_QUERIES && defined('QUERIES_EXECUTED')): ?>
                                    <p><strong>Consultas Ejecutadas:</strong> <?= QUERIES_EXECUTED ?></p>
                                <?php endif; ?>
                            </div>
                        </details>
                    </div>
                <?php endif; ?>

                <div class="footer-links">
                    <a href="documentation/setup.md" target="_blank" title="Guía de Instalación">
                        Instalación
                    </a>
                    <a href="documentation/api.md" target="_blank" title="Documentación API">
                        API Docs
                    </a>
                    <a href="documentation/security.md" target="_blank" title="Seguridad">
                        Seguridad
                    </a>
                    <?php if (ENVIRONMENT === 'development'): ?>
                        <a href="?debug=phpinfo" title="Información PHP" onclick="return confirm('¿Mostrar información de PHP?')">
                            PHP Info
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </footer>
    </div>

    <!-- Font Awesome for icons -->
    <script src="https://kit.fontawesome.com/a2d9d6f7b4.js" crossorigin="anonymous"></script>

    <!-- JavaScript Files -->
    <script src="assets/js/app.js"></script>
    <script src="assets/js/validation.js"></script>

    <?php if ($currentModule === 'properties' || $currentModule === 'contracts'): ?>
        <!-- Load file upload JavaScript only when needed -->
        <script src="assets/js/file-upload.js"></script>
    <?php endif; ?>

    <!-- AJAX JavaScript for enhanced user experience -->
    <script src="assets/js/ajax.js"></script>

    <!-- Educational comment: Scripts are loaded at the end for better performance -->

    <?php
    // Handle debug requests in development
    if (ENVIRONMENT === 'development' && isset($_GET['debug'])):
        switch ($_GET['debug']) {
            case 'phpinfo':
                echo '<div id="phpinfo-modal" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);z-index:1000;overflow:auto;">';
                echo '<div style="background:white;margin:50px;padding:20px;border-radius:8px;">';
                echo '<button onclick="document.getElementById(\'phpinfo-modal\').style.display=\'none\'" style="float:right;padding:5px 10px;">Cerrar</button>';
                phpinfo();
                echo '</div></div>';
                break;

            case 'session':
                if (SHOW_DEBUG_INFO) {
                    echo '<script>console.log("Session Data:", ' . json_encode($_SESSION) . ');</script>';
                }
                break;
        }
    endif;
    ?>

    <!-- Initialize application -->
    <script>
        // Set CSRF token for AJAX requests
        if (typeof window.csrfToken === 'undefined') {
            window.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        }

        // Initialize application on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize tooltips, modals, and other UI components
            App.init();

            <?php if (SHOW_EDUCATIONAL_COMMENTS): ?>
                // Educational mode: Show helpful tooltips
                App.enableEducationalMode();
            <?php endif; ?>
        });

        // Educational comment: JavaScript initialization after DOM is ready
    </script>

    <?php
    // Log page access in development
    if (ENVIRONMENT === 'development' && LOG_ENABLED) {
        $module = $_GET['module'] ?? DEFAULT_MODULE;
        $action = $_GET['action'] ?? 'list';
        logMessage("Page accessed: {$module}/{$action}", 'INFO');
    }
    ?>

</body>
</html>

<?php
// Clean up output buffer if needed
if (ob_get_level()) {
    ob_end_flush();
}
?>