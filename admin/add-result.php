<?php
require_once '../src/config.php';
require_once '../src/db.php';
require_once '../src/helpers.php';
require_once '../src/models/Result.php';
require_once '../src/models/Author.php';
require_once '../src/notifications.php';

requireLogin();

$resultModel = new Result();
$authorModel = new Author();
$success = '';
$error = '';
$authors = $authorModel->getAll(1000, 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $organization = sanitizeInput($_POST['organization'] ?? '');
    $description = $_POST['description'] ?? '';
    $result_date = sanitizeInput($_POST['result_date'] ?? '');
    $download_url = sanitizeInput($_POST['download_url'] ?? '');
    $status = sanitizeInput($_POST['status'] ?? 'draft');
    $send_push = isset($_POST['send_push']);
    $thumbnail_url = sanitizeInput($_POST['thumbnail_url'] ?? '');
    $author_id = isset($_POST['author_id']) && $_POST['author_id'] !== '' ? (int)$_POST['author_id'] : null;
    
    if ($title && $organization && $description) {
        $slugBase = slugify($title);
        $slug = generateUniqueSlug('results', $slugBase);
        
        $data = [
            'title' => $title,
            'slug' => $slug,
            'organization' => $organization,
            'description' => $description,
            'result_date' => $result_date ?: null,
            'download_url' => $download_url,
            'status' => $status,
            'thumbnail_url' => $thumbnail_url ?: null,
            'author_id' => $author_id,
            'published_at' => date('Y-m-d H:i:s')
        ];
        try {
        
        // Create base result
        $newId = $resultModel->create($data);

        // Parse flexible repeaters
        $events = [];
        if (!empty($_POST['events'])) {
            $e = $_POST['events'];
            $count = max(count($e['event_type'] ?? []), count($e['event_label'] ?? []));
            for ($i=0; $i<$count; $i++) {
                $evtType = sanitizeInput($e['event_type'][$i] ?? 'result');
                $evtLabel = sanitizeInput($e['event_label'][$i] ?? '');
                $start = sanitizeInput($e['start_date'][$i] ?? '');
                $end = sanitizeInput($e['end_date'][$i] ?? '');
                $notes = sanitizeInput($e['notes'][$i] ?? '');
                $order = (int)($e['sort_order'][$i] ?? 0);
                if ($evtLabel || $start || $end || $notes) {
                    $events[] = [
                        'event_type' => $evtType ?: 'result',
                        'event_label' => $evtLabel ?: null,
                        'start_date' => $start ?: null,
                        'end_date' => $end ?: null,
                        'notes' => $notes ?: null,
                        'sort_order' => $order,
                    ];
                }
            }
        }

        $links = [];
        if (!empty($_POST['links'])) {
            $l = $_POST['links'];
            $count = max(count($l['label'] ?? []), count($l['url'] ?? []));
            for ($i=0; $i<$count; $i++) {
                $labelType = sanitizeInput($l['label'][$i] ?? '');
                $customLabel = sanitizeInput($l['custom_label'][$i] ?? '');
                $label = $labelType === 'Custom' ? $customLabel : $labelType;
                $url = sanitizeInput($l['url'][$i] ?? '');
                $order = (int)($l['sort_order'][$i] ?? 0);
                if ($label || $url) {
                    $links[] = ['label' => $label, 'url' => $url, 'sort_order' => $order];
                }
            }
        }

        $sections = [];
        if (!empty($_POST['sections'])) {
            $s = $_POST['sections'];
            $count = max(count($s['section_type'] ?? []), count($s['title'] ?? []), count($s['content'] ?? []));
            for ($i=0; $i<$count; $i++) {
                $sections[] = [
                    'section_type' => sanitizeInput($s['section_type'][$i] ?? 'other'),
                    'title' => sanitizeInput($s['title'][$i] ?? ''),
                    'content' => $s['content'][$i] ?? '',
                    'sort_order' => (int)($s['sort_order'][$i] ?? 0),
                ];
            }
        }

        $faqs = [];
        if (!empty($_POST['faqs'])) {
            $f = $_POST['faqs'];
            $count = max(count($f['question'] ?? []), count($f['answer'] ?? []));
            for ($i=0; $i<$count; $i++) {
                $q = sanitizeInput($f['question'][$i] ?? '');
                $a = $f['answer'][$i] ?? '';
                if ($q || $a) {
                    $faqs[] = [
                        'question' => $q,
                        'answer' => $a,
                        'sort_order' => (int)($f['sort_order'][$i] ?? 0),
                        'is_active' => isset($f['is_active'][$i]) ? 1 : 0,
                    ];
                }
            }
        }

        // Save flexible content
        $resultModel->saveEvents($newId, $events);
        $resultModel->saveLinks($newId, $links);
        $resultModel->saveSections($newId, $sections);
        $resultModel->saveFaqs($newId, $faqs);

        if ($send_push && $status === 'published') {
            $resultForNotif = [
                'title' => $title,
                'slug' => $slug,
                'organization' => $organization,
                'thumbnail_url' => $thumbnail_url ?: null,
            ];
            try { onesignal_notify_result($resultForNotif); } catch (Exception $e) { /* ignore */ }
        }
        // Redirect (PRG) to results list
        redirect('results.php?saved=1');
        // Unreachable after redirect
        $success = 'Result added successfully!';
        } catch (Throwable $e) {
            $error = 'Creation failed: ' . htmlspecialchars($e->getMessage());
        }
    } else {
        $error = 'Please fill all required fields';
    }
}

