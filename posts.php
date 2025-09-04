<?php
require_once __DIR__ . '/src/config.php';
require_once __DIR__ . '/src/db.php';
require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/models/Post.php';
require_once __DIR__ . '/src/models/Category.php';

$postModel = new Post();
$categoryModel = new Category();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : null;
$category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : null;

$perPage = POSTS_PER_PAGE ?? 12;

if ($search) {
    $posts = $postModel->search($search, $perPage, ($page - 1) * $perPage);
    $totalPosts = $postModel->countSearch($search);
} else {
    $posts = $postModel->getAll($perPage, ($page - 1) * $perPage, $category);
    $totalPosts = $postModel->getCount($category);
}

$categories = $categoryModel->getAll();
$pagination = paginate($totalPosts, $page, $perPage);

$pageTitle = $search ? "Search Articles: $search" : 'Latest Articles & Updates';
$metaDescription = $search ? "Search results for articles about $search" : 'Read latest articles, updates, and important information related to Sarkari jobs and exams.';
$currentPage = 'posts';

include 'includes/header.php';
?>

    <!-- Breadcrumb -->
    <div class="bg-white border-b">
        <div class="container mx-auto px-4 py-3">
            <div class="breadcrumb">
                <a href="<?= SITE_URL ?>">Home</a>
                <i class="fas fa-chevron-right text-xs"></i>
                <span>Articles</span>
                <?php if ($category): ?>
                <i class="fas fa-chevron-right text-xs"></i>
                <span><?= htmlspecialchars(ucfirst($category)) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Page Header -->
    <section class="bg-white py-8">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-2"><?= htmlspecialchars($pageTitle) ?></h1>
                    <p class="text-gray-600">Found <?= $totalPosts ?> articles</p>
                </div>

                <div class="mt-4 md:mt-0 flex flex-col md:flex-row gap-4">
                    <div class="relative">
                        <input type="text" id="post-search" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Search articles..."
                               class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary w-full md:w-64">
                        <button id="post-search-btn" class="absolute right-2 top-2 text-gray-500 hover:text-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <select id="category-filter" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['slug'] ?>" <?= $category === $cat['slug'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </section>

    <!-- Posts Grid -->
    <section class="py-8">
        <div class="container mx-auto px-4">
            <?php [$scope, $slug] = inferPageScopeFromRequest(); ?>
            <?php if (empty($posts)): ?>
            <div class="text-center py-12">
                <i class="fas fa-newspaper text-gray-400 text-6xl mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-600 mb-2">No articles found</h3>
                <p class="text-gray-500">Try adjusting your search or filter.</p>
            </div>
            <?php else: ?>
            <?php 
                $sidebarTop = renderAd('sidebar_top', $scope, $slug, 1);
                $sidebarBottom = renderAd('sidebar_bottom', $scope, $slug, 1);
            ?>
            <div class="lg:flex lg:items-start lg:gap-6">
                <?php if ($sidebarTop || $sidebarBottom): ?>
                <aside class="hidden lg:block lg:w-1/3 order-2">
                    <?php if ($sidebarTop): ?>
                    <div class="mb-6"><?= $sidebarTop ?></div>
                    <?php endif; ?>
                    <?php if ($sidebarBottom): ?>
                    <div class="mt-6"><?= $sidebarBottom ?></div>
                    <?php endif; ?>
                </aside>
                <?php endif; ?>

                <div class="flex-1 order-1">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php $__i = 0; foreach ($posts as $p): ?>
                <?php $thumb = !empty($p['thumbnail_url']) ? $p['thumbnail_url'] : (getSetting('default_post_thumbnail') ?: null); ?>
                <article class="bg-white rounded-lg shadow-md overflow-hidden card-hover">
                    <?php if (!empty($thumb)): ?>
                    <a href="<?= SITE_URL ?>/post/<?= urlencode($p['slug']) ?>" class="block aspect-[16/9] bg-gray-100">
                        <img src="<?= htmlspecialchars($thumb) ?>" alt="<?= htmlspecialchars($p['title']) ?>" class="w-full h-full object-contain" loading="lazy">
                    </a>
                    <?php else: ?>
                    <a href="<?= SITE_URL ?>/post/<?= urlencode($p['slug']) ?>" class="block aspect-[16/9] bg-gray-100 flex items-center justify-center text-gray-400">
                        <i class="fas fa-image text-3xl"></i>
                    </a>
                    <?php endif; ?>
                    <div class="p-5">
                    <h3 class="text-lg font-semibold text-gray-800 line-clamp-2 mb-2">
                        <a href="<?= SITE_URL ?>/post/<?= urlencode($p['slug']) ?>" class="hover:text-primary">
                            <?= htmlspecialchars($p['title']) ?>
                        </a>
                    </h3>
                    <?php if (!empty($p['excerpt'])): ?>
                    <p class="text-gray-600 text-sm line-clamp-3 mb-3">&nbsp;<?= htmlspecialchars($p['excerpt']) ?></p>
                    <?php endif; ?>
                    <div class="text-xs text-gray-500 flex items-center justify-between">
                        <span class="flex items-center gap-2">
                            <?php if (!empty($p['author_avatar'])): ?>
                                <img src="<?= htmlspecialchars($p['author_avatar']) ?>" alt="<?= htmlspecialchars($p['author_name'] ?? 'Author') ?>" class="w-5 h-5 rounded-full object-cover">
                            <?php endif; ?>
                            <?php if (!empty($p['author_name'])): ?>
                                <span class="text-gray-700"><?= htmlspecialchars($p['author_name']) ?></span>
                                <span class="text-gray-400">•</span>
                            <?php endif; ?>
                            <span><i class="fas fa-clock mr-1"></i><?= timeAgo($p['published_at'] ?? $p['updated_at'] ?? '') ?></span>
                        </span>
                        <?php if (!empty($p['category_name'])): ?>
                        <span class="badge badge-primary"><?= htmlspecialchars($p['category_name']) ?></span>
                        <?php endif; ?>
                    </div>
                    </div>
                </article>
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
            <div class="pagination mt-8">
                <?php if ($pagination['has_prev']): ?>
                <a href="?page=<?= $pagination['prev_page'] ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $category ? '&category=' . $category : '' ?>">
                    <i class="fas fa-chevron-left mr-1"></i> Previous
                </a>
                <?php endif; ?>
                <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                    <?php if ($i == $pagination['current_page']): ?>
                    <span class="current"><?= $i ?></span>
                    <?php else: ?>
                    <a href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $category ? '&category=' . $category : '' ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                <?php if ($pagination['has_next']): ?>
                <a href="?page=<?= $pagination['next_page'] ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $category ? '&category=' . $category : '' ?>">
                    Next <i class="fas fa-chevron-right ml-1"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

    <script>
        document.getElementById('post-search-btn').addEventListener('click', applyFilters);
        document.getElementById('post-search').addEventListener('keypress', function(e){ if(e.key==='Enter'){applyFilters();} });
        document.getElementById('category-filter').addEventListener('change', applyFilters);
        function applyFilters(){
            const s = document.getElementById('post-search').value;
            const c = document.getElementById('category-filter').value;
            let url = 'posts.php';
            const params = [];
            if (s) params.push('search=' + encodeURIComponent(s));
            if (c) params.push('category=' + encodeURIComponent(c));
            if (params.length) url += '?' + params.join('&');
            window.location.href = url;
        }
    </script>

<?php include 'includes/footer.php'; ?>
