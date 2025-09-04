<?php
require_once __DIR__ . '/src/config.php';
require_once __DIR__ . '/src/db.php';
require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/models/Job.php';
require_once __DIR__ . '/src/models/Result.php';
require_once __DIR__ . '/src/models/AdmitCard.php';
require_once __DIR__ . '/src/models/Syllabus.php';
require_once __DIR__ . '/src/models/Post.php';
require_once __DIR__ . '/src/models/Category.php';

$jobModel = new Job();
$resultModel = new Result();
$admitModel = new AdmitCard();
$syllabusModel = new Syllabus();
$postModel = new Post();
$categoryModel = new Category();

// Get latest content for homepage (with error handling)
$latestJobs = [];
$latestResults = [];
$latestAdmits = [];
$expiringJobs = [];
$categories = [];
$dbError = false;

try {
    // Check if database connection exists
    $db = getDB();
    if ($db && $db->getConnection()) {
        // Fetch larger sets for redesigned homepage
        $latestJobs = $jobModel->getLatest(10);
        $latestResults = $resultModel->getLatest(10);
        $latestAdmits = $admitModel->getLatest(10);
        $expiringJobs = $jobModel->getExpiringSoon(7, 6);
        $latestSyllabus = $syllabusModel->getLatest(8);
        $categories = $categoryModel->getAll();
        // Optionally get posts if available (not all installs will use this)
        $latestPosts = method_exists($postModel, 'getLatest') ? $postModel->getLatest(6) : [];
    } else {
        $dbError = true;
    }
} catch (Exception $e) {
    // If database doesn't exist, show setup message
    $dbError = true;
    error_log("Homepage database error: " . $e->getMessage());
}

$pageTitle = "Latest Government Jobs, Results & Admit Cards";
$metaDescription = "Find the latest Sarkari jobs, exam results, admit cards, and exam syllabus in one place. Daily updates, category-wise filters, and SEO-friendly links for quick access to opportunities.";
$metaKeywords = "sarkari jobs, government jobs, exam results, admit cards, syllabus, latest jobs, sarkari result, rojgar";
$hideHeaderSearch = false;

// SEO: Open Graph, Twitter Cards, and JSON-LD
$introImage = SITE_URL . '/assets/images/examszbanner.svg'; // add this image to improve SEO appearance (optional)
// Fallback to a reliable placeholder if local file is missing
$introLocalPath = __DIR__ . '/assets/images/examszbanner.svg';
if (!file_exists($introLocalPath)) {
    $introImage = 'https://images.unsplash.com/photo-1523246195122-01869a0a9532?w=1200&auto=format&fit=crop&q=60';
}
$introVideoUrl = 'https://www.youtube.com/embed/UIQUzWxV62o?si=BSuCjy_3AxcxR146'; // placeholder video; replace with your intro video URL
$additionalHead = (
    '<meta property="og:type" content="website">'
    . '<meta property="og:title" content="' . htmlspecialchars(generateMetaTitle($pageTitle)) . '">'
    . '<meta property="og:description" content="' . htmlspecialchars($metaDescription) . '">'
    . '<meta property="og:url" content="' . htmlspecialchars(SITE_URL) . '">'
    . '<meta property="og:image" content="' . htmlspecialchars($introImage) . '">'
    . '<meta name="twitter:card" content="summary_large_image">'
    . '<meta name="twitter:title" content="' . htmlspecialchars(generateMetaTitle($pageTitle)) . '">'
    . '<meta name="twitter:description" content="' . htmlspecialchars($metaDescription) . '">'
    . '<meta name="twitter:image" content="' . htmlspecialchars($introImage) . '">'
    . '<script type="application/ld+json">' . json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => SITE_NAME,
        'url' => SITE_URL,
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => SITE_URL . '/search?q={search_term_string}',
            'query-input' => 'required name=search_term_string'
        ],
        'description' => $metaDescription,
        'image' => $introImage
    ]) . '</script>'
);

