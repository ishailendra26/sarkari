<?php
require_once __DIR__ . '/src/config.php';
require_once __DIR__ . '/src/db.php';
require_once __DIR__ . '/src/helpers.php';

echo "onesignal_enabled: " . getSetting('onesignal_enabled') . "\n";
echo "onesignal_app_id: " . getSetting('onesignal_app_id') . "\n";
