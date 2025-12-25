<?php
require_once '../src/config.php';
require_once '../src/db.php';
require_once '../src/helpers.php';
require_once '../src/models/Syllabus.php';
require_once '../src/models/Author.php';
require_once '../src/notifications.php';

requireLogin();

$syllabusModel = new Syllabus();
$authorModel = new Author();
$success = '';
$error = '';
$authors = $authorModel->getAll(1000, 0);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    redirect('syllabi.php');
}

$syllabus = $syllabusModel->getById($id);
if (!$syllabus) {
    redirect('syllabi.php');
}
// Previous status to detect publish transition
$__prev_status = $syllabus['status'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $organization = sanitizeInput($_POST['organization'] ?? '');
    $description = $_POST['description'] ?? '';
    $exam_date = sanitizeInput($_POST['exam_date'] ?? '');
    $download_url = sanitizeInput($_POST['download_url'] ?? '');
    $sections = $_POST['sections'] ?? '';
    $status = sanitizeInput($_POST['status'] ?? 'draft');
    $send_push = isset($_POST['send_push']);
    $thumbnail_url = sanitizeInput($_POST['thumbnail_url'] ?? '');
    $author_id = isset($_POST['author_id']) && $_POST['author_id'] !== '' ? (int)$_POST['author_id'] : null;
    $published_at = $_POST['published_at'] ?? '';
    
    if ($title && $organization && $description) {
        $slugBase = slugify($title);
        $slug = generateUniqueSlug('syllabi', $slugBase, $id);
        
        $data = [
            'title' => $title,
            'slug' => $slug,
            'organization' => $organization,
            'description' => $description,
            'exam_date' => $exam_date ?: null,
            'download_url' => $download_url,
            'sections' => $sections,
            'status' => $status,
            'thumbnail_url' => $thumbnail_url ?: null,
            'author_id' => $author_id,
            'published_at' => $published_at ? date('Y-m-d H:i:s', strtotime($published_at)) : null
        ];
        
        try {
            $syllabusModel->update($id, $data);
            $success = 'Syllabus updated successfully!';
            
            // Refresh syllabus data
            $syllabus = $syllabusModel->getById($id);
            // Send push if transitioned to published
            if ($send_push && $__prev_status !== 'published' && $status === 'published') {
                $syllForNotif = [
                    'title' => $syllabus['title'] ?? $title,
                    'slug' => $syllabus['slug'] ?? '',
                    'organization' => $syllabus['organization'] ?? '',
                    'thumbnail_url' => $syllabus['thumbnail_url'] ?? null,
                ];
                try { onesignal_notify_syllabus($syllForNotif); } catch (Exception $e) { /* ignore */ }
            }
        } catch (Throwable $e) {
            $error = 'Update failed: ' . htmlspecialchars($e->getMessage());
        }
    } else {
        $error = 'Please fill all required fields';
    }
}

