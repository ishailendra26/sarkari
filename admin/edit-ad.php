<?php
require_once '../src/config.php';
require_once '../src/db.php';
require_once '../src/helpers.php';
require_once '../src/models/Ad.php';

requireLogin();
requireAdmin();

$adModel = new Ad();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$ad = $id ? $adModel->getById($id) : null;
if (!$ad) {
    redirect('ads.php');
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $placement = sanitizeInput($_POST['placement'] ?? '');
    $page_scope = sanitizeInput($_POST['page_scope'] ?? 'all');
    $slug_scope = sanitizeInput($_POST['slug_scope'] ?? '');
    $code = $_POST['code'] ?? '';
    $status = sanitizeInput($_POST['status'] ?? 'active');
    $priority = (int)($_POST['priority'] ?? 0);
    $start_at = $_POST['start_at'] !== '' ? $_POST['start_at'] : null;
    $end_at = $_POST['end_at'] !== '' ? $_POST['end_at'] : null;

    if ($name && $placement && $code) {
        $ok = $adModel->update($id, [
            'name' => $name,
            'placement' => $placement,
            'page_scope' => $page_scope ?: 'all',
            'slug_scope' => $slug_scope ?: null,
            'code' => $code,
            'status' => $status ?: 'active',
            'priority' => $priority,
            'start_at' => $start_at,
            'end_at' => $end_at,
        ]);
        if ($ok !== false) {
            $success = 'Ad updated successfully!';
            $ad = $adModel->getById($id);
        } else {
            $error = 'Failed to update ad';
        }
    } else {
        $error = 'Please fill required fields';
    }
}

$pageTitle = 'Edit Ad';
$currentPage = 'ads';
include 'includes/header.php';
?>
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-lg shadow-md p-6 md:p-8">
                <?php if ($success): ?>
                <div class="alert alert-success mb-6">
                    <i class="fas fa-check-circle mr-2"></i><?= $success ?>
                    <a href="ads.php" class="ml-4 text-green-700 underline">Back to ads</a>
                </div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="alert alert-error mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i><?= $error ?>
                </div>
                <?php endif; ?>

                <form method="POST" data-validate>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="form-label">Name *</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? $ad['name']) ?>" class="form-input" required>
                        </div>

                        <div>
                            <label class="form-label">Placement *</label>
                            <?php $p = $_POST['placement'] ?? $ad['placement']; ?>
                            <select name="placement" class="form-input" required>
                                <option value="header_banner" <?= $p==='header_banner'?'selected':'' ?>>Header Banner</option>
                                <option value="footer_banner" <?= $p==='footer_banner'?'selected':'' ?>>Footer Banner</option>
                                <option value="sidebar_top" <?= $p==='sidebar_top'?'selected':'' ?>>Sidebar Top</option>
                                <option value="sidebar_bottom" <?= $p==='sidebar_bottom'?'selected':'' ?>>Sidebar Bottom</option>
                                <option value="in_content" <?= $p==='in_content'?'selected':'' ?>>In-Content</option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Page Scope</label>
                            <?php $s = $_POST['page_scope'] ?? $ad['page_scope']; ?>
                            <select name="page_scope" class="form-input">
                                <?php $scopes = ['all','home','jobs','results','admit','syllabus','posts','job_detail','result_detail','admit_detail','syllabus_detail','post_detail','category']; ?>
                                <?php foreach ($scopes as $scope): ?>
                                    <option value="<?= $scope ?>" <?= $s===$scope?'selected':'' ?>><?= ucfirst(str_replace('_',' ', $scope)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Slug Scope (optional)</label>
                            <input type="text" name="slug_scope" value="<?= htmlspecialchars($_POST['slug_scope'] ?? ($ad['slug_scope'] ?? '')) ?>" class="form-input" placeholder="For detail/category pages, e.g., job-slug or category-slug">
                        </div>

                        <div>
                            <label class="form-label">Status</label>
                            <?php $st = $_POST['status'] ?? $ad['status']; ?>
                            <select name="status" class="form-input">
                                <option value="active" <?= $st==='active'?'selected':'' ?>>Active</option>
                                <option value="inactive" <?= $st==='inactive'?'selected':'' ?>>Inactive</option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Priority</label>
                            <input type="number" name="priority" value="<?= htmlspecialchars($_POST['priority'] ?? (string)$ad['priority']) ?>" class="form-input" placeholder="Higher value shows first">
                        </div>

                        <div>
                            <label class="form-label">Start At</label>
                            <input type="datetime-local" name="start_at" value="<?= htmlspecialchars($_POST['start_at'] ?? ($ad['start_at'] ? date('Y-m-d\TH:i', strtotime($ad['start_at'])) : '')) ?>" class="form-input">
                        </div>

                        <div>
                            <label class="form-label">End At</label>
                            <input type="datetime-local" name="end_at" value="<?= htmlspecialchars($_POST['end_at'] ?? ($ad['end_at'] ? date('Y-m-d\TH:i', strtotime($ad['end_at'])) : '')) ?>" class="form-input">
                        </div>

                        <div class="md:col-span-2">
                            <label class="form-label">Ad Code (HTML/JS) *</label>
                            <textarea name="code" rows="8" class="form-input" required><?= htmlspecialchars($_POST['code'] ?? $ad['code']) ?></textarea>
                        </div>
                    </div>

                    <div class="flex gap-4 mt-8">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i>Update Ad
                        </button>
                        <a href="ads.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-2"></i>Back
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>
