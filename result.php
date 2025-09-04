<?php
require_once __DIR__ . '/src/config.php';
require_once __DIR__ . '/src/db.php';
require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/models/Result.php';
require_once __DIR__ . '/src/models/Author.php';

$resultModel = new Result();
$authorModel = new Author();

$slug = isset($_GET['slug']) ? sanitizeInput($_GET['slug']) : '';

if (!$slug) {
    header('Location: results.php');
    exit;
}

$result = $resultModel->getBySlug($slug);
$author = null;
if (!empty($result['author_id'])) {
    $author = $authorModel->getById($result['author_id']);
}

if (!$result) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

$pageTitle = $result['title'];
$metaDescription = $result['description'] ? excerpt(strip_tags($result['description']), 50) : "Download " . $result['title'] . " exam result.";
$currentPage = 'results';

// Build flexible content (extras) from polymorphic tables
$extras = [];
// Important Links
$links = $resultModel->getLinks((int)$result['id']);
if (!empty($links)) {
    $extras['Important Links'] = array_map(function($ln){
        return [
            'label' => $ln['label'] ?? ($ln['url'] ?? ''),
            'url' => $ln['url'] ?? '#'
        ];
    }, $links);
}
// Sections
$sections = $resultModel->getSections((int)$result['id']);
if (!empty($sections)) {
    foreach ($sections as $sec) {
        $key = $sec['title'] ?: ($sec['section_type'] === 'how_to_check' ? 'How to Check Result' : ($sec['section_type'] === 'notes' ? 'Important Notes' : 'Additional Information'));
        // If a key repeats, append numeric suffix
        $finalKey = $key;
        $i = 2;
        while (array_key_exists($finalKey, $extras)) { $finalKey = $key . ' (' . $i++ . ')'; }
        $extras[$finalKey] = (string)($sec['content'] ?? '');
    }
}
// Events
$events = $resultModel->getEvents((int)$result['id']);
if (!empty($events)) {
    $eventItems = [];
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
        if (!empty($pieces)) { $eventItems[] = implode(' | ', $pieces); }
    }
    if (!empty($eventItems)) {
        $extras['Events & Timeline'] = $eventItems;
    }
}
// FAQs (render as simple Q/A lines)
$faqs = $resultModel->getFaqs((int)$result['id']);
if (!empty($faqs)) {
    $faqItems = [];
    foreach ($faqs as $f) {
        $q = trim($f['question'] ?? '');
        $a = trim(strip_tags($f['answer'] ?? ''));
        if ($q || $a) { $faqItems[] = ($q ? 'Q: ' . $q : '') . ($a ? ' — A: ' . $a : ''); }
    }
    if (!empty($faqItems)) {
        $extras['FAQs'] = $faqItems;
    }
}

