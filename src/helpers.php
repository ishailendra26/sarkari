<?php
// Utility Functions

require_once __DIR__ . '/models/Ad.php';
require_once __DIR__ . '/db.php';

function slugify($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\-]/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    return trim($text, '-');
}

/**
 * Ensure slug uniqueness within a table by appending -2, -3, ... if needed.
 * Excludes a specific row by ID (useful for updates).
 *
 * @param string $table Table name (e.g., 'jobs')
 * @param string $baseSlug Slug candidate (usually from slugify(title))
 * @param int|null $excludeId Row ID to exclude from collision checks
 * @param string $idColumn Primary key column name (default 'id')
 * @return string Unique slug
 */
function generateUniqueSlug($table, $baseSlug, $excludeId = null, $idColumn = 'id') {
    // Try to get PDO connection if available
    $pdo = null;
    if (function_exists('getDB')) {
        $db = getDB();
        if ($db && method_exists($db, 'getConnection')) {
            $pdo = $db->getConnection();
        }
    } elseif (class_exists('Database')) {
        $db = Database::getInstance();
        if ($db && method_exists($db, 'getConnection')) {
            $pdo = $db->getConnection();
        }
    }

    // Fallback: if no DB available, just return base slug
    if (!$pdo) return $baseSlug;

    $slug = $baseSlug;
    $i = 1;
    while (true) {
        if ($excludeId) {
            $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM `{$table}` WHERE slug = ? AND `{$idColumn}` != ?");
            $stmt->execute([$slug, $excludeId]);
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM `{$table}` WHERE slug = ?");
            $stmt->execute([$slug]);
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $count = $row ? (int)$row['cnt'] : 0;
        if ($count === 0) {
            break;
        }
        $i++;
        $slug = $baseSlug . '-' . $i;
    }
    return $slug;
}

function excerpt($text, $length = 150) {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}

function formatDate($date, $format = 'd M Y', $outTzName = null) {
    if (!$date) {
        return '';
    }
    $inTzName = defined('DB_TIMEZONE') ? DB_TIMEZONE : 'UTC';
    $outTzName = $outTzName ?: (defined('APP_TIMEZONE') ? APP_TIMEZONE : $inTzName);
    try {
        $inTz = new DateTimeZone($inTzName);
    } catch (Exception $e) {
        $inTz = new DateTimeZone('UTC');
    }
    try {
        $outTz = new DateTimeZone($outTzName);
    } catch (Exception $e) {
        $outTz = $inTz;
    }

    $dt = false;
    // Support date-only and datetime strings. Treat naive values as DB timezone.
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$date)) {
        $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $date . ' 00:00:00', $inTz);
    } elseif (preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}$/', (string)$date)) {
        $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', str_replace('T', ' ', $date), $inTz);
    }
    if (!$dt) {
        try {
            $dt = new DateTimeImmutable((string)$date, $inTz);
        } catch (Exception $e) {
            return '';
        }
    }

    $dt = $dt->setTimezone($outTz);
    return $dt->format($format);
}

