<?php
require_once __DIR__ . '/src/config.php';
require_once __DIR__ . '/src/db.php';
require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/models/Job.php';
require_once __DIR__ . '/src/models/Result.php';
require_once __DIR__ . '/src/models/AdmitCard.php';
require_once __DIR__ . '/src/models/Syllabus.php';
require_once __DIR__ . '/src/models/Category.php';
require_once __DIR__ . '/src/models/Post.php';

$query = isset($_GET['q']) ? sanitizeInput($_GET['q']) : '';
$type = isset($_GET['type']) ? sanitizeInput($_GET['type']) : 'all';
$category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

if (!$query) {
    header('Location: index.php');
    exit;
}

$jobModel = new Job();
$resultModel = new Result();
$admitModel = new AdmitCard();
$syllabusModel = new Syllabus();
$categoryModel = new Category();
$postModel = new Post();

$results = [];
$totalResults = 0;
$categories = $categoryModel->getAll();

// Handle category-specific searches
if ($category && strpos($category, 'category-') === 0) {
    $categorySlug = substr($category, 9); // Remove 'category-' prefix
    $categoryData = $categoryModel->getBySlug($categorySlug);
    if ($categoryData) {
        $results = $jobModel->searchByCategory($query, $categoryData['id'], POSTS_PER_PAGE, ($page - 1) * POSTS_PER_PAGE);
        $totalResults = $jobModel->countSearchByCategory($query, $categoryData['id']);
        $type = 'jobs';
    }
} else {
switch ($type) {
    case 'jobs':
        $results = $jobModel->search($query, POSTS_PER_PAGE, ($page - 1) * POSTS_PER_PAGE);
        $totalResults = $jobModel->countSearch($query);
        break;
    case 'results':
        $results = $resultModel->search($query, POSTS_PER_PAGE, ($page - 1) * POSTS_PER_PAGE);
        $totalResults = $resultModel->countSearch($query);
        break;
    case 'admits':
        $results = $admitModel->search($query, POSTS_PER_PAGE, ($page - 1) * POSTS_PER_PAGE);
        $totalResults = $admitModel->countSearch($query);
        break;
    case 'syllabus':
        $results = $syllabusModel->search($query, POSTS_PER_PAGE, ($page - 1) * POSTS_PER_PAGE);
        $totalResults = $syllabusModel->countSearch($query);
        break;
    case 'posts':
        $results = $postModel->search($query, POSTS_PER_PAGE, ($page - 1) * POSTS_PER_PAGE);
        $totalResults = $postModel->countSearch($query);
        break;
    default:
        // Search all types
        $jobResults = $jobModel->search($query, 5, 0);
        $resultResults = $resultModel->search($query, 5, 0);
        $admitResults = $admitModel->search($query, 5, 0);
        $syllabusResults = $syllabusModel->search($query, 5, 0);
        $postResults = $postModel->search($query, 5, 0);
        
        $results = [
            'jobs' => $jobResults,
            'results' => $resultResults,
            'admits' => $admitResults,
            'syllabus' => $syllabusResults,
            'posts' => $postResults
        ];
        
        // Accurate total across types (not limited to the 5 we fetched)
        $totalResults = $jobModel->countSearch($query)
                        + $resultModel->countSearch($query)
                        + $admitModel->countSearch($query)
                        + $syllabusModel->countSearch($query)
                        + $postModel->countSearch($query);
        break;
}
}

$pageTitle = "Search Results for '$query'";
$metaDescription = "Search results for " . htmlspecialchars($query) . " - Jobs, Results, Admit Cards";
$currentPage = 'search';

