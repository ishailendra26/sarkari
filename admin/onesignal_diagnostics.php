<?php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/helpers.php';

requireLogin();
requireAdmin();

$logDir = __DIR__ . '/../logs';
$apiLog = $logDir . '/onesignal.log';
$subLog = $logDir . '/onesignal_subscriptions.log';

function tail_file($file, $lines = 100) {
    if (!file_exists($file)) return '';
    $data = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$data) return '';
    $start = max(0, count($data) - $lines);
    return implode("\n", array_slice($data, $start));
}

$pageTitle = 'OneSignal Diagnostics';
include 'includes/header.php';
?>
<div class="max-w-5xl mx-auto p-6">
  <h1 class="text-2xl font-bold mb-4">OneSignal Diagnostics</h1>

  <div class="mb-6 bg-white p-4 rounded shadow">
    <h2 class="font-semibold mb-2">Configuration</h2>
    <ul class="text-sm text-gray-700">
      <li><strong>SITE_URL:</strong> <?= htmlspecialchars(SITE_URL) ?></li>
      <li><strong>Computed SW paths:</strong>
        <?php
          $parsedPath = rtrim(parse_url(SITE_URL, PHP_URL_PATH) ?: '/', '/');
          $scopePath = $parsedPath === '' ? '/' : ($parsedPath . '/');
          $swPath = $scopePath . 'OneSignalSDKWorker.js';
          $swUpdaterPath = $scopePath . 'OneSignalSDKUpdaterWorker.js';
        ?>
        <div class="text-xs text-gray-600 mt-1">
          <div>serviceWorkerPath: <?= htmlspecialchars($swPath) ?></div>
          <div>serviceWorkerUpdaterPath: <?= htmlspecialchars($swUpdaterPath) ?></div>
        </div>
      </li>
      <li><strong>OneSignal App ID:</strong> <?= htmlspecialchars(getSetting('onesignal_app_id') ?? '') ?></li>
      <li><strong>OneSignal Subdomain:</strong> <?= htmlspecialchars(getSetting('onesignal_subdomain') ?? '') ?></li>
    </ul>
  </div>

  <div class="grid md:grid-cols-2 gap-6">
    <div class="bg-white p-4 rounded shadow">
      <h2 class="font-semibold mb-2">Recent OneSignal API Logs (onesignal.log)</h2>
      <pre class="text-xs text-gray-800 max-h-96 overflow-auto bg-gray-50 p-3 rounded"><?= htmlspecialchars(tail_file($apiLog, 200)) ?></pre>
    </div>

    <div class="bg-white p-4 rounded shadow">
      <h2 class="font-semibold mb-2">Recent Subscription Diagnostics (onesignal_subscriptions.log)</h2>
      <pre class="text-xs text-gray-800 max-h-96 overflow-auto bg-gray-50 p-3 rounded"><?= htmlspecialchars(tail_file($subLog, 200)) ?></pre>
    </div>
  </div>

  <div class="mt-6 bg-white p-4 rounded shadow">
    <p class="text-sm text-gray-700">Notes:</p>
    <ul class="text-sm text-gray-600 list-disc pl-5 mt-2">
      <li>If <code>onesignal.log</code> shows "All included players are not subscribed", it means OneSignal had no subscribed devices for the requested segment.</li>
      <li>Use the public site (non-admin) to subscribe with a regular browser. Then re-run the test notification from Admin → Settings.</li>
      <li>If diagnostics entries appear in <code>onesignal_subscriptions.log</code> with <code>isEnabled: true</code> and a <code>userId</code>, the admin test should deliver successfully.</li>
    </ul>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
