<?php
require_once __DIR__ . '/src/config.php';
require_once __DIR__ . '/src/db.php';
require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/models/AdmitCard.php';
require_once __DIR__ . '/src/models/Category.php';

// Handle slug redirect
if (isset($_GET['slug'])) {
    header('Location: admit-detail.php?slug=' . $_GET['slug']);
    exit;
}

$admitModel = new AdmitCard();
$categoryModel = new Category();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : null;
$category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : null;

if ($search) {
    $admits = $admitModel->search($search, POSTS_PER_PAGE, ($page - 1) * POSTS_PER_PAGE);
    $totalAdmits = count($admitModel->search($search, 1000, 0));
} else {
    $admits = $admitModel->getAll(POSTS_PER_PAGE, ($page - 1) * POSTS_PER_PAGE);
    $totalAdmits = $admitModel->getCount();
}

$categories = $categoryModel->getAll();
$pagination = paginate($totalAdmits, $page, POSTS_PER_PAGE);

$pageTitle = $search ? "Search Admit Cards for '$search'" : "Latest Admit Cards";
$metaDescription = $search ? "Search results for '$search' admit cards" : "Download latest government job admit cards and hall tickets.";
$currentPage = 'admit';

include 'includes/header.php';
?>

    <!-- Page Header -->
    <section class="bg-yellow-50 py-8">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-2">
                        <i class="fas fa-id-card text-yellow-600 mr-2"></i><?= $pageTitle ?>
                    </h1>
                    <p class="text-gray-600">Found <?= $totalAdmits ?> admit cards</p>
                </div>
                
                <div class="mt-4 md:mt-0">
                    <div class="relative">
                        <input type="text" id="admit-search" value="<?= $search ?>" placeholder="Search admit cards..." 
                               class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary w-full md:w-64">
                        <button id="admit-search-btn" class="absolute right-2 top-2 text-gray-500 hover:text-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Admit Cards Grid -->
    <section class="py-8">
        <div class="container mx-auto px-4">
            <?php [$scope, $slug] = inferPageScopeFromRequest(); ?>
            <?php if (empty($admits)): ?>
            <div class="text-center py-12">
                <i class="fas fa-id-card text-gray-400 text-6xl mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-600 mb-2">No admit cards found</h3>
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
                <?php $__i = 0; foreach ($admits as $admit): ?>
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6 card-hover">
                    <div class="flex items-start justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800 line-clamp-2 flex-1">
                            <a href="<?= SITE_URL ?>/admit/<?= $admit['slug'] ?>" class="hover:text-yellow-600">
                                <?= htmlspecialchars($admit['title']) ?>
                            </a>
                        </h3>
                        <i class="fas fa-id-card text-yellow-600 text-xl ml-2"></i>
                    </div>
                    
                    <div class="space-y-2 text-sm text-gray-600 mb-4">
                        <?php if ($admit['exam_date']): ?>
                        <div class="flex items-center">
                            <i class="fas fa-calendar w-4 mr-2 text-yellow-600"></i>
                            Exam Date: <?= formatDate($admit['exam_date']) ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="flex items-center">
                            <i class="fas fa-clock w-4 mr-2 text-yellow-600"></i>
                            Published: <?= timeAgo($admit['published_at']) ?>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                        <a href="<?= SITE_URL ?>/admit/<?= $admit['slug'] ?>" 
                           class="bg-yellow-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-yellow-700 transition-colors">
                            View Details
                        </a>
                        
                        <?php if ($admit['download_url']): ?>
                        <a href="<?= htmlspecialchars($admit['download_url']) ?>" target="_blank"
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

