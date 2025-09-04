<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? generateMetaTitle($pageTitle) : generateMetaTitle('Latest Government Jobs, Results & Admit Cards') ?></title>
    <meta name="description" content="<?= isset($metaDescription) ? $metaDescription : 'Find latest Sarkari jobs, exam results, admit cards and syllabus. Stay updated with government job notifications and exam updates.' ?>">
    <?php if (isset($metaKeywords)): ?>
    <meta name="keywords" content="<?= $metaKeywords ?>">
    <?php endif; ?>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= SITE_URL ?>/assets/images/Examszfevicon.png">
    <link rel="apple-touch-icon" href="<?= SITE_URL ?>/assets/images/Examszfevicon.png">
    
    <!-- Tailwind CSS with Typography plugin for .prose support -->
    <script src="https://cdn.tailwindcss.com?plugins=typography"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1e40af',
                        secondary: '#dc2626',
                        accent: '#059669'
                    }
                }
            }
        }
    </script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    
    <?php if (isset($additionalHead)): ?>
    <?= $additionalHead ?>
    <?php endif; ?>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-md sticky top-0 z-50">
        <nav class="container mx-auto px-4 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <a href="<?= SITE_URL ?>" aria-label="SarkariJobs Home" class="flex items-center group">
                        <img src="<?= SITE_URL ?>/assets/images/ExamszBlack.svg" alt="SarkariJobs" class="h-9 md:h-10 w-auto drop-shadow-sm transition-transform duration-200 group-hover:scale-[1.02]">
                        <!-- Mobile-only concise tagline -->
                        <div class="ml-2 leading-tight sm:hidden">
                            <div class="text-[10px] text-gray-600">
                                Jobs Notifications | <b>Mock Tests</b>
                            </div>
                        </div>
                        <!-- Desktop/tablet tagline -->
                        <div class="ml-3 leading-tight hidden sm:block">
                            <div class="text-[11px] md:text-sm text-gray-600">
                                <span class="font-medium text-gray-800">Sarkari Jobs Notifications</span>
                                <span class="mx-1 text-gray-300">|</span>
                                <span class="font-medium text-blue-800">Mock Tests</span>
                            </div>
                        </div>
                    </a>
                </div>
                
                <!-- Mobile menu button -->
                <button id="mobile-menu-btn" class="md:hidden text-gray-600 hover:text-primary" aria-controls="mobile-menu" aria-expanded="false" aria-label="Toggle navigation">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                
                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center space-x-6">
                    <a href="<?= SITE_URL ?>" class="text-gray-700 hover:text-primary transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'text-primary font-semibold' : '' ?>">Home</a>
                    <a href="<?= SITE_URL ?>/jobs" class="text-gray-700 hover:text-primary transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'jobs.php') ? 'text-primary font-semibold' : '' ?>">Jobs</a>
                    <a href="<?= SITE_URL ?>/results" class="text-gray-700 hover:text-primary transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'results.php') ? 'text-primary font-semibold' : '' ?>">Results</a>
                    <a href="<?= SITE_URL ?>/admit" class="text-gray-700 hover:text-primary transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'admit.php') ? 'text-primary font-semibold' : '' ?>">Admit Cards</a>
                    <a href="<?= SITE_URL ?>/syllabus" class="text-gray-700 hover:text-primary transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'syllabus.php') ? 'text-primary font-semibold' : '' ?>">Syllabus</a>
                    <a href="<?= SITE_URL ?>/posts" class="text-gray-700 hover:text-primary transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'posts.php') ? 'text-primary font-semibold' : '' ?>">Articles</a>
                    <a href="https://mock.examsz.in" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-accent hover:text-green-700 font-semibold"><i class="fas fa-clipboard-check"></i> Mock Tests</a>

                </div>
            </div>
            
            <!-- Mobile Navigation -->
            <div id="mobile-menu" class="hidden md:hidden mt-4 pb-4 border-t border-gray-200">
                <div class="flex flex-col space-y-3 pt-4">
                    <a href="<?= SITE_URL ?>" class="text-gray-700 hover:text-primary transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'text-primary font-semibold' : '' ?>">Home</a>
                    <a href="<?= SITE_URL ?>/jobs" class="text-gray-700 hover:text-primary transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'jobs.php') ? 'text-primary font-semibold' : '' ?>">Jobs</a>
                    <a href="<?= SITE_URL ?>/results" class="text-gray-700 hover:text-primary transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'results.php') ? 'text-primary font-semibold' : '' ?>">Results</a>
                    <a href="<?= SITE_URL ?>/admit" class="text-gray-700 hover:text-primary transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'admit.php') ? 'text-primary font-semibold' : '' ?>">Admit Cards</a>
                    <a href="<?= SITE_URL ?>/syllabus" class="text-gray-700 hover:text-primary transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'syllabus.php') ? 'text-primary font-semibold' : '' ?>">Syllabus</a>
                    <a href="<?= SITE_URL ?>/posts" class="text-gray-700 hover:text-primary transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'posts.php') ? 'text-primary font-semibold' : '' ?>">Articles</a>
                    <a href="https://mock.examsz.in" target="_blank" rel="noopener" class="text-accent hover:text-green-700 font-semibold">Mock Tests</a>

                </div>
            </div>
        </nav>
    </header>
    <?php 
        if (function_exists('inferPageScopeFromRequest')) {
            [$scope, $slug] = inferPageScopeFromRequest();
            echo renderAd('header_banner', $scope, $slug, 1);
        }
    ?>
