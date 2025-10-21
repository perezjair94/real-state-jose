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

    <!-- Google Fonts - Oswald -->
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;600;700&display=swap" rel="stylesheet">

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
    <!-- Modern Navbar -->
    <header class="navbar">
        <div class="logo">
            <img src="https://images.icon-icons.com/1512/PNG/512/31_104880.png" alt="logo">
            <h1>Inmuebles<span>del sinú</span></h1>
        </div>

        <nav class="menu">
            <!-- Dashboard Link -->
            <?php
            $dashboardUrl = hasRole('admin') ? 'admin/dashboard.php' : 'cliente/dashboard.php';
            $dashboardLabel = hasRole('admin') ? 'Dashboard Admin' : 'Mi Dashboard';
            ?>
            <a href="<?= $dashboardUrl ?>" class="dashboard-link" title="Ir al <?= $dashboardLabel ?>">
                🏠 <?= $dashboardLabel ?>
            </a>

            <?php foreach (MENU_STRUCTURE as $menuItem): ?>
                <?php if (isset($menuItem['submenu'])): ?>
                    <!-- Menu item with submenu -->
                    <div class="menu-item-container <?= ($currentModule === $menuItem['key'] || in_array($currentModule, array_column($menuItem['submenu'], 'key'))) ? 'active' : '' ?>">
                        <a href="?module=<?= $menuItem['key'] ?>" class="menu-item-parent">
                            <?= $menuItem['icon'] ?? '' ?> <?= $menuItem['label'] ?>
                            <span class="submenu-arrow">▼</span>
                        </a>
                        <div class="submenu">
                            <?php foreach ($menuItem['submenu'] as $subItem): ?>
                                <a
                                    href="?module=<?= $subItem['key'] ?>"
                                    class="submenu-item <?= $currentModule === $subItem['key'] ? 'active' : '' ?>"
                                    aria-label="Ir a <?= $subItem['label'] ?>"
                                >
                                    <?= $subItem['icon'] ?? '' ?> <?= $subItem['label'] ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Simple menu item without submenu -->
                    <a
                        href="?module=<?= $menuItem['key'] ?>"
                        class="<?= $currentModule === $menuItem['key'] ? 'active' : '' ?>"
                        aria-label="Ir a <?= $menuItem['label'] ?>"
                    >
                        <?= $menuItem['icon'] ?? '' ?> <?= $menuItem['label'] ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>

            <!-- User Info and Logout -->
            <?php $currentUser = getCurrentUser(); ?>
            <div class="user-menu">
                <span class="user-info">
                    👤 <?= htmlspecialchars($currentUser['nombre_completo'] ?? $currentUser['username']) ?>
                    <?php if (hasRole('admin')): ?>
                        <span class="user-badge">Admin</span>
                    <?php endif; ?>
                </span>
                <a href="<?= hasRole('admin') ? 'admin/logout.php' : 'cliente/logout.php' ?>" class="logout-link" title="Cerrar Sesión">
                    🚪 Salir
                </a>
            </div>
        </nav>
    </header>

    <div class="container">
        <!-- Flash Messages -->
        <?php if (ENVIRONMENT === 'development' && SHOW_DEBUG_INFO): ?>
            <div class="debug-info-bar">
                Versión <?= APP_VERSION ?> | Módulo: <?= $currentModule ?> | Acción: <?= $currentAction ?>
            </div>
        <?php endif; ?>

        <div class="messages">
            <?= displayFlashMessage() ?>
        </div>

        <!-- Main Content Area Starts Here -->
        <main class="content">
            <!-- Educational comment: Main content will be included by index.php -->