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

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    redirect('admit-cards.php');
}

$admitCard = $admitCardModel->getById($id);
if (!$admitCard) {
    redirect('admit-cards.php');
}
// Previous status for transition detection
$__prev_status = $admitCard['status'] ?? null;

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
    $published_at = $_POST['published_at'] ?? '';
    
    if ($title && $organization && $description) {
        $slugBase = slugify($title);
        $slug = generateUniqueSlug('admit_cards', $slugBase, $id);
        
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
            'status' => $status,
            'thumbnail_url' => $thumbnail_url ?: null,
            'author_id' => $author_id,
            'published_at' => $published_at ? date('Y-m-d H:i:s', strtotime($published_at)) : null
        ];
        
        try {
            $admitCardModel->update($id, $data);

            // Save flexible content blocks
            // Events
            $events = [];
            $et = $_POST['events']['event_type'] ?? [];
            $el = $_POST['events']['event_label'] ?? [];
            $sd = $_POST['events']['start_date'] ?? [];
            $ed = $_POST['events']['end_date'] ?? [];
            $nt = $_POST['events']['notes'] ?? [];
            $so = $_POST['events']['sort_order'] ?? [];
            for ($i=0; $i<count($et); $i++) {
                if (($et[$i] ?? '') === '' && ($sd[$i] ?? '') === '' && ($el[$i] ?? '') === '') continue;
                $events[] = [
                    'event_type' => $et[$i] ?: 'exam',
                    'event_label' => $el[$i] ?: null,
                    'start_date' => $sd[$i] ?: null,
                    'end_date' => $ed[$i] ?: null,
                    'notes' => $nt[$i] ?: null,
                    'sort_order' => isset($so[$i]) && $so[$i] !== '' ? (int)$so[$i] : 0,
                ];
            }
            $admitCardModel->saveEvents($id, $events);

            // Links
            $links = [];
            $ll = $_POST['links']['label'] ?? [];
            $lu = $_POST['links']['url'] ?? [];
            $ls = $_POST['links']['sort_order'] ?? [];
            for ($i=0; $i<count($ll); $i++) {
                if (($ll[$i] ?? '') === '' && ($lu[$i] ?? '') === '') continue;
                $links[] = [
                    'label' => $ll[$i] ?: '',
                    'url' => $lu[$i] ?: '',
                    'sort_order' => isset($ls[$i]) && $ls[$i] !== '' ? (int)$ls[$i] : 0,
                ];
            }
            $admitCardModel->saveLinks($id, $links);

            // Sections (Dynamic Repeater)
            $sections = [];
            $st = $_POST['sections']['section_type'] ?? [];
            $sti = $_POST['sections']['title'] ?? [];
            $sc = $_POST['sections']['content'] ?? [];
            $so = $_POST['sections']['sort_order'] ?? [];

            for ($i=0; $i<count($st); $i++) {
                if (empty($sc[$i])) continue;
                $sections[] = [
                    'section_type' => $st[$i] ?: 'other',
                    'title' => $sti[$i] ?: null,
                    'content' => $sc[$i] ?: '',
                    'sort_order' => isset($so[$i]) && $so[$i] !== '' ? (int)$so[$i] : 0
                ];
            }
            $admitCardModel->saveSections($id, $sections);

            // FAQs
            $faqq = $_POST['faqs']['question'] ?? [];
            $faqa = $_POST['faqs']['answer'] ?? [];
            $faqo = $_POST['faqs']['sort_order'] ?? [];
            $faqi = $_POST['faqs']['is_active'] ?? [];
            $faqs = [];
            for ($i=0; $i<count($faqq); $i++) {
                if (($faqq[$i] ?? '') === '' && ($faqa[$i] ?? '') === '') continue;
                $faqs[] = [
                    'question' => $faqq[$i] ?: '',
                    'answer' => $faqa[$i] ?: '',
                    'sort_order' => isset($faqo[$i]) && $faqo[$i] !== '' ? (int)$faqo[$i] : 0,
                    'is_active' => isset($faqi[$i]) ? 1 : 0,
                ];
            }
            $admitCardModel->saveFaqs($id, $faqs);

            // Refresh admit card data
            $admitCard = $admitCardModel->getById($id);
            // If transitioned to published and opted-in, send push
            if ($send_push && $__prev_status !== 'published' && $status === 'published') {
                $admitForNotif = [
                    'title' => $admitCard['title'] ?? $title,
                    'slug' => $admitCard['slug'] ?? '',
                    'organization' => $admitCard['organization'] ?? '',
                    'thumbnail_url' => $admitCard['thumbnail_url'] ?? null,
                ];
                try { onesignal_notify_admit($admitForNotif); } catch (Exception $e) { /* ignore */ }
            }
            // PRG: redirect to list to avoid duplicate submission on refresh
            redirect('admit-cards.php?updated=1');
            // Unreachable after redirect; kept for clarity
            $success = 'Admit card updated successfully!';
        } catch (Throwable $e) {
            $error = 'Update failed: ' . htmlspecialchars($e->getMessage());
        }
    } else {
        $error = 'Please fill all required fields';
    }
}

