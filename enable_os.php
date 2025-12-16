<?php
require_once 'src/config.php';
require_once 'src/db.php';

$db = getDB();
$sql = "INSERT INTO settings (k, v) VALUES ('onesignal_enabled', '1') ON DUPLICATE KEY UPDATE v = '1'";
$db->query($sql);
echo "OneSignal enabled successfully.";
