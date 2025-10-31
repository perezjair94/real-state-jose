<?php
/**
 * Gestión de Usuarios - Inmuebles del Sinú
 * Listado y administración de usuarios del sistema
 */

define('APP_ACCESS', true);

require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

initSession();
requireRole('admin');

$user = getCurrentUser();

// Get all users with optional filters
$filterRol = $_GET['rol'] ?? '';
$filterEstado = $_GET['estado'] ?? '';

try {
    $db = new Database();
    $pdo = $db->getConnection();

    // Build query with filters
    $sql = "SELECT
                u.id_usuario,
                u.username,
                u.email,
                u.nombre_completo,
                u.rol,
                u.activo,
                u.intentos_login,
                u.bloqueado_hasta,
                u.ultimo_acceso,
                u.created_at,
                c.nombre as cliente_nombre,
                c.apellido as cliente_apellido
            FROM usuarios u
            LEFT JOIN cliente c ON u.id_cliente = c.id_cliente
            WHERE 1=1";

    $params = [];

    if ($filterRol) {
        $sql .= " AND u.rol = ?";
        $params[] = $filterRol;
    }

    if ($filterEstado === 'activo') {
        $sql .= " AND u.activo = 1";
    } elseif ($filterEstado === 'inactivo') {
        $sql .= " AND u.activo = 0";
    } elseif ($filterEstado === 'bloqueado') {
        $sql .= " AND u.bloqueado_hasta IS NOT NULL AND u.bloqueado_hasta > NOW()";
    }

    $sql .= " ORDER BY u.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get statistics
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE activo = 1");
    $totalActivos = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'admin'");
    $totalAdmins = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'cliente'");
    $totalClientes = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE bloqueado_hasta IS NOT NULL AND bloqueado_hasta > NOW()");
    $totalBloqueados = $stmt->fetch()['total'];

} catch (PDOException $e) {
    error_log("Error fetching users: " . $e->getMessage());
    $usuarios = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - <?= APP_NAME ?></title>
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

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .btn-back {
            background: #666;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.3s;
        }

        .btn-back:hover {
            background: #555;
        }

        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px;
        }

        /* Page Header */
        .page-header {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header h2 {
            color: #0a1931;
            font-size: 28px;
        }

        .btn-primary {
            background: #00de55;
            color: white;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 16px;
            font-weight: 600;
            transition: background 0.3s;
            display: inline-block;
        }

        .btn-primary:hover {
            background: #00aa41;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-card .value {
            font-size: 32px;
            font-weight: 700;
            color: #0a1931;
            margin-bottom: 5px;
        }

        .stat-card .label {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
        }

        /* Filters */
        .filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .filters form {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filters select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: 'Oswald', sans-serif;
            font-size: 14px;
        }

        .filters button {
            background: #0a1931;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-family: 'Oswald', sans-serif;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.3s;
        }

        .filters button:hover {
            background: #051020;
        }

        .btn-clear {
            background: #666 !important;
        }

        .btn-clear:hover {
            background: #555 !important;
        }

        /* Table */
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #0a1931;
            color: white;
        }

        thead th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
        }

        tbody tr {
            border-bottom: 1px solid #eee;
            transition: background 0.2s;
        }

        tbody tr:hover {
            background: #f8f9fa;
        }

        tbody td {
            padding: 15px;
            font-size: 14px;
        }

        /* Badges */
        .badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
        }

        .badge-admin {
            background: #e94545;
            color: white;
        }

        .badge-cliente {
            background: #5cb85c;
            color: white;
        }

        .badge-activo {
            background: #00de55;
            color: white;
        }

        .badge-inactivo {
            background: #999;
            color: white;
        }

        .badge-bloqueado {
            background: #ff9800;
            color: white;
        }

        /* Action Buttons */
        .actions {
            display: flex;
            gap: 8px;
        }

        .btn-action {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-edit {
            background: #00de55;
            color: white;
        }

        .btn-edit:hover {
            background: #00aa41;
        }

        .btn-toggle {
            background: #ff9800;
            color: white;
        }

        .btn-toggle:hover {
            background: #e68900;
        }

        .btn-unlock {
            background: #2196f3;
            color: white;
        }

        .btn-unlock:hover {
            background: #0b7dda;
        }

        .btn-delete {
            background: #e94545;
            color: white;
        }

        .btn-delete:hover {
            background: #c73838;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 24px;
            margin-bottom: 10px;
        }

        /* Messages */
        .message {
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .message-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .message-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1>🔐 Gestión de Usuarios</h1>
        <div class="user-info">
            <a href="dashboard.php" class="btn-back">← Volver al Dashboard</a>
        </div>
    </div>

    <div class="container">
        <?php displayFlashMessage(); ?>

        <div class="page-header">
            <h2>Usuarios del Sistema</h2>
            <a href="usuarios_crear.php" class="btn-primary">+ Crear Usuario</a>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="value"><?= $totalActivos ?></div>
                <div class="label">Usuarios Activos</div>
            </div>
            <div class="stat-card">
                <div class="value"><?= $totalAdmins ?></div>
                <div class="label">Administradores</div>
            </div>
            <div class="stat-card">
                <div class="value"><?= $totalClientes ?></div>
                <div class="label">Clientes</div>
            </div>
            <div class="stat-card">
                <div class="value"><?= $totalBloqueados ?></div>
                <div class="label">Bloqueados</div>
            </div>
        </div>

        <div class="filters">
            <form method="GET" action="">
                <label>Rol:</label>
                <select name="rol">
                    <option value="">Todos</option>
                    <option value="admin" <?= $filterRol === 'admin' ? 'selected' : '' ?>>Administrador</option>
                    <option value="cliente" <?= $filterRol === 'cliente' ? 'selected' : '' ?>>Cliente</option>
                </select>

                <label>Estado:</label>
                <select name="estado">
                    <option value="">Todos</option>
                    <option value="activo" <?= $filterEstado === 'activo' ? 'selected' : '' ?>>Activo</option>
                    <option value="inactivo" <?= $filterEstado === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                    <option value="bloqueado" <?= $filterEstado === 'bloqueado' ? 'selected' : '' ?>>Bloqueado</option>
                </select>

                <button type="submit">Filtrar</button>
                <a href="usuarios.php" class="btn-action btn-clear">Limpiar</a>
            </form>
        </div>

        <div class="table-container">
            <?php if (count($usuarios) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Nombre Completo</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Último Acceso</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                            <?php
                                $isBloqueado = $usuario['bloqueado_hasta'] && strtotime($usuario['bloqueado_hasta']) > time();
                                $nombreCompleto = $usuario['nombre_completo'];
                                if ($usuario['cliente_nombre']) {
                                    $nombreCompleto .= " ({$usuario['cliente_nombre']} {$usuario['cliente_apellido']})";
                                }
                            ?>
                            <tr>
                                <td><?= $usuario['id_usuario'] ?></td>
                                <td><strong><?= htmlspecialchars($usuario['username']) ?></strong></td>
                                <td><?= htmlspecialchars($nombreCompleto) ?></td>
                                <td><?= htmlspecialchars($usuario['email']) ?></td>
                                <td>
                                    <span class="badge badge-<?= $usuario['rol'] ?>">
                                        <?= $usuario['rol'] === 'admin' ? 'Administrador' : 'Cliente' ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($isBloqueado): ?>
                                        <span class="badge badge-bloqueado" title="Bloqueado hasta: <?= $usuario['bloqueado_hasta'] ?>">
                                            🔒 Bloqueado
                                        </span>
                                    <?php elseif ($usuario['activo']): ?>
                                        <span class="badge badge-activo">✓ Activo</span>
                                    <?php else: ?>
                                        <span class="badge badge-inactivo">✗ Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($usuario['ultimo_acceso']): ?>
                                        <?= date('d/m/Y H:i', strtotime($usuario['ultimo_acceso'])) ?>
                                    <?php else: ?>
                                        <span style="color: #999;">Nunca</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a href="usuarios_editar.php?id=<?= $usuario['id_usuario'] ?>"
                                           class="btn-action btn-edit"
                                           title="Editar usuario">
                                            ✏️
                                        </a>

                                        <?php if ($isBloqueado): ?>
                                            <button onclick="desbloquearUsuario(<?= $usuario['id_usuario'] ?>)"
                                                    class="btn-action btn-unlock"
                                                    title="Desbloquear usuario">
                                                🔓
                                            </button>
                                        <?php endif; ?>

                                        <button onclick="toggleUsuario(<?= $usuario['id_usuario'] ?>, <?= $usuario['activo'] ? 'false' : 'true' ?>)"
                                                class="btn-action btn-toggle"
                                                title="<?= $usuario['activo'] ? 'Desactivar' : 'Activar' ?> usuario">
                                            <?= $usuario['activo'] ? '⏸️' : '▶️' ?>
                                        </button>

                                        <?php if ($usuario['id_usuario'] != $user['id']): ?>
                                            <button onclick="eliminarUsuario(<?= $usuario['id_usuario'] ?>, '<?= htmlspecialchars($usuario['username']) ?>')"
                                                    class="btn-action btn-delete"
                                                    title="Eliminar usuario">
                                                🗑️
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <div class="icon">👥</div>
                    <h3>No se encontraron usuarios</h3>
                    <p>Intenta ajustar los filtros o crea un nuevo usuario</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleUsuario(id, activar) {
            const accion = activar ? 'activar' : 'desactivar';
            if (!confirm(`¿Estás seguro de ${accion} este usuario?`)) {
                return;
            }

            fetch('usuarios_ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=toggle&id=${id}&activo=${activar ? 1 : 0}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error al procesar la solicitud');
                console.error(error);
            });
        }

        function desbloquearUsuario(id) {
            if (!confirm('¿Desbloquear este usuario?')) {
                return;
            }

            fetch('usuarios_ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=unlock&id=${id}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error al procesar la solicitud');
                console.error(error);
            });
        }

        function eliminarUsuario(id, username) {
            if (!confirm(`¿ELIMINAR permanentemente el usuario "${username}"?\n\nEsta acción no se puede deshacer.`)) {
                return;
            }

            fetch('usuarios_ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=delete&id=${id}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error al procesar la solicitud');
                console.error(error);
            });
        }
    </script>
</body>
</html>
