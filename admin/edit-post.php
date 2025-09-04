<?php
require_once '../src/config.php';
require_once '../src/db.php';
require_once '../src/helpers.php';
require_once '../src/models/Post.php';
require_once '../src/models/Author.php';

requireLogin();

$postModel = new Post();
$authorModel = new Author();
$db = getDB();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { redirect('posts.php'); }

$post = $postModel->getById($id);
if (!$post) { redirect('posts.php'); }

$categories = $db->fetchAll("SELECT * FROM categories ORDER BY name");
$authors = $authorModel->getAll(1000, 0);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $excerpt = sanitizeInput($_POST['excerpt'] ?? '');
    $content = $_POST['content'] ?? '';
    $category_id = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null;
    $status = $_POST['status'] ?? 'published';
    $meta_title = sanitizeInput($_POST['meta_title'] ?? '');
    $meta_description = sanitizeInput($_POST['meta_description'] ?? '');
    $thumbnail_url = sanitizeInput($_POST['thumbnail_url'] ?? '');
    $author_id = isset($_POST['author_id']) && $_POST['author_id'] !== '' ? (int)$_POST['author_id'] : null;

    if ($title && $content) {
        $data = [
            'title' => $title,
            'excerpt' => $excerpt ?: null,
            'content' => $content,
            'category_id' => $category_id,
            'status' => $status,
            'meta_title' => $meta_title ?: null,
            'meta_description' => $meta_description ?: null,
            'thumbnail_url' => $thumbnail_url ?: null,
            'author_id' => $author_id
        ];
        $ok = $postModel->update($id, $data);
        if ($ok) {
            $success = 'Post updated successfully!';
            $post = $postModel->getById($id);
        } else {
            $error = 'Failed to update post';
        }
    } else {
        $error = 'Please fill required fields';
    }
}

$pageTitle = 'Edit Post';
$currentPage = 'posts';
include 'includes/header.php';
?>
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-lg shadow-md p-6 md:p-8">
                <?php if ($success): ?>
                <div class="alert alert-success mb-6">
                    <i class="fas fa-check-circle mr-2"></i><?= $success ?>
                    <a href="posts.php" class="ml-4 text-green-700 underline">Back to posts</a>
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
                            <label class="form-label">Title *</label>
                            <input type="text" name="title" value="<?= htmlspecialchars($_POST['title'] ?? $post['title']) ?>" class="form-input" required>
                        </div>

                        <div class="md:col-span-2">
                            <label class="form-label">Excerpt</label>
                            <textarea name="excerpt" rows="3" class="form-input"><?= htmlspecialchars($_POST['excerpt'] ?? ($post['excerpt'] ?? '')) ?></textarea>
                        </div>

                        <div>
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-input">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" <?= (($_POST['category_id'] ?? ($post['category_id'] ?? '')) == $category['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Status</label>
                            <select name="status" class="form-input">
                                <option value="published" <?= ($_POST['status'] ?? $post['status']) === 'published' ? 'selected' : '' ?>>Published</option>
                                <option value="draft" <?= ($_POST['status'] ?? $post['status']) === 'draft' ? 'selected' : '' ?>>Draft</option>
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label class="form-label">Thumbnail URL</label>
                            <input type="url" id="thumbnail_url" name="thumbnail_url" value="<?= htmlspecialchars($_POST['thumbnail_url'] ?? ($post['thumbnail_url'] ?? '')) ?>" class="form-input" placeholder="https://example.com/thumbnail.jpg">
                            <div class="mt-2 flex items-center gap-3">
                                <input type="file" id="thumb_file" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" class="form-input p-1">
                                <button type="button" id="thumb_upload_btn" class="btn btn-secondary"><i class="fas fa-upload mr-2"></i>Upload</button>
                                <span id="thumb_status" class="text-sm text-gray-500"></span>
                            </div>
                            <div id="thumb_preview_wrap" class="mt-2 <?= empty($post['thumbnail_url']) ? 'hidden' : '' ?>">
                                <img id="thumb_preview" src="<?= htmlspecialchars($post['thumbnail_url'] ?? '') ?>" alt="Thumbnail preview" class="h-24 rounded border object-cover">
                            </div>
                        </div>

                        <div>
                            <label class="form-label">Author</label>
                            <select name="author_id" class="form-input">
                                <option value="">Select Author</option>
                                <?php foreach ($authors as $a): ?>
                                <option value="<?= $a['id'] ?>" <?= ((string)($_POST['author_id'] ?? ($post['author_id'] ?? '')) === (string)$a['id']) ? 'selected' : '' ?>><?= htmlspecialchars($a['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label class="form-label">Meta Title</label>
                            <input type="text" name="meta_title" value="<?= htmlspecialchars($_POST['meta_title'] ?? ($post['meta_title'] ?? '')) ?>" class="form-input">
                        </div>

                        <div class="md:col-span-2">
                            <label class="form-label">Meta Description</label>
                            <textarea name="meta_description" rows="3" class="form-input"><?= htmlspecialchars($_POST['meta_description'] ?? ($post['meta_description'] ?? '')) ?></textarea>
                        </div>

                        <div class="md:col-span-2">
                            <label class="form-label">Content *</label>
                            <textarea name="content" rows="10" class="form-input richtext" required><?= htmlspecialchars($_POST['content'] ?? $post['content']) ?></textarea>
                        </div>
                    </div>

                    <div class="flex gap-4 mt-8">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-2"></i>Update Post</button>
                        <a href="posts.php" class="btn btn-secondary"><i class="fas fa-arrow-left mr-2"></i>Back</a>
                    </div>
                </form>
                <script>
                (function(){
                  const fileInput = document.getElementById('thumb_file');
                  const btn = document.getElementById('thumb_upload_btn');
                  const urlInput = document.getElementById('thumbnail_url');
                  const statusEl = document.getElementById('thumb_status');
                  const prevWrap = document.getElementById('thumb_preview_wrap');
                  const prevImg = document.getElementById('thumb_preview');
                  if (!fileInput || !btn || !urlInput) return;
                  btn.addEventListener('click', async function(){
                    if (!fileInput.files || !fileInput.files[0]) { statusEl.textContent = 'Choose a file first'; return; }
                    statusEl.textContent = 'Uploading...';
                    const fd = new FormData();
                    fd.append('file', fileInput.files[0]);
                    try {
                      const res = await fetch('upload.php', { method: 'POST', body: fd, credentials: 'same-origin' });
                      const data = await res.json();
                      if (data && data.success && data.url) {
                        urlInput.value = data.url;
                        statusEl.textContent = 'Uploaded';
                        if (/(jpg|jpeg|png)$/i.test((data.filename||'').split('.').pop())) {
                          prevImg.src = data.url;
                          prevWrap.classList.remove('hidden');
                        } else {
                          prevWrap.classList.add('hidden');
                        }
                      } else {
                        statusEl.textContent = (data && data.error) ? data.error : 'Upload failed';
                      }
                    } catch (e) {
                      statusEl.textContent = 'Upload error';
                    }
                  });
                })();
                </script>
            </div>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>
