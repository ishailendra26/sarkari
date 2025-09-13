<?php
// Get current page for active navigation
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$pageTitle = $pageTitle ?? 'Admin Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Examsz Admin</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .line-clamp-1 {
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
    <!-- TinyMCE Rich Text Editor -->
    <script src="https://cdn.tiny.cloud/1/cdxz570jkij9j6pk03so50yve8zbiai22vxotiw8l1cir2q7/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.tinymce) {
                tinymce.init({
                    selector: 'textarea.richtext',
                    plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table help wordcount',
                    toolbar: 'undo redo | blocks | bold italic underline forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | table link image media | removeformat | preview code fullscreen',
                    menubar: 'file edit view insert format tools table help',
                    height: 420,
                    content_style: 'body { font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif; font-size:14px }',
                    branding: false,
                    convert_urls: false,
                });
            }
        });
    </script>
</head>
<body class="bg-gray-100">
    <!-- Admin Header -->
    <header class="bg-white shadow-md sticky top-0 z-40">
        <nav class="container mx-auto px-4 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <button type="button" onclick="toggleMobileMenu()" class="lg:hidden text-gray-600 hover:text-gray-800 mr-2" aria-label="Open menu">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <a href="index.php" class="flex items-center space-x-2 text-primary hover:text-blue-700">
                        <i class="fas fa-tachometer-alt text-xl"></i>
                        <span class="font-bold text-lg">Admin Panel</span>
                    </a>
                    <?php if ($currentPage !== 'index'): ?>
                    <span class="text-gray-400">|</span>
                    <span class="text-gray-600"><?= htmlspecialchars($pageTitle) ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="<?= SITE_URL ?>" target="_blank" class="text-primary hover:text-blue-700 flex items-center space-x-1">
                        <i class="fas fa-external-link-alt"></i>
                        <span class="hidden sm:inline">View Site</span>
                    </a>
                    <div class="flex items-center space-x-2 text-gray-600">
                        <i class="fas fa-user-circle"></i>
                        <span class="hidden sm:inline"><?= htmlspecialchars($_SESSION['admin_username']) ?></span>
                    </div>
                    <a href="logout.php" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                        <i class="fas fa-sign-out-alt mr-1"></i>
                        <span class="hidden sm:inline">Logout</span>
                    </a>
                </div>
            </div>
        </nav>
    </header>

    <div class="flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-lg min-h-screen sticky top-16 z-30 hidden lg:block">
            <nav class="p-4">
                <ul class="space-y-2">
                    <li>
                        <a href="index.php" class="flex items-center p-3 rounded-lg transition-colors <?= $currentPage === 'index' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                            <i class="fas fa-tachometer-alt mr-3"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="jobs.php" class="flex items-center p-3 rounded-lg transition-colors <?= $currentPage === 'jobs' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                            <i class="fas fa-briefcase mr-3"></i>
                            <span>Manage Jobs</span>
                        </a>
                    </li>
                    <li>
                        <a href="results.php" class="flex items-center p-3 rounded-lg transition-colors <?= $currentPage === 'results' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                            <i class="fas fa-trophy mr-3"></i>
                            <span>Manage Results</span>
                        </a>
                    </li>
                    <li>
                        <a href="admit-cards.php" class="flex items-center p-3 rounded-lg transition-colors <?= $currentPage === 'admit-cards' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                            <i class="fas fa-id-card mr-3"></i>
                            <span>Manage Admit Cards</span>
                        </a>
                    </li>
                    <li>
                        <a href="syllabi.php" class="flex items-center p-3 rounded-lg transition-colors <?= $currentPage === 'syllabi' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                            <i class="fas fa-book mr-3"></i>
                            <span>Manage Syllabus</span>
                        </a>
                    </li>
                    <li>
                        <a href="posts.php" class="flex items-center p-3 rounded-lg transition-colors <?= $currentPage === 'posts' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                            <i class="fas fa-newspaper mr-3"></i>
                            <span>Manage Posts</span>
                        </a>
                    </li>
                    <li>
                        <a href="categories.php" class="flex items-center p-3 rounded-lg transition-colors <?= $currentPage === 'categories' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                            <i class="fas fa-tags mr-3"></i>
                            <span>Categories</span>
                        </a>
                    </li>
                    <li>
                        <a href="authors.php" class="flex items-center p-3 rounded-lg transition-colors <?= $currentPage === 'authors' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                            <i class="fas fa-user-edit mr-3"></i>
                            <span>Authors</span>
                        </a>
                    </li>
                    <li>
                        <?php if (isAdmin()): ?>
                        <a href="ads.php" class="flex items-center p-3 rounded-lg transition-colors <?= $currentPage === 'ads' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                            <i class="fas fa-ad mr-3"></i>
                            <span>Ads</span>
                        </a>
                        <?php endif; ?>
                    </li>
                    <li>
                        <?php if (isAdmin()): ?>
                        <a href="settings.php" class="flex items-center p-3 rounded-lg transition-colors <?= $currentPage === 'settings' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                            <i class="fas fa-cog mr-3"></i>
                            <span>Settings</span>
                        </a>
                        <?php endif; ?>
                    </li>
                    <?php if (isAdmin()): ?>
                    <li>
                        <a href="users.php" class="flex items-center p-3 rounded-lg transition-colors <?= $currentPage === 'users' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                            <i class="fas fa-users mr-3"></i>
                            <span>Users</span>
                        </a>
                    </li>
                    <li>
                        <a href="add-user.php" class="flex items-center p-3 rounded-lg transition-colors <?= $currentPage === 'add-user' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                            <i class="fas fa-user-plus mr-3"></i>
                            <span>Add User</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <li>
                        <a href="upload-media.php" class="flex items-center p-3 rounded-lg transition-colors <?= $currentPage === 'upload-media' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                            <i class="fas fa-cloud-upload-alt mr-3"></i>
                            <span>Upload Media</span>
                        </a>
                    </li>
                </ul>
                
                <!-- Quick Actions -->
                <div class="mt-8 pt-4 border-t border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Quick Actions</h3>
                    <ul class="space-y-2">
                        <li>
                            <a href="add-job.php" class="flex items-center p-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                                <i class="fas fa-plus mr-2"></i>
                                <span>Add Job</span>
                            </a>
                        </li>
                        <li>
                            <a href="add-post.php" class="flex items-center p-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                                <i class="fas fa-plus mr-2"></i>
                                <span>Add Post</span>
                            </a>
                        </li>
                        <li>
                            <a href="add-result.php" class="flex items-center p-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                                <i class="fas fa-plus mr-2"></i>
                                <span>Add Result</span>
                            </a>
                        </li>
                        <li>
                            <a href="add-admit-card.php" class="flex items-center p-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                                <i class="fas fa-plus mr-2"></i>
                                <span>Add Admit Card</span>
                            </a>
                        </li>
                        <li>
                            <a href="add-syllabus.php" class="flex items-center p-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                                <i class="fas fa-plus mr-2"></i>
                                <span>Add Syllabus</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-6">
