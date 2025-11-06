<?php
require_once '../src/config.php';
require_once '../src/db.php';
require_once '../src/helpers.php';
require_once '../src/models/AdmitCard.php';
require_once '../src/models/Author.php';
require_once '../src/notifications.php';

requireLogin();

$admitCardModel = new AdmitCard();
$authorModel = new Author();
$success = '';
$error = '';
$authors = $authorModel->getAll(1000, 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $organization = sanitizeInput($_POST['organization'] ?? '');
    $exam_date = sanitizeInput($_POST['exam_date'] ?? '');
    $description = $_POST['description'] ?? '';
    $instructions = $_POST['instructions'] ?? '';
    $required_documents = $_POST['required_documents'] ?? '';
    $status = sanitizeInput($_POST['status'] ?? 'draft');
    $send_push = isset($_POST['send_push']);
    $thumbnail_url = sanitizeInput($_POST['thumbnail_url'] ?? '');
    $author_id = isset($_POST['author_id']) && $_POST['author_id'] !== '' ? (int)$_POST['author_id'] : null;
    
    if ($title && $organization && $description) {
        $slug = slugify($title);
        // Ensure unique slug by appending incremental suffix if needed
        if (method_exists($admitCardModel, 'getBySlug')) {
            $baseSlug = $slug;
            $n = 2;
            while ($admitCardModel->getBySlug($slug)) {
                $slug = $baseSlug . '-' . $n;
                $n++;
                if ($n > 200) { break; } // safety cap
            }
        }

        $data = [
            'title' => $title,
            'slug' => $slug,
            'organization' => $organization,
            'description' => $description,
            'exam_date' => $exam_date ?: null,
            'download_url' => sanitizeInput($_POST['download_url'] ?? ''),
            'instructions' => $instructions,
            'required_documents' => $required_documents,
            'status' => $status,
            'thumbnail_url' => $thumbnail_url ?: null,
            'author_id' => $author_id,
            'published_at' => date('Y-m-d H:i:s')
        ];
        
        try {
            $admitId = $admitCardModel->create($data);
        } catch (Throwable $ex) {
            $error = 'Failed to add admit card: ' . $ex->getMessage();
            $admitId = 0;
        }
        if ($admitId) {
            // Save flexible content blocks for Admit
            // Events (multi exam dates, admit card date, etc.)
            $events = [];
            $et = $_POST['events']['event_type'] ?? [];
            $el = $_POST['events']['event_label'] ?? [];
            $sd = $_POST['events']['start_date'] ?? [];
            $ed = $_POST['events']['end_date'] ?? [];
            $nt = $_POST['events']['notes'] ?? [];
            $so = $_POST['events']['sort_order'] ?? [];
            for ($i=0; $i<count($et); $i++) {
                if (!$et[$i] && !$sd[$i] && !$el[$i]) continue;
                $events[] = [
                    'event_type' => $et[$i] ?: 'exam',
                    'event_label' => $el[$i] ?: null,
                    'start_date' => $sd[$i] ?: null,
                    'end_date' => $ed[$i] ?: null,
                    'notes' => $nt[$i] ?: null,
                    'sort_order' => isset($so[$i]) && $so[$i] !== '' ? (int)$so[$i] : 0,
                ];
            }
            if (!empty($events)) {
                try { $admitCardModel->saveEvents($admitId, $events); }
                catch (Throwable $ex) { error_log('add-admit saveEvents error: ' . $ex->getMessage()); }
            }

      // Links (support custom label)
      $links = [];
      if (!empty($_POST['links'])) {
        $l = $_POST['links'];
        $count = max(count($l['label'] ?? []), count($l['url'] ?? []));
        for ($i=0; $i<$count; $i++) {
          $labelType = sanitizeInput($l['label'][$i] ?? '');
          $customLabel = sanitizeInput($l['custom_label'][$i] ?? '');
          $label = $labelType === 'Custom' ? $customLabel : $labelType;
          $url = sanitizeInput($l['url'][$i] ?? '');
          $order = isset($l['sort_order'][$i]) && $l['sort_order'][$i] !== '' ? (int)$l['sort_order'][$i] : 0;
          if ($label || $url) {
            $links[] = [
              'label' => $label,
              'url' => $url,
              'sort_order' => $order,
            ];
          }
        }
      }
            if (!empty($links)) {
                try { $admitCardModel->saveLinks($admitId, $links); }
                catch (Throwable $ex) { error_log('add-admit saveLinks error: ' . $ex->getMessage()); }
            }

            // Sections (e.g., Instructions Rich text already exists; keep flexible too)
            $sections = [];
            if (!empty($_POST['sections']['how_to_apply'])) {
                $sections[] = ['section_type' => 'how_to_apply', 'title' => 'How to Apply', 'content' => $_POST['sections']['how_to_apply'], 'sort_order' => 0];
            }
            if (!empty($_POST['sections']['mode_of_exam'])) {
                $sections[] = ['section_type' => 'mode_of_exam', 'title' => 'Mode of Exam', 'content' => $_POST['sections']['mode_of_exam'], 'sort_order' => 1];
            }
            if (!empty($_POST['sections']['other'])) {
                $sections[] = ['section_type' => 'other', 'title' => 'Notes', 'content' => $_POST['sections']['other'], 'sort_order' => 2];
            }
            if (!empty($sections)) {
                try { $admitCardModel->saveSections($admitId, $sections); }
                catch (Throwable $ex) { error_log('add-admit saveSections error: ' . $ex->getMessage()); }
            }

            // FAQs
            $faqq = $_POST['faqs']['question'] ?? [];
            $faqa = $_POST['faqs']['answer'] ?? [];
            $faqo = $_POST['faqs']['sort_order'] ?? [];
            $faqi = $_POST['faqs']['is_active'] ?? [];
            $faqs = [];
            for ($i=0; $i<count($faqq); $i++) {
                if (!$faqq[$i] && !$faqa[$i]) continue;
                $faqs[] = [
                    'question' => $faqq[$i] ?: '',
                    'answer' => $faqa[$i] ?: '',
                    'sort_order' => isset($faqo[$i]) && $faqo[$i] !== '' ? (int)$faqo[$i] : 0,
                    'is_active' => isset($faqi[$i]) ? 1 : 0,
                ];
            }
            if (!empty($faqs)) {
                try { $admitCardModel->saveFaqs($admitId, $faqs); }
                catch (Throwable $ex) { error_log('add-admit saveFaqs error: ' . $ex->getMessage()); }
            }

            if ($send_push && $status === 'published') {
                $admitForNotif = [
                    'title' => $title,
                    'slug' => $slug,
                    'organization' => $organization,
                    'thumbnail_url' => $thumbnail_url ?: null,
                ];
                try { onesignal_notify_admit($admitForNotif); } catch (Exception $e) { /* ignore */ }
            }
            // PRG redirect to admit-cards list
            redirect('admit-cards.php?saved=1');
            // Unreachable after redirect
            $success = 'Admit card added successfully!';
        } else {
            if (!$error) {
                $error = 'Failed to add admit card';
            }
        }
    } else {
        $error = 'Please fill all required fields';
    }
}

