<?php
// OneSignal notifications helper
// Requires settings stored in DB via getSetting('onesignal_app_id') and getSetting('onesignal_api_key')

require_once __DIR__ . '/helpers.php';

// Simple logger for OneSignal requests/responses
function _onesignal_log(array $data) {
    try {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
        $logFile = $logDir . '/onesignal.log';
        $entry = '[' . date('c') . '] ' . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
        file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    } catch (Exception $e) {
        // best-effort logging
    }
}

/**
 * Send a OneSignal notification.
 *
 * @param string $heading Notification heading/title.
 * @param string $content Notification message/body.
 * @param string $url URL to open when clicked.
 * @param string|null $image Optional large image/thumbnail URL.
 * @param array $extraFields Extra OneSignal fields to merge.
 * @return array [success => bool, status => int, response => mixed]
 */
function onesignal_send_notification(string $heading, string $content, string $url, ?string $image = null, array $extraFields = []): array {
    $appId = trim((string)getSetting('onesignal_app_id'));
    $apiKey = trim((string)getSetting('onesignal_api_key'));

    if ($appId === '' || $apiKey === '') {
        _onesignal_log(['error' => 'missing_config', 'app_id' => $appId, 'api_key_present' => $apiKey !== '']);
        return ['success' => false, 'status' => 0, 'response' => 'OneSignal App ID or REST API Key is missing'];
    }

    $payload = array_merge([
        'app_id' => $appId,
        'included_segments' => ['Subscribed Users'],
        'headings' => ['en' => $heading],
        'contents' => ['en' => $content],
        'url' => $url,
    ], $extraFields);

    if (!empty($image)) {
        $payload['chrome_web_image'] = $image; // Large image for Chrome
        $payload['chrome_web_icon'] = $image;  // Small icon
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://onesignal.com/api/v1/notifications');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json; charset=utf-8',
        'Authorization: Basic ' . $apiKey,
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

    $result = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    // Log payload/response for diagnostics
    _onesignal_log([
        'status' => $status,
        'curl_error' => $err ?: null,
        'payload' => $payload,
        'response_raw' => is_string($result) ? $result : json_encode($result),
    ]);

    if ($err) {
        return ['success' => false, 'status' => $status ?: 0, 'response' => $err];
    }

    $decoded = json_decode($result, true);
    $ok = $status >= 200 && $status < 300;
    return ['success' => $ok, 'status' => $status, 'response' => $decoded ?: $result];
}

/**
 * Notify for a new/published job.
 */
function onesignal_notify_job(array $job): array {
    $enabled = (int)getSetting('onesignal_enabled', 0);
    if (!$enabled) { return ['success' => false, 'status' => 0, 'response' => 'OneSignal not enabled']; }
    $title = $job['title'] ?? 'New Job Posted';
    $org = $job['organization'] ?? '';
    $message = trim($org) ? ("$org - New opening") : 'New job notification';
    $slug = $job['slug'] ?? '';
    $url = rtrim(SITE_URL, '/') . '/job/' . urlencode($slug);
    $image = $job['thumbnail_url'] ?? getSetting('default_post_thumbnail');
    return onesignal_send_notification($title, $message, $url, $image);
}

/**
 * Notify for a published result.
 */
function onesignal_notify_result(array $result): array {
    $enabled = (int)getSetting('onesignal_enabled', 0);
    if (!$enabled) { return ['success' => false, 'status' => 0, 'response' => 'OneSignal not enabled']; }
    $title = $result['title'] ?? 'Result Published';
    $org = $result['organization'] ?? '';
    $message = trim($org) ? ("$org - Result Out") : 'Check your result now';
    $slug = $result['slug'] ?? '';
    $url = rtrim(SITE_URL, '/') . '/result/' . urlencode($slug);
    $image = $result['thumbnail_url'] ?? getSetting('default_post_thumbnail');
    return onesignal_send_notification($title, $message, $url, $image);
}

/**
 * Notify for a published admit card.
 */
function onesignal_notify_admit(array $admit): array {
    $enabled = (int)getSetting('onesignal_enabled', 0);
    if (!$enabled) { return ['success' => false, 'status' => 0, 'response' => 'OneSignal not enabled']; }
    $title = $admit['title'] ?? 'Admit Card Released';
    $org = $admit['organization'] ?? '';
    $message = trim($org) ? ("$org - Admit Card Available") : 'Download admit card now';
    $slug = $admit['slug'] ?? '';
    $url = rtrim(SITE_URL, '/') . '/admit/' . urlencode($slug);
    $image = $admit['thumbnail_url'] ?? getSetting('default_post_thumbnail');
    return onesignal_send_notification($title, $message, $url, $image);
}

/**
 * Notify for a published syllabus.
 */
function onesignal_notify_syllabus(array $syllabus): array {
    $enabled = (int)getSetting('onesignal_enabled', 0);
    if (!$enabled) { return ['success' => false, 'status' => 0, 'response' => 'OneSignal not enabled']; }
    $title = $syllabus['title'] ?? 'Syllabus Published';
    $org = $syllabus['organization'] ?? '';
    $message = trim($org) ? ("$org - Syllabus Updated") : 'New syllabus available';
    $slug = $syllabus['slug'] ?? '';
    $url = rtrim(SITE_URL, '/') . '/syllabus/' . urlencode($slug);
    $image = $syllabus['thumbnail_url'] ?? getSetting('default_post_thumbnail');
    return onesignal_send_notification($title, $message, $url, $image);
}

/**
 * Convenience: Send notification for a newly published post (array row from DB).
 * Expects keys: title, slug, excerpt, thumbnail_url
 */
function onesignal_notify_post(array $post): array {
    $enabled = (int)getSetting('onesignal_enabled', 0);

    if (!$enabled) {
        return ['success' => false, 'status' => 0, 'response' => 'OneSignal not enabled'];
    }

    $title = $post['title'] ?? 'New Post';
    $message = $post['excerpt'] ?? '';
    if (!$message) {
        // Fallback to a short content excerpt if available
        $raw = strip_tags($post['content'] ?? '');
        $message = mb_substr(trim($raw), 0, 120);
        if (mb_strlen($raw) > 120) $message .= '...';
    }
    $slug = $post['slug'] ?? '';
    $url = rtrim(SITE_URL, '/') . '/post/' . urlencode($slug);
    $image = $post['thumbnail_url'] ?? getSetting('default_post_thumbnail');

    return onesignal_send_notification($title, $message, $url, $image);
}
