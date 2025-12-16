<?php
require_once 'src/config.php';
require_once 'src/db.php';
require_once 'src/helpers.php';

$db = getDB();
$settings = [];
$stmt = $db->query("SELECT * FROM settings WHERE k LIKE 'onesignal_%'");
while ($row = $stmt->fetch()) {
    $settings[$row['k']] = $row['v'];
}

header('Content-Type: application/json');
echo json_encode($settings, JSON_PRETTY_PRINT);
