<?php
require_once __DIR__ . '/src/config.php';
require_once __DIR__ . '/src/db.php';
require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/models/Author.php';
require_once __DIR__ . '/src/models/Job.php';
require_once __DIR__ . '/src/models/Result.php';
require_once __DIR__ . '/src/models/AdmitCard.php';
require_once __DIR__ . '/src/models/Syllabus.php';

$slug = isset($_GET['slug']) ? sanitizeInput($_GET['slug']) : '';
if (!$slug) {
    header('Location: index.php');
    exit;
}

$authorModel = new Author();
$jobModel = new Job();
$resultModel = new Result();
$admitModel = new AdmitCard();
$syllabusModel = new Syllabus();

$author = $authorModel->getBySlug($slug);
if (!$author) {
    header("HTTP/1.0 404 Not Found");
    include '404.php';
    exit;
}

$pageTitle = $author['name'] . ' - Author Profile';
$metaDescription = excerpt($author['bio'] ?? ($author['name'] . ' profile'), 160);
$currentPage = '';

include 'includes/header.php';

// Fetch authored content
$jobs = $authorModel->getJobsByAuthor($author['id'], 6, 0);
$results = $authorModel->getResultsByAuthor($author['id'], 6, 0);
$admits = $authorModel->getAdmitsByAuthor($author['id'], 6, 0);
$syllabi = $authorModel->getSyllabiByAuthor($author['id'], 6, 0);

// Fetch counts per content type
$db = getDB();
$countJobs = (int)($db->fetchOne('SELECT COUNT(*) c FROM jobs WHERE author_id = ?', [$author['id']])['c'] ?? 0);
$countResults = (int)($db->fetchOne('SELECT COUNT(*) c FROM results WHERE author_id = ?', [$author['id']])['c'] ?? 0);
$countAdmits = (int)($db->fetchOne('SELECT COUNT(*) c FROM admit_cards WHERE author_id = ?', [$author['id']])['c'] ?? 0);
$countSyllabi = (int)($db->fetchOne('SELECT COUNT(*) c FROM syllabi WHERE author_id = ?', [$author['id']])['c'] ?? 0);
$countTotal = $countJobs + $countResults + $countAdmits + $countSyllabi;

