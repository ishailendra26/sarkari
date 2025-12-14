<?php
require_once '../src/config.php';
require_once '../src/db.php';
require_once '../src/helpers.php';
require_once '../src/models/Job.php';
require_once '../src/models/Author.php';
require_once '../src/notifications.php';

requireLogin();

$jobModel = new Job();
$authorModel = new Author();
$db = getDB();

// Get categories
$categories = $db->fetchAll("SELECT * FROM categories ORDER BY name");

$success = '';
$error = '';
$authors = $authorModel->getAll(1000, 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $organization = sanitizeInput($_POST['organization'] ?? '');
    $location = sanitizeInput($_POST['location'] ?? '');
    $apply_link = sanitizeInput($_POST['apply_link'] ?? '');
    $last_date = $_POST['last_date'] ?? '';
    $vacancy_count = (int)($_POST['vacancy_count'] ?? 0);
    $educational_qualification = $_POST['educational_qualification'] ?? '';
    $age_limit = sanitizeInput($_POST['age_limit'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $content = $_POST['content'] ?? '';
    $fees = $_POST['fees'] ?? '';
    $vacancy_details = $_POST['vacancy_details'] ?? '';
    $status = $_POST['status'] ?? 'published';
    $send_push = isset($_POST['send_push']);
    $thumbnail_url = sanitizeInput($_POST['thumbnail_url'] ?? '');
    $author_id = isset($_POST['author_id']) && $_POST['author_id'] !== '' ? (int)$_POST['author_id'] : null;
    
    if ($title && $organization && $category_id) {
        $slugBase = slugify($title);
        $slug = generateUniqueSlug('jobs', $slugBase);
        
        $data = [
            'title' => $title,
            'slug' => $slug,
            'organization' => $organization,
            'location' => $location,
            'apply_link' => $apply_link,
            'last_date' => $last_date ?: null,
            'vacancy_count' => $vacancy_count,
            'educational_qualification' => $educational_qualification,
            'age_limit' => $age_limit,
            'category_id' => $category_id,
            'content' => $content,
            'fees' => $fees,
            'vacancy_details' => $vacancy_details,
            'attachments' => null,
            'status' => $status,
            'thumbnail_url' => $thumbnail_url ?: null,
            'author_id' => $author_id
        ];

        // Validate Events input before creating Job
        $events = [];
        $hasValidationError = false;
        $et = $_POST['events']['event_type'] ?? [];
        $el = $_POST['events']['event_label'] ?? [];
        $sd = $_POST['events']['start_date'] ?? [];
        $ed = $_POST['events']['end_date'] ?? [];
        $nt = $_POST['events']['notes'] ?? [];
        $so = $_POST['events']['sort_order'] ?? [];
        for ($i=0; $i<count($et); $i++) {
            $etype = trim($et[$i] ?? '');
            $label = trim($el[$i] ?? '');
            $start = trim($sd[$i] ?? '');
            $end = trim($ed[$i] ?? '');
            $notes = trim($nt[$i] ?? '');
            $sort = isset($so[$i]) && $so[$i] !== '' ? (int)$so[$i] : 0;

            // Skip completely empty rows
            if ($etype === '' && $start === '' && $label === '' && $end === '' && $notes === '') continue;

            // Require at least Start Date or End Date or Label to avoid saving default type-only rows
            if ($start === '' && $end === '' && $label === '') {
                $error = 'Each event row must include at least a Start Date, End Date, or a Label.';
                $hasValidationError = true;
                break;
            }

            // Validate dates if provided (YYYY-MM-DD)
            if ($start !== '') {
                $d = DateTime::createFromFormat('Y-m-d', $start);
                if (!($d && $d->format('Y-m-d') === $start)) {
                    $error = 'Invalid Start Date format in Events. Please use YYYY-MM-DD.';
                    $hasValidationError = true;
                    break;
                }
            }
            if ($end !== '') {
                $d2 = DateTime::createFromFormat('Y-m-d', $end);
                if (!($d2 && $d2->format('Y-m-d') === $end)) {
                    $error = 'Invalid End Date format in Events. Please use YYYY-MM-DD.';
                    $hasValidationError = true;
                    break;
                }
            }

            $events[] = [
                'event_type' => $etype !== '' ? $etype : 'other',
                'event_label' => $label !== '' ? $label : null,
                'start_date' => $start !== '' ? $start : null,
                'end_date' => $end !== '' ? $end : null,
                'notes' => $notes !== '' ? $notes : null,
                'sort_order' => $sort,
            ];
        }

        if ($hasValidationError) {
            // Fall through and let form re-render with $error
        } else {
        try {
            $jobId = $jobModel->create($data);
            if ($jobId) {
                // Save flexible content blocks
                // Events
                if (!empty($events)) {
                    // Deduplicate events by signature to prevent multiple identical rows
                    $seen = [];
                    $dedup = [];
                    foreach ($events as $ev) {
                        $sig = implode('|', [
                            $ev['event_type'] ?? '',
                            $ev['event_label'] ?? '',
                            $ev['start_date'] ?? '',
                            $ev['end_date'] ?? '',
                            $ev['notes'] ?? '',
                            (string)($ev['sort_order'] ?? '0'),
                        ]);
                        if (isset($seen[$sig])) continue;
                        $seen[$sig] = true;
                        $dedup[] = $ev;
                    }
                    if (!empty($dedup)) { $jobModel->saveEvents($jobId, $dedup); }
                }

                // Links
                $links = [];
                $ll = $_POST['links']['label'] ?? [];
                $lu = $_POST['links']['url'] ?? [];
                $ls = $_POST['links']['sort_order'] ?? [];
                for ($i=0; $i<count($ll); $i++) {
                    // Skip if both label and URL are empty
                    if (trim($ll[$i] ?? '') === '' && trim($lu[$i] ?? '') === '') continue;
                    
                    $links[] = [
                        'label' => $ll[$i] ?: '',
                        'url' => $lu[$i] ?: '',
                        'sort_order' => isset($ls[$i]) && $ls[$i] !== '' ? (int)$ls[$i] : 0,
                    ];
                }
                if (!empty($links)) { $jobModel->saveLinks($jobId, $links); }

                // Old Fees Logic Removed

                // Age limit (single)
                if (isset($_POST['age_block'])) {
                    $jobModel->saveAgeLimit($jobId, [
                        'min_age' => $_POST['age_block']['min_age'] ?? null,
                        'max_age' => $_POST['age_block']['max_age'] ?? null,
                        'cutoff_date' => $_POST['age_block']['cutoff_date'] ?? null,
                        'relaxation_text' => $_POST['age_block']['relaxation_text'] ?? null,
                    ]);
                }

                // Old Vacancies Logic Removed

                // Sections (how_to_apply, mode_of_exam, fees_text, vacancy_text)
                $sections = [];
                if (!empty($_POST['sections']['how_to_apply'])) {
                    $sections[] = ['section_type' => 'how_to_apply', 'title' => 'How to Apply', 'content' => $_POST['sections']['how_to_apply'], 'sort_order' => 0];
                }
                if (!empty($_POST['sections']['mode_of_exam'])) {
                    $sections[] = ['section_type' => 'mode_of_exam', 'title' => 'Mode of Exam', 'content' => $_POST['sections']['mode_of_exam'], 'sort_order' => 1];
                }
                
                if (!empty($sections)) { $jobModel->saveSections($jobId, $sections); }

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
                if (!empty($faqs)) { $jobModel->saveFaqs($jobId, $faqs); }

                // Send notification (if applicable) first, then redirect for PRG
                if ($send_push && $status === 'published') {
                    $jobForNotif = [
                        'title' => $title,
                        'slug' => $slug,
                        'organization' => $organization,
                        'thumbnail_url' => $thumbnail_url ?: null
                    ];
                    try { onesignal_notify_job($jobForNotif); } catch (Exception $e) { /* ignore */ }
                }
                // Redirect to list (PRG) to avoid duplicate submissions
                redirect('jobs.php?saved=1');
                // Unreachable after redirect; kept for clarity
                $success = 'Job created successfully!';
            } else {
                $error = 'Failed to create job';
            }
        } catch (Throwable $e) {
            $error = 'Creation failed: ' . htmlspecialchars($e->getMessage());
        }
        }
    } else {
        $error = 'Please fill required fields (Title, Organization, and Category).';
    }
}

$pageTitle = 'Add New Job';
include 'includes/header.php';
?>
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-lg shadow-md p-6 md:p-8">
                <?php if ($success): ?>
                <div class="alert alert-success mb-6">
                    <i class="fas fa-check-circle mr-2"></i><?= $success ?>
                    <a href="jobs.php" class="ml-4 text-green-700 underline">View all jobs</a>
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
                            <label class="form-label">Job Title *</label>
                            <input type="text" name="title" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" 
                                   class="form-input" required>
                        </div>
                        
                        <div>
                            <label class="form-label">Organization *</label>
                            <input type="text" name="organization" value="<?= htmlspecialchars($_POST['organization'] ?? '') ?>" 
                                   class="form-input" required>
                        </div>
                        
                        <div>
                            <label class="form-label">Location</label>
                            <input type="text" name="location" value="<?= htmlspecialchars($_POST['location'] ?? '') ?>" 
                                   class="form-input">
                        </div>
                        
                        <div>
                            <label class="form-label">Apply Link</label>
                            <input type="url" name="apply_link" value="<?= htmlspecialchars($_POST['apply_link'] ?? '') ?>" 
                                   class="form-input">
                        </div>
                        
                        <div>
                            <label class="form-label">Last Date</label>
                            <input type="date" name="last_date" value="<?= $_POST['last_date'] ?? '' ?>" 
                                   class="form-input">
                        </div>
                        
                        <div>
                            <label class="form-label">Vacancy Count</label>
                            <input type="number" name="vacancy_count" value="<?= $_POST['vacancy_count'] ?? '' ?>" 
                                   class="form-input" min="0">
                        </div>
                        
                        <div>
                            <label class="form-label">Category *</label>
                            <select name="category_id" class="form-input" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" <?= ($_POST['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="form-label">Educational Qualification</label>
                            <textarea id="educational_qualification" name="educational_qualification" rows="3" class="form-input richtext"><?php echo htmlspecialchars($_POST['educational_qualification'] ?? '') ?></textarea>
                        </div>
                        
                        <div>
                            <label class="form-label">Age Limit</label>
                            <input type="text" name="age_limit" value="<?= htmlspecialchars($_POST['age_limit'] ?? '') ?>" 
                                   class="form-input" placeholder="e.g., 18-35 years">
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="form-label">Thumbnail URL</label>
                            <input type="url" id="thumbnail_url" name="thumbnail_url" value="<?= htmlspecialchars($_POST['thumbnail_url'] ?? '') ?>" class="form-input" placeholder="https://example.com/thumbnail.jpg">
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
                                <option value="published" <?= ($_POST['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>Published</option>
                                <option value="draft" <?= ($_POST['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="inline-flex items-center gap-2">
                                <input type="checkbox" name="send_push" <?= (($_POST['send_push'] ?? '1')==='1') ? 'checked' : 'checked' ?>>
                                <span>Send Push Notification (on Published)</span>
                            </label>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="form-label">Job Description</label>
                            <textarea id="content" name="content" rows="8" class="form-input richtext" placeholder="Enter detailed job description..."><?php echo htmlspecialchars($_POST['content'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <!-- Events Repeater -->
                    <div class="mt-8">
                      <h3 class="text-lg font-semibold mb-3">Events (Dates)</h3>
                      <div id="events_wrap" class="space-y-3">
                        <div class="grid grid-cols-6 gap-2 ev_row">
                          <select name="events[event_type][]" class="form-input">
                            <option value="application_start">Application Start</option>
                            <option value="application_end">Application End</option>
                            <option value="fee_payment_last">Fee Payment Last Date</option>
                            <option value="exam">Exam</option>
                            <option value="admit_card">Admit Card</option>
                            <option value="result">Result</option>
                            <option value="other">Other</option>
                          </select>
                          <input type="text" name="events[event_label][]" class="form-input" placeholder="Label (e.g., Prelims)">
                          <input type="date" name="events[start_date][]" class="form-input">
                          <input type="date" name="events[end_date][]" class="form-input">
                          <input type="text" name="events[notes][]" class="form-input" placeholder="Notes">
                          <div class="flex gap-2">
                            <input type="number" name="events[sort_order][]" class="form-input w-20" placeholder="#" value="0">
                            <button type="button" class="btn btn-error delete-row-btn" onclick="deleteRow(this, 'ev_row')">
                              <i class="fas fa-trash"></i>
                            </button>
                          </div>
                        </div>
                      </div>
                      <button type="button" class="btn btn-secondary mt-2" id="add_event">+ Add Event</button>
                    </div>

                    <!-- Important Links Repeater -->
                    <div class="mt-8">
                      <h3 class="text-lg font-semibold mb-3">Important Links</h3>
                      <div id="links_wrap" class="space-y-3">
                        <div class="grid grid-cols-4 gap-2 link_row">
                          <input type="text" name="links[label][]" class="form-input" list="link_labels_list" placeholder="Label (e.g. Apply Online)">
                          <input type="url" name="links[url][]" class="form-input col-span-2" placeholder="https://...">
                          <div class="flex gap-2">
                            <input type="number" name="links[sort_order][]" class="form-input w-20" placeholder="#" value="0">
                            <button type="button" class="btn btn-error delete-link-btn" onclick="deleteLink(this)">
                              <i class="fas fa-trash"></i>
                            </button>
                          </div>
                        </div>
                      </div>
                      <button type="button" class="btn btn-secondary mt-2" id="add_link">+ Add Link</button>
                      
                      <datalist id="link_labels_list">
                        <option value="Apply Online">
                        <option value="Admit Card Download">
                        <option value="Answer Key Download">
                        <option value="Result Download">
                        <option value="Download Notification">
                        <option value="Official Website">
                      </datalist>
                    </div>

                    <!-- Fees Section -->
                    <div class="md:col-span-2 mt-8">
                        <label class="form-label">Application Fees</label>
                        <textarea id="fees" name="fees" rows="5" class="form-input richtext" placeholder="Enter application fees details..."><?php echo htmlspecialchars($_POST['fees'] ?? '') ?></textarea>
                    </div>

                    <!-- Age Limit Block -->
                    <div class="mt-8 grid grid-cols-1 md:grid-cols-4 gap-2">
                      <div>
                        <label class="form-label">Min Age</label>
                        <input type="number" name="age_block[min_age]" class="form-input" min="0">
                      </div>
                      <div>
                        <label class="form-label">Max Age</label>
                        <input type="number" name="age_block[max_age]" class="form-input" min="0">
                      </div>
                      <div>
                        <label class="form-label">As on (Cutoff Date)</label>
                        <input type="date" name="age_block[cutoff_date]" class="form-input">
                      </div>
                      <div class="md:col-span-1 md:col-start-1 md:col-end-5">
                        <label class="form-label">Relaxation Notes</label>
                        <input type="text" name="age_block[relaxation_text]" class="form-input" placeholder="As per rules...">
                      </div>
                    </div>

                    <!-- Vacancies Section -->
                    <div class="mt-8">
                        <label class="form-label">Vacancy Details</label>
                        <textarea id="vacancy_details" name="vacancy_details" rows="5" class="form-input richtext" placeholder="Enter vacancy details..."><?php echo htmlspecialchars($_POST['vacancy_details'] ?? '') ?></textarea>
                    </div>

                    <!-- Sections: How to Apply, Mode of Exam -->
                    <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div>
                        <label class="form-label">How to Apply</label>
                        <textarea id="sections_how_to_apply" name="sections[how_to_apply]" rows="4" class="form-input richtext"></textarea>
                      </div>
                      <div>
                        <label class="form-label">Mode of Exam</label>
                        <textarea id="sections_mode_of_exam" name="sections[mode_of_exam]" rows="4" class="form-input richtext"></textarea>
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
                          <div class="inline-flex items-center gap-2">
                            <input type="hidden" name="faqs[is_active][]" value="1">
                            <input type="checkbox" class="faq_active_cb" checked> <span>Active</span>
                          </div>
                          <div></div>
                        </div>
                      </div>
                      <button type="button" class="btn btn-secondary mt-2" id="add_faq">+ Add FAQ</button>
                    </div>
                    
                    <div class="flex gap-4 mt-8">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i>Save Job
                        </button>
                        <a href="jobs.php" class="btn btn-secondary">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </a>
                    </div>
                </form>
                <script>


                // Generic row deletion function
                function deleteRow(button, rowClass) {
                    const row = button.closest('.' + rowClass);
                    const container = row.closest('.space-y-3');
                    if (container.querySelectorAll('.' + rowClass).length > 1) {
                        row.remove();
                    } else {
                        // If it's the last row, just clear values
                        row.querySelectorAll('input').forEach(i => i.value = '');
                    }
                }
                
                // Specific link deletion (alias to generic)
                function deleteLink(button) {
                    deleteRow(button, 'link_row');
                }

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
                  function cloneRow(wrapperId, rowClass){
                    const wrap = document.getElementById(wrapperId);
                    if (!wrap) return;
                    const first = wrap.querySelector('.' + rowClass);
                    if (!first) return;
                    const node = first.cloneNode(true);
                    // clear inputs
                    node.querySelectorAll('input').forEach(i=>{
                      if(i.type==='checkbox'){
                        i.checked = true;
                      } else if (i.type==='hidden' && i.name && i.name.indexOf('faqs[is_active]') === 0) {
                        i.value = '1';
                      } else {
                        i.value='';
                      }
                    });
                    node.querySelectorAll('select').forEach(s=>{ 
                        s.selectedIndex = 0;
                    });
                    // Preserve delete button handlers
                    node.querySelectorAll('button').forEach(b => {
                        if(b.classList.contains('delete-row-btn')) {
                            b.onclick = function() { deleteRow(this, rowClass); };
                        }
                        if(b.classList.contains('delete-link-btn')) {
                            b.onclick = function() { deleteLink(this); };
                        }
                    });
                    node.querySelectorAll('button').forEach(b => {
                        if(b.classList.contains('delete-row-btn')) {
                            b.onclick = function() { deleteRow(this, rowClass); };
                        }
                    });
                    wrap.appendChild(node);
                  }
                  document.getElementById('add_event')?.addEventListener('click', ()=> cloneRow('events_wrap','ev_row'));
                  document.getElementById('add_link')?.addEventListener('click', ()=> cloneRow('links_wrap','link_row'));
                  document.getElementById('add_fee')?.addEventListener('click', ()=> cloneRow('fees_wrap','fee_row'));
                  document.getElementById('add_vac')?.addEventListener('click', ()=> cloneRow('vacancies_wrap','vac_row'));
                  document.getElementById('add_faq')?.addEventListener('click', ()=> cloneRow('faqs_wrap','faq_row'));
                  // Sync FAQ checkbox to hidden value
                  document.addEventListener('change', function(e){
                    const cb = e.target;
                    if (cb && cb.classList && cb.classList.contains('faq_active_cb')){
                      const container = cb.closest('.inline-flex');
                      const hidden = container?.querySelector('input[type="hidden"][name^="faqs[is_active]"]');
                      if (hidden) hidden.value = cb.checked ? '1' : '0';
                    }
                  });
                  // Events inline validation before submit
                  const form = document.querySelector('form[data-validate]');
                  function clearRowError(row){
                    let err = row.querySelector('.ev_row_error');
                    if (err) err.remove();
                    row.classList.remove('ring-1','ring-red-400','bg-red-50');
                  }
                  function showRowError(row, msg){
                    clearRowError(row);
                    const span = document.createElement('div');
                    span.className = 'ev_row_error text-sm text-red-600 col-span-6';
                    span.textContent = msg;
                    // If using grid, append after row within wrapper
                    row.after(span);
                    row.classList.add('ring-1','ring-red-400','bg-red-50');
                  }
                  function isValidDateStr(s){
                    if (!s) return true; // empty handled elsewhere
                    const m = /^\d{4}-\d{2}-\d{2}$/.test(s);
                    if (!m) return false;
                    const d = new Date(s + 'T00:00:00');
                    // Check month/day overflow by comparing components
                    const [Y,M,D] = s.split('-').map(Number);
                    return d.getFullYear()===Y && (d.getMonth()+1)===M && d.getDate()===D;
                  }
                  form?.addEventListener('submit', function(e){
                    let hasError = false;
                    const wrap = document.getElementById('events_wrap');
                    wrap?.querySelectorAll('.ev_row').forEach(row => {
                      clearRowError(row);
                      const typeSel = row.querySelector('select[name="events[event_type][]"]');
                      const label = row.querySelector('input[name="events[event_label][]"]');
                      const sd = row.querySelector('input[name="events[start_date][]"]');
                      const ed = row.querySelector('input[name="events[end_date][]"]');
                      const notes = row.querySelector('input[name="events[notes][]"]');
                      const allEmpty = (!typeSel || !typeSel.value) && (!label || !label.value.trim()) && (!sd || !sd.value) && (!ed || !ed.value) && (!notes || !notes.value.trim());
                      if (allEmpty) {
                        // remove fully empty rows to avoid accidental default saves
                        row.remove();
                        return;
                      }
                      // Require at least start_date or end_date or label
                      if (((!sd || !sd.value)) && ((!ed || !ed.value)) && ((!label || !label.value.trim()))){
                        showRowError(row, 'Please provide at least a Start Date, End Date, or a Label for this event.');
                        hasError = true;
                        return;
                      }
                      // Validate date formats
                      if (sd && sd.value && !isValidDateStr(sd.value)){
                        showRowError(row, 'Start Date must be in YYYY-MM-DD format and be a valid date.');
                        hasError = true;
                        return;
                      }
                      if (ed && ed.value && !isValidDateStr(ed.value)){
                        showRowError(row, 'End Date must be in YYYY-MM-DD format and be a valid date.');
                        hasError = true;
                        return;
                      }
                    });
                    if (hasError) {
                      e.preventDefault();
                      const firstErr = document.querySelector('.ev_row_error');
                      firstErr?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                  });
                })();
                </script>
            </div>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>