function timeAgo($datetime) {
    // Handle empty or invalid date values gracefully
    if (!$datetime) {
        return 'just now';
    }

    // Determine the timezone in which DB timestamps are stored (assume UTC unless configured)
    $dbTzName = defined('DB_TIMEZONE') ? DB_TIMEZONE : 'UTC';
    try {
        $dbTz = new DateTimeZone($dbTzName);
    } catch (Exception $e) {
        $dbTz = new DateTimeZone('UTC');
    }

    $ts = false;
    // Try parsing as a naive "Y-m-d H:i:s" in DB timezone first (common for MySQL DATETIME)
    if (preg_match('/^\d{4}-\d{2}-\d{2}(?:[ T]\d{2}:\d{2}:\d{2})?$/', (string)$datetime)) {
        // If only date provided, append midnight time to avoid strtotime quirks
        $dtString = strlen($datetime) === 10 ? $datetime . ' 00:00:00' : $datetime;
        $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dtString, $dbTz);
        if ($dt instanceof DateTimeImmutable) {
            $ts = $dt->getTimestamp();
        }
    }

    // Fallback: let PHP parse (may contain timezone info like Z or +05:30)
    if ($ts === false) {
        $dt = @new DateTimeImmutable($datetime, $dbTz);
        if ($dt) {
            $ts = $dt->getTimestamp();
        }
    }

    if ($ts === false) {
        return 'just now';
    }

    // Compare using the same timezone to avoid offsets
    $now = new DateTimeImmutable('now', $dbTz);
    $diff = $now->getTimestamp() - $ts; // seconds

    // Clamp future timestamps to 'just now' to avoid "in X" wording
    if ($diff <= 0) {
        return 'just now';
    }

    $units = [
        ['secs' => 31536000, 'singular' => 'year',   'plural' => 'years'],
        ['secs' => 2592000,  'singular' => 'month',  'plural' => 'months'],
        ['secs' => 86400,    'singular' => 'day',    'plural' => 'days'],
        ['secs' => 3600,     'singular' => 'hour',   'plural' => 'hours'],
        ['secs' => 60,       'singular' => 'minute', 'plural' => 'minutes'],
    ];

    foreach ($units as $u) {
        if ($diff >= $u['secs']) {
            $val = (int) floor($diff / $u['secs']);
            $label = ($val === 1) ? $u['singular'] : $u['plural'];
            return $val . ' ' . $label . ' ago';
        }
    }

    // Less than a minute
    return 'just now';
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function generateMetaTitle($title, $suffix = '') {
    $metaTitle = $title;
    if ($suffix) {
        $metaTitle .= ' - ' . $suffix;
    }
    return $metaTitle . ' | ' . SITE_NAME;
}

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function getCurrentUrl() {
    return (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
}

function uploadFile($file, $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf']) {
    if (!isset($file['tmp_name']) || !$file['tmp_name']) {
        return ['success' => false, 'message' => 'No file uploaded'];
    }

    $fileName = $file['name'];
    $fileSize = $file['size'];
    $fileTmp = $file['tmp_name'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (!in_array($fileExt, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }

    if ($fileSize > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File too large'];
    }

    $newFileName = uniqid() . '.' . $fileExt;
    $uploadPath = UPLOAD_PATH . $newFileName;

    if (!is_dir(UPLOAD_PATH)) {
        mkdir(UPLOAD_PATH, 0755, true);
    }

    if (move_uploaded_file($fileTmp, $uploadPath)) {
        return ['success' => true, 'filename' => $newFileName, 'url' => UPLOAD_URL . $newFileName];
    }

    return ['success' => false, 'message' => 'Upload failed'];
}

// Pagination helper
function paginate($totalItems, $currentPage = 1, $itemsPerPage = POSTS_PER_PAGE) {
    $totalPages = ceil($totalItems / $itemsPerPage);
    $currentPage = max(1, min($totalPages, $currentPage));
    $offset = ($currentPage - 1) * $itemsPerPage;
    
    return [
        'total_items' => $totalItems,
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'items_per_page' => $itemsPerPage,
        'offset' => $offset,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages,
        'prev_page' => $currentPage - 1,
        'next_page' => $currentPage + 1
    ];
}

// Session management
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function isLoggedIn() {
    startSession();
    return isset($_SESSION['admin_id']) && $_SESSION['admin_id'];
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('/admin/login.php');
    }
}

// Role/permission helpers
function currentUserRole() {
    startSession();
    return $_SESSION['admin_role'] ?? 'admin';
}

function isAdmin() {
    return currentUserRole() === 'admin';
}

function isEditor() {
    return currentUserRole() === 'editor';
}

function requireAdmin() {
    if (!isAdmin()) {
        // Editors (or others) cannot access this area
        // Redirect to admin dashboard with an error flag
        header('Location: /admin/index.php?forbidden=1');
        exit();
    }
}

// Granular permissions
function canDeleteContent() {
    // Only Admins can delete
    return isAdmin();
}

function canManageAds() {
    // Only Admins can manage ads
    return isAdmin();
}

function canManageSettings() {
    // Only Admins can manage settings
    return isAdmin();
}

function renderForbiddenAlert() {
    echo '<div class="alert alert-error mb-6"><i class="fas fa-exclamation-triangle mr-2"></i>Access denied</div>';
}

function logout() {
    startSession();
    session_destroy();
    redirect('/admin/login.php');
}

// Settings helpers (cached)
function getSettings($forceRefresh = false) {
    static $cache = null;
    if ($forceRefresh || $cache === null) {
        try {
            $db = getDB();
            $rows = $db->fetchAll("SELECT k, v FROM settings");
            $cache = [];
            foreach ($rows as $row) { $cache[$row['k']] = $row['v']; }
        } catch (Exception $e) {
            $cache = [];
        }
    }
    return $cache;
}

function getSetting($key, $default = null) {
    $all = getSettings();
    return array_key_exists($key, $all) ? $all[$key] : $default;
}

// Render author badge
function renderAuthorBadge($author) {
    if (!$author) return '';
    $name = htmlspecialchars($author['name']);
    $avatar = !empty($author['avatar_url']) ? $author['avatar_url'] : SITE_URL . '/assets/img/avatar-placeholder.png';
    $verified = !empty($author['verified']);
    $slug = htmlspecialchars($author['slug']);
    $url = SITE_URL . '/author/' . $slug;
    ob_start();
    ?>
    <a href="<?= $url ?>" class="inline-flex items-center gap-2 text-sm text-gray-700 hover:text-primary">
        <img src="<?= htmlspecialchars($avatar) ?>" alt="<?= $name ?>" class="w-6 h-6 rounded-full object-cover">
        <span class="font-medium"><?= $name ?></span>
        <?php if ($verified): ?>
            <i class="fas fa-check-circle text-blue-500" title="Verified"></i>
        <?php endif; ?>
    </a>
    <?php
    return ob_get_clean();
}

// Flexible extras renderer (expects JSON string or array)
function renderExtras($extras) {
    if (!$extras) return '';
    if (is_string($extras)) {
        $decoded = json_decode($extras, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $extras = $decoded;
        } else {
            return '';
        }
    }
    if (!is_array($extras)) return '';

    ob_start();
    // Icon mapping based on section title keywords
    $iconFor = function($titleRaw){
        $t = strtolower(trim($titleRaw));
        // normalize common variants
        if (strpos($t, 'date') !== false) return 'fa-calendar';
        if (strpos($t, 'fee') !== false) return 'fa-money-bill-wave';
        if (strpos($t, 'age limit') !== false || strpos($t, 'age') !== false) return 'fa-hourglass-half';
        if (strpos($t, 'vacancy') !== false || strpos($t, 'post') !== false) return 'fa-briefcase';
        if (strpos($t, 'how to apply') !== false) return 'fa-file-signature';
        if (strpos($t, 'mode of exam') !== false || strpos($t, 'exam mode') !== false) return 'fa-clipboard-check';
        if (strpos($t, 'link') !== false) return 'fa-link';
        if ($t === 'faq' || $t === 'faqs' || strpos($t, 'faq') !== false) return 'fa-question-circle';
        if (strpos($t, 'instruction') !== false || strpos($t, 'note') !== false) return 'fa-exclamation-triangle';
        if (strpos($t, 'timeline') !== false || strpos($t, 'event') !== false) return 'fa-stream';
        return 'fa-bookmark';
    };
    foreach ($extras as $sectionTitle => $content) {
        $title = htmlspecialchars($sectionTitle);
        $icon = $iconFor($sectionTitle);
        ?>
        <div class="mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-3 flex items-center">
                <i class="fas <?= $icon ?> text-primary mr-2"></i><?= $title ?>
            </h2>
            <div class="text-gray-700">
                <?php if (is_array($content)):
                    // Detect array of link objects {label, url}
                    $isLinks = false;
                    if (!empty($content)) {
                        $first = reset($content);
                        $isLinks = is_array($first) && (isset($first['url']) || isset($first['label']));
                    }

                    // Detect if items are strings with pipe separators to render as table
                    $hasPipe = false;
                    if (!$isLinks) {
                        foreach ($content as $it) { if (is_string($it) && strpos($it, '|') !== false) { $hasPipe = true; break; } }
                    }
                ?>
                    <?php if ($isLinks): ?>
                      <div class="overflow-x-auto">
                        <table class="min-w-full border border-gray-200 rounded-lg overflow-hidden">
                          <thead class="bg-gray-50">
                            <tr>
                              <th class="px-4 py-2 text-left text-gray-700 font-semibold">Label</th>
                              <th class="px-4 py-2 text-left text-gray-700 font-semibold">Action</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php foreach ($content as $item): ?>
                              <?php if (is_array($item) && isset($item['url'])): ?>
                              <tr class="border-t">
                                <td class="px-4 py-2 text-gray-800">
                                  <?= htmlspecialchars($item['label'] ?? $item['url']) ?>
                                </td>
                                <td class="px-4 py-2">
                                  <a href="<?= htmlspecialchars($item['url']) ?>" target="_blank" class="inline-flex items-center gap-2 text-primary hover:underline">
                                    <i class="fas fa-external-link-alt"></i> Open
                                  </a>
                                </td>
                              </tr>
                              <?php endif; ?>
                            <?php endforeach; ?>
                          </tbody>
                        </table>
                      </div>
                    <?php elseif ($hasPipe): ?>
                      <div class="overflow-x-auto">
                        <table class="min-w-full border border-gray-200 rounded-lg overflow-hidden">
                          <thead class="bg-gray-50">
                            <tr>
                              <th class="px-4 py-2 text-left text-gray-700 font-semibold">Item</th>
                              <th class="px-4 py-2 text-left text-gray-700 font-semibold">Details</th>
                              <th class="px-4 py-2 text-left text-gray-700 font-semibold">Notes</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php foreach ($content as $row): ?>
                              <?php if (!is_string($row)) continue; ?>
                              <?php $parts = array_map('trim', explode('|', $row)); ?>
                              <tr class="border-t">
                                <td class="px-4 py-2 text-gray-800">
                                  <?= htmlspecialchars($parts[0] ?? '') ?>
                                </td>
                                <td class="px-4 py-2 text-gray-700">
                                  <?= htmlspecialchars($parts[1] ?? '') ?>
                                </td>
                                <td class="px-4 py-2 text-gray-600">
                                  <?= htmlspecialchars($parts[2] ?? '') ?>
                                </td>
                              </tr>
                            <?php endforeach; ?>
                          </tbody>
                        </table>
                      </div>
                    <?php else: ?>
                      <ul class="list-disc pl-5 space-y-1">
                        <?php foreach ($content as $item): ?>
                          <li><?= htmlspecialchars(is_array($item) ? json_encode($item) : (string)$item) ?></li>
                        <?php endforeach; ?>
                      </ul>
                    <?php endif; ?>
                <?php else: ?>
                    <?php
                        $raw = (string)$content;
                        // If it looks like HTML, allow a safe subset of tags; otherwise, escape
                        if (preg_match('/<\w+[^>]*>/', $raw)) {
                            // Allowed tags: paragraphs, line breaks, emphasis, lists, simple headings, span
                            $allowedTags = '<p><br><strong><em><b><i><u><ol><ul><li><h1><h2><h3><h4><span>'; 
                            $safe = strip_tags($raw, $allowedTags);
                            echo $safe;
                        } else {
                            echo nl2br(htmlspecialchars($raw));
                        }
                    ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    return ob_get_clean();
}

// Ads rendering helpers
function renderAd($placement, $pageScope = 'all', $slugScope = null, $limit = 1) {
    try {
        $adModel = new Ad();
        $ads = $adModel->getActive($placement, $pageScope, $slugScope);
        if (!$ads) return '';
        $count = 0;
        ob_start();
        ?>
        <div class="ad-slot" data-placement="<?= htmlspecialchars($placement) ?>">
            <?php foreach ($ads as $ad): ?>
                <?php if ($limit && $count >= $limit) break; ?>
                <div class="ad-item ad-<?= htmlspecialchars($ad['placement']) ?>">
                    <!-- Ad: <?= htmlspecialchars($ad['name']) ?> -->
                    <?= $ad['code'] ?>
                </div>
                <?php $count++; ?>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    } catch (Exception $e) {
        return '';
    }
}

// Convenience: infer scope from current path if page doesn't pass scope
function inferPageScopeFromRequest() {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
    // Basic mapping based on memory URL schema
    if (preg_match('#/sarkari/job/#', $path)) return ['job_detail', basename($path)];
    if (preg_match('#/sarkari/result/#', $path)) return ['result_detail', basename($path)];
    if (preg_match('#/sarkari/admit/#', $path)) return ['admit_detail', basename($path)];
    if (preg_match('#/sarkari/syllabus/#', $path)) return ['syllabus_detail', basename($path)];
    if (preg_match('#/sarkari/jobs/([^/]+)$#', $path, $m)) return ['category', $m[1]];
    if (preg_match('#/sarkari/jobs$#', $path)) return ['jobs', null];
    if (preg_match('#/sarkari/results$#', $path)) return ['results', null];
    if (preg_match('#/sarkari/admit$#', $path)) return ['admit', null];
    if (preg_match('#/sarkari/syllabus$#', $path)) return ['syllabus', null];
    if (preg_match('#/sarkari/posts$#', $path)) return ['posts', null];
    return ['all', null];
}

?>
