<?php
require_once __DIR__ . '/src/config.php';
require_once __DIR__ . '/src/db.php';
require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/models/AdmitCard.php';
require_once __DIR__ . '/src/models/Author.php';

$slug = isset($_GET['slug']) ? sanitizeInput($_GET['slug']) : '';

if (!$slug) {
    header('Location: admit.php');
    exit;
}

$admitModel = new AdmitCard();
$authorModel = new Author();
$admit = $admitModel->getBySlug($slug);
$author = null;
if (!empty($admit['author_id'])) {
    $author = $authorModel->getById($admit['author_id']);
}

if (!$admit) {
    header("HTTP/1.0 404 Not Found");
    include '404.php';
    exit;
}

$relatedAdmits = $admitModel->getLatest(4);
// Remove current admit from related
$relatedAdmits = array_filter($relatedAdmits, function($item) use ($admit) {
    return $item['id'] !== $admit['id'];
});

$pageTitle = $admit['title'];
$metaDescription = excerpt($admit['description'] ?? $admit['title'], 160);
$currentPage = 'admit';

// Build flexible content (extras) like result screen in strict order
$extras = [];
$instructionHtml = $admit['instructions'] ?? '';
$eventItems = [];
$linksItems = [];
$faqItems = [];

// Events -> Important Events
$events = $admitModel->getEvents((int)$admit['id']);
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
        if (!empty($pieces)) { $eventItems[] = implode(' | ', $pieces); }
    }
}

// Links -> Important Links
$links = $admitModel->getLinks((int)$admit['id']);
if (!empty($links)) {
    $linksItems = array_map(function($ln){
        return [
            'label' => $ln['label'] ?? ($ln['url'] ?? ''),
            'url' => $ln['url'] ?? '#'
        ];
    }, $links);
}

// FAQs
$faqs = $admitModel->getFaqs((int)$admit['id']);
if (!empty($faqs)) {
    foreach ($faqs as $f) {
        $q = trim($f['question'] ?? '');
        $a = trim(strip_tags($f['answer'] ?? ''));
        if ($q || $a) { $faqItems[] = ($q ? 'Q: ' . $q : '') . ($a ? ' — A: ' . $a : ''); }
    }
}

