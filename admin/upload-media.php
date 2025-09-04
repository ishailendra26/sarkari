<?php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/helpers.php';
requireLogin();
$pageTitle = 'Upload Media';
include __DIR__ . '/includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
  <div class="bg-white shadow rounded p-6">
    <h1 class="text-2xl font-semibold mb-4">Upload Media</h1>
    <p class="text-gray-600 mb-6">Upload images or documents and get a public URL you can paste anywhere in the admin.</p>

    <div class="space-y-4">
      <div>
        <label class="form-label">Choose File</label>
        <input type="file" id="media_file" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" class="form-input p-1" />
        <p class="text-sm text-gray-500 mt-1">Allowed: jpg, jpeg, png, pdf, doc, docx. Max 5MB.</p>
      </div>

      <div class="flex items-center gap-3">
        <button type="button" id="media_upload_btn" class="btn btn-primary">
          <i class="fas fa-upload mr-2"></i>Upload
        </button>
        <span id="media_status" class="text-sm text-gray-500"></span>
      </div>

      <div id="media_result" class="hidden">
        <label class="form-label">File URL</label>
        <div class="flex gap-3 items-center">
          <input type="url" id="media_url" class="form-input" readonly />
          <button type="button" id="copy_btn" class="btn btn-secondary"><i class="fas fa-copy mr-2"></i>Copy</button>
        </div>
        <div id="media_preview_wrap" class="mt-3 hidden">
          <img id="media_preview" src="" alt="Preview" class="h-32 rounded border object-cover" />
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  const fileInput = document.getElementById('media_file');
  const btn = document.getElementById('media_upload_btn');
  const statusEl = document.getElementById('media_status');
  const resultWrap = document.getElementById('media_result');
  const urlInput = document.getElementById('media_url');
  const copyBtn = document.getElementById('copy_btn');
  const prevWrap = document.getElementById('media_preview_wrap');
  const prevImg = document.getElementById('media_preview');

  if (!btn) return;

  btn.addEventListener('click', async function(){
    if (!fileInput.files || !fileInput.files[0]) { statusEl.textContent = 'Choose a file first'; return; }
    statusEl.textContent = 'Uploading...';
    resultWrap.classList.add('hidden');
    prevWrap.classList.add('hidden');

    const fd = new FormData();
    fd.append('file', fileInput.files[0]);
    try {
      const res = await fetch('upload.php', { method: 'POST', body: fd, credentials: 'same-origin' });
      const data = await res.json();
      if (data && data.success && data.url) {
        statusEl.textContent = 'Uploaded';
        urlInput.value = data.url;
        resultWrap.classList.remove('hidden');
        const ext = ((data.filename || '').split('.').pop() || '').toLowerCase();
        if (['jpg','jpeg','png'].includes(ext)) {
          prevImg.src = data.url;
          prevWrap.classList.remove('hidden');
        }
      } else {
        statusEl.textContent = (data && data.error) ? data.error : 'Upload failed';
      }
    } catch (e) {
      statusEl.textContent = 'Upload error';
    }
  });

  copyBtn.addEventListener('click', function() {
    if (!urlInput.value) return;
    urlInput.select();
    urlInput.setSelectionRange(0, 99999);
    try {
      document.execCommand('copy');
      statusEl.textContent = 'URL copied!';
    } catch (e) {
      navigator.clipboard.writeText(urlInput.value).then(() => {
        statusEl.textContent = 'URL copied!';
      }).catch(() => {
        statusEl.textContent = 'Copy failed';
      });
    }
  });
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
