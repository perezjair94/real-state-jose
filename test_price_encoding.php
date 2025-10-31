<?php
/**
 * Test price encoding and display
 */

define('APP_ACCESS', true);
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $db = new Database();
    $pdo = $db->getConnection();

    // Get all properties
    $stmt = $pdo->prepare("SELECT id_inmueble, ciudad, precio FROM inmueble ORDER BY id_inmueble LIMIT 10");
    $stmt->execute();
    $properties = $stmt->fetchAll();

    echo "<!DOCTYPE html>";
    echo "<html><head><meta charset='utf-8'>";
    echo "<style>";
    echo "table { border-collapse: collapse; width: 100%; }";
    echo "th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }";
    echo "th { background-color: #4CAF50; color: white; }";
    echo "tr:nth-child(even) { background-color: #f2f2f2; }";
    echo "</style>";
    echo "</head><body>";

    echo "<h1>Price Encoding Test</h1>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Ciudad</th><th>Precio Raw</th><th>Precio Type</th><th>formatCurrency()</th><th>number_format()</th></tr>";

    foreach ($properties as $property) {
        echo "<tr>";
        echo "<td>" . $property['id_inmueble'] . "</td>";
        echo "<td>" . htmlspecialchars($property['ciudad']) . "</td>";
        echo "<td>" . var_export($property['precio'], true) . "</td>";
        echo "<td>" . gettype($property['precio']) . "</td>";
        echo "<td>" . formatCurrency($property['precio']) . "</td>";
        echo "<td>$ " . number_format($property['precio'], 0, ',', '.') . "</td>";
        echo "</tr>";
    }

    echo "</table>";

    echo "<hr>";
    echo "<h2>Test Single Property Card Display:</h2>";

    $testProperty = $properties[0];
    echo '<div style="border: 1px solid #ccc; padding: 20px; max-width: 300px;">';
    echo '<h3 class="title">' . htmlspecialchars($testProperty['ciudad']) . '</h3>';
    echo '<p class="price" style="font-weight: 600; font-size: 22px; color: #00de55;">';
    echo formatCurrency($testProperty['precio']);
    echo '</p>';
    echo '</div>';

    echo "<hr>";
    echo "<h2>Character Analysis of First Price:</h2>";
    $priceString = formatCurrency($testProperty['precio']);
    echo "<p>String: '" . $priceString . "'</p>";
    echo "<p>Length: " . strlen($priceString) . " characters</p>";
    echo "<p>Character codes:</p>";
    echo "<pre>";
    for ($i = 0; $i < strlen($priceString); $i++) {
        $char = substr($priceString, $i, 1);
        echo "Position $i: '$char' (ASCII " . ord($char) . ")\n";
    }
    echo "</pre>";

    echo "</body></html>";

} catch (Exception $e) {
    echo "<h1>Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