$pageTitle = 'Add New Result';
include 'includes/header.php';
?>
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-lg shadow-md p-6 md:p-8">
                <?php if ($success): ?>
                <div class="alert alert-success mb-6">
                    <i class="fas fa-check-circle mr-2"></i><?= $success ?>
                    <a href="results.php" class="ml-4 text-green-700 underline">View all results</a>
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
                            <label class="form-label">Result Title *</label>
                            <input type="text" name="title" class="form-input" required 
                                   value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                                   placeholder="e.g., SSC CGL 2023 Final Result">
                        </div>
                        
                        <div>
                            <label class="form-label">Organization *</label>
                            <input type="text" name="organization" class="form-input" required 
                                   value="<?= htmlspecialchars($_POST['organization'] ?? '') ?>"
                                   placeholder="e.g., Staff Selection Commission">
                        </div>
                        
                        <div>
                            <label class="form-label">Result Date</label>
                            <input type="date" name="result_date" class="form-input" 
                                   value="<?= htmlspecialchars($_POST['result_date'] ?? '') ?>">
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="form-label">Download URL</label>
                            <input type="url" id="download_url" name="download_url" class="form-input" 
                                   value="<?= htmlspecialchars($_POST['download_url'] ?? '') ?>"
                                   placeholder="https://example.com/result.pdf">
                            <div class="mt-2 flex items-center gap-3">
                                <input type="file" id="dl_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="form-input p-1">
                                <button type="button" id="dl_upload_btn" class="btn btn-secondary"><i class="fas fa-upload mr-2"></i>Upload</button>
                                <span id="dl_status" class="text-sm text-gray-500"></span>
                            </div>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="form-label">Description *</label>
                            <textarea id="description" name="description" rows="8" class="form-input richtext" required 
                                      placeholder="Enter detailed result information..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                        </div>

                        <!-- Events Repeater -->
                        <div class="md:col-span-2">
                            <label class="form-label">Events</label>
                            <div id="events_wrapper" class="space-y-3">
                                <div class="event_row grid grid-cols-12 gap-2">
                                    <div class="col-span-12 md:col-span-2">
                                        <select name="events[event_type][]" class="form-input">
                                            <option value="result" selected>Result</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    <div class="col-span-12 md:col-span-3"><input type="text" name="events[event_label][]" class="form-input" placeholder="Label (e.g., Phase I)"></div>
                                    <div class="col-span-6 md:col-span-2"><input type="date" name="events[start_date][]" class="form-input"></div>
                                    <div class="col-span-6 md:col-span-2"><input type="date" name="events[end_date][]" class="form-input"></div>
                                    <div class="col-span-12 md:col-span-2"><input type="text" name="events[notes][]" class="form-input" placeholder="Notes"></div>
                                    <div class="col-span-6 md:col-span-1"><input type="number" name="events[sort_order][]" class="form-input" placeholder="#"></div>
                                </div>
                            </div>
                            <div id="thumb_preview_wrap" class="mt-2 hidden">
                                <img id="thumb_preview" src="" alt="Thumbnail preview" class="h-24 rounded border object-cover">
                            </div>
                        </div>

                        <!-- Important Links Repeater -->
                        <div class="md:col-span-2">
                            <label class="form-label">Important Links</label>
                            <div id="links_wrapper" class="space-y-3">
                                <div class="link_row grid grid-cols-12 gap-2">
                                    <div class="col-span-12 md:col-span-3">
                                        <select name="links[label][]" class="form-input link-label-select" onchange="handleLinkLabelChange(this)">
                                            <?php $linkTypes=['Check Result','Download Result','Official Website','Other','Custom']; ?>
                                            <?php foreach($linkTypes as $type): ?>
                                            <option value="<?= $type ?>"><?= $type ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-span-12 md:col-span-2">
                                        <input type="text" name="links[custom_label][]" class="form-input custom-label-input" placeholder="Custom label..." style="display: none">
                                    </div>
                                    <div class="col-span-12 md:col-span-5">
                                        <input type="url" name="links[url][]" class="form-input" placeholder="https://...">
                                    </div>
                                    <div class="col-span-12 md:col-span-2 flex gap-2">
                                        <input type="number" name="links[sort_order][]" class="form-input w-20" placeholder="#">
                                        <button type="button" class="btn btn-error delete-row-btn" onclick="deleteRow(this, 'link_row')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-secondary mt-2" onclick="cloneRow('links_wrapper','link_row')"><i class="fas fa-plus mr-1"></i>Add Link</button>
                        </div>

                        <!-- Sections Repeater -->
                        <div class="md:col-span-2">
                             <label class="form-label">Sections</label>
                             <div id="sections_wrapper" class="space-y-3">
                                <div class="section_row grid grid-cols-12 gap-2">
                                    <div class="col-span-12 md:col-span-3">
                                        <select name="sections[section_type][]" class="form-input">
                                            <option value="how_to_check" selected>How to Check Result</option>
                                            <option value="notes">Important Notes</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    <div class="col-span-12 md:col-span-8"><input type="text" name="sections[title][]" class="form-input" placeholder="Section Title"></div>
                                    <div class="col-span-6 md:col-span-1"><input type="number" name="sections[sort_order][]" class="form-input" placeholder="#"></div>
                                    <div class="col-span-12"><textarea name="sections[content][]" id="section_content_new" rows="4" class="form-input richtext" placeholder="Content (supports HTML)"></textarea></div>
                                </div>
                             </div>
                             <button type="button" class="btn btn-secondary mt-2" onclick="cloneRow('sections_wrapper','section_row')"><i class="fas fa-plus mr-1"></i>Add Section</button>
                        </div>

                        <div>
                            <label class="form-label">Author</label>
                            <select name="author_id" class="form-input">
                                <option value="">Select Author</option>
                                <?php foreach ($authors as $a): ?>
                                <option value="<?= $a['id'] ?>" <?= (($_POST['author_id'] ?? '') == $a['id']) ? 'selected' : '' ?>><?= htmlspecialchars($a['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Status</label>
                            <select name="status" class="form-input">
                                <option value="draft" <?= ($_POST['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="published" <?= ($_POST['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="inline-flex items-center gap-2">
                                <input type="checkbox" name="send_push" <?= (($_POST['send_push'] ?? '1')==='1') ? 'checked' : 'checked' ?>>
                                <span>Send Push Notification (on Published)</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="flex gap-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i>Add Result
                        </button>
                        
                        <a href="results.php" class="btn btn-secondary">
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
                <script>
                function handleLinkLabelChange(select) {
                    const row = select.closest('.link_row');
                    const customInput = row.querySelector('.custom-label-input');
                    if (customInput) {
                        customInput.style.display = select.value === 'Custom' ? 'block' : 'none';
                        if (select.value !== 'Custom') customInput.value = '';
                    }
                }
                function deleteRow(btn, rowClass) {
                    const row = btn.closest('.' + rowClass);
                    if (row && confirm('Are you sure you want to delete this item?')) {
                        row.remove();
                    }
                }
                function cloneRow(wrapperId, rowClass) {
                    const wrap = document.getElementById(wrapperId);
                    if (!wrap) return;
                    const row = wrap.querySelector('.' + rowClass);
                    if (!row) return;
                    const clone = row.cloneNode(true);
                    
                    // Generate unique suffix
                    const uniqueSuffix = Date.now() + '_' + Math.floor(Math.random() * 1000);

                    // clear inputs/textareas and reset custom inputs
                    clone.querySelectorAll('input').forEach(i=>{
                        if (i.type==='checkbox' || i.type==='radio') { i.checked = false; }
                        else { i.value = ''; if (i.classList && i.classList.contains('custom-label-input')) { i.style.display = 'none'; } }
                    });
                    
                    // Handle Textareas & TinyMCE
                    clone.querySelectorAll('textarea').forEach(t => {
                        t.value = '';
                        if (t.classList.contains('richtext')) {
                            // Remove any existing ID to avoid conflicts before assigning new one
                            t.removeAttribute('id');
                            // Assign new unique ID
                            t.id = 'editor_' + uniqueSuffix;
                            // Ensure the style is reset if TinyMCE modified it
                            t.style.display = 'block';
                            t.style.visibility = 'visible';
                            // Remove TinyMCE structure if cloned
                            const parent = t.parentElement;
                            const tox = parent.querySelector('.tox-tinymce');
                            if (tox) tox.remove();
                        }
                    });

                    clone.querySelectorAll('select').forEach(s=>{
                        s.selectedIndex = 0;
                        if (s.classList && s.classList.contains('link-label-select')) {
                            s.onchange = function(){ handleLinkLabelChange(this); };
                        }
                    });
                    clone.querySelectorAll('button').forEach(b => {
                        if (b.classList && b.classList.contains('delete-row-btn')) {
                            b.onclick = function(){ deleteRow(this, rowClass); };
                        }
                    });
                    wrap.appendChild(clone);

                    // Re-init TinyMCE for the new textarea
                    clone.querySelectorAll('textarea.richtext').forEach(t => {
                        if (window.tinymce) {
                            tinymce.init({
                                selector: '#' + t.id,
                                plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table help wordcount',
                                toolbar: 'undo redo | blocks | bold italic underline forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | table link image media | removeformat | preview code fullscreen',
                                menubar: 'file edit view insert format tools table help',
                                height: 300,
                                branding: false,
                                convert_urls: false
                            });
                        }
                    });
                }
                </script>
            </div>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>
