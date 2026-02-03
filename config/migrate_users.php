<?php
require_once 'config.php';
$pdo = db();

$cols = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('name', $cols)) {
    $pdo->exec("ALTER TABLE users ADD COLUMN name VARCHAR(100) NOT NULL DEFAULT 'Admin'");
    echo "Added 'name' column.\n";
} else {
    echo "'name' column already exists.\n";
}

if (!in_array('avatar', $cols)) {
    $pdo->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT NULL");
    echo "Added 'avatar' column.\n";
} else {
    echo "'avatar' column already exists.\n";
}
echo "Migration complete.\n";
