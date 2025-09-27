<?php
/**
 * Common Header - Real Estate Management System
 * Navigation and layout header
 * Educational PHP/MySQL Project
 */

// Prevent direct access
if (!defined('APP_ACCESS')) {
    die('Direct access not permitted');
}

// Get current module and action for navigation highlighting
$currentModule = $_GET['module'] ?? DEFAULT_MODULE;
$currentAction = $_GET['action'] ?? 'list';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= APP_DESCRIPTION ?>">
    <meta name="author" content="<?= APP_AUTHOR ?>">

    <title><?= APP_NAME ?> - <?= AVAILABLE_MODULES[$currentModule] ?? 'Sistema' ?></title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">

    <!-- Preload critical resources -->
    <link rel="preload" href="assets/css/style.css" as="style">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="assets/css/style.css">

    <!-- CSRF Token for forms -->
    <meta name="csrf-token" content="<?= generateCSRFToken() ?>">

    <!-- Educational comment: Meta tags improve SEO and security -->
</head>
<body>
    <div class="container">
        <!-- Header Section -->
        <header class="header">
            <div class="header-content">
                <h1><?= APP_NAME ?></h1>
                <p class="header-subtitle"><?= APP_DESCRIPTION ?></p>
                <?php if (ENVIRONMENT === 'development' && SHOW_DEBUG_INFO): ?>
                    <small class="debug-info">
                        Versión <?= APP_VERSION ?> | Módulo: <?= $currentModule ?> | Acción: <?= $currentAction ?>
                    </small>
                <?php endif; ?>
            </div>
        </header>

        <!-- Navigation Section -->
        <nav class="nav">
            <?php foreach (AVAILABLE_MODULES as $moduleKey => $moduleName): ?>
                <button
                    type="button"
                    class="nav-button <?= $currentModule === $moduleKey ? 'active' : '' ?>"
                    onclick="location.href='?module=<?= $moduleKey ?>'"
                    aria-label="Ir a <?= $moduleName ?>"
                >
                    <?= $moduleName ?>
                </button>
            <?php endforeach; ?>

            <!-- Quick Actions (if needed) -->
            <div class="nav-actions">
                <?php if (ENVIRONMENT === 'development'): ?>
                    <button
                        type="button"
                        class="nav-button nav-button-secondary"
                        onclick="location.href='?module=<?= $currentModule ?>&action=create'"
                        title="Crear nuevo registro"
                    >
                        + Nuevo
                    </button>
                <?php endif; ?>
            </div>
        </nav>

        <!-- Flash Messages -->
        <div class="messages">
            <?= displayFlashMessage() ?>
        </div>

        <!-- Breadcrumb Navigation -->
        <div class="breadcrumb">
            <span class="breadcrumb-item">
                <a href="?module=<?= DEFAULT_MODULE ?>">Inicio</a>
            </span>
            <span class="breadcrumb-separator">›</span>
            <span class="breadcrumb-item current">
                <?= AVAILABLE_MODULES[$currentModule] ?? 'Módulo' ?>
            </span>
            <?php if ($currentAction !== 'list'): ?>
                <span class="breadcrumb-separator">›</span>
                <span class="breadcrumb-item current">
                    <?= MODULE_ACTIONS[$currentAction] ?? 'Acción' ?>
                </span>
            <?php endif; ?>
        </div>

        <!-- Main Content Area Starts Here -->
        <main class="content">
            <!-- Educational comment: Main content will be included by index.php -->