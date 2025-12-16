<?php
require_once 'src/config.php';
require_once 'src/db.php';
require_once 'src/helpers.php';
require_once 'src/notifications.php';

echo "<h2>OneSignal Send Test</h2>";

// Enable strict error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

$res = onesignal_send_notification(
    "Debug Test Notification", 
    "This is a manual test from debug_send.php at " . date('H:i:s'), 
    SITE_URL . '/test_os.html' // Using test page as target
);

echo "<h3>Result:</h3>";
echo "<pre>";
print_r($res);
echo "</pre>";

echo "<h3>Logs (last 5 entries):</h3>";
$logFile = __DIR__ . '/logs/onesignal.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    $last5 = array_slice($lines, -5);
    foreach ($last5 as $l) echo htmlspecialchars($l) . "<br>";
} else {
    echo "Log file not found.";
}
