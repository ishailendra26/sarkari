<?php
require_once __DIR__ . '/src/config.php';
require_once __DIR__ . '/src/db.php';
require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/models/Job.php';
require_once __DIR__ . '/src/models/Category.php';

$jobModel = new Job();
$categoryModel = new Category();

// Get parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : null;
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : null;

// Get jobs
if ($search || $category) {
    if ($search && $category) {
        // For search with category, we'll use search and filter results
        $jobs = $jobModel->search($search, POSTS_PER_PAGE, ($page - 1) * POSTS_PER_PAGE);
        $totalJobs = count($jobModel->search($search, 1000, 0));
    } elseif ($search) {
        $jobs = $jobModel->search($search, POSTS_PER_PAGE, ($page - 1) * POSTS_PER_PAGE);
        $totalJobs = count($jobModel->search($search, 1000, 0));
    } else {
        $jobs = $jobModel->getAll(POSTS_PER_PAGE, ($page - 1) * POSTS_PER_PAGE, $category);
        $totalJobs = $jobModel->getCount($category);
    }
} else {
    $jobs = $jobModel->getAll(POSTS_PER_PAGE, ($page - 1) * POSTS_PER_PAGE);
    $totalJobs = $jobModel->getCount();
}

$categories = $categoryModel->getAll();
$pagination = paginate($totalJobs, $page, POSTS_PER_PAGE);

$pageTitle = $search ? "Search Results for '$search'" : ($category ? ucfirst($category) . " Jobs" : "Latest Government Jobs");
$metaDescription = $search ? "Search results for '$search' government jobs" : ($category ? "Latest $category government job notifications and vacancies" : "Find latest government job notifications, vacancies and recruitment updates.");
$currentPage = 'jobs';

