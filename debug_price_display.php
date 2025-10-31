<?php
/**
 * Debug script to verify price display
 */

define('APP_ACCESS', true);
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $db = new Database();
    $pdo = $db->getConnection();

    // Get a sample property
    $stmt = $pdo->prepare("SELECT * FROM inmueble LIMIT 1");
    $stmt->execute();
    $property = $stmt->fetch();

    echo "<h1>Price Display Debug</h1>";
    echo "<hr>";

    echo "<h2>Property Data from Database:</h2>";
    echo "<pre>";
    print_r($property);
    echo "</pre>";

    echo "<hr>";
    echo "<h2>Price Field Value:</h2>";
    echo "<p><strong>Raw value:</strong> " . var_export($property['precio'], true) . "</p>";
    echo "<p><strong>Type:</strong> " . gettype($property['precio']) . "</p>";

    echo "<hr>";
    echo "<h2>formatCurrency() Output:</h2>";
    echo "<p>" . formatCurrency($property['precio']) . "</p>";

    echo "<hr>";
    echo "<h2>HTML Rendering Test:</h2>";
    echo '<div class="card-body">';
    echo '    <p class="price">' . formatCurrency($property['precio']) . '</p>';
    echo '</div>';

    echo "<hr>";
    echo "<h2>number_format() Direct Test:</h2>";
    echo "<p>" . number_format($property['precio'], 0, ',', '.') . "</p>";

} catch (Exception $e) {
    echo "<h1>Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>

<style>
.card-body .price {
    font-weight: 600;
    font-size: 22px;
    margin-bottom: 15px;
    color: #00de55;
}
</style>
