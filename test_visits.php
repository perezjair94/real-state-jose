<?php
/**
 * Test script to verify visits data
 */

define('APP_ACCESS', true);

require_once 'config/constants.php';
require_once 'config/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();

    echo "<h2>Test de Conexión y Datos de Visitas</h2>";

    // Test 1: Count visits
    echo "<h3>1. Total de visitas en la base de datos:</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM visita");
    $result = $stmt->fetch();
    echo "Total de visitas: " . $result['total'] . "<br>";

    // Test 2: Check related tables
    echo "<h3>2. Verificar tablas relacionadas:</h3>";

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM inmueble");
    $result = $stmt->fetch();
    echo "Total de inmuebles: " . $result['total'] . "<br>";

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM cliente");
    $result = $stmt->fetch();
    echo "Total de clientes: " . $result['total'] . "<br>";

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM agente WHERE activo = 1");
    $result = $stmt->fetch();
    echo "Total de agentes activos: " . $result['total'] . "<br>";

    // Test 3: Get all visits with details
    echo "<h3>3. Visitas con detalles:</h3>";
    $sql = "SELECT v.id_visita, v.fecha_visita, v.hora_visita, v.estado,
                   i.tipo_inmueble, i.direccion,
                   CONCAT(cl.nombre, ' ', cl.apellido) as cliente_nombre,
                   ag.nombre as agente_nombre
            FROM visita v
            LEFT JOIN inmueble i ON v.id_inmueble = i.id_inmueble
            LEFT JOIN cliente cl ON v.id_cliente = cl.id_cliente
            LEFT JOIN agente ag ON v.id_agente = ag.id_agente
            ORDER BY v.fecha_visita DESC";

    $stmt = $pdo->query($sql);
    $visits = $stmt->fetchAll();

    if (empty($visits)) {
        echo "<p style='color: red;'>No se encontraron visitas en la base de datos.</p>";
        echo "<p>Verifique que el archivo database/seed.sql haya sido ejecutado.</p>";
    } else {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>Fecha</th><th>Hora</th><th>Estado</th><th>Inmueble</th><th>Cliente</th><th>Agente</th></tr>";
        foreach ($visits as $visit) {
            echo "<tr>";
            echo "<td>" . $visit['id_visita'] . "</td>";
            echo "<td>" . $visit['fecha_visita'] . "</td>";
            echo "<td>" . $visit['hora_visita'] . "</td>";
            echo "<td>" . $visit['estado'] . "</td>";
            echo "<td>" . ($visit['tipo_inmueble'] ?? 'NULL') . " - " . ($visit['direccion'] ?? 'NULL') . "</td>";
            echo "<td>" . ($visit['cliente_nombre'] ?? 'NULL') . "</td>";
            echo "<td>" . ($visit['agente_nombre'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // Test 4: Check for orphaned records
    echo "<h3>4. Verificar registros huérfanos:</h3>";

    $sql = "SELECT v.id_visita, v.id_inmueble, v.id_cliente, v.id_agente
            FROM visita v
            LEFT JOIN inmueble i ON v.id_inmueble = i.id_inmueble
            LEFT JOIN cliente cl ON v.id_cliente = cl.id_cliente
            LEFT JOIN agente ag ON v.id_agente = ag.id_agente
            WHERE i.id_inmueble IS NULL OR cl.id_cliente IS NULL OR ag.id_agente IS NULL";

    $stmt = $pdo->query($sql);
    $orphaned = $stmt->fetchAll();

    if (empty($orphaned)) {
        echo "<p style='color: green;'>✓ No hay registros huérfanos.</p>";
    } else {
        echo "<p style='color: red;'>✗ Se encontraron registros con claves foráneas inválidas:</p>";
        echo "<pre>";
        print_r($orphaned);
        echo "</pre>";
    }

} catch (PDOException $e) {
    echo "<p style='color: red;'>Error de base de datos: " . $e->getMessage() . "</p>";
    echo "<p>Verifique que:</p>";
    echo "<ul>";
    echo "<li>MySQL esté corriendo</li>";
    echo "<li>La base de datos 'real_estate_db' exista</li>";
    echo "<li>Las credenciales en config/database.php sean correctas</li>";
    echo "<li>Las tablas hayan sido creadas (ejecutar database/schema.sql)</li>";
    echo "</ul>";
}
?>
