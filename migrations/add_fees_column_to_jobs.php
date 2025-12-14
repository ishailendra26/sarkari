<?php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/db.php';

$db = getDB();
$conn = $db->getConnection();

try {
    echo "Adding 'fees' column to 'jobs' table...\n";
    
    // Check if column exists
    $check = $conn->query("SHOW COLUMNS FROM jobs LIKE 'fees'");
    if ($check->rowCount() == 0) {
        $sql = "ALTER TABLE jobs ADD COLUMN fees LONGTEXT DEFAULT NULL AFTER content";
        $conn->exec($sql);
        echo "Column 'fees' added successfully.\n";
    } else {
        echo "Column 'fees' already exists.\n";
    }
    
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
