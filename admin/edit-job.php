<?php
require_once '../src/config.php';
require_once '../src/db.php';
require_once '../src/helpers.php';
require_once '../src/models/Job.php';
require_once '../src/models/Author.php';
require_once '../src/models/Category.php';
require_once '../src/notifications.php';

requireLogin();

$jobModel = new Job();
$authorModel = new Author();
$categoryModel = new Category();
$success = '';
$error = '';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    redirect('jobs.php');
}

$job = $jobModel->getById($id);
if (!$job) {
    redirect('jobs.php');
}
// Previous status for notification logic
$__prev_status = $job['status'] ?? null;

// Load existing flexible content for prefill
$events = $jobModel->getEvents($id);
$links = $jobModel->getLinks($id);
$fees = $jobModel->getFees($id);
$vacancies = $jobModel->getVacancies($id);
$sections = $jobModel->getSections($id);
$faqs = $jobModel->getFaqs($id);
$age = $jobModel->getAgeLimit($id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $organization = sanitizeInput($_POST['organization'] ?? '');
    $location = sanitizeInput($_POST['location'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $educational_qualification = $_POST['educational_qualification'] ?? '';
    $age_limit = sanitizeInput($_POST['age_limit'] ?? '');
    $last_date = sanitizeInput($_POST['last_date'] ?? '');
    $apply_link = sanitizeInput($_POST['apply_link'] ?? '');
    $vacancy_count = (int)($_POST['vacancy_count'] ?? 0);
    $content = $_POST['content'] ?? '';
    $status = sanitizeInput($_POST['status'] ?? 'draft');
    $send_push = isset($_POST['send_push']);
    $thumbnail_url = sanitizeInput($_POST['thumbnail_url'] ?? '');
    $author_id = isset($_POST['author_id']) && $_POST['author_id'] !== '' ? (int)$_POST['author_id'] : null;
    
    if ($title && $organization && $category_id) {
        $slugBase = slugify($title);
        $slug = generateUniqueSlug('jobs', $slugBase, $id);
        
        $data = [
            'title' => $title,
            'slug' => $slug,
            'organization' => $organization,
            'location' => $location,
            'category_id' => $category_id,
            'educational_qualification' => $educational_qualification,
            'age_limit' => $age_limit,
            'last_date' => $last_date ?: null,
            'apply_link' => $apply_link,
            'vacancy_count' => $vacancy_count,
            'content' => $content,
            'status' => $status,
            'thumbnail_url' => $thumbnail_url ?: null,
            'author_id' => $author_id
        ];
        
        try {
            $jobModel->update($id, $data);

            // Save flexible content blocks
            // Events
            $events = [];
            if (isset($_POST['events']) && is_array($_POST['events'])) {
                $eventCount = count($_POST['events']['event_type'] ?? []);
                for ($i = 0; $i < $eventCount; $i++) {
                    // Skip if all fields are empty
                    if (empty(trim($_POST['events']['event_type'][$i] ?? '')) && 
                        empty(trim($_POST['events']['event_label'][$i] ?? '')) && 
                        empty(trim($_POST['events']['start_date'][$i] ?? '')) && 
                        empty(trim($_POST['events']['end_date'][$i] ?? '')) && 
                        empty(trim($_POST['events']['notes'][$i] ?? ''))) {
                        continue;
                    }
                    
                    $events[] = [
                        'event_type' => $_POST['events']['event_type'][$i] ?? 'application_start',
                        'event_label' => !empty($_POST['events']['event_label'][$i]) ? $_POST['events']['event_label'][$i] : null,
                        'start_date' => !empty($_POST['events']['start_date'][$i]) ? $_POST['events']['start_date'][$i] : null,
                        'end_date' => !empty($_POST['events']['end_date'][$i]) ? $_POST['events']['end_date'][$i] : null,
                        'notes' => !empty($_POST['events']['notes'][$i]) ? $_POST['events']['notes'][$i] : null,
                        'sort_order' => isset($_POST['events']['sort_order'][$i]) && $_POST['events']['sort_order'][$i] !== '' ? (int)$_POST['events']['sort_order'][$i] : 0,
                    ];
                }
            }
            $jobModel->saveEvents($id, $events);

            // Links
            $links = [];
            if (isset($_POST['links']) && is_array($_POST['links'])) {
                $linkCount = count($_POST['links']['label'] ?? []);
                for ($i = 0; $i < $linkCount; $i++) {
                    // Skip if both label and URL are empty
                    if (empty(trim($_POST['links']['label'][$i] ?? '')) && 
                        empty(trim($_POST['links']['url'][$i] ?? ''))) {
                        continue;
                    }
                    
                    $links[] = [
                        'label' => $_POST['links']['label'][$i] ?? '',
                        'url' => $_POST['links']['url'][$i] ?? '',
                        'sort_order' => isset($_POST['links']['sort_order'][$i]) && $_POST['links']['sort_order'][$i] !== '' ? (int)$_POST['links']['sort_order'][$i] : 0,
                    ];
                }
            }
            $jobModel->saveLinks($id, $links);

            // Fees
            $fees = [];
            if (isset($_POST['fees']) && is_array($_POST['fees'])) {
                $feeCount = count($_POST['fees']['category'] ?? []);
                for ($i = 0; $i < $feeCount; $i++) {
                    // Skip if category is empty
                    if (empty(trim($_POST['fees']['category'][$i] ?? ''))) {
                        continue;
                    }
                    
                    $fees[] = [
                        'category' => $_POST['fees']['category'][$i] ?? '',
                        'amount' => isset($_POST['fees']['amount'][$i]) && $_POST['fees']['amount'][$i] !== '' ? $_POST['fees']['amount'][$i] : null,
                        'text' => !empty($_POST['fees']['text'][$i]) ? $_POST['fees']['text'][$i] : null,
                        'mode_notes' => !empty($_POST['fees']['mode_notes'][$i]) ? $_POST['fees']['mode_notes'][$i] : null,
                        'sort_order' => isset($_POST['fees']['sort_order'][$i]) && $_POST['fees']['sort_order'][$i] !== '' ? (int)$_POST['fees']['sort_order'][$i] : 0,
                    ];
                }
            }
            $jobModel->saveFees($id, $fees);

            // Vacancies
            $vac = [];
            if (isset($_POST['vacancies']) && is_array($_POST['vacancies'])) {
                $vacCount = count($_POST['vacancies']['post_name'] ?? []);
                for ($i = 0; $i < $vacCount; $i++) {
                    // Skip if post_name is empty
                    if (empty(trim($_POST['vacancies']['post_name'][$i] ?? ''))) {
                        continue;
                    }
                    
                    $vac[] = [
                        'post_name' => $_POST['vacancies']['post_name'][$i],
                        'category' => !empty($_POST['vacancies']['category'][$i]) ? $_POST['vacancies']['category'][$i] : null,
                        'total_posts' => isset($_POST['vacancies']['total_posts'][$i]) && $_POST['vacancies']['total_posts'][$i] !== '' ? (int)$_POST['vacancies']['total_posts'][$i] : null,
                        'eligibility_text' => !empty($_POST['vacancies']['eligibility_text'][$i]) ? $_POST['vacancies']['eligibility_text'][$i] : null,
                        'pay_scale' => !empty($_POST['vacancies']['pay_scale'][$i]) ? $_POST['vacancies']['pay_scale'][$i] : null,
                        'sort_order' => isset($_POST['vacancies']['sort_order'][$i]) && $_POST['vacancies']['sort_order'][$i] !== '' ? (int)$_POST['vacancies']['sort_order'][$i] : 0,
                    ];
                }
            }
            $jobModel->saveVacancies($id, $vac);

            // Sections (how_to_apply, mode_of_exam)
            $sections = [];
            if (!empty($_POST['sections']['how_to_apply'])) {
                $sections[] = [
                    'section_type' => 'how_to_apply', 
                    'title' => 'How to Apply', 
                    'content' => $_POST['sections']['how_to_apply'], 
                    'sort_order' => 0
                ];
            }
            if (!empty($_POST['sections']['mode_of_exam'])) {
                $sections[] = [
                    'section_type' => 'mode_of_exam', 
                    'title' => 'Mode of Exam', 
                    'content' => $_POST['sections']['mode_of_exam'], 
                    'sort_order' => 1
                ];
            }
            $jobModel->saveSections($id, $sections);

            // FAQs
            $faqs = [];
            if (isset($_POST['faqs']) && is_array($_POST['faqs'])) {
                $faqCount = count($_POST['faqs']['question'] ?? []);
                for ($i = 0; $i < $faqCount; $i++) {
                    // Skip if both question and answer are empty
                    if (empty(trim($_POST['faqs']['question'][$i] ?? '')) && 
                        empty(trim($_POST['faqs']['answer'][$i] ?? ''))) {
                        continue;
                    }
                    
                    $faqs[] = [
                        'question' => $_POST['faqs']['question'][$i] ?? '',
                        'answer' => $_POST['faqs']['answer'][$i] ?? '',
                        'sort_order' => isset($_POST['faqs']['sort_order'][$i]) && $_POST['faqs']['sort_order'][$i] !== '' ? (int)$_POST['faqs']['sort_order'][$i] : 0,
                        'is_active' => isset($_POST['faqs']['is_active'][$i]) ? 1 : 0,
                    ];
                }
            }
            $jobModel->saveFaqs($id, $faqs);

            // Age Limit (single row)
            if (isset($_POST['age_block'])) {
                $ageData = [
                    'min_age' => isset($_POST['age_block']['min_age']) && $_POST['age_block']['min_age'] !== '' ? (int)$_POST['age_block']['min_age'] : null,
                    'max_age' => isset($_POST['age_block']['max_age']) && $_POST['age_block']['max_age'] !== '' ? (int)$_POST['age_block']['max_age'] : null,
                    'cutoff_date' => !empty($_POST['age_block']['cutoff_date']) ? $_POST['age_block']['cutoff_date'] : null,
                    'relaxation_text' => !empty($_POST['age_block']['relaxation_text']) ? $_POST['age_block']['relaxation_text'] : null,
                ];
                $jobModel->saveAgeLimit($id, $ageData);
            }

            // Send notification if transitioned from draft to published
            // Refresh job data first to have latest slug, etc.
            $job = $jobModel->getById($id);
            if ($send_push && $__prev_status !== 'published' && $status === 'published') {
                $jobForNotif = [
                    'title' => $job['title'] ?? '',
                    'slug' => $job['slug'] ?? '',
                    'organization' => $job['organization'] ?? '',
                    'thumbnail_url' => $job['thumbnail_url'] ?? null,
                ];
                try { onesignal_notify_job($jobForNotif); } catch (Exception $e) { /* ignore */ }
            }
            // PRG: redirect to list to prevent duplicate submissions
            redirect('jobs.php?updated=1');
            // Unreachable after redirect, but keep refresh logic if redirect removed in future
            $success = 'Job updated successfully!';
            $events = $jobModel->getEvents($id);
            $links = $jobModel->getLinks($id);
            $fees = $jobModel->getFees($id);
            $vacancies = $jobModel->getVacancies($id);
            $sections = $jobModel->getSections($id);
            $faqs = $jobModel->getFaqs($id);
            $age = $jobModel->getAgeLimit($id);
        } catch (Throwable $e) {
            $error = 'Update failed: ' . htmlspecialchars($e->getMessage());
        }
    } else {
        $error = 'Please fill all required fields';
    }
}

$categories = $categoryModel->getAll();
$authors = $authorModel->getAll(1000, 0);

$pageTitle = 'Edit Job';
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
                            <label class="form-label">Job Title *</label>
                            <input type="text" name="title" class="form-input" required 
                                   value="<?= htmlspecialchars($job['title']) ?>"
                                   placeholder="e.g., Staff Selection Commission Combined Graduate Level Examination">
                        </div>
                        
                        <div>
                            <label class="form-label">Organization *</label>
                            <input type="text" name="organization" class="form-input" required 
                                   value="<?= htmlspecialchars($job['organization']) ?>"
                                   placeholder="e.g., Staff Selection Commission">
                        </div>
                        
                        <div>
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-input" 
                                   value="<?= htmlspecialchars($job['location']) ?>"
                                   placeholder="e.g., All India">
                        </div>
                        
                        <div>
                            <label class="form-label">Category *</label>
                            <select name="category_id" class="form-input" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" <?= $job['category_id'] == $category['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="form-label">Author</label>
                            <select name="author_id" class="form-input">
                                <option value="">Select Author</option>
                                <?php foreach ($authors as $author): ?>
                                <option value="<?= $author['id'] ?>" <?= $job['author_id'] == $author['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($author['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="form-label">Educational Qualification</label>
                            <textarea id="educational_qualification" name="educational_qualification" rows="3" class="form-input richtext" 
                                      placeholder="e.g., Graduate in any discipline"><?= htmlspecialchars($job['educational_qualification']) ?></textarea>
                        </div>
                        
                        <div>
                            <label class="form-label">Age Limit</label>
                            <input type="text" name="age_limit" class="form-input" 
                                   value="<?= htmlspecialchars($job['age_limit']) ?>"
                                   placeholder="e.g., 18-32 years">
                        </div>
                        
                        <div>
                            <label class="form-label">Vacancy Count</label>
                            <input type="number" name="vacancy_count" class="form-input" 
                                   value="<?= htmlspecialchars($job['vacancy_count']) ?>" min="0">
                        </div>
                        
                        <div>
                            <label class="form-label">Last Date to Apply</label>
                            <input type="date" name="last_date" class="form-input" 
                                   value="<?= $job['last_date'] ?>">
                        </div>
                        
                        <div>
                            <label class="form-label">Apply Link</label>
                            <input type="url" name="apply_link" class="form-input" 
                                   value="<?= htmlspecialchars($job['apply_link']) ?>"
                                   placeholder="https://example.com/apply">
                        </div>
                        
                        <div>
                            <label class="form-label">Thumbnail URL</label>
                            <input type="url" id="thumbnail_url" name="thumbnail_url" class="form-input" 
                                   value="<?= htmlspecialchars($job['thumbnail_url']) ?>"
                                   placeholder="https://example.com/thumbnail.jpg">
                            <div class="mt-2 flex items-center gap-3">
                                <input type="file" id="thumb_file" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" class="form-input p-1">
                                <button type="button" id="thumb_upload_btn" class="btn btn-secondary"><i class="fas fa-upload mr-2"></i>Upload</button>
                                <span id="thumb_status" class="text-sm text-gray-500"></span>
                            </div>
                            <div id="thumb_preview_wrap" class="mt-2 <?= empty($job['thumbnail_url']) ? 'hidden' : '' ?>">
                                <img id="thumb_preview" src="<?= htmlspecialchars($job['thumbnail_url'] ?? '') ?>" alt="Thumbnail preview" class="h-24 rounded border object-cover">
                            </div>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="form-label">Job Description</label>
                            <textarea id="content" name="content" rows="10" class="form-input richtext" 
                                      placeholder="Enter detailed job description..."><?= htmlspecialchars($job['content']) ?></textarea>
                        </div>
                        
                        <div>
                            <label class="form-label">Status</label>
                            <select name="status" class="form-input">
                                <option value="published" <?= ($job['status'] === 'published') ? 'selected' : '' ?>>Published</option>
                                <option value="draft" <?= ($job['status'] === 'draft') ? 'selected' : '' ?>>Draft</option>
                            </select>
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
                          <select name="events[event_type][]" class="form-input">
                            <?php $opts=['application_start','application_end','fee_payment_last','exam','admit_card','result','other']; $ev=htmlspecialchars($e['event_type']); foreach($opts as $o): ?>
                            <option value="<?= $o ?>" <?= $ev===$o?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$o)) ?></option>
                            <?php endforeach; ?>
                          </select>
                          <input type="text" name="events[event_label][]" class="form-input" placeholder="Label" value="<?= htmlspecialchars($e['event_label'] ?? '') ?>">
                          <input type="date" name="events[start_date][]" class="form-input" value="<?= htmlspecialchars($e['start_date'] ?? '') ?>">
                          <input type="date" name="events[end_date][]" class="form-input" value="<?= htmlspecialchars($e['end_date'] ?? '') ?>">
                          <input type="text" name="events[notes][]" class="form-input" placeholder="Notes" value="<?= htmlspecialchars($e['notes'] ?? '') ?>">
                          <input type="number" name="events[sort_order][]" class="form-input" placeholder="#" value="<?= (int)($e['sort_order'] ?? 0) ?>">
                        </div>
                        <?php endforeach; else: ?>
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
                        <div class="grid grid-cols-3 gap-2 link_row">
                          <select name="links[label][]" class="form-input">
                            <?php $labOpts=['Apply Online','Admit Card Download','Answer Key Download','Result Download','Download Notification','Official Website','Other']; $sel=htmlspecialchars($l['label']); foreach($labOpts as $o): ?>
                            <option value="<?= $o ?>" <?= $sel===$o?'selected':'' ?>><?= $o ?></option>
                            <?php endforeach; ?>
                          </select>
                          <input type="url" name="links[url][]" class="form-input" placeholder="https://..." value="<?= htmlspecialchars($l['url'] ?? '') ?>">
                          <input type="number" name="links[sort_order][]" class="form-input" placeholder="#" value="<?= (int)($l['sort_order'] ?? 0) ?>">
                        </div>
                        <?php endforeach; else: ?>
                        <div class="grid grid-cols-3 gap-2 link_row">
                          <select name="links[label][]" class="form-input">
                            <option value="Apply Online">Apply Online</option>
                            <option value="Admit Card Download">Admit Card Download</option>
                            <option value="Answer Key Download">Answer Key Download</option>
                            <option value="Result Download">Result Download</option>
                            <option value="Download Notification">Download Notification</option>
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

                    <!-- Fees Repeater -->
                    <div class="mt-8">
                      <h3 class="text-lg font-semibold mb-3">Application Fees</h3>
                      <div id="fees_wrap" class="space-y-3">
                        <?php if (!empty($fees)): foreach ($fees as $f): ?>
                        <div class="grid grid-cols-5 gap-2 fee_row">
                          <select name="fees[category][]" class="form-input">
                            <?php $catOpts=['General','OBC','EWS','SC','ST','Female','PH']; $sel=htmlspecialchars($f['category']); foreach($catOpts as $o): ?>
                            <option value="<?= $o ?>" <?= $sel===$o?'selected':'' ?>><?= $o ?></option>
                            <?php endforeach; ?>
                          </select>
                          <input type="number" step="0.01" name="fees[amount][]" class="form-input" placeholder="Amount" value="<?= htmlspecialchars($f['amount'] ?? '') ?>">
                          <input type="text" name="fees[text][]" class="form-input" placeholder="Text (e.g., Nil)" value="<?= htmlspecialchars($f['text'] ?? '') ?>">
                          <input type="text" name="fees[mode_notes][]" class="form-input" placeholder="Mode (UPI/NetBanking)" value="<?= htmlspecialchars($f['mode_notes'] ?? '') ?>">
                          <input type="number" name="fees[sort_order][]" class="form-input" placeholder="#" value="<?= (int)($f['sort_order'] ?? 0) ?>">
                        </div>
                        <?php endforeach; else: ?>
                        <div class="grid grid-cols-5 gap-2 fee_row">
                          <select name="fees[category][]" class="form-input">
                            <option>General</option>
                            <option>OBC</option>
                            <option>EWS</option>
                            <option>SC</option>
                            <option>ST</option>
                            <option>Female</option>
                            <option>PH</option>
                          </select>
                          <input type="number" step="0.01" name="fees[amount][]" class="form-input" placeholder="Amount">
                          <input type="text" name="fees[text][]" class="form-input" placeholder="Text (e.g., Nil)">
                          <input type="text" name="fees[mode_notes][]" class="form-input" placeholder="Mode (UPI/NetBanking)">
                          <input type="number" name="fees[sort_order][]" class="form-input" placeholder="#" value="0">
                        </div>
                        <?php endif; ?>
                      </div>
                      <button type="button" class="btn btn-secondary mt-2" id="add_fee">+ Add Fee</button>
                    </div>

                    <!-- Age Limit Block -->
                    <div class="mt-8 grid grid-cols-1 md:grid-cols-4 gap-2">
                      <div>
                        <label class="form-label">Min Age</label>
                        <input type="number" name="age_block[min_age]" class="form-input" min="0" value="<?= htmlspecialchars($age['min_age'] ?? '') ?>">
                      </div>
                      <div>
                        <label class="form-label">Max Age</label>
                        <input type="number" name="age_block[max_age]" class="form-input" min="0" value="<?= htmlspecialchars($age['max_age'] ?? '') ?>">
                      </div>
                      <div>
                        <label class="form-label">As on (Cutoff Date)</label>
                        <input type="date" name="age_block[cutoff_date]" class="form-input" value="<?= htmlspecialchars($age['cutoff_date'] ?? '') ?>">
                      </div>
                      <div class="md:col-span-1 md:col-start-1 md:col-end-5">
                        <label class="form-label">Relaxation Notes</label>
                        <input type="text" name="age_block[relaxation_text]" class="form-input" placeholder="As per rules..." value="<?= htmlspecialchars($age['relaxation_text'] ?? '') ?>">
                      </div>
                    </div>

                    <!-- Vacancies Repeater -->
                    <div class="mt-8">
                      <h3 class="text-lg font-semibold mb-3">Vacancy Details</h3>
                      <div id="vacancies_wrap" class="space-y-3">
                        <?php if (!empty($vacancies)): foreach ($vacancies as $v): ?>
                        <div class="grid grid-cols-6 gap-2 vac_row">
                          <input type="text" name="vacancies[post_name][]" class="form-input" placeholder="Post Name" value="<?= htmlspecialchars($v['post_name'] ?? '') ?>">
                          <input type="text" name="vacancies[category][]" class="form-input" placeholder="Category/Discipline" value="<?= htmlspecialchars($v['category'] ?? '') ?>">
                          <input type="number" name="vacancies[total_posts][]" class="form-input" placeholder="Total" value="<?= htmlspecialchars($v['total_posts'] ?? '') ?>">
                          <input type="text" name="vacancies[eligibility_text][]" class="form-input" placeholder="Eligibility" value="<?= htmlspecialchars($v['eligibility_text'] ?? '') ?>">
                          <input type="text" name="vacancies[pay_scale][]" class="form-input" placeholder="Pay Scale" value="<?= htmlspecialchars($v['pay_scale'] ?? '') ?>">
                          <input type="number" name="vacancies[sort_order][]" class="form-input" placeholder="#" value="<?= (int)($v['sort_order'] ?? 0) ?>">
                        </div>
                        <?php endforeach; else: ?>
                        <div class="grid grid-cols-6 gap-2 vac_row">
                          <input type="text" name="vacancies[post_name][]" class="form-input" placeholder="Post Name">
                          <input type="text" name="vacancies[category][]" class="form-input" placeholder="Category/Discipline">
                          <input type="number" name="vacancies[total_posts][]" class="form-input" placeholder="Total">
                          <input type="text" name="vacancies[eligibility_text][]" class="form-input" placeholder="Eligibility">
                          <input type="text" name="vacancies[pay_scale][]" class="form-input" placeholder="Pay Scale">
                          <input type="number" name="vacancies[sort_order][]" class="form-input" placeholder="#" value="0">
                        </div>
                        <?php endif; ?>
                      </div>
                      <button type="button" class="btn btn-secondary mt-2" id="add_vac">+ Add Vacancy Row</button>
                    </div>

                    <!-- Sections: How to Apply, Mode of Exam -->
                    <?php 
                      $how = '';
                      $mode = '';
                      foreach ($sections as $s) {
                        if (($s['section_type'] ?? '') === 'how_to_apply') $how = $s['content'] ?? '';
                        if (($s['section_type'] ?? '') === 'mode_of_exam') $mode = $s['content'] ?? '';
                      }
                    ?>
                    <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div>
                        <label class="form-label">How to Apply</label>
                        <textarea id="sections_how_to_apply" name="sections[how_to_apply]" rows="4" class="form-input richtext"><?= htmlspecialchars($how) ?></textarea>
                      </div>
                      <div>
                        <label class="form-label">Mode of Exam</label>
                        <textarea id="sections_mode_of_exam" name="sections[mode_of_exam]" rows="4" class="form-input richtext"><?= htmlspecialchars($mode) ?></textarea>
                      </div>
                    </div>

                    <!-- FAQs Repeater -->
                    <div class="mt-8">
                      <h3 class="text-lg font-semibold mb-3">FAQs</h3>
                      <div id="faqs_wrap" class="space-y-3">
                        <?php if (!empty($faqs)): foreach ($faqs as $f): ?>
                        <div class="grid grid-cols-5 gap-2 faq_row">
                          <input type="text" name="faqs[question][]" class="form-input" placeholder="Question" value="<?= htmlspecialchars($f['question'] ?? '') ?>">
                          <input type="text" name="faqs[answer][]" class="form-input" placeholder="Answer" value="<?= htmlspecialchars($f['answer'] ?? '') ?>">
                          <input type="number" name="faqs[sort_order][]" class="form-input" placeholder="#" value="<?= (int)($f['sort_order'] ?? 0) ?>">
                          <label class="inline-flex items-center gap-2"><input type="checkbox" name="faqs[is_active][]" <?= ((int)($f['is_active'] ?? 1)) ? 'checked' : '' ?>> Active</label>
                          <div></div>
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
                            <i class="fas fa-save mr-2"></i>Update Job
                        </button>
                        
                        <a href="jobs.php" class="btn btn-secondary">
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
                  function cloneRow(wrapperId, rowClass){
                    const wrap = document.getElementById(wrapperId);
                    if (!wrap) return;
                    const first = wrap.querySelector('.' + rowClass);
                    if (!first) return;
                    const node = first.cloneNode(true);
                    node.querySelectorAll('input').forEach(i=>{ if(i.type==='checkbox'){ i.checked=true; } else { i.value=''; }});
                    node.querySelectorAll('select').forEach(s=>{ s.selectedIndex = 0; });
                    wrap.appendChild(node);
                  }
                  document.getElementById('add_event')?.addEventListener('click', ()=> cloneRow('events_wrap','ev_row'));
                  document.getElementById('add_link')?.addEventListener('click', ()=> cloneRow('links_wrap','link_row'));
                  document.getElementById('add_fee')?.addEventListener('click', ()=> cloneRow('fees_wrap','fee_row'));
                  document.getElementById('add_vac')?.addEventListener('click', ()=> cloneRow('vacancies_wrap','vac_row'));
                  document.getElementById('add_faq')?.addEventListener('click', ()=> cloneRow('faqs_wrap','faq_row'));
                })();
                </script>
            </div>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>