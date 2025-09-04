<?php
require_once '../src/config.php';
require_once '../src/db.php';
require_once '../src/helpers.php';
require_once '../src/models/Result.php';

requireLogin();

$resultModel = new Result();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : null;

if ($search) {
    $results = $resultModel->search($search, JOBS_PER_PAGE, ($page - 1) * JOBS_PER_PAGE);
    $totalResults = count($resultModel->search($search, 1000, 0));
} else {
    $results = $resultModel->getAll(JOBS_PER_PAGE, ($page - 1) * JOBS_PER_PAGE);
    $totalResults = $resultModel->getCount();
}

$pagination = paginate($totalResults, $page, JOBS_PER_PAGE);

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        $resultModel->delete($id);
        redirect('results.php?deleted=1');
    }
}

$pageTitle = 'Manage Results';
include 'includes/header.php';
?>
        <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success mb-6">
            <i class="fas fa-check-circle mr-2"></i>Result deleted successfully
        </div>
        <?php endif; ?>

        <!-- Search -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <input type="text" id="search-input" value="<?= htmlspecialchars($search ?? '') ?>" 
                           placeholder="Search results..." class="form-input">
                </div>
                <div>
                    <button onclick="applySearch()" class="btn btn-primary">
                        <i class="fas fa-search mr-2"></i>Search
                    </button>
                </div>
            </div>
        </div>

        <!-- Results Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold text-gray-800">Results (<?= $totalResults ?> total)</h2>
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
                            <th>Result Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $result): ?>
                        <tr>
                            <td>
                                <div class="font-medium text-gray-800 line-clamp-2">
                                    <?= htmlspecialchars($result['title']) ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?= timeAgo($result['published_at']) ?>
                                </div>
                            </td>
                            <td class="text-gray-600"><?= htmlspecialchars($result['organization']) ?></td>
                            <td>
                                <?php if ($result['result_date']): ?>
                                <div class="text-sm text-gray-600">
                                    <?= formatDate($result['result_date']) ?>
                                </div>
                                <?php else: ?>
                                <span class="text-gray-400">Not set</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= $result['status'] === 'published' ? 'badge-success' : 'badge-warning' ?>">
                                    <?= ucfirst($result['status']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="flex gap-2">
                                    <a href="<?= SITE_URL ?>/result/<?= urlencode($result['slug']) ?>" target="_blank"
                                       class="text-blue-600 hover:text-blue-800" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit-result.php?id=<?= $result['id'] ?>"
                                       class="text-green-600 hover:text-green-800" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button onclick="deleteResult(<?= $result['id'] ?>, '<?= addslashes($result['title']) ?>')"
                                            class="text-red-600 hover:text-red-800" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
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
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Confirm Delete</h3>
                <p class="text-gray-600 mb-6">Are you sure you want to delete "<span id="deleteResultTitle"></span>"?</p>
                
                <form id="deleteForm" method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteResultId">
                    
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
        function applySearch() {
            const search = document.getElementById('search-input').value;
            let url = 'results.php';
            
            if (search) {
                url += '?search=' + encodeURIComponent(search);
            }
            
            window.location.href = url;
        }

        function deleteResult(id, title) {
            document.getElementById('deleteResultId').value = id;
            document.getElementById('deleteResultTitle').textContent = title;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        // Enter key search
        document.getElementById('search-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applySearch();
            }
        });
    </script>

<?php include 'includes/footer.php'; ?>
