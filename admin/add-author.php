<?php
require_once '../src/config.php';
require_once '../src/db.php';
require_once '../src/helpers.php';
require_once '../src/models/Author.php';

requireLogin();

$pageTitle = 'Add Author';
$authorModel = new Author();

$success = '';
$error = '';

$name = trim($_POST['name'] ?? '');
$slug = trim($_POST['slug'] ?? '');
$bio = trim($_POST['bio'] ?? '');
$avatar_url = trim($_POST['avatar_url'] ?? '');
$verified = isset($_POST['verified']) ? 1 : 0;
$social_input = trim($_POST['social'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($name);
    $slug = $slug ? slugify($slug) : slugify($name);

    if (!$name) {
        $error = 'Name is required';
    }

    // Validate slug uniqueness
    if (!$error) {
        $existing = $authorModel->getBySlug($slug);
        if ($existing) {
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
            'social' => $social
        ];
        $id = $authorModel->create($data);
        if ($id) {
            $success = 'Author created successfully';
            // reset form
            $name = $slug = $bio = $avatar_url = $social_input = '';
            $verified = 0;
        } else {
            $error = 'Failed to create author';
        }
    }
}

include 'includes/header.php';
?>
        <div class="max-w-3xl mx-auto">
            <div class="bg-white rounded-lg shadow-md p-6 md:p-8">
                <?php if ($success): ?>
                <div class="alert alert-success mb-6">
                    <i class="fas fa-check-circle mr-2"></i><?= $success ?>
                    <a href="authors.php" class="ml-3 text-green-700 underline">Back to Authors</a>
                </div>
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
                            <input type="text" name="slug" value="<?= htmlspecialchars($slug) ?>" class="form-input" placeholder="auto-generated from name if left blank">
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label">Bio</label>
                            <textarea name="bio" rows="4" class="form-input" placeholder="Short author bio..."><?= htmlspecialchars($bio) ?></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label">Avatar URL</label>
                            <input type="url" name="avatar_url" value="<?= htmlspecialchars($avatar_url) ?>" class="form-input" placeholder="https://... or use Upload tool in another tab and paste URL here">
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
                            <textarea name="social" rows="3" class="form-input" placeholder='{"twitter":"https://twitter.com/handle"}'><?= htmlspecialchars($social_input) ?></textarea>
                            <p class="text-xs text-gray-500 mt-1">Provide JSON with social links. Example: {"twitter": "https://twitter.com/handle", "facebook": "https://facebook.com/page"}</p>
                        </div>
                    </div>

                    <div class="flex gap-4 mt-8">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i>Save Author
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
