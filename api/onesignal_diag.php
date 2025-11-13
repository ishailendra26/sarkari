<?php
// Lightweight endpoint to accept OneSignal client diagnostics and log them.
// Expects JSON POST from the site (same-origin).

require_once __DIR__ . '/../src/config.php';

// Simple write to logs/onesignal_subscriptions.log
function _diag_log($data) {
    try {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
        $logFile = $logDir . '/onesignal_subscriptions.log';
        $entry = '[' . date('c') . '] ' . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
        file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    } catch (Exception $e) {
        // ignore
    }
}

// Allow only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit();
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    $data = ['raw' => $raw];
}

$data['_remote_ip'] = $_SERVER['REMOTE_ADDR'] ?? null;
$data['_ua'] = $_SERVER['HTTP_USER_AGENT'] ?? null;
$data['_ref'] = $_SERVER['HTTP_REFERER'] ?? null;

_diag_log($data);

// Minimal response
header('Content-Type: application/json');
echo json_encode(['ok' => true]);
exit();
