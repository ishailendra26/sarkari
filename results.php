<?php
require_once __DIR__ . '/src/config.php';
require_once __DIR__ . '/src/db.php';
require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/models/Result.php';
require_once __DIR__ . '/src/models/Category.php';

$resultModel = new Result();
$categoryModel = new Category();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : null;
$category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : null;

if ($search) {
    $results = $resultModel->search($search, POSTS_PER_PAGE, ($page - 1) * POSTS_PER_PAGE);
    $totalResults = count($resultModel->search($search, 1000, 0));
} else {
    $results = $resultModel->getAll(POSTS_PER_PAGE, ($page - 1) * POSTS_PER_PAGE);
    $totalResults = $resultModel->getCount();
}

$categories = $categoryModel->getAll();
$pagination = paginate($totalResults, $page, POSTS_PER_PAGE);

$pageTitle = $search ? "Search Results for '$search'" : "Latest Government Job Results";
$metaDescription = $search ? "Search results for '$search' government job results" : "Find latest government job results, merit lists and selection lists.";
$currentPage = 'results';

include 'includes/header.php';
?>

    <!-- Page Header -->
    <section class="bg-white py-8 border-b">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-2"><?= $pageTitle ?></h1>
                    <p class="text-gray-600">Found <?= $totalResults ?> results</p>
                </div>
                
                <div class="mt-4 md:mt-0">
                    <div class="relative">
                        <input type="text" id="result-search" value="<?= $search ?>" placeholder="Search results..." 
                               class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary w-full md:w-64">
                        <button id="result-search-btn" class="absolute right-2 top-2 text-gray-500 hover:text-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Results Grid -->
    <section class="py-8">
        <div class="container mx-auto px-4">
            <?php [$scope, $slug] = inferPageScopeFromRequest(); ?>
            <?php if (empty($results)): ?>
            <div class="text-center py-12">
                <i class="fas fa-search text-gray-400 text-6xl mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-600 mb-2">No results found</h3>
                <p class="text-gray-500">Try adjusting your search criteria.</p>
                <a href="<?= SITE_URL ?>/results" class="inline-block mt-4 bg-primary text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                    Browse All Results
                </a>
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
                <?php $__i = 0; foreach ($results as $result): ?>
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6 card-hover">
                    <div class="flex items-start justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800 line-clamp-2 flex-1">
                            <a href="<?= SITE_URL ?>/result/<?= $result['slug'] ?>" class="hover:text-accent">
                                <?= htmlspecialchars($result['title']) ?>
                            </a>
                        </h3>
                        <span class="badge badge-success ml-2">New</span>
                    </div>
                    
                    <div class="space-y-2 text-sm text-gray-600 mb-4">
                        <?php if ($result['exam_date']): ?>
                        <div class="flex items-center">
                            <i class="fas fa-calendar w-4 mr-2 text-accent"></i>
                            Exam Date: <?= formatDate($result['exam_date']) ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="flex items-center">
                            <i class="fas fa-clock w-4 mr-2 text-accent"></i>
                            Published: <?= timeAgo($result['published_at']) ?>
                        </div>
                    </div>
                    
                    <?php if ($result['content']): ?>
                    <p class="text-gray-700 text-sm mb-4 line-clamp-3">
                        <?= htmlspecialchars(excerpt(strip_tags($result['content']), 120)) ?>
                    </p>
                    <?php endif; ?>
                    
                    <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                        <a href="<?= SITE_URL ?>/result/<?= $result['slug'] ?>" 
                           class="bg-accent text-white px-4 py-2 rounded-lg text-sm hover:bg-green-700 transition-colors">
                            View Details
                        </a>
                        
                        <?php if ($result['download_link']): ?>
                        <a href="<?= htmlspecialchars($result['download_link']) ?>" target="_blank"
                           class="bg-primary text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 transition-colors">
                            <i class="fas fa-download mr-1"></i>Download
                        </a>
                        <?php endif; ?>
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

            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
            <div class="pagination">
                <?php if ($pagination['has_prev']): ?>
                <a href="?page=<?= $pagination['prev_page'] ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                    <i class="fas fa-chevron-left mr-1"></i> Previous
                </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                <?php if ($i == $pagination['current_page']): ?>
                <span class="current"><?= $i ?></span>
                <?php else: ?>
                <a href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>"><?= $i ?></a>
                <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($pagination['has_next']): ?>
                <a href="?page=<?= $pagination['next_page'] ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                    Next <i class="fas fa-chevron-right ml-1"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

<?php include 'includes/footer.php'; ?>