include 'includes/header.php';
?>

    <!-- Breadcrumb -->
    <div class="bg-white border-b">
        <div class="container mx-auto px-4 py-3">
            <div class="breadcrumb">
                <a href="<?= SITE_URL ?>">Home</a>
                <i class="fas fa-chevron-right text-xs"></i>
                <span>Jobs</span>
                <?php if ($category): ?>
                <i class="fas fa-chevron-right text-xs"></i>
                <span><?= ucfirst($category) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Page Header -->
    <section class="bg-white py-8">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-2"><?= $pageTitle ?></h1>
                    <p class="text-gray-600">Found <?= $totalJobs ?> jobs</p>
                </div>
                
                <!-- Search and Filters -->
                <div class="mt-4 md:mt-0 flex flex-col md:flex-row gap-4">
                    <div class="relative">
                        <input type="text" id="job-search" value="<?= $search ?>" placeholder="Search jobs..." 
                               class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary w-full md:w-64">
                        <button id="job-search-btn" class="absolute right-2 top-2 text-gray-500 hover:text-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    
                    <select id="category-filter" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="">All Categories</option>
                        <option value="central" <?= $category === 'central' ? 'selected' : '' ?>>Central Govt</option>
                        <option value="state" <?= $category === 'state' ? 'selected' : '' ?>>State Govt</option>
                        <option value="railway" <?= $category === 'railway' ? 'selected' : '' ?>>Railway</option>
                        <option value="banking" <?= $category === 'banking' ? 'selected' : '' ?>>Banking</option>
                        <option value="defense" <?= $category === 'defense' ? 'selected' : '' ?>>Defense</option>
                    </select>
                </div>
            </div>
        </div>
    </section>

    <!-- Jobs Grid -->
    <section class="py-8">
        <div class="container mx-auto px-4">
            <?php [$scope, $slug] = inferPageScopeFromRequest(); ?>
            <?php if (empty($jobs)): ?>
            <div class="text-center py-12">
                <i class="fas fa-search text-gray-400 text-6xl mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-600 mb-2">No jobs found</h3>
                <p class="text-gray-500">Try adjusting your search criteria or browse all jobs.</p>
                <a href="<?= SITE_URL ?>/jobs" class="inline-block mt-4 bg-primary text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                    Browse All Jobs
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
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <?php $__i = 0; foreach ($jobs as $job): ?>
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6 card-hover">
                    <div class="flex items-start justify-between mb-4">
                        <h3 class="text-xl font-semibold text-gray-800 line-clamp-2 flex-1 mr-4">
                            <a href="<?= SITE_URL ?>/job/<?= $job['slug'] ?>" class="hover:text-primary">
                                <?= htmlspecialchars($job['title']) ?>
                            </a>
                        </h3>
                        
                        <?php if ($job['last_date']): ?>
                        <?php 
                        $daysLeft = floor((strtotime($job['last_date']) - time()) / 86400);
                        $isExpiring = $daysLeft <= 7 && $daysLeft >= 0;
                        $isExpired = $daysLeft < 0;
                        ?>
                        <span class="badge <?= $isExpired ? 'badge-danger' : ($isExpiring ? 'badge-warning' : 'badge-success') ?>">
                            <?php if ($isExpired): ?>
                                Expired
                            <?php elseif ($isExpiring): ?>
                                <?= $daysLeft ?> days left
                            <?php else: ?>
                                <?= $daysLeft ?> days left
                            <?php endif; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4 text-sm text-gray-600">
                        <?php if ($job['organization']): ?>
                        <div class="flex items-center">
                            <i class="fas fa-building w-4 mr-2 text-primary"></i>
                            <?= htmlspecialchars($job['organization']) ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($job['location']): ?>
                        <div class="flex items-center">
                            <i class="fas fa-map-marker-alt w-4 mr-2 text-primary"></i>
                            <?= htmlspecialchars($job['location']) ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($job['vacancy_count']): ?>
                        <div class="flex items-center">
                            <i class="fas fa-users w-4 mr-2 text-primary"></i>
                            <?= $job['vacancy_count'] ?> Vacancies
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($job['last_date']): ?>
                        <div class="flex items-center">
                            <i class="fas fa-calendar w-4 mr-2 text-primary"></i>
                            Last Date: <?= formatDate($job['last_date']) ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($job['educational_qualification']): ?>
                        <div class="flex items-center md:col-span-2">
                            <i class="fas fa-graduation-cap w-4 mr-2 text-primary"></i>
                            <?= htmlspecialchars(excerpt(strip_tags($job['educational_qualification']), 50)) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                        <span class="text-xs text-gray-500">
                            <i class="fas fa-clock mr-1"></i>
                            <?= timeAgo($job['published_at']) ?>
                        </span>
                        <div class="flex gap-2">
                            <a href="<?= SITE_URL ?>/job/<?= $job['slug'] ?>" 
                               class="bg-primary text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 transition-colors">
                                View Details
                            </a>
                            <?php if ($job['apply_link']): ?>
                            <a href="<?= htmlspecialchars($job['apply_link']) ?>" target="_blank"
                               class="bg-accent text-white px-4 py-2 rounded-lg text-sm hover:bg-green-700 transition-colors">
                                Apply Now <i class="fas fa-external-link-alt ml-1"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php $__i++; if ($__i === 3): ?>
                    <?php $inContent = renderAd('in_content', $scope, $slug, 1); if ($inContent): ?>
                    <div class="col-span-1 lg:col-span-2">
                        <?= $inContent ?>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
                <?php endforeach; ?>
                </div>
                </div>
            </div>

            <?php // ItemList JSON-LD for jobs ?>
            <?php if (!empty($jobs)): ?>
            <?php 
                $items = [];
                $pos = 1;
                foreach ($jobs as $j) {
                    $items[] = [
                        '@type' => 'ListItem',
                        'position' => $pos++,
                        'url' => rtrim(SITE_URL, '/') . '/job/' . urlencode($j['slug']),
                        'name' => $j['title']
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

            <?php 
            // SEO Article section for Jobs page (appears after listing cards)
            $seoPageType = 'jobs';
            include __DIR__ . '/includes/seo-article.php';
            ?>

            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
            <div class="pagination">
                <?php if ($pagination['has_prev']): ?>
                <a href="?page=<?= $pagination['prev_page'] ?><?= $category ? '&category=' . $category : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                    <i class="fas fa-chevron-left mr-1"></i> Previous
                </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                <?php if ($i == $pagination['current_page']): ?>
                <span class="current"><?= $i ?></span>
                <?php else: ?>
                <a href="?page=<?= $i ?><?= $category ? '&category=' . $category : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>"><?= $i ?></a>
                <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($pagination['has_next']): ?>
                <a href="?page=<?= $pagination['next_page'] ?><?= $category ? '&category=' . $category : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                    Next <i class="fas fa-chevron-right ml-1"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

<?php include 'includes/footer.php'; ?>

