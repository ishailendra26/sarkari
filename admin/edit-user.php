<?php
require_once '../src/config.php';
require_once '../src/db.php';
require_once '../src/helpers.php';

requireLogin();
requireAdmin();

$db = getDB();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user = $db->fetchOne('SELECT id, username, email, role, created_at FROM admin_users WHERE id = ?', [$id]);
if (!$user) {
    redirect('users.php');
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $role = sanitizeInput($_POST['role'] ?? $user['role']);
    $password = $_POST['password'] ?? '';

    if (!in_array($role, ['admin', 'editor'], true)) {
        $role = $user['role'];
    }

    if (!$username || !$email) {
        $error = 'Username and email are required';
    } elseif (!isValidEmail($email)) {
        $error = 'Invalid email address';
    } else {
        // Check uniqueness for username/email (excluding current user)
        $dupe = $db->fetchOne('SELECT id FROM admin_users WHERE (username = ? OR email = ?) AND id != ?', [$username, $email, $id]);
        if ($dupe) {
            $error = 'Username or email already in use';
        } else {
            try {
                if ($password !== '') {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $db->query('UPDATE admin_users SET username = ?, email = ?, role = ?, password_hash = ? WHERE id = ?', [
                        $username, $email, $role, $hash, $id
                    ]);
                } else {
                    $db->query('UPDATE admin_users SET username = ?, email = ?, role = ? WHERE id = ?', [
                        $username, $email, $role, $id
                    ]);
                }
                $success = 'User updated successfully';
                $user = $db->fetchOne('SELECT id, username, email, role, created_at FROM admin_users WHERE id = ?', [$id]);
            } catch (Exception $e) {
                $error = 'Failed to update user';
            }
        }
    }
}

$pageTitle = 'Edit User';
$currentPage = 'users';
include 'includes/header.php';
?>
        <div class="max-w-xl mx-auto">
            <div class="bg-white rounded-lg shadow-md p-6 md:p-8">
                <h1 class="text-xl font-semibold text-gray-800 mb-4">Edit User</h1>

                <?php if ($success): ?>
                <div class="alert alert-success mb-6"><i class="fas fa-check-circle mr-2"></i><?= $success ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                <div class="alert alert-error mb-6"><i class="fas fa-exclamation-circle mr-2"></i><?= $error ?></div>
                <?php endif; ?>

                <form method="POST" data-validate>
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label class="form-label">Username *</label>
                            <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? $user['username']) ?>" class="form-input" required>
                        </div>
                        <div>
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? $user['email']) ?>" class="form-input" required>
                        </div>
                        <div>
                            <label class="form-label">Role *</label>
                            <?php $r = $_POST['role'] ?? $user['role']; ?>
                            <select name="role" class="form-input" required>
                                <option value="editor" <?= $r === 'editor' ? 'selected' : '' ?>>Editor</option>
                                <option value="admin" <?= $r === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">New Password (leave blank to keep unchanged)</label>
                            <input type="password" name="password" class="form-input" placeholder="••••••••">
                        </div>
                    </div>

                    <div class="flex gap-4 mt-8">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-2"></i>Save Changes</button>
                        <a href="users.php" class="btn btn-secondary"><i class="fas fa-arrow-left mr-2"></i>Back</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>
