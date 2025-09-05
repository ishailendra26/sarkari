<?php
require_once __DIR__ . '/src/config.php';
require_once __DIR__ . '/src/db.php';
require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/models/Job.php';
require_once __DIR__ . '/src/models/Author.php';

$jobModel = new Job();
$authorModel = new Author();

// Get job slug from URL
$slug = isset($_GET['slug']) ? sanitizeInput($_GET['slug']) : '';

if (!$slug) {
    header('Location: jobs.php');
    exit;
}

$job = $jobModel->getBySlug($slug);
$author = null;
if (!empty($job['author_id'])) {
    $author = $authorModel->getById($job['author_id']);
}

if (!$job) {
    header("HTTP/1.0 404 Not Found");
    include '404.php';
    exit;
}

// Parse attachments if available
$attachments = $job['attachments'] ? json_decode($job['attachments'], true) : [];

$pageTitle = $job['title'];
$metaDescription = $job['content'] ? excerpt(strip_tags($job['content']), 50) : "Apply for " . $job['title'] . " at " . $job['organization'];
$currentPage = 'jobs';

// Build flexible content (extras) from polymorphic tables in a strict order
$extras = [];

// Collect building blocks first
$eventsItems = [];
$feesItems = [];
$ageItems = [];
$vacancyItems = [];
$howToApply = null;
$modeOfExam = null;
$linksItems = [];
$faqItems = [];

// Events -> Important Dates (table via pipes)
$events = $jobModel->getEvents((int)$job['id']);
if (!empty($events)) {
    foreach ($events as $ev) {
        $label = trim(($ev['event_label'] ?? '') ?: ucfirst($ev['event_type'] ?? 'Event'));
        $datePart = '';
        if (!empty($ev['start_date']) && !empty($ev['end_date'])) {
            $datePart = formatDate($ev['start_date']) . ' - ' . formatDate($ev['end_date']);
        } elseif (!empty($ev['start_date'])) {
            $datePart = formatDate($ev['start_date']);
        }
        $notes = trim($ev['notes'] ?? '');
        $pieces = array_filter([$label, $datePart, $notes]);
        if (!empty($pieces)) { $eventsItems[] = implode(' | ', $pieces); }
    }
}

// Fees -> Application Fees
$fees = $jobModel->getFees((int)$job['id']);
if (!empty($fees)) {
    foreach ($fees as $f) {
        $parts = [];
        if (!empty($f['category'])) $parts[] = $f['category'];
        if ($f['amount'] !== null && $f['amount'] !== '') $parts[] = 'Rs. ' . $f['amount'];
        if (!empty($f['text'])) $parts[] = $f['text'];
        if (!empty($f['mode_notes'])) $parts[] = '(' . $f['mode_notes'] . ')';
        if ($parts) $feesItems[] = implode(' - ', $parts);
    }
}

// Age Limit
$age = $jobModel->getAgeLimit((int)$job['id']);
if (!empty($age)) {
    if ($age['min_age'] !== null || $age['max_age'] !== null) {
        $range = [];
        if ($age['min_age'] !== null) $range[] = 'Min: ' . (int)$age['min_age'];
        if ($age['max_age'] !== null) $range[] = 'Max: ' . (int)$age['max_age'];
        if ($range) $ageItems[] = implode(', ', $range);
    }
    if (!empty($age['cutoff_date'])) { $ageItems[] = 'Age as on: ' . formatDate($age['cutoff_date']); }
    if (!empty($age['relaxation_text'])) { $ageItems[] = $age['relaxation_text']; }
}

// Vacancies -> Vacancy Details
$vacancies = $jobModel->getVacancies((int)$job['id']);
if (!empty($vacancies)) {
    foreach ($vacancies as $v) {
        $parts = [];
        if (!empty($v['post_name'])) $parts[] = $v['post_name'];
        if (!empty($v['category'])) $parts[] = '(' . $v['category'] . ')';
        if ($v['total_posts'] !== null && $v['total_posts'] !== '') $parts[] = '- ' . (int)$v['total_posts'] . ' Posts';
        if (!empty($v['eligibility_text'])) $parts[] = '- ' . $v['eligibility_text'];
        if (!empty($v['pay_scale'])) $parts[] = '- Pay: ' . $v['pay_scale'];
        if ($parts) $vacancyItems[] = trim(implode(' ', $parts));
    }
}

