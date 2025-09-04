<?php
require_once __DIR__ . '/src/config.php';
require_once __DIR__ . '/src/db.php';
require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/models/Syllabus.php';
require_once __DIR__ . '/src/models/Author.php';

$slug = isset($_GET['slug']) ? sanitizeInput($_GET['slug']) : '';

if (!$slug) {
    header('Location: syllabus.php');
    exit;
}

$syllabusModel = new Syllabus();
$authorModel = new Author();
$syllabus = $syllabusModel->getBySlug($slug);
$author = null;
if (!empty($syllabus['author_id'])) {
    $author = $authorModel->getById($syllabus['author_id']);
}

if (!$syllabus) {
    header("HTTP/1.0 404 Not Found");
    include '404.php';
    exit;
}

$relatedSyllabus = $syllabusModel->getLatest(4);
// Remove current syllabus from related
$relatedSyllabus = array_filter($relatedSyllabus, function($item) use ($syllabus) {
    return $item['id'] !== $syllabus['id'];
});

$pageTitle = $syllabus['title'];
$metaDescription = excerpt($syllabus['description'] ?? $syllabus['title'], 50);
$currentPage = 'syllabus';

// Parse sections JSON
$sections = $syllabus['sections'] ? json_decode($syllabus['sections'], true) : [];

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
                        <a href="<?= SITE_URL ?>/syllabus" class="hover:text-primary">Syllabus</a>
                    </li>
                    <li><span class="mx-2">/</span></li>
                    <li class="text-gray-800 line-clamp-1" aria-current="page"><?= htmlspecialchars($syllabus['title']) ?></li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Syllabus/ -->
    <section class="py-8">
        <div class="container mx-auto px-4">
            <div class="max-w-6xl mx-auto">
                <div class="bg-white rounded-lg shadow-md p-6 md:p-8">
                    <!-- Header -->
                    <div class="text-center mb-8">
                        <?php if (!empty($syllabus['thumbnail_url'])): ?>
                        <div class="w-full aspect-[16/9] bg-gray-100 rounded-lg mb-4 overflow-hidden">
                            <img src="<?= htmlspecialchars($syllabus['thumbnail_url']) ?>" alt="<?= htmlspecialchars($syllabus['title']) ?>" class="w-full h-full object-contain">
                        </div>
                        <?php else: ?>
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-purple-100 rounded-full mb-4">
                            <i class="fas fa-book text-purple-600 text-2xl"></i>
                        </div>
                        <?php endif; ?>
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-4"><?= htmlspecialchars($syllabus['title']) ?></h1>
                        <div class="mb-3">
                            <?= renderAuthorBadge($author) ?>
                        </div>
                        
                        <div class="flex justify-center gap-4 text-sm text-gray-600 mb-6">
                            <div class="flex items-center">
                                <i class="fas fa-clock mr-2 text-purple-600"></i>
                                Updated: <?= timeAgo($syllabus['published_at']) ?>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-wrap justify-center gap-3">
                            <?php if (!empty($syllabus['download_url'])): ?>
                            <a href="<?= htmlspecialchars($syllabus['download_url']) ?>" target="_blank" rel="noopener"
                               class="bg-purple-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-purple-700 transition-colors">
                                <i class="fas fa-download mr-2"></i>Download PDF
                            </a>
                            <?php endif; ?>
                            
                            <button onclick="shareSyllabus()" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors">
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

                    <!-- Syllabus Description -->
                    <?php if (!empty($syllabus['description'])): ?>
                    <div class="prose max-w-none text-gray-800 leading-relaxed mb-8">
                        <?= $syllabus['description'] ?>
                    </div>
                    <?php endif; ?>

                    <!-- Syllabus Content -->
                    <?php if (!empty($sections)): ?>
                    <div class="space-y-6">
                        <?php foreach ($sections as $sectionTitle => $topics): ?>
                        <div class="bg-gray-50 rounded-lg p-6">
                            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-bookmark text-purple-600 mr-2"></i>
                                <?= htmlspecialchars($sectionTitle) ?>
                            </h2>
                            
                            <?php if (is_array($topics)): ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                <?php foreach ($topics as $topic): ?>
                                <div class="bg-white rounded-lg p-3 shadow-sm">
                                    <div class="flex items-center">
                                        <i class="fas fa-check-circle text-green-600 mr-2"></i>
                                        <span class="text-gray-700"><?= htmlspecialchars($topic) ?></span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <div class="text-gray-700">
                                <?= nl2br(htmlspecialchars($topics)) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-file-alt text-gray-400 text-4xl mb-4"></i>
                        <p class="text-gray-600">Detailed syllabus will be updated soon.</p>
                    </div>
                    <?php endif; ?>

                    <!-- Flexible Sections (Extras) -->
                    <?php if (!empty($syllabus['extras'])): ?>
                    <div class="mt-8">
                        <?= renderExtras($syllabus['extras']) ?>
                    </div>
                    <?php endif; ?>

                    <!-- Study Tips -->
                    <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">
                            <i class="fas fa-lightbulb text-blue-600 mr-2"></i>Study Tips
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h3 class="font-semibold text-gray-800 mb-3">Preparation Strategy:</h3>
                                <ul class="space-y-2 text-gray-700">
                                    <li class="flex items-start">
                                        <i class="fas fa-star text-yellow-500 mr-2 mt-1"></i>
                                        Create a study schedule
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-star text-yellow-500 mr-2 mt-1"></i>
                                        Focus on high-weightage topics
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-star text-yellow-500 mr-2 mt-1"></i>
                                        Practice previous year papers
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-star text-yellow-500 mr-2 mt-1"></i>
                                        Take regular mock tests
                                    </li>
                                </ul>
                            </div>
                            
                            <div>
                                <h3 class="font-semibold text-gray-800 mb-3">Important Resources:</h3>
                                <ul class="space-y-2 text-gray-700">
                                    <li class="flex items-start">
                                        <i class="fas fa-book text-blue-600 mr-2 mt-1"></i>
                                        NCERT books for basics
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-newspaper text-blue-600 mr-2 mt-1"></i>
                                        Current affairs magazines
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-globe text-blue-600 mr-2 mt-1"></i>
                                        Online practice platforms
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-users text-blue-600 mr-2 mt-1"></i>
                                        Join study groups
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        function shareSyllabus() {
            const url = window.location.href;
            const title = '<?= addslashes($syllabus['title']) ?>';
            if (navigator.share) {
                navigator.share({
                    title: title,
                    text: 'Check this syllabus',
                    url: url
                }).catch(() => {/* user canceled or not supported */});
            } else if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(url).then(() => {
                    alert('Syllabus URL copied to clipboard!');
                });
            } else {
                const input = document.createElement('input');
                input.value = url;
                document.body.appendChild(input);
                input.select();
                try { document.execCommand('copy'); } catch (e) {}
                document.body.removeChild(input);
                alert('Syllabus URL copied to clipboard!');
            }
        }
    </script>

<?php include 'includes/footer.php'; ?>

