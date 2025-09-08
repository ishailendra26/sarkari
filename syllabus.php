<?php
require_once __DIR__ . '/src/config.php';
require_once __DIR__ . '/src/db.php';
require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/models/Syllabus.php';
require_once __DIR__ . '/src/models/Category.php';

$syllabusModel = new Syllabus();
$categoryModel = new Category();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : null;
$category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : null;

if ($search) {
    $syllabuses = $syllabusModel->search($search, POSTS_PER_PAGE, ($page - 1) * POSTS_PER_PAGE);
    $totalSyllabuses = count($syllabusModel->search($search, 1000, 0));
} else {
    $syllabuses = $syllabusModel->getAll(POSTS_PER_PAGE, ($page - 1) * POSTS_PER_PAGE);
    $totalSyllabuses = $syllabusModel->getCount();
}

$categories = $categoryModel->getAll();
$pagination = paginate($totalSyllabuses, $page, POSTS_PER_PAGE);

$pageTitle = $search ? "Search Syllabus for '$search'" : "Latest Exam Syllabus";
$metaDescription = $search ? "Search results for '$search' exam syllabus" : "Download latest government job exam syllabus and study materials.";
$currentPage = 'syllabus';

include 'includes/header.php';
?>

    <!-- Page Header -->
    <section class="bg-purple-50 py-8">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-2">
                        <i class="fas fa-book text-purple-600 mr-2"></i><?= $pageTitle ?>
                    </h1>
                    <p class="text-gray-600">Found <?= $totalSyllabuses ?> syllabus documents</p>
                </div>
                
                <div class="mt-4 md:mt-0">
                    <div class="relative">
                        <input type="text" id="syllabus-search" value="<?= $search ?>" placeholder="Search syllabus..." 
                               class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary w-full md:w-64">
                        <button id="syllabus-search-btn" class="absolute right-2 top-2 text-gray-500 hover:text-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Syllabus Grid -->
    <section class="py-8">
        <div class="container mx-auto px-4">
            <?php [$scope, $slug] = inferPageScopeFromRequest(); ?>
            <?php if (empty($syllabuses)): ?>
            <div class="text-center py-12">
                <i class="fas fa-book text-gray-400 text-6xl mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-600 mb-2">No syllabus found</h3>
                <p class="text-gray-500">Try adjusting your search criteria.</p>
            </div>
            <?php else: ?>
            <?php 
                $sidebarTop = renderAd('sidebar_top', $scope, $slug, 1);
                $sidebarBottom = renderAd('sidebar_bottom', $scope, $slug, 1);
            ?>
            <div class="lg:flex lg:items-start lg:gap-6">
                <?php if ($sidebarTop || $sidebarBottom): ?>
                <aside class="hidden lg:block lg:w-1/3 order-2">
                    <?php if ($sidebarTop): ?><div class="mb-6"><?= $sidebarTop ?></div><?php endif; ?>
                    <?php if ($sidebarBottom): ?><div class="mt-6"><?= $sidebarBottom ?></div><?php endif; ?>
                </aside>
                <?php endif; ?>

                <div class="flex-1 order-1">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php $__i = 0; foreach ($syllabuses as $syllabus): ?>
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6 card-hover">
                    <div class="flex items-start justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800 line-clamp-2 flex-1">
                            <a href="<?= SITE_URL ?>/syllabus/<?= $syllabus['slug'] ?>" class="hover:text-purple-600">
                                <?= htmlspecialchars($syllabus['title']) ?>
                            </a>
                        </h3>
                        <i class="fas fa-book text-purple-600 text-xl ml-2"></i>
                    </div>
                    
                    <div class="text-sm text-gray-600 mb-4">
                        <div class="flex items-center">
                            <i class="fas fa-clock w-4 mr-2 text-purple-600"></i>
                            Published: <?= timeAgo($syllabus['published_at']) ?>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                        <a href="<?= SITE_URL ?>/syllabus/<?= $syllabus['slug'] ?>" 
                           class="bg-purple-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-purple-700 transition-colors">
                            View Syllabus
                        </a>
                        
                        <div class="flex items-center gap-2">
                            <?php if (!empty($syllabus['download_url'])): ?>
                            <a href="<?= htmlspecialchars($syllabus['download_url']) ?>" target="_blank"
                               class="bg-primary text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 transition-colors">
                                <i class="fas fa-file-download mr-1"></i>Download PDF
                            </a>
                            <?php endif; ?>
                            <button onclick="shareSyllabus('<?= addslashes($syllabus['title']) ?>')" 
                                    class="bg-gray-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-700 transition-colors">
                                <i class="fas fa-share mr-1"></i>Share
                            </button>
                        </div>
                    </div>
                </div>
                <?php $__i++; if ($__i === 3): ?>
                    <?php $inContent = renderAd('in_content', $scope, $slug, 1); if ($inContent): ?>
                    <div class="col-span-1 md:col-span-2 lg:col-span-3">
                        <?= $inContent ?>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
                <?php endforeach; ?>
                </div>
                </div>
            </div>
            
            <?php // ItemList JSON-LD for syllabus ?>
            <?php if (!empty($syllabuses)): ?>
            <?php 
                $items = [];
                $pos = 1;
                foreach ($syllabuses as $s) {
                    $items[] = [
                        '@type' => 'ListItem',
                        'position' => $pos++,
                        'url' => rtrim(SITE_URL, '/') . '/syllabus/' . urlencode($s['slug']),
                        'name' => $s['title']
                    ];
                }
                $itemList = [
                    '@context' => 'https://schema.org',
                    '@type' => 'ItemList',
                    'name' => $pageTitle,
                    'itemListElement' => $items
                ];
            ?>
            <script type="application/ld+json">
                <?= json_encode($itemList, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
            </script>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

    <?php 
    // SEO Article section for Syllabus page (always shown, after listing section)
    $seoPageType = 'syllabus';
    include __DIR__ . '/includes/seo-article.php';
    ?>

<?php include 'includes/footer.php'; ?>
