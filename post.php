<?php
require_once __DIR__ . '/src/config.php';
require_once __DIR__ . '/src/db.php';
require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/models/Post.php';
require_once __DIR__ . '/src/models/Author.php';

$postModel = new Post();
$authorModel = new Author();

$slug = isset($_GET['slug']) ? sanitizeInput($_GET['slug']) : '';
if (!$slug) {
    header('Location: posts.php');
    exit;
}

$post = $postModel->getBySlug($slug);
$author = null;
if ($post && !empty($post['author_id'])) {
    $author = $authorModel->getById($post['author_id']);
}

if (!$post) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

$pageTitle = $post['meta_title'] ?: $post['title'];
$metaDescription = $post['meta_description'] ?: ($post['excerpt'] ? excerpt(strip_tags($post['excerpt']), 50) : excerpt(strip_tags($post['content']), 160));
$currentPage = 'posts';

// Canonical & JSON-LD
$canonicalUrl = SITE_URL . '/post/' . urlencode($post['slug']);
$authorName = $author['name'] ?? 'SarkariJobs';
$publishedAt = $post['published_at'] ?? $post['updated_at'] ?? null;
// Resolve hero image with fallback from settings
$heroImage = !empty($post['thumbnail_url']) ? $post['thumbnail_url'] : (getSetting('default_post_thumbnail') ?: null);

$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => $post['title'],
    'description' => $metaDescription,
    'datePublished' => $publishedAt,
    'dateModified' => $post['updated_at'] ?? $publishedAt,
    'author' => [
        '@type' => 'Person',
        'name' => $authorName
    ],
    'image' => !empty($heroImage) ? [$heroImage] : null,
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
            'name' => 'Articles', 'item' => SITE_URL . '/posts'
        ],
        [
            '@type' => 'ListItem', 'position' => 3,
            'name' => $post['title'], 'item' => $canonicalUrl
        ]
    ]
];
$additionalHead = "<link rel=\"canonical\" href=\"{$canonicalUrl}\">\n" .
    '<script type="application/ld+json">' . json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n" .
    '<script type="application/ld+json">' . json_encode($breadcrumbs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';

include 'includes/header.php';
?>

    <!-- Post Details -->
    <section class="py-8">
        <div class="container mx-auto px-4">
            <div class="max-w-3xl mx-auto">
                <!-- Breadcrumbs -->
                <nav class="text-sm text-gray-600 mb-4" aria-label="Breadcrumb">
                    <ol class="list-reset flex flex-wrap items-center gap-1">
                        <li>
                            <a href="<?= SITE_URL ?>/" class="hover:text-primary">Home</a>
                        </li>
                        <li><span class="mx-2">/</span></li>
                        <li>
                            <a href="<?= SITE_URL ?>/posts" class="hover:text-primary">Articles</a>
                        </li>
                        <li><span class="mx-2">/</span></li>
                        <li class="text-gray-800 line-clamp-1" aria-current="page"><?= htmlspecialchars($post['title']) ?></li>
                    </ol>
                </nav>
                <article class="bg-white rounded-lg shadow-md p-6 md:p-8">
                    <header class="mb-6 text-center">
                        <div class="flex flex-wrap justify-center gap-3 text-sm text-gray-600 mb-3">
                            <?php if (!empty($post['category_name'])): ?>
                            <span class="badge badge-primary"><?= htmlspecialchars($post['category_name']) ?></span>
                            <?php endif; ?>
                            <span><i class="fas fa-clock mr-1"></i><?= timeAgo($post['published_at'] ?? $post['updated_at'] ?? '') ?></span>
                        </div>
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-800">
                            <?= htmlspecialchars($post['title']) ?>
                        </h1>
                        <div class="mt-3">
                            <?= renderAuthorBadge($author) ?>
                        </div>
                    </header>

                    <?php if (!empty($heroImage)): ?>
                    <figure class="mb-6">
                        <img src="<?= htmlspecialchars($heroImage) ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="w-full rounded-lg object-cover">
                    </figure>
                    <?php endif; ?>

                    <?php if (!empty($post['excerpt'])): ?>
                    <p class="text-gray-600 italic mb-6">"<?= htmlspecialchars($post['excerpt']) ?>"</p>
                    <?php endif; ?>

                    <?php [$scope, $slugScope] = inferPageScopeFromRequest(); ?>
                    <?php $inContent = renderAd('in_content', $scope, $slugScope, 1); if ($inContent): ?>
                    <div class="my-6">
                        <?= $inContent ?>
                    </div>
                    <?php endif; ?>

                    <div class="prose max-w-none text-gray-800 leading-relaxed">
                        <?= $post['content'] ?>
                    </div>
                </article>

                <!-- Related Articles -->
                <div class="mt-8 bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-6">Recent Articles</h3>
                    <?php 
                        $related = $postModel->getLatest(6);
                        $related = array_filter($related, function($r) use ($post) { return $r['id'] !== $post['id']; });
                        $related = array_slice($related, 0, 4);
                    ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($related as $rp): ?>
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                            <h4 class="font-semibold text-gray-800 mb-2 line-clamp-2">
                                <a href="<?= SITE_URL ?>/post/<?= urlencode($rp['slug']) ?>" class="hover:text-primary">
                                    <?= htmlspecialchars($rp['title']) ?>
                                </a>
                            </h4>
                            <div class="text-sm text-gray-600">
                                <i class="fas fa-clock mr-1"></i><?= timeAgo($rp['published_at'] ?? $rp['updated_at'] ?? '') ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php include 'includes/footer.php'; ?>
