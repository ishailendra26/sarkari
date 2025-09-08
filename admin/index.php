<?php
require_once '../src/config.php';
require_once '../src/db.php';
require_once '../src/helpers.php';
require_once '../src/models/Job.php';
require_once '../src/models/Result.php';
require_once '../src/models/AdmitCard.php';
require_once '../src/models/Post.php';
require_once '../src/models/Syllabus.php';

requireLogin();

$jobModel = new Job();
$resultModel = new Result();
$admitModel = new AdmitCard();
$postModel = new Post();
$syllabusModel = new Syllabus();

// Get dashboard stats
$totalJobs = $jobModel->getCount();
$totalResults = $resultModel->getCount();
$totalAdmits = $admitModel->getCount();
$totalPosts = $postModel->getCount();
$totalSyllabi = $syllabusModel->getCount();

$recentJobs = $jobModel->getLatest(5);
$recentResults = $resultModel->getLatest(5);

$pageTitle = 'Dashboard';
include 'includes/header.php';
?>
            <!-- Welcome Section -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white rounded-lg p-6 mb-8">
                <h1 class="text-3xl font-bold mb-2">Welcome to Admin Dashboard</h1>
                <p class="text-blue-100">Manage your SarkariJobs portal efficiently</p>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-2xl font-bold text-gray-800"><?= $totalJobs ?></h3>
                            <p class="text-gray-600">Total Jobs</p>
                        </div>
                        <i class="fas fa-briefcase text-3xl text-blue-600"></i>
                    </div>
                    <a href="jobs.php" class="block mt-4 text-blue-600 hover:text-blue-800 font-medium">View Jobs →</a>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-2xl font-bold text-gray-800"><?= $totalResults ?></h3>
                            <p class="text-gray-600">Total Results</p>
                        </div>
                        <i class="fas fa-trophy text-3xl text-green-600"></i>
                    </div>
                    <a href="results.php" class="block mt-4 text-green-600 hover:text-green-800 font-medium">View Results →</a>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-2xl font-bold text-gray-800"><?= $totalAdmits ?></h3>
                            <p class="text-gray-600">Admit Cards</p>
                        </div>
                        <i class="fas fa-id-card text-3xl text-orange-600"></i>
                    </div>
                    <a href="admit-cards.php" class="block mt-4 text-orange-600 hover:text-orange-800 font-medium">View Admit Cards →</a>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-2xl font-bold text-gray-800"><?= $totalSyllabi ?></h3>
                            <p class="text-gray-600">Syllabi</p>
                        </div>
                        <i class="fas fa-file-alt text-3xl text-purple-600"></i>
                    </div>
                    <a href="syllabi.php" class="block mt-4 text-purple-600 hover:text-purple-800 font-medium">View Syllabi →</a>
                </div>
            </div>

            <!-- Additional Stats Row -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-2xl font-bold text-gray-800"><?= $totalPosts ?></h3>
                            <p class="text-gray-600">Total Posts</p>
                        </div>
                        <i class="fas fa-newspaper text-3xl text-indigo-600"></i>
                    </div>
                    <a href="posts.php" class="block mt-4 text-indigo-600 hover:text-indigo-800 font-medium">View Posts →</a>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Quick Actions</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <a href="add-job.php" class="bg-primary text-white p-4 rounded-lg text-center hover:bg-blue-700 transition-colors">
                        <i class="fas fa-plus-circle text-2xl mb-2"></i>
                        <div class="font-semibold">Add New Job</div>
                    </a>
                    <a href="add-result.php" class="bg-accent text-white p-4 rounded-lg text-center hover:bg-green-700 transition-colors">
                        <i class="fas fa-plus-circle text-2xl mb-2"></i>
                        <div class="font-semibold">Add Result</div>
                    </a>
                    <a href="add-admit-card.php" class="bg-yellow-600 text-white p-4 rounded-lg text-center hover:bg-yellow-700 transition-colors">
                        <i class="fas fa-plus-circle text-2xl mb-2"></i>
                        <div class="font-semibold">Add Admit Card</div>
                    </a>
                    <a href="add-syllabus.php" class="bg-purple-600 text-white p-4 rounded-lg text-center hover:bg-purple-700 transition-colors">
                        <i class="fas fa-plus-circle text-2xl mb-2"></i>
                        <div class="font-semibold">Add Syllabus</div>
                    </a>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Recent Jobs -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-800">Recent Jobs</h3>
                        <a href="jobs.php" class="text-primary hover:text-blue-700">View All</a>
                    </div>
                    <div class="space-y-3">
                        <?php foreach ($recentJobs as $job): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex-1">
                                <h4 class="font-medium text-gray-800 line-clamp-1"><?= htmlspecialchars($job['title']) ?></h4>
                                <p class="text-sm text-gray-600"><?= htmlspecialchars($job['organization']) ?></p>
                            </div>
                            <div class="flex gap-2">
                                <a href="edit-job.php?id=<?= $job['id'] ?>" class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="<?= SITE_URL ?>/job/<?= urlencode($job['slug']) ?>" target="_blank" class="text-green-600 hover:text-green-800">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Recent Results -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-800">Recent Results</h3>
                        <a href="results.php" class="text-primary hover:text-blue-700">View All</a>
                    </div>
                    <div class="space-y-3">
                        <?php foreach ($recentResults as $result): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex-1">
                                <h4 class="font-medium text-gray-800 line-clamp-1"><?= htmlspecialchars($result['title']) ?></h4>
                                <p class="text-sm text-gray-600"><?= timeAgo($result['published_at']) ?></p>
                            </div>
                            <div class="flex gap-2">
                                <a href="edit-result.php?id=<?= $result['id'] ?>" class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="<?= SITE_URL ?>/result/<?= urlencode($result['slug']) ?>" target="_blank" class="text-green-600 hover:text-green-800">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

<?php include 'includes/footer.php'; ?>