// Canonical & JSON-LD
$canonicalUrl = SITE_URL . '/result/' . urlencode($result['slug']);
$authorName = $author['name'] ?? 'SarkariJobs';
$publishedAt = $result['published_at'] ?? null;
$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => $result['title'],
    'description' => $metaDescription,
    'datePublished' => $publishedAt,
    'dateModified' => $result['updated_at'] ?? $publishedAt,
    'author' => [
        '@type' => 'Person',
        'name' => $authorName
    ],
    'mainEntityOfPage' => [
        '@type' => 'WebPage',
        '@id' => $canonicalUrl
    ]
];
$breadcrumbs = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        [
            '@type' => 'ListItem', 'position' => 1,
            'name' => 'Home', 'item' => SITE_URL . '/'
        ],
        [
            '@type' => 'ListItem', 'position' => 2,
            'name' => 'Results', 'item' => SITE_URL . '/results'
        ],
        [
            '@type' => 'ListItem', 'position' => 3,
            'name' => $result['title'], 'item' => $canonicalUrl
        ]
    ]
];
$additionalHead = "<link rel=\"canonical\" href=\"{$canonicalUrl}\">\n" .
    '<script type="application/ld+json">' . json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n" .
    '<script type="application/ld+json">' . json_encode($breadcrumbs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';

include 'includes/header.php';
?>

    <!-- Result Details -->
    <section class="py-8">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto">
                <!-- Breadcrumbs -->
                <nav class="text-sm text-gray-600 mb-4" aria-label="Breadcrumb">
                    <ol class="list-reset flex flex-wrap items-center gap-1">
                        <li>
                            <a href="<?= SITE_URL ?>/" class="hover:text-primary">Home</a>
                        </li>
                        <li><span class="mx-2">/</span></li>
                        <li>
                            <a href="<?= SITE_URL ?>/results" class="hover:text-primary">Results</a>
                        </li>
                        <li><span class="mx-2">/</span></li>
                        <li class="text-gray-800 line-clamp-1" aria-current="page"><?= htmlspecialchars($result['title']) ?></li>
                    </ol>
                </nav>
                <div class="bg-white rounded-lg shadow-md p-6 md:p-8">
                    <!-- Result Header -->
                    <div class="text-center mb-8">
                        <?php if (!empty($result['thumbnail_url'])): ?>
                        <img src="<?= htmlspecialchars($result['thumbnail_url']) ?>" alt="<?= htmlspecialchars($result['title']) ?>" class="w-full h-56 object-cover rounded-lg mb-4">
                        <?php else: ?>
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
                            <i class="fas fa-trophy text-accent text-2xl"></i>
                        </div>
                        <?php endif; ?>
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-4"><?= htmlspecialchars($result['title']) ?></h1>
                        <div class="mb-3">
                            <?= renderAuthorBadge($author) ?>
                        </div>
                        
                        <div class="flex flex-wrap justify-center gap-4 text-sm text-gray-600 mb-6">
                            <?php if ($result['exam_date']): ?>
                            <div class="flex items-center">
                                <i class="fas fa-calendar mr-2 text-accent"></i>
                                Exam Date: <?= formatDate($result['exam_date']) ?>
                            </div>
                            <?php endif; ?>
                            
                            <div class="flex items-center">
                                <i class="fas fa-clock mr-2 text-accent"></i>
                                Published: <?= timeAgo($result['published_at']) ?>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-wrap justify-center gap-3">
                            <?php if ($result['download_url']): ?>
                            <a href="<?= htmlspecialchars($result['download_url']) ?>" target="_blank"
                               class="bg-accent text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition-colors">
                                <i class="fas fa-download mr-2"></i>Download Result
                            </a>
                            <?php endif; ?>
                            
                            <button onclick="shareResult()" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                                <i class="fas fa-share mr-2"></i>Share
                            </button>
                        </div>
                    </div>

                    <?php [$scope, $slugScope] = inferPageScopeFromRequest(); ?>
                    <?php $inContent = renderAd('in_content', $scope, $slugScope, 1); if ($inContent): ?>
                    <div class="my-6">
                        <?= $inContent ?>
                    </div>
                    <?php endif; ?>

                    <!-- Result Content -->
                    <?php if ($result['description']): ?>
                    <div class="prose max-w-none">
                        <div class="text-gray-700 leading-relaxed">
                            <?= $result['description'] ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Flexible Sections (Extras) -->
                    <?php if (!empty($extras)): ?>
                    <div class="mt-8">
                        <?= renderExtras($extras) ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Related Results -->
                <div class="mt-8 bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-6">Other Recent Results</h3>
                    
                    <?php 
                    $relatedResults = $resultModel->getLatest(6);
                    $relatedResults = array_filter($relatedResults, function($r) use ($result) {
                        return $r['id'] !== $result['id'];
                    });
                    $relatedResults = array_slice($relatedResults, 0, 4);
                    ?>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($relatedResults as $relatedResult): ?>
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                            <h4 class="font-semibold text-gray-800 mb-2 line-clamp-2">
                                <a href="<?= SITE_URL ?>/result/<?= $relatedResult['slug'] ?>" class="hover:text-accent">
                                    <?= htmlspecialchars($relatedResult['title']) ?>
                                </a>
                            </h4>
                            <div class="text-sm text-gray-600">
                                <i class="fas fa-clock mr-1"></i>
                                <?= timeAgo($relatedResult['published_at']) ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        function shareResult() {
            if (navigator.share) {
                navigator.share({
                    title: '<?= addslashes($result['title']) ?>',
                    text: 'Check out this exam result',
                    url: window.location.href
                });
            } else {
                navigator.clipboard.writeText(window.location.href).then(() => {
                    alert('Result URL copied to clipboard!');
                });
            }
        }
    </script>

<?php include 'includes/footer.php'; ?>