// Assemble in order: Instruction, Events, Links, FAQs
if (!empty(trim(strip_tags($instructionHtml)))) { $extras['Important Instruction'] = $instructionHtml; }
if (!empty($eventItems)) { $extras['Important Events'] = $eventItems; }
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
                        <a href="<?= SITE_URL ?>/admit" class="hover:text-primary">Admit Cards</a>
                    </li>
                    <li><span class="mx-2">/</span></li>
                    <li class="text-gray-800 line-clamp-1" aria-current="page"><?= htmlspecialchars($admit['title']) ?></li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Admit Card Details -->
    <section class="py-8">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto">
                <div class="bg-white rounded-lg shadow-md p-6 md:p-8">
                    <!-- Header -->
                    <div class="text-center mb-8">
                        <?php if (!empty($admit['thumbnail_url'])): ?>
                        <img src="<?= htmlspecialchars($admit['thumbnail_url']) ?>" alt="<?= htmlspecialchars($admit['title']) ?>" class="w-full h-56 object-cover rounded-lg mb-4">
                        <?php else: ?>
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-yellow-100 rounded-full mb-4">
                            <i class="fas fa-id-card text-yellow-600 text-2xl"></i>
                        </div>
                        <?php endif; ?>
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-4"><?= htmlspecialchars($admit['title']) ?></h1>
                        <div class="mb-3">
                            <?= renderAuthorBadge($author) ?>
                        </div>
                        
                        <div class="flex flex-wrap justify-center gap-4 text-sm text-gray-600 mb-6">
                            <?php if ($admit['exam_date']): ?>
                            <div class="flex items-center">
                                <i class="fas fa-calendar mr-2 text-yellow-600"></i>
                                Exam Date: <?= formatDate($admit['exam_date']) ?>
                            </div>
                            <?php endif; ?>

                            <div class="flex items-center">
                                <i class="fas fa-clock mr-2 text-yellow-600"></i>
                                Published: <?= timeAgo($admit['published_at']) ?>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-wrap justify-center gap-3">
                            <?php if ($admit['download_url']): ?>
                            <a href="<?= htmlspecialchars($admit['download_url']) ?>" target="_blank"
                               class="bg-yellow-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-yellow-700 transition-colors">
                                <i class="fas fa-download mr-2"></i>Download Admit Card
                            </a>
                            <?php endif; ?>
                            
                            <button onclick="shareAdmitCard()" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                                <i class="fas fa-share mr-2"></i>Share
                            </button>
                            
                            <button onclick="window.print()" class="bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-gray-700 transition-colors">
                                <i class="fas fa-print mr-2"></i>Print
                            </button>
                        </div>
                    </div>

                    <!-- Flexible Sections (Extras) placed right below action buttons -->
                    <?php if (!empty($extras)): ?>
                    <div class="mt-2 mb-8">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-id-card text-yellow-600 mr-2"></i>Admit Card Details
                        </h2>
                        <?= renderExtras($extras) ?>
                    </div>
                    <?php endif; ?>

                    <!-- In-content Ad after details -->
                    <?php [$scope, $slugScope] = inferPageScopeFromRequest(); ?>
                    <?php $inContent = renderAd('in_content', $scope, $slugScope, 1); if ($inContent): ?>
                    <div class="my-6">
                        <?= $inContent ?>
                    </div>
                    <?php endif; ?>

                    <!-- Important Instructions moved into extras above -->

                    <!-- General Instructions -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">
                            <i class="fas fa-info-circle text-blue-600 mr-2"></i>General Instructions
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h3 class="font-semibold text-gray-800 mb-3">Before Exam Day:</h3>
                                <ul class="space-y-2 text-gray-700">
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-green-600 mr-2 mt-1"></i>
                                        Download and print admit card
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-green-600 mr-2 mt-1"></i>
                                        Check exam center location
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-green-600 mr-2 mt-1"></i>
                                        Arrange valid photo ID proof
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-green-600 mr-2 mt-1"></i>
                                        Plan your travel route
                                    </li>
                                </ul>
                            </div>
                            
                            <div>
                                <h3 class="font-semibold text-gray-800 mb-3">On Exam Day:</h3>
                                <ul class="space-y-2 text-gray-700">
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-green-600 mr-2 mt-1"></i>
                                        Reach center 30 minutes early
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-green-600 mr-2 mt-1"></i>
                                        Carry admit card and ID proof
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-green-600 mr-2 mt-1"></i>
                                        Follow COVID guidelines
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-green-600 mr-2 mt-1"></i>
                                        No electronic devices allowed
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Required Documents -->
                    <div class="bg-red-50 border border-red-200 rounded-lg p-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">
                            <i class="fas fa-clipboard-list text-red-600 mr-2"></i>Required Documents
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <h3 class="font-semibold text-gray-800">Mandatory:</h3>
                                <ul class="text-gray-700 space-y-1">
                                    <li>• Printed Admit Card</li>
                                    <li>• Original Photo ID Proof</li>
                                    <li>• Passport size photograph</li>
                                </ul>
                            </div>
                            <div class="space-y-2">
                                <h3 class="font-semibold text-gray-800">Valid ID Proofs:</h3>
                                <ul class="text-gray-700 space-y-1">
                                    <li>• Aadhaar Card</li>
                                    <li>• Passport</li>
                                    <li>• Driving License</li>
                                    <li>• Voter ID Card</li>
                                    <li>• PAN Card</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Related Admit Cards -->
                <div class="mt-8 bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-6">Other Admit Cards</h3>
                    
                    <?php 
                    $relatedAdmits = $admitModel->getLatest(6);
                    $relatedAdmits = array_filter($relatedAdmits, function($a) use ($admit) {
                        return $a['id'] !== $admit['id'];
                    });
                    $relatedAdmits = array_slice($relatedAdmits, 0, 4);
                    ?>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($relatedAdmits as $relatedAdmit): ?>
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                            <h4 class="font-semibold text-gray-800 mb-2 line-clamp-2">
                                <a href="<?= SITE_URL ?>/admit/<?= $relatedAdmit['slug'] ?>" class="hover:text-yellow-600">
                                    <?= htmlspecialchars($relatedAdmit['title']) ?>
                                </a>
                            </h4>
                            <div class="text-sm text-gray-600">
                                <i class="fas fa-clock mr-1"></i>
                                <?= timeAgo($relatedAdmit['published_at']) ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        function shareAdmitCard() {
            const url = window.location.href;
            const title = '<?= addslashes($admit['title']) ?>';
            if (navigator.share) {
                navigator.share({
                    title: title,
                    text: 'Check this admit card',
                    url: url
                }).catch(() => {/* ignore cancel */});
            } else if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(url).then(() => {
                    alert('Admit card URL copied to clipboard!');
                });
            } else {
                // Fallback for non-secure context: create temp input
                const input = document.createElement('input');
                input.value = url;
                document.body.appendChild(input);
                input.select();
                try { document.execCommand('copy'); } catch (e) {}
                document.body.removeChild(input);
                alert('Admit card URL copied to clipboard!');
            }
        }
    </script>

<?php include 'includes/footer.php'; ?>

