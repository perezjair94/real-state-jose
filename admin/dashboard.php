<?php
/**
 * Dashboard de Administrador - Sistema de Gestión Inmobiliaria
 * Panel de control completo con acceso a todas las funcionalidades
 */

define('APP_ACCESS', true);

require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

initSession();
requireRole('admin');

$user = getCurrentUser();

// Get statistics
try {
    $db = new Database();
    $pdo = $db->getConnection();

    // Count properties
    $stmt = $pdo->query("SELECT COUNT(*) as total, estado FROM inmueble GROUP BY estado");
    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $propertyStats = [];
    foreach ($properties as $prop) {
        $propertyStats[$prop['estado']] = $prop['total'];
    }
    $totalProperties = array_sum($propertyStats);

    // Count clients
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM cliente");
    $totalClients = $stmt->fetch()['total'];

    // Count agents
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM agente WHERE activo = 1");
    $totalAgents = $stmt->fetch()['total'];

    // Count sales this month
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM venta WHERE MONTH(fecha_venta) = MONTH(CURRENT_DATE())");
    $monthlySales = $stmt->fetch()['total'];

    // Count active rentals
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM arriendo WHERE estado = 'Activo'");
    $activeRentals = $stmt->fetch()['total'];

    // Upcoming visits
    $stmt = $pdo->query("
        SELECT COUNT(*) as total
        FROM visita
        WHERE fecha_visita >= CURRENT_DATE()
        AND estado = 'Programada'
    ");
    $upcomingVisits = $stmt->fetch()['total'];

} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrador - <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Oswald', sans-serif;
            background: #f5f6fa;
        }

        /* Header */
        .admin-header {
            background: #0a1931;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .admin-header h1 {
            font-size: 24px;
            font-weight: 700;
        }

        .admin-header h1 span {
            color: #00de55;
            font-size: 18px;
            font-weight: 400;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-info .username {
            font-weight: 600;
        }

        .user-info .role-badge {
            background: #00de55;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 600;
        }

        .btn-logout {
            background: #e94545;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.3s;
        }

        .btn-logout:hover {
            background: #c73838;
        }

        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px;
        }

        .welcome-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .welcome-section h2 {
            color: #0a1931;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .welcome-section p {
            color: #666;
            font-size: 16px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-left: 4px solid #00de55;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }

        .stat-card .icon {
            font-size: 36px;
            margin-bottom: 10px;
        }

        .stat-card .label {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .stat-card .value {
            color: #0a1931;
            font-size: 36px;
            font-weight: 700;
        }

        /* Menu Grid */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .menu-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-decoration: none;
            color: inherit;
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
            border-color: #00de55;
        }

        .menu-card .icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .menu-card h3 {
            color: #0a1931;
            font-size: 22px;
            margin-bottom: 10px;
        }

        .menu-card p {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }

        .menu-card .btn {
            display: inline-block;
            background: #00de55;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            margin-top: 15px;
            font-weight: 600;
            transition: background 0.3s;
        }

        .menu-card:hover .btn {
            background: #00aa41;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1>🏠 <?= APP_NAME ?> <span>| Panel de Administrador</span></h1>
        <div class="user-info">
            <span class="username"><?= htmlspecialchars($user['nombre_completo']) ?></span>
            <span class="role-badge">Admin</span>
            <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
        </div>
    </div>

    <div class="container">
        <div class="welcome-section">
            <h2>Bienvenido al Panel de Administración</h2>
            <p>Tienes acceso completo a todas las funcionalidades del sistema. Gestiona propiedades, clientes, agentes y transacciones.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon">🏘️</div>
                <div class="label">Total Propiedades</div>
                <div class="value"><?= $totalProperties ?? 0 ?></div>
            </div>

            <div class="stat-card">
                <div class="icon">👥</div>
                <div class="label">Clientes Registrados</div>
                <div class="value"><?= $totalClients ?? 0 ?></div>
            </div>

            <div class="stat-card">
                <div class="icon">👔</div>
                <div class="label">Agentes Activos</div>
                <div class="value"><?= $totalAgents ?? 0 ?></div>
            </div>

            <div class="stat-card">
                <div class="icon">💰</div>
                <div class="label">Ventas Este Mes</div>
                <div class="value"><?= $monthlySales ?? 0 ?></div>
            </div>

            <div class="stat-card">
                <div class="icon">🔑</div>
                <div class="label">Arriendos Activos</div>
                <div class="value"><?= $activeRentals ?? 0 ?></div>
            </div>

            <div class="stat-card">
                <div class="icon">📅</div>
                <div class="label">Visitas Programadas</div>
                <div class="value"><?= $upcomingVisits ?? 0 ?></div>
            </div>
        </div>

        <h2 style="margin-bottom: 20px; color: #0a1931;">Gestión del Sistema</h2>

        <div class="menu-grid">
            <a href="../index.php?module=properties" class="menu-card">
                <div class="icon">🏠</div>
                <h3>Inmuebles</h3>
                <p>Gestiona propiedades: casas, apartamentos, locales y oficinas. Agregar, editar y eliminar inmuebles.</p>
                <div class="btn">Gestionar Inmuebles</div>
            </a>

            <a href="../index.php?module=clients" class="menu-card">
                <div class="icon">👥</div>
                <h3>Clientes</h3>
                <p>Administra la base de datos de clientes, compradores, vendedores y arrendatarios.</p>
                <div class="btn">Gestionar Clientes</div>
            </a>

            <a href="../index.php?module=agents" class="menu-card">
                <div class="icon">👔</div>
                <h3>Agentes</h3>
                <p>Gestiona el equipo de agentes inmobiliarios y sus asignaciones.</p>
                <div class="btn">Gestionar Agentes</div>
            </a>

            <a href="../index.php?module=sales" class="menu-card">
                <div class="icon">💰</div>
                <h3>Ventas</h3>
                <p>Registra y consulta ventas de propiedades, comisiones y transacciones.</p>
                <div class="btn">Gestionar Ventas</div>
            </a>

            <a href="../index.php?module=rentals" class="menu-card">
                <div class="icon">🔑</div>
                <h3>Arriendos</h3>
                <p>Administra contratos de arrendamiento, pagos y vencimientos.</p>
                <div class="btn">Gestionar Arriendos</div>
            </a>

            <a href="../index.php?module=contracts" class="menu-card">
                <div class="icon">📄</div>
                <h3>Contratos</h3>
                <p>Gestiona contratos de venta y arriendo, documentos y estados.</p>
                <div class="btn">Gestionar Contratos</div>
            </a>

            <a href="../index.php?module=visits" class="menu-card">
                <div class="icon">📅</div>
                <h3>Visitas</h3>
                <p>Programa y administra visitas a propiedades con clientes.</p>
                <div class="btn">Gestionar Visitas</div>
            </a>

            <a href="usuarios.php" class="menu-card">
                <div class="icon">🔐</div>
                <h3>Usuarios</h3>
                <p>Administra usuarios del sistema, roles y permisos de acceso.</p>
                <div class="btn">Gestionar Usuarios</div>
            </a>
        </div>
    </div>
</body>
</html>
