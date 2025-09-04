<?php
require_once '../src/config.php';
require_once '../src/db.php';
require_once '../src/helpers.php';
require_once '../src/models/Author.php';

requireLogin();

$authorModel = new Author();
$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: authors.php');
    exit;
}

$author = $authorModel->getById($id);
if (!$author) {
    header('Location: authors.php');
    exit;
}

$pageTitle = 'Edit Author';

$success = '';
$error = '';

// Initialize with existing values
$name = $author['name'];
$slug = $author['slug'];
$bio = $author['bio'];
$avatar_url = $author['avatar_url'];
$verified = (int)$author['verified'];
$social_input = $author['social'] ? (is_array($author['social']) ? json_encode($author['social']) : $author['social']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput(trim($_POST['name'] ?? ''));
    $slug = trim($_POST['slug'] ?? '');
    $slug = $slug ? slugify($slug) : slugify($name);
    $bio = trim($_POST['bio'] ?? '');
    $avatar_url = trim($_POST['avatar_url'] ?? '');
    $verified = isset($_POST['verified']) ? 1 : 0;
    $social_input = trim($_POST['social'] ?? '');

    if (!$name) {
        $error = 'Name is required';
    }

    // Validate slug uniqueness (exclude current)
    if (!$error) {
        $existing = $authorModel->getBySlug($slug);
        if ($existing && (int)$existing['id'] !== $id) {
            $slug .= '-' . time();
        }
    }

    // Parse social JSON if provided
    $social = null;
    if (!$error && $social_input !== '') {
        try {
            $decoded = json_decode($social_input, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($decoded)) { throw new Exception('Invalid social JSON'); }
            $social = $decoded;
        } catch (Throwable $t) {
            $error = 'Invalid Social JSON: ' . htmlspecialchars($t->getMessage());
        }
    }

    if (!$error) {
        $data = [
            'name' => $name,
            'slug' => $slug,
            'bio' => $bio ?: null,
            'avatar_url' => $avatar_url ?: null,
            'verified' => $verified,
        ];
        if ($social_input !== '') { $data['social'] = $social; } else { $data['social'] = null; }

        if ($authorModel->update($id, $data)) {
            $success = 'Author updated successfully';
            // Refresh $author for display
            $author = $authorModel->getById($id);
        } else {
            $error = 'Failed to update author';
        }
    }
}

include 'includes/header.php';
?>
        <div class="max-w-3xl mx-auto">
            <div class="bg-white rounded-lg shadow-md p-6 md:p-8">
                <div class="flex items-center justify-between mb-4">
                    <h1 class="text-xl font-semibold text-gray-800">Edit Author</h1>
                    <a href="authors.php" class="btn btn-secondary"><i class="fas fa-arrow-left mr-2"></i>Back</a>
                </div>
                <?php if ($success): ?>
                <div class="alert alert-success mb-6"><i class="fas fa-check-circle mr-2"></i><?= $success ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                <div class="alert alert-error mb-6"><i class="fas fa-exclamation-circle mr-2"></i><?= $error ?></div>
                <?php endif; ?>

                <form method="POST" data-validate>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="form-label">Name *</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" class="form-input" required>
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" value="<?= htmlspecialchars($slug) ?>" class="form-input">
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label">Bio</label>
                            <textarea name="bio" rows="4" class="form-input"><?= htmlspecialchars($bio) ?></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label">Avatar URL</label>
                            <input type="url" name="avatar_url" value="<?= htmlspecialchars($avatar_url) ?>" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Verified</label>
                            <div class="flex items-center space-x-2 mt-2">
                                <input type="checkbox" name="verified" id="verified" <?= $verified ? 'checked' : '' ?> class="h-4 w-4">
                                <label for="verified" class="text-sm text-gray-700">Mark as verified</label>
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label">Social (JSON)</label>
                            <textarea name="social" rows="3" class="form-input"><?= htmlspecialchars($social_input) ?></textarea>
                            <p class="text-xs text-gray-500 mt-1">Provide JSON with social links. Example: {"twitter": "https://twitter.com/handle"}</p>
                        </div>
                    </div>

                    <div class="flex gap-4 mt-8">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i>Update Author
                        </button>
                        <a href="authors.php" class="btn btn-secondary">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>
