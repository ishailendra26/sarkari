<?php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/db.php';

$db = getDB();
$conn = $db->getConnection();

try {
    echo "Adding 'vacancy_details' column to 'jobs' table...\n";
    
    // Check if column exists
    $check = $conn->query("SHOW COLUMNS FROM jobs LIKE 'vacancy_details'");
    if ($check->rowCount() == 0) {
        // Adding it after 'fees' which was added in the previous step
        $sql = "ALTER TABLE jobs ADD COLUMN vacancy_details LONGTEXT DEFAULT NULL AFTER fees";
        $conn->exec($sql);
        echo "Column 'vacancy_details' added successfully.\n";
    } else {
        echo "Column 'vacancy_details' already exists.\n";
    }
    
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
