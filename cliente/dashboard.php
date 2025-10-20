<?php
/**
 * Dashboard de Cliente - Sistema de Gestión Inmobiliaria
 * Vista limitada para clientes: ver propiedades, sus visitas y contratos
 */

define('APP_ACCESS', true);

require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

initSession();
requireRole('cliente');

$user = getCurrentUser();

// Get client-specific information
try {
    $db = new Database();
    $pdo = $db->getConnection();

    // Get client details if linked
    $clientInfo = null;
    if ($user['id_cliente']) {
        $stmt = $pdo->prepare("SELECT * FROM cliente WHERE id_cliente = ?");
        $stmt->execute([$user['id_cliente']]);
        $clientInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Count available properties
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM inmueble WHERE estado = 'Disponible'");
    $availableProperties = $stmt->fetch()['total'];

    // Count client's visits if linked
    $myVisits = 0;
    if ($user['id_cliente']) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM visita WHERE id_cliente = ?");
        $stmt->execute([$user['id_cliente']]);
        $myVisits = $stmt->fetch()['total'];
    }

    // Count client's contracts if linked
    $myContracts = 0;
    if ($user['id_cliente']) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM contrato WHERE id_cliente = ?");
        $stmt->execute([$user['id_cliente']]);
        $myContracts = $stmt->fetch()['total'];
    }

    // Get upcoming visits
    $upcomingVisits = [];
    if ($user['id_cliente']) {
        $stmt = $pdo->prepare("
            SELECT v.*, i.direccion, i.ciudad, i.tipo_inmueble, a.nombre as agente_nombre
            FROM visita v
            JOIN inmueble i ON v.id_inmueble = i.id_inmueble
            LEFT JOIN agente a ON v.id_agente = a.id_agente
            WHERE v.id_cliente = ?
            AND v.fecha_visita >= CURRENT_DATE()
            AND v.estado = 'Programada'
            ORDER BY v.fecha_visita ASC, v.hora_visita ASC
            LIMIT 5
        ");
        $stmt->execute([$user['id_cliente']]);
        $upcomingVisits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    error_log("Cliente Dashboard error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Cliente - <?= APP_NAME ?></title>
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
        .cliente-header {
            background: linear-gradient(135deg, #0a1931 0%, #1e3a5f 100%);
            color: white;
            padding: 20px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .cliente-header h1 {
            font-size: 28px;
            font-weight: 700;
        }

        .cliente-header h1 span {
            color: #00de55;
            font-size: 20px;
            font-weight: 400;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-info .username {
            font-weight: 600;
            font-size: 16px;
        }

        .user-info .role-badge {
            background: #00de55;
            padding: 5px 14px;
            border-radius: 15px;
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 600;
        }

        .btn-logout {
            background: #e94545;
            color: white;
            padding: 10px 18px;
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
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 5px solid #00de55;
        }

        .welcome-section h2 {
            color: #0a1931;
            font-size: 32px;
            margin-bottom: 10px;
        }

        .welcome-section p {
            color: #666;
            font-size: 17px;
            line-height: 1.6;
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
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid #00de55;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }

        .stat-card .icon {
            font-size: 40px;
            margin-bottom: 12px;
        }

        .stat-card .label {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .stat-card .value {
            color: #0a1931;
            font-size: 36px;
            font-weight: 700;
        }

        /* Menu Grid */
        .menu-section {
            margin-bottom: 40px;
        }

        .menu-section h2 {
            color: #0a1931;
            font-size: 26px;
            margin-bottom: 20px;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .menu-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-decoration: none;
            color: inherit;
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            border-color: #00de55;
        }

        .menu-card .icon {
            font-size: 52px;
            margin-bottom: 15px;
        }

        .menu-card h3 {
            color: #0a1931;
            font-size: 24px;
            margin-bottom: 12px;
        }

        .menu-card p {
            color: #666;
            font-size: 15px;
            line-height: 1.7;
        }

        .menu-card .btn {
            display: inline-block;
            background: #00de55;
            color: white;
            padding: 12px 24px;
            border-radius: 6px;
            margin-top: 18px;
            font-weight: 600;
            transition: background 0.3s;
        }

        .menu-card:hover .btn {
            background: #00aa41;
        }

        /* Visits Section */
        .visits-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .visits-section h2 {
            color: #0a1931;
            font-size: 26px;
            margin-bottom: 20px;
        }

        .visit-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #00de55;
        }

        .visit-card h4 {
            color: #0a1931;
            font-size: 18px;
            margin-bottom: 10px;
        }

        .visit-card p {
            color: #666;
            font-size: 14px;
            margin: 5px 0;
        }

        .no-data {
            text-align: center;
            color: #999;
            padding: 40px;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="cliente-header">
        <div class="header-content">
            <h1>🏠 <?= APP_NAME ?> <span>| Portal del Cliente</span></h1>
            <div class="user-info">
                <span class="username"><?= htmlspecialchars($user['nombre_completo']) ?></span>
                <span class="role-badge">Cliente</span>
                <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="welcome-section">
            <h2>Bienvenido a tu Portal</h2>
            <p>Explora propiedades disponibles, programa visitas y consulta tus contratos. Nuestro equipo está listo para ayudarte a encontrar el inmueble perfecto.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon">🏘️</div>
                <div class="label">Propiedades Disponibles</div>
                <div class="value"><?= $availableProperties ?? 0 ?></div>
            </div>

            <div class="stat-card">
                <div class="icon">📅</div>
                <div class="label">Mis Visitas</div>
                <div class="value"><?= $myVisits ?? 0 ?></div>
            </div>

            <div class="stat-card">
                <div class="icon">📄</div>
                <div class="label">Mis Contratos</div>
                <div class="value"><?= $myContracts ?? 0 ?></div>
            </div>
        </div>

        <?php if (!empty($upcomingVisits)): ?>
        <div class="visits-section">
            <h2>📅 Tus Próximas Visitas</h2>
            <?php foreach ($upcomingVisits as $visit): ?>
                <div class="visit-card">
                    <h4><?= htmlspecialchars($visit['tipo_inmueble']) ?> - <?= htmlspecialchars($visit['ciudad']) ?></h4>
                    <p><strong>Dirección:</strong> <?= htmlspecialchars($visit['direccion']) ?></p>
                    <p><strong>Fecha:</strong> <?= formatDate($visit['fecha_visita']) ?> a las <?= substr($visit['hora_visita'], 0, 5) ?></p>
                    <?php if ($visit['agente_nombre']): ?>
                        <p><strong>Agente:</strong> <?= htmlspecialchars($visit['agente_nombre']) ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="menu-section">
            <h2>¿Qué deseas hacer?</h2>
            <div class="menu-grid">
                <a href="propiedades.php" class="menu-card">
                    <div class="icon">🔍</div>
                    <h3>Explorar Propiedades</h3>
                    <p>Navega por nuestro catálogo de propiedades disponibles. Casas, apartamentos, locales y más.</p>
                    <div class="btn">Ver Propiedades</div>
                </a>

                <a href="mis-visitas.php" class="menu-card">
                    <div class="icon">📅</div>
                    <h3>Mis Visitas</h3>
                    <p>Consulta tus visitas programadas, historial y solicita nuevas citas para ver propiedades.</p>
                    <div class="btn">Ver Mis Visitas</div>
                </a>

                <a href="mis-contratos.php" class="menu-card">
                    <div class="icon">📄</div>
                    <h3>Mis Contratos</h3>
                    <p>Revisa tus contratos activos, documentos y detalles de tus transacciones.</p>
                    <div class="btn">Ver Mis Contratos</div>
                </a>

                <a href="mi-perfil.php" class="menu-card">
                    <div class="icon">👤</div>
                    <h3>Mi Perfil</h3>
                    <p>Actualiza tu información personal, cambia tu contraseña y gestiona tus datos.</p>
                    <div class="btn">Ver Mi Perfil</div>
                </a>
            </div>
        </div>
    </div>
</body>
</html>
