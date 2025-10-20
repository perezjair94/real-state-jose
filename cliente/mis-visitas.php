<?php
/**
 * Mis Visitas - Cliente
 * Historial y visitas programadas del cliente
 */

define('APP_ACCESS', true);

require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

initSession();
requireRole('cliente');

$user = getCurrentUser();

// Verificar que el cliente tenga id_cliente vinculado
if (!$user['id_cliente']) {
    $error = 'Tu cuenta no está vinculada a un cliente. Contacta al administrador.';
    $visitas = [];
} else {
    try {
        $db = new Database();
        $pdo = $db->getConnection();

        // Obtener todas las visitas del cliente
        $stmt = $pdo->prepare("
            SELECT
                v.*,
                i.tipo_inmueble,
                i.direccion,
                i.ciudad,
                i.precio,
                a.nombre AS agente_nombre,
                a.telefono AS agente_telefono,
                a.correo AS agente_correo
            FROM visita v
            JOIN inmueble i ON v.id_inmueble = i.id_inmueble
            LEFT JOIN agente a ON v.id_agente = a.id_agente
            WHERE v.id_cliente = ?
            ORDER BY v.fecha_visita DESC, v.hora_visita DESC
        ");
        $stmt->execute([$user['id_cliente']]);
        $visitas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Error loading visits: " . $e->getMessage());
        $error = 'Error al cargar las visitas';
        $visitas = [];
    }
}

// Separar visitas por estado
$proximas = [];
$realizadas = [];
$canceladas = [];

foreach ($visitas as $visita) {
    $fecha_visita = strtotime($visita['fecha_visita']);
    $hoy = strtotime(date('Y-m-d'));

    if ($visita['estado'] === 'Cancelada') {
        $canceladas[] = $visita;
    } elseif ($visita['estado'] === 'Realizada') {
        $realizadas[] = $visita;
    } elseif ($fecha_visita >= $hoy && $visita['estado'] === 'Programada') {
        $proximas[] = $visita;
    } else {
        $realizadas[] = $visita;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Visitas - <?= APP_NAME ?></title>
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

        .header {
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

        .header h1 {
            font-size: 28px;
        }

        .btn-back {
            background: #00de55;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px;
        }

        .section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .section h2 {
            color: #0a1931;
            font-size: 24px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #00de55;
        }

        .visit-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #00de55;
        }

        .visit-card.proxima {
            border-left-color: #00de55;
            background: #e8f8f0;
        }

        .visit-card.realizada {
            border-left-color: #6c757d;
        }

        .visit-card.cancelada {
            border-left-color: #e94545;
            background: #ffe8e8;
        }

        .visit-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .visit-title {
            font-size: 20px;
            font-weight: 700;
            color: #0a1931;
        }

        .visit-status {
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            color: white;
        }

        .status-programada {
            background: #00de55;
        }

        .status-realizada {
            background: #6c757d;
        }

        .status-cancelada {
            background: #e94545;
        }

        .visit-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .visit-info-item {
            font-size: 14px;
        }

        .visit-info-label {
            font-weight: 600;
            color: #0a1931;
            margin-bottom: 5px;
        }

        .visit-info-value {
            color: #666;
        }

        .visit-property {
            background: white;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
        }

        .visit-property h4 {
            color: #0a1931;
            margin-bottom: 10px;
        }

        .property-details {
            display: flex;
            gap: 20px;
            font-size: 14px;
            color: #666;
        }

        .price {
            font-size: 20px;
            font-weight: 700;
            color: #00de55;
            margin-top: 10px;
        }

        .no-visits {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-number {
            font-size: 36px;
            font-weight: 700;
            color: #00de55;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>📅 Mis Visitas</h1>
            <a href="dashboard.php" class="btn-back">← Volver al Dashboard</a>
        </div>
    </div>

    <div class="container">
        <?php if (isset($error)): ?>
            <div class="alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?= count($proximas) ?></div>
                <div class="stat-label">Próximas Visitas</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= count($realizadas) ?></div>
                <div class="stat-label">Visitas Realizadas</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= count($visitas) ?></div>
                <div class="stat-label">Total de Visitas</div>
            </div>
        </div>

        <!-- Próximas Visitas -->
        <div class="section">
            <h2>🔔 Próximas Visitas</h2>
            <?php if (empty($proximas)): ?>
                <div class="no-visits">No tienes visitas programadas</div>
            <?php else: ?>
                <?php foreach ($proximas as $visita): ?>
                    <div class="visit-card proxima">
                        <div class="visit-header">
                            <div class="visit-title"><?= htmlspecialchars($visita['tipo_inmueble']) ?> en <?= htmlspecialchars($visita['ciudad']) ?></div>
                            <span class="visit-status status-<?= strtolower($visita['estado']) ?>">
                                <?= htmlspecialchars($visita['estado']) ?>
                            </span>
                        </div>

                        <div class="visit-info">
                            <div class="visit-info-item">
                                <div class="visit-info-label">📅 Fecha</div>
                                <div class="visit-info-value"><?= formatDate($visita['fecha_visita']) ?></div>
                            </div>
                            <div class="visit-info-item">
                                <div class="visit-info-label">🕐 Hora</div>
                                <div class="visit-info-value"><?= substr($visita['hora_visita'], 0, 5) ?></div>
                            </div>
                            <?php if ($visita['agente_nombre']): ?>
                                <div class="visit-info-item">
                                    <div class="visit-info-label">👔 Agente</div>
                                    <div class="visit-info-value"><?= htmlspecialchars($visita['agente_nombre']) ?></div>
                                </div>
                                <div class="visit-info-item">
                                    <div class="visit-info-label">📞 Teléfono</div>
                                    <div class="visit-info-value"><?= htmlspecialchars($visita['agente_telefono']) ?></div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="visit-property">
                            <h4>📍 <?= htmlspecialchars($visita['direccion']) ?></h4>
                            <div class="price"><?= formatCurrency($visita['precio']) ?></div>
                        </div>

                        <?php if ($visita['observaciones']): ?>
                            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
                                <strong>Observaciones:</strong>
                                <p style="color: #666; margin-top: 5px;"><?= htmlspecialchars($visita['observaciones']) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Historial de Visitas -->
        <div class="section">
            <h2>📋 Historial de Visitas</h2>
            <?php if (empty($realizadas)): ?>
                <div class="no-visits">No hay visitas realizadas aún</div>
            <?php else: ?>
                <?php foreach ($realizadas as $visita): ?>
                    <div class="visit-card realizada">
                        <div class="visit-header">
                            <div class="visit-title"><?= htmlspecialchars($visita['tipo_inmueble']) ?> en <?= htmlspecialchars($visita['ciudad']) ?></div>
                            <span class="visit-status status-<?= strtolower($visita['estado']) ?>">
                                <?= htmlspecialchars($visita['estado']) ?>
                            </span>
                        </div>

                        <div class="visit-info">
                            <div class="visit-info-item">
                                <div class="visit-info-label">📅 Fecha</div>
                                <div class="visit-info-value"><?= formatDate($visita['fecha_visita']) ?></div>
                            </div>
                            <div class="visit-info-item">
                                <div class="visit-info-label">📍 Dirección</div>
                                <div class="visit-info-value"><?= htmlspecialchars($visita['direccion']) ?></div>
                            </div>
                            <?php if ($visita['calificacion']): ?>
                                <div class="visit-info-item">
                                    <div class="visit-info-label">⭐ Interés</div>
                                    <div class="visit-info-value"><?= htmlspecialchars($visita['calificacion']) ?></div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($visita['observaciones']): ?>
                            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
                                <strong>Observaciones:</strong>
                                <p style="color: #666; margin-top: 5px;"><?= htmlspecialchars($visita['observaciones']) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if (!empty($canceladas)): ?>
        <!-- Visitas Canceladas -->
        <div class="section">
            <h2>❌ Visitas Canceladas</h2>
            <?php foreach ($canceladas as $visita): ?>
                <div class="visit-card cancelada">
                    <div class="visit-header">
                        <div class="visit-title"><?= htmlspecialchars($visita['tipo_inmueble']) ?> en <?= htmlspecialchars($visita['ciudad']) ?></div>
                        <span class="visit-status status-cancelada">Cancelada</span>
                    </div>

                    <div class="visit-info">
                        <div class="visit-info-item">
                            <div class="visit-info-label">📅 Fecha</div>
                            <div class="visit-info-value"><?= formatDate($visita['fecha_visita']) ?></div>
                        </div>
                        <div class="visit-info-item">
                            <div class="visit-info-label">📍 Dirección</div>
                            <div class="visit-info-value"><?= htmlspecialchars($visita['direccion']) ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