// Sections -> capture How to Apply, Mode of Exam (others ignored for now)
$sections = $jobModel->getSections((int)$job['id']);
if (!empty($sections)) {
    foreach ($sections as $sec) {
        $stype = strtolower($sec['section_type'] ?? '');
        $title = strtolower(trim($sec['title'] ?? ''));
        $content = (string)($sec['content'] ?? '');
        if (!$howToApply && ($stype === 'how_to_apply' || strpos($title, 'how to apply') !== false)) {
            $howToApply = $content;
        } elseif (!$modeOfExam && ($stype === 'mode_of_exam' || strpos($title, 'mode of exam') !== false || strpos($title, 'exam mode') !== false)) {
            $modeOfExam = $content;
        }
    }
}

// Links -> Important Link
$links = $jobModel->getLinks((int)$job['id']);
if (!empty($links)) {
    $linksItems = array_map(function($ln){
        return [
            'label' => $ln['label'] ?? ($ln['url'] ?? ''),
            'url' => $ln['url'] ?? '#'
        ];
    }, $links);
}

// FAQs
$faqs = $jobModel->getFaqs((int)$job['id']);
if (!empty($faqs)) {
    foreach ($faqs as $f) {
        $q = trim($f['question'] ?? '');
        $a = trim(strip_tags($f['answer'] ?? ''));
        if ($q || $a) { $faqItems[] = ($q ? 'Q: ' . $q : '') . ($a ? ' — A: ' . $a : ''); }
    }
}

// Assemble in desired order
if (!empty($eventsItems)) { $extras['Important Dates'] = $eventsItems; }
if (!empty($feesItems)) { $extras['Application Fees'] = $feesItems; }
if (!empty($ageItems)) { $extras['Age Limit'] = $ageItems; }
if (!empty($vacancyItems)) { $extras['Vacancy Details'] = $vacancyItems; }
if (!empty($howToApply)) { $extras['How to Apply'] = $howToApply; }
if (!empty($modeOfExam)) { $extras['Mode of Exam'] = $modeOfExam; }
if (!empty($linksItems)) { $extras['Important Links'] = $linksItems; }
if (!empty($faqItems)) { $extras['FAQs'] = $faqItems; }

