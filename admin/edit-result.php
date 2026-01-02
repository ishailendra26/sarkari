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

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    redirect('results.php');
}

$result = $resultModel->getById($id);
if (!$result) {
    redirect('results.php');
}
// Remember previous status for notification
$__prev_status = $result['status'] ?? null;

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
    $published_at = $_POST['published_at'] ?? '';
    
    if ($title && $organization && $description) {
        $slugBase = slugify($title);
        $slug = generateUniqueSlug('results', $slugBase, $id);
        
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
            'published_at' => $published_at ? date('Y-m-d H:i:s', strtotime($published_at)) : null
        ];
        
        try {
            // Update base result
            $resultModel->update($id, $data);

            // Parse flexible content from POST
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
                            'is_active' => (int)($f['is_active'][$i] ?? 0),
                        ];
                    }
                }
            }

            // Save flexible content via model helpers
            $resultModel->saveEvents($id, $events);
            $resultModel->saveLinks($id, $links);
            $resultModel->saveSections($id, $sections);
            $resultModel->saveFaqs($id, $faqs);

            // Refresh result data
            $result = $resultModel->getById($id);
            // Notify if transitioned to published
            if ($send_push && $__prev_status !== 'published' && $status === 'published') {
                $resultForNotif = [
                    'title' => $result['title'] ?? $title,
                    'slug' => $result['slug'] ?? '',
                    'organization' => $result['organization'] ?? '',
                    'thumbnail_url' => $result['thumbnail_url'] ?? null,
                ];
                try { onesignal_notify_result($resultForNotif); } catch (Exception $e) { /* ignore */ }
            }
            // PRG: redirect to list
            redirect('results.php?updated=1');
            // Unreachable after redirect; kept for clarity
            $success = 'Result updated successfully!';
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            $friendly = '';
            // Detect duplicate slug/unique constraint violations (PDO MySQL: SQLSTATE[23000], code 1062)
            if (strpos($msg, 'SQLSTATE[23000]') !== false && (strpos($msg, '1062') !== false || stripos($msg, 'Duplicate entry') !== false)) {
                $friendly = 'Update failed: Another result already uses this title/slug. Please change the title to make a unique slug.';
            }
            if ($friendly) {
                $error = htmlspecialchars($friendly);
                error_log('edit-result duplicate slug: ' . $msg);
            } else {
                // Generic friendly error without leaking raw SQL
                $error = 'Update failed due to a server error. Please try again.';
                error_log('edit-result error: ' . $msg);
            }
        }
    } else {
        $error = 'Please fill all required fields';
    }
}

