<?php
require_once 'config/config.php';
$pdo = db();

try {
    $stmt = $pdo->query("DESCRIBE categories");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Kolom tabel categories: " . implode(", ", $columns) . "\n";
    
    // Show full details
    $stmt = $pdo->query("DESCRIBE categories");
    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($details);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>