include 'includes/header.php';
?>

    <!-- Hero Section -->
    <section class="relative overflow-hidden bg-gradient-to-br from-primary via-blue-700 to-blue-600 text-white py-14 md:py-24">
        <div class="absolute inset-0 opacity-20 pointer-events-none" aria-hidden="true">
            <div class="w-[48rem] h-[48rem] bg-white rounded-full blur-3xl -top-40 -left-40 absolute"></div>
            <div class="w-[42rem] h-[42rem] bg-cyan-300 rounded-full blur-3xl -bottom-40 -right-40 absolute"></div>
        </div>
        <div class="container mx-auto px-4 relative">
            <div class="text-center max-w-3xl mx-auto">
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-white/10 ring-1 ring-white/20">
                    <i class="fas fa-bullhorn"></i>
                    Daily Updates • Verified Sources
                </span>
                <h1 class="mt-4 text-3xl md:text-5xl font-extrabold leading-tight">
                    <span class="bg-clip-text text-transparent bg-gradient-to-r from-white via-sky-100 to-white">
                        Find Your Dream Sarkari Job
                    </span>
                </h1>
                <p class="text-base md:text-xl mt-3 md:mt-4 text-white/90">Latest Government Jobs, Results, Admit Cards &amp; Syllabus</p>
            </div>

            <div class="max-w-5xl mx-auto mt-8 md:mt-10">
                <!-- Glass search card -->
                <div class="rounded-2xl bg-white/20 supports-[backdrop-filter]:backdrop-blur-xl p-4 md:p-6 shadow-2xl ring-1 ring-white/30">
                    <div role="search" aria-label="Site search" class="grid grid-cols-1 md:grid-cols-[minmax(160px,220px)_1fr_auto] gap-2 sm:gap-3 md:gap-4 items-stretch">
                        <!-- Category Select (keep ID for JS) -->
                        <div>
                            <label for="search-category" class="sr-only">Category</label>
                            <div class="relative">
                                <i class="fas fa-layer-group absolute left-3 top-1/2 -translate-y-1/2 text-white/80 pointer-events-none"></i>
                                <select id="search-category" class="w-full appearance-none pl-10 pr-10 py-3.5 md:py-3 rounded-xl bg-white/20 text-white placeholder-white/70 ring-1 ring-white/30 focus:outline-none focus:ring-2 focus:ring-white/70 hover:bg-white/30 min-h-[48px]">
                                    <option value="all" class="text-gray-800">All Categories</option>
                                    <option value="jobs" class="text-gray-800">Jobs</option>
                                    <option value="results" class="text-gray-800">Results</option>
                                    <option value="admits" class="text-gray-800">Admit Cards</option>
                                    <option value="syllabus" class="text-gray-800">Syllabus</option>
                                    <?php if (!empty($categories)): ?>
                                        <optgroup label="Job Categories" class="text-gray-800">
                                            <?php foreach ($categories as $category): ?>
                                                <option value="category-<?= $category['slug'] ?>" class="text-gray-800">
                                                    <?= htmlspecialchars($category['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endif; ?>
                                </select>
                                <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-white/70 pointer-events-none"></i>
                            </div>
                        </div>

                        <!-- Search Input (keep ID for JS) -->
                        <div class="relative">
                            <label for="hero-search" class="sr-only">Search</label>
                            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-500"></i>
                            <input type="text" id="hero-search" placeholder="Search jobs, results, admit cards, syllabus..." 
                                   class="w-full pl-11 pr-4 py-3.5 md:py-3 rounded-xl text-gray-900 placeholder-gray-500 bg-white focus:outline-none focus:ring-2 focus:ring-primary ring-1 ring-gray-200 shadow-inner min-h-[48px]" aria-label="Search input">
                        </div>

                        <!-- Search Button (keep ID for JS) -->
                        <div class="flex">
                            <button id="hero-search-btn" class="w-full md:w-auto inline-flex items-center justify-center gap-2 bg-gradient-to-r from-secondary to-rose-600 hover:from-rose-600 hover:to-rose-700 px-6 md:px-8 py-3.5 md:py-3 rounded-xl font-semibold shadow-lg shadow-rose-900/20 transition-colors min-h-[48px]" aria-label="Search">
                                <i class="fas fa-magnifying-glass"></i>
                                Search
                            </button>
                        </div>
                    </div>

                    <!-- Quick chips -->
                    <div class="mt-4 flex items-center gap-2 text-sm overflow-x-auto whitespace-nowrap [-webkit-overflow-scrolling:touch] -mx-2 px-2 sm:mx-0 sm:px-0">
                        <span class="text-white/80">Popular:</span>
                        <?php $chips = ['SSC', 'UPSC', 'Railway', 'Bank PO', 'Police', 'Teacher', 'Defence']; foreach ($chips as $chip): ?>
                            <a class="px-3 py-1.5 rounded-full bg-white/20 text-white/90 hover:bg-white/30 transition shrink-0" href="<?= SITE_URL ?>/search.php?q=<?= urlencode($chip) ?>&type=jobs">#<?= htmlspecialchars($chip) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Community CTA: Telegram, WhatsApp, Bookmark -->
    <section class="py-6 bg-white">
        <div class="container mx-auto px-4">
            <div class="rounded-2xl p-6 md:p-8 bg-gradient-to-r from-blue-50 via-indigo-50 to-purple-50 border border-blue-100 shadow-sm">
                <div class="flex flex-col md:flex-row items-center gap-4 md:gap-6 justify-between">
                    <div class="text-center md:text-left">
                        <h2 class="text-2xl md:text-3xl font-extrabold text-gray-900">Stay Updated Daily</h2>
                        <p class="text-gray-700 mt-1">Join our channels and bookmark the homepage for instant job alerts and results.</p>
                    </div>
                    <div class="flex flex-wrap items-center justify-center gap-3">
                        <a href="<?= htmlspecialchars(TELEGRAM_URL) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-[#229ED9] text-white font-semibold hover:opacity-90 shadow">
                            <i class="fab fa-telegram-plane text-lg"></i>
                            <span>Join Telegram</span>
                        </a>
                        <a href="<?= htmlspecialchars(WHATSAPP_URL) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-[#25D366] text-white font-semibold hover:opacity-90 shadow">
                            <i class="fab fa-whatsapp text-lg"></i>
                            <span>Join WhatsApp</span>
                        </a>
                        <button id="bookmark-btn" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-primary text-white font-semibold hover:bg-blue-700 shadow">
                            <i class="fas fa-bookmark"></i>
                            <span>Bookmark This Page</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Latest At A Glance: Results, Admit Cards, Jobs (Top 10 each) -->
    <section class="py-10 bg-white">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Results -->
                <div class="bg-green-50 rounded-lg p-5">
                    <div class="flex items-center mb-3">
                        <i class="fas fa-trophy text-accent mr-2"></i>
                        <h2 class="text-xl font-bold text-gray-800">Latest Results</h2>
                    </div>
                    <ul class="divide-y divide-gray-200 bg-white rounded-lg overflow-hidden">
                        <?php foreach ($latestResults as $i => $result): ?>
                        <li class="p-3 hover:bg-gray-50">
                            <a href="<?= SITE_URL ?>/result/<?= urlencode($result['slug']) ?>" class="flex items-start gap-3">
                                <span class="text-xs text-gray-500 mt-1 w-16 shrink-0"><?= timeAgo($result['published_at']) ?></span>
                                <span class="text-gray-800 font-medium line-clamp-2"><?= htmlspecialchars($result['title']) ?></span>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="mt-3 text-right">
                        <a href="<?= SITE_URL ?>/results" class="text-accent font-semibold">View all <i class="fas fa-arrow-right ml-1"></i></a>
                    </div>
                </div>

                <!-- Admit Cards -->
                <div class="bg-yellow-50 rounded-lg p-5">
                    <div class="flex items-center mb-3">
                        <i class="fas fa-id-card text-yellow-600 mr-2"></i>
                        <h2 class="text-xl font-bold text-gray-800">Latest Admit Cards</h2>
                    </div>
                    <ul class="divide-y divide-gray-200 bg-white rounded-lg overflow-hidden">
                        <?php foreach ($latestAdmits as $admit): ?>
                        <li class="p-3 hover:bg-gray-50">
                            <a href="<?= SITE_URL ?>/admit/<?= urlencode($admit['slug']) ?>" class="flex items-start gap-3">
                                <span class="text-xs text-gray-500 mt-1 w-16 shrink-0"><?= timeAgo($admit['published_at']) ?></span>
                                <span class="text-gray-800 font-medium line-clamp-2"><?= htmlspecialchars($admit['title']) ?></span>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="mt-3 text-right">
                        <a href="<?= SITE_URL ?>/admit" class="text-yellow-600 font-semibold">View all <i class="fas fa-arrow-right ml-1"></i></a>
                    </div>
                </div>

                <!-- Jobs -->
                <div class="bg-blue-50 rounded-lg p-5">
                    <div class="flex items-center mb-3">
                        <i class="fas fa-briefcase text-primary mr-2"></i>
                        <h2 class="text-xl font-bold text-gray-800">Latest Jobs</h2>
                    </div>
                    <ul class="divide-y divide-gray-200 bg-white rounded-lg overflow-hidden">
                        <?php foreach ($latestJobs as $job): ?>
                        <li class="p-3 hover:bg-gray-50">
                            <a href="<?= SITE_URL ?>/job/<?= urlencode($job['slug']) ?>" class="flex items-start gap-3 justify-between">
                                <div class="flex items-start gap-3">
                                    <span class="text-xs text-gray-500 mt-1 w-16 shrink-0"><?= timeAgo($job['published_at']) ?></span>
                                    <span class="text-gray-800 font-medium line-clamp-2"><?= htmlspecialchars($job['title']) ?></span>
                                </div>
                                <?php if (!empty($job['last_date'])): ?>
                                <span class="badge badge-danger whitespace-nowrap">Last: <?= formatDate($job['last_date']) ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="mt-3 text-right">
                        <a href="<?= SITE_URL ?>/jobs" class="text-primary font-semibold">View all <i class="fas fa-arrow-right ml-1"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Mock Tests Promo -->
    <section class="py-12 bg-gradient-to-r from-emerald-50 via-green-50 to-teal-50">
        <div class="container mx-auto px-4">
            <div class="rounded-2xl border border-emerald-100 bg-white/70 backdrop-blur-sm p-6 md:p-10 shadow-sm">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-center">
                    <div class="lg:col-span-2">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                <i class="fas fa-clipboard-check"></i> Mock Tests
                            </span>
                        </div>
                        <h2 class="text-2xl md:text-3xl font-extrabold text-gray-900 mb-3">Practice Smarter with Real Exam-like Mock Tests</h2>
                        <p class="text-gray-700 leading-relaxed mb-5">Boost your exam preparation with high-quality mock tests designed for SSC, Banking, Railways, Defence, and State Exams. Detailed solutions, performance analytics, and real exam feel to help you crack faster.</p>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                            <div class="flex items-start gap-3 p-4 rounded-xl bg-emerald-50 border border-emerald-100">
                                <i class="fas fa-bullseye text-emerald-600 mt-1"></i>
                                <div>
                                    <div class="font-semibold text-gray-800">Exam-like Interface</div>
                                    <div class="text-sm text-gray-600">Real-time timer, section-wise navigation</div>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 p-4 rounded-xl bg-emerald-50 border border-emerald-100">
                                <i class="fas fa-lightbulb text-emerald-600 mt-1"></i>
                                <div>
                                    <div class="font-semibold text-gray-800">Detailed Solutions</div>
                                    <div class="text-sm text-gray-600">Step-by-step explanations</div>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 p-4 rounded-xl bg-emerald-50 border border-emerald-100">
                                <i class="fas fa-chart-line text-emerald-600 mt-1"></i>
                                <div>
                                    <div class="font-semibold text-gray-800">Performance Analytics</div>
                                    <div class="text-sm text-gray-600">Strengths, weaknesses, accuracy</div>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <a href="https://mock.examsz.in" target="_blank" rel="noopener" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-emerald-600 text-white font-semibold hover:bg-emerald-700 shadow">
                                <i class="fas fa-play"></i> Start Mock Tests
                            </a>
                            <a href="https://mock.examsz.in" target="_blank" rel="noopener" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-white text-emerald-700 font-semibold border border-emerald-200 hover:bg-emerald-50">
                                <i class="fas fa-list"></i> Browse Test Series
                            </a>
                        </div>
                    </div>
                    <div class="relative">
                        <div class="rounded-2xl bg-gradient-to-br from-emerald-100 to-green-100 p-6 border border-emerald-200">
                            <div class="flex items-center gap-3 mb-3">
                                <i class="fas fa-stopwatch text-emerald-600"></i>
                                <div class="font-semibold text-gray-800">Try a Quick Practice</div>
                            </div>
                            <ul class="space-y-2 text-sm text-gray-700">
                                <li class="flex items-center gap-2"><i class="fas fa-check-circle text-emerald-600"></i><span>15-min Mini Tests</span></li>
                                <li class="flex items-center gap-2"><i class="fas fa-check-circle text-emerald-600"></i><span>Topic-wise Drills</span></li>
                                <li class="flex items-center gap-2"><i class="fas fa-check-circle text-emerald-600"></i><span>All India Leaderboard</span></li>
                            </ul>
                            <a href="https://mock.examsz.in" target="_blank" rel="noopener" class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white text-emerald-700 font-semibold border border-emerald-200 hover:bg-emerald-50">
                                Go to Mock Portal <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Latest Articles -->
    <?php if (!empty($latestPosts)): ?>
    <section class="py-10 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-newspaper text-primary mr-2"></i> Latest Articles
                </h2>
                <a href="<?= SITE_URL ?>/posts" class="text-primary hover:text-blue-700 font-semibold">View All</a>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($latestPosts as $p): ?>
                <article class="bg-white rounded-lg shadow-sm p-5 card-hover">
                    <h3 class="text-lg font-semibold text-gray-800 line-clamp-2 mb-2">
                        <a class="hover:text-primary" href="<?= SITE_URL ?>/post/<?= urlencode($p['slug']) ?>">
                            <?= htmlspecialchars($p['title']) ?>
                        </a>
                    </h3>
                    <?php if (!empty($p['excerpt'])): ?>
                    <p class="text-gray-600 text-sm line-clamp-3 mb-3"><?= htmlspecialchars($p['excerpt']) ?></p>
                    <?php endif; ?>
                    <div class="text-xs text-gray-500 flex items-center justify-between">
                        <span><i class="fas fa-clock mr-1"></i><?= timeAgo($p['published_at'] ?? $p['updated_at'] ?? '') ?></span>
                        <?php if (!empty($p['category_name'])): ?>
                        <span class="badge badge-primary"><?= htmlspecialchars($p['category_name']) ?></span>
                        <?php endif; ?>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Syllabus and Insights -->
    <section class="py-12 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Syllabus List -->
                <div class="lg:col-span-2">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl md:text-3xl font-bold text-gray-800">Latest Syllabus</h2>
                        <a href="<?= SITE_URL ?>/syllabus" class="text-primary hover:text-blue-700 font-semibold">View All</a>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php if (!empty($latestSyllabus)): ?>
                            <?php foreach ($latestSyllabus as $sy): ?>
                            <div class="bg-white rounded-lg shadow-sm p-5 card-hover">
                                <h3 class="font-semibold text-gray-800 mb-2 line-clamp-2">
                                    <a class="hover:text-primary" href="<?= SITE_URL ?>/syllabus/<?= urlencode($sy['slug']) ?>"><?= htmlspecialchars($sy['title']) ?></a>
                                </h3>
                                <div class="text-sm text-gray-600 flex items-center gap-3">
                                    <span><i class="fas fa-building mr-1"></i><?= htmlspecialchars($sy['organization'] ?? '') ?></span>
                                    <span><i class="fas fa-calendar mr-1"></i><?= timeAgo($sy['published_at']) ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-span-full text-gray-600">No syllabus available.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Useful Insights / Features -->
                <aside>
                    <div class="bg-white rounded-lg shadow-sm p-5 mb-6">
                        <h3 class="text-xl font-bold text-gray-800 mb-3 flex items-center"><i class="fas fa-lightbulb text-yellow-500 mr-2"></i>Useful Insights</h3>
                        <ul class="space-y-3 text-sm text-gray-700">
                            <li class="flex items-start gap-2"><i class="fas fa-filter text-primary mt-1"></i>Use category filters in search to find Central/State, Banking, Railways and more.</li>
                            <li class="flex items-start gap-2"><i class="fas fa-bell text-accent mt-1"></i>Bookmark pages and check daily for admit cards and result updates.</li>
                            <li class="flex items-start gap-2"><i class="fas fa-clock text-red-600 mt-1"></i>Watch the “Last Date” badge to never miss an application deadline.</li>
                        </ul>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm p-5">
                        <h3 class="text-xl font-bold text-gray-800 mb-3 flex items-center"><i class="fas fa-chart-line text-green-600 mr-2"></i>Trending Searches</h3>
                        <div class="flex flex-wrap gap-2">
                            <?php $chips = ['SSC', 'UPSC', 'Railway', 'Bank PO', 'Police', 'Teacher', 'Defence']; foreach ($chips as $chip): ?>
                                <a class="px-3 py-1 rounded-full bg-blue-50 text-primary text-sm hover:bg-blue-100" href="<?= SITE_URL ?>/search.php?q=<?= urlencode($chip) ?>&type=jobs">#<?= htmlspecialchars($chip) ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </section>

    <!-- Information Section and Expiring Soon -->
    <section class="py-12 bg-white">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Information / Important Links -->
                <div class="lg:col-span-2">
                    <h2 class="text-2xl md:text-3xl font-bold text-gray-800 mb-6">Important Links</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                        <a href="<?= SITE_URL ?>/jobs/central" class="block bg-blue-50 p-4 rounded-lg hover:shadow">Central Govt Jobs</a>
                        <a href="<?= SITE_URL ?>/jobs/state" class="block bg-green-50 p-4 rounded-lg hover:shadow">State Govt Jobs</a>
                        <a href="<?= SITE_URL ?>/jobs/railway" class="block bg-yellow-50 p-4 rounded-lg hover:shadow">Railway Jobs</a>
                        <a href="<?= SITE_URL ?>/jobs/banking" class="block bg-purple-50 p-4 rounded-lg hover:shadow">Banking Jobs</a>
                        <a href="<?= SITE_URL ?>/jobs/ssc" class="block bg-pink-50 p-4 rounded-lg hover:shadow">SSC</a>
                        <a href="<?= SITE_URL ?>/jobs/upsc" class="block bg-indigo-50 p-4 rounded-lg hover:shadow">UPSC</a>
                        <a href="<?= SITE_URL ?>/jobs/police" class="block bg-red-50 p-4 rounded-lg hover:shadow">Police</a>
                        <a href="<?= SITE_URL ?>/jobs/teaching" class="block bg-teal-50 p-4 rounded-lg hover:shadow">Teaching</a>
                        <a href="<?= SITE_URL ?>/jobs/defence" class="block bg-gray-100 p-4 rounded-lg hover:shadow">Defence</a>
                    </div>
                </div>

                <!-- Expiring Soon -->
                <?php if (!empty($expiringJobs)): ?>
                <div>
                    <h2 class="text-2xl md:text-3xl font-bold text-gray-800 mb-6 flex items-center"><i class="fas fa-clock text-red-600 mr-2"></i>Expiring Soon</h2>
                    <div class="space-y-4">
                        <?php foreach ($expiringJobs as $job): ?>
                        <div class="bg-red-50 rounded-lg p-4 border border-red-100">
                            <a class="font-semibold text-gray-800 hover:text-primary" href="<?= SITE_URL ?>/job/<?= urlencode($job['slug']) ?>"><?= htmlspecialchars($job['title']) ?></a>
                            <div class="text-sm text-red-700 mt-1"><i class="fas fa-calendar mr-1"></i>Last Date: <?= formatDate($job['last_date']) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Counters (placed below Information as requested) -->
    <section class="py-10 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center p-4 bg-blue-50 rounded-lg">
                    <i class="fas fa-briefcase text-primary text-2xl mb-2"></i>
                    <div class="text-2xl font-bold text-gray-800"><?= $dbError ? '0' : $jobModel->getCount() ?></div>
                    <div class="text-sm text-gray-600">Active Jobs</div>
                </div>
                <div class="text-center p-4 bg-green-50 rounded-lg">
                    <i class="fas fa-trophy text-accent text-2xl mb-2"></i>
                    <div class="text-2xl font-bold text-gray-800"><?= $dbError ? '0' : $resultModel->getCount() ?></div>
                    <div class="text-sm text-gray-600">Results</div>
                </div>
                <div class="text-center p-4 bg-yellow-50 rounded-lg">
                    <i class="fas fa-id-card text-yellow-600 text-2xl mb-2"></i>
                    <div class="text-2xl font-bold text-gray-800"><?= $dbError ? '0' : $admitModel->getCount() ?></div>
                    <div class="text-sm text-gray-600">Admit Cards</div>
                </div>
                <div class="text-center p-4 bg-purple-50 rounded-lg">
                    <i class="fas fa-book text-purple-600 text-2xl mb-2"></i>
                    <div class="text-2xl font-bold text-gray-800"><?= $dbError ? '0' : (method_exists($syllabusModel, 'getCount') ? $syllabusModel->getCount() : '—') ?></div>
                    <div class="text-sm text-gray-600">Syllabus</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Intro section with description, image and video -->
    <section class="py-14 bg-gradient-to-b from-white to-gray-50">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
                <!-- Left: Copy -->
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-primary border border-blue-100 mb-3">
                        <i class="fas fa-star"></i>
                        About Examsz
                    </div>
                    <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 leading-tight mb-3">
                        Your trusted partner for Sarkari exams and careers
                    </h2>
                    <p class="text-gray-700 text-base md:text-lg leading-relaxed mb-5">
                        Examsz helps you prepare smarter and stay updated. Access exam-focused Mock Tests, the latest Government Job notifications, Admit Cards, Results, and structured Syllabus — all in one place.
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6">
                        <div class="flex items-start gap-3 p-4 rounded-xl bg-white border border-gray-100 shadow-sm">
                            <i class="fas fa-clipboard-check text-emerald-600 mt-1"></i>
                            <div>
                                <div class="font-semibold text-gray-800">Exam-like Mock Tests</div>
                                <div class="text-sm text-gray-600">Real interface, detailed solutions</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 p-4 rounded-xl bg-white border border-gray-100 shadow-sm">
                            <i class="fas fa-briefcase text-primary mt-1"></i>
                            <div>
                                <div class="font-semibold text-gray-800">Latest Job Alerts</div>
                                <div class="text-sm text-gray-600">Central, State, Railways, Banking</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 p-4 rounded-xl bg-white border border-gray-100 shadow-sm">
                            <i class="fas fa-id-card text-yellow-600 mt-1"></i>
                            <div>
                                <div class="font-semibold text-gray-800">Admit &amp; Result Updates</div>
                                <div class="text-sm text-gray-600">Quick links and official notices</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 p-4 rounded-xl bg-white border border-gray-100 shadow-sm">
                            <i class="fas fa-book text-purple-600 mt-1"></i>
                            <div>
                                <div class="font-semibold text-gray-800">Syllabus &amp; Guidance</div>
                                <div class="text-sm text-gray-600">Organized topics, smart strategy</div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <a href="https://mock.examsz.in" target="_blank" rel="noopener" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-primary text-white font-semibold hover:bg-blue-700 shadow">
                            <i class="fas fa-play"></i> Start Mock Tests
                        </a>
                        <a href="<?= SITE_URL ?>/jobs" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-white text-primary font-semibold border border-blue-200 hover:bg-blue-50">
                            <i class="fas fa-briefcase"></i> Explore Jobs
                        </a>
                        <a href="<?= htmlspecialchars(TELEGRAM_URL) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-[#229ED9] text-white font-semibold hover:opacity-90 shadow">
                            <i class="fab fa-telegram-plane"></i> Join Telegram
                        </a>
                    </div>

                    <p class="text-sm text-gray-600 mt-4">
                        Visit: <a class="text-primary font-semibold hover:underline" href="https://examsz.in" target="_blank" rel="noopener">https://examsz.in</a>
                    </p>
                </div>

                <!-- Right: Media -->
                <div class="space-y-4">
                    <div class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm bg-white">
                        <img src="<?= htmlspecialchars($introImage) ?>" alt="Examsz overview" class="w-full h-56 md:h-72 object-cover">
                    </div>
                    <div class="rounded-2xl overflow-hidden border border-gray-100 shadow-sm bg-white">
                        <div class="aspect-w-16 aspect-h-9">
                            <iframe class="w-full h-64 md:h-80" src="<?= htmlspecialchars($introVideoUrl) ?>" title="Examsz - Your Exam Success Partner" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php include 'includes/footer.php'; ?>