$pageTitle = 'Edit Result';
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
                            <label class="form-label">Result Title *</label>
                            <input type="text" name="title" class="form-input" required 
                                   value="<?= htmlspecialchars($result['title']) ?>"
                                   placeholder="e.g., SSC CGL 2023 Final Result">
                        </div>
                        
                        <div>
                            <label class="form-label">Organization *</label>
                            <input type="text" name="organization" class="form-input" required 
                                   value="<?= htmlspecialchars($result['organization']) ?>"
                                   placeholder="e.g., Staff Selection Commission">
                        </div>
                        
                        <div>
                            <label class="form-label">Result Date</label>
                            <input type="date" name="result_date" class="form-input" 
                                   value="<?= $result['result_date'] ?>">
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="form-label">Download URL</label>
                            <input type="url" id="download_url" name="download_url" class="form-input" 
                                   value="<?= htmlspecialchars($result['download_url']) ?>"
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
                                      placeholder="Enter detailed result information..."><?= htmlspecialchars($result['description']) ?></textarea>
                        </div>
                        
                        <!-- Events Repeater -->
                        <?php $existingEvents = $resultModel->getEvents($id); ?>
                        <div class="md:col-span-2">
                            <label class="form-label">Events</label>
                            <div id="events_wrapper" class="space-y-3">
                                <?php if ($existingEvents): foreach ($existingEvents as $ev): ?>
                                <div class="event_row grid grid-cols-12 gap-2">
                                    <div class="col-span-12 md:col-span-2">
                                        <select name="events[event_type][]" class="form-input event-type-select" onchange="handleEventTypeChange(this)">
                                            <?php $eventTypes=['result','other','custom']; $ev_type=$ev['event_type']; ?>
                                            <?php foreach($eventTypes as $type): ?>
                                            <option value="<?= $type ?>" <?= ($ev_type===$type || ($type==='custom' && !in_array($ev_type, $eventTypes)))?'selected':'' ?>><?= ucfirst($type) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-span-12 md:col-span-2">
                                        <input type="text" name="events[custom_type][]" class="form-input custom-event-input" placeholder="Custom type..." 
                                               value="<?= !in_array($ev_type, array_slice($eventTypes, 0, -1)) ? $ev_type : '' ?>" 
                                               style="display: <?= !in_array($ev_type, array_slice($eventTypes, 0, -1)) ? 'block' : 'none' ?>">
                                    </div>
                                    <div class="col-span-12 md:col-span-2">
                                        <input type="text" name="events[event_label][]" class="form-input" value="<?= htmlspecialchars($ev['event_label'] ?? '') ?>" placeholder="Label">
                                    </div>
                                    <div class="col-span-6 md:col-span-2">
                                        <input type="date" name="events[start_date][]" class="form-input" value="<?= htmlspecialchars($ev['start_date'] ?? '') ?>">
                                    </div>
                                    <div class="col-span-12 md:col-span-2">
                                        <input type="text" name="events[notes][]" class="form-input" value="<?= htmlspecialchars($ev['notes'] ?? '') ?>" placeholder="Notes">
                                    </div>
                                    <div class="col-span-12 md:col-span-2 flex gap-2">
                                        <input type="number" name="events[sort_order][]" class="form-input w-20" value="<?= (int)($ev['sort_order'] ?? 0) ?>" placeholder="#">
                                        <button type="button" class="btn btn-error delete-row-btn" onclick="deleteRow(this, 'event_row')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; else: ?>
                                <div class="event_row grid grid-cols-12 gap-2">
                                    <div class="col-span-12 md:col-span-2">
                                        <select name="events[event_type][]" class="form-input">
                                            <option value="result" selected>Result</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    <div class="col-span-12 md:col-span-3"><input type="text" name="events[event_label][]" class="form-input" placeholder="Label"></div>
                                    <div class="col-span-6 md:col-span-2"><input type="date" name="events[start_date][]" class="form-input"></div>
                                    <div class="col-span-6 md:col-span-2"><input type="date" name="events[end_date][]" class="form-input"></div>
                                    <div class="col-span-12 md:col-span-2"><input type="text" name="events[notes][]" class="form-input" placeholder="Notes"></div>
                                    <div class="col-span-6 md:col-span-1"><input type="number" name="events[sort_order][]" class="form-input" placeholder="#"></div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="btn btn-secondary mt-2" onclick="cloneRow('events_wrapper','event_row')"><i class="fas fa-plus mr-1"></i>Add Event</button>
                        </div>

                        <!-- Important Links Repeater -->
                        <?php $existingLinks = $resultModel->getLinks($id); ?>
                        <div class="md:col-span-2">
                            <label class="form-label">Important Links</label>
                            <div id="links_wrapper" class="space-y-3">
                                <?php if ($existingLinks): foreach ($existingLinks as $ln): ?>
                                <div class="link_row grid grid-cols-12 gap-2">
                                    <div class="col-span-12 md:col-span-3">
                                        <select name="links[label][]" class="form-input link-label-select" onchange="handleLinkLabelChange(this)">
                                            <?php $linkTypes=['Check Result','Download Result','Official Website','Other','Custom']; $lnk_type=htmlspecialchars($ln['label']); ?>
                                            <?php foreach($linkTypes as $type): ?>
                                            <option value="<?= $type ?>" <?= ($lnk_type===$type || ($type==='Custom' && !in_array($lnk_type, $linkTypes)))?'selected':'' ?>><?= $type ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-span-12 md:col-span-2">
                                        <input type="text" name="links[custom_label][]" class="form-input custom-label-input" placeholder="Custom label..." 
                                               value="<?= !in_array($lnk_type, array_slice($linkTypes, 0, -1)) ? $lnk_type : '' ?>" 
                                               style="display: <?= !in_array($lnk_type, array_slice($linkTypes, 0, -1)) ? 'block' : 'none' ?>">
                                    </div>
                                    <div class="col-span-12 md:col-span-5">
                                        <input type="url" name="links[url][]" class="form-input" value="<?= htmlspecialchars($ln['url']) ?>" placeholder="https://...">
                                    </div>
                                    <div class="col-span-12 md:col-span-2 flex gap-2">
                                        <input type="number" name="links[sort_order][]" class="form-input w-20" value="<?= (int)($ln['sort_order'] ?? 0) ?>" placeholder="#">
                                        <button type="button" class="btn btn-error delete-row-btn" onclick="deleteRow(this, 'link_row')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; else: ?>
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
                                <?php endif; ?>
                            </div>
                            <button type="button" class="btn btn-secondary mt-2" onclick="cloneRow('links_wrapper','link_row')"><i class="fas fa-plus mr-1"></i>Add Link</button>
                        </div>

                        <!-- Sections Repeater -->
                        <?php $existingSections = $resultModel->getSections($id); ?>
                        <div class="md:col-span-2">
                            <label class="form-label">Sections</label>
                            <div id="sections_wrapper" class="space-y-3">
                                <?php if ($existingSections): $i=0; foreach ($existingSections as $sc): ?>
                                <div class="section_row grid grid-cols-12 gap-2">
                                    <div class="col-span-12 md:col-span-3">
                                        <select name="sections[section_type][]" class="form-input">
                                            <option value="how_to_check" <?= ($sc['section_type']==='how_to_check')?'selected':''; ?>>How to Check Result</option>
                                            <option value="notes" <?= ($sc['section_type']==='notes')?'selected':''; ?>>Important Notes</option>
                                            <option value="other" <?= ($sc['section_type']==='other')?'selected':''; ?>>Other</option>
                                        </select>
                                    </div>
                                    <div class="col-span-12 md:col-span-8"><input type="text" name="sections[title][]" class="form-input" value="<?= htmlspecialchars($sc['title'] ?? '') ?>" placeholder="Section Title"></div>
                                    <div class="col-span-6 md:col-span-1"><input type="number" name="sections[sort_order][]" class="form-input" value="<?= (int)($sc['sort_order'] ?? 0) ?>" placeholder="#"></div>
                                    <div class="col-span-12"><textarea name="sections[content][]" id="section_content_<?= $i ?>" rows="4" class="form-input richtext" placeholder="Content (supports HTML)"><?= htmlspecialchars($sc['content'] ?? '') ?></textarea></div>
                                </div>
                                <?php $i++; endforeach; else: $i=0; ?>
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
                                <?php endif; ?>
                            </div>
                            <button type="button" class="btn btn-secondary mt-2" onclick="cloneRow('sections_wrapper','section_row')"><i class="fas fa-plus mr-1"></i>Add Section</button>
                        </div>

                        <!-- FAQs Repeater -->
                        <?php $existingFaqs = $resultModel->getFaqs($id); ?>
                        <div class="md:col-span-2">
                            <label class="form-label">FAQs</label>
                            <div id="faqs_wrapper" class="space-y-3">
                                <?php if ($existingFaqs): foreach ($existingFaqs as $fq): ?>
                                <div class="faq_row grid grid-cols-12 gap-2">
                                    <div class="col-span-12 md:col-span-7"><input type="text" name="faqs[question][]" class="form-input" value="<?= htmlspecialchars($fq['question']) ?>" placeholder="Question"></div>
                                    <div class="col-span-8 md:col-span-3"><input type="number" name="faqs[sort_order][]" class="form-input" value="<?= (int)($fq['sort_order'] ?? 0) ?>" placeholder="#"></div>
                                    <div class="col-span-4 md:col-span-2 inline-flex items-center gap-2">
                                        <input type="hidden" name="faqs[is_active][]" value="<?= ((int)($fq['is_active'] ?? 1)) ? '1' : '0' ?>">
                                        <input type="checkbox" class="faq_active_cb" <?= ((int)($fq['is_active'] ?? 1)) ? 'checked' : '' ?>> <span>Active</span>
                                    </div>
                                    <div class="col-span-12"><textarea name="faqs[answer][]" rows="3" class="form-input" placeholder="Answer"><?= htmlspecialchars($fq['answer']) ?></textarea></div>
                                </div>
                                <?php endforeach; else: ?>
                                <div class="faq_row grid grid-cols-12 gap-2">
                                    <div class="col-span-12 md:col-span-7"><input type="text" name="faqs[question][]" class="form-input" placeholder="Question"></div>
                                    <div class="col-span-8 md:col-span-3"><input type="number" name="faqs[sort_order][]" class="form-input" placeholder="#"></div>
                                    <div class="col-span-4 md:col-span-2 inline-flex items-center gap-2">
                                        <input type="hidden" name="faqs[is_active][]" value="1">
                                        <input type="checkbox" class="faq_active_cb" checked> <span>Active</span>
                                    </div>
                                    <div class="col-span-12"><textarea name="faqs[answer][]" rows="3" class="form-input" placeholder="Answer"></textarea></div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="btn btn-secondary mt-2" onclick="cloneRow('faqs_wrapper','faq_row')"><i class="fas fa-plus mr-1"></i>Add FAQ</button>
                        </div>
                        
                        <div>
                            <label class="form-label">Thumbnail URL</label>
                            <input type="url" id="thumbnail_url" name="thumbnail_url" class="form-input" 
                                   value="<?= htmlspecialchars($result['thumbnail_url']) ?>"
                                   placeholder="https://example.com/result-thumb.jpg">
                            <div class="mt-2 flex items-center gap-3">
                                <input type="file" id="thumb_file" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" class="form-input p-1">
                                <button type="button" id="thumb_upload_btn" class="btn btn-secondary"><i class="fas fa-upload mr-2"></i>Upload</button>
                                <span id="thumb_status" class="text-sm text-gray-500"></span>
                            </div>
                            <div id="thumb_preview_wrap" class="mt-2 <?= empty($result['thumbnail_url']) ? 'hidden' : '' ?>">
                                <img id="thumb_preview" src="<?= htmlspecialchars($result['thumbnail_url'] ?? '') ?>" alt="Thumbnail preview" class="h-24 rounded border object-cover">
                            </div>
                        </div>

                        <div>
                            <label class="form-label">Author</label>
                            <select name="author_id" class="form-input">
                                <option value="">Select Author</option>
                                <?php foreach ($authors as $a): ?>
                                <option value="<?= $a['id'] ?>" <?= (($result['author_id'] ?? '') == $a['id']) ? 'selected' : '' ?>><?= htmlspecialchars($a['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Status</label>
                            <select name="status" class="form-input">
                                <option value="draft" <?= $result['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="published" <?= $result['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="form-label">Published Date</label>
                            <input type="datetime-local" name="published_at" class="form-input" 
                                   value="<?= !empty($result['published_at']) ? date('Y-m-d\TH:i', strtotime($result['published_at'])) : '' ?>">
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
                            <i class="fas fa-save mr-2"></i>Update Result
                        </button>
                        
                        <a href="results.php" class="btn btn-secondary">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </a>
                    </div>
                </form>
                <script>
                function cloneRow(wrapperId, rowClass) {
                    const wrap = document.getElementById(wrapperId);
                    if (!wrap) return;
                    const row = wrap.querySelector('.' + rowClass);
                    if (!row) return;
                    const clone = row.cloneNode(true);
                    
                    // Generate unique suffix
                    const uniqueSuffix = Date.now() + '_' + Math.floor(Math.random() * 1000);

                    clone.querySelectorAll('input').forEach(i=>{
                        if (i.type==='checkbox' || i.type==='radio'){
                            i.checked = false;
                        } else if (i.type==='hidden' && i.name && i.name.indexOf('faqs[is_active]') === 0) {
                            i.value = '1';
                        } else {
                            i.value='';
                        }
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
                            // CKEditor cleanup
                            const parent = t.parentElement;
                            const ckEditor = parent.querySelector('.ck-editor');
                            if (ckEditor) ckEditor.remove();
                        }
                    });

                    // If this is an FAQ row, set checkbox to checked by default
                    if (rowClass === 'faq_row') {
                        const cb = clone.querySelector('.faq_active_cb');
                        const hidden = clone.querySelector('input[type="hidden"][name^="faqs[is_active]"]');
                        if (cb) cb.checked = true;
                        if (hidden) hidden.value = '1';
                    }
                    
                    wrap.appendChild(clone);

                    // Re-init CKEditor 5
                    clone.querySelectorAll('textarea.richtext').forEach(t => {
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
                document.addEventListener('change', function(e){
                  const cb = e.target;
                  if (cb && cb.classList && cb.classList.contains('faq_active_cb')){
                    const container = cb.closest('.inline-flex');
                    const hidden = container?.querySelector('input[type="hidden"][name^="faqs[is_active]"]');
                    if (hidden) hidden.value = cb.checked ? '1' : '0';
                  }
                });
                </script>
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
                    function handleLinkLabelChange(select) {
                        const row = select.closest('.link_row');
                        const customInput = row.querySelector('.custom-label-input');
                        if (customInput) {
                            customInput.style.display = select.value === 'Custom' ? 'block' : 'none';
                            if (select.value !== 'Custom') {
                                customInput.value = '';
                            }
                        }
                    }
                    function deleteRow(btn, rowClass) {
                        const row = btn.closest('.' + rowClass);
                        if (row && confirm('Are you sure you want to delete this item?')) {
                            row.remove();
                        }
                    }
                </script>
            </div>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>
