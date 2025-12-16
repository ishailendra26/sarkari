<?php
require_once 'src/config.php';
require_once 'src/db.php';
require_once 'src/helpers.php';

echo "Onesignal Enabled: " . getSetting('onesignal_enabled') . "\n";
echo "Type: " . gettype(getSetting('onesignal_enabled')) . "\n";
echo "Cast to Int: " . (int)getSetting('onesignal_enabled') . "\n";
echo "Test Check: " . ((int)getSetting('onesignal_enabled', 0) === 1 ? 'TRUE' : 'FALSE') . "\n";