$avatar = !empty($author['avatar_url']) ? $author['avatar_url'] : SITE_URL . '/assets/img/avatar-placeholder.png';
$verified = !empty($author['verified']);
$social = [];
if (!empty($author['social'])) {
    $decoded = json_decode($author['social'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $social = $decoded;
    }
}
?>

<section class="py-8">
  <div class="container mx-auto px-4">
    <div class="max-w-6xl mx-auto">
      <div class="bg-white rounded-lg shadow-md p-6 md:p-8 mb-8">
        <div class="flex items-start gap-4 md:gap-6">
          <img src="<?= htmlspecialchars($avatar) ?>" alt="<?= htmlspecialchars($author['name']) ?>" class="w-16 h-16 md:w-20 md:h-20 rounded-full object-cover">
          <div class="flex-1">
            <div class="flex flex-wrap items-center gap-2 md:gap-3">
              <h1 class="text-2xl font-bold text-gray-800">
                <?= htmlspecialchars($author['name']) ?>
              </h1>
              <?php if ($verified): ?>
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700">
                  <i class="fas fa-check-circle mr-1"></i>Verified
                </span>
              <?php endif; ?>
              <?php if ($countTotal > 0): ?>
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-gray-50 text-gray-700">
                  <i class="fas fa-file-alt mr-1"></i><?= $countTotal ?> total posts
                </span>
              <?php endif; ?>
            </div>
            <?php if (!empty($author['bio'])): ?>
              <p class="text-gray-600 mt-2"><?= htmlspecialchars($author['bio']) ?></p>
            <?php endif; ?>

            <?php if (!empty($social)): ?>
              <?php
                $iconMap = [
                  'facebook' => 'fab fa-facebook',
                  'twitter' => 'fab fa-x-twitter',
                  'x' => 'fab fa-x-twitter',
                  'linkedin' => 'fab fa-linkedin',
                  'instagram' => 'fab fa-instagram',
                  'youtube' => 'fab fa-youtube',
                  'telegram' => 'fab fa-telegram',
                  'github' => 'fab fa-github',
                  'website' => 'fas fa-globe'
                ];
              ?>
              <div class="mt-3 flex flex-wrap items-center gap-3 text-gray-600">
                <?php foreach ($social as $platform => $url): 
                    $platformKey = strtolower(trim($platform));
                    $icon = $iconMap[$platformKey] ?? 'fas fa-link';
                ?>
                  <a class="inline-flex items-center gap-2 hover:text-primary" href="<?= htmlspecialchars($url) ?>" target="_blank" rel="noopener">
                    <i class="<?= $icon ?>"></i>
                    <span class="sr-only"><?= htmlspecialchars(ucfirst($platformKey)) ?></span>
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <div class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-3">
              <div class="p-3 rounded border border-gray-100 bg-gray-50">
                <div class="text-xs text-gray-500">Jobs</div>
                <div class="text-lg font-semibold text-gray-800"><?= $countJobs ?></div>
              </div>
              <div class="p-3 rounded border border-gray-100 bg-gray-50">
                <div class="text-xs text-gray-500">Results</div>
                <div class="text-lg font-semibold text-gray-800"><?= $countResults ?></div>
              </div>
              <div class="p-3 rounded border border-gray-100 bg-gray-50">
                <div class="text-xs text-gray-500">Admit Cards</div>
                <div class="text-lg font-semibold text-gray-800"><?= $countAdmits ?></div>
              </div>
              <div class="p-3 rounded border border-gray-100 bg-gray-50">
                <div class="text-xs text-gray-500">Syllabus</div>
                <div class="text-lg font-semibold text-gray-800"><?= $countSyllabi ?></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
          <h2 class="text-xl font-bold mb-4">Recent Jobs</h2>
          <?php if ($jobs): ?>
            <ul class="space-y-3">
              <?php foreach ($jobs as $job): ?>
                <li>
                  <a class="hover:text-primary font-medium" href="<?= SITE_URL ?>/job/<?= $job['slug'] ?>"><?= htmlspecialchars($job['title']) ?></a>
                  <div class="text-sm text-gray-500"><?php if (!empty($job['organization'])): ?><?= htmlspecialchars($job['organization']) ?> · <?php endif; ?><?= timeAgo($job['published_at']) ?></div>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <p class="text-gray-600">No jobs yet.</p>
          <?php endif; ?>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <h2 class="text-xl font-bold mb-4">Recent Results</h2>
          <?php if ($results): ?>
            <ul class="space-y-3">
              <?php foreach ($results as $item): ?>
                <li>
                  <a class="hover:text-primary font-medium" href="<?= SITE_URL ?>/result/<?= $item['slug'] ?>"><?= htmlspecialchars($item['title']) ?></a>
                  <div class="text-sm text-gray-500"><?= timeAgo($item['published_at']) ?></div>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <p class="text-gray-600">No results yet.</p>
          <?php endif; ?>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <h2 class="text-xl font-bold mb-4">Recent Admit Cards</h2>
          <?php if ($admits): ?>
            <ul class="space-y-3">
              <?php foreach ($admits as $item): ?>
                <li>
                  <a class="hover:text-primary font-medium" href="<?= SITE_URL ?>/admit/<?= $item['slug'] ?>"><?= htmlspecialchars($item['title']) ?></a>
                  <div class="text-sm text-gray-500"><?= timeAgo($item['published_at']) ?></div>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <p class="text-gray-600">No admit cards yet.</p>
          <?php endif; ?>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <h2 class="text-xl font-bold mb-4">Recent Syllabus</h2>
          <?php if ($syllabi): ?>
            <ul class="space-y-3">
              <?php foreach ($syllabi as $item): ?>
                <li>
                  <a class="hover:text-primary font-medium" href="<?= SITE_URL ?>/syllabus/<?= $item['slug'] ?>"><?= htmlspecialchars($item['title']) ?></a>
                  <div class="text-sm text-gray-500"><?= timeAgo($item['published_at']) ?></div>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <p class="text-gray-600">No syllabus yet.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
