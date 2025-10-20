<?php
/**
 * Mis Contratos - Cliente
 * Visualización de contratos del cliente
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
    $contratos = [];
} else {
    try {
        $db = new Database();
        $pdo = $db->getConnection();

        // Obtener contratos del cliente
        $stmt = $pdo->prepare("
            SELECT
                c.*,
                i.tipo_inmueble,
                i.direccion,
                i.ciudad,
                i.precio AS precio_inmueble,
                a.nombre AS agente_nombre,
                a.telefono AS agente_telefono,
                a.correo AS agente_correo
            FROM contrato c
            JOIN inmueble i ON c.id_inmueble = i.id_inmueble
            LEFT JOIN agente a ON c.id_agente = a.id_agente
            WHERE c.id_cliente = ?
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$user['id_cliente']]);
        $contratos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Error loading contracts: " . $e->getMessage());
        $error = 'Error al cargar los contratos';
        $contratos = [];
    }
}

// Separar contratos por estado
$activos = [];
$finalizados = [];
$borradores = [];
$cancelados = [];

foreach ($contratos as $contrato) {
    switch ($contrato['estado']) {
        case 'Activo':
            $activos[] = $contrato;
            break;
        case 'Finalizado':
            $finalizados[] = $contrato;
            break;
        case 'Borrador':
            $borradores[] = $contrato;
            break;
        case 'Cancelado':
            $cancelados[] = $contrato;
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Contratos - <?= APP_NAME ?></title>
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

        .contract-card {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 5px solid #00de55;
        }

        .contract-card.activo {
            border-left-color: #00de55;
            background: #e8f8f0;
        }

        .contract-card.finalizado {
            border-left-color: #6c757d;
        }

        .contract-card.borrador {
            border-left-color: #ffc107;
            background: #fff8e1;
        }

        .contract-card.cancelado {
            border-left-color: #e94545;
            background: #ffe8e8;
        }

        .contract-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .contract-title {
            font-size: 22px;
            font-weight: 700;
            color: #0a1931;
        }

        .contract-id {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }

        .contract-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            color: white;
        }

        .status-activo {
            background: #00de55;
        }

        .status-finalizado {
            background: #6c757d;
        }

        .status-borrador {
            background: #ffc107;
        }

        .status-cancelado {
            background: #e94545;
        }

        .contract-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .info-item {
            font-size: 14px;
        }

        .info-label {
            font-weight: 600;
            color: #0a1931;
            margin-bottom: 5px;
        }

        .info-value {
            color: #666;
        }

        .contract-property {
            background: white;
            padding: 20px;
            border-radius: 6px;
            margin-top: 20px;
        }

        .property-title {
            font-size: 18px;
            font-weight: 700;
            color: #0a1931;
            margin-bottom: 10px;
        }

        .property-price {
            font-size: 24px;
            font-weight: 700;
            color: #00de55;
            margin-top: 10px;
        }

        .contract-file {
            background: #0a1931;
            color: white;
            padding: 12px 20px;
            border-radius: 6px;
            display: inline-block;
            text-decoration: none;
            font-weight: 600;
            margin-top: 15px;
        }

        .contract-file:hover {
            background: #1e3a5f;
        }

        .no-contracts {
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

        .dates-info {
            display: flex;
            gap: 30px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
        }

        .date-item {
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>📄 Mis Contratos</h1>
            <a href="dashboard.php" class="btn-back">← Volver al Dashboard</a>
        </div>
    </div>

    <div class="container">
        <?php if (isset($error)): ?>
            <div class="alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?= count($activos) ?></div>
                <div class="stat-label">Contratos Activos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= count($finalizados) ?></div>
                <div class="stat-label">Contratos Finalizados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= count($contratos) ?></div>
                <div class="stat-label">Total de Contratos</div>
            </div>
        </div>

        <!-- Contratos Activos -->
        <div class="section">
            <h2>✅ Contratos Activos</h2>
            <?php if (empty($activos)): ?>
                <div class="no-contracts">No tienes contratos activos</div>
            <?php else: ?>
                <?php foreach ($activos as $contrato): ?>
                    <div class="contract-card activo">
                        <div class="contract-header">
                            <div>
                                <div class="contract-title">Contrato de <?= htmlspecialchars($contrato['tipo_contrato']) ?></div>
                                <div class="contract-id">ID: #<?= str_pad($contrato['id_contrato'], 4, '0', STR_PAD_LEFT) ?></div>
                            </div>
                            <span class="contract-status status-activo">Activo</span>
                        </div>

                        <div class="contract-info">
                            <div class="info-item">
                                <div class="info-label">📅 Fecha Inicio</div>
                                <div class="info-value"><?= formatDate($contrato['fecha_inicio']) ?></div>
                            </div>
                            <?php if ($contrato['fecha_fin']): ?>
                            <div class="info-item">
                                <div class="info-label">📅 Fecha Fin</div>
                                <div class="info-value"><?= formatDate($contrato['fecha_fin']) ?></div>
                            </div>
                            <?php endif; ?>
                            <div class="info-item">
                                <div class="info-label">💰 Valor del Contrato</div>
                                <div class="info-value"><?= formatCurrency($contrato['valor_contrato']) ?></div>
                            </div>
                            <?php if ($contrato['agente_nombre']): ?>
                            <div class="info-item">
                                <div class="info-label">👔 Agente Asignado</div>
                                <div class="info-value"><?= htmlspecialchars($contrato['agente_nombre']) ?></div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="contract-property">
                            <div class="property-title">🏠 <?= htmlspecialchars($contrato['tipo_inmueble']) ?></div>
                            <div class="info-value">📍 <?= htmlspecialchars($contrato['direccion']) ?>, <?= htmlspecialchars($contrato['ciudad']) ?></div>
                            <div class="property-price"><?= formatCurrency($contrato['precio_inmueble']) ?></div>
                        </div>

                        <?php if ($contrato['observaciones']): ?>
                            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
                                <strong>Observaciones:</strong>
                                <p style="color: #666; margin-top: 5px;"><?= htmlspecialchars($contrato['observaciones']) ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ($contrato['archivo_contrato']): ?>
                            <a href="../uploads/contracts/<?= htmlspecialchars($contrato['archivo_contrato']) ?>"
                               class="contract-file" target="_blank">
                                📥 Descargar Contrato
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if (!empty($borradores)): ?>
        <!-- Contratos en Borrador -->
        <div class="section">
            <h2>📝 Contratos en Borrador</h2>
            <?php foreach ($borradores as $contrato): ?>
                <div class="contract-card borrador">
                    <div class="contract-header">
                        <div>
                            <div class="contract-title">Contrato de <?= htmlspecialchars($contrato['tipo_contrato']) ?></div>
                            <div class="contract-id">ID: #<?= str_pad($contrato['id_contrato'], 4, '0', STR_PAD_LEFT) ?></div>
                        </div>
                        <span class="contract-status status-borrador">Borrador</span>
                    </div>

                    <div class="contract-property">
                        <div class="property-title">🏠 <?= htmlspecialchars($contrato['tipo_inmueble']) ?></div>
                        <div class="info-value">📍 <?= htmlspecialchars($contrato['direccion']) ?>, <?= htmlspecialchars($contrato['ciudad']) ?></div>
                    </div>

                    <p style="margin-top: 15px; color: #856404; background: #fff8e1; padding: 10px; border-radius: 4px;">
                        ⚠️ Este contrato está pendiente de formalización. Contacta con tu agente para más información.
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Historial de Contratos -->
        <div class="section">
            <h2>📋 Historial de Contratos</h2>
            <?php if (empty($finalizados) && empty($cancelados)): ?>
                <div class="no-contracts">No hay contratos finalizados o cancelados</div>
            <?php else: ?>
                <?php foreach (array_merge($finalizados, $cancelados) as $contrato): ?>
                    <div class="contract-card <?= strtolower($contrato['estado']) ?>">
                        <div class="contract-header">
                            <div>
                                <div class="contract-title">Contrato de <?= htmlspecialchars($contrato['tipo_contrato']) ?></div>
                                <div class="contract-id">ID: #<?= str_pad($contrato['id_contrato'], 4, '0', STR_PAD_LEFT) ?></div>
                            </div>
                            <span class="contract-status status-<?= strtolower($contrato['estado']) ?>">
                                <?= htmlspecialchars($contrato['estado']) ?>
                            </span>
                        </div>

                        <div class="contract-info">
                            <div class="info-item">
                                <div class="info-label">📅 Fecha Inicio</div>
                                <div class="info-value"><?= formatDate($contrato['fecha_inicio']) ?></div>
                            </div>
                            <?php if ($contrato['fecha_fin']): ?>
                            <div class="info-item">
                                <div class="info-label">📅 Fecha Fin</div>
                                <div class="info-value"><?= formatDate($contrato['fecha_fin']) ?></div>
                            </div>
                            <?php endif; ?>
                            <div class="info-item">
                                <div class="info-label">💰 Valor</div>
                                <div class="info-value"><?= formatCurrency($contrato['valor_contrato']) ?></div>
                            </div>
                        </div>

                        <div class="contract-property">
                            <div class="property-title">🏠 <?= htmlspecialchars($contrato['tipo_inmueble']) ?></div>
                            <div class="info-value">📍 <?= htmlspecialchars($contrato['direccion']) ?>, <?= htmlspecialchars($contrato['ciudad']) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