$events = $admitCardModel->getEvents($id);
$links = $admitCardModel->getLinks($id);
$sections = $admitCardModel->getSections($id);
$faqs = $admitCardModel->getFaqs($id);

$pageTitle = 'Edit Admit Card';
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
                            <label class="form-label">Admit Card Title *</label>
                            <input type="text" name="title" class="form-input" required 
                                   value="<?= htmlspecialchars($admitCard['title']) ?>"
                                   placeholder="e.g., SSC CGL 2023 Admit Card">
                        </div>
                        
                        <div>
                            <label class="form-label">Organization *</label>
                            <input type="text" name="organization" class="form-input" required 
                                   value="<?= htmlspecialchars($admitCard['organization']) ?>"
                                   placeholder="e.g., Staff Selection Commission">
                        </div>
                        
                        <div>
                            <label class="form-label">Exam Date</label>
                            <input type="text" name="exam_date" class="form-input" 
                                   value="<?= $admitCard['exam_date'] ?>"
                                   placeholder="e.g., 2025-10-15 or 10-15 October 2025">
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="form-label">Download URL</label>
                            <input type="url" id="download_url" name="download_url" class="form-input" 
                                   value="<?= htmlspecialchars($admitCard['download_url']) ?>"
                                   placeholder="https://example.com/admit-card.pdf">
                            <div class="mt-2 flex items-center gap-3">
                                <input type="file" id="dl_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="form-input p-1">
                                <button type="button" id="dl_upload_btn" class="btn btn-secondary"><i class="fas fa-upload mr-2"></i>Upload</button>
                                <span id="dl_status" class="text-sm text-gray-500"></span>
                            </div>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="form-label">Description *</label>
                            <textarea name="description" rows="6" class="form-input richtext" required><?= htmlspecialchars($admitCard['description']) ?></textarea>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="form-label">Instructions</label>
                            <textarea name="instructions" rows="4" class="form-input richtext"><?= htmlspecialchars($admitCard['instructions']) ?></textarea>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="form-label">Required Documents</label>
                            <textarea name="required_documents" rows="3" class="form-input richtext"><?= htmlspecialchars($admitCard['required_documents']) ?></textarea>
                        </div>
                        
                        <div>
                            <label class="form-label">Thumbnail URL</label>
                            <input type="url" id="thumbnail_url" name="thumbnail_url" class="form-input" 
                                   value="<?= htmlspecialchars($admitCard['thumbnail_url']) ?>"
                                   placeholder="https://example.com/admit-thumb.jpg">
                            <div class="mt-2 flex items-center gap-3">
                                <input type="file" id="thumb_file" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" class="form-input p-1">
                                <button type="button" id="thumb_upload_btn" class="btn btn-secondary"><i class="fas fa-upload mr-2"></i>Upload</button>
                                <span id="thumb_status" class="text-sm text-gray-500"></span>
                            </div>
                            <div id="thumb_preview_wrap" class="mt-2 <?= empty($admitCard['thumbnail_url']) ? 'hidden' : '' ?>">
                                <img id="thumb_preview" src="<?= htmlspecialchars($admitCard['thumbnail_url'] ?? '') ?>" alt="Thumbnail preview" class="h-24 rounded border object-cover">
                            </div>
                        </div>

                        <div>
                            <label class="form-label">Author</label>
                            <select name="author_id" class="form-input">
                                <option value="">Select Author</option>
                                <?php foreach ($authors as $a): ?>
                                <option value="<?= $a['id'] ?>" <?= (($admitCard['author_id'] ?? '') == $a['id']) ? 'selected' : '' ?>><?= htmlspecialchars($a['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Status</label>
                            <select name="status" class="form-input">
                                <option value="draft" <?= $admitCard['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="published" <?= $admitCard['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="form-label">Published Date</label>
                            <input type="datetime-local" name="published_at" class="form-input" 
                                   value="<?= !empty($admitCard['published_at']) ? date('Y-m-d\TH:i', strtotime($admitCard['published_at'])) : '' ?>">
                        </div>
                        <div class="md:col-span-2">
                            <label class="inline-flex items-center gap-2">
                                <input type="checkbox" name="send_push" <?= isset($_POST['send_push']) ? 'checked' : '' ?>>
                                <span>Send Push Notification (on Publish)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Events Repeater -->
                    <div class="mt-8">
                      <h3 class="text-lg font-semibold mb-3">Events (Dates)</h3>
                      <div id="events_wrap" class="space-y-3">
                        <?php if (!empty($events)): foreach ($events as $e): ?>
                        <div class="grid grid-cols-6 gap-2 ev_row">
                          <select name="events[event_type][]" class="form-input event-type-select" onchange="handleEventTypeChange(this)">
                            <?php $opts=['exam','admit_card','result','other','custom']; $ev=htmlspecialchars($e['event_type']); foreach($opts as $o): ?>
                            <option value="<?= $o ?>" <?= $ev===$o || ($o==='custom' && !in_array($ev, $opts))?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$o)) ?></option>
                            <?php endforeach; ?>
                          </select>
                          <input type="text" name="events[custom_type][]" class="form-input custom-event-input" placeholder="Enter custom type..." value="<?= !in_array($ev, array_slice($opts, 0, -1)) ? $ev : '' ?>" style="display: <?= !in_array($ev, array_slice($opts, 0, -1)) ? 'block' : 'none' ?>">
                          <input type="text" name="events[event_label][]" class="form-input" placeholder="Label" value="<?= htmlspecialchars($e['event_label'] ?? '') ?>">
                          <input type="date" name="events[start_date][]" class="form-input" value="<?= htmlspecialchars($e['start_date'] ?? '') ?>">
                          <input type="text" name="events[notes][]" class="form-input" placeholder="Notes" value="<?= htmlspecialchars($e['notes'] ?? '') ?>">
                          <div class="flex gap-2">
                            <input type="number" name="events[sort_order][]" class="form-input w-20" placeholder="#" value="<?= (int)($e['sort_order'] ?? 0) ?>">
                            <button type="button" class="btn btn-error delete-row-btn" onclick="deleteRow(this, 'ev_row')">
                              <i class="fas fa-trash"></i>
                            </button>
                          </div>
                        </div>
                        <?php endforeach; else: ?>
                        <div class="grid grid-cols-6 gap-2 ev_row">
                          <select name="events[event_type][]" class="form-input">
                            <option value="exam">Exam</option>
                            <option value="admit_card">Admit Card</option>
                            <option value="result">Result</option>
                            <option value="other">Other</option>
                          </select>
                          <input type="text" name="events[event_label][]" class="form-input" placeholder="Label (e.g., Phase I)">
                          <input type="date" name="events[start_date][]" class="form-input">
                          <input type="date" name="events[end_date][]" class="form-input">
                          <input type="text" name="events[notes][]" class="form-input" placeholder="Notes">
                          <input type="number" name="events[sort_order][]" class="form-input" placeholder="#" value="0">
                        </div>
                        <?php endif; ?>
                      </div>
                      <button type="button" class="btn btn-secondary mt-2" id="add_event">+ Add Event</button>
                    </div>

                    <!-- Important Links Repeater -->
                    <div class="mt-8">
                      <h3 class="text-lg font-semibold mb-3">Important Links</h3>
                      <div id="links_wrap" class="space-y-3">
                        <?php if (!empty($links)): foreach ($links as $l): ?>
                        <div class="grid grid-cols-4 gap-2 link_row">
                          <select name="links[label][]" class="form-input link-label-select" onchange="handleLinkLabelChange(this)">
                            <?php $labOpts=['Download Admit Card','Notification','Official Website','Other','Custom']; $sel=htmlspecialchars($l['label']); foreach($labOpts as $o): ?>
                            <option value="<?= $o ?>" <?= $sel===$o || ($o==='Custom' && !in_array($sel, $labOpts))?'selected':'' ?>><?= $o ?></option>
                            <?php endforeach; ?>
                          </select>
                          <input type="text" name="links[custom_label][]" class="form-input custom-label-input" placeholder="Enter custom label..." value="<?= !in_array($sel, array_slice($labOpts, 0, -1)) ? $sel : '' ?>" style="display: <?= !in_array($sel, array_slice($labOpts, 0, -1)) ? 'block' : 'none' ?>">
                          <input type="url" name="links[url][]" class="form-input" placeholder="https://..." value="<?= htmlspecialchars($l['url'] ?? '') ?>">
                          <div class="flex gap-2">
                            <input type="number" name="links[sort_order][]" class="form-input w-20" placeholder="#" value="<?= (int)($l['sort_order'] ?? 0) ?>">
                            <button type="button" class="btn btn-error delete-row-btn" onclick="deleteRow(this, 'link_row')">
                              <i class="fas fa-trash"></i>
                            </button>
                          </div>
                        </div>
                        <?php endforeach; else: ?>
                        <div class="grid grid-cols-3 gap-2 link_row">
                          <select name="links[label][]" class="form-input">
                            <option value="Download Admit Card">Download Admit Card</option>
                            <option value="Notification">Notification</option>
                            <option value="Official Website">Official Website</option>
                            <option value="Other">Other</option>
                          </select>
                          <input type="url" name="links[url][]" class="form-input" placeholder="https://...">
                          <input type="number" name="links[sort_order][]" class="form-input" placeholder="#" value="0">
                        </div>
                        <?php endif; ?>
                      </div>
                      <button type="button" class="btn btn-secondary mt-2" id="add_link">+ Add Link</button>
                    </div>

                    <!-- Sections Repeater -->
                    <div class="mt-8">
                      <h3 class="text-lg font-semibold mb-3">Sections</h3>
                      <div id="sections_wrapper" class="space-y-3">
                        <?php if (!empty($sections)): $i=0; foreach ($sections as $sc): ?>
                        <div class="grid grid-cols-12 gap-2 section_row">
                            <div class="col-span-12 md:col-span-3">
                                <select name="sections[section_type][]" class="form-input">
                                    <option value="how_to_download_admit_card" <?= ($sc['section_type'] ?? '') === 'how_to_download_admit_card' ? 'selected' : '' ?>>How to Download Admit Card</option>
                                    <option value="exam_instructions" <?= ($sc['section_type'] ?? '') === 'exam_instructions' ? 'selected' : '' ?>>Exam Instructions</option>
                                    <option value="important_notes" <?= ($sc['section_type'] ?? '') === 'important_notes' ? 'selected' : '' ?>>Important Notes</option>
                                    <option value="how_to_apply" <?= ($sc['section_type'] ?? '') === 'how_to_apply' ? 'selected' : '' ?>>How to Apply (Legacy)</option>
                                    <option value="mode_of_exam" <?= ($sc['section_type'] ?? '') === 'mode_of_exam' ? 'selected' : '' ?>>Mode of Exam (Legacy)</option>
                                    <option value="other" <?= ($sc['section_type'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                            <div class="col-span-12 md:col-span-8"><input type="text" name="sections[title][]" class="form-input" value="<?= htmlspecialchars($sc['title'] ?? '') ?>" placeholder="Section Title"></div>
                            <div class="col-span-6 md:col-span-1"><input type="number" name="sections[sort_order][]" class="form-input" value="<?= (int)($sc['sort_order'] ?? 0) ?>" placeholder="#"></div>
                            <div class="col-span-12"><textarea name="sections[content][]" id="section_content_<?= $i ?>" rows="4" class="form-input richtext" placeholder="Content (supports HTML)"><?= htmlspecialchars($sc['content'] ?? '') ?></textarea></div>
                            <div class="col-span-12 text-right">
                                <button type="button" class="btn btn-error delete-row-btn" onclick="deleteRow(this, 'section_row')"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                        <?php $i++; endforeach; else: ?>
                        <div class="grid grid-cols-12 gap-2 section_row">
                            <div class="col-span-12 md:col-span-3">
                                <select name="sections[section_type][]" class="form-input">
                                    <option value="how_to_download_admit_card" selected>How to Download Admit Card</option>
                                    <option value="exam_instructions">Exam Instructions</option>
                                    <option value="important_notes">Important Notes</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-span-12 md:col-span-8"><input type="text" name="sections[title][]" class="form-input" placeholder="Section Title"></div>
                            <div class="col-span-6 md:col-span-1"><input type="number" name="sections[sort_order][]" class="form-input" placeholder="#"></div>
                            <div class="col-span-12"><textarea name="sections[content][]" id="section_content_new" rows="4" class="form-input richtext" placeholder="Content (supports HTML)"></textarea></div>
                            <div class="col-span-12 text-right">
                                <button type="button" class="btn btn-error delete-row-btn" onclick="deleteRow(this, 'section_row')"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                        <?php endif; ?>
                      </div>
                      <button type="button" class="btn btn-secondary mt-2" onclick="cloneRow('sections_wrapper','section_row')"><i class="fas fa-plus mr-1"></i>Add Section</button>
                    </div>

                    <!-- FAQs Repeater -->
                    <div class="mt-8">
                      <h3 class="text-lg font-semibold mb-3">FAQs</h3>
                      <div id="faqs_wrap" class="space-y-3">
                        <?php if (!empty($faqs)): foreach ($faqs as $f): ?>
                        <div class="grid grid-cols-5 gap-2 faq_row">
                          <input type="text" name="faqs[question][]" class="form-input" placeholder="Question" value="<?= htmlspecialchars($f['question'] ?? '') ?>">
                          <input type="text" name="faqs[answer][]" class="form-input" placeholder="Answer" value="<?= htmlspecialchars($f['answer'] ?? '') ?>">
                          <div class="flex gap-2">
                            <input type="number" name="faqs[sort_order][]" class="form-input w-20" placeholder="#" value="<?= (int)($f['sort_order'] ?? 0) ?>">
                            <button type="button" class="btn btn-error delete-row-btn" onclick="deleteRow(this, 'faq_row')">
                              <i class="fas fa-trash"></i>
                            </button>
                          </div>
                          <label class="inline-flex items-center gap-2"><input type="checkbox" name="faqs[is_active][]" <?= ((int)($f['is_active'] ?? 1)) ? 'checked' : '' ?>> Active</label>
                        </div>
                        <?php endforeach; else: ?>
                        <div class="grid grid-cols-5 gap-2 faq_row">
                          <input type="text" name="faqs[question][]" class="form-input" placeholder="Question">
                          <input type="text" name="faqs[answer][]" class="form-input" placeholder="Answer">
                          <input type="number" name="faqs[sort_order][]" class="form-input" placeholder="#" value="0">
                          <label class="inline-flex items-center gap-2"><input type="checkbox" name="faqs[is_active][]" checked> Active</label>
                          <div></div>
                        </div>
                        <?php endif; ?>
                      </div>
                      <button type="button" class="btn btn-secondary mt-2" id="add_faq">+ Add FAQ</button>
                    </div>
                    
                    <div class="flex gap-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i>Update Admit Card
                        </button>
                        
                        <a href="admit-cards.php" class="btn btn-secondary">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </a>
                    </div>
                </form>
                <script>
                // Handle custom fields for all sections
                function handleCustomFieldChange(selectElement, customFieldClass) {
                    const customInput = selectElement.closest('.grid').querySelector('.' + customFieldClass);
                    if (selectElement.value.toLowerCase() === 'custom' || selectElement.value.toLowerCase() === 'other') {
                        customInput.style.display = 'block';
                        customInput.required = true;
                    } else {
                        customInput.style.display = 'none';
                        customInput.required = false;
                        customInput.value = '';
                    }
                }

                // Handle event type changes
                function handleEventTypeChange(selectElement) {
                    handleCustomFieldChange(selectElement, 'custom-event-input');
                }

                // Handle link label changes
                function handleLinkLabelChange(selectElement) {
                    handleCustomFieldChange(selectElement, 'custom-label-input');
                }

                // Generic row deletion function
                function deleteRow(button, rowClass) {
                    const row = button.closest('.' + rowClass);
                    const container = row.closest('.space-y-3');
                    if (container.querySelectorAll('.' + rowClass).length > 1) {
                        row.remove();
                    }
                }

                // Form submit handler for custom fields
                document.querySelector('form').addEventListener('submit', function(e) {
                    // Handle custom event types
                    document.querySelectorAll('.event-type-select').forEach(select => {
                        if (select.value.toLowerCase() === 'custom') {
                            const customInput = select.closest('.grid').querySelector('.custom-event-input');
                            if (customInput.value.trim()) {
                                select.value = customInput.value.trim();
                            }
                        }
                    });

                    // Handle custom link labels
                    document.querySelectorAll('.link-label-select').forEach(select => {
                        if (select.value === 'Custom') {
                            const customInput = select.closest('.grid').querySelector('.custom-label-input');
                            if (customInput.value.trim()) {
                                select.value = customInput.value.trim();
                            }
                        }
                    });
                });

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
                  function cloneRow(wrapperId, rowClass){
                    const wrap = document.getElementById(wrapperId);
                    if (!wrap) return;
                    const first = wrap.querySelector('.' + rowClass);
                    if (!first) return;
                    const node = first.cloneNode(true);
                    
                    // Unique suffix
                    const uniqueSuffix = Date.now() + '_' + Math.floor(Math.random() * 1000);

                    node.querySelectorAll('input').forEach(i=>{ 
                        if(i.type==='checkbox'){ 
                            i.checked=true; 
                        } else { 
                            i.value='';
                            if(i.classList.contains('custom-label-input') || i.classList.contains('custom-event-input')) {
                                i.style.display = 'none';
                                i.required = false;
                            }
                        }
                    });

                    // Handle Textareas & TinyMCE
                    node.querySelectorAll('textarea').forEach(t => {
                       t.value = '';
                       if (t.classList.contains('richtext')) {
                           t.removeAttribute('id');
                           t.id = 'editor_' + uniqueSuffix;
                           t.style.display = 'block';
                           t.style.visibility = 'visible';
                           // CKEditor cleanup
                           const parent = t.parentElement;
                           const ckEditor = parent.querySelector('.ck-editor');
                           if (ckEditor) ckEditor.remove();
                       }
                    });

                    node.querySelectorAll('select').forEach(s=>{ 
                        s.selectedIndex = 0;
                        if(s.classList.contains('link-label-select')) {
                            s.onchange = function() { handleLinkLabelChange(this); };
                        } else if(s.classList.contains('event-type-select')) {
                            s.onchange = function() { handleEventTypeChange(this); };
                        }
                    });
                    node.querySelectorAll('button').forEach(b => {
                        if(b.classList.contains('delete-row-btn')) {
                            b.onclick = function() { deleteRow(this, rowClass); };
                        }
                    });
                    wrap.appendChild(node);
                    
                    // Re-init CKEditor 5
                    node.querySelectorAll('textarea.richtext').forEach(t => {
                        ClassicEditor
                            .create(t, {
                                toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'insertTable', 'undo', 'redo'],
                                heading: {
                                    options: [
                                        { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                                        { model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
                                        { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                                        { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' }
                                    ]
                                }
                            })
                            .catch(error => console.error(error));
                    });
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