$pageTitle = 'Edit Syllabus';
include 'includes/header.php';
?>
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-lg shadow-md p-6 md:p-8">
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

                <form method="POST" data-validate class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="form-label">Syllabus Title *</label>
                            <input type="text" name="title" class="form-input" required 
                                   value="<?= htmlspecialchars($syllabus['title']) ?>"
                                   placeholder="e.g., SSC CGL 2023 Syllabus">
                        </div>
                        
                        <div>
                            <label class="form-label">Organization *</label>
                            <input type="text" name="organization" class="form-input" required 
                                   value="<?= htmlspecialchars($syllabus['organization']) ?>"
                                   placeholder="e.g., Staff Selection Commission">
                        </div>
                        
                        <div>
                            <label class="form-label">Exam Date</label>
                            <input type="text" name="exam_date" class="form-input" 
                                   value="<?= htmlspecialchars($syllabus['exam_date']) ?>"
                                   placeholder="e.g., 2025-10-15 or 10-15 October 2025">
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="form-label">Download URL</label>
                            <input type="url" id="download_url" name="download_url" class="form-input" 
                                   value="<?= htmlspecialchars($syllabus['download_url']) ?>"
                                   placeholder="https://example.com/syllabus.pdf">
                            <div class="mt-2 flex items-center gap-3">
                                <input type="file" id="dl_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="form-input p-1">
                                <button type="button" id="dl_upload_btn" class="btn btn-secondary"><i class="fas fa-upload mr-2"></i>Upload</button>
                                <span id="dl_status" class="text-sm text-gray-500"></span>
                            </div>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="form-label">Description *</label>
                            <textarea name="description" rows="6" class="form-input richtext" required 
                                      placeholder="Enter syllabus overview and exam pattern..."><?= htmlspecialchars($syllabus['description']) ?></textarea>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="form-label">Syllabus Sections (JSON Format)</label>
                            <textarea name="sections" rows="10" class="form-input font-mono text-sm" 
                                      placeholder='{"General Knowledge": ["History", "Geography", "Current Affairs"], "Quantitative Aptitude": ["Arithmetic", "Algebra", "Geometry"]}'><?= htmlspecialchars($syllabus['sections']) ?></textarea>
                            <small class="text-gray-500">Format: {"Section Name": ["Topic 1", "Topic 2"]} or {"Section": "Description text"}</small>
                        </div>
                        
                        <div>
                            <label class="form-label">Thumbnail URL</label>
                            <input type="url" id="thumbnail_url" name="thumbnail_url" class="form-input" 
                                   value="<?= htmlspecialchars($syllabus['thumbnail_url']) ?>"
                                   placeholder="https://example.com/syllabus-thumb.jpg">
                            <div class="mt-2 flex items-center gap-3">
                                <input type="file" id="thumb_file" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" class="form-input p-1">
                                <button type="button" id="thumb_upload_btn" class="btn btn-secondary"><i class="fas fa-upload mr-2"></i>Upload</button>
                                <span id="thumb_status" class="text-sm text-gray-500"></span>
                            </div>
                            <div id="thumb_preview_wrap" class="mt-2 <?= empty($syllabus['thumbnail_url']) ? 'hidden' : '' ?>">
                                <img id="thumb_preview" src="<?= htmlspecialchars($syllabus['thumbnail_url'] ?? '') ?>" alt="Thumbnail preview" class="h-24 rounded border object-cover">
                            </div>
                        </div>

                        <div>
                            <label class="form-label">Author</label>
                            <select name="author_id" class="form-input">
                                <option value="">Select Author</option>
                                <?php foreach ($authors as $a): ?>
                                <option value="<?= $a['id'] ?>" <?= (($syllabus['author_id'] ?? '') == $a['id']) ? 'selected' : '' ?>><?= htmlspecialchars($a['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Status</label>
                            <select name="status" class="form-input">
                                <option value="draft" <?= $syllabus['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="published" <?= $syllabus['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="form-label">Published Date</label>
                            <input type="datetime-local" name="published_at" class="form-input" 
                                   value="<?= !empty($syllabus['published_at']) ? date('Y-m-d\TH:i', strtotime($syllabus['published_at'])) : '' ?>">
                        </div>
                        <div class="md:col-span-2">
                            <label class="inline-flex items-center gap-2">
                                <input type="checkbox" name="send_push" <?= isset($_POST['send_push']) ? 'checked' : '' ?>>
                                <span>Send Push Notification (on Publish)</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="flex gap-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i>Update Syllabus
                        </button>
                        
                        <a href="syllabi.php" class="btn btn-secondary">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </a>
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
                (function(){
                  const fileInput = document.getElementById('dl_file');
                  const btn = document.getElementById('dl_upload_btn');
                  const urlInput = document.getElementById('download_url');
                  const statusEl = document.getElementById('dl_status');
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
    <script>
        // JSON validation for sections
        document.querySelector('textarea[name="sections"]').addEventListener('blur', function() {
            if (this.value.trim()) {
                try {
                    JSON.parse(this.value);
                    this.classList.remove('border-red-500');
                    this.classList.add('border-green-500');
                } catch (e) {
                    this.classList.remove('border-green-500');
                    this.classList.add('border-red-500');
                    alert('Invalid JSON format in sections field');
                }
            }
        });
    </script>

<?php include 'includes/footer.php'; ?>
