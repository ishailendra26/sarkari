<?php
require_once '../src/config.php';
require_once '../src/db.php';
require_once '../src/helpers.php';
require_once '../src/models/Ad.php';

requireLogin();
requireAdmin();

$adModel = new Ad();

// Delete (admin-only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        $adModel->delete($id);
        redirect('ads.php?deleted=1');
    }
}

$pageTitle = 'Manage Ads';
$currentPage = 'ads';
include 'includes/header.php';

$ads = $adModel->getAll(200, 0);
?>
        <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success mb-6">
            <i class="fas fa-check-circle mr-2"></i>Ad deleted successfully
        </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-md p-6 mb-6 flex items-center justify-between">
            <h2 class="text-lg font-bold text-gray-800">Ads (<?= count($ads) ?> total)</h2>
            <a href="add-ad.php" class="btn btn-secondary"><i class="fas fa-plus mr-2"></i>Add Ad</a>
        </div>

        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table w-full">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Placement</th>
                            <th>Scope</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Schedule</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ads as $ad): ?>
                        <tr>
                            <td class="font-medium text-gray-800 line-clamp-2"><?= htmlspecialchars($ad['name']) ?></td>
                            <td><span class="badge badge-primary"><?= htmlspecialchars($ad['placement']) ?></span></td>
                            <td class="text-sm text-gray-600">
                                <?= htmlspecialchars($ad['page_scope']) ?>
                                <?php if (!empty($ad['slug_scope'])): ?>
                                    <span class="text-gray-400">/</span>
                                    <span class="text-gray-700"><?= htmlspecialchars($ad['slug_scope']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= ($ad['status'] ?? 'active') === 'active' ? 'badge-success' : 'badge-warning' ?>">
                                    <?= ucfirst($ad['status'] ?? 'active') ?>
                                </span>
                            </td>
                            <td><?= (int)$ad['priority'] ?></td>
                            <td class="text-xs text-gray-600">
                                <?php if (!empty($ad['start_at'])): ?>
                                    <div>From: <?= htmlspecialchars($ad['start_at']) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($ad['end_at'])): ?>
                                    <div>To: <?= htmlspecialchars($ad['end_at']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="flex gap-2">
                                    <a href="edit-ad.php?id=<?= $ad['id'] ?>" class="text-green-600 hover:text-green-800" title="Edit"><i class="fas fa-edit"></i></a>
                                    <button onclick="deleteAd(<?= $ad['id'] ?>, '<?= addslashes($ad['name']) ?>')" class="text-red-600 hover:text-red-800" title="Delete"><i class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Delete Modal -->
        <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Confirm Delete</h3>
                    <p class="text-gray-600 mb-6">Are you sure you want to delete "<span id="deleteAdTitle"></span>"?</p>
                    <form method="POST">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="deleteAdId">
                        <div class="flex gap-3">
                            <button type="submit" class="btn btn-danger flex-1"><i class="fas fa-trash mr-2"></i>Delete</button>
                            <button type="button" onclick="closeDeleteModal()" class="btn btn-secondary">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            function deleteAd(id, title) {
                document.getElementById('deleteAdId').value = id;
                document.getElementById('deleteAdTitle').textContent = title;
                document.getElementById('deleteModal').classList.remove('hidden');
            }
            function closeDeleteModal() {
                document.getElementById('deleteModal').classList.add('hidden');
            }
        </script>

<?php include 'includes/footer.php'; ?>
