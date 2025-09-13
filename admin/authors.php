<?php
require_once '../src/config.php';
require_once '../src/db.php';
require_once '../src/helpers.php';
require_once '../src/models/Author.php';

requireLogin();

$pageTitle = 'Authors';
$authorModel = new Author();
$db = getDB();

$success = '';
$error = '';

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!canDeleteContent()) {
        $error = 'Access denied';
    } else {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            try {
                // ON DELETE SET NULL is enforced by FK, so safe to delete
                $db->query('DELETE FROM authors WHERE id = ?', [$id]);
                $success = 'Author deleted successfully';
            } catch (Exception $e) {
                $error = 'Failed to delete author';
            }
        }
    }
}

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$total = (int)$db->fetchOne('SELECT COUNT(*) as c FROM authors')['c'];
$authors = $authorModel->getAll($limit, $offset);
$totalPages = max(1, (int)ceil($total / $limit));

include 'includes/header.php';
?>
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-xl font-semibold text-gray-800">Authors</h1>
                <a href="add-author.php" class="btn btn-primary"><i class="fas fa-plus mr-2"></i>Add Author</a>
            </div>

            <?php if ($success): ?>
            <div class="alert alert-success mb-4"><i class="fas fa-check-circle mr-2"></i><?= $success ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="alert alert-error mb-4"><i class="fas fa-exclamation-circle mr-2"></i><?= $error ?></div>
            <?php endif; ?>

            <div class="bg-white rounded-lg shadow">
                <div class="overflow-x-auto">
                <table class="min-w-full table-auto text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">Slug</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Verified</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Created</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (!$authors): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-6 text-center text-gray-500">No authors found. <a href="add-author.php" class="text-primary underline">Add your first author</a>.</td>
                        </tr>
                        <?php endif; ?>
                        <?php foreach ($authors as $a): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <?php if (!empty($a['avatar_url'])): ?>
                                    <img src="<?= htmlspecialchars($a['avatar_url']) ?>" alt="" class="h-8 w-8 rounded-full mr-3 object-cover">
                                    <?php endif; ?>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 line-clamp-1"><?= htmlspecialchars($a['name']) ?></div>
                                        <?php if (!empty($a['bio'])): ?>
                                        <div class="text-xs text-gray-500 line-clamp-1"><?= htmlspecialchars($a['bio']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-500 hidden sm:table-cell"><?= htmlspecialchars($a['slug']) ?></td>
                            <td class="px-6 py-4">
                                <?php if (!empty($a['verified'])): ?>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800"><i class="fas fa-check mr-1"></i>Verified</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">Unverified</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-gray-500 hidden md:table-cell"><?= htmlspecialchars($a['created_at']) ?></td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end items-center gap-4 flex-wrap">
                                    <a href="edit-author.php?id=<?= $a['id'] ?>" class="text-primary hover:text-blue-700 inline-flex items-center"><i class="fas fa-edit mr-1"></i>Edit</a>
                                    <?php if (canDeleteContent()): ?>
                                    <form method="POST" class="inline" onsubmit="return confirmDelete('Delete this author? Content will remain but without an author.');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-700 inline-flex items-center"><i class="fas fa-trash mr-1"></i>Delete</button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>

            <?php if ($totalPages > 1): ?>
            <div class="flex justify-between items-center mt-4">
                <div class="text-sm text-gray-600">Page <?= $page ?> of <?= $totalPages ?></div>
                <div class="space-x-2">
                    <?php if ($page > 1): ?>
                        <a class="btn btn-secondary" href="?page=<?= $page - 1 ?>"><i class="fas fa-angle-left mr-1"></i>Prev</a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a class="btn btn-secondary" href="?page=<?= $page + 1 ?>">Next<i class="fas fa-angle-right ml-1"></i></a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>
