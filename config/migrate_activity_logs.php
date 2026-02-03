<?php
require_once 'config.php';

$pdo = db();

try {
    $sql = "CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        username VARCHAR(100) NOT NULL,
        action_type VARCHAR(50) NOT NULL,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sql);
    echo "Table 'activity_logs' created successfully.<br>";
    
    // Add index for faster cleanup
    $pdo->exec("CREATE INDEX idx_created_at ON activity_logs(created_at)");
    echo "Index created.<br>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>