$pageTitle = 'Add New Admit Card';
include 'includes/header.php';
?>
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-lg shadow-md p-6 md:p-8">
                <?php if ($success): ?>
                <div class="alert alert-success mb-6">
                    <i class="fas fa-check-circle mr-2"></i><?= $success ?>
                    <a href="admit-cards.php" class="ml-4 text-green-700 underline">View all admit cards</a>
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
                            <label class="form-label">Admit Card Title *</label>
                            <input type="text" name="title" class="form-input" required 
                                   value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                                   placeholder="e.g., SSC CGL 2023 Admit Card">
                        </div>
                        
                        <div>
                            <label class="form-label">Organization *</label>
                            <input type="text" name="organization" class="form-input" required 
                                   value="<?= htmlspecialchars($_POST['organization'] ?? '') ?>"
                                   placeholder="e.g., Staff Selection Commission">
                        </div>
                        
                        <div>
                            <label class="form-label">Exam Date</label>
                            <input type="text" name="exam_date" class="form-input" 
                                   value="<?= htmlspecialchars($_POST['exam_date'] ?? '') ?>"
                                   placeholder="e.g., 2025-10-15 or 10-15 October 2025">
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="form-label">Download URL</label>
                            <input type="url" id="download_url" name="download_url" class="form-input" 
                                   value="<?= htmlspecialchars($_POST['download_url'] ?? '') ?>"
                                   placeholder="https://example.com/admit-card.pdf">
                            <div class="mt-2 flex items-center gap-3">
                                <input type="file" id="dl_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="form-input p-1">
                                <button type="button" id="dl_upload_btn" class="btn btn-secondary"><i class="fas fa-upload mr-2"></i>Upload</button>
                                <span id="dl_status" class="text-sm text-gray-500"></span>
                            </div>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="form-label">Description *</label>
                            <textarea id="description" name="description" rows="6" class="form-input richtext" required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="form-label">Instructions</label>
                            <textarea id="instructions" name="instructions" rows="4" class="form-input richtext"><?= htmlspecialchars($_POST['instructions'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="form-label">Required Documents</label>
                            <textarea id="required_documents" name="required_documents" rows="3" class="form-input richtext"><?= htmlspecialchars($_POST['required_documents'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="form-label">Thumbnail URL</label>
                            <input type="url" id="thumbnail_url" name="thumbnail_url" class="form-input" 
                                   value="<?= htmlspecialchars($_POST['thumbnail_url'] ?? '') ?>"
                                   placeholder="https://example.com/admit-thumb.jpg">
                            <div class="mt-2 flex items-center gap-3">
                                <input type="file" id="thumb_file" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" class="form-input p-1">
                                <button type="button" id="thumb_upload_btn" class="btn btn-secondary"><i class="fas fa-upload mr-2"></i>Upload</button>
                                <span id="thumb_status" class="text-sm text-gray-500"></span>
                            </div>
                            <div id="thumb_preview_wrap" class="mt-2 hidden">
                                <img id="thumb_preview" src="" alt="Thumbnail preview" class="h-24 rounded border object-cover">
                            </div>
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
                    
                    <!-- Events Repeater -->
                    <div class="mt-8">
                      <h3 class="text-lg font-semibold mb-3">Events (Dates)</h3>
                      <div id="events_wrap" class="space-y-3">
                        <div class="grid grid-cols-6 gap-2 ev_row">
                          <select name="events[event_type][]" class="form-input">
                            <option value="exam">Exam</option>
                            <option value="admit_card">Admit Card</option>
                            <option value="result">Result</option>
                            <option value="other">Other</option>
                          </select>
                          <input type="text" name="events[event_label][]" class="form-input" placeholder="Label (e.g., Tier I)">
                          <input type="date" name="events[start_date][]" class="form-input">
                          <input type="date" name="events[end_date][]" class="form-input">
                          <input type="text" name="events[notes][]" class="form-input" placeholder="Notes">
                          <input type="number" name="events[sort_order][]" class="form-input" placeholder="#" value="0">
                        </div>
                      </div>
                      <button type="button" class="btn btn-secondary mt-2" id="add_event">+ Add Event</button>
                    </div>

                    <!-- Important Links Repeater -->
                    <div class="mt-8">
                      <h3 class="text-lg font-semibold mb-3">Important Links</h3>
                      <div id="links_wrap" class="space-y-3">
                        <div class="grid grid-cols-12 gap-2 link_row">
                          <div class="col-span-12 md:col-span-3">
                            <select name="links[label][]" class="form-input link-label-select" onchange="handleLinkLabelChange(this)">
                              <?php $labOpts=['Download Admit Card','Notification','Official Website','Other','Custom']; foreach($labOpts as $o): ?>
                                <option value="<?= $o ?>"><?= $o ?></option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                          <div class="col-span-12 md:col-span-3">
                            <input type="text" name="links[custom_label][]" class="form-input custom-label-input" placeholder="Enter custom label..." style="display: none">
                          </div>
                          <div class="col-span-12 md:col-span-4">
                            <input type="url" name="links[url][]" class="form-input" placeholder="https://...">
                          </div>
                          <div class="col-span-12 md:col-span-2 flex gap-2">
                            <input type="number" name="links[sort_order][]" class="form-input w-20" placeholder="#" value="0">
                            <button type="button" class="btn btn-error delete-row-btn" onclick="deleteRow(this, 'link_row')">
                              <i class="fas fa-trash"></i>
                            </button>
                          </div>
                        </div>
                      </div>
                      <button type="button" class="btn btn-secondary mt-2" id="add_link">+ Add Link</button>
                    </div>

                    <!-- Sections: How to Apply, Mode of Exam, Notes -->
                    <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-4">
                      <div>
                        <label class="form-label">How to Apply</label>
                        <textarea name="sections[how_to_apply]" rows="3" class="form-input richtext"></textarea>
                      </div>
                      <div>
                        <label class="form-label">Mode of Exam</label>
                        <textarea name="sections[mode_of_exam]" rows="3" class="form-input richtext"></textarea>
                      </div>
                      <div>
                        <label class="form-label">Notes</label>
                        <textarea name="sections[other]" rows="3" class="form-input richtext"></textarea>
                      </div>
                    </div>

                    <!-- FAQs Repeater -->
                    <div class="mt-8">
                      <h3 class="text-lg font-semibold mb-3">FAQs</h3>
                      <div id="faqs_wrap" class="space-y-3">
                        <div class="grid grid-cols-5 gap-2 faq_row">
                          <input type="text" name="faqs[question][]" class="form-input" placeholder="Question">
                          <input type="text" name="faqs[answer][]" class="form-input" placeholder="Answer">
                          <input type="number" name="faqs[sort_order][]" class="form-input" placeholder="#" value="0">
                          <label class="inline-flex items-center gap-2"><input type="checkbox" name="faqs[is_active][]" checked> Active</label>
                          <div></div>
                        </div>
                      </div>
                      <button type="button" class="btn btn-secondary mt-2" id="add_faq">+ Add FAQ</button>
                    </div>

                    <div class="flex gap-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i>Add Admit Card
                        </button>
                        
                        <a href="admit-cards.php" class="btn btn-secondary">
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
                (function(){
                  function handleLinkLabelChange(selectElement) {
                    const customInput = selectElement.closest('.grid').querySelector('.custom-label-input');
                    if (!customInput) return;
                    if (selectElement.value === 'Custom' || selectElement.value === 'Other') {
                      customInput.style.display = 'block';
                      customInput.required = true;
                    } else {
                      customInput.style.display = 'none';
                      customInput.required = false;
                      customInput.value = '';
                    }
                  }

                  function deleteRow(button, rowClass) {
                    const row = button.closest('.' + rowClass);
                    const container = row.closest('.space-y-3');
                    if (container && container.querySelectorAll('.' + rowClass).length > 1) {
                        if (confirm('Are you sure you want to delete this item?')) row.remove();
                    }
                  }

                  function cloneRow(wrapperId, rowClass){
                    const wrap = document.getElementById(wrapperId);
                    if (!wrap) return;
                    const first = wrap.querySelector('.' + rowClass);
                    if (!first) return;
                    const node = first.cloneNode(true);
                    node.querySelectorAll('input').forEach(i=>{ 
                        if(i.type==='checkbox'){ i.checked=true; } else { i.value=''; if (i.classList && i.classList.contains('custom-label-input')) { i.style.display='none'; i.required=false; } }
                    });
                    node.querySelectorAll('select').forEach(s=>{ 
                        s.selectedIndex = 0; 
                        if (s.classList && s.classList.contains('link-label-select')) {
                            s.onchange = function(){ handleLinkLabelChange(this); };
                        }
                    });
                    node.querySelectorAll('button').forEach(b=>{ if (b.classList && b.classList.contains('delete-row-btn')) { b.onclick = function(){ deleteRow(this, rowClass); }; } });
                    wrap.appendChild(node);
                  }
                  document.getElementById('add_event')?.addEventListener('click', ()=> cloneRow('events_wrap','ev_row'));
                  document.getElementById('add_link')?.addEventListener('click', ()=> cloneRow('links_wrap','link_row'));
                  document.getElementById('add_faq')?.addEventListener('click', ()=> cloneRow('faqs_wrap','faq_row'));
                })();
                </script>
            </div>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>
