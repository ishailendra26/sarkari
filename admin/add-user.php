<?php
require_once '../src/config.php';
require_once '../src/db.php';
require_once '../src/helpers.php';

requireLogin();
requireAdmin();

$db = getDB();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = sanitizeInput($_POST['role'] ?? 'editor');

    if (!in_array($role, ['admin', 'editor'], true)) {
        $role = 'editor';
    }

    if (!$username || !$email || !$password) {
        $error = 'Please fill all required fields';
    } elseif (!isValidEmail($email)) {
        $error = 'Invalid email address';
    } else {
        // Check duplicates
        $existingUser = $db->fetchOne('SELECT id FROM admin_users WHERE username = ? OR email = ?', [$username, $email]);
        if ($existingUser) {
            $error = 'Username or email already exists';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            try {
                $db->query('INSERT INTO admin_users (username, password_hash, email, role) VALUES (?, ?, ?, ?)', [
                    $username,
                    $hash,
                    $email,
                    $role,
                ]);
                $success = 'User created successfully!';
            } catch (Exception $e) {
                $error = 'Failed to create user';
            }
        }
    }
}

$pageTitle = 'Add User';
$currentPage = 'add-user';
include 'includes/header.php';
?>
        <div class="max-w-xl mx-auto">
            <div class="bg-white rounded-lg shadow-md p-6 md:p-8">
                <h1 class="text-xl font-semibold text-gray-800 mb-4">Add New User</h1>

                <?php if ($success): ?>
                <div class="alert alert-success mb-6">
                    <i class="fas fa-check-circle mr-2"></i><?= $success ?>
                </div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="alert alert-error mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i><?= $error ?>
                </div>
                <?php endif; ?>

                <form method="POST" data-validate>
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label class="form-label">Username *</label>
                            <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" class="form-input" required>
                        </div>
                        <div>
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" class="form-input" required>
                        </div>
                        <div>
                            <label class="form-label">Password *</label>
                            <input type="password" name="password" class="form-input" required>
                        </div>
                        <div>
                            <label class="form-label">Role *</label>
                            <?php $r = $_POST['role'] ?? 'editor'; ?>
                            <select name="role" class="form-input" required>
                                <option value="editor" <?= $r === 'editor' ? 'selected' : '' ?>>Editor (can add/edit content only)</option>
                                <option value="admin" <?= $r === 'admin' ? 'selected' : '' ?>>Admin (full access)</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex gap-4 mt-8">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus mr-2"></i>Create User
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-2"></i>Back
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>