include 'includes/header.php';
?>

    <!-- Search Header -->
    <section class="bg-white py-8 border-b">
        <div class="container mx-auto px-4">
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-4"><?= $pageTitle ?></h1>
            
            <!-- Search Form -->
            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <select id="search-category" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="all" <?= $category === 'all' || !$category ? 'selected' : '' ?>>All Categories</option>
                        <option value="jobs" <?= $type === 'jobs' ? 'selected' : '' ?>>Jobs Only</option>
                        <option value="results" <?= $type === 'results' ? 'selected' : '' ?>>Results Only</option>
                        <option value="admits" <?= $type === 'admits' ? 'selected' : '' ?>>Admit Cards Only</option>
                        <option value="syllabus" <?= $type === 'syllabus' ? 'selected' : '' ?>>Syllabus Only</option>
                        <option value="posts" <?= $type === 'posts' ? 'selected' : '' ?>>Articles Only</option>
                        <?php if (!empty($categories)): ?>
                            <optgroup label="Job Categories">
                                <?php foreach ($categories as $cat): ?>
                                    <option value="category-<?= $cat['slug'] ?>" <?= $category === 'category-' . $cat['slug'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="flex-2">
                    <div class="relative">
                        <input type="text" id="search-query" value="<?= htmlspecialchars($query) ?>" placeholder="Search..." 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                        <button id="search-query-btn" class="absolute right-3 top-3 text-gray-500 hover:text-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <p class="text-gray-600 mt-4">Found <?= $totalResults ?> results</p>
        </div>
    </section>

    <!-- Search Results -->
    <section class="py-8">
        <div class="container mx-auto px-4">
            <?php if ($totalResults === 0): ?>
            <div class="text-center py-12">
                <i class="fas fa-search text-gray-400 text-6xl mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-600 mb-2">No results found</h3>
                <p class="text-gray-500">Try different keywords or browse our categories.</p>
                <div class="mt-6 flex flex-wrap justify-center gap-4">
                    <a href="<?= SITE_URL ?>/jobs" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-blue-700">Browse Jobs</a>
                    <a href="<?= SITE_URL ?>/results" class="bg-accent text-white px-6 py-2 rounded-lg hover:bg-green-700">Browse Results</a>
                    <a href="<?= SITE_URL ?>/admit" class="bg-yellow-600 text-white px-6 py-2 rounded-lg hover:bg-yellow-700">Browse Admit Cards</a>
                </div>
            </div>
            <?php elseif ($type === 'all'): ?>
            <!-- All Types Search Results -->
            
            <?php if (!empty($results['jobs'])): ?>
            <div class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-gray-800">
                        <i class="fas fa-briefcase text-primary mr-2"></i>Jobs
                    </h2>
                    <a href="search.php?q=<?= urlencode($query) ?>&type=jobs" class="text-primary hover:text-blue-700">View All</a>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($results['jobs'] as $job): ?>
                    <div class="bg-white rounded-lg shadow-md p-4 hover:shadow-lg transition-shadow">
                        <h3 class="font-semibold text-gray-800 mb-2 line-clamp-2">
                            <a href="<?= SITE_URL ?>/job/<?= $job['slug'] ?>" class="hover:text-primary">
                                <?= htmlspecialchars($job['title']) ?>
                            </a>
                        </h3>
                        <div class="text-sm text-gray-600 mb-2">
                            <i class="fas fa-building mr-1"></i><?= htmlspecialchars($job['organization']) ?>
                        </div>
                        <div class="text-xs text-gray-500"><?= timeAgo($job['published_at']) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($results['results'])): ?>
            <div class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-gray-800">
                        <i class="fas fa-trophy text-accent mr-2"></i>Results
                    </h2>
                    <a href="search.php?q=<?= urlencode($query) ?>&type=results" class="text-accent hover:text-green-700">View All</a>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($results['results'] as $result): ?>
                    <div class="bg-white rounded-lg shadow-md p-4 hover:shadow-lg transition-shadow">
                        <h3 class="font-semibold text-gray-800 mb-2 line-clamp-2">
                            <a href="<?= SITE_URL ?>/result/<?= $result['slug'] ?>" class="hover:text-accent">
                                <?= htmlspecialchars($result['title']) ?>
                            </a>
                        </h3>
                        <div class="text-xs text-gray-500"><?= timeAgo($result['published_at']) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($results['admits'])): ?>
            <div class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-gray-800">
                        <i class="fas fa-id-card text-yellow-600 mr-2"></i>Admit Cards
                    </h2>
                    <a href="search.php?q=<?= urlencode($query) ?>&type=admits" class="text-yellow-600 hover:text-yellow-700">View All</a>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($results['admits'] as $admit): ?>
                    <div class="bg-white rounded-lg shadow-md p-4 hover:shadow-lg transition-shadow">
                        <h3 class="font-semibold text-gray-800 mb-2 line-clamp-2">
                            <a href="<?= SITE_URL ?>/admit/<?= $admit['slug'] ?>" class="hover:text-yellow-600">
                                <?= htmlspecialchars($admit['title']) ?>
                            </a>
                        </h3>
                        <div class="text-xs text-gray-500"><?= timeAgo($admit['published_at']) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($results['syllabus'])): ?>
            <div class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-gray-800">
                        <i class="fas fa-book text-purple-600 mr-2"></i>Syllabus
                    </h2>
                    <a href="search.php?q=<?= urlencode($query) ?>&type=syllabus" class="text-purple-600 hover:text-purple-700">View All</a>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($results['syllabus'] as $syllabus): ?>
                    <div class="bg-white rounded-lg shadow-md p-4 hover:shadow-lg transition-shadow">
                        <h3 class="font-semibold text-gray-800 mb-2 line-clamp-2">
                            <a href="<?= SITE_URL ?>/syllabus/<?= $syllabus['slug'] ?>" class="hover:text-purple-600">
                                <?= htmlspecialchars($syllabus['title']) ?>
                            </a>
                        </h3>
                        <div class="text-xs text-gray-500"><?= timeAgo($syllabus['published_at']) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($results['posts'])): ?>
            <div class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-gray-800">
                        <i class="fas fa-newspaper text-blue-700 mr-2"></i>Articles
                    </h2>
                    <a href="search.php?q=<?= urlencode($query) ?>&type=posts" class="text-primary hover:text-blue-700">View All</a>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($results['posts'] as $post): ?>
                    <div class="bg-white rounded-lg shadow-md p-4 hover:shadow-lg transition-shadow">
                        <h3 class="font-semibold text-gray-800 mb-2 line-clamp-2">
                            <a href="<?= SITE_URL ?>/post/<?= $post['slug'] ?>" class="hover:text-primary">
                                <?= htmlspecialchars($post['title']) ?>
                            </a>
                        </h3>
                        <div class="text-xs text-gray-500"><?= timeAgo($post['published_at'] ?? $post['updated_at'] ?? '') ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <!-- Single Type Search Results -->
            <?php 
                $totalPages = max(1, (int)ceil($totalResults / POSTS_PER_PAGE));
                $baseParams = ['q' => $query];
                // Preserve either type or category in pagination
                if (!empty($category)) { $baseParams['category'] = $category; }
                else { $baseParams['type'] = $type; }
            ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($results as $item): ?>
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3 line-clamp-2">
                        <?php
                        $detailUrl = '';
                        switch ($type) {
                            case 'jobs':
                                $detailUrl = SITE_URL . "/job/{$item['slug']}";
                                break;
                            case 'results':
                                $detailUrl = SITE_URL . "/result/{$item['slug']}";
                                break;
                            case 'admits':
                                $detailUrl = SITE_URL . "/admit/{$item['slug']}";
                                break;
                            case 'syllabus':
                                $detailUrl = SITE_URL . "/syllabus/{$item['slug']}";
                                break;
                            case 'posts':
                                $detailUrl = SITE_URL . "/post/{$item['slug']}";
                                break;
                        }
                        ?>
                        <a href="<?= $detailUrl ?>" class="hover:text-primary">
                            <?= htmlspecialchars($item['title']) ?>
                        </a>
                    </h3>
                    
                    <?php if ($type === 'jobs' && isset($item['organization'])): ?>
                    <div class="text-sm text-gray-600 mb-2">
                        <i class="fas fa-building mr-1"></i><?= htmlspecialchars($item['organization']) ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="text-xs text-gray-500 mb-4"><?= timeAgo($item['published_at']) ?></div>
                    
                    <a href="<?= $detailUrl ?>" class="bg-primary text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 transition-colors">
                        View Details
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

<?php include 'includes/footer.php'; ?>
