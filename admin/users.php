<?php
require_once '../src/config.php';
require_once '../src/db.php';
require_once '../src/helpers.php';

requireLogin();
requireAdmin();

$db = getDB();
$success = '';
$error = '';

// Handle delete user (prevent deleting yourself)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    startSession();
    $currentId = (int)($_SESSION['admin_id'] ?? 0);
    if ($id && $id !== $currentId) {
        try {
            $db->query('DELETE FROM admin_users WHERE id = ?', [$id]);
            $success = 'User deleted';
        } catch (Exception $e) {
            $error = 'Failed to delete user';
        }
    } else {
        $error = 'Cannot delete the currently logged-in admin';
    }
}

$users = $db->fetchAll('SELECT id, username, email, role, created_at FROM admin_users ORDER BY id DESC');

$pageTitle = 'Users';
$currentPage = 'users';
include 'includes/header.php';
?>
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-xl font-semibold text-gray-800">Users</h1>
                <a href="add-user.php" class="btn btn-primary"><i class="fas fa-user-plus mr-2"></i>Add User</a>
            </div>

            <?php if ($success): ?>
            <div class="alert alert-success mb-4"><i class="fas fa-check-circle mr-2"></i><?= $success ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="alert alert-error mb-4"><i class="fas fa-exclamation-circle mr-2"></i><?= $error ?></div>
            <?php endif; ?>

            <div class="overflow-x-auto">
                <table class="table w-full">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= (int)$u['id'] ?></td>
                            <td class="font-medium"><?= htmlspecialchars($u['username']) ?></td>
                            <td class="text-sm text-gray-600"><?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                <span class="badge <?= $u['role'] === 'admin' ? 'badge-primary' : 'badge-secondary' ?>"><?= htmlspecialchars(ucfirst($u['role'])) ?></span>
                            </td>
                            <td class="text-sm text-gray-600"><?= htmlspecialchars($u['created_at']) ?></td>
                            <td>
                                <div class="flex gap-2">
                                    <a href="edit-user.php?id=<?= (int)$u['id'] ?>" class="text-green-600 hover:text-green-800" title="Edit"><i class="fas fa-edit"></i></a>
                                    <form method="POST" class="inline" onsubmit="return confirm('Delete this user?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-800" title="Delete"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>
