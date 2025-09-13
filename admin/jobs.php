<?php
require_once '../src/config.php';
require_once '../src/db.php';
require_once '../src/helpers.php';
require_once '../src/models/Job.php';
require_once '../src/models/Category.php';

requireLogin();

$jobModel = new Job();
$categoryModel = new Category();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : null;
$category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : null;

if ($search) {
    $jobs = $jobModel->search($search, JOBS_PER_PAGE, ($page - 1) * JOBS_PER_PAGE);
    $totalJobs = count($jobModel->search($search, 1000, 0));
} else {
    $jobs = $jobModel->getAll(JOBS_PER_PAGE, ($page - 1) * JOBS_PER_PAGE, $category);
    $totalJobs = $jobModel->getCount($category);
}

$pagination = paginate($totalJobs, $page, JOBS_PER_PAGE);
$categories = $categoryModel->getAll();

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!canDeleteContent()) {
        redirect('jobs.php?forbidden=1');
    }
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        $jobModel->delete($id);
        redirect('jobs.php?deleted=1');
    }
}

$pageTitle = 'Manage Jobs';
include 'includes/header.php';
?>
        <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success mb-6">
            <i class="fas fa-check-circle mr-2"></i>Job deleted successfully
        </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <input type="text" id="search-input" value="<?= htmlspecialchars($search ?? '') ?>" 
                           placeholder="Search jobs..." class="form-input">
                </div>
                <div>
                    <select id="category-filter" class="form-input">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['slug'] ?>" <?= $category === $cat['slug'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <button onclick="applyFilters()" class="btn btn-primary">
                        <i class="fas fa-search mr-2"></i>Search
                    </button>
                </div>
            </div>
        </div>

        <!-- Jobs Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold text-gray-800">Jobs (<?= $totalJobs ?> total)</h2>
                    <div class="text-sm text-gray-600">
                        Page <?= $pagination['current_page'] ?> of <?= $pagination['total_pages'] ?>
                    </div>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="table w-full">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Organization</th>
                            <th>Category</th>
                            <th>Last Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($jobs as $job): ?>
                        <tr>
                            <td>
                                <div class="font-medium text-gray-800 line-clamp-2">
                                    <?= htmlspecialchars($job['title']) ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?= timeAgo($job['published_at']) ?>
                                </div>
                            </td>
                            <td class="text-gray-600"><?= htmlspecialchars($job['organization']) ?></td>
                            <td>
                                <?php if ($job['category_name']): ?>
                                <span class="badge badge-primary"><?= htmlspecialchars($job['category_name']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($job['last_date']): ?>
                                <?php 
                                $isExpired = strtotime($job['last_date']) < time();
                                $daysLeft = floor((strtotime($job['last_date']) - time()) / 86400);
                                ?>
                                <div class="text-sm <?= $isExpired ? 'text-red-600' : ($daysLeft <= 7 ? 'text-yellow-600' : 'text-gray-600') ?>">
                                    <?= formatDate($job['last_date']) ?>
                                    <?php if (!$isExpired): ?>
                                    <br><span class="text-xs"><?= $daysLeft ?> days left</span>
                                    <?php else: ?>
                                    <br><span class="text-xs">Expired</span>
                                    <?php endif; ?>
                                </div>
                                <?php else: ?>
                                <span class="text-gray-400">Not set</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= $job['status'] === 'published' ? 'badge-success' : 'badge-warning' ?>">
                                    <?= ucfirst($job['status']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="flex gap-2">
                                    <a href="<?= SITE_URL ?>/job/<?= urlencode($job['slug']) ?>" target="_blank"
                                       class="text-blue-600 hover:text-blue-800" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit-job.php?id=<?= $job['id'] ?>"
                                       class="text-green-600 hover:text-green-800" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if (canDeleteContent()): ?>
                                    <button onclick="deleteJob(<?= $job['id'] ?>, '<?= addslashes($job['title']) ?>')"
                                            class="text-red-600 hover:text-red-800" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
            <div class="p-6 border-t border-gray-200">
                <div class="pagination">
                    <?php if ($pagination['has_prev']): ?>
                    <a href="jobs.php?page=<?= $pagination['prev_page'] ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $category ? '&category=' . $category : '' ?>">
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
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Confirm Delete</h3>
                <p class="text-gray-600 mb-6">Are you sure you want to delete "<span id="deleteJobTitle"></span>"?</p>
                
                <form id="deleteForm" method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteJobId">
                    
                    <div class="flex gap-3">
                        <button type="submit" class="btn btn-danger flex-1">
                            <i class="fas fa-trash mr-2"></i>Delete
                        </button>
                        <button type="button" onclick="closeDeleteModal()" class="btn btn-secondary">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function applyFilters() {
            const search = document.getElementById('search-input').value;
            const category = document.getElementById('category-filter').value;
            
            let url = 'jobs.php';
            const params = [];
            
            if (search) params.push('search=' + encodeURIComponent(search));
            if (category) params.push('category=' + encodeURIComponent(category));
            
            if (params.length > 0) {
                url += '?' + params.join('&');
            }
            
            window.location.href = url;
        }

        function deleteJob(id, title) {
            document.getElementById('deleteJobId').value = id;
            document.getElementById('deleteJobTitle').textContent = title;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        // Enter key search
        document.getElementById('search-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyFilters();
            }
        });
    </script>

<?php include 'includes/footer.php'; ?>