include 'includes/header.php';
?>

    <!-- Breadcrumbs -->
    <div class="bg-white border-b">
        <div class="container mx-auto px-4 py-3">
            <nav class="text-sm text-gray-600" aria-label="Breadcrumb">
                <ol class="list-reset flex flex-wrap items-center gap-1">
                    <li>
                        <a href="<?= SITE_URL ?>/" class="hover:text-primary">Home</a>
                    </li>
                    <li><span class="mx-2">/</span></li>
                    <li>
                        <a href="<?= SITE_URL ?>/jobs" class="hover:text-primary">Jobs</a>
                    </li>
                    <li><span class="mx-2">/</span></li>
                    <li class="text-gray-800 line-clamp-1" aria-current="page"><?= htmlspecialchars($job['title']) ?></li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Job Details -->
    <section class="py-8">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-md p-6 md:p-8">
                        <!-- Job Header -->
                        <div class="mb-6">
                            <?php if (!empty($job['thumbnail_url'])): ?>
                            <div class="w-full aspect-[16/9] bg-gray-100 rounded-lg mb-4 overflow-hidden">
                                <img src="<?= htmlspecialchars($job['thumbnail_url']) ?>" alt="<?= htmlspecialchars($job['title']) ?>" class="w-full h-full object-contain">
                            </div>
                            <?php endif; ?>
                            <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-4"><?= htmlspecialchars($job['title']) ?></h1>
                            <div class="mb-3">
                                <?= renderAuthorBadge($author) ?>
                            </div>
                            
                            <div class="flex flex-wrap gap-4 text-sm text-gray-600 mb-4">
                                <?php if ($job['organization']): ?>
                                <div class="flex items-center">
                                    <i class="fas fa-building w-4 mr-2 text-primary"></i>
                                    <span class="font-medium"><?= htmlspecialchars($job['organization']) ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($job['location']): ?>
                                <div class="flex items-center">
                                    <i class="fas fa-map-marker-alt w-4 mr-2 text-primary"></i>
                                    <span><?= htmlspecialchars($job['location']) ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <div class="flex items-center">
                                    <i class="fas fa-clock w-4 mr-2 text-primary"></i>
                                    <span>Posted <?= timeAgo($job['published_at'] ?: ($job['created_at'] ?? null)) ?></span>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex flex-wrap gap-3">
                                <?php if ($job['apply_link']): ?>
                                <a href="<?= htmlspecialchars($job['apply_link']) ?>" target="_blank"
                                   class="bg-accent text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition-colors">
                                    <i class="fas fa-external-link-alt mr-2"></i>Apply Online
                                </a>
                                <?php endif; ?>
                                
                                <button onclick="shareJob()" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                                    <i class="fas fa-share mr-2"></i>Share
                                </button>
                                
                                <button onclick="window.print()" class="bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-gray-700 transition-colors">
                                    <i class="fas fa-print mr-2"></i>Print
                                </button>
                            </div>
                        </div>

                        <?php [$scope, $slugScope] = inferPageScopeFromRequest(); ?>
                        <?php $inContent = renderAd('in_content', $scope, $slugScope, 1); if ($inContent): ?>
                        <div class="my-6">
                            <?= $inContent ?>
                        </div>
                        <?php endif; ?>

                        <!-- Job Content -->
                        <?php if ($job['content']): ?>
                        <div class="prose max-w-none mb-8">
                            <h2 class="text-xl font-bold text-gray-800 mb-4">Job Description</h2>
                            <div class="text-gray-700 leading-relaxed">
                                <?= $job['content'] ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Flexible Sections (Extras) -->
                        <?php if (!empty($extras)): ?>
                        <div class="mb-8">
                            <?= renderExtras($extras) ?>
                        </div>
                        <?php endif; ?>

                        <!-- Attachments -->
                        <?php if (!empty($attachments)): ?>
                        <div class="mb-8">
                            <h2 class="text-xl font-bold text-gray-800 mb-4">Downloads</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <?php foreach ($attachments as $attachment): ?>
                                <a href="<?= htmlspecialchars($attachment['url']) ?>" target="_blank"
                                   class="flex items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                                    <i class="fas fa-file-pdf text-red-600 text-2xl mr-3"></i>
                                    <div>
                                        <div class="font-medium text-gray-800"><?= htmlspecialchars($attachment['name']) ?></div>
                                        <div class="text-sm text-gray-600">PDF Document</div>
                                    </div>
                                    <i class="fas fa-download ml-auto text-primary"></i>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1">
                    <!-- Job Info Card -->
                    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Job Information</h3>
                        
                        <div class="space-y-4">
                            <?php if ($job['vacancy_count']): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Total Vacancies:</span>
                                <span class="font-semibold text-gray-800"><?= $job['vacancy_count'] ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($job['last_date']): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Last Date:</span>
                                <span class="font-semibold <?= strtotime($job['last_date']) < time() ? 'text-red-600' : 'text-gray-800' ?>">
                                    <?= formatDate($job['last_date']) ?>
                                </span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($job['educational_qualification']): ?>
                            <div>
                                <span class="text-gray-600 block mb-2">Education:</span>
                                <div class="prose max-w-none text-gray-800 leading-relaxed">
                                    <?= $job['educational_qualification'] ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($job['age_limit']): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Age Limit:</span>
                                <span class="font-semibold text-gray-800"><?= htmlspecialchars($job['age_limit']) ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($job['category_name']): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Category:</span>
                                <span class="badge badge-primary"><?= htmlspecialchars($job['category_name']) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Related Jobs -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Related Jobs</h3>
                        
                        <?php 
                        $relatedJobs = $jobModel->getAll(5, 0, $job['category_slug']);
                        $relatedJobs = array_filter($relatedJobs, function($relatedJob) use ($job) {
                            return $relatedJob['id'] !== $job['id'];
                        });
                        $relatedJobs = array_slice($relatedJobs, 0, 4);
                        ?>
                        
                        <div class="space-y-4">
                            <?php foreach ($relatedJobs as $relatedJob): ?>
                            <div class="border-b border-gray-200 pb-4 last:border-b-0 last:pb-0">
                                <h4 class="font-medium text-gray-800 mb-2 line-clamp-2">
                                    <a href="<?= SITE_URL ?>/job/<?= $relatedJob['slug'] ?>" class="hover:text-primary">
                                        <?= htmlspecialchars($relatedJob['title']) ?>
                                    </a>
                                </h4>
                                <div class="text-sm text-gray-600">
                                    <?= htmlspecialchars($relatedJob['organization']) ?>
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    <?= timeAgo($relatedJob['published_at'] ?: ($relatedJob['created_at'] ?? null)) ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-4">
                            <a href="<?= SITE_URL ?>/jobs<?= $job['category_slug'] ? '/' . $job['category_slug'] : '' ?>" 
                               class="text-primary hover:text-blue-700 font-semibold text-sm">
                                View More Jobs <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        function shareJob() {
            if (navigator.share) {
                navigator.share({
                    title: '<?= addslashes($job['title']) ?>',
                    text: 'Check out this job opportunity at <?= addslashes($job['organization']) ?>',
                    url: window.location.href
                });
            } else {
                // Fallback to copy URL
                navigator.clipboard.writeText(window.location.href).then(() => {
                    alert('Job URL copied to clipboard!');
                });
            }
        }
    </script>

<?php include 'includes/footer.php'; ?>

